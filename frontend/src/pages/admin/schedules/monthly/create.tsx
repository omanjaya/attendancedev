import { useState, useEffect } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import {
  ArrowLeft,
  Clock,
  Loader2,
  Save,
  CalendarDays,
  Sparkles,
  Briefcase,
  CheckCircle2,
} from 'lucide-react';

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
import { Badge } from '@/components/ui/badge';
import { useNotificationStore } from '@/stores';
import {
  createMonthlyAttendanceSchedule,
  generateWorkingDays,
  type MonthlyAttendanceScheduleFormData,
  type GenerateWorkingDaysParams,
} from '@/lib/api/schedules';
import { getHolidaysByMonth } from '@/lib/api/holidays';
import { cn } from '@/lib/utils';

const monthlyScheduleSchema = z.object({
  name: z.string().min(2, 'Nama jadwal minimal 2 karakter'),
  month: z.number().min(1).max(12),
  year: z.number().min(2024).max(2030),
  location_id: z.string().optional(), // Optional - handled by backend default
  default_start_time: z.string().min(1, 'Pilih jam kerja awal'),
  default_end_time: z.string().min(1, 'Pilih jam kerja akhir'),
  checkin_start_time: z.string().min(1, 'Pilih jam absen masuk awal'),
  checkin_end_time: z.string().min(1, 'Pilih jam absen masuk akhir'),
  checkout_start_time: z.string().min(1, 'Pilih jam absen pulang awal'),
  checkout_end_time: z.string().min(1, 'Pilih jam absen pulang akhir'),
  working_days: z.array(z.string()).min(1, 'Pilih minimal 1 hari kerja'),
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

const daysOfWeek = [
  { id: 'monday', label: 'Senin' },
  { id: 'tuesday', label: 'Selasa' },
  { id: 'wednesday', label: 'Rabu' },
  { id: 'thursday', label: 'Kamis' },
  { id: 'friday', label: 'Jumat' },
  { id: 'saturday', label: 'Sabtu' },
  { id: 'sunday', label: 'Minggu' },
];

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
  }
];

export default function MonthlyScheduleCreatePage() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { success, error: showError } = useNotificationStore();

  const [selectedDayPattern, setSelectedDayPattern] = useState(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
  const [generatedWorkingDays, setGeneratedWorkingDays] = useState<string[]>([]);
  const [isGeneratingDays, setIsGeneratingDays] = useState(false);
  const [holidays, setHolidays] = useState<string[]>([]);

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
  } = useForm<MonthlyScheduleForm>({
    resolver: zodResolver(monthlyScheduleSchema),
    defaultValues: {
      month: new Date().getMonth() + 1,
      year: new Date().getFullYear(),
      default_start_time: '08:00',
      default_end_time: '17:00',
      checkin_start_time: '07:00',
      checkin_end_time: '09:00',
      checkout_start_time: '16:00',
      checkout_end_time: '18:00',
      working_days: [],
    },
  });

  const selectedMonth = watch('month');
  const selectedYear = watch('year');
  const workingDays = watch('working_days');

  // Fetch holidays for selected month
  useEffect(() => {
    if (selectedMonth && selectedYear) {
      getHolidaysByMonth(selectedMonth, selectedYear)
        .then((data) => {
          setHolidays(data.holidays.map(h => h.date));
        })
        .catch((err) => {
          console.error('Failed to fetch holidays:', err);
        });
    }
  }, [selectedMonth, selectedYear]);

  // Auto-generate working days when month/year changes
  useEffect(() => {
    if (selectedMonth && selectedYear && selectedDayPattern.length > 0) {
      handleGenerateWorkingDays(true);
    }
  }, [selectedMonth, selectedYear]);

  const handleGenerateWorkingDays = async (silent = false) => {
    if (!selectedMonth || !selectedYear) return;

    setIsGeneratingDays(true);
    try {
      const params: GenerateWorkingDaysParams = {
        month: selectedMonth,
        year: selectedYear,
        working_day_pattern: selectedDayPattern,
      };

      const result = await generateWorkingDays(params);
      setGeneratedWorkingDays(result.working_days);
      setValue('working_days', result.working_days);

      if (!silent) {
        success(
          'Hari kerja berhasil dibuat',
          `${result.total_working_days} hari kerja (${result.total_holidays} hari libur dikecualikan)`
        );
      }
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal generate hari kerja';
      showError('Error', message);
    } finally {
      setIsGeneratingDays(false);
    }
  };

  const toggleDayPattern = (dayId: string) => {
    setSelectedDayPattern(prev =>
      prev.includes(dayId)
        ? prev.filter(d => d !== dayId)
        : [...prev, dayId]
    );
  };

  const toggleWorkingDay = (date: string) => {
    const currentDays = watch('working_days') || [];
    const newDays = currentDays.includes(date)
      ? currentDays.filter(d => d !== date)
      : [...currentDays, date];
    setValue('working_days', newDays);
    setGeneratedWorkingDays(newDays);
  };

  const applyPreset = (preset: typeof TIME_PRESETS[0]) => {
    Object.entries(preset.values).forEach(([key, value]) => {
      setValue(key as any, value);
    });
    success('Preset diterapkan', `Waktu diatur ke ${preset.label}`);
  };

  const createScheduleMutation = useMutation({
    mutationFn: (data: MonthlyAttendanceScheduleFormData) => createMonthlyAttendanceSchedule(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['monthly-schedules'] });
      success('Berhasil', 'Jadwal bulanan berhasil dibuat');
      navigate({ to: '/admin/schedules/monthly' });
    },
    onError: (err: any) => {
      const message = err?.response?.data?.message || 'Gagal membuat jadwal bulanan';
      showError('Error', message);
    },
  });

  const onSubmit = async (data: MonthlyScheduleForm) => {
    createScheduleMutation.mutate(data);
  };

  // Generate calendar grid for working days selection
  const generateCalendarDays = () => {
    if (!selectedMonth || !selectedYear) return [];

    const firstDay = new Date(selectedYear, selectedMonth - 1, 1);
    const lastDay = new Date(selectedYear, selectedMonth, 0);
    const days = [];

    const startDayOfWeek = firstDay.getDay(); // 0 for Sunday, 1 for Monday, ..., 6 for Saturday
    // Adjust to make Monday the first day (0 for Monday, ..., 6 for Sunday)
    const adjustedStartDayOfWeek = (startDayOfWeek === 0) ? 6 : startDayOfWeek - 1;

    for (let i = 0; i < adjustedStartDayOfWeek; i++) {
      days.push(null);
    }

    for (let day = 1; day <= lastDay.getDate(); day++) {
      const date = new Date(selectedYear, selectedMonth - 1, day);
      const dateStr = date.toISOString().split('T')[0];
      days.push({
        day,
        date: dateStr,
        isHoliday: holidays.includes(dateStr),
        isSelected: generatedWorkingDays.includes(dateStr),
      });
    }

    return days;
  };

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
          <h1 className="text-2xl font-bold text-foreground">Buat Jadwal Bulanan</h1>
          <p className="text-sm text-muted-foreground">
            Lengkapi formulir di bawah ini untuk membuat jadwal absensi baru
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
            disabled={createScheduleMutation.isPending}
          >
            {createScheduleMutation.isPending ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Menyimpan...
              </>
            ) : (
              <>
                <Save className="mr-2 h-4 w-4" />
                Simpan Jadwal
              </>
            )}
          </Button>
        </div>
      </div>

      <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          {/* Left Column: Basic Info (4 cols) */}
          <div className="lg:col-span-4 space-y-6">
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
                    placeholder="Contoh: Jadwal Staff IT"
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
                      value={selectedMonth?.toString()}
                      onValueChange={(value) => setValue('month', parseInt(value))}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Bulan" />
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
                      value={selectedYear?.toString()}
                      onValueChange={(value) => setValue('year', parseInt(value))}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Tahun" />
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
                    placeholder="Keterangan tambahan..."
                    {...register('description')}
                    className="min-h-[120px] resize-none"
                  />
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Right Column: Time Settings (8 cols) */}
          <div className="lg:col-span-8 space-y-6">
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
                <div className="grid md:grid-cols-3 gap-6">
                  {/* Jam Kerja */}
                  <div className="space-y-4 p-4 bg-muted/30 rounded-lg border">
                    <h3 className="text-sm font-semibold flex items-center gap-2">
                      <Briefcase className="h-4 w-4 text-muted-foreground" />
                      Jam Kerja
                    </h3>
                    <div className="space-y-3">
                      <div className="space-y-1.5">
                        <label className="text-xs font-medium text-muted-foreground">Masuk</label>
                        <Input type="time" {...register('default_start_time')} className="bg-background" />
                        {errors.default_start_time && <p className="text-xs text-destructive">{errors.default_start_time.message}</p>}
                      </div>
                      <div className="space-y-1.5">
                        <label className="text-xs font-medium text-muted-foreground">Pulang</label>
                        <Input type="time" {...register('default_end_time')} className="bg-background" />
                        {errors.default_end_time && <p className="text-xs text-destructive">{errors.default_end_time.message}</p>}
                      </div>
                    </div>
                  </div>

                  {/* Window Check-In */}
                  <div className="space-y-4 p-4 bg-muted/30 rounded-lg border">
                    <h3 className="text-sm font-semibold flex items-center gap-2">
                      <CheckCircle2 className="h-4 w-4 text-muted-foreground" />
                      Batas Masuk
                    </h3>
                    <div className="space-y-3">
                      <div className="space-y-1.5">
                        <label className="text-xs font-medium text-muted-foreground">Mulai</label>
                        <Input type="time" {...register('checkin_start_time')} className="bg-background" />
                        {errors.checkin_start_time && <p className="text-xs text-destructive">{errors.checkin_start_time.message}</p>}
                      </div>
                      <div className="space-y-1.5">
                        <label className="text-xs font-medium text-muted-foreground">Sampai</label>
                        <Input type="time" {...register('checkin_end_time')} className="bg-background" />
                        {errors.checkin_end_time && <p className="text-xs text-destructive">{errors.checkin_end_time.message}</p>}
                      </div>
                    </div>
                  </div>

                  {/* Window Check-Out */}
                  <div className="space-y-4 p-4 bg-muted/30 rounded-lg border">
                    <h3 className="text-sm font-semibold flex items-center gap-2">
                      <CheckCircle2 className="h-4 w-4 text-muted-foreground" />
                      Batas Pulang
                    </h3>
                    <div className="space-y-3">
                      <div className="space-y-1.5">
                        <label className="text-xs font-medium text-muted-foreground">Mulai</label>
                        <Input type="time" {...register('checkout_start_time')} className="bg-background" />
                        {errors.checkout_start_time && <p className="text-xs text-destructive">{errors.checkout_start_time.message}</p>}
                      </div>
                      <div className="space-y-1.5">
                        <label className="text-xs font-medium text-muted-foreground">Sampai</label>
                        <Input type="time" {...register('checkout_end_time')} className="bg-background" />
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
              <div className="flex items-center gap-4">
                <div className="flex items-center gap-2 text-sm bg-muted px-3 py-1.5 rounded-md">
                  <span className="text-muted-foreground">Pola:</span>
                  <div className="flex gap-1">
                    {daysOfWeek.map((day) => (
                      <div
                        key={day.id}
                        onClick={() => toggleDayPattern(day.id)}
                        className={cn(
                          "w-6 h-6 rounded flex items-center justify-center text-[10px] font-bold cursor-pointer transition-colors",
                          selectedDayPattern.includes(day.id)
                            ? "bg-primary text-primary-foreground"
                            : "bg-background text-muted-foreground border hover:bg-muted-foreground/10"
                        )}
                        title={day.label}
                      >
                        {day.label.substring(0, 1)}
                      </div>
                    ))}
                  </div>
                </div>
                <Button
                  type="button"
                  size="sm"
                  variant="secondary"
                  onClick={() => handleGenerateWorkingDays(false)}
                  disabled={isGeneratingDays}
                >
                  {isGeneratingDays ? (
                    <Loader2 className="mr-2 h-3 w-3 animate-spin" />
                  ) : (
                    <Sparkles className="mr-2 h-3 w-3" />
                  )}
                  Terapkan
                </Button>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div className="grid grid-cols-7 gap-1 sm:gap-4">
                {['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'].map(d => (
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
                      onClick={() => toggleWorkingDay(day.date)}
                      disabled={day.isHoliday}
                      className={cn(
                        "aspect-square rounded-lg flex flex-col items-center justify-center text-sm transition-all border relative group",
                        day.isHoliday
                          ? "bg-red-50 text-red-600 border-red-100 cursor-not-allowed"
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
                      {!day.isHoliday && day.isSelected && (
                        <span className="text-[10px] opacity-80 mt-1">Kerja</span>
                      )}
                    </button>
                  )
                ))}
              </div>

              <div className="flex justify-between items-center pt-4 text-xs text-muted-foreground border-t">
                <div>
                  Total Hari Kerja: <span className="font-medium text-foreground">{workingDays?.length || 0} hari</span>
                </div>
                <div className="flex gap-4">
                  <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded bg-primary" />
                    <span>Hari Kerja</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded bg-red-100 border border-red-200" />
                    <span>Hari Libur</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded bg-card border" />
                    <span>Hari Libur (Off)</span>
                  </div>
                </div>
              </div>
              {errors.working_days && (
                <p className="text-xs text-destructive mt-2">{errors.working_days.message}</p>
              )}
            </div>
          </CardContent>
        </Card>
      </form>
    </div>
  );
}
