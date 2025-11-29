import { useState } from 'react';
import { Link } from '@tanstack/react-router';
import {
  ArrowLeft,
  ArrowRight,
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
  nip: 'EMP2022001',
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

// Stats data for Stats8 style
const stats = [
  { id: 'stat-1', value: `${mockEmployee.attendance_rate}%`, label: 'tingkat kehadiran bulan ini' },
  { id: 'stat-2', value: `${mockEmployee.total_present}`, label: 'hari hadir dalam bulan ini' },
  { id: 'stat-3', value: `${mockEmployee.total_late}`, label: 'kali terlambat bulan ini' },
  { id: 'stat-4', value: `${mockEmployee.total_leave}`, label: 'hari cuti digunakan' },
];

export default function EmployeeShowPage() {
  const [activeTab, setActiveTab] = useState('overview');
  const employee = mockEmployee;
  const id = '1';

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'present':
        return <Badge variant="outline">Hadir</Badge>;
      case 'late':
        return <Badge variant="outline" className="border-amber-500 text-amber-600">Terlambat</Badge>;
      case 'leave':
        return <Badge variant="outline" className="border-blue-500 text-blue-600">Cuti</Badge>;
      default:
        return <Badge variant="outline">{status}</Badge>;
    }
  };

  return (
    <section className="py-16">
      <div className="container">
        {/* Back Link */}
        <Link
          to="/employees"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-8"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar karyawan
        </Link>

        {/* Profile Header - shadcnblocks style */}
        <div className="flex flex-col md:flex-row md:items-start gap-8 mb-16">
          <Avatar className="h-28 w-28 border-4 border-background shadow-lg">
            <AvatarImage src="" />
            <AvatarFallback className="text-3xl">
              {employee.name.split(' ').map(n => n[0]).join('')}
            </AvatarFallback>
          </Avatar>

          <div className="flex-1 space-y-4">
            <div>
              <div className="flex flex-wrap items-center gap-3 mb-2">
                <h1 className="text-4xl font-bold">{employee.name}</h1>
                <Badge variant={employee.status === 'active' ? 'default' : 'secondary'}>
                  {employee.status === 'active' ? 'Aktif' : 'Tidak Aktif'}
                </Badge>
                {employee.face_enrolled && (
                  <Badge variant="outline">
                    <Camera className="h-3 w-3 mr-1" />
                    Face Enrolled
                  </Badge>
                )}
              </div>
              <p className="text-xl text-muted-foreground">{employee.position}</p>
              <p className="text-muted-foreground">{employee.department} • {employee.nip}</p>
            </div>

            <div className="flex gap-3">
              <Button variant="outline" asChild>
                <a href={`/employees/${id}/edit`}>
                  <Edit className="h-4 w-4 mr-2" />
                  Edit Profil
                </a>
              </Button>
              <AlertDialog>
                <AlertDialogTrigger asChild>
                  <Button variant="outline" className="text-destructive hover:text-destructive">
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
        </div>

        {/* Stats Section - shadcnblocks Stats8 style */}
        <div className="mb-16">
          <div className="flex flex-col gap-4 mb-10">
            <h2 className="text-2xl font-bold md:text-3xl">Statistik Kehadiran</h2>
            <p className="text-muted-foreground">Ringkasan performa kehadiran bulan ini</p>
            <a
              href={`/employees/${id}/attendance`}
              className="flex items-center gap-1 font-bold hover:underline w-fit"
            >
              Lihat riwayat lengkap
              <ArrowRight className="h-auto w-4" />
            </a>
          </div>
          <div className="grid gap-x-5 gap-y-8 md:grid-cols-2 lg:grid-cols-4">
            {stats.map((stat) => (
              <div key={stat.id} className="flex flex-col gap-4">
                <div className="text-5xl font-bold md:text-6xl">{stat.value}</div>
                <p className="text-muted-foreground">{stat.label}</p>
              </div>
            ))}
          </div>
        </div>

        {/* Tabs Content */}
        <Tabs value={activeTab} onValueChange={setActiveTab}>
          <TabsList className="mb-8">
            <TabsTrigger value="overview">Overview</TabsTrigger>
            <TabsTrigger value="attendance">Kehadiran</TabsTrigger>
            <TabsTrigger value="security">Keamanan</TabsTrigger>
          </TabsList>

          <TabsContent value="overview">
            <div className="grid gap-8 md:grid-cols-2">
              {/* Personal Info - shadcnblocks style */}
              <div className="border-border bg-background rounded-xl border p-6 shadow-sm">
                <div className="flex items-center gap-2 mb-6">
                  <User className="w-5 h-5" />
                  <h3 className="text-lg font-semibold">Informasi Pribadi</h3>
                </div>
                <div className="space-y-5">
                  <div className="flex items-start gap-4">
                    <Mail className="h-5 w-5 text-muted-foreground mt-0.5" />
                    <div>
                      <p className="text-sm text-muted-foreground">Email</p>
                      <p>{employee.email}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-4">
                    <Phone className="h-5 w-5 text-muted-foreground mt-0.5" />
                    <div>
                      <p className="text-sm text-muted-foreground">Telepon</p>
                      <p>{employee.phone}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-4">
                    <MapPin className="h-5 w-5 text-muted-foreground mt-0.5" />
                    <div>
                      <p className="text-sm text-muted-foreground">Alamat</p>
                      <p>{employee.address}</p>
                    </div>
                  </div>
                </div>
              </div>

              {/* Employment Info */}
              <div className="border-border bg-background rounded-xl border p-6 shadow-sm">
                <div className="flex items-center gap-2 mb-6">
                  <Briefcase className="w-5 h-5" />
                  <h3 className="text-lg font-semibold">Informasi Pekerjaan</h3>
                </div>
                <div className="space-y-5">
                  <div className="flex items-start gap-4">
                    <Building className="h-5 w-5 text-muted-foreground mt-0.5" />
                    <div>
                      <p className="text-sm text-muted-foreground">Departemen</p>
                      <p>{employee.department}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-4">
                    <Briefcase className="h-5 w-5 text-muted-foreground mt-0.5" />
                    <div>
                      <p className="text-sm text-muted-foreground">Posisi</p>
                      <p>{employee.position}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-4">
                    <Calendar className="h-5 w-5 text-muted-foreground mt-0.5" />
                    <div>
                      <p className="text-sm text-muted-foreground">Tanggal Bergabung</p>
                      <p>
                        {new Date(employee.join_date).toLocaleDateString('id-ID', {
                          day: 'numeric',
                          month: 'long',
                          year: 'numeric',
                        })}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </TabsContent>

          <TabsContent value="attendance">
            <div className="border-border bg-background rounded-xl border shadow-sm overflow-hidden">
              <div className="p-6 border-b">
                <h3 className="text-lg font-semibold">Kehadiran Terbaru</h3>
              </div>
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="border-b bg-muted/50">
                      <th className="px-6 py-4 text-left text-sm font-medium text-muted-foreground">Tanggal</th>
                      <th className="px-6 py-4 text-left text-sm font-medium text-muted-foreground">Check In</th>
                      <th className="px-6 py-4 text-left text-sm font-medium text-muted-foreground">Check Out</th>
                      <th className="px-6 py-4 text-left text-sm font-medium text-muted-foreground">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {recentAttendance.map((record, index) => (
                      <tr key={index} className="border-b last:border-0">
                        <td className="px-6 py-4">
                          {new Date(record.date).toLocaleDateString('id-ID', {
                            weekday: 'short',
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric',
                          })}
                        </td>
                        <td className="px-6 py-4 font-mono">{record.check_in}</td>
                        <td className="px-6 py-4 font-mono">{record.check_out}</td>
                        <td className="px-6 py-4">{getStatusBadge(record.status)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </TabsContent>

          <TabsContent value="security">
            <div className="border-border bg-background rounded-xl border p-6 shadow-sm">
              <div className="flex items-center gap-2 mb-6">
                <Shield className="w-5 h-5" />
                <h3 className="text-lg font-semibold">Keamanan & Verifikasi</h3>
              </div>
              <div className="flex items-center justify-between p-4 rounded-lg border">
                <div className="flex items-center gap-4">
                  <Camera className="h-6 w-6 text-muted-foreground" />
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
                  <Badge variant="outline" className="border-green-500 text-green-600">
                    <CheckCircle className="h-3 w-3 mr-1" />
                    Terdaftar
                  </Badge>
                ) : (
                  <Button>Daftarkan Wajah</Button>
                )}
              </div>
            </div>
          </TabsContent>
        </Tabs>
      </div>
    </section>
  );
}
