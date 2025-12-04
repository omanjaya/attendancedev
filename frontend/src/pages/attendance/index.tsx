import { useState } from 'react';
import {
    Clock,
    LogIn,
    LogOut,
    ScanFace,
    Filter,
    XCircle,
    UserCheck,
    CalendarOff,
    ClipboardList,
    Download,
} from 'lucide-react';
import { useIsMobile } from '@/lib/utils/device';
import { MobileAttendancePage } from './mobile';
import { CheckInFlow } from './check-in-flow';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { DataTable, PageHeader, StatsGrid, type Column, type StatItem } from '@/components/shared';
import { ResponsiveDataView } from '@/components/mobile';
import { useAttendance, useAttendanceStatistics } from '@/hooks';
import type { Attendance, AttendanceStatus, AttendanceFilters } from '@/types';
import { AttendanceBadge, StatusBadge } from '@/components/status';

const getStatusBadge = (status: AttendanceStatus) => {
    // Map attendance status to badge component
    if (status === 'present') return <AttendanceBadge status="present" />;
    if (status === 'late') return <AttendanceBadge status="late" />;
    if (status === 'absent') return <AttendanceBadge status="absent" />;
    if (status === 'leave') return <AttendanceBadge status="leave" />;
    if (status === 'holiday') return <StatusBadge label="Libur" variant="info" />;
    return <StatusBadge label={status} variant="default" />;
};

// Wrapper component to handle mobile vs desktop rendering
export default function AttendancePage() {
    const isMobile = useIsMobile();

    // Render mobile or desktop version
    return isMobile ? <MobileAttendancePage /> : <DesktopAttendancePage />;
}

// Desktop version
function DesktopAttendancePage() {
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState<string>('all');
    const [page, setPage] = useState(1);
    const [pageSize, setPageSize] = useState(10);

    const filters: AttendanceFilters = {
        status: statusFilter !== 'all' ? statusFilter as AttendanceStatus : undefined,
        page,
        per_page: pageSize,
    };
    const { data: attendanceData, isLoading: isLoadingAttendance } = useAttendance(filters);
    const { data: statistics } = useAttendanceStatistics();

    const attendanceRecords = attendanceData?.data || [];
    const filteredData = search
        ? attendanceRecords.filter((att) =>
            att.employee_name.toLowerCase().includes(search.toLowerCase())
        )
        : attendanceRecords;
    const totalPages = attendanceData?.meta?.last_page || 1;
    const totalItems = attendanceData?.meta?.total || 0;

    const columns: Column<Attendance>[] = [
        {
            key: 'employee_name',
            header: 'Karyawan',
            cell: (row) => (
                <div className="flex items-center gap-3">
                    <div className="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10">
                        <span className="text-xs font-medium text-primary">
                            {(row.employee_name || '').split(' ').map((n) => n[0]).join('').slice(0, 2)}
                        </span>
                    </div>
                    <span className="font-medium">{row.employee_name}</span>
                </div>
            ),
        },
        {
            key: 'date',
            header: 'Tanggal',
            cell: (row) => (
                <span className="text-muted-foreground">
                    {new Date(row.date).toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                    })}
                </span>
            ),
        },
        {
            key: 'check_in',
            header: 'Masuk',
            cell: (row) => (
                <div className="flex items-center gap-1">
                    <LogIn className="h-3 w-3 text-success" />
                    <span>{row.check_in || '-'}</span>
                </div>
            ),
        },
        {
            key: 'check_out',
            header: 'Keluar',
            cell: (row) => (
                <div className="flex items-center gap-1">
                    <LogOut className="h-3 w-3 text-primary" />
                    <span>{row.check_out || '-'}</span>
                </div>
            ),
        },
        {
            key: 'work_hours',
            header: 'Jam Kerja',
            cell: (row) => (
                <span>{row.work_hours ? `${row.work_hours.toFixed(1)} jam` : '-'}</span>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            cell: (row) => (
                <div className="flex items-center gap-2">
                    {getStatusBadge(row.status)}
                    {row.late_minutes && (
                        <span className="text-xs text-warning">+{row.late_minutes}m</span>
                    )}
                    {row.overtime_minutes && (
                        <span className="text-xs text-success">OT {row.overtime_minutes}m</span>
                    )}
                </div>
            ),
        },
        {
            key: 'face_verified',
            header: 'Verifikasi',
            cell: (row) => (
                <ScanFace
                    className={`h-4 w-4 ${row.face_verified ? 'text-success' : 'text-muted-foreground'}`}
                />
            ),
        },
    ];

    const stats: StatItem[] = [
        {
            label: 'Hadir',
            value: statistics?.present || 0,
            icon: UserCheck,
            color: 'success',
        },
        {
            label: 'Terlambat',
            value: statistics?.late || 0,
            icon: Clock,
            color: 'warning',
        },
        {
            label: 'Tidak Hadir',
            value: statistics?.absent || 0,
            icon: XCircle,
            color: 'destructive',
        },
        {
            label: 'Cuti',
            value: statistics?.on_leave || 0,
            icon: CalendarOff,
            color: 'primary',
        },
    ];

    return (
        <div className="space-y-4 p-4 sm:space-y-6 sm:p-6">
            {/* Page Header */}
            <PageHeader
                title="Absensi"
                description="Kelola kehadiran karyawan"
                icon={ClipboardList}
                actions={
                    <Button variant="outline" size="sm">
                        <Download className="mr-2 h-4 w-4" />
                        Export
                    </Button>
                }
            />

            {/* Stats + Check-in Widget */}
            <div className="flex flex-col gap-4 lg:grid lg:grid-cols-5">
                {/* Check-in Widget - New Auto-Capture Flow */}
                <div className="order-2 lg:order-1 lg:col-span-1">
                    <CheckInFlow />
                </div>

                {/* Stats */}
                <div className="order-1 lg:order-2 lg:col-span-4">
                    <StatsGrid stats={stats} columns={4} variant="cards" />
                </div>
            </div>

            {/* Filters */}
            <div className="flex items-center gap-4">
                <div className="flex items-center gap-2">
                    <Filter className="h-4 w-4 text-muted-foreground" />
                    <Select value={statusFilter} onValueChange={setStatusFilter}>
                        <SelectTrigger className="w-[150px]">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Status</SelectItem>
                            <SelectItem value="present">Hadir</SelectItem>
                            <SelectItem value="late">Terlambat</SelectItem>
                            <SelectItem value="absent">Tidak Hadir</SelectItem>
                            <SelectItem value="leave">Cuti</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            {/* Data Table / Mobile Cards */}
            <Card>
                <CardContent className="p-4">
                    <ResponsiveDataView
                        items={filteredData}
                        renderCard={(attendance) => (
                            <div className="space-y-2">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                                        <span className="text-sm font-medium text-primary">
                                            {(attendance.employee_name || '').split(' ').map((n) => n[0]).join('').slice(0, 2)}
                                        </span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="font-medium text-foreground">{attendance.employee_name}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {new Date(attendance.date).toLocaleDateString('id-ID', {
                                                day: 'numeric',
                                                month: 'short',
                                                year: 'numeric',
                                            })}
                                        </p>
                                    </div>
                                    {getStatusBadge(attendance.status)}
                                </div>
                                <div className="grid grid-cols-2 gap-2 pl-[52px]">
                                    <div className="flex items-center gap-1 text-sm">
                                        <LogIn className="h-3 w-3 text-success" />
                                        <span>{attendance.check_in || '-'}</span>
                                    </div>
                                    <div className="flex items-center gap-1 text-sm">
                                        <LogOut className="h-3 w-3 text-primary" />
                                        <span>{attendance.check_out || '-'}</span>
                                    </div>
                                    {attendance.work_hours && (
                                        <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                            <Clock className="h-3 w-3" />
                                            <span>{attendance.work_hours.toFixed(1)} jam</span>
                                        </div>
                                    )}
                                    {attendance.face_verified && (
                                        <div className="flex items-center gap-1 text-sm text-success">
                                            <ScanFace className="h-3 w-3" />
                                            <span>Verified</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                        desktopView={
                            <DataTable
                                columns={columns}
                                data={filteredData}
                                searchPlaceholder="Cari karyawan..."
                                searchValue={search}
                                onSearchChange={setSearch}
                                page={page}
                                pageSize={pageSize}
                                totalPages={totalPages}
                                totalItems={totalItems}
                                onPageChange={setPage}
                                onPageSizeChange={(size) => {
                                    setPageSize(size);
                                    setPage(1);
                                }}
                                emptyMessage="Tidak ada data absensi"
                                isLoading={isLoadingAttendance}
                            />
                        }
                        loading={isLoadingAttendance}
                        emptyMessage="Tidak ada data absensi"
                    />
                </CardContent>
            </Card>
        </div>
    );
}
