import { useQuery } from '@tanstack/react-query';
import { Clock, Calendar, Plane, DollarSign, User, CheckCircle, AlertCircle, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { getEmployeeDashboardData } from '@/lib/api/employees';
import { useAuthStore } from '@/stores';

/**
 * Mobile Employee Dashboard
 * Optimized layout for mobile view
 */
export function MobileEmployeeDashboard() {
  const { user } = useAuthStore();

  // Get today's date for cache key (ensures fresh data each day)
  const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD format

  // Fetch dashboard stats
  const { data: stats } = useQuery({
    queryKey: ['employee', 'dashboard-stats', user?.id, today],
    queryFn: getEmployeeDashboardData,
    staleTime: 0, // Always refetch on mount
  });

  const dashboardStats = [
    {
      label: 'Hadir',
      value: stats?.attendance.present || 0,
      icon: Clock,
      color: 'text-green-600 dark:text-green-400' as const,
      bgColor: 'bg-green-50 dark:bg-green-900/20' as const,
      detail: `${stats?.attendance.late || 0} terlambat`,
    },
    {
      label: 'Cuti',
      value: stats?.leave.balance || 0,
      icon: Plane,
      color: 'text-blue-600 dark:text-blue-400' as const,
      bgColor: 'bg-blue-50 dark:bg-blue-900/20' as const,
      detail: `${stats?.leave.used || 0} terpakai`,
    },
    {
      label: 'Shift',
      value: (stats?.schedule.today.shift || '-') as string,
      icon: Calendar,
      color: stats?.schedule.today.can_attend ? 'text-purple-600 dark:text-purple-400' : 'text-muted-foreground',
      bgColor: stats?.schedule.today.can_attend ? 'bg-purple-50 dark:bg-purple-900/20' : 'bg-muted/20',
      detail: stats?.schedule.today.time || 'Tidak ada',
    },
    {
      label: 'Gaji',
      value: (stats?.payroll.lastPayment?.amount
        ? `${(stats.payroll.lastPayment.amount / 1000000).toFixed(1)}jt`
        : '-') as any,
      icon: DollarSign,
      color: 'text-orange-600 dark:text-orange-400' as const,
      bgColor: 'bg-orange-50 dark:bg-orange-900/20' as const,
      detail: stats?.payroll.lastPayment?.date || 'Belum ada',
    },
  ];

  return (
    <div className="min-h-screen bg-background pb-24">
      {/* Header */}
      <div className="px-4 pt-6 pb-4 space-y-2">
        <h1 className="text-xl font-bold text-foreground">
          Selamat datang, {user?.name || 'Employee'}!
        </h1>
        <p className="text-sm text-muted-foreground">
          Dashboard pribadi Anda
        </p>
      </div>

      <div className="px-4 space-y-4">
        {/* Stats Grid */}
        <div className="grid grid-cols-2 gap-3">
          {dashboardStats.map((stat, index) => (
            <div key={index} className={`${stat.bgColor} rounded-2xl shadow-sm dark:border dark:border-border/50 p-4 text-center`}>
              <stat.icon className={`h-6 w-6 mx-auto mb-2 ${stat.color}`} />
              <p className="text-2xl font-bold text-foreground">{stat.value}</p>
              <p className="text-xs text-muted-foreground mt-1">{stat.label}</p>
              <p className="text-xs text-muted-foreground mt-0.5">{stat.detail}</p>
            </div>
          ))}
        </div>

      {/* Today's Attendance Status */}
      <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
        <div className="flex items-center justify-between mb-3">
          <h3 className="text-sm font-bold text-foreground flex items-center gap-2">
            <Clock className="h-4 w-4" />
            Absensi Hari Ini
          </h3>
          {stats?.attendance.todayStatus && (
            <Badge variant="default" className="text-xs">
              Sudah Absen
            </Badge>
          )}
        </div>
        <div className="space-y-3">
          {!stats?.attendance.todayStatus ? (
            <>
              {stats?.schedule.today.can_attend ? (
                <div className="text-center py-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                  <AlertCircle className="h-10 w-10 text-yellow-500 mx-auto mb-3" />
                  <p className="text-sm font-medium mb-3">Belum absen hari ini</p>
                  <Button
                    onClick={() => window.location.href = '/employee/attendance'}
                    className="w-full"
                    size="sm"
                  >
                    Check-In Sekarang
                  </Button>
                </div>
              ) : (
                <div className="text-center py-6 bg-muted/20 rounded-lg">
                  <Calendar className="h-10 w-10 text-muted-foreground mx-auto mb-3" />
                  <p className="text-sm font-medium mb-1">{stats?.schedule.today.message || 'Tidak ada jadwal'}</p>
                  <p className="text-xs text-muted-foreground">
                    {stats?.schedule.today.schedule_type === 'holiday' ? 'Selamat berlibur!' : 'Tidak perlu absen hari ini'}
                  </p>
                </div>
              )}
            </>
          ) : (
            <div className="space-y-2">
              {stats.attendance.checkIn && (
                <div className="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                  <div className="flex items-center gap-3">
                    <CheckCircle className="h-5 w-5 text-green-600 dark:text-green-400 flex-shrink-0" />
                    <div className="min-w-0">
                      <p className="text-sm font-medium">Check-In</p>
                      <p className="text-xs text-muted-foreground">{stats.attendance.checkIn}</p>
                    </div>
                  </div>
                  <Badge variant="outline" className="text-xs px-2 py-1 border-green-500 text-green-700 dark:text-green-300">
                    Tepat Waktu
                  </Badge>
                </div>
              )}

              {stats.attendance.checkOut ? (
                <div className="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                  <div className="flex items-center gap-3">
                    <CheckCircle className="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0" />
                    <div className="min-w-0">
                      <p className="text-sm font-medium">Check-Out</p>
                      <p className="text-xs text-muted-foreground">{stats.attendance.checkOut}</p>
                    </div>
                  </div>
                  <Badge variant="outline" className="text-xs px-2 py-1 border-blue-500 text-blue-700 dark:text-blue-300">
                    Selesai
                  </Badge>
                </div>
              ) : (
                <Button
                  onClick={() => window.location.href = '/employee/attendance'}
                  className="w-full"
                  variant="outline"
                >
                  Check-Out
                </Button>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Quick Actions */}
      <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
        <h3 className="text-sm font-bold text-foreground mb-3">Aksi Cepat</h3>
          <div className="grid grid-cols-2 gap-3">
            <Button
              onClick={() => window.location.href = '/employee/attendance'}
              className="h-auto p-4 flex-col gap-2"
              variant="outline"
            >
              <Clock className="h-6 w-6" />
              <span className="text-xs">Absensi</span>
            </Button>
            <Button
              onClick={() => window.location.href = '/employee/leave/create'}
              className="h-auto p-4 flex-col gap-2"
              variant="outline"
            >
              <Plane className="h-6 w-6" />
              <span className="text-xs">Ajukan Cuti</span>
            </Button>
            <Button
              onClick={() => window.location.href = '/employee/schedule'}
              className="h-auto p-4 flex-col gap-2"
              variant="outline"
            >
              <Calendar className="h-6 w-6" />
              <span className="text-xs">Jadwal</span>
            </Button>
            <Button
              onClick={() => window.location.href = '/employee/profile'}
              className="h-auto p-4 flex-col gap-2"
              variant="outline"
            >
              <User className="h-6 w-6" />
              <span className="text-xs">Profil</span>
            </Button>
          </div>
      </div>

      {/* Today's Schedule */}
      {stats?.schedule.today && (
        <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
          <div className="flex items-center justify-between mb-3">
            <h3 className="text-sm font-bold text-foreground flex items-center gap-2">
              <Calendar className="h-4 w-4" />
              Jadwal Hari Ini
            </h3>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => window.location.href = '/employee/schedule'}
              className="text-xs"
            >
              Lihat Semua
            </Button>
          </div>
          <div className="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
            <p className="font-semibold text-sm mb-1">{stats.schedule.today.shift}</p>
            <p className="text-xs text-muted-foreground mb-1">{stats.schedule.today.time}</p>
            <p className="text-xs text-muted-foreground">{stats.schedule.today.location}</p>
          </div>
        </div>
      )}

      {/* Leave Balance */}
      <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
        <div className="flex items-center justify-between mb-3">
          <h3 className="text-sm font-bold text-foreground flex items-center gap-2">
            <Plane className="h-4 w-4" />
            Saldo Cuti
          </h3>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => window.location.href = '/employee/leave/create'}
            className="text-xs"
          >
            Ajukan Cuti
          </Button>
        </div>
        <div className="p-4 bg-teal-50 dark:bg-teal-900/20 rounded-xl">
          <div className="flex items-center justify-between mb-2">
            <p className="text-sm text-muted-foreground">Sisa Cuti</p>
            <Plane className="h-6 w-6 text-teal-600/30 dark:text-teal-400/30" />
          </div>
          <p className="text-2xl font-bold text-teal-600 dark:text-teal-400">{stats?.leave.balance || 0} hari</p>
          <div className="grid grid-cols-2 gap-3 mt-3">
            <div className="text-center">
              <p className="text-xs text-muted-foreground">Terpakai</p>
              <p className="text-base font-semibold">{stats?.leave.used || 0}</p>
            </div>
            <div className="text-center">
              <p className="text-xs text-muted-foreground">Pending</p>
              <p className="text-base font-semibold">{stats?.leave.pending || 0}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Quick Links */}
      <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
        <h3 className="text-sm font-bold text-foreground mb-3">Menu Lengkap</h3>
        <div className="space-y-2">
          <Button
            variant="ghost"
            onClick={() => window.location.href = '/employee/attendance'}
            className="w-full justify-between h-auto p-3 hover:bg-muted/50"
          >
            <span className="flex items-center gap-3">
              <div className="h-8 w-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                <Clock className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
              </div>
              <span className="text-sm">Riwayat Absensi</span>
            </span>
            <ChevronRight className="h-4 w-4 text-muted-foreground" />
          </Button>
          <Button
            variant="ghost"
            onClick={() => window.location.href = '/employee/leave'}
            className="w-full justify-between h-auto p-3 hover:bg-muted/50"
          >
            <span className="flex items-center gap-3">
              <div className="h-8 w-8 rounded-full bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                <Plane className="h-4 w-4 text-teal-600 dark:text-teal-400" />
              </div>
              <span className="text-sm">Riwayat Cuti</span>
            </span>
            <ChevronRight className="h-4 w-4 text-muted-foreground" />
          </Button>
          <Button
            variant="ghost"
            onClick={() => window.location.href = '/employee/payroll'}
            className="w-full justify-between h-auto p-3 hover:bg-muted/50"
          >
            <span className="flex items-center gap-3">
              <div className="h-8 w-8 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                <DollarSign className="h-4 w-4 text-amber-600 dark:text-amber-400" />
              </div>
              <span className="text-sm">Slip Gaji</span>
            </span>
            <ChevronRight className="h-4 w-4 text-muted-foreground" />
          </Button>
          <Button
            variant="ghost"
            onClick={() => window.location.href = '/employee/reports'}
            className="w-full justify-between h-auto p-3 hover:bg-muted/50"
          >
            <span className="flex items-center gap-3">
              <div className="h-8 w-8 rounded-full bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center">
                <User className="h-4 w-4 text-rose-600 dark:text-rose-400" />
              </div>
              <span className="text-sm">Laporan</span>
            </span>
            <ChevronRight className="h-4 w-4 text-muted-foreground" />
          </Button>
        </div>
      </div>
      </div>
    </div>
  );
}
