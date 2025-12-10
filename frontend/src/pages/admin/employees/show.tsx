import { useState } from 'react';
import { Link, useParams, useNavigate } from '@tanstack/react-router';
import { useQuery } from '@tanstack/react-query';
import { motion } from 'framer-motion';
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
  Clock,
  CalendarDays,
  XCircle,
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
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';
import { useEmployee, useDeleteEmployee } from '@/hooks';
import { getEmployeeDashboardById, type EmployeeDashboardData } from '@/lib/api/employees';

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
  } = useEmployee(id);

  // Fetch employee dashboard data (attendance stats, etc)
  const {
    data: dashboardData,
    isLoading: isDashboardLoading,
  } = useQuery<EmployeeDashboardData>({
    queryKey: ['employee-dashboard', id],
    queryFn: () => getEmployeeDashboardById(id),
    enabled: !!id && !!employee,
    staleTime: 60000, // 1 minute
  });

  // Delete mutation
  const deleteEmployeeMutation = useDeleteEmployee();

  const handleDelete = async () => {
    try {
      await deleteEmployeeMutation.mutateAsync(id);
      navigate({ to: '/admin/employees' });
    } catch {
      // Error handled by mutation
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'present':
        return <Badge variant="outline" className="border-success/50 text-success bg-success/10">Hadir</Badge>;
      case 'late':
        return <Badge variant="outline" className="border-warning/50 text-warning bg-warning/10">Terlambat</Badge>;
      case 'leave':
        return <Badge variant="outline" className="border-info/50 text-info bg-info/10">Cuti</Badge>;
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
            <Link to="/admin/employees">Kembali ke Daftar</Link>
          </Button>
        </div>
      </section>
    );
  }

  // Calculate attendance percentage
  const attendancePercentage = dashboardData?.attendance
    ? dashboardData.attendance.thisMonth > 0
      ? Math.round((dashboardData.attendance.present / dashboardData.attendance.thisMonth) * 100 * 10) / 10
      : 0
    : 0;

  // Stats data from real API
  const stats = [
    { 
      id: 'stat-1', 
      value: isDashboardLoading ? '-' : `${attendancePercentage}%`, 
      label: 'Kehadiran Bulan Ini', 
      icon: CheckCircle, 
      color: 'text-success' 
    },
    { 
      id: 'stat-2', 
      value: isDashboardLoading ? '-' : String(dashboardData?.attendance?.present || 0), 
      label: 'Hari Hadir', 
      icon: CalendarDays, 
      color: 'text-primary' 
    },
    { 
      id: 'stat-3', 
      value: isDashboardLoading ? '-' : String(dashboardData?.attendance?.late || 0), 
      label: 'Terlambat', 
      icon: Clock, 
      color: 'text-warning' 
    },
    { 
      id: 'stat-4', 
      value: isDashboardLoading ? '-' : String(dashboardData?.leave?.used || 0), 
      label: 'Cuti Digunakan', 
      icon: Briefcase, 
      color: 'text-info' 
    },
  ];

  return (
    <section className="relative py-8 sm:py-16 overflow-hidden min-h-screen">
      {/* Background Gradients */}
      <div className="pointer-events-none absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80">
        <div className="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-primary to-secondary opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]" />
      </div>

      <div className="container relative z-10">
        {/* Back Link */}
        <motion.div
          initial={{ opacity: 0, x: -20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.3 }}
        >
          <Link
            to="/admin/employees"
            className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-8 transition-colors"
          >
            <ArrowLeft className="h-4 w-4 mr-2" />
            Kembali ke daftar karyawan
          </Link>
        </motion.div>

        {/* Profile Header */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5 }}
          className="flex flex-col md:flex-row md:items-start gap-8 mb-12"
        >
          <div className="relative">
            <div className="absolute -inset-1 bg-gradient-to-br from-primary to-accent rounded-full blur opacity-50"></div>
            <Avatar className="relative h-32 w-32 border-4 border-background shadow-xl">
              <AvatarImage src={employee.avatar} className="object-cover" />
              <AvatarFallback className="text-4xl font-bold bg-primary/10 text-primary">
                {employee.name?.split(' ').map(n => n[0]).join('') || '??'}
              </AvatarFallback>
            </Avatar>
            <div className={`absolute bottom-2 right-2 h-6 w-6 rounded-full border-4 border-background ${employee.status === 'active' ? 'bg-success' : 'bg-muted-foreground'}`}></div>
          </div>

          <div className="flex-1 space-y-4">
            <div>
              <div className="flex flex-wrap items-center gap-3 mb-2">
                <h1 className="text-4xl font-bold tracking-tight">{employee.name}</h1>
                <Badge variant={employee.status === 'active' ? 'default' : 'secondary'} className="rounded-full px-3">
                  {employee.status === 'active' ? 'Aktif' : 'Tidak Aktif'}
                </Badge>
                {employee.face_registered && (
                  <Badge variant="outline" className="rounded-full px-3 border-primary/20 bg-primary/5 text-primary">
                    <Camera className="h-3 w-3 mr-1" />
                    Face Enrolled
                  </Badge>
                )}
              </div>
              <p className="text-xl text-muted-foreground font-medium">{employee.position}</p>
              <div className="flex items-center gap-2 text-sm text-muted-foreground mt-1">
                <Building className="h-4 w-4" />
                <span>{employee.department}</span>
                <span>•</span>
                <Badge variant="secondary" className="font-mono text-xs">{employee.employee_id}</Badge>
              </div>
            </div>

            <div className="flex flex-wrap gap-3 pt-2">
              <Button variant="outline" className="shadow-sm hover:bg-accent hover:text-accent-foreground transition-all" asChild>
                <Link to="/admin/employees/$id/edit" params={{ id }}>
                  <Edit className="h-4 w-4 mr-2" />
                  Edit Profil
                </Link>
              </Button>
              <AlertDialog>
                <AlertDialogTrigger asChild>
                  <Button variant="outline" className="text-destructive hover:text-destructive hover:bg-destructive/10 border-destructive/20 shadow-sm transition-all">
                    <Trash2 className="h-4 w-4 mr-2" />
                    Hapus
                  </Button>
                </AlertDialogTrigger>
                <AlertDialogContent>
                  <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Karyawan?</AlertDialogTitle>
                    <AlertDialogDescription>
                      Tindakan ini tidak dapat dibatalkan. Data karyawan <strong>{employee.name}</strong> akan dihapus permanen dari sistem.
                    </AlertDialogDescription>
                  </AlertDialogHeader>
                  <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction
                      onClick={handleDelete}
                      className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                      disabled={deleteEmployeeMutation.isPending}
                    >
                      {deleteEmployeeMutation.isPending ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                      ) : (
                        'Hapus Permanen'
                      )}
                    </AlertDialogAction>
                  </AlertDialogFooter>
                </AlertDialogContent>
              </AlertDialog>
            </div>
          </div>
        </motion.div>

        {/* Stats Section */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5, delay: 0.1 }}
          className="mb-12"
        >
          <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            {stats.map((stat, index) => (
              <motion.div
                key={stat.id}
                initial={{ opacity: 0, scale: 0.9 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ duration: 0.3, delay: 0.1 + index * 0.1 }}
                className="group relative overflow-hidden rounded-xl border bg-card p-6 shadow-sm transition-all hover:shadow-md"
              >
                <div className="flex items-center justify-between space-y-0 pb-2">
                  <p className="text-sm font-medium text-muted-foreground">{stat.label}</p>
                  <stat.icon className={`h-4 w-4 ${stat.color}`} />
                </div>
                <div className="flex items-baseline gap-1">
                  <div className="text-2xl font-bold">{stat.value}</div>
                </div>
                <div className={`absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-transparent via-${stat.color.replace('text-', '')}/50 to-transparent opacity-0 transition-opacity group-hover:opacity-100`} />
              </motion.div>
            ))}
          </div>
        </motion.div>

        {/* Tabs Content */}
        <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-8">
          <TabsList className="w-full justify-start border-b bg-transparent p-0 h-auto rounded-none">
            <TabsTrigger
              value="overview"
              className="rounded-none border-b-2 border-transparent px-4 py-3 data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:shadow-none transition-all"
            >
              Overview
            </TabsTrigger>
            <TabsTrigger
              value="attendance"
              className="rounded-none border-b-2 border-transparent px-4 py-3 data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:shadow-none transition-all"
            >
              Kehadiran
            </TabsTrigger>
            <TabsTrigger
              value="security"
              className="rounded-none border-b-2 border-transparent px-4 py-3 data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:shadow-none transition-all"
            >
              Keamanan
            </TabsTrigger>
          </TabsList>

          <TabsContent value="overview" className="mt-0">
            <motion.div
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.3 }}
              className="grid gap-8 md:grid-cols-2"
            >
              {/* Personal Info */}
              <div className="rounded-xl border bg-card p-6 shadow-sm">
                <div className="flex items-center gap-3 mb-6 pb-4 border-b">
                  <div className="p-2 bg-primary/10 rounded-lg">
                    <User className="w-5 h-5 text-primary" />
                  </div>
                  <h3 className="text-lg font-semibold">Informasi Pribadi</h3>
                </div>
                <div className="space-y-6">
                  <div className="flex items-start gap-4 group">
                    <div className="p-2 rounded-md bg-muted group-hover:bg-primary/5 transition-colors">
                      <Mail className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-muted-foreground mb-1">Email</p>
                      <p className="font-medium">{employee.email}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-4 group">
                    <div className="p-2 rounded-md bg-muted group-hover:bg-primary/5 transition-colors">
                      <Phone className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-muted-foreground mb-1">Telepon</p>
                      <p className="font-medium">{employee.phone || '-'}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-4 group">
                    <div className="p-2 rounded-md bg-muted group-hover:bg-primary/5 transition-colors">
                      <MapPin className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-muted-foreground mb-1">Alamat</p>
                      <p className="font-medium leading-relaxed">{employee.address || '-'}</p>
                    </div>
                  </div>
                </div>
              </div>

              {/* Employment Info */}
              <div className="rounded-xl border bg-card p-6 shadow-sm">
                <div className="flex items-center gap-3 mb-6 pb-4 border-b">
                  <div className="p-2 bg-primary/10 rounded-lg">
                    <Briefcase className="w-5 h-5 text-primary" />
                  </div>
                  <h3 className="text-lg font-semibold">Informasi Pekerjaan</h3>
                </div>
                <div className="space-y-6">
                  <div className="flex items-start gap-4 group">
                    <div className="p-2 rounded-md bg-muted group-hover:bg-primary/5 transition-colors">
                      <Building className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-muted-foreground mb-1">Departemen</p>
                      <p className="font-medium">{employee.department || <span className="text-muted-foreground italic">Belum diatur</span>}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-4 group">
                    <div className="p-2 rounded-md bg-muted group-hover:bg-primary/5 transition-colors">
                      <Briefcase className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-muted-foreground mb-1">Posisi</p>
                      <p className="font-medium">{employee.position || <span className="text-muted-foreground italic">Belum diatur</span>}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-4 group">
                    <div className="p-2 rounded-md bg-muted group-hover:bg-primary/5 transition-colors">
                      <Calendar className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
                    </div>
                    <div>
                      <p className="text-sm font-medium text-muted-foreground mb-1">Tanggal Bergabung</p>
                      <p className="font-medium">
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
            </motion.div>
          </TabsContent>

          <TabsContent value="attendance" className="mt-0">
            <motion.div
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.3 }}
              className="rounded-xl border bg-card shadow-sm overflow-hidden"
            >
              <div className="p-6 border-b flex items-center justify-between">
                <div>
                  <h3 className="text-lg font-semibold">Kehadiran Terbaru</h3>
                  <p className="text-sm text-muted-foreground">Riwayat absensi 5 hari terakhir</p>
                </div>
                <Button variant="outline" size="sm" asChild>
                  <Link to="/admin/attendance" search={{ employee_id: id }}>
                    Lihat Semua
                    <ArrowRight className="ml-2 h-4 w-4" />
                  </Link>
                </Button>
              </div>
              <div className="overflow-x-auto">
                {isDashboardLoading ? (
                  <div className="flex items-center justify-center py-8">
                    <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                  </div>
                ) : !dashboardData?.recent_attendance?.length ? (
                  <div className="py-8 text-center text-muted-foreground">
                    Belum ada data kehadiran
                  </div>
                ) : (
                  <table className="w-full">
                    <thead>
                      <tr className="border-b bg-muted/30">
                        <th className="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Tanggal</th>
                        <th className="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Check In</th>
                        <th className="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Check Out</th>
                        <th className="px-6 py-4 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Status</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y">
                      {dashboardData.recent_attendance.map((record, index) => (
                        <tr key={index} className="hover:bg-muted/30 transition-colors">
                          <td className="px-6 py-4 text-sm font-medium">
                            {new Date(record.date).toLocaleDateString('id-ID', {
                              weekday: 'short',
                              day: 'numeric',
                              month: 'short',
                              year: 'numeric',
                            })}
                          </td>
                          <td className="px-6 py-4 text-sm font-mono text-muted-foreground">
                            {record.check_in}
                            {record.is_late && record.late_minutes > 0 && (
                              <Badge variant="outline" className="ml-2 border-warning/50 text-warning bg-warning/10 text-xs">
                                +{record.late_minutes}m
                              </Badge>
                            )}
                          </td>
                          <td className="px-6 py-4 text-sm font-mono text-muted-foreground">{record.check_out}</td>
                          <td className="px-6 py-4">{getStatusBadge(record.status)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                )}
              </div>
            </motion.div>
          </TabsContent>

          <TabsContent value="security" className="mt-0">
            <motion.div
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.3 }}
              className="rounded-xl border bg-card p-6 shadow-sm"
            >
              <div className="flex items-center gap-3 mb-6 pb-4 border-b">
                <div className="p-2 bg-primary/10 rounded-lg">
                  <Shield className="w-5 h-5 text-primary" />
                </div>
                <h3 className="text-lg font-semibold">Keamanan & Verifikasi</h3>
              </div>

              <div className="flex flex-col sm:flex-row items-center justify-between p-6 rounded-xl border bg-muted/10 gap-6">
                <div className="flex items-center gap-5">
                  <div className={`h-16 w-16 rounded-full flex items-center justify-center ${employee.face_registered ? 'bg-success/10 text-success' : 'bg-muted text-muted-foreground'}`}>
                    {employee.face_registered ? (
                      <CheckCircle className="h-8 w-8" />
                    ) : (
                      <XCircle className="h-8 w-8" />
                    )}
                  </div>
                  <div>
                    <h4 className="font-semibold text-lg">Face Recognition</h4>
                    <p className="text-muted-foreground">
                      {employee.face_registered
                        ? 'Wajah sudah terdaftar dan diverifikasi untuk absensi.'
                        : 'Wajah belum didaftarkan. Karyawan tidak dapat melakukan absensi wajah.'}
                    </p>
                  </div>
                </div>

                {employee.face_registered ? (
                  <div className="flex gap-3">
                    <Badge variant="outline" className="h-9 px-4 border-success/30 text-success bg-success/5 text-sm">
                      Terverifikasi
                    </Badge>
                    <Button variant="outline" asChild>
                      <Link to="/admin/employees/$id/edit" params={{ id }}>
                        Update Wajah
                      </Link>
                    </Button>
                  </div>
                ) : (
                  <Button asChild className="bg-primary hover:bg-primary/90">
                    <Link to="/admin/employees/$id/edit" params={{ id }}>
                      <Camera className="mr-2 h-4 w-4" />
                      Daftarkan Wajah
                    </Link>
                  </Button>
                )}
              </div>
            </motion.div>
          </TabsContent>
        </Tabs>
      </div>
    </section>
  );
}
