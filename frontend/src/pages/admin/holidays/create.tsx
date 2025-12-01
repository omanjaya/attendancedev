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
import { useNotificationStore } from '@/stores';

const holidaySchema = z.object({
  name: z.string().min(2, 'Nama hari libur minimal 2 karakter'),
  date: z.string().min(1, 'Pilih tanggal'),
  type: z.string().min(1, 'Pilih jenis hari libur'),
  description: z.string().optional(),
  is_recurring: z.boolean().optional(),
});

type HolidayForm = z.infer<typeof holidaySchema>;

const holidayTypes = [
  { value: 'national', label: 'Hari Libur Nasional', icon: Flag },
  { value: 'religious', label: 'Hari Raya Keagamaan', icon: Star },
  { value: 'company', label: 'Cuti Bersama Perusahaan', icon: Palmtree },
];

export default function HolidayCreatePage() {
  const navigate = useNavigate();
  const { success, error: showError } = useNotificationStore();
  const [isLoading, setIsLoading] = useState(false);
  const [isRecurring, setIsRecurring] = useState(false);

  const {
    register,
    handleSubmit,
    setValue,
    formState: { errors },
  } = useForm<HolidayForm>({
    resolver: zodResolver(holidaySchema),
    defaultValues: {
      is_recurring: false,
    },
  });

  const onSubmit = async (data: HolidayForm) => {
    setIsLoading(true);
    try {
      console.log('Creating holiday:', { ...data, is_recurring: isRecurring });
      await new Promise(resolve => setTimeout(resolve, 1000));
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
                <Select onValueChange={(value) => setValue('type', value)}>
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
                  Tanggal Hari Libur <span className="text-destructive">*</span>
                </label>
                <Input type="date" {...register('date')} />
                {errors.date && (
                  <p className="text-xs text-destructive">{errors.date.message}</p>
                )}
              </div>

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
