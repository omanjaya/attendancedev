import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Calendar, Clock, MapPin, Download, ChevronLeft, ChevronRight, List } from 'lucide-react';
import { PageLayout } from '@/components/shared/PageLayout';
import { ContentCard } from '@/components/shared/ContentCard';
import { useAuthStore } from '@/stores';
import {
  format,
  startOfMonth,
  endOfMonth,
  eachDayOfInterval,
  isSameDay,
  parseISO,
  addMonths,
  subMonths,
  startOfWeek,
  endOfWeek,
  isToday,
} from 'date-fns';
import { id } from 'date-fns/locale';

interface Schedule {
  id: number;
  date: string;
  shiftName: string;
  startTime: string;
  endTime: string;
  location: string;
  notes?: string;
  status: 'upcoming' | 'today' | 'completed';
}

/**
 * Employee Schedule Page
 * Read-only view for employees to see their assigned schedules
 */
export function DesktopEmployeeSchedulePage() {
  const { user } = useAuthStore();
  const [selectedMonth, setSelectedMonth] = useState(new Date());
  const [viewMode, setViewMode] = useState<'calendar' | 'list'>('calendar');

  // Fetch employee's schedule data
  const { data: scheduleData, isLoading } = useQuery({
    queryKey: ['employee', 'schedule', user?.id, format(selectedMonth, 'yyyy-MM')],
    queryFn: async () => {
      // TODO: Replace with actual API call
      return {
        schedules: [
          {
            id: 1,
            date: '2025-12-01',
            shiftName: 'Shift Pagi',
            startTime: '08:00',
            endTime: '17:00',
            location: 'Kantor Pusat',
            notes: null,
            status: 'today' as const,
          },
          {
            id: 2,
            date: '2025-12-02',
            shiftName: 'Shift Pagi',
            startTime: '08:00',
            endTime: '17:00',
            location: 'Kantor Pusat',
            notes: null,
            status: 'upcoming' as const,
          },
          {
            id: 3,
            date: '2025-12-03',
            shiftName: 'Shift Siang',
            startTime: '12:00',
            endTime: '21:00',
            location: 'Kantor Cabang A',
            notes: 'Penugasan khusus',
            status: 'upcoming' as const,
          },
          {
            id: 4,
            date: '2025-11-30',
            shiftName: 'Shift Pagi',
            startTime: '08:00',
            endTime: '17:00',
            location: 'Kantor Pusat',
            notes: null,
            status: 'completed' as const,
          },
          {
            id: 5,
            date: '2025-11-29',
            shiftName: 'Shift Malam',
            startTime: '20:00',
            endTime: '05:00',
            location: 'Kantor Pusat',
            notes: 'Shift malam',
            status: 'completed' as const,
          },
        ] as Schedule[],
      };
    },
  });

  const handleExport = () => {
    // TODO: Implement export functionality
    console.log('Export schedule data');
  };

  const handlePreviousMonth = () => {
    setSelectedMonth(subMonths(selectedMonth, 1));
  };

  const handleNextMonth = () => {
    setSelectedMonth(addMonths(selectedMonth, 1));
  };

  const getShiftColor = (shiftName: string) => {
    const lowerName = shiftName.toLowerCase();
    if (lowerName.includes('pagi')) return 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300';
    if (lowerName.includes('siang')) return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300';
    if (lowerName.includes('malam')) return 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300';
    return 'bg-gray-100 dark:bg-gray-900/30 text-gray-700 dark:text-gray-300';
  };

  const renderCalendarView = () => {
    const monthStart = startOfMonth(selectedMonth);
    const monthEnd = endOfMonth(selectedMonth);
    const calendarStart = startOfWeek(monthStart, { weekStartsOn: 0 }); // Start on Sunday
    const calendarEnd = endOfWeek(monthEnd, { weekStartsOn: 0 });
    const days = eachDayOfInterval({ start: calendarStart, end: calendarEnd });

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
          {days.map((day) => {
            const daySchedules = scheduleData?.schedules.filter((s) =>
              isSameDay(parseISO(s.date), day)
            ) || [];

            const isCurrentMonth = day.getMonth() === selectedMonth.getMonth();
            const isDayToday = isToday(day);

            return (
              <div
                key={day.toString()}
                className={`min-h-[80px] md:min-h-[100px] p-1 md:p-2 rounded-lg border transition-colors ${isDayToday
                  ? 'border-primary bg-primary/5'
                  : isCurrentMonth
                    ? 'border-border bg-card hover:bg-muted/50'
                    : 'border-muted bg-muted/20 opacity-50'
                  }`}
              >
                <div
                  className={`text-xs md:text-sm font-medium mb-1 ${isDayToday
                    ? 'text-primary font-bold'
                    : isCurrentMonth
                      ? 'text-foreground'
                      : 'text-muted-foreground'
                    }`}
                >
                  {format(day, 'd')}
                </div>

                <div className="space-y-1">
                  {daySchedules.map((schedule) => (
                    <div
                      key={schedule.id}
                      className={`text-[10px] md:text-xs p-1 rounded ${getShiftColor(schedule.shiftName)}`}
                      title={`${schedule.shiftName}: ${schedule.startTime} - ${schedule.endTime}`}
                    >
                      <div className="font-medium truncate">{schedule.shiftName}</div>
                      <div className="truncate">{schedule.startTime}</div>
                    </div>
                  ))}
                </div>
              </div>
            );
          })}
        </div>

        {/* Legend */}
        <div className="mt-4 pt-4 border-t grid grid-cols-2 md:grid-cols-3 gap-2 text-xs">
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 rounded bg-blue-100 dark:bg-blue-900/30" />
            <span>Shift Pagi</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 rounded bg-yellow-100 dark:bg-yellow-900/30" />
            <span>Shift Siang</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 rounded bg-purple-100 dark:bg-purple-900/30" />
            <span>Shift Malam</span>
          </div>
        </div>
      </ContentCard>
    );
  };

  const renderListView = () => {
    const upcomingSchedules = scheduleData?.schedules.filter((s) =>
      s.status === 'today' || s.status === 'upcoming'
    ) || [];

    const pastSchedules = scheduleData?.schedules.filter((s) =>
      s.status === 'completed'
    ) || [];

    return (
      <div className="space-y-6">
        {/* Upcoming Schedules */}
        <ContentCard title="Jadwal Mendatang">
          <div className="space-y-3">
            {upcomingSchedules.length > 0 ? (
              upcomingSchedules.map((schedule) => (
                <div
                  key={schedule.id}
                  className={`p-4 rounded-lg border transition-colors ${schedule.status === 'today'
                    ? 'border-primary bg-primary/5'
                    : 'border-border bg-card hover:bg-muted/50'
                    }`}
                >
                  <div className="flex items-start justify-between gap-4 mb-3">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-1">
                        <h3 className="text-sm font-semibold">
                          {format(parseISO(schedule.date), 'EEEE, dd MMMM yyyy', { locale: id })}
                        </h3>
                        {schedule.status === 'today' && (
                          <span className="text-xs px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 font-medium">
                            Hari Ini
                          </span>
                        )}
                      </div>
                      <span className={`inline-block text-xs px-2 py-1 rounded-full font-medium ${getShiftColor(schedule.shiftName)}`}>
                        {schedule.shiftName}
                      </span>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                    <div className="flex items-center gap-2 text-muted-foreground">
                      <Clock className="h-4 w-4" />
                      <span>{schedule.startTime} - {schedule.endTime}</span>
                    </div>
                    <div className="flex items-center gap-2 text-muted-foreground">
                      <MapPin className="h-4 w-4" />
                      <span>{schedule.location}</span>
                    </div>
                  </div>

                  {schedule.notes && (
                    <div className="mt-2 p-2 bg-muted/50 rounded text-xs text-muted-foreground">
                      <strong>Catatan:</strong> {schedule.notes}
                    </div>
                  )}
                </div>
              ))
            ) : (
              <div className="text-center py-12">
                <Calendar className="h-12 w-12 text-muted-foreground mx-auto mb-3 opacity-50" />
                <p className="text-sm text-muted-foreground">Tidak ada jadwal mendatang</p>
              </div>
            )}
          </div>
        </ContentCard>

        {/* Past Schedules */}
        {pastSchedules.length > 0 && (
          <ContentCard title="Riwayat Jadwal">
            <div className="space-y-2">
              {pastSchedules.map((schedule) => (
                <div
                  key={schedule.id}
                  className="p-3 rounded-lg border bg-card hover:bg-muted/50 transition-colors opacity-75"
                >
                  <div className="flex items-center justify-between gap-4">
                    <div className="flex-1">
                      <div className="text-sm font-medium mb-1">
                        {format(parseISO(schedule.date), 'EEEE, dd MMM yyyy', { locale: id })}
                      </div>
                      <div className="flex items-center gap-3 text-xs text-muted-foreground">
                        <span className={`px-2 py-0.5 rounded-full ${getShiftColor(schedule.shiftName)}`}>
                          {schedule.shiftName}
                        </span>
                        <span>{schedule.startTime} - {schedule.endTime}</span>
                        <span>• {schedule.location}</span>
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

  return (
    <PageLayout
      title="Jadwal Saya"
      description="Lihat jadwal kerja yang telah ditetapkan"
      actions={
        <button
          onClick={handleExport}
          className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90 transition-colors"
        >
          <Download className="h-4 w-4" />
          Export
        </button>
      }
    >
      {/* Info card */}
      <ContentCard className="mb-6 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
        <div className="flex items-start gap-3">
          <Calendar className="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5" />
          <div className="flex-1">
            <h3 className="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-1">
              Informasi Jadwal
            </h3>
            <p className="text-xs text-blue-700 dark:text-blue-300">
              Jadwal kerja Anda ditampilkan di bawah ini. Jika ada perubahan atau pertanyaan,
              silakan hubungi supervisor atau bagian HR.
            </p>
          </div>
        </div>
      </ContentCard>

      {/* Month selector and view mode toggle */}
      <div className="flex items-center justify-between gap-4 mb-4">
        <div className="flex items-center gap-2">
          <button
            onClick={handlePreviousMonth}
            className="p-2 rounded-lg border hover:bg-muted transition-colors"
            aria-label="Previous month"
          >
            <ChevronLeft className="h-4 w-4" />
          </button>
          <span className="text-sm font-semibold min-w-[150px] text-center">
            {format(selectedMonth, 'MMMM yyyy', { locale: id })}
          </span>
          <button
            onClick={handleNextMonth}
            className="p-2 rounded-lg border hover:bg-muted transition-colors"
            aria-label="Next month"
          >
            <ChevronRight className="h-4 w-4" />
          </button>
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={() => setViewMode('list')}
            className={`p-2 rounded-lg transition-colors ${viewMode === 'list'
              ? 'bg-primary text-primary-foreground'
              : 'border hover:bg-muted'
              }`}
            aria-label="List view"
          >
            <List className="h-4 w-4" />
          </button>
          <button
            onClick={() => setViewMode('calendar')}
            className={`p-2 rounded-lg transition-colors ${viewMode === 'calendar'
              ? 'bg-primary text-primary-foreground'
              : 'border hover:bg-muted'
              }`}
            aria-label="Calendar view"
          >
            <Calendar className="h-4 w-4" />
          </button>
        </div>
      </div>

      {/* View content */}
      {isLoading ? (
        <ContentCard>
          <div className="flex items-center justify-center py-12">
            <div className="text-center">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-3" />
              <p className="text-sm text-muted-foreground">Memuat jadwal...</p>
            </div>
          </div>
        </ContentCard>
      ) : viewMode === 'calendar' ? (
        renderCalendarView()
      ) : (
        renderListView()
      )}

      {/* Today's schedule quick view */}
      {scheduleData?.schedules.find((s) => s.status === 'today') && (
        <ContentCard title="Jadwal Hari Ini" className="mt-6">
          {scheduleData.schedules
            .filter((s) => s.status === 'today')
            .map((schedule) => (
              <div
                key={schedule.id}
                className="flex items-center justify-between p-4 bg-primary/5 border border-primary/20 rounded-lg"
              >
                <div className="flex-1">
                  <div className="flex items-center gap-2 mb-2">
                    <span className={`text-sm px-3 py-1 rounded-full font-medium ${getShiftColor(schedule.shiftName)}`}>
                      {schedule.shiftName}
                    </span>
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-muted-foreground">
                    <div className="flex items-center gap-2">
                      <Clock className="h-4 w-4" />
                      <span>{schedule.startTime} - {schedule.endTime}</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <MapPin className="h-4 w-4" />
                      <span>{schedule.location}</span>
                    </div>
                  </div>
                </div>
                <button
                  onClick={() => window.location.href = '/shared/verify-face'}
                  className="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90 transition-colors"
                >
                  Check-In
                </button>
              </div>
            ))}
        </ContentCard>
      )}
    </PageLayout>
  );
}
