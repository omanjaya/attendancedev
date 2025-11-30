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
import { StatsGrid, PageHeader, type StatItem } from '@/components/shared';
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
    <div className="space-y-6 p-6">
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
        <Card className="border-l-4 border-l-primary">
          <CardContent className="p-4">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
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
        <Card className="border-l-4 border-l-warning">
          <CardContent className="p-4">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-warning/10">
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
              <QuickActionLink
                href="/face-recognition"
                icon={Clock}
                title="Absen Sekarang"
                description="Check-in / Check-out"
                iconColor="primary"
              />
              <QuickActionLink
                href="/leave"
                icon={CalendarOff}
                title={isAdmin || isManager ? 'Kelola Cuti' : 'Ajukan Cuti'}
                description={isAdmin || isManager ? 'Review pengajuan' : 'Buat pengajuan baru'}
                iconColor="warning"
              />
              {(isAdmin || isManager) && (
                <>
                  <QuickActionLink
                    href="/employees"
                    icon={Users}
                    title="Data Karyawan"
                    description="Kelola karyawan"
                    iconColor="primary"
                  />
                  <QuickActionLink
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
                  <QuickActionLink
                    href="/payroll"
                    icon={DollarSign}
                    title="Slip Gaji"
                    description="Lihat slip gaji"
                    iconColor="success"
                  />
                  <QuickActionLink
                    href="/schedules"
                    icon={Calendar}
                    title="Jadwal Saya"
                    description="Lihat jadwal kerja"
                    iconColor="info"
                  />
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
interface QuickActionLinkProps {
  href: string;
  icon: React.ComponentType<{ className?: string }>;
  title: string;
  description: string;
  iconColor: 'primary' | 'success' | 'warning' | 'destructive' | 'info';
}

const iconColorClasses = {
  primary: 'bg-primary/10 text-primary',
  success: 'bg-success/10 text-success',
  warning: 'bg-warning/10 text-warning',
  destructive: 'bg-destructive/10 text-destructive',
  info: 'bg-info/10 text-info',
};

function QuickActionLink({ href, icon: Icon, title, description, iconColor }: QuickActionLinkProps) {
  return (
    <a
      href={href}
      className="flex items-center gap-3 rounded-lg border border-border bg-card p-3 text-left transition-all hover:bg-muted hover:shadow-sm"
    >
      <div className={`rounded-lg p-2 ${iconColorClasses[iconColor]}`}>
        <Icon className="h-4 w-4" />
      </div>
      <div className="flex-1">
        <p className="text-sm font-medium text-foreground">{title}</p>
        <p className="text-xs text-muted-foreground">{description}</p>
      </div>
      <ArrowRight className="h-4 w-4 text-muted-foreground" />
    </a>
  );
}

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
