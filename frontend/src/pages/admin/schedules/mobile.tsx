import { useState, useMemo } from 'react';
import { Badge } from '@/components/ui/badge';
import type { Holiday } from '@/types/holiday';
import { useNavigate } from '@tanstack/react-router';
import { useQuery } from '@tanstack/react-query';
import {
  Calendar as CalendarIcon,
  Download,
  ChevronRight,
  ChevronLeft,
  Check,
  Minus,
  Clock,
  Info,
  Plus,
  Users,
  Settings,
  FileText
} from 'lucide-react';
import { MobilePageHeader } from '@/components/mobile';
import { useAuthStore } from '@/stores';
import { getAttendance } from '@/lib/api/attendance';
import { getHolidays } from '@/lib/api/holidays';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { LoadingState } from '@/components/states';

export function MobileSchedulesPage() {
  const { user } = useAuthStore();
  const isAdmin = user?.role === 'admin' || user?.role === 'super-admin' || user?.role === 'kepala-sekolah';

  if (isAdmin) {
    return <AdminScheduleView />;
  }

  return <MyScheduleView />;
}

function AdminScheduleView() {
  const navigate = useNavigate();

  const menuItems = [
    {
      title: 'Buat Jadwal',
      description: 'Buat jadwal pelajaran baru',
      icon: Plus,
      color: 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400',
      action: () => navigate({ to: '/admin/schedules/create' })
    },
    {
      title: 'Jadwal Bulanan',
      description: 'Kelola jadwal bulanan',
      icon: CalendarIcon,
      color: 'bg-violet-50 text-violet-600 dark:bg-violet-900/20 dark:text-violet-400',
      action: () => navigate({ to: '/admin/schedules/monthly' })
    },
    {
      title: 'Assign Jadwal',
      description: 'Tetapkan jadwal ke guru',
      icon: Users,
      color: 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400',
      action: () => navigate({ to: '/admin/schedules/assign' })
    },
    {
      title: 'Builder',
      description: 'Visual schedule builder',
      icon: Settings,
      color: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400',
      action: () => navigate({ to: '/admin/schedules/builder' })
    }
  ];

  return (
    <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-20">
      {/* Header */}
      <MobilePageHeader
        title="Manajemen Jadwal"
        onBack={() => navigate({ to: '/admin/dashboard' })}
        gradient="indigo"
      />

      <div className="px-4 mt-4 space-y-4">
        {/* Menu Grid */}
        <div className="grid grid-cols-2 gap-3">
          {menuItems.map((item, index) => (
            <div
              key={index}
              onClick={item.action}
              className="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-border/50 shadow-sm active:scale-[0.98] transition-transform cursor-pointer"
            >
              <div className={cn("h-10 w-10 rounded-xl flex items-center justify-center mb-3", item.color)}>
                <item.icon className="h-5 w-5" />
              </div>
              <h3 className="font-bold text-sm mb-1">{item.title}</h3>
              <p className="text-xs text-muted-foreground">{item.description}</p>
            </div>
          ))}
        </div>

        {/* Recent Schedules Placeholder */}
        <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-sm font-bold text-foreground">Jadwal Terbaru</h2>
            <Button variant="ghost" size="sm" className="h-8 text-xs">Lihat Semua</Button>
          </div>
          <div className="text-center py-8 text-muted-foreground text-xs">
            <FileText className="h-8 w-8 mx-auto mb-2 opacity-20" />
            Belum ada jadwal yang dibuat baru-baru ini.
          </div>
        </div>
      </div>
    </div>
  );
}

function MyScheduleView() {
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const [showMonthPicker, setShowMonthPicker] = useState(false);

  // Current selected date
  const [selectedDate, setSelectedDate] = useState(new Date());
  const year = selectedDate.getFullYear();
  const month = selectedDate.getMonth() + 1; // 1-12

  // Format display
  const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  const selectedMonthDisplay = `${monthNames[selectedDate.getMonth()]} ${year}`;

  // Fetch attendance data for selected month
  const { data: attendanceData, isLoading: attendanceLoading } = useQuery({
    queryKey: ['attendance', year, month, user?.id],
    queryFn: () => {
      const startDate = new Date(year, month - 1, 1);
      const endDate = new Date(year, month, 0);
      return getAttendance({
        employee_id: Number(user?.id),
        date_from: startDate.toISOString().split('T')[0],
        date_to: endDate.toISOString().split('T')[0],
      });
    },
    enabled: !!user?.id,
  });

  // Fetch holidays for selected month
  const { data: holidaysData, isLoading: holidaysLoading } = useQuery({
    queryKey: ['holidays', year, month],
    queryFn: () => getHolidays({ year, month }),
  });

  // Process attendance data to calendar format
  const getAttendanceStatus = (day: number) => {
    if (!attendanceData?.data) return null;

    const dateStr = new Date(year, month - 1, day).toISOString().split('T')[0];
    const record = attendanceData.data.find((att) => att.date === dateStr);

    if (!record) return null;

    // Determine status based on attendance record
    if (record.status === 'present') {
      if (record.late_minutes && record.late_minutes > 0) return { status: 'late', label: 'Terlambat' };
      return { status: 'on-time', label: 'Tepat waktu' };
    } else if (record.status === 'late') {
      return { status: 'late', label: 'Terlambat' };
    } else if (record.status === 'absent') {
      return { status: 'absent', label: 'Alpha' };
    } else if (record.status === 'leave') {
      return { status: 'leave', label: 'Cuti/Dinas' };
    } else if (record.status === 'holiday') {
      return { status: 'holiday', label: 'Libur' };
    }

    return { status: 'scheduled', label: 'Ada jadwal' };
  };

  // Generate calendar grid dynamically
  const days = useMemo(() => {
    const firstDay = new Date(year, month - 1, 1);
    const lastDay = new Date(year, month, 0);
    const daysInMonth = lastDay.getDate();
    const firstDayOfWeek = firstDay.getDay(); // 0 = Sunday

    const daysList = [];

    // Add empty cells for days before month starts
    const prevMonthLastDay = new Date(year, month - 1, 0).getDate();
    for (let i = firstDayOfWeek - 1; i >= 0; i--) {
      daysList.push({
        date: null,
        prevMonth: true,
        dayNumber: prevMonthLastDay - i
      });
    }

    // Add days of the current month
    for (let i = 1; i <= daysInMonth; i++) {
      daysList.push({
        date: i,
        attendance: getAttendanceStatus(i),
      });
    }

    // Add empty cells for remaining days to complete the grid
    const remainingCells = 7 - (daysList.length % 7);
    if (remainingCells < 7) {
      for (let i = 1; i <= remainingCells; i++) {
        daysList.push({ date: null, nextMonth: true, dayNumber: i });
      }
    }

    return daysList;
  }, [year, month, attendanceData]);

  const getStatusIcon = (status: string) => {
    const iconClasses = "h-2 w-2 text-white";
    const iconStroke = 3;

    switch (status) {
      case 'leave': // Cuti/Dinas - Light Blue
        return (
          <div className="bg-sky-400 dark:bg-sky-500 rounded-full p-0.5 shadow-sm ring-1 ring-white dark:ring-gray-900">
            <Check className={iconClasses} strokeWidth={iconStroke} />
          </div>
        );
      case 'late': // Terlambat - Light Green
        return (
          <div className="bg-lime-400 dark:bg-lime-500 rounded-full p-0.5 shadow-sm ring-1 ring-white dark:ring-gray-900">
            <Check className={iconClasses} strokeWidth={iconStroke} />
          </div>
        );
      case 'on-time': // Tepat waktu - Dark Green
        return (
          <div className="bg-emerald-600 dark:bg-emerald-500 rounded-full p-0.5 shadow-sm ring-1 ring-white dark:ring-gray-900">
            <Check className={iconClasses} strokeWidth={iconStroke} />
          </div>
        );
      case 'absent': // Alpha - Red
        return (
          <div className="bg-red-500 dark:bg-red-600 rounded-full p-0.5 shadow-sm ring-1 ring-white dark:ring-gray-900">
            <Check className={iconClasses} strokeWidth={iconStroke} />
          </div>
        );
      case 'scheduled': // Ada jadwal - Gray
        return (
          <div className="bg-gray-400 dark:bg-gray-500 rounded-full p-0.5 shadow-sm ring-1 ring-white dark:ring-gray-900">
            <Check className={iconClasses} strokeWidth={iconStroke} />
          </div>
        );
      case 'holiday': // Hari libur - Red minus
        return (
          <div className="bg-red-400 dark:bg-red-500 rounded-full p-0.5 shadow-sm ring-1 ring-white dark:ring-gray-900">
            <Minus className={iconClasses} strokeWidth={iconStroke} />
          </div>
        );
      default:
        return null;
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-20">
      {/* Header */}
      <MobilePageHeader
        title="Jadwal Saya"
        onBack={() => navigate({ to: '/employee/dashboard' })}
        gradient="gray"
        actions={
          <button
            onClick={() => setShowMonthPicker(true)}
            className="p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
          >
            <CalendarIcon className="h-5 w-5 text-white" />
          </button>
        }
      />

      {/* Content */}
      <div className="px-4 space-y-4">
        {/* Month Selector & Download */}
        <div className="flex gap-3">
          <button
            onClick={() => setShowMonthPicker(true)}
            className="flex-1 bg-white dark:bg-gray-900 border border-border/50 rounded-2xl px-4 py-3 flex items-center justify-between hover:bg-muted/50 dark:hover:bg-gray-800 transition-all active:scale-[0.98] shadow-sm group"
          >
            <div className="flex flex-col items-start">
              <span className="text-xs text-muted-foreground font-medium uppercase tracking-wider">Bulan</span>
              <span className="text-base font-bold text-foreground group-hover:text-primary transition-colors">{selectedMonthDisplay}</span>
            </div>
            <div className="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
              <ChevronRight className="h-4 w-4 text-primary group-hover:rotate-90 transition-transform" />
            </div>
          </button>
          <button
            onClick={() => {
              // Download attendance report
              const filename = `jadwal-${year}-${month}.pdf`;
              alert(`Download ${filename} - Feature akan segera diimplementasikan`);
            }}
            className="bg-white dark:bg-gray-900 border border-border/50 rounded-2xl px-4 py-3 flex items-center justify-center hover:bg-muted/50 dark:hover:bg-gray-800 transition-all active:scale-95 shadow-sm aspect-square"
          >
            <Download className="h-5 w-5 text-foreground" />
          </button>
        </div>

        {/* Employee Info */}
        <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50 relative overflow-hidden">
          <div className="absolute top-0 right-0 p-4 opacity-5">
            <Clock className="h-24 w-24" />
          </div>
          <div className="relative z-10 space-y-4">
            <div className="flex items-center gap-4">
              <div className="h-12 w-12 rounded-2xl bg-gradient-to-br from-primary to-primary/80 flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-primary/20">
                {user?.name ? user.name.charAt(0).toUpperCase() : 'U'}
              </div>
              <div>
                <p className="text-xs text-muted-foreground font-medium">Karyawan</p>
                <p className="font-bold text-foreground text-sm line-clamp-1">{user?.name || '-'}</p>
                <p className="text-xs font-mono text-muted-foreground mt-0.5">{user?.employee_id || '-'}</p>
              </div>
            </div>
          </div>
        </div>

        {/* Calendar */}
        <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-sm font-bold text-foreground">Kalender Kehadiran</h2>
            <div className="flex items-center gap-2 text-xs text-muted-foreground bg-muted/50 px-2 py-1 rounded-lg">
              <Info className="h-3 w-3" />
              <span>Tap tanggal untuk detail</span>
            </div>
          </div>

          {/* Day Headers */}
          <div className="grid grid-cols-7 gap-1 mb-2">
            {['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'].map((day, i) => (
              <div key={day} className={cn(
                "text-center text-[10px] font-bold uppercase tracking-wider py-2",
                i === 0 || i === 6 ? "text-rose-500" : "text-muted-foreground"
              )}>
                {day}
              </div>
            ))}
          </div>

          {/* Calendar Grid */}
          {attendanceLoading ? (
            <div className="flex items-center justify-center py-12">
              <LoadingState message="Memuat kalender..." size="sm" />
            </div>
          ) : (
            <div className="grid grid-cols-7 gap-1">
              {days.map((day, index) => (
                <div
                  key={index}
                  className={cn(
                    "aspect-[4/5] flex flex-col items-center justify-center gap-1 rounded-xl transition-all relative border border-transparent",
                    day.date === null ? "opacity-30" : "hover:bg-muted/50 hover:border-border/50 active:scale-95 cursor-pointer",
                    // Highlight today
                    day.date === new Date().getDate() && month === new Date().getMonth() + 1 && year === new Date().getFullYear() && !day.prevMonth && !day.nextMonth
                      ? "bg-primary/5 border-primary/20"
                      : ""
                  )}
                >
                  <span className={cn(
                    "text-sm font-semibold",
                    day.date === null ? "text-muted-foreground" : "text-foreground",
                    (index % 7 === 0 || index % 7 === 6) && day.date !== null ? "text-rose-500" : ""
                  )}>
                    {day.date || day.dayNumber}
                  </span>
                  {day.attendance && getStatusIcon(day.attendance.status)}
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Legend */}
        <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50">
          <h3 className="text-sm font-bold text-foreground mb-4">Keterangan Status</h3>
          <div className="grid grid-cols-2 gap-x-4 gap-y-3">
            <LegendItem color="bg-emerald-600" label="Tepat Waktu" icon={Check} />
            <LegendItem color="bg-lime-400" label="Terlambat" icon={Check} />
            <LegendItem color="bg-sky-400" label="Cuti / Dinas" icon={Check} />
            <LegendItem color="bg-red-500" label="Alpha / Absen" icon={Check} />
            <LegendItem color="bg-red-400" label="Hari Libur" icon={Minus} />
            <LegendItem color="bg-gray-400" label="Jadwal Kerja" icon={Check} />
          </div>
        </div>

        {/* Holidays List */}
        <div className="space-y-3 pb-6">
          <div className="flex items-center justify-between px-1">
            <h2 className="text-sm font-bold text-foreground">Hari Libur Bulan Ini</h2>
            <Badge variant="outline" className="text-xs font-normal">
              {holidaysData?.data?.length || 0} Hari
            </Badge>
          </div>

          {holidaysLoading ? (
            <div className="bg-white dark:bg-gray-900 rounded-2xl p-6 text-center shadow-sm border border-border/50">
              <p className="text-xs text-muted-foreground">Memuat hari libur...</p>
            </div>
          ) : holidaysData?.data && holidaysData.data.length > 0 ? (
            <div className="space-y-2">
              {holidaysData.data.map((holiday) => (
                <HolidayCard key={holiday.id} holiday={holiday} />
              ))}
            </div>
          ) : (
            <div className="bg-white dark:bg-gray-900 rounded-2xl p-6 text-center shadow-sm border border-border/50">
              <CalendarIcon className="h-8 w-8 text-muted-foreground/30 mx-auto mb-2" />
              <p className="text-xs text-muted-foreground">Tidak ada hari libur di bulan ini</p>
            </div>
          )}
        </div>
      </div>

      {/* Month Picker Dialog */}
      <Dialog open={showMonthPicker} onOpenChange={setShowMonthPicker}>
        <DialogContent className="sm:max-w-[380px] p-0 gap-0 overflow-hidden rounded-3xl">
          <DialogHeader className="px-6 pt-6 pb-4 bg-gradient-to-br from-primary/10 via-primary/5 to-transparent">
            <DialogTitle className="text-xl font-bold">Pilih Periode</DialogTitle>
            <p className="text-xs text-muted-foreground mt-1">
              Pilih bulan dan tahun untuk melihat jadwal
            </p>
          </DialogHeader>

          <div className="px-6 py-5 space-y-5">
            {/* Year Selector */}
            <div className="flex items-center gap-3 bg-muted/30 p-2 rounded-2xl border border-border/50">
              <Button
                variant="ghost"
                size="icon"
                onClick={() => setSelectedDate(new Date(year - 1, month - 1, 1))}
                className="h-10 w-10 rounded-xl hover:bg-background shadow-sm transition-all"
              >
                <ChevronLeft className="h-4 w-4" />
              </Button>
              <div className="flex-1 text-center">
                <span className="text-lg font-bold text-foreground">{year}</span>
              </div>
              <Button
                variant="ghost"
                size="icon"
                onClick={() => setSelectedDate(new Date(year + 1, month - 1, 1))}
                className="h-10 w-10 rounded-xl hover:bg-background shadow-sm transition-all"
              >
                <ChevronRight className="h-4 w-4" />
              </Button>
            </div>

            {/* Month Grid */}
            <div className="grid grid-cols-3 gap-2">
              {monthNames.map((monthName, index) => {
                const isSelected = month === index + 1;
                return (
                  <button
                    key={index}
                    onClick={() => {
                      setSelectedDate(new Date(year, index, 1));
                      setShowMonthPicker(false);
                    }}
                    className={cn(
                      "relative overflow-hidden rounded-xl px-2 py-3 text-xs font-semibold transition-all duration-200 active:scale-95 border",
                      isSelected
                        ? "bg-primary text-primary-foreground border-primary shadow-lg shadow-primary/20"
                        : "bg-background text-foreground border-border/50 hover:border-primary/50 hover:bg-primary/5"
                    )}
                  >
                    {monthName}
                  </button>
                );
              })}
            </div>
          </div>

          <div className="p-4 bg-muted/30 border-t border-border/50">
            <Button
              variant="outline"
              className="w-full rounded-xl"
              onClick={() => setShowMonthPicker(false)}
            >
              Tutup
            </Button>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}

function LegendItem({ color, label, icon: Icon }: { color: string, label: string, icon: React.ElementType }) {
  return (
    <div className="flex items-center gap-3">
      <div className={cn("h-6 w-6 rounded-full flex items-center justify-center shadow-sm", color)}>
        <Icon className="h-3 w-3 text-white" strokeWidth={3} />
      </div>
      <span className="text-xs font-medium text-muted-foreground">{label}</span>
    </div>
  );
}

function HolidayCard({ holiday }: { holiday: Holiday }) {
  const holidayDate = new Date(holiday.date);
  const formattedDate = holidayDate.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });

  const getHolidayColor = (type: string) => {
    switch (type) {
      case 'public_holiday': return 'bg-red-50 text-red-700 border-red-100 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/50';
      case 'religious_holiday': return 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-900/50';
      default: return 'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-900/50';
    }
  };

  return (
    <div className={cn("rounded-2xl p-4 border flex items-center gap-4 transition-all hover:shadow-md", getHolidayColor(holiday.type))}>
      <div className="flex flex-col items-center justify-center bg-white/50 dark:bg-black/20 rounded-xl h-12 w-12 shrink-0 backdrop-blur-sm">
        <span className="text-lg font-bold leading-none">{holidayDate.getDate()}</span>
        <span className="text-[10px] font-medium uppercase">{holidayDate.toLocaleDateString('id-ID', { month: 'short' })}</span>
      </div>
      <div className="flex-1 min-w-0">
        <p className="font-bold text-sm truncate">{holiday.name}</p>
        <p className="text-xs opacity-80 mt-0.5">{formattedDate}</p>
      </div>
    </div>
  );
}


