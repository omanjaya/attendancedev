import { Link } from '@tanstack/react-router';
import {
  ArrowLeft,
  Clock,
  Calendar,
  MapPin,
  Users,
  Edit,
  Trash2,
  CheckCircle,
  AlertCircle,
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

// Mock schedule data
const mockSchedule = {
  id: 1,
  name: 'Jadwal Kantor Reguler',
  description: 'Jadwal kerja standar untuk karyawan kantor pusat dengan jam kerja 08:00 - 17:00',
  check_in: '08:00',
  check_out: '17:00',
  late_tolerance: 15,
  is_flexible: false,
  location: 'Kantor Pusat',
  days: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
  status: 'active',
  employee_count: 45,
  created_at: '2024-01-15',
};

const assignedEmployees = [
  { id: 1, name: 'Ahmad Fauzi', position: 'Senior Developer' },
  { id: 2, name: 'Siti Aminah', position: 'HR Manager' },
  { id: 3, name: 'Budi Santoso', position: 'Finance Staff' },
  { id: 4, name: 'Dewi Lestari', position: 'Marketing Lead' },
  { id: 5, name: 'Rizki Pratama', position: 'IT Support' },
];

export default function ScheduleShowPage() {
  const schedule = mockSchedule;

  return (
    <div className="p-6 max-w-4xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/schedules"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar jadwal
        </Link>

        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <div className="flex items-center gap-3">
              <h1 className="text-2xl font-bold text-foreground">{schedule.name}</h1>
              <Badge variant={schedule.status === 'active' ? 'default' : 'secondary'}>
                {schedule.status === 'active' ? 'Aktif' : 'Tidak Aktif'}
              </Badge>
            </div>
            <p className="text-sm text-muted-foreground mt-1">
              {schedule.description}
            </p>
          </div>
          <div className="flex items-center gap-2">
            <Button variant="outline" asChild>
              <a href={`/schedules/1/edit`}>
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
                  <AlertDialogTitle>Hapus Jadwal?</AlertDialogTitle>
                  <AlertDialogDescription>
                    Tindakan ini tidak dapat dibatalkan. Jadwal akan dihapus dan semua karyawan yang terkait akan kehilangan jadwal ini.
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

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Main Info */}
        <div className="lg:col-span-2 space-y-6">
          {/* Time Settings */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Clock className="h-5 w-5 text-primary" />
                Pengaturan Waktu
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div className="text-center p-4 rounded-lg bg-success/10">
                  <p className="text-2xl font-bold text-success">{schedule.check_in}</p>
                  <p className="text-xs text-muted-foreground">Jam Masuk</p>
                </div>
                <div className="text-center p-4 rounded-lg bg-destructive/10">
                  <p className="text-2xl font-bold text-destructive">{schedule.check_out}</p>
                  <p className="text-xs text-muted-foreground">Jam Keluar</p>
                </div>
                <div className="text-center p-4 rounded-lg bg-warning/10">
                  <p className="text-2xl font-bold text-warning">{schedule.late_tolerance}m</p>
                  <p className="text-xs text-muted-foreground">Toleransi</p>
                </div>
                <div className="text-center p-4 rounded-lg bg-primary/10">
                  <p className="text-2xl font-bold text-primary">9h</p>
                  <p className="text-xs text-muted-foreground">Total Jam</p>
                </div>
              </div>

              <div className="mt-4 p-4 rounded-lg bg-muted flex items-center justify-between">
                <div className="flex items-center gap-2">
                  {schedule.is_flexible ? (
                    <CheckCircle className="h-5 w-5 text-success" />
                  ) : (
                    <AlertCircle className="h-5 w-5 text-muted-foreground" />
                  )}
                  <div>
                    <p className="font-medium">Jam Kerja Fleksibel</p>
                    <p className="text-sm text-muted-foreground">
                      {schedule.is_flexible ? 'Aktif' : 'Tidak Aktif'}
                    </p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Working Days */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Calendar className="h-5 w-5 text-primary" />
                Hari Kerja
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex flex-wrap gap-2">
                {['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'].map((day) => (
                  <Badge
                    key={day}
                    variant={schedule.days.includes(day) ? 'default' : 'outline'}
                    className={schedule.days.includes(day) ? '' : 'opacity-50'}
                  >
                    {day}
                  </Badge>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* Assigned Employees */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle className="flex items-center gap-2 text-lg">
                <Users className="h-5 w-5 text-primary" />
                Karyawan Terkait ({schedule.employee_count})
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
                      <p className="text-sm text-muted-foreground">{employee.position}</p>
                    </div>
                  </div>
                ))}
                {schedule.employee_count > 5 && (
                  <p className="text-center text-sm text-muted-foreground py-2">
                    +{schedule.employee_count - 5} karyawan lainnya
                  </p>
                )}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Quick Info */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Informasi</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center gap-3">
                <MapPin className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-xs text-muted-foreground">Lokasi</p>
                  <p className="text-sm font-medium">{schedule.location}</p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <Users className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-xs text-muted-foreground">Jumlah Karyawan</p>
                  <p className="text-sm font-medium">{schedule.employee_count} orang</p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <Calendar className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-xs text-muted-foreground">Dibuat</p>
                  <p className="text-sm font-medium">
                    {new Date(schedule.created_at).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'long',
                      year: 'numeric',
                    })}
                  </p>
                </div>
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
                <Calendar className="h-4 w-4 mr-2" />
                Lihat di Kalender
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
