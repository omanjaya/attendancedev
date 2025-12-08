import { useState, useEffect, Suspense, lazy } from 'react';
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
  Wifi,
  FileText,
  Info,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Slider } from '@/components/ui/slider';
import { useNotificationStore } from '@/stores';
import { createLocation } from '@/lib/api/locations';
import type { LocationFormData, LocationType } from '@/types/location';
import { locationTypeLabels } from '@/types/location';

// Lazy load map component
const LocationMapPickerWithRadius = lazy(() =>
  import('@/components/maps/LocationMapPickerWithRadius').then((mod) => ({
    default: mod.LocationMapPickerWithRadius,
  }))
);

const locationSchema = z.object({
  name: z.string().min(2, 'Nama lokasi minimal 2 karakter'),
  address: z.string().min(5, 'Alamat minimal 5 karakter'),
  latitude: z.string().min(1, 'Masukkan latitude'),
  longitude: z.string().min(1, 'Masukkan longitude'),
  radius: z.string().min(1, 'Masukkan radius'),
  wifi_ssid: z.string().optional(),
  description: z.string().optional(),
  type: z.string().optional(),
});

type LocationForm = z.infer<typeof locationSchema>;

// Default coordinates (Jakarta)
const DEFAULT_LAT = -6.2088;
const DEFAULT_LNG = 106.8456;

export default function LocationCreatePage() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { success, error: showError } = useNotificationStore();
  const [isActive, setIsActive] = useState(true);
  const [mapKey, setMapKey] = useState(0);

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
  } = useForm<LocationForm>({
    resolver: zodResolver(locationSchema),
    defaultValues: {
      radius: '100',
      latitude: DEFAULT_LAT.toString(),
      longitude: DEFAULT_LNG.toString(),
      wifi_ssid: '',
      description: '',
      type: '',
    },
  });

  const watchedLat = watch('latitude');
  const watchedLng = watch('longitude');
  const watchedRadius = watch('radius');
  const watchedType = watch('type');

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

  const handleLocationChange = (lat: number, lng: number, address?: string) => {
    setValue('latitude', lat.toString());
    setValue('longitude', lng.toString());
    if (address) {
      setValue('address', address);
    }
  };

  const handleRadiusChange = (value: number[]) => {
    setValue('radius', value[0].toString());
  };

  const onSubmit = (data: LocationForm) => {
    createMutation.mutate({
      name: data.name,
      address: data.address,
      latitude: parseFloat(data.latitude),
      longitude: parseFloat(data.longitude),
      radius_meters: parseInt(data.radius),
      wifi_ssid: data.wifi_ssid || undefined,
      is_active: isActive,
      description: data.description || undefined,
      type: (data.type as LocationType) || undefined,
    });
  };

  // Force map re-render when coordinates change significantly
  useEffect(() => {
    setMapKey((prev) => prev + 1);
  }, []);

  return (
    <div className="p-4 sm:p-6 max-w-4xl mx-auto">
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
          Buat lokasi baru untuk absensi karyawan dengan map interaktif
        </p>
      </div>

      <form onSubmit={handleSubmit(onSubmit)}>
        <div className="grid gap-6 lg:grid-cols-2">
          {/* Left Column - Form Fields */}
          <div className="space-y-6">
            {/* Basic Info */}
            <Card>
              <CardHeader className="pb-4">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <Building className="h-5 w-5 text-primary" />
                  Informasi Lokasi
                </CardTitle>
                <CardDescription>
                  Informasi dasar tentang lokasi absensi
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="name">
                    Nama Lokasi <span className="text-destructive">*</span>
                  </Label>
                  <Input
                    id="name"
                    placeholder="Contoh: Kantor Pusat Jakarta"
                    {...register('name')}
                  />
                  {errors.name && (
                    <p className="text-xs text-destructive">{errors.name.message}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="address">
                    Alamat <span className="text-destructive">*</span>
                  </Label>
                  <Textarea
                    id="address"
                    placeholder="Masukkan alamat lengkap..."
                    {...register('address')}
                    rows={3}
                  />
                  {errors.address && (
                    <p className="text-xs text-destructive">{errors.address.message}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="type">Tipe Lokasi</Label>
                  <Select
                    value={watchedType || ''}
                    onValueChange={(value) => setValue('type', value)}
                  >
                    <SelectTrigger id="type">
                      <SelectValue placeholder="Pilih tipe lokasi" />
                    </SelectTrigger>
                    <SelectContent>
                      {Object.entries(locationTypeLabels).map(([value, label]) => (
                        <SelectItem key={value} value={value}>
                          {label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="description">Deskripsi (Opsional)</Label>
                  <Textarea
                    id="description"
                    placeholder="Deskripsi tambahan tentang lokasi..."
                    {...register('description')}
                    rows={2}
                  />
                </div>
              </CardContent>
            </Card>

            {/* WiFi & Radius Settings */}
            <Card>
              <CardHeader className="pb-4">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <Wifi className="h-5 w-5 text-primary" />
                  Pengaturan Verifikasi
                </CardTitle>
                <CardDescription>
                  Atur radius dan WiFi untuk verifikasi absensi
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <Label htmlFor="radius">
                      Radius (meter) <span className="text-destructive">*</span>
                    </Label>
                    <span className="text-sm font-mono bg-muted px-2 py-1 rounded">
                      {watchedRadius}m
                    </span>
                  </div>
                  <Slider
                    value={[parseInt(watchedRadius || '100')]}
                    onValueChange={handleRadiusChange}
                    min={10}
                    max={1000}
                    step={10}
                    className="w-full"
                  />
                  <div className="flex justify-between text-xs text-muted-foreground">
                    <span>10m</span>
                    <span>500m</span>
                    <span>1000m</span>
                  </div>
                  <p className="text-xs text-muted-foreground flex items-start gap-1.5">
                    <Info className="h-3 w-3 mt-0.5 flex-shrink-0" />
                    Jarak maksimal dari titik koordinat untuk absensi valid
                  </p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="wifi_ssid">WiFi SSID (Opsional)</Label>
                  <Input
                    id="wifi_ssid"
                    placeholder="Contoh: OFFICE_WIFI"
                    {...register('wifi_ssid')}
                  />
                  <p className="text-xs text-muted-foreground">
                    Jika diisi, karyawan harus terhubung ke WiFi ini untuk absensi
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
          </div>

          {/* Right Column - Map */}
          <div className="space-y-6">
            <Card>
              <CardHeader className="pb-4">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <MapPin className="h-5 w-5 text-primary" />
                  Lokasi di Peta
                </CardTitle>
                <CardDescription>
                  Pilih lokasi dengan menggeser marker atau mencari alamat
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <Suspense
                  fallback={
                    <div className="h-[400px] rounded-lg bg-muted flex items-center justify-center">
                      <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                    </div>
                  }
                >
                  <LocationMapPickerWithRadius
                    key={mapKey}
                    latitude={parseFloat(watchedLat) || DEFAULT_LAT}
                    longitude={parseFloat(watchedLng) || DEFAULT_LNG}
                    radius={parseInt(watchedRadius) || 100}
                    onLocationChange={handleLocationChange}
                    height="400px"
                  />
                </Suspense>

                {/* Manual Coordinate Inputs (Collapsible) */}
                <details className="group">
                  <summary className="text-sm text-muted-foreground cursor-pointer hover:text-foreground transition-colors flex items-center gap-1">
                    <FileText className="h-3 w-3" />
                    Input koordinat manual
                  </summary>
                  <div className="mt-3 grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="latitude">Latitude</Label>
                      <Input
                        id="latitude"
                        type="text"
                        placeholder="-6.2088"
                        {...register('latitude')}
                      />
                      {errors.latitude && (
                        <p className="text-xs text-destructive">{errors.latitude.message}</p>
                      )}
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="longitude">Longitude</Label>
                      <Input
                        id="longitude"
                        type="text"
                        placeholder="106.8456"
                        {...register('longitude')}
                      />
                      {errors.longitude && (
                        <p className="text-xs text-destructive">{errors.longitude.message}</p>
                      )}
                    </div>
                  </div>
                </details>
              </CardContent>
            </Card>
          </div>
        </div>

        {/* Actions */}
        <div className="flex justify-end gap-3 mt-6 pt-6 border-t">
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
      </form>
    </div>
  );
}
