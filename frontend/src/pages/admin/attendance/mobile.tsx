import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
    Clock,
    Filter,
    CheckCircle,
    AlertCircle,
    Loader2,
} from 'lucide-react';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { MobilePageHeader, MobileEmptyState } from '@/components/mobile';
import { SearchBar } from '@/components/shared';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { useNotificationStore } from '@/stores';
import {
    getAdminAttendanceStats,
    getAdminAttendanceRecords,
    approveAttendance,
    rejectAttendance,
} from '@/lib/api/attendance';

export function MobileAdminAttendancePage() {
    const queryClient = useQueryClient();
    const { success, error: showError } = useNotificationStore();
    const [searchQuery, setSearchQuery] = useState('');
    const [filterStatus, setFilterStatus] = useState<string>('all');
    const [selectedDate, setSelectedDate] = useState(new Date());

    const dateStr = format(selectedDate, 'yyyy-MM-dd');

    // Fetch attendance statistics
    const { data: stats, isLoading: statsLoading } = useQuery({
        queryKey: ['admin', 'attendance-stats', dateStr],
        queryFn: () => getAdminAttendanceStats(dateStr),
    });

    // Fetch attendance records
    const { data: attendanceRecords, isLoading: recordsLoading } = useQuery({
        queryKey: ['admin', 'attendance-records', dateStr, searchQuery, filterStatus],
        queryFn: () => getAdminAttendanceRecords({
            date: dateStr,
            search: searchQuery || undefined,
            status: filterStatus !== 'all' ? filterStatus : undefined,
        }),
    });

    // Approve mutation
    const approveMutation = useMutation({
        mutationFn: (id: number) => approveAttendance(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['admin', 'attendance-records'] });
            queryClient.invalidateQueries({ queryKey: ['admin', 'attendance-stats'] });
            success('Berhasil', 'Absensi berhasil disetujui');
        },
        onError: () => {
            showError('Gagal', 'Gagal menyetujui absensi');
        },
    });

    // Reject mutation
    const rejectMutation = useMutation({
        mutationFn: (id: number) => rejectAttendance(id, 'Ditolak oleh admin'),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['admin', 'attendance-records'] });
            queryClient.invalidateQueries({ queryKey: ['admin', 'attendance-stats'] });
            success('Berhasil', 'Absensi berhasil ditolak');
        },
        onError: () => {
            showError('Gagal', 'Gagal menolak absensi');
        },
    });

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'present': return 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800';
            case 'late': return 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800';
            case 'absent': return 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800';
            case 'leave': return 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800';
            case 'pending': return 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800';
            default: return 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700';
        }
    };

    const isLoading = statsLoading || recordsLoading;

    if (isLoading) {
        return (
            <div className="min-h-screen bg-background pb-24">
                <MobilePageHeader title="Kelola Absensi" gradient="emerald" backTo="/admin/dashboard" />
                <div className="px-4 space-y-4">
                    <div className="grid grid-cols-2 gap-3">
                        <Skeleton className="h-20 rounded-2xl" />
                        <Skeleton className="h-20 rounded-2xl" />
                    </div>
                    {[1, 2, 3].map((i) => (
                        <Skeleton key={i} className="h-32 rounded-2xl" />
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-background pb-24">
            <MobilePageHeader
                title="Kelola Absensi"
                gradient="emerald"
                backTo="/admin/dashboard"
                subtitle={format(selectedDate, 'EEEE, d MMMM yyyy', { locale: id })}
                rightAction={
                    <Sheet>
                        <SheetTrigger asChild>
                            <button className="p-2 hover:bg-white/10 rounded-full transition-colors">
                                <Filter className="h-5 w-5 text-white" />
                            </button>
                        </SheetTrigger>
                        <SheetContent>
                            <SheetHeader>
                                <SheetTitle>Filter Absensi</SheetTitle>
                            </SheetHeader>
                            <div className="space-y-4 mt-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Status</label>
                                    <select
                                        className="w-full p-2 border rounded-md bg-background"
                                        value={filterStatus}
                                        onChange={(e) => setFilterStatus(e.target.value)}
                                    >
                                        <option value="all">Semua</option>
                                        <option value="present">Hadir</option>
                                        <option value="late">Terlambat</option>
                                        <option value="absent">Tidak Hadir</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Tanggal</label>
                                    <Input
                                        type="date"
                                        value={format(selectedDate, 'yyyy-MM-dd')}
                                        onChange={(e) => setSelectedDate(parseISO(e.target.value))}
                                    />
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                }
            />

            <div className="px-4 space-y-4">
                {/* Search */}
                <SearchBar
                    value={searchQuery}
                    onChange={setSearchQuery}
                    placeholder="Cari karyawan..."
                />

                {/* Quick Stats */}
                <div className="grid grid-cols-2 gap-3">
                    <div className="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 shadow-sm dark:border dark:border-border/50">
                        <div className="flex items-center gap-2 mb-1">
                            <CheckCircle className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                            <span className="text-xs font-medium text-emerald-700 dark:text-emerald-400">Hadir</span>
                        </div>
                        <p className="text-2xl font-bold text-emerald-700 dark:text-emerald-300">{stats?.presentToday || 0}</p>
                    </div>
                    <div className="p-4 rounded-2xl bg-amber-50 dark:bg-amber-900/20 shadow-sm dark:border dark:border-border/50">
                        <div className="flex items-center gap-2 mb-1">
                            <AlertCircle className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                            <span className="text-xs font-medium text-amber-700 dark:text-amber-400">Terlambat</span>
                        </div>
                        <p className="text-2xl font-bold text-amber-700 dark:text-amber-300">{stats?.lateToday || 0}</p>
                    </div>
                </div>

            {/* List */}
            <div className="space-y-3">
                {!attendanceRecords?.length ? (
                    <MobileEmptyState
                        icon={Clock}
                        title="Tidak Ada Data"
                        description="Tidak ada data absensi untuk tanggal ini"
                    />
                ) : attendanceRecords?.map((record) => (
                    <div
                        key={record.id}
                        className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4 space-y-3"
                    >
                        <div className="flex items-start justify-between">
                            <div>
                                <h3 className="font-semibold text-sm">{record.employee.name}</h3>
                                <p className="text-xs text-muted-foreground">#{record.employee.employeeId}</p>
                            </div>
                            <Badge variant="outline" className={getStatusColor(record.status)}>
                                {record.status === 'present' ? 'Hadir' :
                                    record.status === 'late' ? 'Terlambat' :
                                        record.status === 'absent' ? 'Alpha' :
                                            record.status === 'pending' ? 'Pending' : record.status}
                            </Badge>
                        </div>

                        <div className="grid grid-cols-2 gap-2 text-sm">
                            <div className="flex items-center gap-2 text-muted-foreground">
                                <Clock className="h-4 w-4" />
                                <span>In: {record.checkIn || '-'}</span>
                            </div>
                            <div className="flex items-center gap-2 text-muted-foreground">
                                <Clock className="h-4 w-4" />
                                <span>Out: {record.checkOut || '-'}</span>
                            </div>
                        </div>

                        {record.status === 'pending' && (
                            <div className="pt-2 border-t border-border/50 flex gap-2">
                                <Button
                                    className="flex-1 h-8 text-xs bg-emerald-600 hover:bg-emerald-700"
                                    onClick={() => approveMutation.mutate(record.id)}
                                    disabled={approveMutation.isPending}
                                >
                                    {approveMutation.isPending ? <Loader2 className="h-3 w-3 animate-spin" /> : 'Approve'}
                                </Button>
                                <Button
                                    variant="outline"
                                    className="flex-1 h-8 text-xs text-red-600 border-red-200 hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-900/20"
                                    onClick={() => rejectMutation.mutate(record.id)}
                                    disabled={rejectMutation.isPending}
                                >
                                    {rejectMutation.isPending ? <Loader2 className="h-3 w-3 animate-spin" /> : 'Reject'}
                                </Button>
                            </div>
                        )}
                    </div>
                ))}
            </div>
            </div>
        </div>
    );
}
