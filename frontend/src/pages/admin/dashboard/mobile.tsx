import { useQuery } from '@tanstack/react-query';
import { Users, Clock, Calendar, DollarSign, AlertCircle, CheckCircle } from 'lucide-react';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { MobilePageHeader } from '@/components/mobile';
import { getDashboardData } from '@/lib/api/dashboard';

export function MobileAdminDashboard() {
    // Fetch dashboard stats
    const { data: dashboardData } = useQuery({
        queryKey: ['admin', 'dashboard-stats'],
        queryFn: getDashboardData,
    });

    const stats = dashboardData?.summary;

    return (
        <div className="min-h-screen bg-background pb-24">
            <MobilePageHeader
                title="Dashboard Admin"
                gradient="blue"
                subtitle={format(new Date(), 'EEEE, d MMMM yyyy', { locale: id })}
            />

            <div className="px-4 space-y-4">
            {/* Stats Cards */}
            <div className="grid grid-cols-2 gap-3">
                <div className="p-4 rounded-2xl bg-blue-50 dark:bg-blue-900/20 shadow-sm dark:border dark:border-border/50">
                    <div className="flex items-center gap-2 mb-1">
                        <Users className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        <span className="text-xs font-medium text-blue-700 dark:text-blue-400">Karyawan</span>
                    </div>
                    <p className="text-2xl font-bold text-blue-700 dark:text-blue-300">{stats?.employees.total || 0}</p>
                    <p className="text-xs text-blue-600 dark:text-blue-400">{stats?.employees.active || 0} aktif</p>
                </div>

                <div className="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 shadow-sm dark:border dark:border-border/50">
                    <div className="flex items-center gap-2 mb-1">
                        <Clock className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        <span className="text-xs font-medium text-emerald-700 dark:text-emerald-400">Hadir</span>
                    </div>
                    <p className="text-2xl font-bold text-emerald-700 dark:text-emerald-300">{stats?.attendance.present || 0}</p>
                    <p className="text-xs text-emerald-600 dark:text-emerald-400">{stats?.attendance.late || 0} terlambat</p>
                </div>

                <div className="p-4 rounded-2xl bg-amber-50 dark:bg-amber-900/20 shadow-sm dark:border dark:border-border/50">
                    <div className="flex items-center gap-2 mb-1">
                        <Calendar className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                        <span className="text-xs font-medium text-amber-700 dark:text-amber-400">Cuti</span>
                    </div>
                    <p className="text-2xl font-bold text-amber-700 dark:text-amber-300">{stats?.leave?.pending || 0}</p>
                    <p className="text-xs text-amber-600 dark:text-amber-400">Pending</p>
                </div>

                <div className="p-4 rounded-2xl bg-violet-50 dark:bg-violet-900/20 shadow-sm dark:border dark:border-border/50">
                    <div className="flex items-center gap-2 mb-1">
                        <DollarSign className="h-4 w-4 text-violet-600 dark:text-violet-400" />
                        <span className="text-xs font-medium text-violet-700 dark:text-violet-400">Payroll</span>
                    </div>
                    <p className="text-2xl font-bold text-violet-700 dark:text-violet-300">{stats?.payroll?.pending || 0}</p>
                    <p className="text-xs text-violet-600 dark:text-violet-400">Pending</p>
                </div>
            </div>

            {/* Pending Approvals List */}
            <div className="space-y-3">
                <h2 className="text-sm font-bold text-foreground">Perlu Approval</h2>

                <div className="space-y-3">
                    {/* Leave Requests */}
                    <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-xl">
                                <Calendar className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div>
                                <h3 className="font-semibold text-sm">Pengajuan Cuti</h3>
                                <p className="text-xs text-muted-foreground">{stats?.leave?.pending || 0} menunggu approval</p>
                            </div>
                        </div>
                        <AlertCircle className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                    </div>

                    {/* Attendance Corrections */}
                    <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
                                <Clock className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <h3 className="font-semibold text-sm">Koreksi Absensi</h3>
                                <p className="text-xs text-muted-foreground">3 menunggu approval</p>
                            </div>
                        </div>
                        <AlertCircle className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                    </div>

                    {/* Payroll */}
                    <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-violet-100 dark:bg-violet-900/30 rounded-xl">
                                <DollarSign className="h-5 w-5 text-violet-600 dark:text-violet-400" />
                            </div>
                            <div>
                                <h3 className="font-semibold text-sm">Payroll Pending</h3>
                                <p className="text-xs text-muted-foreground">{stats?.payroll?.pending || 0} perlu diproses</p>
                            </div>
                        </div>
                        <AlertCircle className="h-5 w-5 text-violet-600 dark:text-violet-400" />
                    </div>
                </div>
            </div>

            {/* Recent Activity */}
            <div className="space-y-3">
                <h2 className="text-sm font-bold text-foreground">Aktivitas Terbaru</h2>

                <div className="space-y-3">
                    {[
                        { name: 'John Doe', action: 'Check-in', time: '08:00', status: 'success' },
                        { name: 'Jane Smith', action: 'Check-in', time: '08:15', status: 'success' },
                        { name: 'Bob Johnson', action: 'Pengajuan cuti', time: '09:30', status: 'pending' },
                        { name: 'Alice Brown', action: 'Check-in', time: '09:45', status: 'late' },
                    ].map((activity, index) => (
                        <div key={index} className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-3 flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className={`p-2 rounded-full ${activity.status === 'success' ? 'bg-emerald-100 dark:bg-emerald-900/30' :
                                    activity.status === 'late' ? 'bg-amber-100 dark:bg-amber-900/30' :
                                        'bg-blue-100 dark:bg-blue-900/30'
                                    }`}>
                                    {activity.status === 'success' ? (
                                        <CheckCircle className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                    ) : activity.status === 'late' ? (
                                        <AlertCircle className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                    ) : (
                                        <Clock className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                    )}
                                </div>
                                <div>
                                    <p className="text-sm font-medium">{activity.name}</p>
                                    <p className="text-xs text-muted-foreground">{activity.action}</p>
                                </div>
                            </div>
                            <span className="text-xs text-muted-foreground">{activity.time}</span>
                        </div>
                    ))}
                </div>
            </div>
            </div>
        </div>
    );
}
