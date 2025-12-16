import { useQuery } from '@tanstack/react-query';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { getMyTeachingSchedule, type TeachingSession } from '@/lib/api/schedules';
import { Clock, BookOpen, Users, Calendar, AlertCircle, Info, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import { MobilePageHeader, MobileEmptyState } from '@/components/mobile';

const DAY_LABELS: Record<string, string> = {
  monday: 'Senin',
  tuesday: 'Selasa',
  wednesday: 'Rabu',
  thursday: 'Kamis',
  friday: 'Jumat',
  saturday: 'Sabtu',
  sunday: 'Minggu',
};

function formatTime(timeString: string): string {
  if (!timeString) return '-';
  const timePart = timeString.includes(' ') ? timeString.split(' ')[1] : timeString;
  return timePart.substring(0, 5);
}

function SessionItem({ session, isToday }: { session: TeachingSession; isToday: boolean }) {
  return (
    <div
      className={`p-3 rounded-xl ${
        isToday ? 'bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800' : 'bg-muted/30 border border-border/50'
      }`}
    >
      <div className="flex items-center justify-between mb-1">
        <span className="font-semibold">
          {formatTime(session.teaching_start_time)} - {formatTime(session.teaching_end_time)}
        </span>
        {session.teaching_duration_hours && (
          <Badge variant="secondary" className="text-xs">
            {session.teaching_duration_hours} jam
          </Badge>
        )}
      </div>
      <p className="font-medium">{session.subject?.name || 'Mata Pelajaran'}</p>
      <div className="flex items-center gap-3 text-sm text-muted-foreground mt-1">
        {session.class_name && (
          <span className="flex items-center gap-1">
            <Users className="h-3 w-3" />
            {session.class_name}
          </span>
        )}
        {session.room && <span>Ruang: {session.room}</span>}
      </div>
    </div>
  );
}

function DayAccordion({
  day,
  sessions,
  isToday,
  defaultOpen,
}: {
  day: string;
  sessions: TeachingSession[];
  isToday: boolean;
  defaultOpen: boolean;
}) {
  const [isOpen, setIsOpen] = useState(defaultOpen);
  const totalHours = sessions.reduce((sum, s) => sum + (s.teaching_duration_hours || 0), 0);

  return (
    <div className={`bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 ${isToday ? 'ring-2 ring-indigo-500 dark:ring-indigo-400' : ''}`}>
      <button
        className="w-full p-4 flex items-center justify-between text-left"
        onClick={() => setIsOpen(!isOpen)}
      >
        <div className="flex items-center gap-2">
          <Calendar className="h-4 w-4 text-muted-foreground" />
          <span className="font-semibold">{DAY_LABELS[day] || day}</span>
          {isToday && (
            <Badge variant="default" className="text-xs">
              Hari Ini
            </Badge>
          )}
        </div>
        <div className="flex items-center gap-2">
          <Badge variant="outline" className="text-xs">
            {sessions.length} sesi
          </Badge>
          <ChevronRight className={`h-4 w-4 transition-transform ${isOpen ? 'rotate-90' : ''}`} />
        </div>
      </button>

      {isOpen && (
        <div className="px-4 pb-4 space-y-2">
          {sessions.map((session) => (
            <SessionItem key={session.id} session={session} isToday={isToday} />
          ))}
          <p className="text-xs text-muted-foreground text-center pt-2">
            Total: {totalHours} jam
          </p>
        </div>
      )}
    </div>
  );
}

function StatBadge({
  icon: Icon,
  value,
  label,
}: {
  icon: React.ElementType;
  value: number | string;
  label: string;
}) {
  return (
    <div className="flex flex-col items-center p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
      <Icon className="h-5 w-5 text-indigo-600 dark:text-indigo-400 mb-1" />
      <span className="text-lg font-bold text-foreground">{value}</span>
      <span className="text-xs text-muted-foreground">{label}</span>
    </div>
  );
}

export function MobileTeachingSchedulePage() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['my-teaching-schedule'],
    queryFn: getMyTeachingSchedule,
  });

  if (isLoading) {
    return (
      <div className="min-h-screen bg-background pb-24">
        <MobilePageHeader title="Jadwal Mengajar" gradient="indigo" backTo="/employee/dashboard" />
        <div className="px-4 space-y-4">
          <Skeleton className="h-8 w-48" />
          <div className="grid grid-cols-4 gap-2">
            {[1, 2, 3, 4].map((i) => (
              <Skeleton key={i} className="h-20 rounded-xl" />
            ))}
          </div>
          {[1, 2, 3].map((i) => (
            <Skeleton key={i} className="h-24 rounded-2xl" />
          ))}
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-background pb-24">
        <MobilePageHeader title="Jadwal Mengajar" gradient="indigo" backTo="/employee/dashboard" />
        <div className="px-4">
          <Alert variant="destructive">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>Gagal memuat jadwal mengajar.</AlertDescription>
          </Alert>
        </div>
      </div>
    );
  }

  const schedules = data?.schedules || {};
  const today = data?.today;
  const stats = data?.statistics;
  const employee = data?.employee;

  const hasSchedules = Object.keys(schedules).length > 0;

  return (
    <div className="min-h-screen bg-background pb-24">
      <MobilePageHeader
        title="Jadwal Mengajar"
        gradient="indigo"
        backTo="/employee/dashboard"
        subtitle={
          employee?.is_guru_honorer && (
            <Badge className="bg-white/20 text-white border-0 text-xs">Guru Honor</Badge>
          )
        }
      />

      <div className="px-4 space-y-4">

      {/* Info for Guru Honorer */}
      {employee?.is_guru_honorer && (
        <Alert className="py-2">
          <Info className="h-4 w-4" />
          <AlertDescription className="text-xs">
            Absensi mengikuti jadwal mengajar. Jam kerja dihitung dari total jam mengajar.
          </AlertDescription>
        </Alert>
      )}

      {/* Statistics */}
      {stats && (
        <div className="grid grid-cols-4 gap-2">
          <StatBadge icon={Calendar} value={stats.total_sessions_per_week} label="Sesi/minggu" />
          <StatBadge icon={Clock} value={stats.total_hours_per_week} label="Jam/minggu" />
          <StatBadge icon={BookOpen} value={stats.subjects_count} label="Mapel" />
          <StatBadge icon={Users} value={stats.classes_count} label="Kelas" />
        </div>
      )}

      {/* Today's Schedule */}
      {today && today.schedules.length > 0 && (
        <div className="bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl p-4 border border-indigo-200 dark:border-indigo-800">
          <h3 className="text-sm font-bold text-foreground flex items-center gap-2 mb-3">
            <Clock className="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
            Hari Ini ({DAY_LABELS[today.day_of_week]})
          </h3>
          <div className="space-y-2">
            {today.schedules.map((session) => (
              <SessionItem key={session.id} session={session} isToday={true} />
            ))}
            <p className="text-xs text-muted-foreground text-center pt-2">
              Total: {today.total_hours} jam hari ini
            </p>
          </div>
        </div>
      )}

      {/* No schedule today */}
      {today && today.schedules.length === 0 && hasSchedules && (
        <Alert>
          <Info className="h-4 w-4" />
          <AlertDescription className="text-sm">
            Tidak ada jadwal mengajar hari ini ({DAY_LABELS[today.day_of_week]}).
          </AlertDescription>
        </Alert>
      )}

      {/* No schedules at all */}
      {!hasSchedules && (
        <MobileEmptyState
          icon={BookOpen}
          title="Belum Ada Jadwal"
          description="Belum ada jadwal mengajar. Hubungi administrator."
        />
      )}

      {/* Weekly Schedule */}
      {hasSchedules && (
        <div className="space-y-3">
          <h2 className="text-sm font-bold text-foreground">Jadwal Mingguan</h2>
          {Object.entries(schedules).map(([day, sessions]) => (
            <DayAccordion
              key={day}
              day={day}
              sessions={sessions as TeachingSession[]}
              isToday={day === today?.day_of_week}
              defaultOpen={day === today?.day_of_week}
            />
          ))}
        </div>
      )}
      </div>
    </div>
  );
}
