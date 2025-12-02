import { useState, useEffect } from 'react';
import { useNavigate } from '@tanstack/react-router';
import {
    Users,
    UserCheck,
    CalendarOff,
    UserX,
    Clock,
    Calendar,
    FileText,
    DollarSign,
    Bell,
    Briefcase,
    Target,
    Award,
    LogIn,
    LogOut,
} from 'lucide-react';
import { Card } from '@/components/ui/card';
import { useAuthStore } from '@/stores';
import { useDashboard } from '@/hooks';
import { cn } from '@/lib/utils';
import { LoadingState } from '@/components/states';
import { StatsGrid, type StatItem } from '@/components/shared';

export function MobileDashboard() {
    const navigate = useNavigate();
    const { user } = useAuthStore();
    const { data: dashboardData, isLoading } = useDashboard();
    const [currentTime, setCurrentTime] = useState('');
    const [currentDate, setCurrentDate] = useState('');

    useEffect(() => {
        const updateTime = () => {
            const now = new Date();
            setCurrentTime(
                now.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                })
            );
            setCurrentDate(
                now.toLocaleDateString('id-ID', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                })
            );
        };

        updateTime();
        const interval = setInterval(updateTime, 1000);
        return () => clearInterval(interval);
    }, []);

    if (isLoading) {
        return <LoadingState message="Memuat dashboard..." />;
    }

    const { summary, today_schedule, recent_activity } = dashboardData || {};
    const isAdmin = user?.role === 'super-admin' || user?.role === 'admin';
    const isManager = user?.role === 'kepala-sekolah';
    const isEmployee = user?.role === 'guru' || user?.role === 'pegawai';

    // Get user initials for avatar
    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    // Stats Logic (Same as Desktop)
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
            label: 'Kehadiran',
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
            description: 'hari',
            icon: CalendarOff,
            color: 'primary',
        },
        {
            label: 'Jam Kerja',
            value: 176,
            description: 'jam',
            icon: Clock,
            color: 'primary',
        },
    ];

    const stats = isAdmin || isManager ? adminStats : employeeStats;

    return (
        <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-20">
            {/* Header Wrapper */}
            <div className="px-4 pt-3 pb-3">
                <div className="bg-card/80 dark:bg-card/60 backdrop-blur-md rounded-3xl p-1.5 shadow-xl border border-border/40 dark:border-border/30">
                    {/* Header Section */}
                    <div className="bg-gradient-to-br from-primary/95 via-primary to-primary/90 dark:from-primary/90 dark:via-primary/95 dark:to-primary px-5 pt-8 pb-4 space-y-2.5 shadow-lg shadow-primary/10 rounded-[20px]">
                        {/* User Info */}
                        <div className="flex items-center gap-3">
                            <div className="h-11 w-11 rounded-full bg-gradient-to-br from-white/20 to-white/10 backdrop-blur-sm flex items-center justify-center text-white font-bold shadow-lg border-2 border-white/20">
                                {user?.name ? getInitials(user.name) : 'U'}
                            </div>
                            <div className="flex-1">
                                <p className="text-xs text-white/70">Selamat Datang</p>
                                <p className="text-white font-semibold text-sm">
                                    {user?.name || 'User'}
                                </p>
                            </div>
                            <button className="h-10 w-10 rounded-full bg-white/10 backdrop-blur-sm flex items-center justify-center text-white hover:bg-white/20 transition-colors">
                                <Bell className="h-5 w-5" />
                            </button>
                        </div>

                        {/* Date Time Card */}
                        <div className="bg-white/10 backdrop-blur-md rounded-xl px-3 py-2 flex items-center gap-2.5 border border-white/20 shadow-sm">
                            <div className="flex items-center gap-2 flex-1">
                                <Calendar className="h-3.5 w-3.5 text-white/80" />
                                <span className="text-xs text-white/90">{currentDate}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Clock className="h-3.5 w-3.5 text-white/80" />
                                <span className="text-xs text-white/90 font-mono">{currentTime}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Main Content */}
            <div className="px-4 space-y-4">
                {/* Stats Grid - Mobile Optimized (2 columns) */}
                <StatsGrid stats={stats} columns={2} variant="cards" />

                {/* Today's Schedule Card (Employee Only) */}
                {isEmployee && (
                    <div className="bg-background dark:bg-gray-900 rounded-3xl px-4 py-4 shadow-lg border border-border/50">
                        <div className="flex items-center justify-between mb-3">
                            <h2 className="text-sm font-bold text-foreground flex items-center gap-2">
                                <Calendar className="h-4 w-4 text-primary" />
                                Jadwal Hari Ini
                            </h2>
                            {today_schedule && (
                                <span className="text-xs font-medium px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                    {today_schedule.shift_name}
                                </span>
                            )}
                        </div>

                        {today_schedule ? (
                            <div className="flex items-center justify-between bg-muted/30 rounded-xl p-3 border border-border/50">
                                <div className="text-center flex-1 border-r border-border/50">
                                    <p className="text-xs text-muted-foreground mb-1">Masuk</p>
                                    <p className="text-lg font-bold text-foreground">{today_schedule.start_time}</p>
                                </div>
                                <div className="text-center flex-1">
                                    <p className="text-xs text-muted-foreground mb-1">Pulang</p>
                                    <p className="text-lg font-bold text-foreground">{today_schedule.end_time}</p>
                                </div>
                            </div>
                        ) : (
                            <div className="text-center py-4 text-muted-foreground text-sm">
                                Tidak ada jadwal hari ini
                            </div>
                        )}
                    </div>
                )}

                {/* Menu Grid - Dashboard Actions */}
                <div className="bg-background dark:bg-gray-900 rounded-3xl px-4 py-4 shadow-lg border border-border/50">
                    <h2 className="text-sm font-bold text-foreground mb-3">Aksi Cepat</h2>
                    <div className="grid grid-cols-4 gap-4">
                        <MenuButton
                            icon={Clock}
                            label="Absen"
                            color="text-emerald-500"
                            onClick={() => navigate({ to: (isAdmin || isManager) ? '/admin/attendance' : '/employee/attendance' })}
                        />
                        <MenuButton
                            icon={CalendarOff}
                            label="Cuti"
                            color="text-orange-500"
                            onClick={() => navigate({ to: (isAdmin || isManager) ? '/admin/leave' : '/employee/leave' })}
                        />
                        {(isAdmin || isManager) && (
                            <>
                                <MenuButton
                                    icon={Users}
                                    label="Karyawan"
                                    color="text-blue-500"
                                    onClick={() => navigate({ to: '/admin/employees' })}
                                />
                                <MenuButton
                                    icon={Calendar}
                                    label="Jadwal"
                                    color="text-purple-500"
                                    onClick={() => navigate({ to: '/admin/schedules' })}
                                />
                                <MenuButton
                                    icon={FileText}
                                    label="Laporan"
                                    color="text-indigo-500"
                                    onClick={() => navigate({ to: '/admin/reports' })}
                                />
                            </>
                        )}
                        {isEmployee && (
                            <>
                                <MenuButton
                                    icon={Calendar}
                                    label="Jadwal"
                                    color="text-purple-500"
                                    onClick={() => navigate({ to: '/employee/schedule' })}
                                />
                                <MenuButton
                                    icon={DollarSign}
                                    label="Gaji"
                                    color="text-green-500"
                                    onClick={() => navigate({ to: '/employee/payroll' })}
                                />
                            </>
                        )}
                        <MenuButton
                            icon={Target}
                            label="Profil"
                            color="text-gray-500"
                            onClick={() => navigate({ to: '/employee/profile' })}
                        />
                    </div>
                </div>

                {/* Recent Activity */}
                <div className="bg-background dark:bg-gray-900 rounded-3xl px-4 py-4 shadow-lg border border-border/50">
                    <div className="flex items-center justify-between mb-3">
                        <h2 className="text-sm font-bold text-foreground">Aktivitas Terakhir</h2>
                        <button className="text-xs text-primary font-medium">Lihat Semua</button>
                    </div>

                    <div className="space-y-3">
                        {!recent_activity || recent_activity.length === 0 ? (
                            <p className="text-center text-sm text-muted-foreground py-4">Belum ada aktivitas</p>
                        ) : (
                            recent_activity.slice(0, 3).map((activity) => (
                                <div key={activity.id} className="flex items-start gap-3 pb-3 border-b border-border/50 last:border-0 last:pb-0">
                                    <div className={cn(
                                        "h-8 w-8 rounded-full flex items-center justify-center shrink-0 mt-0.5",
                                        activity.type === 'check_in' ? "bg-emerald-100 text-emerald-600" :
                                            activity.type === 'check_out' ? "bg-rose-100 text-rose-600" :
                                                "bg-blue-100 text-blue-600"
                                    )}>
                                        {activity.type === 'check_in' ? <LogIn className="h-4 w-4" /> :
                                            activity.type === 'check_out' ? <LogOut className="h-4 w-4" /> :
                                                <FileText className="h-4 w-4" />}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium text-foreground truncate">
                                            {activity.type === 'check_in' ? 'Check In' :
                                                activity.type === 'check_out' ? 'Check Out' :
                                                    'Aktivitas Lain'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">{activity.description}</p>
                                    </div>
                                    <span className="text-xs text-muted-foreground whitespace-nowrap">
                                        {new Date(activity.timestamp).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}
                                    </span>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

function MenuButton({ icon: Icon, label, color, onClick }: { icon: any, label: string, color: string, onClick: () => void }) {
    return (
        <button onClick={onClick} className="flex flex-col items-center gap-2 active:scale-95 transition-transform">
            <div className={cn("h-12 w-12 rounded-2xl bg-muted/50 flex items-center justify-center shadow-sm border border-border/50", color)}>
                <Icon className="h-6 w-6" />
            </div>
            <span className="text-xs font-medium text-muted-foreground">{label}</span>
        </button>
    );
}
