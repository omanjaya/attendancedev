import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import {
  Clock,
  BookOpen,
  Users,
  MapPin,
  Calendar,
  CheckCircle2,
  AlertCircle,
  Info,
  GraduationCap,
} from 'lucide-react';
import {
  Drawer,
  DrawerContent,
  DrawerDescription,
  DrawerHeader,
  DrawerTitle,
} from '@/components/ui/drawer';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Separator } from '@/components/ui/separator';
import { useMediaQuery } from '@/hooks/use-media-query';
import type { TeachingSession } from '@/lib/api/schedules';

interface ScheduleDayDetailProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  date: Date | null;
  isWorkingDay: boolean;
  workingHours?: {
    start: string;
    end: string;
  };
  teachingSessions: TeachingSession[];
  isTeacher: boolean;
  isGuruHonorer: boolean;
  canCheckIn?: boolean;
  onCheckIn?: () => void;
}

function formatTime(timeString: string): string {
  if (!timeString) return '-';
  const timePart = timeString.includes(' ') ? timeString.split(' ')[1] : timeString;
  return timePart.substring(0, 5);
}

function TeachingSessionCard({ session }: { session: TeachingSession }) {
  return (
    <div className="p-3 rounded-lg border bg-card hover:bg-muted/50 transition-colors">
      <div className="flex items-start justify-between gap-3">
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 mb-1">
            <span className="font-semibold text-sm">
              {formatTime(session.teaching_start_time)} - {formatTime(session.teaching_end_time)}
            </span>
            {session.teaching_duration_hours && (
              <Badge variant="secondary" className="text-[10px] px-1.5 py-0">
                {session.teaching_duration_hours} jp
              </Badge>
            )}
          </div>
          <p className="font-medium text-foreground truncate">
            {session.subject?.name || 'Mata Pelajaran'}
          </p>
          <div className="flex items-center gap-3 mt-1 text-xs text-muted-foreground">
            {session.class_name && (
              <span className="flex items-center gap-1">
                <Users className="h-3 w-3" />
                {session.class_name}
              </span>
            )}
            {session.room && (
              <span className="flex items-center gap-1">
                <MapPin className="h-3 w-3" />
                {session.room}
              </span>
            )}
          </div>
        </div>
        <div className="p-2 rounded-lg bg-primary/10">
          <BookOpen className="h-4 w-4 text-primary" />
        </div>
      </div>
    </div>
  );
}

function DrawerContentInner({
  date,
  isWorkingDay,
  workingHours,
  teachingSessions,
  isTeacher,
  isGuruHonorer,
  canCheckIn,
  onCheckIn,
}: Omit<ScheduleDayDetailProps, 'open' | 'onOpenChange'>) {
  if (!date) return null;

  const totalTeachingHours = teachingSessions.reduce(
    (sum, s) => sum + (s.teaching_duration_hours || 0),
    0
  );

  const isToday = format(date, 'yyyy-MM-dd') === format(new Date(), 'yyyy-MM-dd');
  const isPast = date < new Date() && !isToday;

  return (
    <div className="space-y-4">
      {/* Date Header */}
      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-lg font-semibold">
            {format(date, 'EEEE', { locale: id })}
          </h3>
          <p className="text-sm text-muted-foreground">
            {format(date, 'd MMMM yyyy', { locale: id })}
          </p>
        </div>
        <div className="flex items-center gap-2">
          {isToday && (
            <Badge variant="default" className="bg-primary">
              Hari Ini
            </Badge>
          )}
          {isWorkingDay ? (
            <Badge variant="outline" className="border-green-500 text-green-600">
              <CheckCircle2 className="h-3 w-3 mr-1" />
              Hari Kerja
            </Badge>
          ) : (
            <Badge variant="outline" className="border-muted-foreground text-muted-foreground">
              Libur
            </Badge>
          )}
        </div>
      </div>

      <Separator />

      {/* Working Hours - For non-honorer or staff */}
      {isWorkingDay && workingHours && !isGuruHonorer && (
        <div className="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
          <div className="flex items-center gap-3">
            <div className="p-2 rounded-lg bg-blue-100 dark:bg-blue-800">
              <Clock className="h-5 w-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p className="text-sm font-medium text-blue-900 dark:text-blue-100">
                Jam Kerja
              </p>
              <p className="text-lg font-bold text-blue-700 dark:text-blue-300">
                {workingHours.start} - {workingHours.end}
              </p>
            </div>
          </div>
        </div>
      )}

      {/* Teaching Sessions - For teachers */}
      {isTeacher && teachingSessions.length > 0 && (
        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <h4 className="font-semibold flex items-center gap-2">
              <GraduationCap className="h-4 w-4" />
              Jadwal Mengajar
            </h4>
            <Badge variant="secondary">
              {teachingSessions.length} sesi ({totalTeachingHours} jp)
            </Badge>
          </div>

          <div className="space-y-2 max-h-[300px] overflow-y-auto pr-1">
            {teachingSessions
              .sort((a, b) => a.teaching_start_time.localeCompare(b.teaching_start_time))
              .map((session) => (
                <TeachingSessionCard key={session.id} session={session} />
              ))}
          </div>
        </div>
      )}

      {/* No teaching sessions for teacher */}
      {isTeacher && teachingSessions.length === 0 && isWorkingDay && (
        <div className="p-4 rounded-lg bg-muted/50 text-center">
          <Calendar className="h-8 w-8 mx-auto mb-2 text-muted-foreground" />
          <p className="text-sm text-muted-foreground">
            Tidak ada jadwal mengajar pada hari ini
          </p>
        </div>
      )}

      {/* Guru Honorer Info */}
      {isGuruHonorer && teachingSessions.length > 0 && (
        <Alert>
          <Info className="h-4 w-4" />
          <AlertDescription className="text-xs">
            Sebagai <strong>Guru Honor</strong>, absensi Anda fleksibel mengikuti jadwal mengajar.
            Check-in dihitung tepat waktu jika sebelum sesi pertama ({formatTime(teachingSessions[0]?.teaching_start_time)}).
          </AlertDescription>
        </Alert>
      )}

      {/* Not a working day */}
      {!isWorkingDay && (
        <div className="p-6 rounded-lg bg-muted/30 text-center">
          <AlertCircle className="h-10 w-10 mx-auto mb-3 text-muted-foreground" />
          <p className="font-medium text-muted-foreground">Hari Libur</p>
          <p className="text-sm text-muted-foreground mt-1">
            Tidak ada jadwal kerja pada hari ini
          </p>
        </div>
      )}

      {/* Quick Action - Check In */}
      {isToday && isWorkingDay && canCheckIn && onCheckIn && (
        <>
          <Separator />
          <Button onClick={onCheckIn} className="w-full" size="lg">
            <CheckCircle2 className="h-4 w-4 mr-2" />
            Check-In Sekarang
          </Button>
        </>
      )}

      {/* Past day info */}
      {isPast && isWorkingDay && (
        <p className="text-xs text-center text-muted-foreground">
          Hari ini sudah berlalu
        </p>
      )}
    </div>
  );
}

export function ScheduleDayDetailDrawer(props: ScheduleDayDetailProps) {
  const { open, onOpenChange, date } = props;
  const isDesktop = useMediaQuery('(min-width: 768px)');

  if (!date) return null;

  // Use Dialog for desktop, Drawer for mobile
  if (isDesktop) {
    return (
      <Dialog open={open} onOpenChange={onOpenChange}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <Calendar className="h-5 w-5" />
              Detail Jadwal
            </DialogTitle>
            <DialogDescription>
              Informasi jadwal untuk tanggal yang dipilih
            </DialogDescription>
          </DialogHeader>
          <DrawerContentInner {...props} />
        </DialogContent>
      </Dialog>
    );
  }

  return (
    <Drawer open={open} onOpenChange={onOpenChange}>
      <DrawerContent>
        <DrawerHeader>
          <DrawerTitle className="flex items-center gap-2">
            <Calendar className="h-5 w-5" />
            Detail Jadwal
          </DrawerTitle>
          <DrawerDescription>
            Informasi jadwal untuk tanggal yang dipilih
          </DrawerDescription>
        </DrawerHeader>
        <div className="px-4 pb-6">
          <DrawerContentInner {...props} />
        </div>
      </DrawerContent>
    </Drawer>
  );
}
