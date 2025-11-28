import { useAuthStore } from '@/stores';
import { useMockDashboard } from '@/hooks';
import {
  Users,
  UserCheck,
  CalendarOff,
  UserX,
  TrendingUp,
  Clock,
  Calendar,
  Loader2,
  ArrowUpRight,
  ArrowDownRight,
  CheckCircle,
  AlertCircle,
  FileText,
  DollarSign,
  Briefcase,
  Target,
  Award,
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
  const { data: dashboardData, isLoading } = useMockDashboard();

  if (isLoading) {
    return (
      <div className="flex h-[50vh] items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  const { summary, recent_activity, attendance_trends, today_schedule } = dashboardData || {};

  // Determine user role for conditional rendering
  // Valid roles: 'super-admin' | 'admin' | 'kepala-sekolah' | 'guru' | 'pegawai'
  const isAdmin = user?.role === 'super-admin' || user?.role === 'admin';
  const isManager = user?.role === 'kepala-sekolah';
  const isEmployee = user?.role === 'guru' || user?.role === 'pegawai';

  // Format time for display
  const formatTime = (timestamp: string) => {
    return new Date(timestamp).toLocaleTimeString('id-ID', {
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  // Get activity status style
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

  // Role labels
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

  // Admin/Manager Stats
  const adminStats = [
    {
      title: 'Total Karyawan',
      value: summary?.employees.total || 0,
      icon: Users,
      change: `${summary?.employees.active || 0} aktif`,
      trend: 'up' as const,
      color: 'bg-primary',
    },
    {
      title: 'Hadir Hari Ini',
      value: summary?.attendance.present || 0,
      icon: UserCheck,
      change: `${summary?.attendance.attendance_rate?.toFixed(1) || 0}%`,
      trend: 'up' as const,
      color: 'bg-success',
    },
    {
      title: 'Cuti / Izin',
      value: summary?.attendance.on_leave || 0,
      icon: CalendarOff,
      change: `${summary?.pending_leaves || 0} pending`,
      trend: 'neutral' as const,
      color: 'bg-warning',
    },
    {
      title: 'Tidak Hadir',
      value: summary?.attendance.absent || 0,
      icon: UserX,
      change: `${summary?.attendance.late || 0} terlambat`,
      trend: 'down' as const,
      color: 'bg-destructive',
    },
  ];

  // Employee personal stats (mock)
  const employeeStats = [
    {
      title: 'Kehadiran Bulan Ini',
      value: 22,
      icon: UserCheck,
      change: '95.6%',
      trend: 'up' as const,
      color: 'bg-success',
    },
    {
      title: 'Terlambat',
      value: 1,
      icon: Clock,
      change: 'bulan ini',
      trend: 'neutral' as const,
      color: 'bg-warning',
    },
    {
      title: 'Sisa Cuti',
      value: 10,
      icon: CalendarOff,
      change: 'hari tersisa',
      trend: 'neutral' as const,
      color: 'bg-primary',
    },
    {
      title: 'Jam Kerja',
      value: 176,
      icon: Clock,
      change: 'jam bulan ini',
      trend: 'up' as const,
      color: 'bg-blue-500',
    },
  ];

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-8">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 className="text-macos-2xl font-bold text-foreground">Dashboard</h1>
            <p className="text-macos-sm text-muted-foreground">
              Selamat datang kembali, {user?.name}!
            </p>
          </div>
          <div className="flex items-center gap-2">
            <Badge variant="outline" className="capitalize">
              {roleLabels[user?.role || ''] || user?.role}
            </Badge>
            <span className="text-macos-sm text-muted-foreground">
              {new Date().toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
              })}
            </span>
          </div>
        </div>
      </div>

      {/* Quick Check-in Banner for Employees */}
      {isEmployee && (
        <Card className="mb-6 border-primary/50 bg-primary/5">
          <CardContent className="p-4">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div className="flex items-center gap-3">
                <div className="p-3 rounded-full bg-primary/10">
                  <Clock className="h-6 w-6 text-primary" />
                </div>
                <div>
                  <p className="font-medium">Belum Check-in Hari Ini</p>
                  <p className="text-sm text-muted-foreground">
                    Jadwal: {today_schedule?.start_time || '08:00'} - {today_schedule?.end_time || '17:00'}
                  </p>
                </div>
              </div>
              <Button asChild>
                <a href="/face-recognition">Check-in Sekarang</a>
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Pending Approvals Banner for Admins */}
      {(isAdmin || isManager) && (summary?.pending_leaves || 0) > 0 && (
        <Card className="mb-6 border-warning/50 bg-warning/5">
          <CardContent className="p-4">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div className="flex items-center gap-3">
                <div className="p-3 rounded-full bg-warning/10">
                  <AlertCircle className="h-6 w-6 text-warning" />
                </div>
                <div>
                  <p className="font-medium">{summary?.pending_leaves} Pengajuan Menunggu Persetujuan</p>
                  <p className="text-sm text-muted-foreground">
                    Cuti dan izin yang perlu ditinjau
                  </p>
                </div>
              </div>
              <Button variant="outline" asChild>
                <a href="/leave">Lihat Pengajuan</a>
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Stats Grid - shadcnblocks style */}
      <div className="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {(isAdmin || isManager ? adminStats : employeeStats).map((stat) => (
          <Card key={stat.title} className="bg-accent/50 border-none shadow-sm hover:shadow-md transition-all">
            <CardContent className="p-6">
              <div className="flex flex-col items-center text-center">
                <div className={`mb-3 rounded-xl p-3 ${stat.color}`}>
                  <stat.icon className="h-6 w-6 text-white" />
                </div>
                <p className="mb-1 flex items-center justify-center text-3xl font-bold">
                  {stat.trend === 'up' && (
                    <ArrowUpRight className="mr-1 h-6 w-6 text-green-500" />
                  )}
                  {stat.trend === 'down' && (
                    <ArrowDownRight className="mr-1 h-6 w-6 text-red-500" />
                  )}
                  {typeof stat.value === 'number' ? stat.value.toLocaleString() : stat.value}
                </p>
                <p className="text-sm font-medium text-foreground">{stat.title}</p>
                <p className="text-xs text-muted-foreground mt-1">{stat.change}</p>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Content Grid */}
      <div className="grid gap-6 lg:grid-cols-3">
        {/* Chart + Activity */}
        <div className="space-y-6 lg:col-span-2">
          {/* Attendance Trend Chart */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="flex items-center gap-2 text-macos-base font-semibold">
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
              <CardTitle className="flex items-center gap-2 text-macos-base font-semibold">
                <Clock className="h-5 w-5 text-primary" />
                {isEmployee ? 'Aktivitas Saya' : 'Aktivitas Terbaru'}
              </CardTitle>
              <button className="text-macos-sm text-primary hover:underline">
                Lihat Semua
              </button>
            </CardHeader>
            <CardContent className="p-0">
              <div className="divide-y divide-border">
                {recent_activity?.map((activity) => (
                  <div
                    key={activity.id}
                    className="flex items-center justify-between p-4"
                  >
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                        <span className="text-macos-sm font-medium text-foreground">
                          {activity.employee_name
                            .split(' ')
                            .map((n) => n[0])
                            .join('')}
                        </span>
                      </div>
                      <div>
                        <p className="text-macos-sm font-medium text-foreground">
                          {isEmployee ? 'Anda' : activity.employee_name}
                        </p>
                        <p className="text-macos-xs text-muted-foreground">
                          {activity.description}
                        </p>
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="text-macos-sm text-foreground">
                        {formatTime(activity.timestamp)}
                      </p>
                      <span
                        className={`inline-block rounded-full px-2 py-0.5 text-macos-xs font-medium ${getActivityStyle(
                          activity.type
                        )}`}
                      >
                        {getActivityLabel(activity.type)}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Right Column */}
        <div className="space-y-6">
          {/* Quick Actions */}
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="flex items-center gap-2 text-macos-base font-semibold">
                <Target className="h-5 w-5 text-primary" />
                Aksi Cepat
              </CardTitle>
            </CardHeader>
            <CardContent className="grid gap-2">
              <a
                href="/face-recognition"
                className="flex items-center gap-3 rounded-lg border border-border bg-background p-3 text-left transition-colors hover:bg-muted"
              >
                <div className="rounded-lg bg-primary/10 p-2">
                  <Clock className="h-4 w-4 text-primary" />
                </div>
                <div>
                  <p className="text-macos-sm font-medium text-foreground">
                    Absen Sekarang
                  </p>
                  <p className="text-macos-xs text-muted-foreground">
                    Check-in / Check-out
                  </p>
                </div>
              </a>
              <a
                href="/leave"
                className="flex items-center gap-3 rounded-lg border border-border bg-background p-3 text-left transition-colors hover:bg-muted"
              >
                <div className="rounded-lg bg-warning/10 p-2">
                  <CalendarOff className="h-4 w-4 text-warning" />
                </div>
                <div>
                  <p className="text-macos-sm font-medium text-foreground">
                    {isAdmin || isManager ? 'Kelola Cuti' : 'Ajukan Cuti'}
                  </p>
                  <p className="text-macos-xs text-muted-foreground">
                    {isAdmin || isManager ? 'Review pengajuan' : 'Buat pengajuan baru'}
                  </p>
                </div>
              </a>
              {(isAdmin || isManager) && (
                <>
                  <a
                    href="/employees"
                    className="flex items-center gap-3 rounded-lg border border-border bg-background p-3 text-left transition-colors hover:bg-muted"
                  >
                    <div className="rounded-lg bg-blue-500/10 p-2">
                      <Users className="h-4 w-4 text-blue-500" />
                    </div>
                    <div>
                      <p className="text-macos-sm font-medium text-foreground">
                        Data Karyawan
                      </p>
                      <p className="text-macos-xs text-muted-foreground">
                        Kelola karyawan
                      </p>
                    </div>
                  </a>
                  <a
                    href="/reports"
                    className="flex items-center gap-3 rounded-lg border border-border bg-background p-3 text-left transition-colors hover:bg-muted"
                  >
                    <div className="rounded-lg bg-purple-500/10 p-2">
                      <FileText className="h-4 w-4 text-purple-500" />
                    </div>
                    <div>
                      <p className="text-macos-sm font-medium text-foreground">
                        Laporan
                      </p>
                      <p className="text-macos-xs text-muted-foreground">
                        Generate laporan
                      </p>
                    </div>
                  </a>
                </>
              )}
              {isEmployee && (
                <>
                  <a
                    href="/payroll"
                    className="flex items-center gap-3 rounded-lg border border-border bg-background p-3 text-left transition-colors hover:bg-muted"
                  >
                    <div className="rounded-lg bg-green-500/10 p-2">
                      <DollarSign className="h-4 w-4 text-green-500" />
                    </div>
                    <div>
                      <p className="text-macos-sm font-medium text-foreground">
                        Slip Gaji
                      </p>
                      <p className="text-macos-xs text-muted-foreground">
                        Lihat slip gaji
                      </p>
                    </div>
                  </a>
                  <a
                    href="/schedules"
                    className="flex items-center gap-3 rounded-lg border border-border bg-background p-3 text-left transition-colors hover:bg-muted"
                  >
                    <div className="rounded-lg bg-purple-500/10 p-2">
                      <Calendar className="h-4 w-4 text-purple-500" />
                    </div>
                    <div>
                      <p className="text-macos-sm font-medium text-foreground">
                        Jadwal Saya
                      </p>
                      <p className="text-macos-xs text-muted-foreground">
                        Lihat jadwal kerja
                      </p>
                    </div>
                  </a>
                </>
              )}
            </CardContent>
          </Card>

          {/* Today's Schedule */}
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="flex items-center gap-2 text-macos-base font-semibold">
                <Calendar className="h-5 w-5 text-primary" />
                Jadwal Hari Ini
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {today_schedule ? (
                <div className="rounded-lg bg-muted p-3">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-macos-sm font-medium text-foreground">
                        {today_schedule.shift_name}
                      </p>
                      <p className="text-macos-xs text-muted-foreground">
                        {today_schedule.start_time} - {today_schedule.end_time}
                      </p>
                    </div>
                    <Badge variant="outline" className="bg-green-50 text-green-600 border-green-200">
                      <CheckCircle className="h-3 w-3 mr-1" />
                      Aktif
                    </Badge>
                  </div>
                </div>
              ) : (
                <div className="text-center py-4">
                  <p className="text-macos-sm text-muted-foreground">
                    Tidak ada jadwal
                  </p>
                </div>
              )}
              <div className="text-center text-macos-xs text-muted-foreground">
                {new Date().toLocaleDateString('id-ID', {
                  weekday: 'long',
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric',
                })}
              </div>
            </CardContent>
          </Card>

          {/* Admin-only: Department Stats */}
          {(isAdmin || isManager) && (
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="flex items-center gap-2 text-macos-base font-semibold">
                  <Briefcase className="h-5 w-5 text-primary" />
                  Per Departemen
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-2">
                {summary?.employees.by_department &&
                  Object.entries(summary.employees.by_department)
                    .slice(0, 5)
                    .map(([dept, count]) => (
                      <div
                        key={dept}
                        className="flex items-center justify-between"
                      >
                        <span className="text-macos-sm text-muted-foreground">
                          {dept}
                        </span>
                        <span className="text-macos-sm font-medium text-foreground">
                          {count}
                        </span>
                      </div>
                    ))}
              </CardContent>
            </Card>
          )}

          {/* Employee-only: Attendance Progress */}
          {isEmployee && (
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="flex items-center gap-2 text-macos-base font-semibold">
                  <Award className="h-5 w-5 text-primary" />
                  Progres Bulan Ini
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <div className="flex justify-between text-sm mb-1">
                    <span className="text-muted-foreground">Kehadiran</span>
                    <span className="font-medium">22/23 hari</span>
                  </div>
                  <Progress value={95.6} className="h-2" />
                </div>
                <div>
                  <div className="flex justify-between text-sm mb-1">
                    <span className="text-muted-foreground">Tepat Waktu</span>
                    <span className="font-medium">21/22 hari</span>
                  </div>
                  <Progress value={95.4} className="h-2" />
                </div>
                <div>
                  <div className="flex justify-between text-sm mb-1">
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
