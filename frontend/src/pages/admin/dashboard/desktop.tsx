import { useQuery } from '@tanstack/react-query';
import { Users, Clock, Calendar, DollarSign, FileText, AlertCircle, CheckCircle } from 'lucide-react';
import { PageLayout } from '@/components/shared/PageLayout';
import { StatsGrid } from '@/components/shared/StatsGrid';
import { ContentCard } from '@/components/shared/ContentCard';
import { getDashboardStatistics } from '@/lib/api/dashboard';

/**
 * Admin Dashboard
 * Shows company-wide statistics, pending approvals, and quick actions
 */
export function DesktopAdminDashboard() {
  // Fetch dashboard stats from real API
  const { data: stats, isLoading } = useQuery({
    queryKey: ['admin', 'dashboard-stats'],
    queryFn: getDashboardStatistics,
    refetchInterval: 30000, // Refetch every 30 seconds for real-time updates
  });

  // Fix color types if needed. StatsGrid usually supports: default, primary, success, warning, destructive, info.
  // Let's adjust colors to match supported types.

  const refinedStats = [
    {
      label: 'Total Karyawan',
      value: stats?.employees?.total || 0,
      description: `${stats?.employees?.active || 0} aktif, ${stats?.employees?.inactive || 0} tidak aktif`,
      icon: Users,
      trend: 'up' as const,
      trendValue: stats?.employees?.by_type ? `${Object.keys(stats.employees.by_type).length} tipe` : '',
      color: 'info' as const,
    },
    {
      label: 'Kehadiran Hari Ini',
      value: stats?.attendance?.present_today || 0,
      description: `${stats?.attendance?.late_today || 0} terlambat, ${stats?.attendance?.absent_today || 0} tidak hadir`,
      icon: Clock,
      trend: stats?.attendance?.attendance_rate && stats.attendance.attendance_rate >= 90 ? 'up' as const : 'down' as const,
      trendValue: stats?.attendance?.attendance_rate ? `${stats.attendance.attendance_rate.toFixed(1)}%` : '',
      color: 'success' as const,
    },
    {
      label: 'Pengajuan Cuti',
      value: stats?.leave?.pending || 0,
      description: 'Menunggu approval',
      icon: Calendar,
      color: 'warning' as const,
    },
    {
      label: 'Payroll Pending',
      value: stats?.payroll?.pending_periods || 0,
      description: `${stats?.payroll?.completed_periods || 0} sudah diproses`,
      icon: DollarSign,
      color: 'primary' as const,
    },
  ];

  // Show loading state
  if (isLoading) {
    return (
      <PageLayout title="Dashboard Admin" description="Memuat data...">
        <div className="flex items-center justify-center py-12">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary" />
        </div>
      </PageLayout>
    );
  }

  return (
    <PageLayout
      title="Dashboard Admin"
      description="Overview perusahaan dan pending approvals"
    >
      {/* Stats Grid */}
      <StatsGrid stats={refinedStats} />

      <div className="grid gap-6 grid-cols-1 lg:grid-cols-2 mt-6">
        {/* Pending Approvals */}
        <ContentCard
          title="Pending Approvals"
          description="Pengajuan yang menunggu persetujuan"
          headerActions={
            <button
              onClick={() => window.location.href = '/admin/leave'}
              className="text-sm text-primary hover:underline"
            >
              Lihat Semua
            </button>
          }
        >
          <div className="space-y-3">
            {/* Leave Requests */}
            <div className="flex items-center justify-between p-3 bg-muted/30 rounded-lg hover:bg-muted/50 transition-colors cursor-pointer">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                  <Calendar className="h-4 w-4 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                  <p className="text-sm font-medium">Pengajuan Cuti</p>
                  <p className="text-xs text-muted-foreground">{stats?.leave?.pending || 0} menunggu approval</p>
                </div>
              </div>
              <AlertCircle className="h-4 w-4 text-yellow-600" />
            </div>

            {/* Attendance Corrections */}
            <div className="flex items-center justify-between p-3 bg-muted/30 rounded-lg hover:bg-muted/50 transition-colors cursor-pointer">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                  <Clock className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                  <p className="text-sm font-medium">Koreksi Absensi</p>
                  <p className="text-xs text-muted-foreground">3 menunggu approval</p>
                </div>
              </div>
              <AlertCircle className="h-4 w-4 text-blue-600" />
            </div>

            {/* Payroll */}
            <div className="flex items-center justify-between p-3 bg-muted/30 rounded-lg hover:bg-muted/50 transition-colors cursor-pointer">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                  <DollarSign className="h-4 w-4 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                  <p className="text-sm font-medium">Payroll Pending</p>
                  <p className="text-xs text-muted-foreground">{stats?.payroll?.pending_periods || 0} perlu diproses</p>
                </div>
              </div>
              <AlertCircle className="h-4 w-4 text-purple-600" />
            </div>
          </div>
        </ContentCard>

        {/* Quick Actions */}
        <ContentCard
          title="Quick Actions"
          description="Aksi cepat untuk admin"
        >
          <div className="grid grid-cols-2 gap-3">
            <button
              onClick={() => window.location.href = '/admin/employees/create'}
              className="flex flex-col items-center gap-2 p-4 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
            >
              <Users className="h-6 w-6 text-blue-600 dark:text-blue-400" />
              <span className="text-sm font-medium text-blue-900 dark:text-blue-100">Tambah Karyawan</span>
            </button>

            <button
              onClick={() => window.location.href = '/admin/schedules/create'}
              className="flex flex-col items-center gap-2 p-4 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors"
            >
              <Calendar className="h-6 w-6 text-green-600 dark:text-green-400" />
              <span className="text-sm font-medium text-green-900 dark:text-green-100">Buat Jadwal</span>
            </button>

            <button
              onClick={() => window.location.href = '/admin/payroll'}
              className="flex flex-col items-center gap-2 p-4 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded-lg transition-colors"
            >
              <DollarSign className="h-6 w-6 text-purple-600 dark:text-purple-400" />
              <span className="text-sm font-medium text-purple-900 dark:text-purple-100">Proses Payroll</span>
            </button>

            <button
              onClick={() => window.location.href = '/admin/reports'}
              className="flex flex-col items-center gap-2 p-4 bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/30 rounded-lg transition-colors"
            >
              <FileText className="h-6 w-6 text-orange-600 dark:text-orange-400" />
              <span className="text-sm font-medium text-orange-900 dark:text-orange-100">Lihat Reports</span>
            </button>
          </div>
        </ContentCard>

        {/* Recent Activity */}
        <ContentCard
          title="Aktivitas Terbaru"
          description="Aktivitas karyawan hari ini"
          className="lg:col-span-2"
        >
          <div className="space-y-2">
            {[
              { name: 'John Doe', action: 'Check-in', time: '08:00', status: 'success' },
              { name: 'Jane Smith', action: 'Check-in', time: '08:15', status: 'success' },
              { name: 'Bob Johnson', action: 'Pengajuan cuti', time: '09:30', status: 'pending' },
              { name: 'Alice Brown', action: 'Check-in', time: '09:45', status: 'late' },
              { name: 'Charlie Wilson', action: 'Koreksi absensi', time: '10:00', status: 'pending' },
            ].map((activity, index) => (
              <div
                key={index}
                className="flex items-center justify-between p-3 bg-muted/30 rounded-lg hover:bg-muted/50 transition-colors"
              >
                <div className="flex items-center gap-3">
                  <div className={`p-2 rounded-lg ${activity.status === 'success' ? 'bg-green-100 dark:bg-green-900/30' :
                    activity.status === 'late' ? 'bg-yellow-100 dark:bg-yellow-900/30' :
                      'bg-blue-100 dark:bg-blue-900/30'
                    }`}>
                    {activity.status === 'success' ? (
                      <CheckCircle className="h-4 w-4 text-green-600 dark:text-green-400" />
                    ) : activity.status === 'late' ? (
                      <AlertCircle className="h-4 w-4 text-yellow-600 dark:text-yellow-400" />
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
        </ContentCard>
      </div>
    </PageLayout>
  );
}
