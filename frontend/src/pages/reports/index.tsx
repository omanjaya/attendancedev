import { useState } from 'react';
import {
  Download,
  Calendar,
  TrendingUp,
  Users,
  Clock,
  PieChart as PieChartIcon,
  Wrench,
  Loader2,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Skeleton } from '@/components/ui/skeleton';
import { ReportBuilderContent } from './tabs/ReportBuilderContent';
import {
  useReportSummary,
  useMonthlyAttendance,
  useWeeklyTrend,
  useDepartmentStats,
  useLeaveStats,
} from '@/hooks/use-reports';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  LineChart,
  Line,
  Legend,
} from 'recharts';

// Default colors for department chart
const DEPARTMENT_COLORS = [
  'hsl(var(--primary))',
  'hsl(var(--success))',
  'hsl(var(--warning))',
  'hsl(var(--destructive))',
  'hsl(210 40% 60%)',
  'hsl(280 40% 60%)',
];

export default function ReportsPage() {
  const [year, setYear] = useState(new Date().getFullYear().toString());

  const filters = { year: parseInt(year) };

  // Fetch data from API
  const { data: summary, isLoading: summaryLoading } = useReportSummary(filters);
  const { data: monthlyAttendance = [], isLoading: monthlyLoading } = useMonthlyAttendance(filters);
  const { data: weeklyTrend = [], isLoading: weeklyLoading } = useWeeklyTrend(filters);
  const { data: departmentStats = [], isLoading: departmentLoading } = useDepartmentStats(filters);
  const { data: leaveStats = [], isLoading: leaveLoading } = useLeaveStats(filters);

  // Add colors to department stats if not provided
  const departmentStatsWithColors = departmentStats.map((dept, index) => ({
    ...dept,
    color: dept.color || DEPARTMENT_COLORS[index % DEPARTMENT_COLORS.length],
  }));

  const totalEmployees = departmentStats.reduce((sum, dept) => sum + dept.value, 0) || 125;

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Laporan</h1>
          <p className="text-sm text-muted-foreground">
            Analisis data kehadiran dan karyawan
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Select value={year} onValueChange={setYear}>
            <SelectTrigger className="w-[100px]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="2025">2025</SelectItem>
              <SelectItem value="2024">2024</SelectItem>
              <SelectItem value="2023">2023</SelectItem>
            </SelectContent>
          </Select>
          <Button variant="outline">
            <Download className="mr-2 h-4 w-4" />
            Export
          </Button>
        </div>
      </div>

      {/* Summary Cards */}
      <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {summaryLoading ? (
          <>
            {[1, 2, 3, 4].map((i) => (
              <Card key={i}>
                <CardContent className="flex items-center gap-4 p-6">
                  <Skeleton className="h-11 w-11 rounded-lg" />
                  <div className="space-y-2">
                    <Skeleton className="h-3 w-24" />
                    <Skeleton className="h-6 w-16" />
                  </div>
                </CardContent>
              </Card>
            ))}
          </>
        ) : (
          <>
            <Card>
              <CardContent className="flex items-center gap-4 p-6">
                <div className="rounded-lg bg-muted p-3 text-success">
                  <TrendingUp className="h-5 w-5" />
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Rata-rata Kehadiran</p>
                  <p className="text-xl font-bold text-foreground">
                    {summary?.average_attendance_rate?.toFixed(1) || 0}%
                  </p>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="flex items-center gap-4 p-6">
                <div className="rounded-lg bg-muted p-3 text-primary">
                  <Users className="h-5 w-5" />
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Total Karyawan</p>
                  <p className="text-xl font-bold text-foreground">
                    {summary?.total_employees || 0}
                  </p>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="flex items-center gap-4 p-6">
                <div className="rounded-lg bg-muted p-3 text-warning">
                  <Clock className="h-5 w-5" />
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Jam Kerja Rata-rata</p>
                  <p className="text-xl font-bold text-foreground">
                    {summary?.average_working_hours?.toFixed(1) || 0} jam
                  </p>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="flex items-center gap-4 p-6">
                <div className="rounded-lg bg-muted p-3 text-muted-foreground">
                  <Calendar className="h-5 w-5" />
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Cuti Digunakan</p>
                  <p className="text-xl font-bold text-foreground">
                    {summary?.total_leave_used || 0}
                  </p>
                </div>
              </CardContent>
            </Card>
          </>
        )}
      </div>

      {/* Tabs */}
      <Tabs defaultValue="attendance" className="space-y-6">
        <TabsList>
          <TabsTrigger value="attendance">
            <Clock className="mr-2 h-4 w-4" />
            Kehadiran
          </TabsTrigger>
          <TabsTrigger value="employees">
            <Users className="mr-2 h-4 w-4" />
            Karyawan
          </TabsTrigger>
          <TabsTrigger value="leave">
            <Calendar className="mr-2 h-4 w-4" />
            Cuti
          </TabsTrigger>
          <TabsTrigger value="builder">
            <Wrench className="mr-2 h-4 w-4" />
            Builder
          </TabsTrigger>
        </TabsList>

        {/* Attendance Tab */}
        <TabsContent value="attendance" className="space-y-6">
          <div className="grid gap-6 lg:grid-cols-2">
            {/* Monthly Attendance Chart */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                  <TrendingUp className="h-5 w-5 text-primary" />
                  Kehadiran Bulanan
                </CardTitle>
              </CardHeader>
              <CardContent>
                {monthlyLoading ? (
                  <div className="flex h-[300px] items-center justify-center">
                    <Loader2 className="h-8 w-8 animate-spin text-primary" />
                  </div>
                ) : (
                  <div className="h-[300px]">
                    <ResponsiveContainer width="100%" height="100%">
                      <BarChart data={monthlyAttendance}>
                        <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                        <XAxis dataKey="month" tick={{ fontSize: 12 }} />
                        <YAxis tick={{ fontSize: 12 }} />
                        <Tooltip
                          contentStyle={{
                            backgroundColor: 'hsl(var(--card))',
                            border: '1px solid hsl(var(--border))',
                            borderRadius: '8px',
                          }}
                        />
                        <Legend />
                        <Bar dataKey="hadir" name="Hadir" fill="hsl(var(--success))" radius={4} />
                        <Bar
                          dataKey="terlambat"
                          name="Terlambat"
                          fill="hsl(var(--warning))"
                          radius={4}
                        />
                        <Bar
                          dataKey="tidak_hadir"
                          name="Tidak Hadir"
                          fill="hsl(var(--destructive))"
                          radius={4}
                        />
                      </BarChart>
                    </ResponsiveContainer>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Weekly Trend */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                  <Clock className="h-5 w-5 text-primary" />
                  Tren Mingguan
                </CardTitle>
              </CardHeader>
              <CardContent>
                {weeklyLoading ? (
                  <div className="flex h-[300px] items-center justify-center">
                    <Loader2 className="h-8 w-8 animate-spin text-primary" />
                  </div>
                ) : (
                  <div className="h-[300px]">
                    <ResponsiveContainer width="100%" height="100%">
                      <LineChart data={weeklyTrend}>
                        <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                        <XAxis dataKey="day" tick={{ fontSize: 12 }} />
                        <YAxis tick={{ fontSize: 12 }} />
                        <Tooltip
                          contentStyle={{
                            backgroundColor: 'hsl(var(--card))',
                            border: '1px solid hsl(var(--border))',
                            borderRadius: '8px',
                          }}
                        />
                        <Legend />
                        <Line
                          type="monotone"
                          dataKey="masuk"
                          name="Check In"
                          stroke="hsl(var(--success))"
                          strokeWidth={2}
                          dot={{ fill: 'hsl(var(--success))' }}
                        />
                        <Line
                          type="monotone"
                          dataKey="keluar"
                          name="Check Out"
                          stroke="hsl(var(--primary))"
                          strokeWidth={2}
                          dot={{ fill: 'hsl(var(--primary))' }}
                        />
                      </LineChart>
                    </ResponsiveContainer>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Employees Tab */}
        <TabsContent value="employees" className="space-y-6">
          <div className="grid gap-6 lg:grid-cols-2">
            {/* Department Distribution */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                  <PieChartIcon className="h-5 w-5 text-primary" />
                  Distribusi Departemen
                </CardTitle>
              </CardHeader>
              <CardContent>
                {departmentLoading ? (
                  <div className="flex h-[300px] items-center justify-center">
                    <Loader2 className="h-8 w-8 animate-spin text-primary" />
                  </div>
                ) : (
                  <div className="h-[300px]">
                    <ResponsiveContainer width="100%" height="100%">
                      <PieChart>
                        <Pie
                          data={departmentStatsWithColors}
                          cx="50%"
                          cy="50%"
                          innerRadius={60}
                          outerRadius={100}
                          paddingAngle={2}
                          dataKey="value"
                          label={({ name, percent }) =>
                            `${name} ${((percent ?? 0) * 100).toFixed(0)}%`
                          }
                          labelLine={{ stroke: 'hsl(var(--muted-foreground))' }}
                        >
                          {departmentStatsWithColors.map((entry, index) => (
                            <Cell key={`cell-${index}`} fill={entry.color} />
                          ))}
                        </Pie>
                        <Tooltip
                          contentStyle={{
                            backgroundColor: 'hsl(var(--card))',
                            border: '1px solid hsl(var(--border))',
                            borderRadius: '8px',
                          }}
                        />
                      </PieChart>
                    </ResponsiveContainer>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Department List */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                  <Users className="h-5 w-5 text-primary" />
                  Detail Departemen
                </CardTitle>
              </CardHeader>
              <CardContent>
                {departmentLoading ? (
                  <div className="space-y-4">
                    {[1, 2, 3, 4, 5].map((i) => (
                      <div key={i} className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                          <Skeleton className="h-3 w-3 rounded-full" />
                          <Skeleton className="h-4 w-24" />
                        </div>
                        <Skeleton className="h-4 w-20" />
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="space-y-4">
                    {departmentStatsWithColors.map((dept) => (
                      <div key={dept.name} className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                          <div
                            className="h-3 w-3 rounded-full"
                            style={{ backgroundColor: dept.color }}
                          />
                          <span className="text-sm">{dept.name}</span>
                        </div>
                        <div className="flex items-center gap-4">
                          <span className="text-sm font-medium">{dept.value} orang</span>
                          <span className="text-xs text-muted-foreground">
                            {((dept.value / totalEmployees) * 100).toFixed(1)}%
                          </span>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Leave Tab */}
        <TabsContent value="leave" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <Calendar className="h-5 w-5 text-primary" />
                Penggunaan Cuti
              </CardTitle>
            </CardHeader>
            <CardContent>
              {leaveLoading ? (
                <div className="space-y-6">
                  {[1, 2, 3, 4].map((i) => (
                    <div key={i} className="space-y-2">
                      <div className="flex items-center justify-between">
                        <Skeleton className="h-4 w-24" />
                        <Skeleton className="h-4 w-20" />
                      </div>
                      <Skeleton className="h-2 w-full rounded-full" />
                    </div>
                  ))}
                </div>
              ) : (
                <div className="space-y-6">
                  {leaveStats.map((leave) => (
                    <div key={leave.type} className="space-y-2">
                      <div className="flex items-center justify-between text-sm">
                        <span>{leave.type}</span>
                        <span className="font-medium">
                          {leave.used} / {leave.total} hari
                        </span>
                      </div>
                      <div className="h-2 overflow-hidden rounded-full bg-muted">
                        <div
                          className="h-full rounded-full bg-primary transition-all"
                          style={{ width: `${(leave.used / leave.total) * 100}%` }}
                        />
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Builder Tab */}
        <TabsContent value="builder" className="mt-0">
          <ReportBuilderContent />
        </TabsContent>
      </Tabs>
    </div>
  );
}
