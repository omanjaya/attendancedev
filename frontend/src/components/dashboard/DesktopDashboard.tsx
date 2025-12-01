import { useAuthStore } from '@/stores';
import { useDashboard } from '@/hooks';
import {
    Users,
    UserCheck,
    CalendarOff,
    UserX,
    TrendingUp,
    Clock,
    Calendar,
    CheckCircle,
    AlertCircle,
    FileText,
    DollarSign,
    Briefcase,
    Target,
    Award,
    LayoutDashboard,
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { StatsGrid, PageHeader, type StatItem } from '@/components/shared';
import { ActionCard, BannerCard, ActivityCard, ActivityCardList, InfoCard } from '@/components/cards';
import { LoadingState, EmptyStateInline, StatSkeleton } from '@/components/states';
import {
    Area,
    AreaChart,
    ResponsiveContainer,
    XAxis,
    YAxis,
    Tooltip,
} from 'recharts';

export function DesktopDashboard() {
    const { user } = useAuthStore();
    const { data: dashboardData, isLoading } = useDashboard();

    if (isLoading) {
        return (
            <div className="space-y-4 p-4 sm:space-y-6 sm:p-6">
                <div className="space-y-2">
                    <div className="h-8 w-64 bg-muted animate-pulse rounded" />
                    <div className="h-4 w-48 bg-muted animate-pulse rounded" />
                </div>
                <StatSkeleton count={4} />
                <LoadingState message="Memuat data dashboard..." size="lg" />
            </div>
        );
    }

    const { summary, recent_activity, attendance_trends, today_schedule } = dashboardData || {};

    const isAdmin = user?.role === 'super-admin' || user?.role === 'admin';
    const isManager = user?.role === 'kepala-sekolah';
    const isEmployee = user?.role === 'guru' || user?.role === 'pegawai';

    const formatTime = (timestamp: string) => {
        return new Date(timestamp).toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getActivityStyle = (type: string) => {
        switch (type) {
            case 'check_in':
                return 'bg-success/10 text-success';
            case 'check_out':
                return 'bg-primary/10 text-primary';
            case 'leave_request':
                return 'bg-warning/10 text-warning';
            case 'leave_approved':
                return 'bg-success/10 text-success';
            case 'leave_rejected':
                return 'bg-destructive/10 text-destructive';
            default:
                return 'bg-muted text-muted-foreground';
        }
    };

    const getActivityLabel = (type: string) => {
        switch (type) {
            case 'check_in':
                return 'Check In';
            case 'check_out':
                return 'Check Out';
            case 'leave_request':
                return 'Cuti';
            case 'leave_approved':
                return 'Disetujui';
            case 'leave_rejected':
                return 'Ditolak';
            default:
                return type;
        }
    };

    const roleLabels: Record<string, string> = {
        'super-admin': 'Super Admin',
        'admin': 'Administrator',
        'manager': 'Manager',
        'kepala-sekolah': 'Kepala Sekolah',
        'teacher': 'Guru',
        'guru': 'Guru',
        'employee': 'Pegawai',
        'pegawai': 'Pegawai',
    };

    // Stats data using new StatItem interface
    const adminStats: StatItem[] = [
        {
            label: 'Total Karyawan',
            value: summary?.employees.total || 0,
            description: `${summary?.employees.active || 0} aktif`,
            icon: Users,
            color: 'primary',
        },
        {
            label: 'Hadir Hari Ini',
            value: summary?.attendance.present || 0,
            description: `${summary?.attendance.attendance_rate?.toFixed(1) || 0}%`,
            icon: UserCheck,
            color: 'success',
        },
        {
            label: 'Cuti / Izin',
            value: summary?.attendance.on_leave || 0,
            description: `${summary?.pending_leaves || 0} pending`,
            icon: CalendarOff,
            color: 'warning',
        },
        {
            label: 'Tidak Hadir',
            value: summary?.attendance.absent || 0,
            description: `${summary?.attendance.late || 0} terlambat`,
            icon: UserX,
            color: 'destructive',
        },
    ];

    const employeeStats: StatItem[] = [
        {
            label: 'Kehadiran Bulan Ini',
            value: 22,
            description: '95.6%',
            icon: UserCheck,
            color: 'success',
        },
        {
            label: 'Terlambat',
            value: 1,
            description: 'bulan ini',
            icon: Clock,
            color: 'warning',
        },
        {
            label: 'Sisa Cuti',
            value: 10,
            description: 'hari tersisa',
            icon: CalendarOff,
            color: 'primary',
        },
        {
            label: 'Jam Kerja',
            value: 176,
            description: 'jam bulan ini',
            icon: Clock,
            color: 'primary',
        },
    ];

    const stats = isAdmin || isManager ? adminStats : employeeStats;

    return (
        <div className="space-y-4 p-4 sm:space-y-6 sm:p-6">
            {/* Page Header */}
            <PageHeader
                title={`Selamat datang, ${user?.name?.split(' ')[0]}!`}
                description={new Date().toLocaleDateString('id-ID', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                })}
                icon={LayoutDashboard}
                actions={
                    <Badge variant="secondary">
                        {roleLabels[user?.role || ''] || user?.role}
                    </Badge>
                }
                size="lg"
            />

            {/* Stats Grid using new StatsGrid component */}
            <StatsGrid stats={stats} columns={4} variant="cards" />

            {/* Quick Check-in Banner for Employees */}
            {isEmployee && (
                <BannerCard
                    icon={Clock}
                    title="Belum Check-in Hari Ini"
                    description={`Jadwal: ${today_schedule?.start_time || '08:00'} - ${today_schedule?.end_time || '17:00'}`}
                    actionLabel="Check-in Sekarang"
                    actionHref="/face-recognition"
                    variant="primary"
                />
            )}

            {/* Pending Approvals Banner for Admins */}
            {(isAdmin || isManager) && (summary?.pending_leaves || 0) > 0 && (
                <BannerCard
                    icon={AlertCircle}
                    title={`${summary?.pending_leaves} Pengajuan Menunggu Persetujuan`}
                    description="Cuti dan izin yang perlu ditinjau"
                    actionLabel="Lihat Pengajuan"
                    actionHref="/leave"
                    variant="warning"
                    buttonVariant="outline"
                />
            )}

            {/* Content Grid */}
            <div className="grid gap-4 sm:gap-6 lg:grid-cols-3">
                {/* Chart + Activity */}
                <div className="space-y-4 sm:space-y-6 lg:col-span-2">
                    {/* Attendance Trend Chart */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="flex items-center gap-2 text-base font-semibold">
                                <TrendingUp className="h-5 w-5 text-primary" />
                                {isEmployee ? 'Kehadiran Saya (7 Hari)' : 'Tren Kehadiran (7 Hari)'}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-[200px] sm:h-[250px]">
                                <ResponsiveContainer width="100%" height="100%">
                                    <AreaChart data={attendance_trends}>
                                        <defs>
                                            <linearGradient id="colorPresent" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="5%" stopColor="var(--color-primary)" stopOpacity={0.3} />
                                                <stop offset="95%" stopColor="var(--color-primary)" stopOpacity={0} />
                                            </linearGradient>
                                        </defs>
                                        <XAxis
                                            dataKey="date"
                                            tickFormatter={(value) =>
                                                new Date(value).toLocaleDateString('id-ID', { weekday: 'short' })
                                            }
                                            tick={{ fontSize: 12 }}
                                            tickLine={false}
                                            axisLine={false}
                                        />
                                        <YAxis hide />
                                        <Tooltip
                                            content={({ active, payload, label }) => {
                                                if (active && payload && payload.length && label) {
                                                    return (
                                                        <div className="rounded-lg border bg-card p-3 shadow-lg">
                                                            <p className="mb-2 text-sm font-medium">
                                                                {new Date(String(label)).toLocaleDateString('id-ID', {
                                                                    weekday: 'long',
                                                                    day: 'numeric',
                                                                    month: 'short',
                                                                })}
                                                            </p>
                                                            {payload.map((entry, index) => (
                                                                <p key={index} className="text-xs text-muted-foreground">
                                                                    {isEmployee ? 'Jam Kerja' : 'Hadir'}: {entry.value}
                                                                </p>
                                                            ))}
                                                        </div>
                                                    );
                                                }
                                                return null;
                                            }}
                                        />
                                        <Area
                                            type="monotone"
                                            dataKey="present"
                                            stroke="var(--color-primary)"
                                            strokeWidth={2}
                                            fillOpacity={1}
                                            fill="url(#colorPresent)"
                                        />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Recent Activity */}
                    <InfoCard
                        icon={Clock}
                        title={isEmployee ? 'Aktivitas Saya' : 'Aktivitas Terbaru'}
                        action={
                            <Button variant="ghost" size="sm" className="text-primary">
                                Lihat Semua
                            </Button>
                        }
                        contentClassName="p-0"
                    >
                        {!recent_activity || recent_activity.length === 0 ? (
                            <EmptyStateInline message="Belum ada aktivitas" />
                        ) : (
                            <ActivityCardList>
                                {recent_activity.slice(0, 5).map((activity) => (
                                    <ActivityCard
                                        key={activity.id}
                                        initials={activity.employee_name
                                            .split(' ')
                                            .map((n) => n[0])
                                            .join('')
                                            .slice(0, 2)}
                                        title={isEmployee ? 'Anda' : activity.employee_name}
                                        description={activity.description}
                                        time={formatTime(activity.timestamp)}
                                        badge={{
                                            label: getActivityLabel(activity.type),
                                            className: getActivityStyle(activity.type),
                                        }}
                                    />
                                ))}
                            </ActivityCardList>
                        )}
                    </InfoCard>
                </div>

                {/* Right Column */}
                <div className="space-y-4 sm:space-y-6">
                    {/* Quick Actions */}
                    <InfoCard
                        icon={Target}
                        title="Aksi Cepat"
                        contentClassName="grid gap-2"
                    >
                        <ActionCard
                            href="/face-recognition"
                            icon={Clock}
                            title="Absen Sekarang"
                            description="Check-in / Check-out"
                            iconColor="primary"
                        />
                        <ActionCard
                            href="/leave"
                            icon={CalendarOff}
                            title={isAdmin || isManager ? 'Kelola Cuti' : 'Ajukan Cuti'}
                            description={isAdmin || isManager ? 'Review pengajuan' : 'Buat pengajuan baru'}
                            iconColor="warning"
                        />
                        {(isAdmin || isManager) && (
                            <>
                                <ActionCard
                                    href="/employees"
                                    icon={Users}
                                    title="Data Karyawan"
                                    description="Kelola karyawan"
                                    iconColor="primary"
                                />
                                <ActionCard
                                    href="/reports"
                                    icon={FileText}
                                    title="Laporan"
                                    description="Generate laporan"
                                    iconColor="info"
                                />
                            </>
                        )}
                        {isEmployee && (
                            <>
                                <ActionCard
                                    href="/payroll"
                                    icon={DollarSign}
                                    title="Slip Gaji"
                                    description="Lihat slip gaji"
                                    iconColor="success"
                                />
                                <ActionCard
                                    href="/schedules"
                                    icon={Calendar}
                                    title="Jadwal Saya"
                                    description="Lihat jadwal kerja"
                                    iconColor="info"
                                />
                            </>
                        )}
                    </InfoCard>

                    {/* Today's Schedule */}
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center gap-2 text-base font-semibold">
                                <Calendar className="h-5 w-5 text-primary" />
                                Jadwal Hari Ini
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {today_schedule ? (
                                <div className="rounded-lg bg-muted/50 p-3">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm font-medium text-foreground">
                                                {today_schedule.shift_name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {today_schedule.start_time} - {today_schedule.end_time}
                                            </p>
                                        </div>
                                        <Badge className="bg-success/10 text-success border-0">
                                            <CheckCircle className="h-3 w-3 mr-1" />
                                            Aktif
                                        </Badge>
                                    </div>
                                </div>
                            ) : (
                                <div className="rounded-lg bg-muted/50 p-6 text-center">
                                    <Calendar className="h-8 w-8 text-muted-foreground/50 mx-auto mb-2" />
                                    <p className="text-sm text-muted-foreground">Tidak ada jadwal</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Admin-only: Department Stats */}
                    {(isAdmin || isManager) && (
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="flex items-center gap-2 text-base font-semibold">
                                    <Briefcase className="h-5 w-5 text-primary" />
                                    Per Departemen
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {summary?.employees.by_department &&
                                    Object.entries(summary.employees.by_department)
                                        .slice(0, 5)
                                        .map(([dept, count]) => (
                                            <div
                                                key={dept}
                                                className="flex items-center justify-between rounded-lg bg-muted/30 px-3 py-2"
                                            >
                                                <span className="text-sm text-muted-foreground">{dept}</span>
                                                <Badge variant="secondary">{count}</Badge>
                                            </div>
                                        ))}
                                {(!summary?.employees.by_department ||
                                    Object.keys(summary.employees.by_department).length === 0) && (
                                        <div className="py-4 text-center">
                                            <p className="text-sm text-muted-foreground">Tidak ada data</p>
                                        </div>
                                    )}
                            </CardContent>
                        </Card>
                    )}

                    {/* Employee-only: Attendance Progress */}
                    {isEmployee && (
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="flex items-center gap-2 text-base font-semibold">
                                    <Award className="h-5 w-5 text-primary" />
                                    Progres Bulan Ini
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <ProgressItem label="Kehadiran" current="22/23 hari" value={95.6} />
                                <ProgressItem label="Tepat Waktu" current="21/22 hari" value={95.4} />
                                <ProgressItem label="Jam Kerja" current="176/184 jam" value={95.6} />
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </div>
    );
}

// Helper components
interface ProgressItemProps {
    label: string;
    current: string;
    value: number;
}

function ProgressItem({ label, current, value }: ProgressItemProps) {
    return (
        <div className="space-y-2">
            <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">{label}</span>
                <span className="font-medium">{current}</span>
            </div>
            <Progress value={value} className="h-2" />
        </div>
    );
}
