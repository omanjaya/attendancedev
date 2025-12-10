import { useState } from 'react';
import { useNavigate, Link } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import {
  ArrowLeft,
  Calendar,
  FileText,
  Loader2,
  Save,
  Palmtree,
  Flag,
  Star,
  Users,
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
import { Switch } from '@/components/ui/switch';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useNotificationStore } from '@/stores';
import { createHoliday, type HolidayFormData } from '@/lib/api/holidays';

const holidaySchema = z.object({
  name: z.string().min(2, 'Nama hari libur minimal 2 karakter'),
  date: z.string().min(1, 'Pilih tanggal'),
  end_date: z.string().optional(),
  type: z.enum(['public_holiday', 'religious_holiday', 'school_holiday', 'substitute_holiday']),
  description: z.string().optional(),
  is_recurring: z.boolean().optional(),
  affected_roles: z.array(z.string()).optional(),
  is_paid: z.boolean().optional(),
});

type HolidayForm = z.infer<typeof holidaySchema>;

const holidayTypes = [
  { value: 'public_holiday', label: 'Libur Nasional', icon: Flag },
  { value: 'religious_holiday', label: 'Libur Keagamaan', icon: Star },
  { value: 'school_holiday', label: 'Libur Sekolah', icon: Palmtree },
  { value: 'substitute_holiday', label: 'Cuti Bersama', icon: Calendar },
];

const availableRoles = [
  { value: 'guru', label: 'Guru' },
  { value: 'pegawai', label: 'Pegawai' },
  { value: 'kepala-sekolah', label: 'Kepala Sekolah' },
];

export default function HolidayCreatePage() {
  const navigate = useNavigate();
  const { success, error: showError } = useNotificationStore();
  const [isLoading, setIsLoading] = useState(false);
  const [isRecurring, setIsRecurring] = useState(false);
  const [isPaid, setIsPaid] = useState(true);
  const [selectedRoles, setSelectedRoles] = useState<string[]>([]);
  const [isMultiDay, setIsMultiDay] = useState(false);

  const {
    register,
    handleSubmit,
    setValue,
    formState: { errors },
  } = useForm<HolidayForm>({
    resolver: zodResolver(holidaySchema),
    defaultValues: {
      is_recurring: false,
      is_paid: true,
      affected_roles: [],
    },
  });

  const handleRoleToggle = (role: string) => {
    setSelectedRoles(prev => {
      const newRoles = prev.includes(role)
        ? prev.filter(r => r !== role)
        : [...prev, role];
      setValue('affected_roles', newRoles);
      return newRoles;
    });
  };

  const onSubmit = async (data: HolidayForm) => {
    setIsLoading(true);
    try {
      const payload: HolidayFormData = {
        name: data.name,
        date: data.date,
        end_date: isMultiDay && data.end_date ? data.end_date : undefined,
        type: data.type,
        description: data.description,
        is_recurring: isRecurring,
        is_paid: isPaid,
        affected_roles: selectedRoles.length > 0 ? selectedRoles : undefined,
        status: 'active',
      };

      await createHoliday(payload);
      success('Berhasil', 'Hari libur berhasil ditambahkan');
      navigate({ to: '/admin/holidays' });
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal menambahkan hari libur';
      showError('Error', message);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="p-4 sm:p-6 max-w-2xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/admin/holidays"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar hari libur
        </Link>
        <h1 className="text-2xl font-bold text-foreground">Tambah Hari Libur</h1>
        <p className="text-sm text-muted-foreground">
          Buat hari libur baru untuk kalender perusahaan
        </p>
      </div>

      <form onSubmit={handleSubmit(onSubmit)}>
        <div className="space-y-6">
          {/* Basic Info */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <FileText className="h-5 w-5 text-primary" />
                Informasi Dasar
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">
                  Nama Hari Libur <span className="text-destructive">*</span>
                </label>
                <Input
                  placeholder="Contoh: Hari Raya Idul Fitri"
                  {...register('name')}
                />
                {errors.name && (
                  <p className="text-xs text-destructive">{errors.name.message}</p>
                )}
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium">
                  Jenis <span className="text-destructive">*</span>
                </label>
                <Select onValueChange={(value) => setValue('type', value as HolidayForm['type'])}>
                  <SelectTrigger>
                    <SelectValue placeholder="Pilih jenis hari libur" />
                  </SelectTrigger>
                  <SelectContent>
                    {holidayTypes.map((type) => (
                      <SelectItem key={type.value} value={type.value}>
                        <div className="flex items-center gap-2">
                          <type.icon className="h-4 w-4" />
                          {type.label}
                        </div>
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.type && (
                  <p className="text-xs text-destructive">{errors.type.message}</p>
                )}
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium">Deskripsi</label>
                <Textarea
                  placeholder="Deskripsi singkat tentang hari libur ini..."
                  {...register('description')}
                />
              </div>
            </CardContent>
          </Card>

          {/* Date */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Calendar className="h-5 w-5 text-primary" />
                Tanggal
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">
                  Tanggal Mulai <span className="text-destructive">*</span>
                </label>
                <Input type="date" {...register('date')} />
                {errors.date && (
                  <p className="text-xs text-destructive">{errors.date.message}</p>
                )}
              </div>

              <div className="flex items-center justify-between p-4 rounded-lg bg-muted">
                <div>
                  <p className="font-medium">Libur Multi-hari</p>
                  <p className="text-sm text-muted-foreground">
                    Aktifkan jika libur lebih dari satu hari
                  </p>
                </div>
                <Switch
                  checked={isMultiDay}
                  onCheckedChange={setIsMultiDay}
                />
              </div>

              {isMultiDay && (
                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Tanggal Selesai
                  </label>
                  <Input type="date" {...register('end_date')} />
                </div>
              )}

              <div className="flex items-center justify-between p-4 rounded-lg bg-muted">
                <div>
                  <p className="font-medium">Berulang Setiap Tahun</p>
                  <p className="text-sm text-muted-foreground">
                    Hari libur ini akan otomatis muncul setiap tahun
                  </p>
                </div>
                <Switch
                  checked={isRecurring}
                  onCheckedChange={setIsRecurring}
                />
              </div>

              <div className="flex items-center justify-between p-4 rounded-lg bg-muted">
                <div>
                  <p className="font-medium">Libur Dibayar</p>
                  <p className="text-sm text-muted-foreground">
                    Karyawan tetap dibayar meski tidak masuk
                  </p>
                </div>
                <Switch
                  checked={isPaid}
                  onCheckedChange={setIsPaid}
                />
              </div>
            </CardContent>
          </Card>

          {/* Affected Roles */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Users className="h-5 w-5 text-primary" />
                Berlaku Untuk
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <p className="text-sm text-muted-foreground">
                Pilih role yang berlaku libur ini. Jika tidak ada yang dipilih, libur berlaku untuk semua karyawan.
              </p>
              <div className="space-y-3">
                {availableRoles.map((role) => (
                  <div key={role.value} className="flex items-center space-x-3">
                    <Checkbox
                      id={`role-${role.value}`}
                      checked={selectedRoles.includes(role.value)}
                      onCheckedChange={() => handleRoleToggle(role.value)}
                    />
                    <Label
                      htmlFor={`role-${role.value}`}
                      className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
                      {role.label}
                    </Label>
                  </div>
                ))}
              </div>
              {selectedRoles.length > 0 && (
                <div className="mt-3 p-3 bg-blue-50 dark:bg-blue-950 rounded-lg">
                  <p className="text-sm text-blue-700 dark:text-blue-300">
                    <strong>Info:</strong> Libur ini hanya berlaku untuk: {selectedRoles.map(r => availableRoles.find(ar => ar.value === r)?.label).join(', ')}
                  </p>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Actions */}
          <div className="flex justify-end gap-3">
            <Button
              type="button"
              variant="outline"
              onClick={() => navigate({ to: '/admin/holidays' })}
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
                  Simpan Hari Libur
                </>
              )}
            </Button>
          </div>
        </div>
      </form>
    </div>
  );
}
