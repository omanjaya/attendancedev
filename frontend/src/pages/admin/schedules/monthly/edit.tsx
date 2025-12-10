import { useState, useEffect } from 'react';
import { Link, useNavigate, useParams } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  ArrowLeft,
  Clock,
  Loader2,
  Save,
  CalendarDays,
  Briefcase,
  CheckCircle2,
  Wand2,
  RotateCcw,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

import { useNotificationStore } from '@/stores';
import {
  getMonthlyAttendanceSchedule,
  updateMonthlyAttendanceSchedule,
  type MonthlyAttendanceScheduleFormData,
} from '@/lib/api/schedules';
import { getHolidaysByMonth } from '@/lib/api/holidays';

const monthlyScheduleSchema = z.object({
  name: z.string().min(2, 'Nama jadwal minimal 2 karakter'),
  month: z.number().min(1).max(12),
  year: z.number().min(2024).max(2030),
  default_start_time: z.string().min(1, 'Pilih jam kerja awal'),
  default_end_time: z.string().min(1, 'Pilih jam kerja akhir'),
  checkin_start_time: z.string().min(1, 'Pilih jam absen masuk awal'),
  checkin_end_time: z.string().min(1, 'Pilih jam absen masuk akhir'),
  checkout_start_time: z.string().min(1, 'Pilih jam absen pulang awal'),
  checkout_end_time: z.string().min(1, 'Pilih jam absen pulang akhir'),
  working_days: z.array(z.string()).min(1, 'Pilih minimal 1 hari kerja'),
  is_active: z.boolean(),
  description: z.string().optional(),
});

type MonthlyScheduleForm = z.infer<typeof monthlyScheduleSchema>;

const months = [
  { value: 1, label: 'Januari' },
  { value: 2, label: 'Februari' },
  { value: 3, label: 'Maret' },
  { value: 4, label: 'April' },
  { value: 5, label: 'Mei' },
  { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' },
  { value: 8, label: 'Agustus' },
  { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' },
  { value: 11, label: 'November' },
  { value: 12, label: 'Desember' },
];

const years = Array.from({ length: 7 }, (_, i) => new Date().getFullYear() + i);


const TIME_PRESETS = [
  {
    label: 'Normal',
    description: '08:00 - 17:00',
    values: {
      default_start_time: '08:00',
      default_end_time: '17:00',
      checkin_start_time: '07:00',
      checkin_end_time: '09:00',
      checkout_start_time: '16:00',
      checkout_end_time: '18:00',
    }
  },
  {
    label: 'Pagi',
    description: '07:00 - 15:00',
    values: {
      default_start_time: '07:00',
      default_end_time: '15:00',
      checkin_start_time: '06:00',
      checkin_end_time: '08:00',
      checkout_start_time: '14:00',
      checkout_end_time: '16:00',
    }
  },
  {
    label: 'Siang',
    description: '14:00 - 22:00',
    values: {
      default_start_time: '14:00',
      default_end_time: '22:00',
      checkin_start_time: '13:00',
      checkin_end_time: '15:00',
      checkout_start_time: '21:00',
      checkout_end_time: '23:00',
    }
  },
];

export default function MonthlyScheduleEditPage() {
  const { id } = useParams({ strict: false });
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();
  const [generatedWorkingDays, setGeneratedWorkingDays] = useState<string[]>([]);
  const [holidays, setHolidays] = useState<string[]>([]);
  const [isFormReady, setIsFormReady] = useState(false);

  // Fetch existing schedule
  const { data: schedule, isLoading: isLoadingSchedule } = useQuery({
    queryKey: ['monthly-schedule', id],
    queryFn: () => getMonthlyAttendanceSchedule(id!),
  });

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
    reset,
  } = useForm<MonthlyScheduleForm>({
    resolver: zodResolver(monthlyScheduleSchema),
    defaultValues: {
      name: '',
      month: undefined as unknown as number,
      year: undefined as unknown as number,
      default_start_time: '',
      default_end_time: '',
      checkin_start_time: '',
      checkin_end_time: '',
      checkout_start_time: '',
      checkout_end_time: '',
      working_days: [],
      is_active: true,
      description: '',
    },
  });

  const selectedMonth = watch('month');
  const selectedYear = watch('year');
  const workingDays = watch('working_days');

  // Pre-fill form when schedule data is loaded
  useEffect(() => {
    if (schedule && !isFormReady) {
      // Reset form with schedule data
      reset({
        name: schedule.name,
        month: schedule.month,
        year: schedule.year,
        default_start_time: schedule.default_start_time,
        default_end_time: schedule.default_end_time,
        checkin_start_time: schedule.checkin_start_time,
        checkin_end_time: schedule.checkin_end_time,
        checkout_start_time: schedule.checkout_start_time,
        checkout_end_time: schedule.checkout_end_time,
        working_days: schedule.working_days,
        is_active: schedule.is_active,
        description: schedule.description || '',
      });

      // Explicitly set month and year to ensure Select components update
      setValue('month', schedule.month, { shouldValidate: true });
      setValue('year', schedule.year, { shouldValidate: true });

      setGeneratedWorkingDays(schedule.working_days);
      setIsFormReady(true);
    }
  }, [schedule, reset, setValue, isFormReady]);

  // Fetch holidays for selected month
  useEffect(() => {
    if (selectedMonth && selectedYear) {
      getHolidaysByMonth(selectedMonth, selectedYear)
        .then((data) => {
          // Parse holiday dates - handle both single date and date ranges
          const holidayDates: string[] = [];
          data.holidays.forEach(h => {
            // Parse start date (handle timezone by extracting date part)
            const startDate = h.date.split('T')[0];
            holidayDates.push(startDate);
            
            // If has end_date, add all dates in range
            if (h.end_date) {
              const endDate = h.end_date.split('T')[0];
              const start = new Date(startDate);
              const end = new Date(endDate);
              
              // Add all dates between start and end
              const current = new Date(start);
              current.setDate(current.getDate() + 1); // Start from day after
              while (current <= end) {
                const dateStr = [
                  current.getFullYear(),
                  String(current.getMonth() + 1).padStart(2, '0'),
                  String(current.getDate()).padStart(2, '0')
                ].join('-');
                if (!holidayDates.includes(dateStr)) {
                  holidayDates.push(dateStr);
                }
                current.setDate(current.getDate() + 1);
              }
            }
          });
          setHolidays(holidayDates);
        })
        .catch((err) => {
          console.error('Failed to fetch holidays:', err);
          setHolidays([]);
        });
    }
  }, [selectedMonth, selectedYear]);

  // Generate working days (Mon-Fri) excluding holidays
  const generateWorkingDays = () => {
    if (!selectedMonth || !selectedYear) return;

    const workingDaysList: string[] = [];
    const lastDay = new Date(selectedYear, selectedMonth, 0);

    for (let day = 1; day <= lastDay.getDate(); day++) {
      const date = new Date(selectedYear, selectedMonth - 1, day);
      const dayOfWeek = date.getDay(); // 0 = Sunday, 6 = Saturday
      
      // Skip weekends (Saturday = 6, Sunday = 0)
      if (dayOfWeek === 0 || dayOfWeek === 6) continue;

      const dateStr = [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0')
      ].join('-');

      // Skip holidays
      if (holidays.includes(dateStr)) continue;

      workingDaysList.push(dateStr);
    }

    setValue('working_days', workingDaysList);
    setGeneratedWorkingDays(workingDaysList);
    success('Hari Kerja Digenerate', `${workingDaysList.length} hari kerja (Senin-Jumat, exclude libur)`);
  };

  // Clear all working days
  const clearWorkingDays = () => {
    setValue('working_days', []);
    setGeneratedWorkingDays([]);
  };

  const toggleWorkingDay = (date: string) => {
    const currentDays = watch('working_days') || [];
    const newDays = currentDays.includes(date)
      ? currentDays.filter(d => d !== date)
      : [...currentDays, date];
    setValue('working_days', newDays);
    setGeneratedWorkingDays(newDays);
  };

  const updateScheduleMutation = useMutation({
    mutationFn: (data: Partial<MonthlyAttendanceScheduleFormData>) =>
      updateMonthlyAttendanceSchedule(id!, data),
    onSuccess: async () => {
      // Await query invalidation to ensure data is refreshed before navigation
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ['monthly-schedules'] }),
        queryClient.invalidateQueries({ queryKey: ['monthly-schedule', id] }),
      ]);
      success('Berhasil', 'Jadwal bulanan berhasil diupdate');
      navigate({ to: '/admin/schedules/monthly' });
    },
    // onError removed - global error handler will catch it
  });

  const onSubmit = async (data: MonthlyScheduleForm) => {
    updateScheduleMutation.mutate(data);
  };

  const applyPreset = (preset: typeof TIME_PRESETS[0]) => {
    Object.entries(preset.values).forEach(([key, value]) => {
      setValue(key as any, value);
    });
    success('Preset diterapkan', `Waktu diatur ke ${preset.label}`);
  };

  // Generate calendar grid for working days selection
  const generateCalendarDays = () => {
    if (!selectedMonth || !selectedYear) return [];

    const firstDay = new Date(selectedYear, selectedMonth - 1, 1);
    const lastDay = new Date(selectedYear, selectedMonth, 0);
    const days = [];

    // Get day of week for first day of month
    const startDayOfWeek = firstDay.getDay(); // 0 for Sunday, 1 for Monday, ..., 6 for Saturday
    // Adjust to make Monday the first day (0 for Monday, ..., 6 for Sunday)
    const adjustedStartDayOfWeek = (startDayOfWeek === 0) ? 6 : startDayOfWeek - 1;

    // Add empty cells for days before month starts
    for (let i = 0; i < adjustedStartDayOfWeek; i++) {
      days.push(null);
    }

    // Add days of month
    for (let day = 1; day <= lastDay.getDate(); day++) {
      const date = new Date(selectedYear, selectedMonth - 1, day);
      const dayOfWeek = date.getDay(); // 0 = Sunday, 6 = Saturday
      const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
      
      // Use local date string to match backend format and avoid timezone shifts
      const dateStr = [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0')
      ].join('-');
      days.push({
        day,
        date: dateStr,
        isHoliday: holidays.includes(dateStr),
        isWeekend,
        isSelected: generatedWorkingDays.includes(dateStr),
      });
    }

    return days;
  };

  if (isLoadingSchedule || !isFormReady) {
    return (
      <div className="p-4 sm:p-6 flex items-center justify-center min-h-screen">
        <div className="text-center">
          <Loader2 className="h-8 w-8 animate-spin text-primary mx-auto mb-4" />
          <p className="text-muted-foreground">Memuat data jadwal...</p>
        </div>
      </div>
    );
  }

  if (!schedule) {
    return (
      <div className="p-4 sm:p-6 flex items-center justify-center min-h-screen">
        <div className="text-center">
          <CalendarDays className="h-16 w-16 text-muted-foreground/40 mx-auto mb-4" />
          <h2 className="text-xl font-semibold text-foreground mb-2">Jadwal Tidak Ditemukan</h2>
          <p className="text-sm text-muted-foreground mb-6">
            Jadwal yang Anda cari tidak tersedia atau telah dihapus
          </p>
          <Link to="/admin/schedules/monthly">
            <Button>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Kembali ke Daftar Jadwal
            </Button>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="p-4 sm:p-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <Link
            to="/admin/schedules/monthly"
            className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-2"
          >
            <ArrowLeft className="h-4 w-4 mr-2" />
            Kembali ke daftar jadwal
          </Link>
          <h1 className="text-2xl font-bold text-foreground">Edit Jadwal Bulanan</h1>
          <p className="text-sm text-muted-foreground">
            Update jadwal absensi bulanan: {schedule.name}
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Button
            type="button"
            variant="outline"
            onClick={() => navigate({ to: '/admin/schedules/monthly' })}
          >
            Batal
          </Button>
          <Button
            onClick={handleSubmit(onSubmit)}
            disabled={updateScheduleMutation.isPending}
          >
            {updateScheduleMutation.isPending ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Menyimpan...
              </>
            ) : (
              <>
                <Save className="mr-2 h-4 w-4" />
                Simpan Perubahan
              </>
            )}
          </Button>
        </div>
      </div>

      <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Left Column: Basic Info */}
          <div className="space-y-6">
            <Card className="h-full border-none shadow-md">
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg">
                  <Briefcase className="h-5 w-5 text-primary" />
                  Informasi Dasar
                </CardTitle>
                <CardDescription>
                  Detail identitas jadwal
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Nama Jadwal <span className="text-destructive">*</span>
                  </label>
                  <Input
                    placeholder="Contoh: Jadwal Februari 2025 - Kantor Pusat"
                    {...register('name')}
                  />
                  {errors.name && (
                    <p className="text-xs text-destructive">{errors.name.message}</p>
                  )}
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <label className="text-sm font-medium">
                      Bulan <span className="text-destructive">*</span>
                    </label>
                    <Select
                      value={selectedMonth?.toString() ?? ''}
                      onValueChange={(value) => setValue('month', parseInt(value))}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Pilih bulan" />
                      </SelectTrigger>
                      <SelectContent>
                        {months.map((month) => (
                          <SelectItem key={month.value} value={month.value.toString()}>
                            {month.label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    {errors.month && (
                      <p className="text-xs text-destructive">{errors.month.message}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <label className="text-sm font-medium">
                      Tahun <span className="text-destructive">*</span>
                    </label>
                    <Select
                      value={selectedYear?.toString() ?? ''}
                      onValueChange={(value) => setValue('year', parseInt(value))}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Pilih tahun" />
                      </SelectTrigger>
                      <SelectContent>
                        {years.map((year) => (
                          <SelectItem key={year} value={year.toString()}>
                            {year}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    {errors.year && (
                      <p className="text-xs text-destructive">{errors.year.message}</p>
                    )}
                  </div>
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium">Deskripsi</label>
                  <Textarea
                    placeholder="Deskripsi singkat tentang jadwal ini..."
                    {...register('description')}
                    className="min-h-[100px] resize-none"
                  />
                </div>

                <div className="flex items-center justify-between p-4 bg-muted/30 rounded-lg border">
                  <div className="space-y-0.5">
                    <Label htmlFor="is_active" className="text-sm font-medium">Status Jadwal</Label>
                    <p className="text-xs text-muted-foreground">
                      Aktifkan jadwal ini untuk digunakan oleh pegawai
                    </p>
                  </div>
                  <Switch
                    id="is_active"
                    checked={watch('is_active')}
                    onCheckedChange={(checked) => setValue('is_active', checked)}
                  />
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Right Column: Time Settings */}
          <div className="space-y-6">
            <Card className="h-full border-none shadow-md">
              <CardHeader className="pb-4">
                <div className="flex items-center justify-between">
                  <div className="space-y-1">
                    <CardTitle className="flex items-center gap-2 text-lg">
                      <Clock className="h-5 w-5 text-primary" />
                      Pengaturan Waktu
                    </CardTitle>
                    <CardDescription>
                      Jam kerja dan batasan absensi
                    </CardDescription>
                  </div>
                  <div className="flex gap-2">
                    {TIME_PRESETS.map((preset, idx) => (
                      <Button
                        key={idx}
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => applyPreset(preset)}
                        className="text-xs h-8"
                      >
                        {preset.label}
                      </Button>
                    ))}
                  </div>
                </div>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  {/* Jam Kerja - Full Width */}
                  <div className="sm:col-span-2 space-y-4 p-4 bg-muted/30 rounded-lg border">
                    <h3 className="text-sm font-semibold flex items-center gap-2">
                      <Briefcase className="h-4 w-4 text-primary" />
                      Jam Kerja Utama
                    </h3>
                    <div className="grid grid-cols-2 gap-4">
                      <div className="space-y-1.5">
                        <label className="text-xs font-medium text-muted-foreground">Jam Masuk</label>
                        <Input type="time" step="60" {...register('default_start_time')} className="bg-background" />
                        {errors.default_start_time && <p className="text-xs text-destructive">{errors.default_start_time.message}</p>}
                      </div>
                      <div className="space-y-1.5">
                        <label className="text-xs font-medium text-muted-foreground">Jam Pulang</label>
                        <Input type="time" step="60" {...register('default_end_time')} className="bg-background" />
                        {errors.default_end_time && <p className="text-xs text-destructive">{errors.default_end_time.message}</p>}
                      </div>
                    </div>
                  </div>

                  {/* Window Check-In */}
                  <div className="space-y-4 p-4 bg-muted/30 rounded-lg border">
                    <h3 className="text-sm font-semibold flex items-center gap-2">
                      <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                      Batas Absen Masuk
                    </h3>
                    <div className="space-y-3">
                      <div className="space-y-1.5">
                        <label className="text-xs font-medium text-muted-foreground">Mulai Absen</label>
                        <Input type="time" step="60" {...register('checkin_start_time')} className="bg-background" />
                        {errors.checkin_start_time && <p className="text-xs text-destructive">{errors.checkin_start_time.message}</p>}
                      </div>
                      <div className="space-y-1.5">
                        <label className="text-xs font-medium text-muted-foreground">Batas Terlambat</label>
                        <Input type="time" step="60" {...register('checkin_end_time')} className="bg-background" />
                        {errors.checkin_end_time && <p className="text-xs text-destructive">{errors.checkin_end_time.message}</p>}
                      </div>
                    </div>
                  </div>

                  {/* Window Check-Out */}
                  <div className="space-y-4 p-4 bg-muted/30 rounded-lg border">
                    <h3 className="text-sm font-semibold flex items-center gap-2">
                      <CheckCircle2 className="h-4 w-4 text-orange-600" />
                      Batas Absen Pulang
                    </h3>
                    <div className="space-y-3">
                      <div className="space-y-1.5">
                        <label className="text-xs font-medium text-muted-foreground">Mulai Absen</label>
                        <Input type="time" step="60" {...register('checkout_start_time')} className="bg-background" />
                        {errors.checkout_start_time && <p className="text-xs text-destructive">{errors.checkout_start_time.message}</p>}
                      </div>
                      <div className="space-y-1.5">
                        <label className="text-xs font-medium text-muted-foreground">Batas Akhir</label>
                        <Input type="time" step="60" {...register('checkout_end_time')} className="bg-background" />
                        {errors.checkout_end_time && <p className="text-xs text-destructive">{errors.checkout_end_time.message}</p>}
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>

        {/* Bottom Section: Working Days */}
        <Card className="border-none shadow-md">
          <CardHeader>
            <div className="flex items-center justify-between">
              <div className="space-y-1">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <CalendarDays className="h-5 w-5 text-primary" />
                  Kalender Hari Kerja
                </CardTitle>
                <CardDescription>
                  Pilih hari kerja dalam sebulan
                </CardDescription>
              </div>
              <div className="flex items-center gap-2">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={generateWorkingDays}
                    className="h-8"
                  >
                    <Wand2 className="h-4 w-4 mr-1" />
                    Generate Senin-Jumat
                  </Button>
                  {workingDays && workingDays.length > 0 && (
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      onClick={clearWorkingDays}
                      className="h-8 text-muted-foreground"
                    >
                      <RotateCcw className="h-4 w-4 mr-1" />
                      Reset
                    </Button>
                  )}
                </div>
            </div>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div className="grid grid-cols-7 gap-1 sm:gap-4">
                {['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'].map(d => (
                  <div key={d} className="text-center text-xs font-semibold text-muted-foreground uppercase tracking-wider py-2">
                    {d}
                  </div>
                ))}

                {generateCalendarDays().map((day, idx) => (
                  day === null ? (
                    <div key={`empty-${idx}`} className="aspect-square bg-muted/5 rounded-lg" />
                  ) : (
                    <button
                      key={day.date}
                      type="button"
                      onClick={() => !day.isHoliday && !day.isWeekend && toggleWorkingDay(day.date)}
                      disabled={day.isHoliday || day.isWeekend}
                      className={cn(
                        "aspect-square rounded-lg flex flex-col items-center justify-center text-sm transition-all border relative group",
                        day.isHoliday
                          ? "bg-red-50 text-red-600 border-red-100 cursor-not-allowed"
                          : day.isWeekend
                            ? "bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed"
                            : day.isSelected
                              ? "bg-primary text-primary-foreground border-primary shadow-sm hover:bg-primary/90"
                              : "bg-card hover:bg-accent border-border text-foreground"
                      )}
                    >
                      <span className="font-semibold text-lg">{day.day}</span>
                      {day.isHoliday && (
                        <Badge variant="destructive" className="absolute bottom-2 text-[10px] px-1.5 py-0 h-4">
                          Libur
                        </Badge>
                      )}
                      {day.isWeekend && !day.isHoliday && (
                        <span className="text-[10px] opacity-60 mt-1">Weekend</span>
                      )}
                      {!day.isHoliday && !day.isWeekend && day.isSelected && (
                        <span className="text-[10px] opacity-80 mt-1">Kerja</span>
                      )}
                    </button>
                  )
                ))}
              </div>

              <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pt-4 text-xs text-muted-foreground border-t">
                <div>
                  Total Hari Kerja: <span className="font-medium text-foreground">{workingDays?.length || 0} hari</span>
                  {holidays.length > 0 && (
                    <span className="ml-2">• {holidays.length} hari libur</span>
                  )}
                </div>
                <div className="flex flex-wrap gap-3">
                  <div className="flex items-center gap-1.5">
                    <div className="w-3 h-3 rounded bg-primary" />
                    <span>Hari Kerja</span>
                  </div>
                  <div className="flex items-center gap-1.5">
                    <div className="w-3 h-3 rounded bg-red-100 border border-red-200" />
                    <span>Libur Nasional</span>
                  </div>
                  <div className="flex items-center gap-1.5">
                    <div className="w-3 h-3 rounded bg-gray-100 border border-gray-200" />
                    <span>Weekend</span>
                  </div>
                  <div className="flex items-center gap-1.5">
                    <div className="w-3 h-3 rounded bg-card border" />
                    <span>Tersedia</span>
                  </div>
                </div>
              </div>
              {errors.working_days && (
                <p className="text-xs text-destructive mt-2">{errors.working_days.message}</p>
              )}
            </div>
          </CardContent>
        </Card>

        <div className="flex items-center justify-end pt-4">
          <Button
            type="submit"
            size="lg"
            className="w-full sm:w-auto"
            disabled={updateScheduleMutation.isPending}
          >
            {updateScheduleMutation.isPending ? (
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            ) : (
              <Save className="mr-2 h-4 w-4" />
            )}
            Simpan Perubahan
          </Button>
        </div>
      </form>
    </div>
  );
}
