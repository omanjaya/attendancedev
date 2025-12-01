import { useState } from 'react';
import { Link, useParams, useNavigate } from '@tanstack/react-router';
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
  Camera,
  Shield,
  AlertCircle,
  RefreshCw,
  Loader2,
  CheckCircle,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { LoadingState } from '@/components/states';
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
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';
import { useEmployee, useDeleteEmployee } from '@/hooks';

// Mock attendance data (TODO: integrate with attendance API)
const recentAttendance = [
  { date: '2024-11-28', check_in: '08:15', check_out: '17:30', status: 'present' },
  { date: '2024-11-27', check_in: '08:45', check_out: '17:00', status: 'late' },
  { date: '2024-11-26', check_in: '08:00', check_out: '17:15', status: 'present' },
  { date: '2024-11-25', check_in: '07:55', check_out: '17:30', status: 'present' },
  { date: '2024-11-24', check_in: '-', check_out: '-', status: 'leave' },
];

// Loading skeleton
function ShowLoadingSkeleton() {
  return (
    <section className="py-8 sm:py-16">
      <div className="container">
        <Skeleton className="h-4 w-48 mb-8" />
        <div className="flex flex-col md:flex-row md:items-start gap-8 mb-16">
          <Skeleton className="h-28 w-28 rounded-full" />
          <div className="flex-1 space-y-4">
            <Skeleton className="h-10 w-64" />
            <Skeleton className="h-6 w-48" />
            <Skeleton className="h-4 w-32" />
            <div className="flex gap-3 mt-4">
              <Skeleton className="h-10 w-32" />
              <Skeleton className="h-10 w-24" />
            </div>
          </div>
        </div>
        <div className="mb-16">
          <Skeleton className="h-8 w-48 mb-4" />
          <div className="grid gap-x-5 gap-y-8 md:grid-cols-2 lg:grid-cols-4">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="flex flex-col gap-4">
                <Skeleton className="h-16 w-24" />
                <Skeleton className="h-4 w-32" />
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

export default function EmployeeShowPage() {
  const { id } = useParams({ strict: false }) as { id: string };
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('overview');

  // Fetch employee data
  const {
    data: employee,
    isLoading,
    error,
    refetch,
  } = useEmployee(Number(id));

  // Delete mutation
  const deleteEmployeeMutation = useDeleteEmployee();

  const handleDelete = async () => {
    try {
      await deleteEmployeeMutation.mutateAsync(Number(id));
      navigate({ to: '/employees' });
    } catch {
      // Error handled by mutation
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'present':
        return <Badge variant="outline" className="border-success/50 text-success">Hadir</Badge>;
      case 'late':
        return <Badge variant="outline" className="border-warning/50 text-warning">Terlambat</Badge>;
      case 'leave':
        return <Badge variant="outline" className="border-info/50 text-info">Cuti</Badge>;
      default:
        return <Badge variant="outline">{status}</Badge>;
    }
  };

  // Show loading state
  if (isLoading) {
    return <ShowLoadingSkeleton />;
  }

  // Show error state
  if (error) {
    return (
      <section className="py-8 sm:py-16">
        <div className="container">
          <Alert variant="destructive">
            <AlertCircle className="h-4 w-4" />
            <AlertTitle>Error</AlertTitle>
            <AlertDescription>
              Gagal memuat data karyawan. {error.message}
            </AlertDescription>
          </Alert>
          <Button onClick={() => refetch()} className="mt-4">
            <RefreshCw className="mr-2 h-4 w-4" />
            Coba Lagi
          </Button>
        </div>
      </section>
    );
  }

  if (!employee) {
    return (
      <section className="py-8 sm:py-16">
        <div className="container">
          <Alert variant="destructive">
            <AlertCircle className="h-4 w-4" />
            <AlertTitle>Tidak Ditemukan</AlertTitle>
            <AlertDescription>
              Karyawan dengan ID {id} tidak ditemukan.
            </AlertDescription>
          </Alert>
          <Button asChild className="mt-4">
            <Link to="/employees">Kembali ke Daftar</Link>
          </Button>
        </div>
      </section>
    );
  }

  // Stats data for Stats8 style
  const stats = [
    { id: 'stat-1', value: '95.6%', label: 'tingkat kehadiran bulan ini' },
    { id: 'stat-2', value: '22', label: 'hari hadir dalam bulan ini' },
    { id: 'stat-3', value: '2', label: 'kali terlambat bulan ini' },
    { id: 'stat-4', value: '3', label: 'hari cuti digunakan' },
  ];

  return (
    <section className="py-8 sm:py-16">
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
            <AvatarImage src={employee.avatar} />
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
                {employee.face_registered && (
                  <Badge variant="outline">
                    <Camera className="h-3 w-3 mr-1" />
                    Face Enrolled
                  </Badge>
                )}
              </div>
              <p className="text-xl text-muted-foreground">{employee.position}</p>
              <p className="text-muted-foreground">{employee.department} • {employee.employee_id}</p>
            </div>

            <div className="flex gap-3">
              <Button variant="outline" asChild>
                <Link to="/employees/$id/edit" params={{ id }}>
                  <Edit className="h-4 w-4 mr-2" />
                  Edit Profil
                </Link>
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
                    <AlertDialogAction
                      onClick={handleDelete}
                      className="bg-destructive text-destructive-foreground"
                      disabled={deleteEmployeeMutation.isPending}
                    >
                      {deleteEmployeeMutation.isPending ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                      ) : (
                        'Hapus'
                      )}
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
                      <p>{employee.phone || '-'}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-4">
                    <MapPin className="h-5 w-5 text-muted-foreground mt-0.5" />
                    <div>
                      <p className="text-sm text-muted-foreground">Alamat</p>
                      <p>{employee.address || '-'}</p>
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
              <div className="p-4 sm:p-6 border-b">
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
                      {employee.face_registered
                        ? 'Wajah sudah terdaftar untuk absensi'
                        : 'Wajah belum didaftarkan'}
                    </p>
                  </div>
                </div>
                {employee.face_registered ? (
                  <Badge variant="outline" className="border-success/50 text-success">
                    <CheckCircle className="h-3 w-3 mr-1" />
                    Terdaftar
                  </Badge>
                ) : (
                  <Button asChild>
                    <Link to="/employees/$id/edit" params={{ id }}>
                      Daftarkan Wajah
                    </Link>
                  </Button>
                )}
              </div>
            </div>
          </TabsContent>
        </Tabs>
      </div>
    </section>
  );
}
