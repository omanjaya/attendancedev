import { useState } from 'react';
import {
  CheckCircle,
  Clock,
  XCircle,
  AlertTriangle,
  Download,
  Filter,
  Calendar,
  RefreshCw,
  LayoutGrid,
  List,
  ChevronLeft,
  ChevronRight,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { useAttendance, useAttendanceStatistics } from '@/hooks';
import type { AttendanceFilters, AttendanceStatus } from '@/types';

const statusConfig = {
  present: { label: 'Hadir', color: 'bg-success/10 text-success', icon: CheckCircle },
  late: { label: 'Terlambat', color: 'bg-warning/10 text-warning', icon: Clock },
  absent: { label: 'Tidak Hadir', color: 'bg-destructive/10 text-destructive', icon: XCircle },
  early_departure: { label: 'Pulang Awal', color: 'bg-warning/10 text-warning', icon: AlertTriangle },
};

export default function AttendanceHistoryPage() {
  const [viewMode, setViewMode] = useState<'table' | 'calendar'>('table');
  const [filterOpen, setFilterOpen] = useState(false);
  const [currentFilter, setCurrentFilter] = useState('month');
  const [page, setPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');

  // API hooks
  const filters: AttendanceFilters = {
    status: statusFilter !== 'all' ? statusFilter as AttendanceStatus : undefined,
    date_from: dateFrom || undefined,
    date_to: dateTo || undefined,
    page,
    per_page: 10,
  };
  const { data: attendanceData, isLoading, refetch } = useAttendance(filters);
  const { data: statistics, isLoading: isLoadingStats } = useAttendanceStatistics();

  // Stats from API
  const stats = {
    present: statistics?.present || 0,
    late: statistics?.late || 0,
    totalHours: 0, // Not available in API, would need calculation
    attendanceRate: statistics?.attendance_rate || 0,
  };

  const attendanceRecords = attendanceData?.data || [];
  const totalPages = attendanceData?.meta?.last_page || 1;

  const getStatusBadge = (status: string) => {
    const config = statusConfig[status as keyof typeof statusConfig];
    if (!config) return null;
    const Icon = config.icon;
    return (
      <Badge variant="outline" className={config.color}>
        <Icon className="h-3 w-3 mr-1" />
        {config.label}
      </Badge>
    );
  };

  return (
    <div className="p-4 space-y-4 sm:p-6 sm:space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Riwayat Kehadiran</h1>
          <p className="text-sm text-muted-foreground">
            Lacak kehadiran dan jam kerja Anda
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => setViewMode(viewMode === 'table' ? 'calendar' : 'table')}
          >
            {viewMode === 'table' ? (
              <>
                <LayoutGrid className="h-4 w-4 mr-2" />
                Calendar
              </>
            ) : (
              <>
                <List className="h-4 w-4 mr-2" />
                Table
              </>
            )}
          </Button>
          <Dialog open={filterOpen} onOpenChange={setFilterOpen}>
            <DialogTrigger asChild>
              <Button variant="outline" size="sm">
                <Filter className="h-4 w-4 mr-2" />
                Filter
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Filter Kehadiran</DialogTitle>
              </DialogHeader>
              <div className="space-y-4 pt-4">
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <label className="text-sm font-medium">Tanggal Mulai</label>
                    <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
                  </div>
                  <div className="space-y-2">
                    <label className="text-sm font-medium">Tanggal Selesai</label>
                    <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
                  </div>
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium">Status</label>
                  <Select value={statusFilter} onValueChange={setStatusFilter}>
                    <SelectTrigger>
                      <SelectValue placeholder="Semua Status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">Semua Status</SelectItem>
                      <SelectItem value="present">Hadir</SelectItem>
                      <SelectItem value="late">Terlambat</SelectItem>
                      <SelectItem value="absent">Tidak Hadir</SelectItem>
                      <SelectItem value="leave">Cuti</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="flex justify-end gap-2">
                  <Button variant="outline" onClick={() => {
                    setDateFrom('');
                    setDateTo('');
                    setStatusFilter('all');
                    setFilterOpen(false);
                  }}>
                    Reset
                  </Button>
                  <Button onClick={() => setFilterOpen(false)}>Terapkan Filter</Button>
                </div>
              </div>
            </DialogContent>
          </Dialog>
          <Button size="sm">
            <Download className="h-4 w-4 mr-2" />
            Export
          </Button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <Card className="bg-accent/50 border-none">
          <CardContent className="p-4">
            <div className="flex items-center justify-between mb-2">
              <CheckCircle className="h-5 w-5 text-success" />
              <Badge variant="outline" className="bg-success/10 text-success text-xs">
                Hadir
              </Badge>
            </div>
            {isLoadingStats ? (
              <Skeleton className="h-8 w-12" />
            ) : (
              <p className="text-2xl font-bold">{stats.present}</p>
            )}
            <p className="text-xs text-muted-foreground">Hari Hadir</p>
          </CardContent>
        </Card>

        <Card className="bg-accent/50 border-none">
          <CardContent className="p-4">
            <div className="flex items-center justify-between mb-2">
              <Clock className="h-5 w-5 text-warning" />
              <Badge variant="outline" className="bg-warning/10 text-warning text-xs">
                Telat
              </Badge>
            </div>
            {isLoadingStats ? (
              <Skeleton className="h-8 w-12" />
            ) : (
              <p className="text-2xl font-bold">{stats.late}</p>
            )}
            <p className="text-xs text-muted-foreground">Terlambat</p>
          </CardContent>
        </Card>

        <Card className="bg-accent/50 border-none">
          <CardContent className="p-4">
            <div className="flex items-center justify-between mb-2">
              <Clock className="h-5 w-5 text-primary" />
              <Badge variant="outline" className="bg-primary/10 text-primary text-xs">
                Jam
              </Badge>
            </div>
            {isLoadingStats ? (
              <Skeleton className="h-8 w-12" />
            ) : (
              <p className="text-2xl font-bold">{stats.totalHours}h</p>
            )}
            <p className="text-xs text-muted-foreground">Total Jam</p>
          </CardContent>
        </Card>

        <Card className="bg-accent/50 border-none">
          <CardContent className="p-4">
            <div className="flex items-center justify-between mb-2">
              <CheckCircle className="h-5 w-5 text-chart-5" />
              <Badge variant="outline" className="bg-chart-5/10 text-chart-5 text-xs">
                Rate
              </Badge>
            </div>
            {isLoadingStats ? (
              <Skeleton className="h-8 w-16" />
            ) : (
              <p className="text-2xl font-bold">{stats.attendanceRate}%</p>
            )}
            <p className="text-xs text-muted-foreground">Kehadiran</p>
          </CardContent>
        </Card>
      </div>

      {/* Main Content */}
      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {/* Table */}
        <div className="lg:col-span-3">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <div>
                <CardTitle className="text-lg">Catatan Kehadiran</CardTitle>
                <p className="text-sm text-muted-foreground">
                  Riwayat lengkap check-in dan check-out
                </p>
              </div>
              <Button variant="ghost" size="icon" onClick={() => refetch()} disabled={isLoading}>
                <RefreshCw className={`h-4 w-4 ${isLoading ? 'animate-spin' : ''}`} />
              </Button>
            </CardHeader>
            <CardContent>
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="border-b">
                      <th className="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                        Tanggal
                      </th>
                      <th className="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                        Check In
                      </th>
                      <th className="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                        Check Out
                      </th>
                      <th className="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                        Jam Kerja
                      </th>
                      <th className="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                        Status
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {isLoading ? (
                      Array.from({ length: 5 }).map((_, i) => (
                        <tr key={i} className="border-b last:border-0">
                          <td className="px-4 py-3"><Skeleton className="h-4 w-24" /></td>
                          <td className="px-4 py-3"><Skeleton className="h-4 w-16" /></td>
                          <td className="px-4 py-3"><Skeleton className="h-4 w-16" /></td>
                          <td className="px-4 py-3"><Skeleton className="h-4 w-16" /></td>
                          <td className="px-4 py-3"><Skeleton className="h-6 w-20" /></td>
                        </tr>
                      ))
                    ) : attendanceRecords.length === 0 ? (
                      <tr>
                        <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                          Tidak ada data kehadiran
                        </td>
                      </tr>
                    ) : (
                      attendanceRecords.map((record) => (
                        <tr key={record.id} className="border-b last:border-0 hover:bg-muted/50">
                          <td className="px-4 py-3 text-sm">
                            {new Date(record.date).toLocaleDateString('id-ID', {
                              weekday: 'short',
                              day: 'numeric',
                              month: 'short',
                            })}
                          </td>
                          <td className="px-4 py-3 text-sm font-mono">{record.check_in || '-'}</td>
                          <td className="px-4 py-3 text-sm font-mono">{record.check_out || '-'}</td>
                          <td className="px-4 py-3 text-sm">{record.work_hours ? `${record.work_hours.toFixed(1)}h` : '-'}</td>
                          <td className="px-4 py-3">{getStatusBadge(record.status)}</td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>

              {/* Pagination */}
              <div className="flex items-center justify-between pt-4">
                <p className="text-sm text-muted-foreground">
                  Halaman {page} dari {totalPages}
                </p>
                <div className="flex items-center gap-2">
                  <Button
                    variant="outline"
                    size="icon"
                    disabled={page <= 1}
                    onClick={() => setPage(p => Math.max(1, p - 1))}
                  >
                    <ChevronLeft className="h-4 w-4" />
                  </Button>
                  <Button
                    variant="outline"
                    size="icon"
                    disabled={page >= totalPages}
                    onClick={() => setPage(p => p + 1)}
                  >
                    <ChevronRight className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Quick Filter */}
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-base">Filter Cepat</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              <button
                onClick={() => setCurrentFilter('today')}
                className={`w-full text-left px-4 py-3 rounded-lg transition-colors ${
                  currentFilter === 'today'
                    ? 'bg-primary/10 border border-primary/20'
                    : 'bg-muted hover:bg-muted/80'
                }`}
              >
                <div className="flex items-center gap-3">
                  <Calendar className="h-5 w-5 text-primary" />
                  <div>
                    <p className="font-medium text-sm">Hari Ini</p>
                    <p className="text-xs text-muted-foreground">
                      {new Date().toLocaleDateString('id-ID')}
                    </p>
                  </div>
                </div>
              </button>

              <button
                onClick={() => setCurrentFilter('week')}
                className={`w-full text-left px-4 py-3 rounded-lg transition-colors ${
                  currentFilter === 'week'
                    ? 'bg-primary/10 border border-primary/20'
                    : 'bg-muted hover:bg-muted/80'
                }`}
              >
                <div className="flex items-center gap-3">
                  <Calendar className="h-5 w-5 text-success" />
                  <div>
                    <p className="font-medium text-sm">Minggu Ini</p>
                    <p className="text-xs text-muted-foreground">7 hari terakhir</p>
                  </div>
                </div>
              </button>

              <button
                onClick={() => setCurrentFilter('month')}
                className={`w-full text-left px-4 py-3 rounded-lg transition-colors ${
                  currentFilter === 'month'
                    ? 'bg-primary/10 border border-primary/20'
                    : 'bg-muted hover:bg-muted/80'
                }`}
              >
                <div className="flex items-center gap-3">
                  <Calendar className="h-5 w-5 text-chart-5" />
                  <div>
                    <p className="font-medium text-sm">Bulan Ini</p>
                    <p className="text-xs text-muted-foreground">November 2024</p>
                  </div>
                </div>
              </button>
            </CardContent>
          </Card>

          {/* Status Legend */}
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-base">Status Legend</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {Object.entries(statusConfig).map(([key, config]) => (
                <div key={key} className="flex items-center justify-between">
                  <span className="text-sm text-muted-foreground">{config.label}</span>
                  <Badge variant="outline" className={`${config.color} text-xs`}>
                    {key}
                  </Badge>
                </div>
              ))}
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
