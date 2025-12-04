import { Link, useNavigate, useParams } from '@tanstack/react-router';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  ArrowLeft,
  MapPin,
  Building,
  Edit,
  Trash2,
  Users,
  Clock,
  Navigation,
  CheckCircle,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { LoadingState } from '@/components/states';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { useNotificationStore } from '@/stores';
import { getLocation, deleteLocation } from '@/lib/api/locations';

export default function LocationShowPage() {
  const { id } = useParams({ strict: false }) as { id: string };
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { success, error: showError } = useNotificationStore();

  // Fetch location data
  const { data: location, isLoading } = useQuery({
    queryKey: ['location', id],
    queryFn: () => getLocation(id),
    enabled: !!id,
  });

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: () => deleteLocation(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['locations'] });
      success('Berhasil', 'Lokasi berhasil dihapus');
      navigate({ to: '/admin/locations' });
    },
    onError: (err: any) => {
      const message = err.response?.data?.message || err.message || 'Gagal menghapus lokasi';
      showError('Error', message);
    },
  });

  if (isLoading) {
    return <LoadingState message="Memuat detail lokasi..." />;
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

        <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
          <div>
            <div className="flex items-center gap-3 mb-2">
              <h1 className="text-2xl font-bold text-foreground">{location.name}</h1>
              <Badge variant={location.is_active ? 'default' : 'secondary'}>
                {location.is_active ? 'Aktif' : 'Tidak Aktif'}
              </Badge>
            </div>
            <p className="text-sm text-muted-foreground">{location.address}</p>
          </div>
          <div className="flex items-center gap-2">
            <Button variant="outline" asChild>
              <Link to="/admin/locations/$id/edit" params={{ id: location.id }}>
                <Edit className="h-4 w-4 mr-2" />
                Edit
              </Link>
            </Button>
            <AlertDialog>
              <AlertDialogTrigger asChild>
                <Button variant="destructive">
                  <Trash2 className="h-4 w-4 mr-2" />
                  Hapus
                </Button>
              </AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>Hapus Lokasi?</AlertDialogTitle>
                  <AlertDialogDescription>
                    Tindakan ini tidak dapat dibatalkan. Lokasi "{location.name}" akan dihapus dari sistem.
                  </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>Batal</AlertDialogCancel>
                  <AlertDialogAction
                    className="bg-destructive text-destructive-foreground"
                    onClick={() => deleteMutation.mutate()}
                    disabled={deleteMutation.isPending}
                  >
                    {deleteMutation.isPending ? 'Menghapus...' : 'Hapus'}
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>
          </div>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <Card className="bg-primary/10 border-none">
          <CardContent className="p-4 text-center">
            <Users className="h-6 w-6 mx-auto mb-2 text-primary" />
            <p className="text-2xl font-bold">{location.employee_count || 0}</p>
            <p className="text-xs text-muted-foreground">Karyawan</p>
          </CardContent>
        </Card>
        <Card className="bg-success/10 border-none">
          <CardContent className="p-4 text-center">
            <CheckCircle className="h-6 w-6 mx-auto mb-2 text-success" />
            <p className="text-2xl font-bold">-</p>
            <p className="text-xs text-muted-foreground">Check-in Hari Ini</p>
          </CardContent>
        </Card>
        <Card className="bg-warning/10 border-none">
          <CardContent className="p-4 text-center">
            <Navigation className="h-6 w-6 mx-auto mb-2 text-warning" />
            <p className="text-2xl font-bold">{location.radius_meters}m</p>
            <p className="text-xs text-muted-foreground">Radius</p>
          </CardContent>
        </Card>
        <Card className="bg-accent border-none">
          <CardContent className="p-4 text-center">
            <Clock className="h-6 w-6 mx-auto mb-2 text-muted-foreground" />
            <p className="text-2xl font-bold">-</p>
            <p className="text-xs text-muted-foreground">Tingkat Kehadiran</p>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-6">
          {/* Map */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <MapPin className="h-5 w-5 text-primary" />
                Peta Lokasi
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="h-[300px] rounded-lg bg-muted flex items-center justify-center border-2 border-dashed">
                <div className="text-center text-muted-foreground">
                  <MapPin className="h-12 w-12 mx-auto mb-3" />
                  <p className="font-medium">Peta Interaktif</p>
                  <p className="text-sm">Lat: {location.latitude}, Lng: {location.longitude}</p>
                  <p className="text-sm">Radius: {location.radius_meters} meter</p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Assigned Employees - Placeholder for now */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle className="flex items-center gap-2 text-lg">
                <Users className="h-5 w-5 text-primary" />
                Karyawan di Lokasi Ini
              </CardTitle>
              <Button variant="outline" size="sm" disabled>
                Kelola
              </Button>
            </CardHeader>
            <CardContent>
              <div className="text-center py-6 text-muted-foreground">
                <p>Daftar karyawan belum tersedia.</p>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Location Details */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Building className="h-5 w-5 text-primary" />
                Detail Lokasi
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <p className="text-xs text-muted-foreground">Koordinat</p>
                <p className="text-sm font-mono">
                  {location.latitude}, {location.longitude}
                </p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Radius Absensi</p>
                <p className="text-sm font-medium">{location.radius_meters} meter</p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Dibuat</p>
                <p className="text-sm">
                  {new Date(location.created_at).toLocaleDateString('id-ID')}
                </p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Terakhir Diperbarui</p>
                <p className="text-sm">
                  {new Date(location.updated_at).toLocaleDateString('id-ID')}
                </p>
              </div>
            </CardContent>
          </Card>

          {/* Recent Check-ins - Placeholder */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Clock className="h-5 w-5 text-primary" />
                Check-in Terbaru
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-center py-6 text-muted-foreground">
                <p>Data check-in belum tersedia.</p>
              </div>
            </CardContent>
          </Card>

          {/* Quick Actions */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Aksi Cepat</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              <Button variant="outline" className="w-full justify-start" disabled>
                <Users className="h-4 w-4 mr-2" />
                Assign Karyawan
              </Button>
              <Button variant="outline" className="w-full justify-start" asChild>
                <Link to="/admin/locations/$id/edit" params={{ id: location.id }}>
                  <MapPin className="h-4 w-4 mr-2" />
                  Update Koordinat
                </Link>
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
