import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
    Calendar,
    ChevronLeft,
    ChevronRight,
    Check,
    Minus,
    Clock,
    MapPin,
    X,
} from 'lucide-react';
import { useAuthStore } from '@/stores';
import { useNavigate } from '@tanstack/react-router';
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
} from 'date-fns';
import { id } from 'date-fns/locale';
import { cn } from '@/lib/utils';
import { LoadingState } from '@/components/states';
import { getAttendance } from '@/lib/api/attendance';
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
    const navigate = useNavigate();
    const [selectedMonth, setSelectedMonth] = useState(new Date());
    const [showMonthPicker, setShowMonthPicker] = useState(false);
    const [selectedDate, setSelectedDate] = useState<Date | null>(null);
    const [showScheduleModal, setShowScheduleModal] = useState(false);

    // Fetch employee's attendance data for the calendar
    const { data: attendanceData, isLoading } = useQuery({
        queryKey: ['employee', 'schedule-calendar', user?.id, format(selectedMonth, 'yyyy-MM')],
        queryFn: async () => {
            const monthStart = startOfMonth(selectedMonth);
            const monthEnd = endOfMonth(selectedMonth);

            // Fetch attendance records for the month
            const response = await getAttendance({
                date_from: format(monthStart, 'yyyy-MM-dd'),
                date_to: format(monthEnd, 'yyyy-MM-dd'),
                employee_id: user?.employee_id,
            });

            // Transform API response to calendar format
            const attendanceMap = new Map<string, AttendanceDay>();

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

                attendanceMap.set(date, { date, status });
            });

            // Convert map to array
            const attendance = Array.from(attendanceMap.values());

            // Rahina events (Balinese calendar) - can be fetched from API or hardcoded
            const rahina: RahinaEvent[] = [];

            return { attendance, rahina };
        },
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
        <div className="min-h-screen bg-gradient-to-b from-muted/20 via-background to-background pb-20">
            {/* Header */}
            <div className="sticky top-0 z-10 bg-background/95 backdrop-blur-lg border-b border-border/50 shadow-sm">
                <div className="flex items-center justify-center py-4 relative px-4">
                    <button
                        onClick={() => navigate({ to: '/employee/attendance' })}
                        className="absolute left-4 p-2 hover:bg-muted rounded-xl transition-all active:scale-95"
                        aria-label="Kembali"
                    >
                        <ChevronLeft className="h-5 w-5" />
                    </button>
                    <h1 className="text-lg font-bold">Jadwal Saya</h1>
                </div>
            </div>

            {/* Content */}
            <div className="px-4 py-5 space-y-5">
                {/* Month Selector - Symmetrical */}
                <div className="flex items-center justify-center gap-3">
                    <button
                        onClick={handlePreviousMonth}
                        className="bg-card border border-border/50 rounded-2xl p-3.5 hover:bg-muted/50 transition-all active:scale-95 shadow-sm w-12 h-12 flex items-center justify-center"
                        aria-label="Bulan Sebelumnya"
                    >
                        <ChevronLeft className="h-5 w-5" />
                    </button>
                    <button
                        onClick={() => setShowMonthPicker(true)}
                        className="flex-1 bg-card border border-border/50 rounded-2xl px-6 py-3.5 flex items-center justify-center gap-2 shadow-sm hover:bg-muted/50 hover:border-border transition-all active:scale-[0.98] cursor-pointer max-w-xs"
                    >
                        <span className="text-base font-semibold capitalize">
                            {format(selectedMonth, 'MMMM yyyy', { locale: id })}
                        </span>
                        <Calendar className="h-5 w-5 text-muted-foreground" />
                    </button>
                    <button
                        onClick={handleNextMonth}
                        className="bg-card border border-border/50 rounded-2xl p-3.5 hover:bg-muted/50 transition-all active:scale-95 shadow-sm w-12 h-12 flex items-center justify-center"
                        aria-label="Bulan Berikutnya"
                    >
                        <ChevronRight className="h-5 w-5" />
                    </button>
                </div>

                {/* Employee Info */}
                <div className="bg-card border border-border/50 rounded-2xl px-5 py-4 shadow-sm">
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
                <div className="bg-card border border-border/50 rounded-2xl px-4 py-5 shadow-sm">
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
                                    {dayAttendance && (
                                        <div
                                            className={cn(
                                                'rounded-full p-1.5 flex items-center justify-center shadow-md',
                                                getAttendanceColor(dayAttendance.status)
                                            )}
                                        >
                                            {getAttendanceIcon(dayAttendance.status)}
                                        </div>
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* Legend */}
                <div className="bg-card border border-border/50 rounded-2xl px-4 py-3 shadow-sm">
                    <div className="grid grid-cols-2 gap-2 text-xs">
                        <div className="flex items-center gap-2">
                            <div className="bg-sky-400 rounded-full p-1 shadow-sm">
                                <Check className="h-3 w-3 text-white" strokeWidth={2.5} />
                            </div>
                            <span className="text-muted-foreground">Cuti/Ijin/Sakit</span>
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
                    <h2 className="text-base font-bold px-1">Daftar Rahina</h2>
                    <div className="space-y-2.5">
                        {attendanceData?.rahina.map((event, idx) => (
                            <div
                                key={idx}
                                className="flex items-center gap-3.5 bg-card border border-border/50 rounded-2xl px-4 py-3.5 shadow-sm hover:shadow-md transition-shadow"
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
                                        {format(parseISO(event.date), 'dd MMM yyyy', { locale: id })} - {event.name}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Schedule Detail Modal */}
            {showScheduleModal && selectedDate && (
                <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/50 backdrop-blur-sm">
                    <div className="bg-card rounded-t-3xl shadow-2xl border-t border-border/50 w-full max-h-[80vh] overflow-y-auto animate-in slide-in-from-bottom">
                        {/* Modal Header */}
                        <div className="sticky top-0 bg-card/95 backdrop-blur-md px-5 py-4 border-b border-border/50 flex items-center justify-between">
                            <div>
                                <h3 className="text-lg font-bold">Detail Jadwal</h3>
                                <p className="text-sm text-muted-foreground">
                                    {format(selectedDate, 'EEEE, dd MMMM yyyy', { locale: id })}
                                </p>
                            </div>
                            <button
                                onClick={() => setShowScheduleModal(false)}
                                className="p-2 hover:bg-muted rounded-xl transition-colors"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>

                        {/* Modal Content */}
                        <div className="px-5 py-6 space-y-6">
                            {(() => {
                                const dayAttendance = attendanceData?.attendance.find((a) =>
                                    isSameDay(parseISO(a.date), selectedDate)
                                );

                                if (!dayAttendance) {
                                    return (
                                        <div className="text-center py-12">
                                            <div className="bg-muted/30 rounded-full p-4 w-fit mx-auto mb-4">
                                                <Calendar className="h-12 w-12 text-muted-foreground" />
                                            </div>
                                            <p className="text-sm font-semibold mb-1">Tidak Ada Jadwal</p>
                                            <p className="text-xs text-muted-foreground">
                                                Anda libur pada tanggal ini
                                            </p>
                                        </div>
                                    );
                                }

                                const statusLabels: Record<string, string> = {
                                    'leave': 'Cuti/Ijin/Sakit',
                                    'late': 'Hadir Terlambat',
                                    'on-time': 'Hadir Tepat Waktu',
                                    'absent': 'Alpha',
                                    'holiday': 'Hari Libur'
                                };

                                return (
                                    <>
                                        {/* Status Badge */}
                                        <div className="flex items-center justify-center">
                                            <div className={cn(
                                                'rounded-full p-3 mb-2',
                                                getAttendanceColor(dayAttendance.status)
                                            )}>
                                                <div className="scale-150">
                                                    {getAttendanceIcon(dayAttendance.status)}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="text-center">
                                            <h4 className="text-xl font-bold mb-1">
                                                {statusLabels[dayAttendance.status] || 'Status Tidak Diketahui'}
                                            </h4>
                                            <p className="text-sm text-muted-foreground">
                                                Status kehadiran Anda
                                            </p>
                                        </div>

                                        {/* Work Schedule Card */}
                                        <div className="bg-muted/30 rounded-2xl p-5 space-y-4">
                                            <h5 className="font-semibold text-sm">Jam Kerja</h5>

                                            {/* Working Hours */}
                                            <div className="flex items-start gap-3">
                                                <div className="bg-card rounded-xl p-2.5">
                                                    <Clock className="h-5 w-5 text-primary" />
                                                </div>
                                                <div className="flex-1">
                                                    <p className="text-xs text-muted-foreground">Waktu Kerja</p>
                                                    <p className="text-sm font-semibold">
                                                        08:00 - 17:00 WIB
                                                    </p>
                                                    <p className="text-xs text-muted-foreground mt-1">
                                                        Total: 8 jam kerja
                                                    </p>
                                                </div>
                                            </div>

                                            {/* Location */}
                                            <div className="flex items-start gap-3">
                                                <div className="bg-card rounded-xl p-2.5">
                                                    <MapPin className="h-5 w-5 text-emerald-600" />
                                                </div>
                                                <div className="flex-1">
                                                    <p className="text-xs text-muted-foreground">Lokasi Kerja</p>
                                                    <p className="text-sm font-semibold">
                                                        Kantor Pusat
                                                    </p>
                                                    <p className="text-xs text-muted-foreground mt-1">
                                                        Jl. Raya Ubud, Bali
                                                    </p>
                                                </div>
                                            </div>

                                            {/* Shift Info */}
                                            {dayAttendance.status !== 'holiday' && dayAttendance.status !== 'leave' && (
                                                <div className="pt-3 border-t border-border/50">
                                                    <div className="flex items-center justify-between text-xs">
                                                        <span className="text-muted-foreground">Shift</span>
                                                        <span className="font-semibold">Shift Pagi</span>
                                                    </div>
                                                </div>
                                            )}
                                        </div>

                                        {/* Additional Info */}
                                        {dayAttendance.status === 'late' && (
                                            <div className="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-2xl p-4">
                                                <p className="text-sm font-semibold text-amber-900 dark:text-amber-100 mb-1">
                                                    Keterlambatan
                                                </p>
                                                <p className="text-xs text-amber-700 dark:text-amber-300">
                                                    Anda terlambat 15 menit pada hari ini
                                                </p>
                                            </div>
                                        )}
                                    </>
                                );
                            })()}
                        </div>

                        {/* Modal Actions */}
                        <div className="sticky bottom-0 bg-card/95 backdrop-blur-md px-5 py-4 border-t border-border/50">
                            <button
                                onClick={() => setShowScheduleModal(false)}
                                className="w-full py-3 px-4 rounded-xl bg-primary text-primary-foreground hover:bg-primary/90 font-medium transition-colors"
                            >
                                Tutup
                            </button>
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
                                >
                                    <ChevronLeft className="h-5 w-5" />
                                </button>
                                <span className="text-lg font-semibold">
                                    {format(selectedMonth, 'yyyy')}
                                </span>
                                <button
                                    onClick={() => setSelectedMonth(addMonths(selectedMonth, 12))}
                                    className="p-2 hover:bg-muted rounded-lg transition-colors"
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
