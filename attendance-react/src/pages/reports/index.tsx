import { useState } from 'react';
import {
  Download,
  Calendar,
  TrendingUp,
  Users,
  Clock,
  PieChart as PieChartIcon,
  Wrench,
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
import { ReportBuilderContent } from './tabs/ReportBuilderContent';
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

// Mock data for charts
const monthlyAttendance = [
  { month: 'Jan', hadir: 95, terlambat: 3, tidak_hadir: 2 },
  { month: 'Feb', hadir: 93, terlambat: 4, tidak_hadir: 3 },
  { month: 'Mar', hadir: 96, terlambat: 2, tidak_hadir: 2 },
  { month: 'Apr', hadir: 94, terlambat: 4, tidak_hadir: 2 },
  { month: 'Mei', hadir: 97, terlambat: 2, tidak_hadir: 1 },
  { month: 'Jun', hadir: 95, terlambat: 3, tidak_hadir: 2 },
];

const departmentStats = [
  { name: 'IT', value: 25, color: 'hsl(var(--primary))' },
  { name: 'HR', value: 10, color: 'hsl(var(--success))' },
  { name: 'Finance', value: 15, color: 'hsl(var(--warning))' },
  { name: 'Marketing', value: 20, color: 'hsl(var(--destructive))' },
  { name: 'Operations', value: 45, color: 'hsl(var(--info))' },
  { name: 'Sales', value: 10, color: 'hsl(210 40% 60%)' },
];

const weeklyTrend = [
  { day: 'Sen', masuk: 118, keluar: 115 },
  { day: 'Sel', masuk: 120, keluar: 118 },
  { day: 'Rab', masuk: 115, keluar: 112 },
  { day: 'Kam', masuk: 122, keluar: 120 },
  { day: 'Jum', masuk: 110, keluar: 108 },
];

const leaveStats = [
  { type: 'Cuti Tahunan', used: 45, total: 100 },
  { type: 'Cuti Sakit', used: 12, total: 50 },
  { type: 'Cuti Melahirkan', used: 2, total: 10 },
  { type: 'Izin Khusus', used: 8, total: 20 },
];

export default function ReportsPage() {
  const [year, setYear] = useState('2024');

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-macos-2xl font-bold text-foreground">Laporan</h1>
          <p className="text-macos-sm text-muted-foreground">
            Analisis data kehadiran dan karyawan
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Select value={year} onValueChange={setYear}>
            <SelectTrigger className="w-[100px]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="2024">2024</SelectItem>
              <SelectItem value="2023">2023</SelectItem>
              <SelectItem value="2022">2022</SelectItem>
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
        {[
          { title: 'Rata-rata Kehadiran', value: '94.5%', icon: TrendingUp, color: 'text-success' },
          { title: 'Total Karyawan', value: '125', icon: Users, color: 'text-primary' },
          { title: 'Jam Kerja Rata-rata', value: '8.2 jam', icon: Clock, color: 'text-warning' },
          { title: 'Cuti Digunakan', value: '67', icon: Calendar, color: 'text-muted-foreground' },
        ].map((stat) => (
          <Card key={stat.title}>
            <CardContent className="flex items-center gap-4 p-6">
              <div className={`rounded-lg bg-muted p-3 ${stat.color}`}>
                <stat.icon className="h-5 w-5" />
              </div>
              <div>
                <p className="text-macos-xs text-muted-foreground">{stat.title}</p>
                <p className="text-macos-xl font-bold text-foreground">{stat.value}</p>
              </div>
            </CardContent>
          </Card>
        ))}
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
                <CardTitle className="flex items-center gap-2 text-macos-base">
                  <TrendingUp className="h-5 w-5 text-primary" />
                  Kehadiran Bulanan
                </CardTitle>
              </CardHeader>
              <CardContent>
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
                      <Bar dataKey="terlambat" name="Terlambat" fill="hsl(var(--warning))" radius={4} />
                      <Bar dataKey="tidak_hadir" name="Tidak Hadir" fill="hsl(var(--destructive))" radius={4} />
                    </BarChart>
                  </ResponsiveContainer>
                </div>
              </CardContent>
            </Card>

            {/* Weekly Trend */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-macos-base">
                  <Clock className="h-5 w-5 text-primary" />
                  Tren Mingguan
                </CardTitle>
              </CardHeader>
              <CardContent>
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
                <CardTitle className="flex items-center gap-2 text-macos-base">
                  <PieChartIcon className="h-5 w-5 text-primary" />
                  Distribusi Departemen
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="h-[300px]">
                  <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                      <Pie
                        data={departmentStats}
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
                        {departmentStats.map((entry, index) => (
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
              </CardContent>
            </Card>

            {/* Department List */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-macos-base">
                  <Users className="h-5 w-5 text-primary" />
                  Detail Departemen
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  {departmentStats.map((dept) => (
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
                          {((dept.value / 125) * 100).toFixed(1)}%
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Leave Tab */}
        <TabsContent value="leave" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-macos-base">
                <Calendar className="h-5 w-5 text-primary" />
                Penggunaan Cuti
              </CardTitle>
            </CardHeader>
            <CardContent>
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
