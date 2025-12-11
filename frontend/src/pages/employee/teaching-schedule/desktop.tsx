import { useQuery } from '@tanstack/react-query';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { getMyTeachingSchedule, type TeachingSession } from '@/lib/api/schedules';
import { Clock, BookOpen, Users, Calendar, AlertCircle, Info } from 'lucide-react';

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
  // Handle both "HH:mm:ss" and "YYYY-MM-DD HH:mm:ss" formats
  const timePart = timeString.includes(' ') ? timeString.split(' ')[1] : timeString;
  return timePart.substring(0, 5);
}

function SessionCard({ session, isToday }: { session: TeachingSession; isToday: boolean }) {
  return (
    <div
      className={`p-4 rounded-lg border ${
        isToday ? 'border-primary bg-primary/5' : 'border-border bg-card'
      }`}
    >
      <div className="flex items-start justify-between">
        <div className="flex-1">
          <div className="flex items-center gap-2 mb-1">
            <span className="font-semibold text-lg">
              {formatTime(session.teaching_start_time)} - {formatTime(session.teaching_end_time)}
            </span>
            {session.teaching_duration_hours && (
              <Badge variant="secondary" className="text-xs">
                {session.teaching_duration_hours} jam
              </Badge>
            )}
          </div>
          <p className="text-base font-medium text-foreground">
            {session.subject?.name || 'Mata Pelajaran'}
          </p>
          {session.class_name && (
            <p className="text-sm text-muted-foreground flex items-center gap-1 mt-1">
              <Users className="h-3.5 w-3.5" />
              Kelas {session.class_name}
            </p>
          )}
          {session.room && (
            <p className="text-sm text-muted-foreground mt-0.5">Ruang: {session.room}</p>
          )}
        </div>
      </div>
    </div>
  );
}

function DayScheduleCard({
  day,
  sessions,
  isToday,
}: {
  day: string;
  sessions: TeachingSession[];
  isToday: boolean;
}) {
  const totalHours = sessions.reduce((sum, s) => sum + (s.teaching_duration_hours || 0), 0);

  return (
    <Card className={isToday ? 'ring-2 ring-primary' : ''}>
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <CardTitle className="text-lg flex items-center gap-2">
            <Calendar className="h-5 w-5" />
            {DAY_LABELS[day] || day}
            {isToday && (
              <Badge variant="default" className="ml-2">
                Hari Ini
              </Badge>
            )}
          </CardTitle>
          <Badge variant="outline">
            {sessions.length} sesi ({totalHours} jam)
          </Badge>
        </div>
      </CardHeader>
      <CardContent className="space-y-3">
        {sessions.map((session) => (
          <SessionCard key={session.id} session={session} isToday={isToday} />
        ))}
      </CardContent>
    </Card>
  );
}

function StatCard({
  icon: Icon,
  label,
  value,
  suffix,
}: {
  icon: React.ElementType;
  label: string;
  value: number | string;
  suffix?: string;
}) {
  return (
    <Card>
      <CardContent className="pt-6">
        <div className="flex items-center gap-4">
          <div className="p-3 rounded-full bg-primary/10">
            <Icon className="h-6 w-6 text-primary" />
          </div>
          <div>
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="text-2xl font-bold">
              {value}
              {suffix && <span className="text-base font-normal text-muted-foreground ml-1">{suffix}</span>}
            </p>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

export function DesktopTeachingSchedulePage() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['my-teaching-schedule'],
    queryFn: getMyTeachingSchedule,
  });

  if (isLoading) {
    return (
      <div className="container mx-auto p-6 space-y-6">
        <Skeleton className="h-10 w-64" />
        <div className="grid grid-cols-4 gap-4">
          {[1, 2, 3, 4].map((i) => (
            <Skeleton key={i} className="h-32" />
          ))}
        </div>
        <div className="grid grid-cols-2 gap-6">
          {[1, 2, 3, 4].map((i) => (
            <Skeleton key={i} className="h-64" />
          ))}
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="container mx-auto p-6">
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>
            Gagal memuat jadwal mengajar. Silakan coba lagi.
          </AlertDescription>
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
    <div className="container mx-auto p-6 space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Jadwal Mengajar</h1>
          <p className="text-muted-foreground mt-1">
            {employee?.name} - {employee?.is_guru_honorer ? 'Guru Honor' : 'Guru Tetap'}
          </p>
        </div>
        {employee?.is_guru_honorer && (
          <Badge variant="secondary" className="text-sm px-3 py-1">
            Absensi berdasarkan jadwal mengajar
          </Badge>
        )}
      </div>

      {/* Info for Guru Honorer */}
      {employee?.is_guru_honorer && (
        <Alert>
          <Info className="h-4 w-4" />
          <AlertDescription>
            Sebagai Guru Honor, waktu absensi Anda mengikuti jadwal mengajar. Check-in dihitung
            tepat waktu jika dilakukan sebelum atau tepat pada jam sesi pertama. Jam kerja
            dihitung berdasarkan total jam mengajar, bukan rentang waktu.
          </AlertDescription>
        </Alert>
      )}

      {/* Statistics */}
      {stats && (
        <div className="grid grid-cols-4 gap-4">
          <StatCard
            icon={Calendar}
            label="Total Sesi per Minggu"
            value={stats.total_sessions_per_week}
            suffix="sesi"
          />
          <StatCard
            icon={Clock}
            label="Total Jam per Minggu"
            value={stats.total_hours_per_week}
            suffix="jam"
          />
          <StatCard icon={BookOpen} label="Mata Pelajaran" value={stats.subjects_count} />
          <StatCard icon={Users} label="Kelas" value={stats.classes_count} />
        </div>
      )}

      {/* Today's Schedule Highlight */}
      {today && today.schedules.length > 0 && (
        <Card className="bg-primary/5 border-primary">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Clock className="h-5 w-5" />
              Jadwal Hari Ini ({DAY_LABELS[today.day_of_week]})
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-3 gap-4">
              {today.schedules.map((session) => (
                <SessionCard key={session.id} session={session} isToday={true} />
              ))}
            </div>
            <p className="text-sm text-muted-foreground mt-4">
              Total: {today.total_hours} jam mengajar hari ini
            </p>
          </CardContent>
        </Card>
      )}

      {/* No schedules message */}
      {!hasSchedules && (
        <Alert>
          <Info className="h-4 w-4" />
          <AlertDescription>
            Belum ada jadwal mengajar yang di-assign untuk Anda. Hubungi administrator untuk
            informasi lebih lanjut.
          </AlertDescription>
        </Alert>
      )}

      {/* Weekly Schedule */}
      {hasSchedules && (
        <div>
          <h2 className="text-xl font-semibold mb-4">Jadwal Mingguan</h2>
          <div className="grid grid-cols-2 gap-6">
            {Object.entries(schedules).map(([day, sessions]) => (
              <DayScheduleCard
                key={day}
                day={day}
                sessions={sessions as TeachingSession[]}
                isToday={day === today?.day_of_week}
              />
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
