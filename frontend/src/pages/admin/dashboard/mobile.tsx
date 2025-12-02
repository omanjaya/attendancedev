import { useQuery } from '@tanstack/react-query';
import { Users, Clock, Calendar, DollarSign, AlertCircle, CheckCircle } from 'lucide-react';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';

export function MobileAdminDashboard() {
    // Fetch dashboard stats
    const { data: stats } = useQuery({
        queryKey: ['admin', 'dashboard-stats'],
        queryFn: async () => {
            // TODO: Replace with actual API call
            return {
                employees: { total: 150, active: 142, onLeave: 8 },
                attendance: { today: 128, present: 120, late: 5, absent: 3 },
                leave: { pending: 12, approved: 45, rejected: 3 },
                schedules: { upcoming: 8, active: 15, draft: 3 },
                payroll: { pending: 5, processed: 145, total: 2450000 },
            };
        },
    });

    return (
        <div className="min-h-screen bg-background pb-20">
            {/* Header */}
            <div className="sticky top-0 z-10 bg-background/80 backdrop-blur-md border-b p-4">
                <h1 className="text-lg font-bold">Dashboard Admin</h1>
                <p className="text-xs text-muted-foreground">
                    {format(new Date(), 'EEEE, d MMMM yyyy', { locale: id })}
                </p>
            </div>

            {/* Stats Cards */}
            <div className="grid grid-cols-2 gap-3 p-4">
                <div className="p-3 rounded-xl bg-blue-50 border border-blue-100">
                    <div className="flex items-center gap-2 mb-1">
                        <Users className="h-4 w-4 text-blue-600" />
                        <span className="text-xs font-medium text-blue-700">Karyawan</span>
                    </div>
                    <p className="text-2xl font-bold text-blue-700">{stats?.employees.total || 0}</p>
                    <p className="text-xs text-blue-600">{stats?.employees.active || 0} aktif</p>
                </div>

                <div className="p-3 rounded-xl bg-green-50 border border-green-100">
                    <div className="flex items-center gap-2 mb-1">
                        <Clock className="h-4 w-4 text-green-600" />
                        <span className="text-xs font-medium text-green-700">Hadir</span>
                    </div>
                    <p className="text-2xl font-bold text-green-700">{stats?.attendance.present || 0}</p>
                    <p className="text-xs text-green-600">{stats?.attendance.late || 0} terlambat</p>
                </div>

                <div className="p-3 rounded-xl bg-yellow-50 border border-yellow-100">
                    <div className="flex items-center gap-2 mb-1">
                        <Calendar className="h-4 w-4 text-yellow-600" />
                        <span className="text-xs font-medium text-yellow-700">Cuti</span>
                    </div>
                    <p className="text-2xl font-bold text-yellow-700">{stats?.leave.pending || 0}</p>
                    <p className="text-xs text-yellow-600">Pending</p>
                </div>

                <div className="p-3 rounded-xl bg-purple-50 border border-purple-100">
                    <div className="flex items-center gap-2 mb-1">
                        <DollarSign className="h-4 w-4 text-purple-600" />
                        <span className="text-xs font-medium text-purple-700">Payroll</span>
                    </div>
                    <p className="text-2xl font-bold text-purple-700">{stats?.payroll.pending || 0}</p>
                    <p className="text-xs text-purple-600">Pending</p>
                </div>
            </div>

            {/* Pending Approvals List */}
            <div className="px-4 space-y-4">
                <h2 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider">Perlu Approval</h2>

                <div className="space-y-3">
                    {/* Leave Requests */}
                    <div className="bg-card border rounded-xl p-4 shadow-sm flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-yellow-100 rounded-lg">
                                <Calendar className="h-5 w-5 text-yellow-600" />
                            </div>
                            <div>
                                <h3 className="font-semibold">Pengajuan Cuti</h3>
                                <p className="text-xs text-muted-foreground">{stats?.leave.pending || 0} menunggu approval</p>
                            </div>
                        </div>
                        <AlertCircle className="h-5 w-5 text-yellow-600" />
                    </div>

                    {/* Attendance Corrections */}
                    <div className="bg-card border rounded-xl p-4 shadow-sm flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-blue-100 rounded-lg">
                                <Clock className="h-5 w-5 text-blue-600" />
                            </div>
                            <div>
                                <h3 className="font-semibold">Koreksi Absensi</h3>
                                <p className="text-xs text-muted-foreground">3 menunggu approval</p>
                            </div>
                        </div>
                        <AlertCircle className="h-5 w-5 text-blue-600" />
                    </div>

                    {/* Payroll */}
                    <div className="bg-card border rounded-xl p-4 shadow-sm flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-purple-100 rounded-lg">
                                <DollarSign className="h-5 w-5 text-purple-600" />
                            </div>
                            <div>
                                <h3 className="font-semibold">Payroll Pending</h3>
                                <p className="text-xs text-muted-foreground">{stats?.payroll.pending || 0} perlu diproses</p>
                            </div>
                        </div>
                        <AlertCircle className="h-5 w-5 text-purple-600" />
                    </div>
                </div>
            </div>

            {/* Recent Activity */}
            <div className="px-4 mt-6 space-y-4">
                <h2 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider">Aktivitas Terbaru</h2>

                <div className="space-y-3">
                    {[
                        { name: 'John Doe', action: 'Check-in', time: '08:00', status: 'success' },
                        { name: 'Jane Smith', action: 'Check-in', time: '08:15', status: 'success' },
                        { name: 'Bob Johnson', action: 'Pengajuan cuti', time: '09:30', status: 'pending' },
                        { name: 'Alice Brown', action: 'Check-in', time: '09:45', status: 'late' },
                    ].map((activity, index) => (
                        <div key={index} className="bg-card border rounded-xl p-3 shadow-sm flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className={`p-2 rounded-full ${activity.status === 'success' ? 'bg-green-100' :
                                        activity.status === 'late' ? 'bg-yellow-100' :
                                            'bg-blue-100'
                                    }`}>
                                    {activity.status === 'success' ? (
                                        <CheckCircle className="h-4 w-4 text-green-600" />
                                    ) : activity.status === 'late' ? (
                                        <AlertCircle className="h-4 w-4 text-yellow-600" />
                                    ) : (
                                        <Clock className="h-4 w-4 text-blue-600" />
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
    );
}
