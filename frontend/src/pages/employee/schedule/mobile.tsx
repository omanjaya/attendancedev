import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
    Calendar,
    ChevronLeft,
    ChevronRight,
    Check,
    Minus,
    Clock,
    X,
} from 'lucide-react';
import { useAuthStore } from '@/stores';
import { MobilePageHeader } from '@/components/mobile';
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
    isValid,
} from 'date-fns';
import { id } from 'date-fns/locale';
import { cn } from '@/lib/utils';

// Safe date formatting helper
const safeFormatDate = (dateStr: string | null | undefined, formatStr: string, fallback: string = '-') => {
    if (!dateStr) return fallback;
    try {
        const date = parseISO(dateStr);
        if (!isValid(date)) return fallback;
        return format(date, formatStr, { locale: id });
    } catch {
        return fallback;
    }
};
import { LoadingState } from '@/components/states';
import { getAttendance } from '@/lib/api/attendance';
import { getMySchedule } from '@/lib/api/schedules';
import type { Attendance } from '@/types';

interface AttendanceDay {
    date: string;
    status: 'leave' | 'late' | 'on-time' | 'absent' | 'holiday';
}

interface RahinaEvent {
    date: string;
    name: string;
    type: 'purnama' | 'tilem';
}

export function MobileEmployeeSchedulePage() {
    const { user } = useAuthStore();
    const [selectedMonth, setSelectedMonth] = useState(new Date());
    const [showMonthPicker, setShowMonthPicker] = useState(false);
    const [selectedDate, setSelectedDate] = useState<Date | null>(null);
    const [showScheduleModal, setShowScheduleModal] = useState(false);

    // Fetch employee's schedule
    const { data: scheduleData } = useQuery({
        queryKey: ['employee', 'my-schedule', format(selectedMonth, 'yyyy-MM')],
        queryFn: async () => {
            return await getMySchedule({
                month: selectedMonth.getMonth() + 1,
                year: selectedMonth.getFullYear(),
            });
        },
    });

    // Fetch employee's attendance data for the calendar
    const { data: attendanceData, isLoading } = useQuery({
        queryKey: ['employee', 'schedule-calendar', user?.id, format(selectedMonth, 'yyyy-MM')],
        queryFn: async () => {
            const monthStart = startOfMonth(selectedMonth);
            const monthEnd = endOfMonth(selectedMonth);

            // Fetch attendance records for the month
            // Note: Backend now auto-filters by authenticated user for employee/guru roles
            const response = await getAttendance({
                date_from: format(monthStart, 'yyyy-MM-dd'),
                date_to: format(monthEnd, 'yyyy-MM-dd'),
                employee_id: user?.employee?.id, // Use employee UUID, not user.employee_id
                per_page: 100, // Fetch all records for the month
            });

            // Transform API response to calendar format
            const attendanceMap = new Map<string, any>();

            response.data.forEach((record: Attendance) => {
                const date = record.date;
                let status: AttendanceDay['status'] = 'on-time';

                // Determine status based on attendance record
                if (record.status === 'absent') {
                    status = 'absent'; // Alpha
                } else if (record.status === 'late') {
                    status = 'late'; // Terlambat
                } else if (record.status === 'present') {
                    status = 'on-time'; // Tepat waktu
                } else if (record.status === 'holiday' || record.status === 'leave') {
                    status = 'holiday'; // Libur/Cuti
                } else if (record.status === 'sick_leave' || record.status === 'permission') {
                    status = 'leave'; // Cuti/Ijin/Sakit
                }

                // Store full record with status
                attendanceMap.set(date, {
                    date,
                    status,
                    check_in_time: record.check_in_time || record.check_in,
                    check_out_time: record.check_out_time || record.check_out,
                    record, // Keep record just in case
                });
            });

            const attendance = Array.from(attendanceMap.values());
            const rahina: RahinaEvent[] = [];

            return {
                attendance,
                map: attendanceMap,
                rahina,
            };
        },
        refetchOnMount: true,
        staleTime: 0,
    });

    const handlePreviousMonth = () => {
        setSelectedMonth(subMonths(selectedMonth, 1));
    };

    const handleNextMonth = () => {
        setSelectedMonth(addMonths(selectedMonth, 1));
    };

    const getAttendanceIcon = (status: string) => {
        switch (status) {
            case 'leave':
                return <Check className="h-3.5 w-3.5 text-white" strokeWidth={2.5} />;
            case 'late':
                return <Check className="h-3.5 w-3.5 text-white" strokeWidth={2.5} />;
            case 'on-time':
                return <Check className="h-3.5 w-3.5 text-white" strokeWidth={2.5} />;
            case 'absent':
                return <Check className="h-3.5 w-3.5 text-white" strokeWidth={2.5} />;
            case 'holiday':
                return <Minus className="h-3.5 w-3.5 text-white" strokeWidth={2.5} />;
            default:
                return null;
        }
    };

    const getAttendanceColor = (status: string) => {
        switch (status) {
            case 'leave':
                return 'bg-sky-400'; // Biru muda = cuti/ijin/sakit
            case 'late':
                return 'bg-emerald-400'; // Hijau muda = terlambat
            case 'on-time':
                return 'bg-emerald-600'; // Hijau tua = tepat waktu
            case 'absent':
                return 'bg-rose-500'; // Merah = alpha
            case 'holiday':
                return 'bg-rose-400'; // (-) Merah = libur
            default:
                return '';
        }
    };

    const monthStart = startOfMonth(selectedMonth);
    const monthEnd = endOfMonth(selectedMonth);
    const calendarStart = startOfWeek(monthStart, { weekStartsOn: 0 });
    const calendarEnd = endOfWeek(monthEnd, { weekStartsOn: 0 });
    const days = eachDayOfInterval({ start: calendarStart, end: calendarEnd });

    if (isLoading) {
        return (
            <div className="min-h-screen bg-background flex items-center justify-center">
                <LoadingState message="Memuat jadwal..." size="sm" />
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-background pb-24">
            <MobilePageHeader
                title="Jadwal Saya"
                gradient="indigo"
                backTo="/employee/dashboard"
            />

            {/* Content */}
            <div className="px-4 space-y-4">
                {/* Month Selector - Symmetrical */}
                <div className="flex items-center justify-center gap-3">
                    <button
                        onClick={handlePreviousMonth}
                        className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-3.5 hover:bg-muted/50 transition-all active:scale-95 w-12 h-12 flex items-center justify-center"
                        aria-label="Bulan Sebelumnya"
                    >
                        <ChevronLeft className="h-5 w-5" />
                    </button>
                    <button
                        onClick={() => setShowMonthPicker(true)}
                        className="flex-1 bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 px-6 py-3.5 flex items-center justify-center gap-2 hover:bg-muted/50 transition-all active:scale-[0.98] cursor-pointer max-w-xs"
                    >
                        <span className="text-base font-semibold capitalize">
                            {format(selectedMonth, 'MMMM yyyy', { locale: id })}
                        </span>
                        <Calendar className="h-5 w-5 text-muted-foreground" />
                    </button>
                    <button
                        onClick={handleNextMonth}
                        className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-3.5 hover:bg-muted/50 transition-all active:scale-95 w-12 h-12 flex items-center justify-center"
                        aria-label="Bulan Berikutnya"
                    >
                        <ChevronRight className="h-5 w-5" />
                    </button>
                </div>

                {/* Employee Info */}
                <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 px-5 py-4">
                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <span className="text-sm text-muted-foreground">Nama</span>
                            <span className="text-sm font-semibold text-foreground">{user?.name || '-'}</span>
                        </div>
                        <div className="h-px bg-border/50" />
                        <div className="flex items-center justify-between">
                            <span className="text-sm text-muted-foreground">NIP</span>
                            <span className="text-sm font-semibold font-mono text-foreground">{user?.employee_id || '-'}</span>
                        </div>
                    </div>
                </div>

                {/* Calendar Grid */}
                <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 px-4 py-5">
                    {/* Day Headers */}
                    <div className="grid grid-cols-7 gap-2 mb-4">
                        {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((day) => (
                            <div key={day} className="text-center">
                                <span className="text-xs font-semibold text-muted-foreground/80">
                                    {day}
                                </span>
                            </div>
                        ))}
                    </div>

                    {/* Calendar Days */}
                    <div className="grid grid-cols-7 gap-1.5">
                        {days.map((day, idx) => {
                            const isCurrentMonth = day.getMonth() === selectedMonth.getMonth();
                            const dayAttendance = attendanceData?.attendance.find((a) =>
                                isSameDay(parseISO(a.date), day)
                            );

                            return (
                                <button
                                    key={idx}
                                    onClick={() => {
                                        if (isCurrentMonth) {
                                            setSelectedDate(day);
                                            setShowScheduleModal(true);
                                        }
                                    }}
                                    className={cn(
                                        "flex flex-col items-center justify-start min-h-[56px] py-2 rounded-lg transition-all",
                                        isCurrentMonth && "hover:bg-muted/50 active:scale-95 cursor-pointer",
                                        !isCurrentMonth && "cursor-default"
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'text-sm font-medium mb-1',
                                            !isCurrentMonth && 'text-muted-foreground/30',
                                            isCurrentMonth && 'text-foreground'
                                        )}
                                    >
                                        {format(day, 'd')}
                                    </span>
                                    {dayAttendance ? (
                                        <div
                                            className={cn(
                                                'rounded-full p-1.5 flex items-center justify-center shadow-md',
                                                getAttendanceColor(dayAttendance.status)
                                            )}
                                        >
                                            {getAttendanceIcon(dayAttendance.status)}
                                        </div>
                                    ) : (
                                        scheduleData?.schedule?.working_days?.some((d) =>
                                            isSameDay(parseISO(d), day)
                                        ) && (
                                            <div className="rounded-full p-1.5 flex items-center justify-center shadow-md bg-gray-400">
                                                <Check className="h-3.5 w-3.5 text-white" strokeWidth={2.5} />
                                            </div>
                                        )
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* Legend */}
                <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 px-4 py-3">
                    <div className="grid grid-cols-2 gap-2 text-xs">
                        <div className="flex items-center gap-2">
                            <div className="bg-sky-400 rounded-full p-1 shadow-sm">
                                <Check className="h-3 w-3 text-white" strokeWidth={2.5} />
                            </div>
                            <span className="text-muted-foreground">Cuti/Ijin/Sakit</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="bg-gray-400 rounded-full p-1 shadow-sm">
                                <Check className="h-3 w-3 text-white" strokeWidth={2.5} />
                            </div>
                            <span className="text-muted-foreground">Jadwal Kerja</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="bg-emerald-400 rounded-full p-1 shadow-sm">
                                <Check className="h-3 w-3 text-white" strokeWidth={2.5} />
                            </div>
                            <span className="text-muted-foreground">Terlambat</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="bg-emerald-600 rounded-full p-1 shadow-sm">
                                <Check className="h-3 w-3 text-white" strokeWidth={2.5} />
                            </div>
                            <span className="text-muted-foreground">Tepat Waktu</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="bg-rose-500 rounded-full p-1 shadow-sm">
                                <Check className="h-3 w-3 text-white" strokeWidth={2.5} />
                            </div>
                            <span className="text-muted-foreground">Alpha</span>
                        </div>
                        <div className="flex items-center gap-2 col-span-2">
                            <div className="bg-rose-400 rounded-full p-1 shadow-sm">
                                <Minus className="h-3 w-3 text-white" strokeWidth={2.5} />
                            </div>
                            <span className="text-muted-foreground">Hari Raya/Libur</span>
                        </div>
                    </div>
                </div>

                {/* Daftar Rahina */}
                <div className="space-y-3 pb-2">
                    <h2 className="text-sm font-bold text-foreground px-1">Daftar Rahina</h2>
                    <div className="space-y-3">
                        {attendanceData?.rahina.map((event, idx) => (
                            <div
                                key={idx}
                                className="flex items-center gap-3.5 bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 px-4 py-3.5 hover:shadow-md transition-shadow"
                            >
                                <div
                                    className={cn(
                                        'w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0 shadow-md',
                                        event.type === 'purnama'
                                            ? 'bg-gradient-to-br from-yellow-400 to-yellow-600'
                                            : 'bg-gradient-to-br from-gray-700 to-gray-900'
                                    )}
                                >
                                    <div className={cn(
                                        'w-6 h-6 rounded-full',
                                        event.type === 'purnama' ? 'bg-white/30' : 'bg-white/20'
                                    )} />
                                </div>
                                <div className="flex-1">
                                    <p className="text-sm font-semibold leading-relaxed">
                                        {safeFormatDate(event.date, 'dd MMM yyyy')} - {event.name}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Schedule Detail Modal */}
            {showScheduleModal && selectedDate && (
                <div
                    className="fixed inset-0 z-[60] flex items-end justify-center bg-black/50 backdrop-blur-sm"
                    onClick={() => setShowScheduleModal(false)}
                >
                    <div
                        className="bg-card rounded-t-3xl shadow-2xl border-t border-border/50 w-full max-h-[80vh] overflow-y-auto animate-in slide-in-from-bottom"
                        onClick={(e) => e.stopPropagation()}
                    >
                        {/* Modal Header */}
                        <div className="sticky top-0 bg-card/95 backdrop-blur-md px-5 py-4 border-b border-border/50 flex items-center justify-between">
                            <div>
                                <h3 className="text-lg font-bold">Jadwal Kerja</h3>
                                <p className="text-sm text-muted-foreground capitalize">
                                    {format(selectedDate, 'EEEE, dd/MM/yyyy', { locale: id })}
                                </p>
                            </div>
                            <button
                                onClick={() => setShowScheduleModal(false)}
                                className="p-2 hover:bg-muted rounded-full transition-colors"
                                aria-label="Tutup"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>

                        {/* Modal Content */}
                        <div className="px-5 py-6 space-y-4">
                            {(() => {
                                const dayAttendance = attendanceData?.attendance.find((a) => {
                                    if (!a.date || !selectedDate) return false;
                                    try {
                                        const recordDate = new Date(a.date);
                                        const fmt = new Intl.DateTimeFormat('en-CA', { // YYYY-MM-DD
                                            timeZone: 'Asia/Makassar',
                                            year: 'numeric',
                                            month: '2-digit',
                                            day: '2-digit'
                                        });
                                        return fmt.format(recordDate) === fmt.format(selectedDate);
                                    } catch (e) {
                                        return false;
                                    }
                                });



                                // Get schedule times from actual schedule data
                                const schedule = scheduleData?.schedule;
                                const scheduleStartTime = schedule?.default_start_time || '08:00:00';
                                const scheduleEndTime = schedule?.default_end_time || '16:00:00';

                                // Format attendance times to HH:MM:SS in WITA (UTC+8) manually
                                const formatTime = (timeString: string | undefined) => {
                                    if (!timeString) return '-';
                                    try {
                                        // Check if it's ISO timestamp (contains T)
                                        if (timeString.includes('T')) {
                                            const date = new Date(timeString);
                                            // Get UTC parts
                                            const utcHours = date.getUTCHours();
                                            const utcMinutes = date.getUTCMinutes();
                                            const utcSeconds = date.getUTCSeconds();

                                            // Add 8 hours for WITA
                                            let hours = utcHours + 8;

                                            // Handle day overflow (simple modulo)
                                            if (hours >= 24) hours -= 24;

                                            const pad = (n: number) => n.toString().padStart(2, '0');
                                            return `${pad(hours)}:${pad(utcMinutes)}:${pad(utcSeconds)}`;
                                        }
                                        // If it's already HH:MM:SS format, return as is
                                        return timeString;
                                    } catch (error) {
                                        console.error('Error formatting time:', error);
                                        return timeString;
                                    }
                                };

                                const checkInTimeFormatted = formatTime(dayAttendance?.check_in_time);
                                const checkOutTimeFormatted = formatTime(dayAttendance?.check_out_time);

                                return (
                                    <>
                                        {/* Jadwal Kerja - Red Cards */}
                                        <div className="space-y-2.5">
                                            {/* Jam Masuk */}
                                            <div className="bg-gradient-to-br from-rose-500 to-rose-600 rounded-2xl px-4 py-3.5 shadow-lg">
                                                <div className="flex items-center justify-between text-white">
                                                    <div className="flex items-center gap-3">
                                                        <div className="bg-white/20 rounded-xl p-2">
                                                            <Clock className="h-5 w-5" />
                                                        </div>
                                                        <span className="font-medium">Jam masuk</span>
                                                    </div>
                                                    <span className="text-lg font-bold">{scheduleStartTime} WITA</span>
                                                </div>
                                            </div>

                                            {/* Jam Keluar */}
                                            <div className="bg-gradient-to-br from-rose-500 to-rose-600 rounded-2xl px-4 py-3.5 shadow-lg">
                                                <div className="flex items-center justify-between text-white">
                                                    <div className="flex items-center gap-3">
                                                        <div className="bg-white/20 rounded-xl p-2">
                                                            <Clock className="h-5 w-5" />
                                                        </div>
                                                        <span className="font-medium">Jam keluar</span>
                                                    </div>
                                                    <span className="text-lg font-bold">{scheduleEndTime} WITA</span>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Separator */}
                                        <div className="border-t border-border/30 my-6" />

                                        {/* Kehadiran or Status */}
                                        {dayAttendance && dayAttendance.check_in_time ? (
                                            <div className="space-y-3">
                                                <h4 className="font-bold text-base">Kehadiran</h4>
                                                <div className="space-y-2.5">
                                                    {/* Jam Masuk Actual */}
                                                    <div className="bg-gradient-to-br from-rose-500 to-rose-600 rounded-2xl px-4 py-3.5 shadow-lg">
                                                        <div className="flex items-center justify-between text-white">
                                                            <div className="flex items-center gap-3">
                                                                <div className="bg-white/20 rounded-xl p-2">
                                                                    <Clock className="h-4 w-4" />
                                                                </div>
                                                                <span className="font-medium text-sm">Jam masuk</span>
                                                            </div>
                                                            <span className="text-base font-semibold">
                                                                {checkInTimeFormatted}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    {/* Jam Keluar Actual */}
                                                    <div className="bg-gradient-to-br from-rose-500 to-rose-600 rounded-2xl px-4 py-3.5 shadow-lg">
                                                        <div className="flex items-center justify-between text-white">
                                                            <div className="flex items-center gap-3">
                                                                <div className="bg-white/20 rounded-xl p-2">
                                                                    <Clock className="h-4 w-4" />
                                                                </div>
                                                                <span className="font-medium text-sm">Jam keluar</span>
                                                            </div>
                                                            <span className="text-base font-semibold">
                                                                {checkOutTimeFormatted}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ) : (
                                            /* Status: Belum Absen */
                                            <div className="flex justify-center">
                                                <button className="bg-gradient-to-br from-rose-500 to-rose-600 text-white px-8 py-3 rounded-2xl font-semibold shadow-lg">
                                                    Status : Belum Absen
                                                </button>
                                            </div>
                                        )}
                                    </>
                                );
                            })()}
                        </div>
                    </div>
                </div>
            )}

            {/* Month Picker Modal */}
            {showMonthPicker && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                    <div className="bg-card rounded-2xl shadow-2xl border border-border/50 w-full max-w-sm">
                        {/* Modal Header */}
                        <div className="px-5 py-4 border-b border-border/50">
                            <h3 className="text-lg font-bold">Pilih Bulan & Tahun</h3>
                        </div>

                        {/* Year Selector */}
                        <div className="px-5 py-4 border-b border-border/50">
                            <div className="flex items-center justify-between">
                                <button
                                    onClick={() => setSelectedMonth(subMonths(selectedMonth, 12))}
                                    className="p-2 hover:bg-muted rounded-lg transition-colors"
                                    aria-label="Tahun Sebelumnya"
                                >
                                    <ChevronLeft className="h-5 w-5" />
                                </button>
                                <span className="text-lg font-semibold">
                                    {format(selectedMonth, 'yyyy')}
                                </span>
                                <button
                                    onClick={() => setSelectedMonth(addMonths(selectedMonth, 12))}
                                    className="p-2 hover:bg-muted rounded-lg transition-colors"
                                    aria-label="Tahun Berikutnya"
                                >
                                    <ChevronRight className="h-5 w-5" />
                                </button>
                            </div>
                        </div>

                        {/* Month Grid */}
                        <div className="px-5 py-4">
                            <div className="grid grid-cols-3 gap-2">
                                {['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'].map((month, idx) => {
                                    const monthDate = new Date(selectedMonth.getFullYear(), idx, 1);
                                    const isSelected = selectedMonth.getMonth() === idx;

                                    return (
                                        <button
                                            key={month}
                                            onClick={() => {
                                                setSelectedMonth(monthDate);
                                                setShowMonthPicker(false);
                                            }}
                                            className={cn(
                                                'py-3 px-4 rounded-xl font-medium transition-all',
                                                isSelected
                                                    ? 'bg-primary text-primary-foreground shadow-md'
                                                    : 'bg-muted/30 hover:bg-muted'
                                            )}
                                        >
                                            {month}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Modal Actions */}
                        <div className="px-5 py-4 border-t border-border/50 flex gap-3">
                            <button
                                onClick={() => setShowMonthPicker(false)}
                                className="flex-1 py-2.5 px-4 rounded-xl bg-muted hover:bg-muted/80 font-medium transition-colors"
                            >
                                Batal
                            </button>
                            <button
                                onClick={() => {
                                    setSelectedMonth(new Date());
                                    setShowMonthPicker(false);
                                }}
                                className="flex-1 py-2.5 px-4 rounded-xl bg-primary text-primary-foreground hover:bg-primary/90 font-medium transition-colors"
                            >
                                Bulan Ini
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
