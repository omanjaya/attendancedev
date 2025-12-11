import { useQuery } from '@tanstack/react-query';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { getMyTeachingSchedule, type TeachingSession } from '@/lib/api/schedules';
import { Clock, BookOpen, Users, Calendar, AlertCircle, Info, ChevronRight } from 'lucide-react';
import { useState } from 'react';

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
      className={`p-3 rounded-lg border ${
        isToday ? 'border-primary bg-primary/5' : 'border-border bg-card'
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
    <div className={`border rounded-lg ${isToday ? 'ring-2 ring-primary' : ''}`}>
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
    <div className="flex flex-col items-center p-3 bg-muted/50 rounded-lg">
      <Icon className="h-5 w-5 text-primary mb-1" />
      <span className="text-lg font-bold">{value}</span>
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
      <div className="p-4 space-y-4">
        <Skeleton className="h-8 w-48" />
        <div className="grid grid-cols-4 gap-2">
          {[1, 2, 3, 4].map((i) => (
            <Skeleton key={i} className="h-20" />
          ))}
        </div>
        {[1, 2, 3].map((i) => (
          <Skeleton key={i} className="h-24" />
        ))}
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-4">
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>Gagal memuat jadwal mengajar.</AlertDescription>
        </Alert>
      </div>
    );
  }

  const schedules = data?.schedules || {};
  const today = data?.today;
  const stats = data?.statistics;
  const employee = data?.employee;

  const hasSchedules = Object.keys(schedules).length > 0;

  return (
    <div className="p-4 pb-24 space-y-4">
      {/* Header */}
      <div>
        <h1 className="text-xl font-bold">Jadwal Mengajar</h1>
        <p className="text-sm text-muted-foreground">
          {employee?.name}
          {employee?.is_guru_honorer && (
            <Badge variant="secondary" className="ml-2 text-xs">
              Guru Honor
            </Badge>
          )}
        </p>
      </div>

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
        <Card className="bg-primary/5 border-primary">
          <CardHeader className="pb-2 pt-3">
            <CardTitle className="text-base flex items-center gap-2">
              <Clock className="h-4 w-4" />
              Hari Ini ({DAY_LABELS[today.day_of_week]})
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 pt-0">
            {today.schedules.map((session) => (
              <SessionItem key={session.id} session={session} isToday={true} />
            ))}
            <p className="text-xs text-muted-foreground text-center">
              Total: {today.total_hours} jam hari ini
            </p>
          </CardContent>
        </Card>
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
        <Alert>
          <Info className="h-4 w-4" />
          <AlertDescription className="text-sm">
            Belum ada jadwal mengajar. Hubungi administrator.
          </AlertDescription>
        </Alert>
      )}

      {/* Weekly Schedule */}
      {hasSchedules && (
        <div className="space-y-2">
          <h2 className="font-semibold">Jadwal Mingguan</h2>
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
  );
}
