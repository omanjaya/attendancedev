import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from '@tanstack/react-router';
import { Calendar, Clock, CheckCircle, XCircle, AlertCircle, Download, Filter } from 'lucide-react';
import { PageLayout } from '@/components/shared/PageLayout';
import { StatsGrid } from '@/components/shared/StatsGrid';
import { ContentCard } from '@/components/shared/ContentCard';
import { getAttendance, getAttendanceStatistics } from '@/lib/api/attendance';
import { getEmployeeDashboardData } from '@/lib/api/employees';
import { useAuthStore } from '@/stores';
import { format, startOfMonth, endOfMonth, eachDayOfInterval, isSameDay, parseISO, isValid } from 'date-fns';
import { id } from 'date-fns/locale';

// Safe date formatting helper
const safeFormatDate = (dateStr: string | null | undefined, formatStr: string, fallback: string = '-') => {
  if (!dateStr) return fallback;
  try {
    const date = parseISO(dateStr);
    if (!isValid(date)) return fallback;
    return format(date, formatStr, { locale: id });
  } catch {
    return fallback;
  }
};

// Safe time formatting (for HH:mm format)
const safeFormatTime = (timeStr: string | null | undefined, fallback: string = '-') => {
  if (!timeStr) return fallback;
  try {
    // If it's just time like "07:30:00", prepend a date
    const date = parseISO(timeStr.includes('T') ? timeStr : `2000-01-01T${timeStr}`);
    if (!isValid(date)) return fallback;
    return format(date, 'HH:mm');
  } catch {
    return fallback;
  }
};

/**
 * Employee Attendance Page
 * View-only page for employees to see their own attendance history
 */
export function DesktopEmployeeAttendancePage() {
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const [selectedMonth, setSelectedMonth] = useState(new Date());
  const [viewMode, setViewMode] = useState<'calendar' | 'list'>('list');

  // Fetch employee's attendance data from API
  const { data: attendanceData } = useQuery({
    queryKey: ['employee', 'attendance', user?.id, format(selectedMonth, 'yyyy-MM')],
    queryFn: async () => {
      const [attendanceResponse, statsResponse] = await Promise.all([
        getAttendance({
          month: format(selectedMonth, 'yyyy-MM'),
          employee_id: user?.employee_id ? Number(user.employee_id) : undefined,
        }),
        getAttendanceStatistics(format(selectedMonth, 'yyyy-MM-01')),
      ]);

      // Transform API response to component format
      const records = attendanceResponse.data.map((record) => ({
        id: record.id,
        date: record.date,
        checkIn: record.check_in_time || record.check_in || null,
        checkOut: record.check_out_time || record.check_out || null,
        status: record.status as 'present' | 'late' | 'absent' | 'leave',
        location: typeof record.location === 'object' ? record.location?.address : undefined,
        notes: record.notes || null,
      }));

      // Calculate stats from response
      const stats = {
        totalDays: statsResponse?.total_employees || 22, // Use as working days estimate
        present: statsResponse?.present_today ?? statsResponse?.present ?? records.filter(r => r.status === 'present').length,
        late: statsResponse?.late_today ?? statsResponse?.late ?? records.filter(r => r.status === 'late').length,
        absent: statsResponse?.absent_today ?? statsResponse?.absent ?? records.filter(r => r.status === 'absent').length,
        onLeave: statsResponse?.on_leave ?? records.filter(r => r.status === 'leave').length,
      };

      return { stats, records };
    },
    enabled: !!user?.id,
  });

  // Fetch dashboard stats for schedule info
  const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD format
  const { data: dashboardStats } = useQuery({
    queryKey: ['employee', 'dashboard-stats', user?.id, today],
    queryFn: getEmployeeDashboardData,
    enabled: !!user?.id,
    staleTime: 0, // Always refetch on mount
  });

  const canAttend = dashboardStats?.schedule.today.can_attend ?? true;
  const scheduleMessage = dashboardStats?.schedule.today.message;

  const stats = [
    {
      label: 'Hadir',
      value: attendanceData?.stats.present || 0,
      description: `dari ${attendanceData?.stats.totalDays || 0} hari kerja`,
      icon: CheckCircle,
      color: 'success' as const,
      trend: 'up' as const,
      trendValue: `${Math.round(((attendanceData?.stats.present || 0) / (attendanceData?.stats.totalDays || 1)) * 100)}%`,
    },
    {
      label: 'Terlambat',
      value: attendanceData?.stats.late || 0,
      description: 'kali bulan ini',
      icon: Clock,
      color: 'warning' as const,
    },
    {
      label: 'Tidak Hadir',
      value: attendanceData?.stats.absent || 0,
      description: 'tanpa keterangan',
      icon: XCircle,
      color: 'destructive' as const,
    },
    {
      label: 'Cuti',
      value: attendanceData?.stats.onLeave || 0,
      description: 'hari cuti',
      icon: Calendar,
      color: 'info' as const,
    },
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'present':
        return 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20';
      case 'late':
        return 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20';
      case 'absent':
        return 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20';
      case 'leave':
        return 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20';
      default:
        return 'text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/20';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'present':
        return <CheckCircle className="h-4 w-4" />;
      case 'late':
        return <Clock className="h-4 w-4" />;
      case 'absent':
        return <XCircle className="h-4 w-4" />;
      case 'leave':
        return <Calendar className="h-4 w-4" />;
      default:
        return <AlertCircle className="h-4 w-4" />;
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'present':
        return 'Hadir';
      case 'late':
        return 'Terlambat';
      case 'absent':
        return 'Tidak Hadir';
      case 'leave':
        return 'Cuti';
      default:
        return status;
    }
  };

  const handleExport = () => {
    // TODO: Implement export functionality
    console.log('Export attendance data');
  };

  const handlePreviousMonth = () => {
    setSelectedMonth(new Date(selectedMonth.getFullYear(), selectedMonth.getMonth() - 1));
  };

  const handleNextMonth = () => {
    setSelectedMonth(new Date(selectedMonth.getFullYear(), selectedMonth.getMonth() + 1));
  };

  const renderCalendarView = () => {
    const monthStart = startOfMonth(selectedMonth);
    const monthEnd = endOfMonth(selectedMonth);
    const days = eachDayOfInterval({ start: monthStart, end: monthEnd });

    return (
      <ContentCard title="Kalender Kehadiran">
        <div className="grid grid-cols-7 gap-2">
          {/* Day headers */}
          {['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'].map((day) => (
            <div key={day} className="text-center text-xs font-medium text-muted-foreground py-2">
              {day}
            </div>
          ))}

          {/* Calendar days */}
          {days.map((day: Date) => {
            const record = attendanceData?.records.find((r: any) =>
              r.date && isSameDay(parseISO(r.date), day)
            );

            return (
              <div
                key={day.toString()}
                className={`aspect-square p-1 rounded-lg border ${record
                  ? getStatusColor(record.status)
                  : 'border-muted bg-muted/20'
                  }`}
              >
                <div className="text-xs font-medium text-center">
                  {format(day, 'd')}
                </div>
                {record && (
                  <div className="flex justify-center mt-1">
                    {getStatusIcon(record.status)}
                  </div>
                )}
              </div>
            );
          })}
        </div>

        {/* Legend */}
        <div className="mt-4 pt-4 border-t grid grid-cols-2 md:grid-cols-4 gap-2">
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 rounded bg-green-100 dark:bg-green-900/30" />
            <span className="text-xs">Hadir</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 rounded bg-yellow-100 dark:bg-yellow-900/30" />
            <span className="text-xs">Terlambat</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 rounded bg-red-100 dark:bg-red-900/30" />
            <span className="text-xs">Tidak Hadir</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 rounded bg-blue-100 dark:bg-blue-900/30" />
            <span className="text-xs">Cuti</span>
          </div>
        </div>
      </ContentCard>
    );
  };

  const renderListView = () => {
    return (
      <ContentCard title="Riwayat Kehadiran">
        <div className="space-y-2">
          {attendanceData?.records.map((record: any) => (
            <div
              key={record.id}
              className="p-4 rounded-lg border bg-card hover:bg-muted/50 transition-colors"
            >
              <div className="flex items-start justify-between gap-4">
                <div className="flex-1">
                  <div className="flex items-center gap-2 mb-2">
                    <span className="text-sm font-medium">
                      {safeFormatDate(record.date, 'EEEE, dd MMMM yyyy')}
                    </span>
                    <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${getStatusColor(record.status)}`}>
                      {getStatusIcon(record.status)}
                      {getStatusLabel(record.status)}
                    </span>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm text-muted-foreground">
                    <div className="flex items-center gap-2">
                      <Clock className="h-4 w-4" />
                      <span>
                        Check-in: {safeFormatTime(record.checkIn)}
                      </span>
                    </div>
                    <div className="flex items-center gap-2">
                      <Clock className="h-4 w-4" />
                      <span>
                        Check-out: {safeFormatTime(record.checkOut)}
                      </span>
                    </div>
                    {record.location && (
                      <div className="flex items-center gap-2">
                        <Calendar className="h-4 w-4" />
                        <span>{record.location}</span>
                      </div>
                    )}
                  </div>

                  {record.notes && (
                    <div className="mt-2 text-sm text-muted-foreground italic">
                      {record.notes}
                    </div>
                  )}
                </div>
              </div>
            </div>
          ))}

          {attendanceData?.records.length === 0 && (
            <div className="text-center py-12">
              <Calendar className="h-12 w-12 text-muted-foreground mx-auto mb-3 opacity-50" />
              <p className="text-sm text-muted-foreground">Tidak ada data kehadiran</p>
            </div>
          )}
        </div>
      </ContentCard>
    );
  };

  return (
    <PageLayout
      title="Kehadiran Saya"
      description="Riwayat kehadiran pribadi"
      actions={
        <button
          onClick={handleExport}
          className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors"
        >
          <Download className="h-4 w-4" />
          Export
        </button>
      }
    >
      {/* Stats Grid */}
      <StatsGrid stats={stats} />

      {/* Month selector and view mode toggle */}
      <div className="flex items-center justify-between gap-4 mt-6 mb-4">
        <div className="flex items-center gap-2">
          <button
            onClick={handlePreviousMonth}
            className="px-3 py-2 rounded-lg border hover:bg-muted transition-colors"
          >
            ←
          </button>
          <span className="text-sm font-medium min-w-[150px] text-center">
            {format(selectedMonth, 'MMMM yyyy', { locale: id })}
          </span>
          <button
            onClick={handleNextMonth}
            className="px-3 py-2 rounded-lg border hover:bg-muted transition-colors"
          >
            →
          </button>
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={() => setViewMode('list')}
            title="List View"
            className={`px-3 py-2 rounded-lg transition-colors ${viewMode === 'list'
              ? 'bg-primary text-primary-foreground'
              : 'border hover:bg-muted'
              }`}
          >
            <Filter className="h-4 w-4" />
          </button>
          <button
            onClick={() => setViewMode('calendar')}
            title="Calendar View"
            className={`px-3 py-2 rounded-lg transition-colors ${viewMode === 'calendar'
              ? 'bg-primary text-primary-foreground'
              : 'border hover:bg-muted'
              }`}
          >
            <Calendar className="h-4 w-4" />
          </button>
        </div>
      </div>

      {/* View content */}
      {viewMode === 'calendar' ? renderCalendarView() : renderListView()}

      {/* Quick action to check-in/out */}
      <ContentCard title="Aksi Cepat" className="mt-6">
        <div className="grid grid-cols-2 gap-4">
          <button
            onClick={() => canAttend && navigate({ to: '/shared/verify-face', search: { type: 'check-in' } })}
            disabled={!canAttend}
            className={`flex items-center justify-center gap-2 p-4 rounded-lg transition-colors border ${canAttend
              ? 'bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 border-green-200 dark:border-green-900'
              : 'bg-muted border-muted-foreground/20 cursor-not-allowed opacity-70'
              }`}
            title={!canAttend ? (scheduleMessage || 'Absensi tidak tersedia') : 'Check-In'}
          >
            <Clock className={`h-5 w-5 ${canAttend ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'}`} />
            <span className={`text-sm font-medium ${canAttend ? 'text-green-900 dark:text-green-100' : 'text-muted-foreground'}`}>
              {!canAttend && scheduleMessage ? scheduleMessage : 'Check-In'}
            </span>
          </button>
          <button
            onClick={() => navigate({ to: '/shared/verify-face', search: { type: 'check-out' } })}
            className="flex items-center justify-center gap-2 p-4 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors border border-red-200 dark:border-red-900"
          >
            <Clock className="h-5 w-5 text-red-600 dark:text-red-400" />
            <span className="text-sm font-medium text-red-900 dark:text-red-100">
              Check-Out
            </span>
          </button>
        </div>
      </ContentCard>
    </PageLayout>
  );
}
