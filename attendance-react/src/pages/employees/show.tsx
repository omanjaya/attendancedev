import { useState } from 'react';
import { Link } from '@tanstack/react-router';
import {
  ArrowLeft,
  User,
  Mail,
  Phone,
  Building,
  Briefcase,
  Calendar,
  MapPin,
  Edit,
  Trash2,
  CheckCircle,
  Camera,
  Shield,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
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

// Mock employee data
const mockEmployee = {
  id: 1,
  name: 'Ahmad Fauzi',
  email: 'ahmad.fauzi@example.com',
  phone: '081234567890',
  department: 'IT & Development',
  position: 'Senior Developer',
  join_date: '2022-01-15',
  address: 'Jl. Sudirman No. 123, Jakarta Selatan',
  status: 'active',
  face_enrolled: true,
  attendance_rate: 95.6,
  total_present: 22,
  total_late: 2,
  total_leave: 3,
};

const recentAttendance = [
  { date: '2024-11-28', check_in: '08:15', check_out: '17:30', status: 'present' },
  { date: '2024-11-27', check_in: '08:45', check_out: '17:00', status: 'late' },
  { date: '2024-11-26', check_in: '08:00', check_out: '17:15', status: 'present' },
  { date: '2024-11-25', check_in: '07:55', check_out: '17:30', status: 'present' },
  { date: '2024-11-24', check_in: '-', check_out: '-', status: 'leave' },
];

export default function EmployeeShowPage() {
  const [activeTab, setActiveTab] = useState('overview');

  const employee = mockEmployee; // In real app, fetch by id
  const id = '1'; // Mock id

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'present':
        return <Badge className="bg-success/10 text-success">Hadir</Badge>;
      case 'late':
        return <Badge className="bg-warning/10 text-warning">Terlambat</Badge>;
      case 'leave':
        return <Badge className="bg-primary/10 text-primary">Cuti</Badge>;
      default:
        return <Badge variant="outline">{status}</Badge>;
    }
  };

  return (
    <div className="p-6 max-w-6xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/employees"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar karyawan
        </Link>
      </div>

      {/* Profile Header */}
      <Card className="mb-6">
        <CardContent className="p-6">
          <div className="flex flex-col md:flex-row items-start md:items-center gap-6">
            <Avatar className="h-24 w-24">
              <AvatarImage src="" />
              <AvatarFallback className="text-2xl bg-primary/10 text-primary">
                {employee.name.split(' ').map(n => n[0]).join('')}
              </AvatarFallback>
            </Avatar>

            <div className="flex-1">
              <div className="flex flex-col sm:flex-row sm:items-center gap-2 mb-2">
                <h1 className="text-2xl font-bold text-foreground">{employee.name}</h1>
                <Badge variant={employee.status === 'active' ? 'default' : 'secondary'}>
                  {employee.status === 'active' ? 'Aktif' : 'Tidak Aktif'}
                </Badge>
                {employee.face_enrolled && (
                  <Badge variant="outline" className="bg-success/10 text-success">
                    <Camera className="h-3 w-3 mr-1" />
                    Face Enrolled
                  </Badge>
                )}
              </div>
              <p className="text-muted-foreground">{employee.position}</p>
              <p className="text-sm text-muted-foreground">{employee.department}</p>
            </div>

            <div className="flex gap-2">
              <Button variant="outline" asChild>
                <a href={`/employees/${id}/edit`}>
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
                    <AlertDialogTitle>Hapus Karyawan?</AlertDialogTitle>
                    <AlertDialogDescription>
                      Tindakan ini tidak dapat dibatalkan. Data karyawan akan dihapus permanen.
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
        </CardContent>
      </Card>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <Card className="bg-accent/50 border-none">
          <CardContent className="p-4 text-center">
            <p className="text-3xl font-bold text-success">{employee.attendance_rate}%</p>
            <p className="text-xs text-muted-foreground">Kehadiran</p>
          </CardContent>
        </Card>
        <Card className="bg-accent/50 border-none">
          <CardContent className="p-4 text-center">
            <p className="text-3xl font-bold">{employee.total_present}</p>
            <p className="text-xs text-muted-foreground">Hari Hadir</p>
          </CardContent>
        </Card>
        <Card className="bg-accent/50 border-none">
          <CardContent className="p-4 text-center">
            <p className="text-3xl font-bold text-warning">{employee.total_late}</p>
            <p className="text-xs text-muted-foreground">Terlambat</p>
          </CardContent>
        </Card>
        <Card className="bg-accent/50 border-none">
          <CardContent className="p-4 text-center">
            <p className="text-3xl font-bold text-primary">{employee.total_leave}</p>
            <p className="text-xs text-muted-foreground">Cuti Diambil</p>
          </CardContent>
        </Card>
      </div>

      {/* Tabs Content */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList className="mb-4">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="attendance">Kehadiran</TabsTrigger>
          <TabsTrigger value="security">Keamanan</TabsTrigger>
        </TabsList>

        <TabsContent value="overview">
          <div className="grid gap-6 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg">
                  <User className="h-5 w-5 text-primary" />
                  Informasi Pribadi
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center gap-3">
                  <Mail className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-xs text-muted-foreground">Email</p>
                    <p className="text-sm">{employee.email}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <Phone className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-xs text-muted-foreground">Telepon</p>
                    <p className="text-sm">{employee.phone}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <MapPin className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-xs text-muted-foreground">Alamat</p>
                    <p className="text-sm">{employee.address}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg">
                  <Briefcase className="h-5 w-5 text-primary" />
                  Informasi Pekerjaan
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center gap-3">
                  <Building className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-xs text-muted-foreground">Departemen</p>
                    <p className="text-sm">{employee.department}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <Briefcase className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-xs text-muted-foreground">Posisi</p>
                    <p className="text-sm">{employee.position}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <Calendar className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-xs text-muted-foreground">Tanggal Bergabung</p>
                    <p className="text-sm">
                      {new Date(employee.join_date).toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric',
                      })}
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="attendance">
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Kehadiran Terbaru</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="border-b">
                      <th className="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Tanggal</th>
                      <th className="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Check In</th>
                      <th className="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Check Out</th>
                      <th className="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {recentAttendance.map((record, index) => (
                      <tr key={index} className="border-b last:border-0">
                        <td className="px-4 py-3 text-sm">
                          {new Date(record.date).toLocaleDateString('id-ID')}
                        </td>
                        <td className="px-4 py-3 text-sm font-mono">{record.check_in}</td>
                        <td className="px-4 py-3 text-sm font-mono">{record.check_out}</td>
                        <td className="px-4 py-3">{getStatusBadge(record.status)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="security">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Shield className="h-5 w-5 text-primary" />
                Keamanan & Verifikasi
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between p-4 rounded-lg bg-muted">
                <div className="flex items-center gap-3">
                  <Camera className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <p className="font-medium">Face Recognition</p>
                    <p className="text-sm text-muted-foreground">
                      {employee.face_enrolled
                        ? 'Wajah sudah terdaftar untuk absensi'
                        : 'Wajah belum didaftarkan'}
                    </p>
                  </div>
                </div>
                {employee.face_enrolled ? (
                  <Badge className="bg-success/10 text-success">
                    <CheckCircle className="h-3 w-3 mr-1" />
                    Terdaftar
                  </Badge>
                ) : (
                  <Button size="sm">Daftarkan</Button>
                )}
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
