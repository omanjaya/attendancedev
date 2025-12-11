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
import { SearchBar } from '@/components/shared';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
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
            case 'present': return 'bg-green-100 text-green-700 border-green-200';
            case 'late': return 'bg-yellow-100 text-yellow-700 border-yellow-200';
            case 'absent': return 'bg-red-100 text-red-700 border-red-200';
            case 'leave': return 'bg-blue-100 text-blue-700 border-blue-200';
            case 'pending': return 'bg-orange-100 text-orange-700 border-orange-200';
            default: return 'bg-gray-100 text-gray-700 border-gray-200';
        }
    };

    const isLoading = statsLoading || recordsLoading;

    return (
        <div className="min-h-screen bg-background pb-20">
            {/* Header */}
            <div className="sticky top-0 z-10 bg-background/80 backdrop-blur-md border-b p-4">
                <div className="flex items-center justify-between mb-4">
                    <div>
                        <h1 className="text-lg font-bold">Kelola Absensi</h1>
                        <p className="text-xs text-muted-foreground">
                            {format(selectedDate, 'EEEE, d MMMM yyyy', { locale: id })}
                        </p>
                    </div>
                    <Sheet>
                        <SheetTrigger asChild>
                            <Button variant="outline" size="icon">
                                <Filter className="h-4 w-4" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent>
                            <SheetHeader>
                                <SheetTitle>Filter Absensi</SheetTitle>
                            </SheetHeader>
                            <div className="space-y-4 mt-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Status</label>
                                    <select
                                        className="w-full p-2 border rounded-md"
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
                </div>

                {/* Search */}
                <SearchBar
                    value={searchQuery}
                    onChange={setSearchQuery}
                    placeholder="Cari karyawan..."
                />
            </div>

            {/* Quick Stats */}
            <div className="grid grid-cols-2 gap-3 p-4">
                <div className="p-3 rounded-xl bg-green-50 border border-green-100">
                    <div className="flex items-center gap-2 mb-1">
                        <CheckCircle className="h-4 w-4 text-green-600" />
                        <span className="text-xs font-medium text-green-700">Hadir</span>
                    </div>
                    <p className="text-2xl font-bold text-green-700">{stats?.presentToday || 0}</p>
                </div>
                <div className="p-3 rounded-xl bg-orange-50 border border-orange-100">
                    <div className="flex items-center gap-2 mb-1">
                        <AlertCircle className="h-4 w-4 text-orange-600" />
                        <span className="text-xs font-medium text-orange-700">Terlambat</span>
                    </div>
                    <p className="text-2xl font-bold text-orange-700">{stats?.lateToday || 0}</p>
                </div>
            </div>

            {/* List */}
            <div className="px-4 space-y-3">
                {isLoading ? (
                    <div className="text-center py-8 text-muted-foreground">
                        <Loader2 className="h-6 w-6 animate-spin mx-auto mb-2" />
                        Memuat data...
                    </div>
                ) : !attendanceRecords?.length ? (
                    <div className="text-center py-8 text-muted-foreground">
                        Tidak ada data absensi untuk tanggal ini
                    </div>
                ) : attendanceRecords?.map((record) => (
                    <div
                        key={record.id}
                        className="bg-card border rounded-xl p-4 shadow-sm space-y-3"
                    >
                        <div className="flex items-start justify-between">
                            <div>
                                <h3 className="font-semibold">{record.employee.name}</h3>
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
                            <div className="pt-2 border-t flex gap-2">
                                <Button
                                    className="flex-1 h-8 text-xs bg-green-600 hover:bg-green-700"
                                    onClick={() => approveMutation.mutate(record.id)}
                                    disabled={approveMutation.isPending}
                                >
                                    {approveMutation.isPending ? <Loader2 className="h-3 w-3 animate-spin" /> : 'Approve'}
                                </Button>
                                <Button
                                    variant="outline"
                                    className="flex-1 h-8 text-xs text-red-600 border-red-200 hover:bg-red-50"
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
    );
}
