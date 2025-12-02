import { useState, useEffect } from 'react';
import { Link, useNavigate, useParams } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation, useQuery } from '@tanstack/react-query';
import {
  ArrowLeft,
  Clock,
  Loader2,
  Save,
  Calendar,
  MapPin,
  CalendarDays,
  Sparkles,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Checkbox } from '@/components/ui/checkbox';
import { useNotificationStore } from '@/stores';
import { getLocations } from '@/lib/api/locations';
import {
  getMonthlyAttendanceSchedule,
  updateMonthlyAttendanceSchedule,
  generateWorkingDays,
  type MonthlyAttendanceScheduleFormData,
  type GenerateWorkingDaysParams,
} from '@/lib/api/schedules';
import { getHolidaysByMonth } from '@/lib/api/holidays';

const monthlyScheduleSchema = z.object({
  name: z.string().min(2, 'Nama jadwal minimal 2 karakter'),
  month: z.number().min(1).max(12),
  year: z.number().min(2024).max(2030),
  location_id: z.string().min(1, 'Pilih lokasi'),
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

export default function MonthlyScheduleEditPage() {
  const { id } = useParams({ from: '/admin/schedules/monthly/$id/edit' });
  const navigate = useNavigate();
  const { success, error: showError } = useNotificationStore();
  const [selectedDayPattern, setSelectedDayPattern] = useState<string[]>([]);
  const [generatedWorkingDays, setGeneratedWorkingDays] = useState<string[]>([]);
  const [isGeneratingDays, setIsGeneratingDays] = useState(false);
  const [holidays, setHolidays] = useState<string[]>([]);

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
    reset,
  } = useForm<MonthlyScheduleForm>({
    resolver: zodResolver(monthlyScheduleSchema),
  });

  const selectedMonth = watch('month');
  const selectedYear = watch('year');

  // Fetch existing schedule
  const { data: schedule, isLoading: isLoadingSchedule } = useQuery({
    queryKey: ['monthly-schedule', id],
    queryFn: () => getMonthlyAttendanceSchedule(id),
  });

  // Fetch locations
  const { data: locations = [], isLoading: isLoadingLocations } = useQuery({
    queryKey: ['locations'],
    queryFn: getLocations,
  });

  // Pre-fill form when schedule data is loaded
  useEffect(() => {
    if (schedule) {
      reset({
        name: schedule.name,
        month: schedule.month,
        year: schedule.year,
        location_id: schedule.location_id,
        default_start_time: schedule.default_start_time,
        default_end_time: schedule.default_end_time,
        checkin_start_time: schedule.checkin_start_time,
        checkin_end_time: schedule.checkin_end_time,
        checkout_start_time: schedule.checkout_start_time,
        checkout_end_time: schedule.checkout_end_time,
        working_days: schedule.working_days,
        description: schedule.description || '',
      });
      setGeneratedWorkingDays(schedule.working_days);
    }
  }, [schedule, reset]);

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

  const handleGenerateWorkingDays = async () => {
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

      success(
        'Hari kerja berhasil dibuat',
        `${result.total_working_days} hari kerja (${result.total_holidays} hari libur dikecualikan)`
      );
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

  const updateScheduleMutation = useMutation({
    mutationFn: (data: Partial<MonthlyAttendanceScheduleFormData>) =>
      updateMonthlyAttendanceSchedule(id, data),
    onSuccess: () => {
      success('Berhasil', 'Jadwal bulanan berhasil diupdate');
      navigate({ to: '/admin/schedules/monthly' });
    },
    onError: (err: any) => {
      const message = err?.response?.data?.message || 'Gagal mengupdate jadwal bulanan';
      showError('Error', message);
    },
  });

  const onSubmit = async (data: MonthlyScheduleForm) => {
    updateScheduleMutation.mutate(data);
  };

  // Generate calendar grid for working days selection
  const generateCalendarDays = () => {
    if (!selectedMonth || !selectedYear) return [];

    const firstDay = new Date(selectedYear, selectedMonth - 1, 1);
    const lastDay = new Date(selectedYear, selectedMonth, 0);
    const days = [];

    // Add empty cells for days before month starts
    const startDayOfWeek = firstDay.getDay();
    for (let i = 0; i < startDayOfWeek; i++) {
      days.push(null);
    }

    // Add days of month
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

  if (isLoadingSchedule) {
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
          <p className="text-muted-foreground">Jadwal tidak ditemukan</p>
          <Link to="/admin/schedules/monthly">
            <Button className="mt-4">Kembali ke Daftar Jadwal</Button>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="p-4 sm:p-6 max-w-5xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/admin/schedules/monthly"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar jadwal bulanan
        </Link>
        <h1 className="text-2xl font-bold text-foreground">Edit Jadwal Bulanan</h1>
        <p className="text-sm text-muted-foreground">
          Update jadwal absensi bulanan: {schedule.name}
        </p>
      </div>

      <form onSubmit={handleSubmit(onSubmit)}>
        <div className="space-y-6">
          {/* Basic Info */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Calendar className="h-5 w-5 text-primary" />
                Informasi Dasar
              </CardTitle>
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

              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Bulan <span className="text-destructive">*</span>
                  </label>
                  <Select
                    value={selectedMonth?.toString()}
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
                    value={selectedYear?.toString()}
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
                <label className="text-sm font-medium">
                  Lokasi <span className="text-destructive">*</span>
                </label>
                <Select
                  value={watch('location_id')}
                  onValueChange={(value) => setValue('location_id', value)}
                >
                  <SelectTrigger>
                    <MapPin className="h-4 w-4 mr-2 text-muted-foreground" />
                    <SelectValue placeholder="Pilih lokasi" />
                  </SelectTrigger>
                  <SelectContent>
                    {isLoadingLocations ? (
                      <div className="p-2 text-center text-sm text-muted-foreground">
                        Memuat lokasi...
                      </div>
                    ) : (
                      locations.map((loc) => (
                        <SelectItem key={loc.id} value={loc.id}>
                          {loc.name}
                        </SelectItem>
                      ))
                    )}
                  </SelectContent>
                </Select>
                {errors.location_id && (
                  <p className="text-xs text-destructive">{errors.location_id.message}</p>
                )}
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium">Deskripsi</label>
                <Textarea
                  placeholder="Deskripsi singkat tentang jadwal ini..."
                  {...register('description')}
                />
              </div>
            </CardContent>
          </Card>

          {/* Time Settings */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Clock className="h-5 w-5 text-primary" />
                Pengaturan Waktu
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
              <div>
                <h3 className="text-sm font-medium mb-3">Jam Kerja</h3>
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <label className="text-sm">
                      Jam Masuk <span className="text-destructive">*</span>
                    </label>
                    <Input
                      type="time"
                      {...register('default_start_time')}
                    />
                    {errors.default_start_time && (
                      <p className="text-xs text-destructive">{errors.default_start_time.message}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <label className="text-sm">
                      Jam Keluar <span className="text-destructive">*</span>
                    </label>
                    <Input
                      type="time"
                      {...register('default_end_time')}
                    />
                    {errors.default_end_time && (
                      <p className="text-xs text-destructive">{errors.default_end_time.message}</p>
                    )}
                  </div>
                </div>
              </div>

              <div>
                <h3 className="text-sm font-medium mb-3">Window Absen Masuk</h3>
                <p className="text-xs text-muted-foreground mb-3">
                  Rentang waktu dimana karyawan diperbolehkan absen masuk
                </p>
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <label className="text-sm">
                      Mulai <span className="text-destructive">*</span>
                    </label>
                    <Input
                      type="time"
                      {...register('checkin_start_time')}
                    />
                    {errors.checkin_start_time && (
                      <p className="text-xs text-destructive">{errors.checkin_start_time.message}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <label className="text-sm">
                      Sampai <span className="text-destructive">*</span>
                    </label>
                    <Input
                      type="time"
                      {...register('checkin_end_time')}
                    />
                    {errors.checkin_end_time && (
                      <p className="text-xs text-destructive">{errors.checkin_end_time.message}</p>
                    )}
                  </div>
                </div>
              </div>

              <div>
                <h3 className="text-sm font-medium mb-3">Window Absen Pulang</h3>
                <p className="text-xs text-muted-foreground mb-3">
                  Rentang waktu dimana karyawan diperbolehkan absen pulang
                </p>
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <label className="text-sm">
                      Mulai <span className="text-destructive">*</span>
                    </label>
                    <Input
                      type="time"
                      {...register('checkout_start_time')}
                    />
                    {errors.checkout_start_time && (
                      <p className="text-xs text-destructive">{errors.checkout_start_time.message}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <label className="text-sm">
                      Sampai <span className="text-destructive">*</span>
                    </label>
                    <Input
                      type="time"
                      {...register('checkout_end_time')}
                    />
                    {errors.checkout_end_time && (
                      <p className="text-xs text-destructive">{errors.checkout_end_time.message}</p>
                    )}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Working Days */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <CalendarDays className="h-5 w-5 text-primary" />
                Hari Kerja
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
              <div>
                <div className="flex items-center justify-between mb-3">
                  <label className="text-sm font-medium">Pola Hari Kerja</label>
                  <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    onClick={handleGenerateWorkingDays}
                    disabled={isGeneratingDays || !selectedMonth || !selectedYear}
                  >
                    {isGeneratingDays ? (
                      <>
                        <Loader2 className="mr-2 h-3 w-3 animate-spin" />
                        Membuat...
                      </>
                    ) : (
                      <>
                        <Sparkles className="mr-2 h-3 w-3" />
                        Re-generate Hari Kerja
                      </>
                    )}
                  </Button>
                </div>
                <p className="text-xs text-muted-foreground mb-3">
                  Pilih pola hari kerja mingguan, kemudian klik tombol untuk generate ulang hari kerja
                </p>
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                  {daysOfWeek.map((day) => (
                    <label
                      key={day.id}
                      className={`
                        flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors
                        ${selectedDayPattern.includes(day.id)
                          ? 'bg-primary/10 border-primary'
                          : 'bg-background hover:bg-muted'
                        }
                      `}
                    >
                      <Checkbox
                        checked={selectedDayPattern.includes(day.id)}
                        onCheckedChange={() => toggleDayPattern(day.id)}
                      />
                      <span className="text-sm font-medium">{day.label}</span>
                    </label>
                  ))}
                </div>
              </div>

              {generatedWorkingDays.length > 0 && (
                <div>
                  <label className="text-sm font-medium">
                    Kalender Hari Kerja ({generatedWorkingDays.length} hari)
                  </label>
                  <p className="text-xs text-muted-foreground mb-3">
                    Klik tanggal untuk mengubah status hari kerja secara manual
                  </p>
                  <div className="grid grid-cols-7 gap-2">
                    <div className="text-center text-xs font-medium text-muted-foreground py-2">Min</div>
                    <div className="text-center text-xs font-medium text-muted-foreground py-2">Sen</div>
                    <div className="text-center text-xs font-medium text-muted-foreground py-2">Sel</div>
                    <div className="text-center text-xs font-medium text-muted-foreground py-2">Rab</div>
                    <div className="text-center text-xs font-medium text-muted-foreground py-2">Kam</div>
                    <div className="text-center text-xs font-medium text-muted-foreground py-2">Jum</div>
                    <div className="text-center text-xs font-medium text-muted-foreground py-2">Sab</div>

                    {generateCalendarDays().map((day, idx) => (
                      day === null ? (
                        <div key={`empty-${idx}`} />
                      ) : (
                        <button
                          key={day.date}
                          type="button"
                          onClick={() => toggleWorkingDay(day.date)}
                          className={`
                            p-2 rounded-lg text-sm transition-colors
                            ${day.isHoliday
                              ? 'bg-red-100 text-red-700 cursor-not-allowed opacity-50'
                              : day.isSelected
                              ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                              : 'bg-muted hover:bg-muted/70'
                            }
                          `}
                          disabled={day.isHoliday}
                        >
                          {day.day}
                        </button>
                      )
                    ))}
                  </div>
                  <div className="flex gap-4 mt-3 text-xs">
                    <div className="flex items-center gap-2">
                      <div className="w-4 h-4 rounded bg-primary" />
                      <span>Hari kerja</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <div className="w-4 h-4 rounded bg-red-100" />
                      <span>Hari libur</span>
                    </div>
                  </div>
                </div>
              )}

              {errors.working_days && (
                <p className="text-xs text-destructive">{errors.working_days.message}</p>
              )}
            </CardContent>
          </Card>

          {/* Actions */}
          <div className="flex justify-end gap-3">
            <Button
              type="button"
              variant="outline"
              onClick={() => navigate({ to: '/admin/schedules/monthly' })}
            >
              Batal
            </Button>
            <Button type="submit" disabled={updateScheduleMutation.isPending}>
              {updateScheduleMutation.isPending ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Menyimpan...
                </>
              ) : (
                <>
                  <Save className="mr-2 h-4 w-4" />
                  Update Jadwal
                </>
              )}
            </Button>
          </div>
        </div>
      </form>
    </div>
  );
}
