
import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
    Clock,
    Filter,
    CheckCircle,
    AlertCircle,
} from 'lucide-react';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { SearchBar } from '@/components/shared';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';

interface AttendanceRecord {
    id: number;
    employeeId: number;
    employeeName: string;
    employeePhoto?: string;
    date: string;
    checkIn?: string;
    checkOut?: string;
    status: 'present' | 'late' | 'absent' | 'leave' | 'pending';
    location?: string;
    notes?: string;
    needsApproval: boolean;
}

export function MobileAdminAttendancePage() {
    const [searchQuery, setSearchQuery] = useState('');
    const [filterStatus, setFilterStatus] = useState<string>('all');
    const [selectedDate, setSelectedDate] = useState(new Date());

    // Fetch attendance statistics
    const { data: stats } = useQuery({
        queryKey: ['admin', 'attendance-stats', format(selectedDate, 'yyyy-MM-dd')],
        queryFn: async () => {
            // TODO: Replace with actual API call
            return {
                presentToday: 128,
                lateToday: 12,
                absentToday: 10,
                pendingApprovals: 5,
            };
        },
    });

    // Fetch attendance records
    const { data: attendanceRecords, isLoading } = useQuery({
        queryKey: ['admin', 'attendance-records', format(selectedDate, 'yyyy-MM-dd'), searchQuery, filterStatus],
        queryFn: async () => {
            // TODO: Replace with actual API call
            return [
                {
                    id: 1,
                    employeeId: 101,
                    employeeName: 'John Doe',
                    date: '2025-12-01',
                    checkIn: '08:00:00',
                    checkOut: '17:00:00',
                    status: 'present' as const,
                    location: 'Kantor Pusat',
                    needsApproval: false,
                },
                {
                    id: 4,
                    employeeId: 104,
                    employeeName: 'Alice Brown',
                    date: '2025-12-01',
                    checkIn: '07:45:00',
                    checkOut: null,
                    status: 'pending' as const,
                    location: 'Kantor Cabang A',
                    notes: 'Koreksi check-out: sistem error',
                    needsApproval: true,
                },
            ] as AttendanceRecord[];
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
                        <span className="text-xs font-medium text-orange-700">Pending</span>
                    </div>
                    <p className="text-2xl font-bold text-orange-700">{stats?.pendingApprovals || 0}</p>
                </div>
            </div>

            {/* List */}
            <div className="px-4 space-y-3">
                {isLoading ? (
                    <div className="text-center py-8 text-muted-foreground">Memuat data...</div>
                ) : attendanceRecords?.map((record) => (
                    <div
                        key={record.id}
                        className="bg-card border rounded-xl p-4 shadow-sm space-y-3"
                    >
                        <div className="flex items-start justify-between">
                            <div>
                                <h3 className="font-semibold">{record.employeeName}</h3>
                                <p className="text-xs text-muted-foreground">#{record.employeeId}</p>
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

                        {record.needsApproval && (
                            <div className="pt-2 border-t flex gap-2">
                                <Button className="flex-1 h-8 text-xs bg-green-600 hover:bg-green-700">
                                    Approve
                                </Button>
                                <Button variant="outline" className="flex-1 h-8 text-xs text-red-600 border-red-200 hover:bg-red-50">
                                    Reject
                                </Button>
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}
