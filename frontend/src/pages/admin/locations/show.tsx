import { Link } from '@tanstack/react-router';
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
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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

// Mock location data
const mockLocation = {
  id: 1,
  name: 'Kantor Pusat Jakarta',
  address: 'Jl. Sudirman No. 123, Karet Semanggi, Setiabudi, Jakarta Selatan 12930',
  latitude: '-6.2088',
  longitude: '106.8456',
  radius: 100,
  is_active: true,
  employee_count: 45,
  today_checkins: 42,
  created_at: '2024-01-15',
  updated_at: '2024-11-20',
};

const assignedEmployees = [
  { id: 1, name: 'Ahmad Fauzi', department: 'IT' },
  { id: 2, name: 'Siti Aminah', department: 'HR' },
  { id: 3, name: 'Budi Santoso', department: 'Finance' },
  { id: 4, name: 'Dewi Lestari', department: 'Marketing' },
  { id: 5, name: 'Rizki Pratama', department: 'IT' },
];

const recentCheckins = [
  { name: 'Ahmad Fauzi', time: '08:00', type: 'check_in' },
  { name: 'Siti Aminah', time: '08:05', type: 'check_in' },
  { name: 'Budi Santoso', time: '08:10', type: 'check_in' },
];

export default function LocationShowPage() {
  const location = mockLocation;

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
              <a href={`/admin/locations/${location.id}/edit`}>
                <Edit className="h-4 w-4 mr-2" />
                Edit
              </a>
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
                  <AlertDialogAction className="bg-destructive text-destructive-foreground">
                    Hapus
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
            <p className="text-2xl font-bold">{location.employee_count}</p>
            <p className="text-xs text-muted-foreground">Karyawan</p>
          </CardContent>
        </Card>
        <Card className="bg-success/10 border-none">
          <CardContent className="p-4 text-center">
            <CheckCircle className="h-6 w-6 mx-auto mb-2 text-success" />
            <p className="text-2xl font-bold">{location.today_checkins}</p>
            <p className="text-xs text-muted-foreground">Check-in Hari Ini</p>
          </CardContent>
        </Card>
        <Card className="bg-warning/10 border-none">
          <CardContent className="p-4 text-center">
            <Navigation className="h-6 w-6 mx-auto mb-2 text-warning" />
            <p className="text-2xl font-bold">{location.radius}m</p>
            <p className="text-xs text-muted-foreground">Radius</p>
          </CardContent>
        </Card>
        <Card className="bg-accent border-none">
          <CardContent className="p-4 text-center">
            <Clock className="h-6 w-6 mx-auto mb-2 text-muted-foreground" />
            <p className="text-2xl font-bold">93%</p>
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
                  <p className="text-sm">Radius: {location.radius} meter</p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Assigned Employees */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle className="flex items-center gap-2 text-lg">
                <Users className="h-5 w-5 text-primary" />
                Karyawan di Lokasi Ini
              </CardTitle>
              <Button variant="outline" size="sm">
                Kelola
              </Button>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {assignedEmployees.map((employee) => (
                  <div
                    key={employee.id}
                    className="flex items-center gap-3 p-3 rounded-lg bg-muted/50"
                  >
                    <Avatar className="h-10 w-10">
                      <AvatarFallback className="bg-primary/10 text-primary">
                        {employee.name.split(' ').map(n => n[0]).join('')}
                      </AvatarFallback>
                    </Avatar>
                    <div className="flex-1">
                      <p className="font-medium">{employee.name}</p>
                      <p className="text-sm text-muted-foreground">{employee.department}</p>
                    </div>
                  </div>
                ))}
                <p className="text-center text-sm text-muted-foreground py-2">
                  +{location.employee_count - 5} karyawan lainnya
                </p>
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
                <p className="text-sm font-medium">{location.radius} meter</p>
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

          {/* Recent Check-ins */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Clock className="h-5 w-5 text-primary" />
                Check-in Terbaru
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {recentCheckins.map((checkin, index) => (
                  <div key={index} className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <CheckCircle className="h-4 w-4 text-success" />
                      <span className="text-sm">{checkin.name}</span>
                    </div>
                    <span className="text-sm text-muted-foreground font-mono">
                      {checkin.time}
                    </span>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* Quick Actions */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Aksi Cepat</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              <Button variant="outline" className="w-full justify-start">
                <Users className="h-4 w-4 mr-2" />
                Assign Karyawan
              </Button>
              <Button variant="outline" className="w-full justify-start">
                <MapPin className="h-4 w-4 mr-2" />
                Update Koordinat
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
