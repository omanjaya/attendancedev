import { useState } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import {
  ArrowLeft,
  Clock,
  Loader2,
  Save,
  Calendar,
  Users,
  MapPin,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { LoadingState } from '@/components/states';
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
import { Switch } from '@/components/ui/switch';
import { Checkbox } from '@/components/ui/checkbox';
import { useNotificationStore } from '@/stores';

const scheduleSchema = z.object({
  name: z.string().min(2, 'Nama jadwal minimal 2 karakter'),
  check_in: z.string().min(1, 'Pilih waktu masuk'),
  check_out: z.string().min(1, 'Pilih waktu keluar'),
  late_tolerance: z.string().optional(),
  location_id: z.string().optional(),
  description: z.string().optional(),
});

type ScheduleForm = z.infer<typeof scheduleSchema>;

const locations = [
  { id: '1', name: 'Kantor Pusat' },
  { id: '2', name: 'Kantor Cabang A' },
  { id: '3', name: 'Kantor Cabang B' },
];

const daysOfWeek = [
  { id: 'monday', label: 'Senin' },
  { id: 'tuesday', label: 'Selasa' },
  { id: 'wednesday', label: 'Rabu' },
  { id: 'thursday', label: 'Kamis' },
  { id: 'friday', label: 'Jumat' },
  { id: 'saturday', label: 'Sabtu' },
  { id: 'sunday', label: 'Minggu' },
];

// Mock schedule data
const mockSchedule = {
  id: 1,
  name: 'Jadwal Kantor Reguler',
  description: 'Jadwal kerja standar untuk karyawan kantor pusat',
  check_in: '08:00',
  check_out: '17:00',
  late_tolerance: '15',
  location_id: '1',
  is_flexible: false,
  days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
};

export default function ScheduleEditPage() {
  const navigate = useNavigate();
  const { success, error: showError } = useNotificationStore();
  const [isLoading, setIsLoading] = useState(false);
  const [isFlexible, setIsFlexible] = useState(mockSchedule.is_flexible);
  const [selectedDays, setSelectedDays] = useState(mockSchedule.days);

  const {
    register,
    handleSubmit,
    setValue,
    formState: { errors },
  } = useForm<ScheduleForm>({
    resolver: zodResolver(scheduleSchema),
    defaultValues: {
      name: mockSchedule.name,
      description: mockSchedule.description,
      check_in: mockSchedule.check_in,
      check_out: mockSchedule.check_out,
      late_tolerance: mockSchedule.late_tolerance,
      location_id: mockSchedule.location_id,
    },
  });

  const toggleDay = (dayId: string) => {
    setSelectedDays(prev =>
      prev.includes(dayId)
        ? prev.filter(d => d !== dayId)
        : [...prev, dayId]
    );
  };

  const onSubmit = async (data: ScheduleForm) => {
    setIsLoading(true);
    try {
      console.log('Updating schedule:', { ...data, days: selectedDays, is_flexible: isFlexible });
      await new Promise(resolve => setTimeout(resolve, 1000));
      success('Berhasil', 'Jadwal berhasil diperbarui');
      navigate({ to: '/schedules' });
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal memperbarui jadwal';
      showError('Error', message);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="p-4 sm:p-6 max-w-3xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/schedules"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar jadwal
        </Link>
        <h1 className="text-2xl font-bold text-foreground">Edit Jadwal</h1>
        <p className="text-sm text-muted-foreground">
          Perbarui pengaturan jadwal kerja
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
                  placeholder="Contoh: Jadwal Kantor Reguler"
                  {...register('name')}
                />
                {errors.name && (
                  <p className="text-xs text-destructive">{errors.name.message}</p>
                )}
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium">Deskripsi</label>
                <Textarea
                  placeholder="Deskripsi singkat tentang jadwal ini..."
                  {...register('description')}
                />
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium">Lokasi</label>
                <Select
                  defaultValue={mockSchedule.location_id}
                  onValueChange={(value) => setValue('location_id', value)}
                >
                  <SelectTrigger>
                    <MapPin className="h-4 w-4 mr-2 text-muted-foreground" />
                    <SelectValue placeholder="Pilih lokasi (opsional)" />
                  </SelectTrigger>
                  <SelectContent>
                    {locations.map((loc) => (
                      <SelectItem key={loc.id} value={loc.id}>
                        {loc.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
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
            <CardContent className="space-y-4">
              <div className="grid gap-4 md:grid-cols-3">
                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Jam Masuk <span className="text-destructive">*</span>
                  </label>
                  <Input
                    type="time"
                    {...register('check_in')}
                  />
                  {errors.check_in && (
                    <p className="text-xs text-destructive">{errors.check_in.message}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Jam Keluar <span className="text-destructive">*</span>
                  </label>
                  <Input
                    type="time"
                    {...register('check_out')}
                  />
                  {errors.check_out && (
                    <p className="text-xs text-destructive">{errors.check_out.message}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium">Toleransi Terlambat (menit)</label>
                  <Input
                    type="number"
                    placeholder="15"
                    {...register('late_tolerance')}
                  />
                </div>
              </div>

              <div className="flex items-center justify-between p-4 rounded-lg bg-muted">
                <div>
                  <p className="font-medium">Jam Kerja Fleksibel</p>
                  <p className="text-sm text-muted-foreground">
                    Karyawan dapat check-in dalam rentang waktu tertentu
                  </p>
                </div>
                <Switch
                  checked={isFlexible}
                  onCheckedChange={setIsFlexible}
                />
              </div>
            </CardContent>
          </Card>

          {/* Working Days */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Users className="h-5 w-5 text-primary" />
                Hari Kerja
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                {daysOfWeek.map((day) => (
                  <label
                    key={day.id}
                    className={`
                      flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors
                      ${selectedDays.includes(day.id)
                        ? 'bg-primary/10 border-primary'
                        : 'bg-background hover:bg-muted'
                      }
                    `}
                  >
                    <Checkbox
                      checked={selectedDays.includes(day.id)}
                      onCheckedChange={() => toggleDay(day.id)}
                    />
                    <span className="text-sm font-medium">{day.label}</span>
                  </label>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* Actions */}
          <div className="flex justify-end gap-3">
            <Button
              type="button"
              variant="outline"
              onClick={() => navigate({ to: '/schedules' })}
            >
              Batal
            </Button>
            <Button type="submit" disabled={isLoading}>
              {isLoading ? (
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
      </form>
    </div>
  );
}
