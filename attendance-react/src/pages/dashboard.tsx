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
  Loader2,
  CheckCircle,
  AlertCircle,
  FileText,
  DollarSign,
  Briefcase,
  Target,
  Award,
  LayoutDashboard,
  ArrowRight,
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import {
  Area,
  AreaChart,
  ResponsiveContainer,
  XAxis,
  YAxis,
  Tooltip,
} from 'recharts';

export default function DashboardPage() {
  const { user } = useAuthStore();
  const { data: dashboardData, isLoading } = useDashboard();

  if (isLoading) {
    return (
      <div className="flex h-[50vh] items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
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

  // Stats data
  const adminStats = [
    {
      title: 'Total Karyawan',
      value: summary?.employees.total || 0,
      subtitle: `${summary?.employees.active || 0} aktif`,
      icon: Users,
      color: 'primary' as const,
    },
    {
      title: 'Hadir Hari Ini',
      value: summary?.attendance.present || 0,
      subtitle: `${summary?.attendance.attendance_rate?.toFixed(1) || 0}%`,
      icon: UserCheck,
      color: 'success' as const,
    },
    {
      title: 'Cuti / Izin',
      value: summary?.attendance.on_leave || 0,
      subtitle: `${summary?.pending_leaves || 0} pending`,
      icon: CalendarOff,
      color: 'warning' as const,
    },
    {
      title: 'Tidak Hadir',
      value: summary?.attendance.absent || 0,
      subtitle: `${summary?.attendance.late || 0} terlambat`,
      icon: UserX,
      color: 'destructive' as const,
    },
  ];

  const employeeStats = [
    {
      title: 'Kehadiran Bulan Ini',
      value: 22,
      subtitle: '95.6%',
      icon: UserCheck,
      color: 'success' as const,
    },
    {
      title: 'Terlambat',
      value: 1,
      subtitle: 'bulan ini',
      icon: Clock,
      color: 'warning' as const,
    },
    {
      title: 'Sisa Cuti',
      value: 10,
      subtitle: 'hari tersisa',
      icon: CalendarOff,
      color: 'primary' as const,
    },
    {
      title: 'Jam Kerja',
      value: 176,
      subtitle: 'jam bulan ini',
      icon: Clock,
      color: 'primary' as const,
    },
  ];

  const stats = isAdmin || isManager ? adminStats : employeeStats;

  const colorClasses = {
    primary: 'bg-primary/10 text-primary',
    success: 'bg-success/10 text-success',
    warning: 'bg-warning/10 text-warning',
    destructive: 'bg-destructive/10 text-destructive',
  };

  return (
    <div className="space-y-6 p-6">
      {/* Hero Header */}
      <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary/10 via-primary/5 to-background p-8">
        <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-primary/10 blur-3xl" />
        <div className="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-primary/5 blur-3xl" />

        <div className="relative">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-3">
              <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10">
                <LayoutDashboard className="h-6 w-6 text-primary" />
              </div>
              <div>
                <h1 className="text-2xl font-bold text-foreground">
                  Selamat datang, {user?.name?.split(' ')[0]}!
                </h1>
                <p className="text-sm text-muted-foreground">
                  {new Date().toLocaleDateString('id-ID', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                  })}
                </p>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <Badge variant="outline" className="bg-background/50 backdrop-blur-sm">
                {roleLabels[user?.role || ''] || user?.role}
              </Badge>
            </div>
          </div>

          {/* Stats Grid */}
          <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {stats.map((stat) => (
              <Card key={stat.title} className="bg-card/50 backdrop-blur-sm">
                <CardContent className="p-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-xs text-muted-foreground">{stat.title}</p>
                      <p className="text-2xl font-bold">{stat.value}</p>
                      <p className="text-xs text-muted-foreground">{stat.subtitle}</p>
                    </div>
                    <div className={`rounded-lg p-2 ${colorClasses[stat.color]}`}>
                      <stat.icon className="h-5 w-5" />
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </div>

      {/* Quick Check-in Banner for Employees */}
      {isEmployee && (
        <Card className="border-primary/20 bg-gradient-to-r from-primary/5 to-transparent">
          <CardContent className="p-4">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10">
                  <Clock className="h-5 w-5 text-primary" />
                </div>
                <div>
                  <p className="font-medium">Belum Check-in Hari Ini</p>
                  <p className="text-sm text-muted-foreground">
                    Jadwal: {today_schedule?.start_time || '08:00'} - {today_schedule?.end_time || '17:00'}
                  </p>
                </div>
              </div>
              <Button asChild className="gap-2">
                <a href="/face-recognition">
                  Check-in Sekarang
                  <ArrowRight className="h-4 w-4" />
                </a>
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Pending Approvals Banner for Admins */}
      {(isAdmin || isManager) && (summary?.pending_leaves || 0) > 0 && (
        <Card className="border-warning/20 bg-gradient-to-r from-warning/5 to-transparent">
          <CardContent className="p-4">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-warning/10">
                  <AlertCircle className="h-5 w-5 text-warning" />
                </div>
                <div>
                  <p className="font-medium">{summary?.pending_leaves} Pengajuan Menunggu Persetujuan</p>
                  <p className="text-sm text-muted-foreground">
                    Cuti dan izin yang perlu ditinjau
                  </p>
                </div>
              </div>
              <Button variant="outline" asChild className="gap-2">
                <a href="/leave">
                  Lihat Pengajuan
                  <ArrowRight className="h-4 w-4" />
                </a>
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Content Grid */}
      <div className="grid gap-6 lg:grid-cols-3">
        {/* Chart + Activity */}
        <div className="space-y-6 lg:col-span-2">
          {/* Attendance Trend Chart */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="flex items-center gap-2 text-base font-semibold">
                <TrendingUp className="h-5 w-5 text-primary" />
                {isEmployee ? 'Kehadiran Saya (7 Hari)' : 'Tren Kehadiran (7 Hari)'}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="h-[250px]">
                <ResponsiveContainer width="100%" height="100%">
                  <AreaChart data={attendance_trends}>
                    <defs>
                      <linearGradient id="colorPresent" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="hsl(var(--primary))" stopOpacity={0.3} />
                        <stop offset="95%" stopColor="hsl(var(--primary))" stopOpacity={0} />
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
                            <div className="rounded-lg border bg-background p-3 shadow-lg">
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
                      stroke="hsl(var(--primary))"
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
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="flex items-center gap-2 text-base font-semibold">
                <Clock className="h-5 w-5 text-primary" />
                {isEmployee ? 'Aktivitas Saya' : 'Aktivitas Terbaru'}
              </CardTitle>
              <Button variant="ghost" size="sm" className="text-primary">
                Lihat Semua
              </Button>
            </CardHeader>
            <CardContent className="p-0">
              <div className="divide-y divide-border">
                {recent_activity?.slice(0, 5).map((activity) => (
                  <div
                    key={activity.id}
                    className="flex items-center justify-between p-4 transition-colors hover:bg-muted/50"
                  >
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                        <span className="text-sm font-medium text-foreground">
                          {activity.employee_name
                            .split(' ')
                            .map((n) => n[0])
                            .join('')
                            .slice(0, 2)}
                        </span>
                      </div>
                      <div>
                        <p className="text-sm font-medium text-foreground">
                          {isEmployee ? 'Anda' : activity.employee_name}
                        </p>
                        <p className="text-xs text-muted-foreground">
                          {activity.description}
                        </p>
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="text-sm text-foreground">
                        {formatTime(activity.timestamp)}
                      </p>
                      <Badge variant="secondary" className={getActivityStyle(activity.type)}>
                        {getActivityLabel(activity.type)}
                      </Badge>
                    </div>
                  </div>
                ))}
                {(!recent_activity || recent_activity.length === 0) && (
                  <div className="p-8 text-center">
                    <p className="text-sm text-muted-foreground">Belum ada aktivitas</p>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Right Column */}
        <div className="space-y-6">
          {/* Quick Actions */}
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="flex items-center gap-2 text-base font-semibold">
                <Target className="h-5 w-5 text-primary" />
                Aksi Cepat
              </CardTitle>
            </CardHeader>
            <CardContent className="grid gap-2">
              <a
                href="/face-recognition"
                className="flex items-center gap-3 rounded-lg border border-border bg-background p-3 text-left transition-all hover:bg-muted hover:border-primary/20"
              >
                <div className="rounded-lg bg-primary/10 p-2">
                  <Clock className="h-4 w-4 text-primary" />
                </div>
                <div className="flex-1">
                  <p className="text-sm font-medium text-foreground">Absen Sekarang</p>
                  <p className="text-xs text-muted-foreground">Check-in / Check-out</p>
                </div>
                <ArrowRight className="h-4 w-4 text-muted-foreground" />
              </a>
              <a
                href="/leave"
                className="flex items-center gap-3 rounded-lg border border-border bg-background p-3 text-left transition-all hover:bg-muted hover:border-warning/20"
              >
                <div className="rounded-lg bg-warning/10 p-2">
                  <CalendarOff className="h-4 w-4 text-warning" />
                </div>
                <div className="flex-1">
                  <p className="text-sm font-medium text-foreground">
                    {isAdmin || isManager ? 'Kelola Cuti' : 'Ajukan Cuti'}
                  </p>
                  <p className="text-xs text-muted-foreground">
                    {isAdmin || isManager ? 'Review pengajuan' : 'Buat pengajuan baru'}
                  </p>
                </div>
                <ArrowRight className="h-4 w-4 text-muted-foreground" />
              </a>
              {(isAdmin || isManager) && (
                <>
                  <a
                    href="/employees"
                    className="flex items-center gap-3 rounded-lg border border-border bg-background p-3 text-left transition-all hover:bg-muted hover:border-primary/20"
                  >
                    <div className="rounded-lg bg-primary/10 p-2">
                      <Users className="h-4 w-4 text-primary" />
                    </div>
                    <div className="flex-1">
                      <p className="text-sm font-medium text-foreground">Data Karyawan</p>
                      <p className="text-xs text-muted-foreground">Kelola karyawan</p>
                    </div>
                    <ArrowRight className="h-4 w-4 text-muted-foreground" />
                  </a>
                  <a
                    href="/reports"
                    className="flex items-center gap-3 rounded-lg border border-border bg-background p-3 text-left transition-all hover:bg-muted hover:border-chart-5/20"
                  >
                    <div className="rounded-lg bg-chart-5/10 p-2">
                      <FileText className="h-4 w-4 text-chart-5" />
                    </div>
                    <div className="flex-1">
                      <p className="text-sm font-medium text-foreground">Laporan</p>
                      <p className="text-xs text-muted-foreground">Generate laporan</p>
                    </div>
                    <ArrowRight className="h-4 w-4 text-muted-foreground" />
                  </a>
                </>
              )}
              {isEmployee && (
                <>
                  <a
                    href="/payroll"
                    className="flex items-center gap-3 rounded-lg border border-border bg-background p-3 text-left transition-all hover:bg-muted hover:border-success/20"
                  >
                    <div className="rounded-lg bg-success/10 p-2">
                      <DollarSign className="h-4 w-4 text-success" />
                    </div>
                    <div className="flex-1">
                      <p className="text-sm font-medium text-foreground">Slip Gaji</p>
                      <p className="text-xs text-muted-foreground">Lihat slip gaji</p>
                    </div>
                    <ArrowRight className="h-4 w-4 text-muted-foreground" />
                  </a>
                  <a
                    href="/schedules"
                    className="flex items-center gap-3 rounded-lg border border-border bg-background p-3 text-left transition-all hover:bg-muted hover:border-chart-5/20"
                  >
                    <div className="rounded-lg bg-chart-5/10 p-2">
                      <Calendar className="h-4 w-4 text-chart-5" />
                    </div>
                    <div className="flex-1">
                      <p className="text-sm font-medium text-foreground">Jadwal Saya</p>
                      <p className="text-xs text-muted-foreground">Lihat jadwal kerja</p>
                    </div>
                    <ArrowRight className="h-4 w-4 text-muted-foreground" />
                  </a>
                </>
              )}
            </CardContent>
          </Card>

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
                <div className="space-y-2">
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Kehadiran</span>
                    <span className="font-medium">22/23 hari</span>
                  </div>
                  <Progress value={95.6} className="h-2" />
                </div>
                <div className="space-y-2">
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Tepat Waktu</span>
                    <span className="font-medium">21/22 hari</span>
                  </div>
                  <Progress value={95.4} className="h-2" />
                </div>
                <div className="space-y-2">
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Jam Kerja</span>
                    <span className="font-medium">176/184 jam</span>
                  </div>
                  <Progress value={95.6} className="h-2" />
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      </div>
    </div>
  );
}
