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
import { useAuthStore } from '@/stores';
import { useQuery } from '@tanstack/react-query';
import { getTodayAttendance } from '@/lib/api/attendance';
import { getEmployeeDashboardData } from '@/lib/api/employees';

export function MobileEmployeeAttendancePage() {
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const [currentTime, setCurrentTime] = useState('');
  const [currentDate, setCurrentDate] = useState('');

  // State for re-attendance confirmation
  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [pendingAction, setPendingAction] = useState<'check_in' | 'check_out' | null>(null);

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
  const scheduleMessage = dashboardStats?.schedule.today.message;
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

  const handleActionClick = (type: 'check_in' | 'check_out') => {
    if (!canAttend) {
      // We can show a toast or modal here, but for now rely on the UI state
      return;
    }

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

  // Format time from "HH:MM:SS" to "HH:MM:SS"
  const formatTime = (time: string | null) => {
    if (!time) return '--:--:--';
    try {
      const date = new Date(time);
      if (isNaN(date.getTime())) return time;
      return date.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
      }).replace(/\./g, ':');
    } catch (e) {
      return time;
    }
  };

  // Format date
  const formatDate = (date: string | null) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    });
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
    <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-20">
      {/* Header Wrapper */}
      <div className="px-4 pt-3 pb-3">
        <div className="bg-card/80 dark:bg-card/60 backdrop-blur-md rounded-3xl p-1.5 shadow-xl border border-border/40 dark:border-border/30">
          {/* Header Section */}
          <div className="bg-gradient-to-br from-primary/95 via-primary to-primary/90 dark:from-primary/90 dark:via-primary/95 dark:to-primary px-5 pt-8 pb-4 space-y-2.5 shadow-lg shadow-primary/10 rounded-[20px]">
            {/* User Info */}
            <div className="flex items-center gap-3">
              <div className="h-11 w-11 rounded-full bg-gradient-to-br from-white/20 to-white/10 backdrop-blur-sm flex items-center justify-center text-white font-bold shadow-lg border-2 border-white/20">
                {user?.name ? getInitials(user.name) : 'U'}
              </div>
              <div className="flex-1">
                <p className="text-xs text-white/70">Om Swastyastu</p>
                <p className="text-white font-semibold text-sm">
                  {user?.name || 'User'}
                </p>
              </div>
            </div>

            {/* Date Time Card */}
            <div className="bg-white/10 backdrop-blur-md rounded-xl px-3 py-2 flex items-center gap-2.5 border border-white/20 shadow-sm">
              <div className="flex items-center gap-2 flex-1">
                <Calendar className="h-3.5 w-3.5 text-white/80" />
                <span className="text-xs text-white/90">{currentDate}</span>
              </div>
              <div className="flex items-center gap-2">
                <Clock className="h-3.5 w-3.5 text-white/80" />
                <span className="text-xs text-white/90 font-mono">{currentTime}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="px-4 pb-4 space-y-3">
        {/* Attendance Status Cards */}
        <div className="grid grid-cols-2 gap-3">
          {/* Check In Status */}
          <div className={`rounded-2xl p-3 flex flex-col items-center justify-center text-center ${todayAttendance?.has_checked_in ? 'bg-emerald-500 dark:bg-emerald-600 shadow-emerald-500/20' : 'bg-card dark:bg-card/50 border border-border/50'} shadow-lg transition-all`}>
            <p className={`text-[10px] font-semibold uppercase tracking-wider mb-1.5 ${todayAttendance?.has_checked_in ? 'text-white/90' : 'text-muted-foreground/70'}`}>
              Datang
            </p>
            <p className={`text-xl font-bold font-mono tracking-tight mb-1.5 ${todayAttendance?.has_checked_in ? 'text-white' : 'text-muted-foreground'}`}>
              {formatTime(todayAttendance?.attendance?.check_in_time || null)}
            </p>
            <p className={`text-[10px] ${todayAttendance?.has_checked_in ? 'text-white/80' : 'text-muted-foreground/60'}`}>
              {todayAttendance?.attendance?.date ? formatDate(todayAttendance.attendance.date) : new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
            </p>
          </div>

          {/* Check Out Status */}
          <div className={`rounded-2xl p-3 flex flex-col items-center justify-center text-center ${todayAttendance?.has_checked_out ? 'bg-rose-500 dark:bg-rose-600 shadow-rose-500/20' : 'bg-card dark:bg-card/50 border border-border/50'} shadow-lg transition-all`}>
            <p className={`text-[10px] font-semibold uppercase tracking-wider mb-1.5 ${todayAttendance?.has_checked_out ? 'text-white/90' : 'text-muted-foreground/70'}`}>
              Pulang
            </p>
            <p className={`text-xl font-bold font-mono tracking-tight mb-1.5 ${todayAttendance?.has_checked_out ? 'text-white' : 'text-muted-foreground'}`}>
              {formatTime(todayAttendance?.attendance?.check_out_time || null)}
            </p>
            <p className={`text-[10px] ${todayAttendance?.has_checked_out ? 'text-white/80' : 'text-muted-foreground/60'}`}>
              {todayAttendance?.attendance?.date ? formatDate(todayAttendance.attendance.date) : new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
            </p>
          </div>
        </div>

        {/* Alert if needs check out */}
        {needsCheckOut && (
          <Alert variant="destructive" className="border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/30">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription className="text-red-800 dark:text-red-200">
              Anda belum melakukan absensi pulang
            </AlertDescription>
          </Alert>
        )}

        {/* Action Buttons Container */}
        <div className="bg-background dark:bg-gray-900 rounded-3xl px-4 py-3 space-y-3 shadow-2xl border border-border/50">
          {/* Check In/Out Cards */}
          <div className="space-y-2">
            {/* Datang (Check In) */}
            <button
              onClick={() => handleActionClick('check_in')}
              disabled={!canAttend}
              className={`w-full rounded-xl p-3 shadow-lg transition-all active:scale-[0.98] group ${canAttend
                ? 'bg-gradient-to-br from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 dark:from-emerald-600 dark:to-emerald-700 dark:hover:from-emerald-700 dark:hover:to-emerald-800 shadow-emerald-500/30 dark:shadow-emerald-600/20'
                : 'bg-muted cursor-not-allowed opacity-70'
                }`}
            >
              <div className="flex items-center gap-2.5">
                <div className={`backdrop-blur-sm rounded-lg p-2 border border-white/10 ${canAttend ? 'bg-white/20 dark:bg-white/10' : 'bg-gray-400/20'}`}>
                  <LogIn className={`h-5 w-5 drop-shadow-sm ${canAttend ? 'text-white' : 'text-muted-foreground'}`} />
                </div>
                <div className="flex-1 text-left">
                  <h3 className={`text-base font-bold drop-shadow-sm ${canAttend ? 'text-white' : 'text-muted-foreground'}`}>
                    {canAttend ? 'Datang' : (scheduleType === 'holiday' ? 'Libur' : 'Tidak Ada Jadwal')}
                  </h3>
                  <p className={`text-xs line-clamp-1 ${canAttend ? 'text-white/90 dark:text-white/80' : 'text-muted-foreground'}`}>
                    {canAttend ? 'Absensi wajib yang dipergunakan pada saat datang di hari kerja' : (scheduleMessage || 'Absensi tidak tersedia hari ini')}
                  </p>
                </div>
                {canAttend && <ChevronRight className="h-5 w-5 text-white/90 group-hover:translate-x-1 transition-transform" />}
              </div>
            </button>

            {/* Pulang (Check Out) */}
            <button
              onClick={() => handleActionClick('check_out')}
              className="w-full bg-gradient-to-br from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 dark:from-rose-600 dark:to-rose-700 dark:hover:from-rose-700 dark:hover:to-rose-800 rounded-xl p-3 shadow-lg shadow-rose-500/30 dark:shadow-rose-600/20 transition-all active:scale-[0.98] group"
            >
              <div className="flex items-center gap-2.5">
                <div className="bg-white/20 dark:bg-white/10 backdrop-blur-sm rounded-lg p-2 border border-white/10">
                  <LogOut className="h-5 w-5 text-white drop-shadow-sm" />
                </div>
                <div className="flex-1 text-left">
                  <h3 className="text-base font-bold text-white drop-shadow-sm">Pulang</h3>
                  <p className="text-xs text-white/90 dark:text-white/80 line-clamp-1">
                    Absensi wajib yang dipergunakan pada saat pulang di hari kerja
                  </p>
                </div>
                <ChevronRight className="h-5 w-5 text-white/90 group-hover:translate-x-1 transition-transform" />
              </div>
            </button>
          </div>

          {/* Pengajuan Section */}
          <div className="space-y-2">
            <h2 className="text-xs font-bold text-foreground">Pengajuan</h2>

            {/* Grid 2x2 - Mixed Layout */}
            <div className="grid grid-cols-2 gap-2">
              {/* Ubah Absen - Vertical Layout */}
              <Card className="p-2.5 hover:shadow-lg dark:hover:shadow-emerald-500/5 hover:border-emerald-200 dark:hover:border-emerald-800 transition-all cursor-pointer active:scale-95 border-border/50 rounded-xl">
                <div className="space-y-2">
                  <div className="bg-emerald-50 dark:bg-emerald-950/50 rounded-lg p-2 w-fit border border-emerald-100 dark:border-emerald-900">
                    <Edit className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-foreground text-sm">Ubah Absen</h3>
                    <p className="text-xs text-muted-foreground mt-0.5 line-clamp-2">
                      Ajukan perubahan absen karena alasan tertentu
                    </p>
                  </div>
                </div>
              </Card>

              {/* Cuti - Vertical Layout */}
              <Card
                onClick={() => navigate({ to: '/employee/leave' })}
                className="p-2.5 hover:shadow-lg dark:hover:shadow-rose-500/5 hover:border-rose-200 dark:hover:border-rose-800 transition-all cursor-pointer active:scale-95 border-border/50 rounded-xl"
              >
                <div className="space-y-2">
                  <div className="bg-rose-50 dark:bg-rose-950/50 rounded-lg p-2 w-fit border border-rose-100 dark:border-rose-900">
                    <CalendarOff className="h-5 w-5 text-rose-600 dark:text-rose-400" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-foreground text-sm">Cuti</h3>
                    <p className="text-xs text-muted-foreground mt-0.5 line-clamp-2">
                      Ajukan cuti karena alasan tertentu
                    </p>
                  </div>
                </div>
              </Card>

              {/* Dinas/Diklat - Horizontal Compact */}
              <Card
                onClick={() => navigate({ to: '/employee/leave' })}
                className="p-2 hover:shadow-lg hover:border-muted-foreground/20 transition-all cursor-pointer active:scale-95 border-border/50 rounded-xl"
              >
                <div className="flex items-center gap-2">
                  <div className="bg-muted/50 dark:bg-muted/20 rounded-lg p-1.5 shrink-0 border border-border/50">
                    <Briefcase className="h-4 w-4 text-muted-foreground" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <h3 className="font-semibold text-foreground text-xs leading-tight">Dinas/Diklat</h3>
                    <p className="text-[10px] text-muted-foreground line-clamp-1">Ajukan Dinas/Diklat</p>
                  </div>
                  <ChevronRight className="h-4 w-4 text-muted-foreground shrink-0" />
                </div>
              </Card>

              {/* Jadwal Saya - Horizontal Compact */}
              <Card
                onClick={() => navigate({ to: '/employee/schedule' })}
                className="p-2 hover:shadow-lg hover:border-muted-foreground/20 transition-all cursor-pointer active:scale-95 border-border/50 rounded-xl"
              >
                <div className="flex items-center gap-2">
                  <div className="bg-muted/50 dark:bg-muted/20 rounded-lg p-1.5 shrink-0 border border-border/50">
                    <Calendar className="h-4 w-4 text-muted-foreground" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <h3 className="font-semibold text-foreground text-xs leading-tight">Jadwal Saya</h3>
                    <p className="text-[10px] text-muted-foreground line-clamp-1">Lihat detail jadwal kehadiran rutin Anda disini</p>
                  </div>
                  <ChevronRight className="h-4 w-4 text-muted-foreground shrink-0" />
                </div>
              </Card>
            </div>
          </div>

          {/* Info Footer */}
          <div className="flex items-center justify-center gap-3 pt-1">
            <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
              <MapPin className="h-3.5 w-3.5 text-emerald-500 dark:text-emerald-400" />
              <span>GPS Enabled</span>
            </div>
            <div className="h-1 w-1 rounded-full bg-border" />
            <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
              <ScanFace className="h-3.5 w-3.5 text-primary" />
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
    </div>
  );
}
