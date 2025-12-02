import { useState, useEffect } from 'react';
import { Link, useNavigate, useParams } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import {
  ArrowLeft,
  Loader2,
  Save,
  CheckCircle,
  ScanFace,
  CheckCircle2,
  XCircle,
  Camera,
  Upload,
  Trash2,
  AlertCircle,
  RefreshCw,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { LoadingState } from '@/components/states';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';
import { useNotificationStore } from '@/stores';
import { useEmployee, useUpdateEmployee } from '@/hooks';
import { FaceEnrollmentFromPhoto, FaceEnrollmentWizard } from '@/components/face-recognition';
import { useFaceData, useDeleteFace } from '@/hooks/use-face-recognition-api';

const employeeSchema = z.object({
  name: z.string().min(2, 'Nama minimal 2 karakter'),
  email: z.string().email('Email tidak valid'),
  phone: z.string().min(10, 'Nomor telepon minimal 10 digit'),
  department: z.string().min(1, 'Pilih departemen'),
  position: z.string().min(2, 'Posisi minimal 2 karakter'),
  join_date: z.string().min(1, 'Pilih tanggal bergabung'),
  address: z.string().optional(),
});

type EmployeeForm = z.infer<typeof employeeSchema>;

const departments = [
  { id: '1', name: 'IT & Development' },
  { id: '2', name: 'Human Resources' },
  { id: '3', name: 'Finance & Accounting' },
  { id: '4', name: 'Marketing' },
  { id: '5', name: 'Operations' },
];

type EnrollmentMode = 'photo' | 'camera';

// Loading skeleton
function EditLoadingSkeleton() {
  return (
    <div className="container py-16">
      <Skeleton className="h-4 w-48 mb-8" />
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-16">
        <div className="space-y-6">
          <Skeleton className="h-12 w-64" />
          <Skeleton className="h-6 w-48" />
          <div className="space-y-4 mt-8">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="flex gap-4">
                <Skeleton className="h-5 w-5 rounded-full" />
                <div className="space-y-2">
                  <Skeleton className="h-4 w-32" />
                  <Skeleton className="h-3 w-48" />
                </div>
              </div>
            ))}
          </div>
        </div>
        <div className="space-y-6 p-6 border rounded-xl">
          <Skeleton className="h-6 w-32" />
          <div className="grid grid-cols-2 gap-4">
            {[1, 2, 3, 4, 5, 6].map((i) => (
              <div key={i} className="space-y-2">
                <Skeleton className="h-4 w-24" />
                <Skeleton className="h-10 w-full" />
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

export default function EmployeeEditPage() {
  const navigate = useNavigate();
  const { id } = useParams({ strict: false }) as { id: string };
  const { success, error: showError } = useNotificationStore();
  const [isActive, setIsActive] = useState(true);

  // Face enrollment state
  const [isEnrollmentDialogOpen, setIsEnrollmentDialogOpen] = useState(false);
  const [enrollmentMode, setEnrollmentMode] = useState<EnrollmentMode>('photo');

  // Fetch employee data
  const {
    data: employee,
    isLoading: isLoadingEmployee,
    error: employeeError,
    refetch: refetchEmployee,
  } = useEmployee(Number(id));

  // Update mutation
  const updateEmployeeMutation = useUpdateEmployee();

  // Face data hooks
  const { data: faceData, isLoading: isLoadingFaceData, refetch: refetchFaceData } = useFaceData(id);
  const deleteFaceMutation = useDeleteFace();

  const hasFaceData = faceData?.data?.has_face_data ?? false;

  const {
    register,
    handleSubmit,
    setValue,
    reset,
    formState: { errors },
  } = useForm<EmployeeForm>({
    resolver: zodResolver(employeeSchema),
  });

  // Populate form when employee data loads
  useEffect(() => {
    if (employee) {
      reset({
        name: employee.name,
        email: employee.email,
        phone: employee.phone || '',
        department: employee.department,
        position: employee.position,
        join_date: employee.join_date,
        address: employee.address || '',
      });
      setIsActive(employee.status === 'active');
    }
  }, [employee, reset]);

  const handleEnrollmentSuccess = () => {
    setIsEnrollmentDialogOpen(false);
    refetchFaceData();
    success('Berhasil', 'Data wajah berhasil disimpan');
  };

  const handleDeleteFace = async () => {
    if (!confirm('Apakah Anda yakin ingin menghapus data wajah karyawan ini?')) return;

    try {
      await deleteFaceMutation.mutateAsync(id);
      refetchFaceData();
    } catch {
      showError('Gagal', 'Gagal menghapus data wajah');
    }
  };

  const onSubmit = async (data: EmployeeForm) => {
    try {
      await updateEmployeeMutation.mutateAsync({
        id: Number(id),
        data: {
          ...data,
          status: isActive ? 'active' : 'inactive',
        },
      });
      navigate({ to: '/employees' });
    } catch {
      // Error handled by mutation hook
    }
  };

  // Checklist items for the left panel
  const infoItems = [
    { label: 'Data Pribadi', description: 'Nama, email, dan nomor telepon karyawan' },
    { label: 'Informasi Pekerjaan', description: 'Departemen, posisi, dan tanggal bergabung' },
    { label: 'Status Karyawan', description: 'Aktifkan atau nonaktifkan akun karyawan' },
    { label: 'Face Recognition', description: 'Kelola data wajah untuk verifikasi absensi' },
  ];

  // Show loading state
  if (isLoadingEmployee) {
    return <EditLoadingSkeleton />;
  }

  // Show error state
  if (employeeError) {
    return (
      <div className="container py-16">
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertTitle>Error</AlertTitle>
          <AlertDescription>
            Gagal memuat data karyawan. {employeeError.message}
          </AlertDescription>
        </Alert>
        <Button onClick={() => refetchEmployee()} className="mt-4">
          <RefreshCw className="mr-2 h-4 w-4" />
          Coba Lagi
        </Button>
      </div>
    );
  }

  if (!employee) {
    return (
      <div className="container py-16">
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
    );
  }

  return (
    <section className="relative py-8 sm:py-16">
      {/* Background pattern - shadcnblocks style */}
      <div className="pointer-events-none absolute inset-x-0 -bottom-20 -top-20 bg-[radial-gradient(ellipse_35%_15%_at_40%_55%,hsl(var(--accent))_0%,transparent_100%)] opacity-50"></div>
      <div className="pointer-events-none absolute inset-x-0 -bottom-20 -top-20 bg-[radial-gradient(ellipse_35%_20%_at_60%_60%,hsl(var(--accent))_0%,transparent_100%)] opacity-30"></div>

      <div className="container relative">
        {/* Back Link */}
        <Link
          to="/admin/employees"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-8"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar karyawan
        </Link>

        <div className="grid w-full grid-cols-1 gap-x-16 overflow-hidden lg:grid-cols-2">
          {/* Left Side - Info Panel */}
          <div className="w-full pb-10 md:space-y-10 md:pb-0">
            <div className="space-y-4 md:space-y-6">
              <div>
                <Badge variant="outline" className="mb-4">Edit Karyawan</Badge>
                <h1 className="text-4xl font-medium lg:text-5xl">{employee.name}</h1>
                <p className="text-lg text-muted-foreground mt-2">NIP: {employee.employee_id}</p>
              </div>

              <div className="space-y-4">
                {infoItems.map((item, index) => (
                  <div key={index} className="flex gap-4">
                    <CheckCircle className="mt-1 h-5 w-5 shrink-0 text-primary" />
                    <div>
                      <p className="font-medium">{item.label}</p>
                      <p className="text-sm text-muted-foreground">{item.description}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Face Recognition Status Card */}
            <div className="mt-10 border-border bg-background rounded-xl border p-6 shadow-sm">
              <div className="flex items-center gap-2 mb-4">
                <ScanFace className="h-5 w-5" />
                <h3 className="text-lg font-semibold">Face Recognition</h3>
              </div>

              <div className="flex items-center justify-between p-4 rounded-lg border mb-4">
                <div className="flex items-center gap-3">
                  {isLoadingFaceData ? (
                    <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                  ) : hasFaceData ? (
                    <div className="h-12 w-12 rounded-full bg-success/10 flex items-center justify-center">
                      <CheckCircle2 className="h-6 w-6 text-success" />
                    </div>
                  ) : (
                    <div className="h-12 w-12 rounded-full bg-muted flex items-center justify-center">
                      <XCircle className="h-6 w-6 text-muted-foreground" />
                    </div>
                  )}
                  <div>
                    <p className="font-medium">
                      {hasFaceData ? 'Wajah Terdaftar' : 'Belum Terdaftar'}
                    </p>
                    <p className="text-sm text-muted-foreground">
                      {hasFaceData
                        ? `Quality: ${faceData?.data?.quality_score ? Math.round(faceData.data.quality_score * 100) : '-'}%`
                        : 'Daftarkan wajah untuk verifikasi absensi'}
                    </p>
                  </div>
                </div>
                {hasFaceData && (
                  <Badge variant="outline" className="border-success/50 text-success">
                    Aktif
                  </Badge>
                )}
              </div>

              <div className="flex flex-wrap gap-2">
                <Button
                  type="button"
                  variant={hasFaceData ? 'outline' : 'default'}
                  size="sm"
                  onClick={() => {
                    setEnrollmentMode('photo');
                    setIsEnrollmentDialogOpen(true);
                  }}
                >
                  <Upload className="h-4 w-4 mr-2" />
                  {hasFaceData ? 'Update Foto' : 'Upload Foto'}
                </Button>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => {
                    setEnrollmentMode('camera');
                    setIsEnrollmentDialogOpen(true);
                  }}
                >
                  <Camera className="h-4 w-4 mr-2" />
                  {hasFaceData ? 'Update Kamera' : 'Via Kamera'}
                </Button>
                {hasFaceData && (
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="text-destructive hover:text-destructive"
                    onClick={handleDeleteFace}
                    disabled={deleteFaceMutation.isPending}
                  >
                    {deleteFaceMutation.isPending ? (
                      <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                    ) : (
                      <Trash2 className="h-4 w-4 mr-2" />
                    )}
                    Hapus
                  </Button>
                )}
              </div>
            </div>
          </div>

          {/* Right Side - Form */}
          <div className="border-border bg-background w-full space-y-6 rounded-xl border px-6 py-8 shadow-sm">
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
              {/* Personal Info Section */}
              <div className="space-y-4">
                <h3 className="font-semibold text-lg">Data Pribadi</h3>

                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="name">
                      Nama Lengkap <span className="text-destructive">*</span>
                    </Label>
                    <Input
                      id="name"
                      placeholder="Masukkan nama lengkap"
                      {...register('name')}
                    />
                    {errors.name && (
                      <p className="text-xs text-destructive">{errors.name.message}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="email">
                      Email <span className="text-destructive">*</span>
                    </Label>
                    <Input
                      id="email"
                      type="email"
                      placeholder="email@example.com"
                      {...register('email')}
                    />
                    {errors.email && (
                      <p className="text-xs text-destructive">{errors.email.message}</p>
                    )}
                  </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="phone">
                      Nomor Telepon <span className="text-destructive">*</span>
                    </Label>
                    <Input
                      id="phone"
                      type="tel"
                      placeholder="08xxxxxxxxxx"
                      {...register('phone')}
                    />
                    {errors.phone && (
                      <p className="text-xs text-destructive">{errors.phone.message}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="join_date">
                      Tanggal Bergabung <span className="text-destructive">*</span>
                    </Label>
                    <Input
                      id="join_date"
                      type="date"
                      {...register('join_date')}
                    />
                    {errors.join_date && (
                      <p className="text-xs text-destructive">{errors.join_date.message}</p>
                    )}
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="address">Alamat</Label>
                  <Textarea
                    id="address"
                    placeholder="Masukkan alamat lengkap"
                    className="min-h-[80px]"
                    {...register('address')}
                  />
                </div>
              </div>

              {/* Divider */}
              <div className="border-t" />

              {/* Employment Info Section */}
              <div className="space-y-4">
                <h3 className="font-semibold text-lg">Informasi Pekerjaan</h3>

                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="department">
                      Departemen <span className="text-destructive">*</span>
                    </Label>
                    <Select
                      defaultValue={employee.department}
                      onValueChange={(value) => setValue('department', value)}
                    >
                      <SelectTrigger id="department">
                        <SelectValue placeholder="Pilih departemen" />
                      </SelectTrigger>
                      <SelectContent>
                        {departments.map((dept) => (
                          <SelectItem key={dept.id} value={dept.name}>
                            {dept.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    {errors.department && (
                      <p className="text-xs text-destructive">{errors.department.message}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="position">
                      Posisi/Jabatan <span className="text-destructive">*</span>
                    </Label>
                    <Input
                      id="position"
                      placeholder="Contoh: Staff IT"
                      {...register('position')}
                    />
                    {errors.position && (
                      <p className="text-xs text-destructive">{errors.position.message}</p>
                    )}
                  </div>
                </div>

                {/* Status Toggle */}
                <div className="flex items-center justify-between p-4 rounded-lg border">
                  <div>
                    <p className="font-medium">Status Karyawan</p>
                    <p className="text-sm text-muted-foreground">
                      {isActive ? 'Karyawan aktif dan dapat melakukan absensi' : 'Karyawan tidak aktif'}
                    </p>
                  </div>
                  <Switch
                    checked={isActive}
                    onCheckedChange={setIsActive}
                  />
                </div>
              </div>

              {/* Actions */}
              <div className="flex justify-end gap-3 pt-4">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => navigate({ to: '/employees' })}
                >
                  Batal
                </Button>
                <Button type="submit" disabled={updateEmployeeMutation.isPending}>
                  {updateEmployeeMutation.isPending ? (
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
            </form>
          </div>
        </div>
      </div>

      {/* Face Enrollment Dialog */}
      <Dialog open={isEnrollmentDialogOpen} onOpenChange={setIsEnrollmentDialogOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <ScanFace className="h-5 w-5" />
              {hasFaceData ? 'Update Data Wajah' : 'Daftarkan Wajah'}
            </DialogTitle>
            <DialogDescription>
              {enrollmentMode === 'photo'
                ? 'Upload foto untuk mendaftarkan wajah'
                : 'Gunakan kamera untuk mengambil foto wajah'}
            </DialogDescription>
          </DialogHeader>

          {enrollmentMode === 'photo' ? (
            <FaceEnrollmentFromPhoto
              employeeId={id}
              employeeName={employee.name}
              existingPhotoUrl={employee.avatar}
              onSuccess={handleEnrollmentSuccess}
              onCancel={() => setIsEnrollmentDialogOpen(false)}
              isUpdate={hasFaceData}
            />
          ) : (
            <FaceEnrollmentWizard
              employeeId={id}
              employeeName={employee.name}
              onSuccess={handleEnrollmentSuccess}
              onCancel={() => setIsEnrollmentDialogOpen(false)}
              isUpdate={hasFaceData}
            />
          )}
        </DialogContent>
      </Dialog>
    </section>
  );
}
