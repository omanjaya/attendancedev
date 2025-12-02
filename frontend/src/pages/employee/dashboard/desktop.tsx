import { useQuery } from '@tanstack/react-query';
import { Clock, Calendar, Plane, DollarSign, User, CheckCircle, AlertCircle } from 'lucide-react';
import { PageLayout } from '@/components/shared/PageLayout';
import { StatsGrid } from '@/components/shared/StatsGrid';
import { ContentCard } from '@/components/shared/ContentCard';
import { useAuthStore } from '@/stores';

/**
 * Employee Dashboard
 * Shows personal stats, my schedule, and recent activity
 */
export function DesktopEmployeeDashboard() {
  const { user } = useAuthStore();

  // Fetch personal stats
  const { data: stats } = useQuery({
    queryKey: ['employee', 'dashboard-stats', user?.id],
    queryFn: async () => {
      // TODO: Replace with actual API call
      return {
        attendance: {
          thisMonth: 18,
          present: 16,
          late: 2,
          absent: 0,
          todayStatus: null, // 'checked-in' | 'checked-out' | null
          checkIn: null,
          checkOut: null,
        },
        leave: {
          balance: 12,
          used: 3,
          pending: 1,
        },
        schedule: {
          today: { shift: 'Pagi', time: '08:00 - 17:00', location: 'Kantor Pusat' },
          nextShift: { date: 'Besok', shift: 'Pagi', time: '08:00 - 17:00' },
        },
        payroll: {
          lastPayment: { amount: 8500000, date: '2025-11-25', status: 'paid' },
          nextPayment: { date: '2025-12-25', estimated: 8500000 },
        },
      };
    },
  });

  const dashboardStats = [
    {
      label: 'Kehadiran Bulan Ini',
      value: stats?.attendance.present || 0,
      description: `${stats?.attendance.late || 0} terlambat, ${stats?.attendance.absent || 0} tidak hadir`,
      icon: Clock,
      color: 'success' as const,
      trend: 'up' as const,
      trendValue: `${stats?.attendance.thisMonth || 0} hari kerja`,
    },
    {
      label: 'Sisa Cuti',
      value: stats?.leave.balance || 0,
      description: `${stats?.leave.used || 0} terpakai, ${stats?.leave.pending || 0} pending`,
      icon: Plane,
      color: 'info' as const,
    },
    {
      label: 'Jadwal Hari Ini',
      value: (stats?.schedule.today.shift || '-') as any,
      description: stats?.schedule.today.time || 'Tidak ada jadwal',
      icon: Calendar,
      color: 'primary' as const,
    },
    {
      label: 'Gaji Terakhir',
      value: (stats?.payroll.lastPayment?.amount
        ? `Rp ${(stats.payroll.lastPayment.amount / 1000000).toFixed(1)}jt`
        : '-') as any,
      description: stats?.payroll.lastPayment?.date || 'Belum ada',
      icon: DollarSign,
      color: 'warning' as const,
    },
  ];

  return (
    <PageLayout
      title={`Selamat datang, ${user?.name || 'Employee'}!`}
      description="Dashboard pribadi Anda"
    >
      {/* Stats Grid */}
      <StatsGrid stats={dashboardStats} />

      <div className="grid gap-6 grid-cols-1 lg:grid-cols-2 mt-6">
        {/* Today's Attendance Status */}
        <ContentCard
          title="Status Kehadiran Hari Ini"
          description="Status absensi Anda hari ini"
          headerActions={
            <button
              onClick={() => window.location.href = '/employee/attendance/history'}
              className="text-sm text-primary hover:underline"
            >
              Lihat History
            </button>
          }
        >
          <div className="space-y-4">
            {!stats?.attendance.todayStatus ? (
              <div className="text-center py-8">
                <AlertCircle className="h-12 w-12 text-yellow-500 mx-auto mb-3" />
                <p className="text-sm font-medium">Belum absen hari ini</p>
                <p className="text-xs text-muted-foreground mt-1">Klik tombol di bawah untuk check-in</p>
                <button
                  onClick={() => window.location.href = '/employee/attendance'}
                  className="mt-4 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                  Check-In Sekarang
                </button>
              </div>
            ) : (
              <div className="space-y-3">
                {stats.attendance.checkIn && (
                  <div className="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div className="flex items-center gap-3">
                      <CheckCircle className="h-5 w-5 text-green-600 dark:text-green-400" />
                      <div>
                        <p className="text-sm font-medium">Check-In</p>
                        <p className="text-xs text-muted-foreground">{stats.attendance.checkIn}</p>
                      </div>
                    </div>
                    <span className="text-xs px-2 py-1 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 rounded-full">
                      Tepat Waktu
                    </span>
                  </div>
                )}

                {stats.attendance.checkOut ? (
                  <div className="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div className="flex items-center gap-3">
                      <CheckCircle className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                      <div>
                        <p className="text-sm font-medium">Check-Out</p>
                        <p className="text-xs text-muted-foreground">{stats.attendance.checkOut}</p>
                      </div>
                    </div>
                    <span className="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 rounded-full">
                      Selesai
                    </span>
                  </div>
                ) : (
                  <button
                    onClick={() => window.location.href = '/employee/attendance'}
                    className="w-full p-3 border-2 border-dashed border-blue-300 dark:border-blue-700 hover:border-blue-500 dark:hover:border-blue-500 rounded-lg transition-colors"
                  >
                    <p className="text-sm font-medium text-blue-600 dark:text-blue-400">Check-Out</p>
                    <p className="text-xs text-muted-foreground">Klik untuk check-out</p>
                  </button>
                )}
              </div>
            )}
          </div>
        </ContentCard>

        {/* My Schedule */}
        <ContentCard
          title="Jadwal Saya"
          description="Jadwal kerja Anda"
          headerActions={
            <button
              onClick={() => window.location.href = '/employee/schedule'}
              className="text-sm text-primary hover:underline"
            >
              Lihat Semua
            </button>
          }
        >
          <div className="space-y-3">
            {/* Today */}
            {stats?.schedule.today && (
              <div className="p-4 bg-primary/5 border border-primary/20 rounded-lg">
                <div className="flex items-center gap-2 mb-2">
                  <Calendar className="h-4 w-4 text-primary" />
                  <span className="text-sm font-semibold text-primary">Hari Ini</span>
                </div>
                <p className="text-sm font-medium">{stats.schedule.today.shift}</p>
                <p className="text-xs text-muted-foreground">{stats.schedule.today.time}</p>
                <p className="text-xs text-muted-foreground mt-1">{stats.schedule.today.location}</p>
              </div>
            )}

            {/* Next Shift */}
            {stats?.schedule.nextShift && (
              <div className="p-3 bg-muted/30 rounded-lg">
                <p className="text-xs text-muted-foreground mb-1">{stats.schedule.nextShift.date}</p>
                <p className="text-sm font-medium">{stats.schedule.nextShift.shift}</p>
                <p className="text-xs text-muted-foreground">{stats.schedule.nextShift.time}</p>
              </div>
            )}
          </div>
        </ContentCard>

        {/* Leave Balance */}
        <ContentCard
          title="Saldo Cuti"
          description="Status cuti Anda"
          headerActions={
            <button
              onClick={() => window.location.href = '/employee/leave/create'}
              className="text-sm text-primary hover:underline"
            >
              Ajukan Cuti
            </button>
          }
        >
          <div className="space-y-3">
            <div className="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              <div>
                <p className="text-xs text-muted-foreground">Sisa Cuti</p>
                <p className="text-2xl font-bold text-blue-600 dark:text-blue-400">{stats?.leave.balance || 0} hari</p>
              </div>
              <Plane className="h-8 w-8 text-blue-600/30 dark:text-blue-400/30" />
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div className="p-3 bg-muted/30 rounded-lg text-center">
                <p className="text-xs text-muted-foreground">Terpakai</p>
                <p className="text-lg font-semibold">{stats?.leave.used || 0}</p>
              </div>
              <div className="p-3 bg-muted/30 rounded-lg text-center">
                <p className="text-xs text-muted-foreground">Pending</p>
                <p className="text-lg font-semibold">{stats?.leave.pending || 0}</p>
              </div>
            </div>
          </div>
        </ContentCard>

        {/* Payroll Info */}
        <ContentCard
          title="Informasi Gaji"
          description="Slip gaji terbaru"
          headerActions={
            <button
              onClick={() => window.location.href = '/employee/payroll'}
              className="text-sm text-primary hover:underline"
            >
              Lihat Semua
            </button>
          }
        >
          <div className="space-y-3">
            {stats?.payroll.lastPayment && (
              <div className="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <p className="text-xs text-muted-foreground mb-1">Gaji Terakhir</p>
                <p className="text-2xl font-bold text-green-600 dark:text-green-400">
                  Rp {stats.payroll.lastPayment.amount.toLocaleString('id-ID')}
                </p>
                <p className="text-xs text-muted-foreground mt-1">{stats.payroll.lastPayment.date}</p>
                <span className="inline-block mt-2 text-xs px-2 py-1 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 rounded-full">
                  Dibayar
                </span>
              </div>
            )}

            {stats?.payroll.nextPayment && (
              <div className="p-3 bg-muted/30 rounded-lg">
                <p className="text-xs text-muted-foreground">Gaji Berikutnya</p>
                <p className="text-sm font-medium">{stats.payroll.nextPayment.date}</p>
                <p className="text-xs text-muted-foreground">
                  Est. Rp {stats.payroll.nextPayment.estimated.toLocaleString('id-ID')}
                </p>
              </div>
            )}
          </div>
        </ContentCard>
      </div>

      {/* Quick Actions */}
      <ContentCard title="Aksi Cepat" className="mt-6">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
          <button
            onClick={() => window.location.href = '/employee/attendance'}
            className="flex flex-col items-center gap-2 p-4 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors"
          >
            <Clock className="h-6 w-6 text-green-600 dark:text-green-400" />
            <span className="text-sm font-medium text-green-900 dark:text-green-100">Absensi</span>
          </button>

          <button
            onClick={() => window.location.href = '/employee/leave/create'}
            className="flex flex-col items-center gap-2 p-4 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
          >
            <Plane className="h-6 w-6 text-blue-600 dark:text-blue-400" />
            <span className="text-sm font-medium text-blue-900 dark:text-blue-100">Ajukan Cuti</span>
          </button>

          <button
            onClick={() => window.location.href = '/employee/schedule'}
            className="flex flex-col items-center gap-2 p-4 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded-lg transition-colors"
          >
            <Calendar className="h-6 w-6 text-purple-600 dark:text-purple-400" />
            <span className="text-sm font-medium text-purple-900 dark:text-purple-100">Jadwal</span>
          </button>

          <button
            onClick={() => window.location.href = '/employee/profile'}
            className="flex flex-col items-center gap-2 p-4 bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/30 rounded-lg transition-colors"
          >
            <User className="h-6 w-6 text-orange-600 dark:text-orange-400" />
            <span className="text-sm font-medium text-orange-900 dark:text-orange-100">Profil</span>
          </button>
        </div>
      </ContentCard>
    </PageLayout>
  );
}
