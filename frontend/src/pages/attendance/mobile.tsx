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
} from 'lucide-react';
import { Card } from '@/components/ui/card';
import { useAuthStore } from '@/stores';
import { useQuery } from '@tanstack/react-query';
import { getTodayAttendance } from '@/lib/api/attendance';
import { cn } from '@/lib/utils';

export function MobileAttendancePage() {
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const [currentTime, setCurrentTime] = useState('');
  const [currentDate, setCurrentDate] = useState('');

  // Fetch today's attendance
  const { data: todayAttendance, refetch } = useQuery({
    queryKey: ['attendance-today', user?.id],
    queryFn: getTodayAttendance,
    enabled: !!user?.id,
    refetchInterval: 30000, // Refetch every 30 seconds
  });

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

  const handleCheckIn = () => {
    navigate({
      to: '/attendance/verify-location',
      search: { type: 'check-in' },
    });
  };

  const handleCheckOut = () => {
    navigate({
      to: '/attendance/verify-location',
      search: { type: 'check-out' },
    });
  };

  // Format time from "HH:MM:SS" to "HH:MM"
  const formatTime = (time: string | null) => {
    if (!time) return null;
    return time.substring(0, 5); // Get "HH:MM" from "HH:MM:SS"
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

  return (
    <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900">
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
      <div className="px-4 pb-4">
        <div className="bg-background dark:bg-gray-900 rounded-3xl px-4 py-3 space-y-3 shadow-2xl border border-border/50">
          {/* Check In/Out Cards */}
          <div className="space-y-2">
            {/* Datang (Check In) */}
            <button
              onClick={handleCheckIn}
              className="w-full bg-gradient-to-br from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 dark:from-emerald-600 dark:to-emerald-700 dark:hover:from-emerald-700 dark:hover:to-emerald-800 rounded-xl p-3 shadow-lg shadow-emerald-500/30 dark:shadow-emerald-600/20 transition-all active:scale-[0.98] group"
            >
              <div className="flex items-center gap-2.5">
                <div className="bg-white/20 dark:bg-white/10 backdrop-blur-sm rounded-lg p-2 border border-white/10">
                  <LogIn className="h-5 w-5 text-white drop-shadow-sm" />
                </div>
                <div className="flex-1 text-left">
                  <h3 className="text-base font-bold text-white drop-shadow-sm">Datang</h3>
                  <p className="text-xs text-white/90 dark:text-white/80 line-clamp-1">
                    {todayAttendance?.check_in_time
                      ? `${formatTime(todayAttendance.check_in_time)}`
                      : 'Absensi wajib yang dipergunakan pada saat datang di hari kerja'}
                  </p>
                </div>
                {todayAttendance?.check_in_time ? (
                  <Clock className="h-5 w-5 text-white/90" />
                ) : (
                  <ChevronRight className="h-5 w-5 text-white/90 group-hover:translate-x-1 transition-transform" />
                )}
              </div>
            </button>

            {/* Pulang (Check Out) */}
            <button
              onClick={handleCheckOut}
              className="w-full bg-gradient-to-br from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 dark:from-rose-600 dark:to-rose-700 dark:hover:from-rose-700 dark:hover:to-rose-800 rounded-xl p-3 shadow-lg shadow-rose-500/30 dark:shadow-rose-600/20 transition-all active:scale-[0.98] group"
            >
              <div className="flex items-center gap-2.5">
                <div className="bg-white/20 dark:bg-white/10 backdrop-blur-sm rounded-lg p-2 border border-white/10">
                  <LogOut className="h-5 w-5 text-white drop-shadow-sm" />
                </div>
                <div className="flex-1 text-left">
                  <h3 className="text-base font-bold text-white drop-shadow-sm">Pulang</h3>
                  <p className="text-xs text-white/90 dark:text-white/80 line-clamp-1">
                    {todayAttendance?.check_out_time
                      ? `${formatTime(todayAttendance.check_out_time)}`
                      : 'Absensi wajib yang dipergunakan pada saat pulang di hari kerja'}
                  </p>
                </div>
                {todayAttendance?.check_out_time ? (
                  <Clock className="h-5 w-5 text-white/90" />
                ) : (
                  <ChevronRight className="h-5 w-5 text-white/90 group-hover:translate-x-1 transition-transform" />
                )}
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
                onClick={() => navigate({ to: '/leave' })}
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
                onClick={() => navigate({ to: '/leave' })}
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
                onClick={() => navigate({ to: '/schedules' })}
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
    </div>
  );
}
