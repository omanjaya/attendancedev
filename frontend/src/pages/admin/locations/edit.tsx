import { useState, useEffect } from 'react';
import { Link, useNavigate, useParams } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  ArrowLeft,
  MapPin,
  Loader2,
  Save,
  Building,
  Navigation,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { LoadingState } from '@/components/states';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { useNotificationStore } from '@/stores';
import { getLocation, updateLocation } from '@/lib/api/locations';
import type { LocationFormData } from '@/types/location';
import { LocationMapPicker } from '@/components/maps/LocationMapPicker';

const locationSchema = z.object({
  name: z.string().min(2, 'Nama lokasi minimal 2 karakter'),
  address: z.string().min(5, 'Alamat minimal 5 karakter'),
  latitude: z.string().min(1, 'Masukkan latitude'),
  longitude: z.string().min(1, 'Masukkan longitude'),
  radius: z.string().min(1, 'Masukkan radius'),
});

type LocationForm = z.infer<typeof locationSchema>;

export default function LocationEditPage() {
  const { id } = useParams({ strict: false }) as { id: string };
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { success, error: showError } = useNotificationStore();
  const [isActive, setIsActive] = useState(true);
  const [isGettingLocation, setIsGettingLocation] = useState(false);

  const {
    register,
    handleSubmit,
    setValue,
    reset,
    watch,
    formState: { errors },
  } = useForm<LocationForm>({
    resolver: zodResolver(locationSchema),
  });

  // Fetch location data
  const { data: location, isLoading: isFetching } = useQuery({
    queryKey: ['location', id],
    queryFn: () => getLocation(id),
    enabled: !!id,
  });

  // Update form when data is loaded
  useEffect(() => {
    if (location) {
      reset({
        name: location.name,
        address: location.address || '',
        latitude: location.latitude.toString(),
        longitude: location.longitude.toString(),
        radius: location.radius_meters.toString(),
      });
      setIsActive(location.is_active);
    }
  }, [location, reset]);

  // Update mutation
  const updateMutation = useMutation({
    mutationFn: (data: Partial<LocationFormData>) => updateLocation(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['locations'] });
      queryClient.invalidateQueries({ queryKey: ['location', id] });
      success('Berhasil', 'Lokasi berhasil diperbarui');
      navigate({ to: '/admin/locations' });
    },
    onError: (err: any) => {
      const message = err.response?.data?.message || err.message || 'Gagal memperbarui lokasi';
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
    updateMutation.mutate({
      name: data.name,
      address: data.address,
      latitude: parseFloat(data.latitude),
      longitude: parseFloat(data.longitude),
      radius_meters: parseInt(data.radius),
      is_active: isActive,
    });
  };

  if (isFetching) {
    return <LoadingState message="Memuat data lokasi..." />;
  }

  if (!location) {
    return (
      <div className="p-8 text-center">
        <h2 className="text-xl font-semibold text-destructive">Lokasi tidak ditemukan</h2>
        <Button variant="outline" className="mt-4" onClick={() => navigate({ to: '/admin/locations' })}>
          Kembali
        </Button>
      </div>
    );
  }

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
        <h1 className="text-2xl font-bold text-foreground">Edit Lokasi</h1>
        <p className="text-sm text-muted-foreground">
          Perbarui informasi lokasi absensi
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

              {/* Map Picker */}
              <div className="space-y-2">
                <label className="text-sm font-medium">
                  Peta Lokasi
                </label>
                <LocationMapPicker
                  latitude={parseFloat(watch('latitude') || '-6.2088')}
                  longitude={parseFloat(watch('longitude') || '106.8456')}
                  onLocationChange={(lat: number, lng: number, address?: string) => {
                    setValue('latitude', lat.toString());
                    setValue('longitude', lng.toString());
                    if (address) {
                      setValue('address', address);
                    }
                  }}
                  height="300px"
                />
                <p className="text-xs text-muted-foreground">
                  Geser pin atau cari lokasi untuk memperbarui alamat dan koordinat secara otomatis
                </p>
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
            <Button type="submit" disabled={updateMutation.isPending}>
              {updateMutation.isPending ? (
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
