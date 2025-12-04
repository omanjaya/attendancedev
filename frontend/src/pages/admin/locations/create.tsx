import { useState } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import {
  ArrowLeft,
  MapPin,
  Loader2,
  Save,
  Building,
  Navigation,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { useNotificationStore } from '@/stores';
import { createLocation } from '@/lib/api/locations';
import type { LocationFormData } from '@/types/location';

const locationSchema = z.object({
  name: z.string().min(2, 'Nama lokasi minimal 2 karakter'),
  address: z.string().min(5, 'Alamat minimal 5 karakter'),
  latitude: z.string().min(1, 'Masukkan latitude'),
  longitude: z.string().min(1, 'Masukkan longitude'),
  radius: z.string().min(1, 'Masukkan radius'),
});

type LocationForm = z.infer<typeof locationSchema>;

export default function LocationCreatePage() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { success, error: showError } = useNotificationStore();
  const [isActive, setIsActive] = useState(true);
  const [isGettingLocation, setIsGettingLocation] = useState(false);

  const {
    register,
    handleSubmit,
    setValue,
    formState: { errors },
  } = useForm<LocationForm>({
    resolver: zodResolver(locationSchema),
    defaultValues: {
      radius: '100',
    },
  });

  const createMutation = useMutation({
    mutationFn: (data: LocationFormData) => createLocation(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['locations'] });
      success('Berhasil', 'Lokasi berhasil dibuat');
      navigate({ to: '/admin/locations' });
    },
    onError: (err: any) => {
      const message = err.response?.data?.message || err.message || 'Gagal membuat lokasi';
      showError('Error', message);
    },
  });

  const getCurrentLocation = () => {
    if (!navigator.geolocation) {
      showError('Error', 'Geolocation tidak didukung browser Anda');
      return;
    }

    setIsGettingLocation(true);
    navigator.geolocation.getCurrentPosition(
      (position) => {
        setValue('latitude', position.coords.latitude.toString());
        setValue('longitude', position.coords.longitude.toString());
        setIsGettingLocation(false);
        success('Berhasil', 'Lokasi berhasil didapatkan');
      },
      (error) => {
        setIsGettingLocation(false);
        showError('Error', 'Gagal mendapatkan lokasi: ' + error.message);
      }
    );
  };

  const onSubmit = (data: LocationForm) => {
    createMutation.mutate({
      name: data.name,
      address: data.address,
      latitude: parseFloat(data.latitude),
      longitude: parseFloat(data.longitude),
      radius_meters: parseInt(data.radius),
      is_active: isActive,
    });
  };

  return (
    <div className="p-4 sm:p-6 max-w-2xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/admin/locations"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar lokasi
        </Link>
        <h1 className="text-2xl font-bold text-foreground">Tambah Lokasi</h1>
        <p className="text-sm text-muted-foreground">
          Buat lokasi baru untuk absensi karyawan
        </p>
      </div>

      <form onSubmit={handleSubmit(onSubmit)}>
        <div className="space-y-6">
          {/* Basic Info */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Building className="h-5 w-5 text-primary" />
                Informasi Lokasi
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">
                  Nama Lokasi <span className="text-destructive">*</span>
                </label>
                <Input
                  placeholder="Contoh: Kantor Pusat Jakarta"
                  {...register('name')}
                />
                {errors.name && (
                  <p className="text-xs text-destructive">{errors.name.message}</p>
                )}
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium">
                  Alamat <span className="text-destructive">*</span>
                </label>
                <Textarea
                  placeholder="Masukkan alamat lengkap..."
                  {...register('address')}
                />
                {errors.address && (
                  <p className="text-xs text-destructive">{errors.address.message}</p>
                )}
              </div>
            </CardContent>
          </Card>

          {/* Coordinates */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <MapPin className="h-5 w-5 text-primary" />
                Koordinat GPS
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <Button
                type="button"
                variant="outline"
                onClick={getCurrentLocation}
                disabled={isGettingLocation}
                className="w-full"
              >
                {isGettingLocation ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Mendapatkan lokasi...
                  </>
                ) : (
                  <>
                    <Navigation className="mr-2 h-4 w-4" />
                    Gunakan Lokasi Saat Ini
                  </>
                )}
              </Button>

              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Latitude <span className="text-destructive">*</span>
                  </label>
                  <Input
                    type="text"
                    placeholder="-6.2088"
                    {...register('latitude')}
                  />
                  {errors.latitude && (
                    <p className="text-xs text-destructive">{errors.latitude.message}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Longitude <span className="text-destructive">*</span>
                  </label>
                  <Input
                    type="text"
                    placeholder="106.8456"
                    {...register('longitude')}
                  />
                  {errors.longitude && (
                    <p className="text-xs text-destructive">{errors.longitude.message}</p>
                  )}
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium">
                  Radius (meter) <span className="text-destructive">*</span>
                </label>
                <Input
                  type="number"
                  placeholder="100"
                  {...register('radius')}
                />
                <p className="text-xs text-muted-foreground">
                  Jarak maksimal dari titik koordinat untuk absensi valid
                </p>
                {errors.radius && (
                  <p className="text-xs text-destructive">{errors.radius.message}</p>
                )}
              </div>

              {/* Map Placeholder */}
              <div className="h-[200px] rounded-lg bg-muted flex items-center justify-center border-2 border-dashed">
                <div className="text-center text-muted-foreground">
                  <MapPin className="h-8 w-8 mx-auto mb-2" />
                  <p className="text-sm">Preview peta akan ditampilkan di sini</p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Status */}
          <Card>
            <CardContent className="p-4 sm:p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium">Status Lokasi</p>
                  <p className="text-sm text-muted-foreground">
                    {isActive ? 'Lokasi aktif dan dapat digunakan untuk absensi' : 'Lokasi tidak aktif'}
                  </p>
                </div>
                <Switch
                  checked={isActive}
                  onCheckedChange={setIsActive}
                />
              </div>
            </CardContent>
          </Card>

          {/* Actions */}
          <div className="flex justify-end gap-3">
            <Button
              type="button"
              variant="outline"
              onClick={() => navigate({ to: '/admin/locations' })}
            >
              Batal
            </Button>
            <Button type="submit" disabled={createMutation.isPending}>
              {createMutation.isPending ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Menyimpan...
                </>
              ) : (
                <>
                  <Save className="mr-2 h-4 w-4" />
                  Simpan Lokasi
                </>
              )}
            </Button>
          </div>
        </div>
      </form>
    </div>
  );
}
