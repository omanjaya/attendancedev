import { useState, useEffect } from 'react';
import { useNavigate } from '@tanstack/react-router';
import {
  LogIn,
  LogOut,
  Edit,
  CalendarOff,
  Briefcase,
  Calendar,
  ChevronRight,
  Clock,
  MapPin,
  ScanFace,
  AlertCircle,
  AlertTriangle,
  Loader2,
} from 'lucide-react';
import { Card } from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import { MobilePageHeader, MobileStatusBadge } from '@/components/mobile';
import { useAuthStore } from '@/stores';
import { useQuery } from '@tanstack/react-query';
import { getTodayAttendance, validateAttendanceTime } from '@/lib/api/attendance';
import { getEmployeeDashboardData } from '@/lib/api/employees';

export function MobileEmployeeAttendancePage() {
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const [currentTime, setCurrentTime] = useState('');
  const [currentDate, setCurrentDate] = useState('');

  // State for re-attendance confirmation
  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [pendingAction, setPendingAction] = useState<'check_in' | 'check_out' | null>(null);

  // State for no schedule modal
  const [showNoScheduleModal, setShowNoScheduleModal] = useState(false);

  // State for time validation
  const [isValidating, setIsValidating] = useState(false);
  const [showTimeErrorModal, setShowTimeErrorModal] = useState(false);
  const [timeErrorMessage, setTimeErrorMessage] = useState('');

  // Get today's date for cache key (ensures fresh data each day)
  const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD format

  // Fetch today's attendance
  const { data: todayAttendance } = useQuery({
    queryKey: ['attendance-today', user?.id, today],
    queryFn: getTodayAttendance,
    enabled: !!user?.id,
    refetchInterval: 30000, // Refetch every 30 seconds
    staleTime: 0, // Always refetch on mount to ensure fresh data
  });

  // Fetch dashboard stats for schedule info
  const { data: dashboardStats } = useQuery({
    queryKey: ['employee', 'dashboard-stats', user?.id, today],
    queryFn: getEmployeeDashboardData,
    enabled: !!user?.id,
    staleTime: 0, // Always refetch on mount
  });

  const canAttend = dashboardStats?.schedule.today.can_attend ?? true;
  const scheduleType = dashboardStats?.schedule.today.schedule_type;

  useEffect(() => {
    const updateTime = () => {
      const now = new Date();
      setCurrentTime(
        now.toLocaleTimeString('id-ID', {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit',
        })
      );
      setCurrentDate(
        now.toLocaleDateString('id-ID', {
          weekday: 'long',
          day: '2-digit',
          month: 'short',
          year: 'numeric',
        })
      );
    };

    updateTime();
    const interval = setInterval(updateTime, 1000);
    return () => clearInterval(interval);
  }, []);

  const handleActionClick = async (type: 'check_in' | 'check_out') => {
    // Show modal if no schedule instead of blocking
    if (!canAttend) {
      setShowNoScheduleModal(true);
      return;
    }

    // Validate time with server before proceeding
    setIsValidating(true);
    try {
      const validation = await validateAttendanceTime(type);

      if (!validation.allowed) {
        setTimeErrorMessage(validation.message);
        setShowTimeErrorModal(true);
        setIsValidating(false);
        return;
      }
    } catch (error) {
      console.error('Time validation error:', error);
      // If validation fails, show generic error
      setTimeErrorMessage('Gagal memvalidasi waktu absensi. Silakan coba lagi.');
      setShowTimeErrorModal(true);
      setIsValidating(false);
      return;
    }
    setIsValidating(false);

    // Check if already attended
    const alreadyAttended = type === 'check_in'
      ? (todayAttendance?.has_checked_in || !!todayAttendance?.attendance?.check_in_time || !!todayAttendance?.attendance?.check_in)
      : (todayAttendance?.has_checked_out || !!todayAttendance?.attendance?.check_out_time || !!todayAttendance?.attendance?.check_out);

    if (alreadyAttended) {
      setPendingAction(type);
      setShowConfirmModal(true);
    } else {
      // Proceed directly to new unified verification page
      navigate({ to: '/shared/verify-attendance', search: { type: type === 'check_in' ? 'check-in' : 'check-out' } });
    }
  };

  const handleConfirmReattend = () => {
    if (pendingAction) {
      navigate({ to: '/shared/verify-attendance', search: { type: pendingAction === 'check_in' ? 'check-in' : 'check-out', overwrite: true } });
    }
    setShowConfirmModal(false);
  };

  const handleCancelReattend = () => {
    setShowConfirmModal(false);
    setPendingAction(null);
  };

  // Format time from ISO string or "HH:MM:SS" to "HH:MM:SS"
  const formatTime = (time: string | null | undefined) => {
    if (!time) return '--:--:--';
    try {
      // If it's already in HH:MM:SS format, return as-is
      if (/^\d{2}:\d{2}(:\d{2})?$/.test(time)) {
        return time.length === 5 ? `${time}:00` : time;
      }
      // Parse ISO date string
      const date = new Date(time);
      if (isNaN(date.getTime())) {
        // If invalid, try to extract time portion
        const match = time.match(/(\d{2}):(\d{2}):?(\d{2})?/);
        if (match) {
          return `${match[1]}:${match[2]}:${match[3] || '00'}`;
        }
        return '--:--:--';
      }
      return date.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
      }).replace(/\./g, ':');
    } catch (_e) {
      return '--:--:--';
    }
  };


  // Get user initials for avatar
  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  // Check if user needs to check out
  const needsCheckOut = todayAttendance?.has_checked_in && !todayAttendance?.has_checked_out;

  return (
    <div className="min-h-screen bg-background pb-24">
      <MobilePageHeader
        title="Absensi"
        gradient="emerald"
        subtitle={
          <div className="flex gap-2 mt-2">
            <div className="flex items-center gap-1 text-white/80 text-xs">
              <Calendar className="h-3 w-3" />
              <span>{currentDate}</span>
            </div>
            <div className="flex items-center gap-1 text-white/80 text-xs">
              <Clock className="h-3 w-3" />
              <span className="font-mono">{currentTime}</span>
            </div>
          </div>
        }
        rightAction={
          <div className="h-8 w-8 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-sm shadow-lg border-2 border-white/20">
            {user?.name ? getInitials(user.name) : 'U'}
          </div>
        }
      />

      <div className="px-4 space-y-4">
        {/* Attendance Status Cards */}
        <div className="grid grid-cols-2 gap-3">
          {/* Check In Status */}
          <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
            <div className="flex flex-col items-center text-center">
              <div className={`h-12 w-12 rounded-full ${todayAttendance?.has_checked_in ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-muted/30'} flex items-center justify-center mb-2`}>
                <LogIn className={`h-6 w-6 ${todayAttendance?.has_checked_in ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'}`} />
              </div>
              <p className="text-xs font-medium text-muted-foreground mb-1">Datang</p>
              <p className="text-lg font-bold font-mono text-foreground">
                {formatTime(todayAttendance?.attendance?.check_in_time || null)}
              </p>
              {todayAttendance?.has_checked_in && (
                <MobileStatusBadge status="present" size="sm" className="mt-2" />
              )}
            </div>
          </div>

          {/* Check Out Status */}
          <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
            <div className="flex flex-col items-center text-center">
              <div className={`h-12 w-12 rounded-full ${todayAttendance?.has_checked_out ? 'bg-rose-100 dark:bg-rose-900/30' : 'bg-muted/30'} flex items-center justify-center mb-2`}>
                <LogOut className={`h-6 w-6 ${todayAttendance?.has_checked_out ? 'text-rose-600 dark:text-rose-400' : 'text-muted-foreground'}`} />
              </div>
              <p className="text-xs font-medium text-muted-foreground mb-1">Pulang</p>
              <p className="text-lg font-bold font-mono text-foreground">
                {formatTime(todayAttendance?.attendance?.check_out_time || null)}
              </p>
              {todayAttendance?.has_checked_out && (
                <MobileStatusBadge status="completed" size="sm" className="mt-2" />
              )}
            </div>
          </div>
        </div>

        {/* Alert if needs check out */}
        {needsCheckOut && (
          <Alert className="border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 rounded-xl">
            <AlertCircle className="h-4 w-4 text-amber-600 dark:text-amber-400" />
            <AlertDescription className="text-amber-800 dark:text-amber-200 text-sm">
              Anda belum melakukan absensi pulang
            </AlertDescription>
          </Alert>
        )}

        {/* Action Buttons Container */}
        <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4 space-y-3">
          <h3 className="text-sm font-bold text-foreground">Aksi Absensi</h3>

          {/* Check In/Out Buttons */}
          <div className="space-y-3">
            {/* Datang (Check In) */}
            <button
              onClick={() => handleActionClick('check_in')}
              disabled={isValidating}
              className="w-full bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-800 rounded-xl p-4 shadow-lg transition-all active:scale-[0.98] disabled:opacity-70"
            >
              <div className="flex items-center gap-3">
                <div className="bg-white/20 backdrop-blur-sm rounded-lg p-2.5 border border-white/20">
                  {isValidating ? (
                    <Loader2 className="h-6 w-6 text-white animate-spin" />
                  ) : (
                    <LogIn className="h-6 w-6 text-white drop-shadow-sm" />
                  )}
                </div>
                <div className="flex-1 text-left">
                  <h3 className="text-base font-semibold text-white drop-shadow-sm">Datang</h3>
                  <p className="text-xs text-white/90 line-clamp-1">
                    Absensi wajib saat datang di hari kerja
                  </p>
                </div>
                <ChevronRight className="h-5 w-5 text-white/90" />
              </div>
            </button>

            {/* Pulang (Check Out) */}
            <button
              onClick={() => handleActionClick('check_out')}
              disabled={isValidating}
              className="w-full bg-rose-600 hover:bg-rose-700 dark:bg-rose-700 dark:hover:bg-rose-800 rounded-xl p-4 shadow-lg transition-all active:scale-[0.98] disabled:opacity-70"
            >
              <div className="flex items-center gap-3">
                <div className="bg-white/20 backdrop-blur-sm rounded-lg p-2.5 border border-white/20">
                  {isValidating ? (
                    <Loader2 className="h-6 w-6 text-white animate-spin" />
                  ) : (
                    <LogOut className="h-6 w-6 text-white drop-shadow-sm" />
                  )}
                </div>
                <div className="flex-1 text-left">
                  <h3 className="text-base font-semibold text-white drop-shadow-sm">Pulang</h3>
                  <p className="text-xs text-white/90 line-clamp-1">
                    Absensi wajib saat pulang di hari kerja
                  </p>
                </div>
                <ChevronRight className="h-5 w-5 text-white/90" />
              </div>
            </button>
          </div>
        </div>

        {/* Other Actions */}
        <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4 space-y-3">
          <h3 className="text-sm font-bold text-foreground">Pengajuan</h3>

          <div className="grid grid-cols-2 gap-3">
            {/* Ubah Absen */}
            <Card className="p-3 hover:shadow-lg transition-all cursor-pointer active:scale-[0.98] border-border/50 rounded-xl">
              <div className="space-y-2">
                <div className="bg-amber-50 dark:bg-amber-950/50 rounded-lg p-2 w-fit border border-amber-100 dark:border-amber-900">
                  <Edit className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                  <h3 className="font-semibold text-foreground text-sm">Ubah Absen</h3>
                  <p className="text-xs text-muted-foreground mt-0.5">
                    Ajukan perubahan absen
                  </p>
                </div>
              </div>
            </Card>

            {/* Cuti */}
            <Card
              onClick={() => navigate({ to: '/employee/leave' })}
              className="p-3 hover:shadow-lg transition-all cursor-pointer active:scale-[0.98] border-border/50 rounded-xl"
            >
              <div className="space-y-2">
                <div className="bg-teal-50 dark:bg-teal-950/50 rounded-lg p-2 w-fit border border-teal-100 dark:border-teal-900">
                  <CalendarOff className="h-5 w-5 text-teal-600 dark:text-teal-400" />
                </div>
                <div>
                  <h3 className="font-semibold text-foreground text-sm">Cuti</h3>
                  <p className="text-xs text-muted-foreground mt-0.5">
                    Ajukan cuti
                  </p>
                </div>
              </div>
            </Card>

            {/* Dinas/Diklat */}
            <Card
              onClick={() => navigate({ to: '/employee/leave' })}
              className="p-3 hover:shadow-lg transition-all cursor-pointer active:scale-[0.98] border-border/50 rounded-xl"
            >
              <div className="space-y-2">
                <div className="bg-blue-50 dark:bg-blue-950/50 rounded-lg p-2 w-fit border border-blue-100 dark:border-blue-900">
                  <Briefcase className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                  <h3 className="font-semibold text-foreground text-sm">Dinas/Diklat</h3>
                  <p className="text-xs text-muted-foreground mt-0.5">
                    Ajukan Dinas/Diklat
                  </p>
                </div>
              </div>
            </Card>

            {/* Jadwal Saya */}
            <Card
              onClick={() => navigate({ to: '/employee/schedule' })}
              className="p-3 hover:shadow-lg transition-all cursor-pointer active:scale-[0.98] border-border/50 rounded-xl"
            >
              <div className="space-y-2">
                <div className="bg-indigo-50 dark:bg-indigo-950/50 rounded-lg p-2 w-fit border border-indigo-100 dark:border-indigo-900">
                  <Calendar className="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div>
                  <h3 className="font-semibold text-foreground text-sm">Jadwal Saya</h3>
                  <p className="text-xs text-muted-foreground mt-0.5">
                    Lihat jadwal
                  </p>
                </div>
              </div>
            </Card>
          </div>
        </div>

        {/* Status Footer */}
        <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
          <div className="flex items-center justify-center gap-4">
            <div className="flex items-center gap-2 text-xs text-muted-foreground">
              <MapPin className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
              <span>GPS Enabled</span>
            </div>
            <div className="h-1 w-1 rounded-full bg-border" />
            <div className="flex items-center gap-2 text-xs text-muted-foreground">
              <ScanFace className="h-4 w-4 text-primary" />
              <span>Face ID Ready</span>
            </div>
          </div>
        </div>
      </div>

      {/* Re-Attendance Confirmation Dialog */}
      <Dialog open={showConfirmModal} onOpenChange={handleCancelReattend}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <AlertTriangle className="h-5 w-5 text-warning" />
              Konfirmasi Absen Ulang
            </DialogTitle>
            <DialogDescription>
              Anda sudah melakukan absensi sebelumnya
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4 py-4">
            <Alert className="border-warning/50 bg-warning/10">
              <AlertTriangle className="h-4 w-4 text-warning" />
              <AlertTitle className="text-warning">Sudah Absen</AlertTitle>
              <AlertDescription className="space-y-2">
                <p className="font-medium">
                  Anda sudah absen {pendingAction === 'check_in' ? 'datang' : 'pulang'} pada:
                </p>
                <p className="text-lg font-bold">
                  {formatTime(pendingAction === 'check_in'
                    ? (todayAttendance?.attendance?.check_in_time || todayAttendance?.attendance?.check_in || null)
                    : (todayAttendance?.attendance?.check_out_time || todayAttendance?.attendance?.check_out || null))}
                </p>
                <p className="text-sm text-muted-foreground mt-2">
                  Apakah Anda ingin absen ulang? Data absensi lama akan diganti dengan yang baru.
                </p>
              </AlertDescription>
            </Alert>

            <div className="flex gap-2">
              <Button
                onClick={handleConfirmReattend}
                className="flex-1"
                variant="default"
              >
                Lanjut Absen
              </Button>
              <Button
                variant="outline"
                onClick={handleCancelReattend}
                className="flex-1"
              >
                Batal
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      {/* No Schedule Modal */}
      <Dialog open={showNoScheduleModal} onOpenChange={setShowNoScheduleModal}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <Calendar className="h-5 w-5 text-muted-foreground" />
              Tidak Dapat Absen
            </DialogTitle>
            <DialogDescription>
              {scheduleType === 'holiday' ? 'Hari ini adalah hari libur' : 'Anda belum memiliki jadwal'}
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4 py-4">
            <Alert className="border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-950/30">
              <AlertCircle className="h-4 w-4 text-orange-600 dark:text-orange-400" />
              <AlertTitle className="text-orange-800 dark:text-orange-200">
                {scheduleType === 'holiday' ? 'Hari Libur' : 'Jadwal Tidak Ditemukan'}
              </AlertTitle>
              <AlertDescription className="text-orange-700 dark:text-orange-300 space-y-2">
                {scheduleType === 'holiday' ? (
                  <p>
                    Hari ini adalah hari libur. Anda tidak perlu melakukan absensi.
                  </p>
                ) : (
                  <>
                    <p>
                      Anda belum memiliki jadwal kerja untuk hari ini. Silakan hubungi administrator untuk melakukan assign jadwal.
                    </p>
                    <p className="text-sm mt-2">
                      Buka menu <strong>Jadwal Saya</strong> untuk melihat jadwal yang sudah di-assign kepada Anda.
                    </p>
                  </>
                )}
              </AlertDescription>
            </Alert>

            <div className="flex gap-2">
              <Button
                variant="outline"
                onClick={() => setShowNoScheduleModal(false)}
                className="flex-1"
              >
                Tutup
              </Button>
              {scheduleType !== 'holiday' && (
                <Button
                  onClick={() => {
                    setShowNoScheduleModal(false);
                    navigate({ to: '/employee/schedule' });
                  }}
                  className="flex-1"
                >
                  Lihat Jadwal
                </Button>
              )}
            </div>
          </div>
        </DialogContent>
      </Dialog>

      {/* Time Validation Error Modal */}
      <Dialog open={showTimeErrorModal} onOpenChange={setShowTimeErrorModal}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <Clock className="h-5 w-5 text-orange-500" />
              Tidak Dapat Absen
            </DialogTitle>
            <DialogDescription>
              Waktu absensi di luar batas yang ditentukan
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4 py-4">
            <Alert className="border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-950/30">
              <Clock className="h-4 w-4 text-orange-600 dark:text-orange-400" />
              <AlertTitle className="text-orange-800 dark:text-orange-200">
                Batas Waktu Absensi
              </AlertTitle>
              <AlertDescription className="text-orange-700 dark:text-orange-300">
                {timeErrorMessage}
              </AlertDescription>
            </Alert>

            <Button
              variant="outline"
              onClick={() => setShowTimeErrorModal(false)}
              className="w-full"
            >
              Tutup
            </Button>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}
