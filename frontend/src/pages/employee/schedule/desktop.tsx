import { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Calendar, Clock, Download, ChevronLeft, ChevronRight, List, CalendarOff, AlertCircle, CheckCircle2 } from 'lucide-react';
import { PageLayout } from '@/components/shared/PageLayout';
import { ContentCard } from '@/components/shared/ContentCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';
import { useAuthStore, useNotificationStore } from '@/stores';
import { getMySchedule } from '@/lib/api/schedules';
import { getEmployeeDashboardData } from '@/lib/api/employees';
import {
  format,
  startOfMonth,
  endOfMonth,
  eachDayOfInterval,
  parseISO,
  addMonths,
  subMonths,
  startOfWeek,
  endOfWeek,
  isToday,
} from 'date-fns';
import { id } from 'date-fns/locale';

interface DaySchedule {
  date: Date;
  isWorkingDay: boolean;
  isHoliday: boolean;
  holidayName?: string;
  isToday: boolean;
  startTime?: string;
  endTime?: string;
}

/**
 * Employee Schedule Page
 * Read-only view for employees to see their assigned schedules
 */
export function DesktopEmployeeSchedulePage() {
  const { user } = useAuthStore();
  const { error: showError } = useNotificationStore();
  const [selectedMonth, setSelectedMonth] = useState(new Date());
  const [viewMode, setViewMode] = useState<'calendar' | 'list'>('calendar');

  // Fetch employee's schedule data
  const { data: scheduleResponse, isLoading: isLoadingSchedule } = useQuery({
    queryKey: ['employee', 'my-schedule', format(selectedMonth, 'yyyy-MM')],
    queryFn: () => getMySchedule({
      month: selectedMonth.getMonth() + 1,
      year: selectedMonth.getFullYear(),
    }),
  });

  // Fetch dashboard data for effective schedule info
  const { data: dashboardData } = useQuery({
    queryKey: ['employee', 'dashboard', user?.id],
    queryFn: getEmployeeDashboardData,
  });

  const schedule = scheduleResponse?.schedule;
  const effectiveSchedule = dashboardData?.schedule;

  // Process schedule data to create day-by-day view
  const calendarData = useMemo(() => {
    if (!schedule) return [];

    const monthStart = startOfMonth(selectedMonth);
    const monthEnd = endOfMonth(selectedMonth);
    const calendarStart = startOfWeek(monthStart, { weekStartsOn: 0 });
    const calendarEnd = endOfWeek(monthEnd, { weekStartsOn: 0 });
    const days = eachDayOfInterval({ start: calendarStart, end: calendarEnd });

    const workingDaysSet = new Set(schedule.working_days || []);

    return days.map((day): DaySchedule => {
      const dateStr = format(day, 'yyyy-MM-dd');
      const isWorkingDay = workingDaysSet.has(dateStr);

      return {
        date: day,
        isWorkingDay,
        isHoliday: false, // Will be handled by holiday_conflicts if available
        isToday: isToday(day),
        startTime: isWorkingDay ? schedule.default_start_time : undefined,
        endTime: isWorkingDay ? schedule.default_end_time : undefined,
      };
    });
  }, [schedule, selectedMonth]);

  const handleExport = () => {
    showError('Export', 'Fitur export akan segera tersedia');
  };

  const handlePreviousMonth = () => {
    setSelectedMonth(subMonths(selectedMonth, 1));
  };

  const handleNextMonth = () => {
    setSelectedMonth(addMonths(selectedMonth, 1));
  };

  const getShiftColor = (isWorkingDay: boolean, isToday: boolean) => {
    if (!isWorkingDay) return '';
    if (isToday) return 'bg-primary/10 border-primary text-primary';
    return 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300';
  };

  const renderCalendarView = () => {
    if (!schedule) {
      return (
        <ContentCard title="Kalender Jadwal">
          <div className="text-center py-12">
            <CalendarOff className="h-16 w-16 text-muted-foreground mx-auto mb-4 opacity-50" />
            <h3 className="text-lg font-semibold mb-2">Belum Ada Jadwal</h3>
            <p className="text-sm text-muted-foreground">
              Anda belum memiliki jadwal untuk bulan {format(selectedMonth, 'MMMM yyyy', { locale: id })}.
              <br />
              Hubungi administrator untuk penugasan jadwal.
            </p>
          </div>
        </ContentCard>
      );
    }

    return (
      <ContentCard title="Kalender Jadwal">
        <div className="grid grid-cols-7 gap-1 md:gap-2">
          {/* Day headers */}
          {['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'].map((day) => (
            <div key={day} className="text-center text-xs font-semibold text-muted-foreground py-2">
              {day}
            </div>
          ))}

          {/* Calendar days */}
          {calendarData.map((dayData) => {
            const isCurrentMonth = dayData.date.getMonth() === selectedMonth.getMonth();

            return (
              <div
                key={dayData.date.toString()}
                className={`min-h-[80px] md:min-h-[100px] p-1 md:p-2 rounded-lg border transition-colors ${dayData.isToday
                  ? 'border-primary bg-primary/5'
                  : isCurrentMonth
                    ? dayData.isWorkingDay
                      ? 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20'
                      : 'border-border bg-card hover:bg-muted/50'
                    : 'border-muted bg-muted/20 opacity-50'
                  }`}
              >
                <div
                  className={`text-xs md:text-sm font-medium mb-1 ${dayData.isToday
                    ? 'text-primary font-bold'
                    : isCurrentMonth
                      ? 'text-foreground'
                      : 'text-muted-foreground'
                    }`}
                >
                  {format(dayData.date, 'd')}
                </div>

                {dayData.isWorkingDay && isCurrentMonth && (
                  <div className="space-y-1">
                    <div className={`text-[10px] md:text-xs p-1 rounded ${getShiftColor(dayData.isWorkingDay, dayData.isToday)}`}>
                      <div className="font-medium truncate">Masuk</div>
                      <div className="truncate">{dayData.startTime}</div>
                    </div>
                  </div>
                )}

                {dayData.isHoliday && isCurrentMonth && (
                  <Badge variant="secondary" className="text-[10px]">
                    {dayData.holidayName || 'Libur'}
                  </Badge>
                )}
              </div>
            );
          })}
        </div>

        {/* Legend */}
        <div className="mt-4 pt-4 border-t flex flex-wrap gap-4 text-xs">
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 rounded bg-green-100 dark:bg-green-900/30 border border-green-200" />
            <span>Hari Kerja</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 rounded bg-primary/10 border border-primary" />
            <span>Hari Ini</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 rounded bg-muted border border-muted" />
            <span>Libur</span>
          </div>
        </div>
      </ContentCard>
    );
  };

  const renderListView = () => {
    if (!schedule) {
      return (
        <ContentCard title="Daftar Jadwal">
          <div className="text-center py-12">
            <CalendarOff className="h-12 w-12 text-muted-foreground mx-auto mb-3 opacity-50" />
            <p className="text-sm text-muted-foreground">Tidak ada jadwal untuk bulan ini</p>
          </div>
        </ContentCard>
      );
    }

    const workingDays = (schedule.working_days || [])
      .map(d => parseISO(d))
      .filter(d => d.getMonth() === selectedMonth.getMonth())
      .sort((a, b) => a.getTime() - b.getTime());

    const upcomingDays = workingDays.filter(d => d >= new Date() || isToday(d));
    const pastDays = workingDays.filter(d => d < new Date() && !isToday(d));

    return (
      <div className="space-y-6">
        {/* Upcoming Schedules */}
        <ContentCard title="Jadwal Mendatang">
          <div className="space-y-3">
            {upcomingDays.length > 0 ? (
              upcomingDays.slice(0, 10).map((day) => (
                <div
                  key={day.toString()}
                  className={`p-4 rounded-lg border transition-colors ${isToday(day)
                    ? 'border-primary bg-primary/5'
                    : 'border-border bg-card hover:bg-muted/50'
                    }`}
                >
                  <div className="flex items-start justify-between gap-4 mb-3">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-1">
                        <h3 className="text-sm font-semibold">
                          {format(day, 'EEEE, dd MMMM yyyy', { locale: id })}
                        </h3>
                        {isToday(day) && (
                          <span className="text-xs px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 font-medium">
                            Hari Ini
                          </span>
                        )}
                      </div>
                      <Badge variant="default" className="gap-1">
                        <CheckCircle2 className="h-3 w-3" />
                        Hari Kerja
                      </Badge>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                    <div className="flex items-center gap-2 text-muted-foreground">
                      <Clock className="h-4 w-4" />
                      <span>{schedule.default_start_time} - {schedule.default_end_time}</span>
                    </div>

                  </div>
                </div>
              ))
            ) : (
              <div className="text-center py-12">
                <Calendar className="h-12 w-12 text-muted-foreground mx-auto mb-3 opacity-50" />
                <p className="text-sm text-muted-foreground">Tidak ada jadwal mendatang bulan ini</p>
              </div>
            )}
          </div>
        </ContentCard>

        {/* Past Schedules */}
        {pastDays.length > 0 && (
          <ContentCard title="Riwayat Jadwal">
            <div className="space-y-2">
              {pastDays.slice(-5).map((day) => (
                <div
                  key={day.toString()}
                  className="p-3 rounded-lg border bg-card hover:bg-muted/50 transition-colors opacity-75"
                >
                  <div className="flex items-center justify-between gap-4">
                    <div className="flex-1">
                      <div className="text-sm font-medium mb-1">
                        {format(day, 'EEEE, dd MMM yyyy', { locale: id })}
                      </div>
                      <div className="flex items-center gap-3 text-xs text-muted-foreground">
                        <Badge variant="outline">Hari Kerja</Badge>
                        <span>{schedule.default_start_time} - {schedule.default_end_time}</span>

                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </ContentCard>
        )}
      </div>
    );
  };

  // Render loading skeleton
  if (isLoadingSchedule) {
    return (
      <PageLayout
        title="Jadwal Saya"
        description="Lihat jadwal kerja yang telah ditetapkan"
      >
        <div className="space-y-6">
          <Skeleton className="h-24 w-full rounded-lg" />
          <div className="flex justify-between">
            <Skeleton className="h-10 w-48" />
            <Skeleton className="h-10 w-24" />
          </div>
          <Skeleton className="h-96 w-full rounded-lg" />
        </div>
      </PageLayout>
    );
  }

  return (
    <PageLayout
      title="Jadwal Saya"
      description="Lihat jadwal kerja yang telah ditetapkan"
      actions={
        <Button onClick={handleExport} variant="outline">
          <Download className="h-4 w-4 mr-2" />
          Export
        </Button>
      }
    >
      {/* Today's Effective Schedule Info */}
      {effectiveSchedule && (
        <Alert className={`mb-6 ${effectiveSchedule.can_attend
          ? 'border-green-200 bg-green-50 dark:bg-green-900/20'
          : 'border-yellow-200 bg-yellow-50 dark:bg-yellow-900/20'}`}
        >
          {effectiveSchedule.can_attend ? (
            <CheckCircle2 className="h-5 w-5 text-green-600" />
          ) : (
            <AlertCircle className="h-5 w-5 text-yellow-600" />
          )}
          <AlertTitle className="text-sm font-semibold">
            Status Jadwal Hari Ini
          </AlertTitle>
          <AlertDescription className="text-sm">
            {effectiveSchedule.can_attend ? (
              <>
                Anda dapat melakukan absensi hari ini.
                {effectiveSchedule.start_time && effectiveSchedule.end_time && (
                  <> Jam kerja: <strong>{effectiveSchedule.start_time} - {effectiveSchedule.end_time}</strong></>
                )}
              </>
            ) : (
              <>
                {effectiveSchedule.message || 'Anda tidak memiliki jadwal kerja hari ini.'}
                {effectiveSchedule.schedule_type === 'holiday' && (
                  <> ({effectiveSchedule.holiday_name || 'Hari Libur'})</>
                )}
              </>
            )}
          </AlertDescription>
        </Alert>
      )}

      {/* Schedule Info Card */}
      {schedule && (
        <ContentCard className="mb-6 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
          <div className="flex items-start gap-3">
            <Calendar className="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5" />
            <div className="flex-1">
              <h3 className="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-1">
                {schedule.name}
              </h3>
              <div className="flex flex-wrap gap-4 text-xs text-blue-700 dark:text-blue-300">
                <div className="flex items-center gap-1">
                  <Clock className="h-3.5 w-3.5" />
                  <span>{schedule.default_start_time} - {schedule.default_end_time}</span>
                </div>
                <div>
                  <Badge variant="outline" className="text-[10px]">
                    {schedule.total_working_days} hari kerja
                  </Badge>
                </div>
              </div>
            </div>
          </div>
        </ContentCard>
      )}

      {/* Month selector and view mode toggle */}
      <div className="flex items-center justify-between gap-4 mb-4">
        <div className="flex items-center gap-2">
          <Button
            variant="outline"
            size="icon"
            onClick={handlePreviousMonth}
            aria-label="Previous month"
          >
            <ChevronLeft className="h-4 w-4" />
          </Button>
          <span className="text-sm font-semibold min-w-[150px] text-center">
            {format(selectedMonth, 'MMMM yyyy', { locale: id })}
          </span>
          <Button
            variant="outline"
            size="icon"
            onClick={handleNextMonth}
            aria-label="Next month"
          >
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>

        <div className="flex items-center gap-2">
          <Button
            variant={viewMode === 'list' ? 'default' : 'outline'}
            size="icon"
            onClick={() => setViewMode('list')}
            aria-label="List view"
          >
            <List className="h-4 w-4" />
          </Button>
          <Button
            variant={viewMode === 'calendar' ? 'default' : 'outline'}
            size="icon"
            onClick={() => setViewMode('calendar')}
            aria-label="Calendar view"
          >
            <Calendar className="h-4 w-4" />
          </Button>
        </div>
      </div>

      {/* View content */}
      {viewMode === 'calendar' ? renderCalendarView() : renderListView()}

      {/* Today's schedule quick action */}
      {schedule && effectiveSchedule?.can_attend && isToday(new Date()) && (
        <ContentCard title="Quick Action" className="mt-6">
          <div className="flex items-center justify-between p-4 bg-primary/5 border border-primary/20 rounded-lg">
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-2">
                <Badge variant="default" className="gap-1">
                  <CheckCircle2 className="h-3 w-3" />
                  Jadwal Hari Ini
                </Badge>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-muted-foreground">
                <div className="flex items-center gap-2">
                  <Clock className="h-4 w-4" />
                  <span>{schedule.default_start_time} - {schedule.default_end_time}</span>
                </div>
              </div>
            </div>
            <Button
              onClick={() => window.location.href = '/shared/verify-location'}
            >
              Check-In
            </Button>
          </div>
        </ContentCard>
      )}
    </PageLayout>
  );
}
