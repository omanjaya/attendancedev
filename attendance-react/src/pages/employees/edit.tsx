import { useState } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import {
  ArrowLeft,
  User,
  Briefcase,
  Loader2,
  Save,
  Mail,
  Phone,
  MapPin,
  Building,
  Calendar,
  ScanFace,
  CheckCircle2,
  XCircle,
  Camera,
  Upload,
  Trash2,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
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
import { useNotificationStore } from '@/stores';
import { FaceEnrollmentFromPhoto, FaceEnrollmentWizard } from '@/components/face-recognition';
import { useFaceData, useDeleteFace } from '@/hooks/use-face-recognition-api';

const employeeSchema = z.object({
  name: z.string().min(2, 'Nama minimal 2 karakter'),
  email: z.string().email('Email tidak valid'),
  phone: z.string().min(10, 'Nomor telepon minimal 10 digit'),
  department_id: z.string().min(1, 'Pilih departemen'),
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

// Mock employee data
const mockEmployee = {
  id: '1',
  name: 'Ahmad Fauzi',
  email: 'ahmad.fauzi@example.com',
  phone: '081234567890',
  department_id: '1',
  position: 'Senior Developer',
  join_date: '2022-01-15',
  address: 'Jl. Sudirman No. 123, Jakarta Selatan',
  status: 'active',
  photo_url: undefined as string | undefined,
};

type EnrollmentMode = 'photo' | 'camera';

export default function EmployeeEditPage() {
  const navigate = useNavigate();
  const { success, error: showError } = useNotificationStore();
  const [isLoading, setIsLoading] = useState(false);
  const [isActive, setIsActive] = useState(mockEmployee.status === 'active');

  // Face enrollment state
  const [isEnrollmentDialogOpen, setIsEnrollmentDialogOpen] = useState(false);
  const [enrollmentMode, setEnrollmentMode] = useState<EnrollmentMode>('photo');

  // Face data hooks
  const { data: faceData, isLoading: isLoadingFaceData, refetch: refetchFaceData } = useFaceData(mockEmployee.id);
  const deleteFaceMutation = useDeleteFace();

  const hasFaceData = faceData?.data?.has_face_data ?? false;

  const handleEnrollmentSuccess = () => {
    setIsEnrollmentDialogOpen(false);
    refetchFaceData();
    success('Berhasil', 'Data wajah berhasil disimpan');
  };

  const handleDeleteFace = async () => {
    if (!confirm('Apakah Anda yakin ingin menghapus data wajah karyawan ini?')) return;

    try {
      await deleteFaceMutation.mutateAsync(mockEmployee.id);
      refetchFaceData();
    } catch (err) {
      showError('Gagal', 'Gagal menghapus data wajah');
    }
  };

  const {
    register,
    handleSubmit,
    setValue,
    formState: { errors },
  } = useForm<EmployeeForm>({
    resolver: zodResolver(employeeSchema),
    defaultValues: {
      name: mockEmployee.name,
      email: mockEmployee.email,
      phone: mockEmployee.phone,
      department_id: mockEmployee.department_id,
      position: mockEmployee.position,
      join_date: mockEmployee.join_date,
      address: mockEmployee.address,
    },
  });

  const onSubmit = async (data: EmployeeForm) => {
    setIsLoading(true);
    try {
      console.log('Updating employee:', { ...data, status: isActive ? 'active' : 'inactive' });
      await new Promise(resolve => setTimeout(resolve, 1000));
      success('Berhasil', 'Data karyawan berhasil diperbarui');
      navigate({ to: '/employees' });
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal memperbarui data karyawan';
      showError('Error', message);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="p-6 max-w-3xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/employees"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar karyawan
        </Link>
        <h1 className="text-2xl font-bold text-foreground">Edit Karyawan</h1>
        <p className="text-sm text-muted-foreground">
          Perbarui informasi karyawan
        </p>
      </div>

      <form onSubmit={handleSubmit(onSubmit)}>
        <div className="space-y-6">
          {/* Personal Info */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <User className="h-5 w-5 text-primary" />
                Informasi Pribadi
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Nama Lengkap <span className="text-destructive">*</span>
                  </label>
                  <Input
                    placeholder="Masukkan nama lengkap"
                    {...register('name')}
                  />
                  {errors.name && (
                    <p className="text-xs text-destructive">{errors.name.message}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Email <span className="text-destructive">*</span>
                  </label>
                  <div className="relative">
                    <Mail className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                      type="email"
                      placeholder="email@example.com"
                      className="pl-10"
                      {...register('email')}
                    />
                  </div>
                  {errors.email && (
                    <p className="text-xs text-destructive">{errors.email.message}</p>
                  )}
                </div>
              </div>

              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Nomor Telepon <span className="text-destructive">*</span>
                  </label>
                  <div className="relative">
                    <Phone className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                      type="tel"
                      placeholder="08xxxxxxxxxx"
                      className="pl-10"
                      {...register('phone')}
                    />
                  </div>
                  {errors.phone && (
                    <p className="text-xs text-destructive">{errors.phone.message}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Tanggal Bergabung <span className="text-destructive">*</span>
                  </label>
                  <div className="relative">
                    <Calendar className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                      type="date"
                      className="pl-10"
                      {...register('join_date')}
                    />
                  </div>
                  {errors.join_date && (
                    <p className="text-xs text-destructive">{errors.join_date.message}</p>
                  )}
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium">Alamat</label>
                <div className="relative">
                  <MapPin className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                  <Textarea
                    placeholder="Masukkan alamat lengkap"
                    className="pl-10 min-h-[80px]"
                    {...register('address')}
                  />
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Employment Info */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Briefcase className="h-5 w-5 text-primary" />
                Informasi Pekerjaan
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Departemen <span className="text-destructive">*</span>
                  </label>
                  <Select
                    defaultValue={mockEmployee.department_id}
                    onValueChange={(value) => setValue('department_id', value)}
                  >
                    <SelectTrigger>
                      <Building className="h-4 w-4 mr-2 text-muted-foreground" />
                      <SelectValue placeholder="Pilih departemen" />
                    </SelectTrigger>
                    <SelectContent>
                      {departments.map((dept) => (
                        <SelectItem key={dept.id} value={dept.id}>
                          {dept.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.department_id && (
                    <p className="text-xs text-destructive">{errors.department_id.message}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Posisi/Jabatan <span className="text-destructive">*</span>
                  </label>
                  <Input
                    placeholder="Contoh: Staff IT"
                    {...register('position')}
                  />
                  {errors.position && (
                    <p className="text-xs text-destructive">{errors.position.message}</p>
                  )}
                </div>
              </div>

              <div className="flex items-center justify-between p-4 rounded-lg bg-muted">
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
            </CardContent>
          </Card>

          {/* Face Recognition */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <ScanFace className="h-5 w-5 text-primary" />
                Face Recognition
              </CardTitle>
              <CardDescription>
                Kelola data wajah untuk verifikasi kehadiran
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {/* Face Status */}
              <div className="flex items-center justify-between p-4 rounded-lg bg-muted">
                <div className="flex items-center gap-3">
                  {isLoadingFaceData ? (
                    <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                  ) : hasFaceData ? (
                    <div className="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                      <CheckCircle2 className="h-6 w-6 text-green-600" />
                    </div>
                  ) : (
                    <div className="h-12 w-12 rounded-full bg-muted-foreground/10 flex items-center justify-center">
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
                  <Badge variant="secondary" className="bg-green-100 text-green-700">
                    Aktif
                  </Badge>
                )}
              </div>

              {/* Enrollment Actions */}
              <div className="flex flex-wrap gap-2">
                <Button
                  type="button"
                  variant={hasFaceData ? 'outline' : 'default'}
                  onClick={() => {
                    setEnrollmentMode('photo');
                    setIsEnrollmentDialogOpen(true);
                  }}
                >
                  <Upload className="h-4 w-4 mr-2" />
                  {hasFaceData ? 'Update dari Foto' : 'Daftar dari Foto'}
                </Button>
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => {
                    setEnrollmentMode('camera');
                    setIsEnrollmentDialogOpen(true);
                  }}
                >
                  <Camera className="h-4 w-4 mr-2" />
                  {hasFaceData ? 'Update via Kamera' : 'Daftar via Kamera'}
                </Button>
                {hasFaceData && (
                  <Button
                    type="button"
                    variant="ghost"
                    className="text-destructive hover:text-destructive"
                    onClick={handleDeleteFace}
                    disabled={deleteFaceMutation.isPending}
                  >
                    {deleteFaceMutation.isPending ? (
                      <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                    ) : (
                      <Trash2 className="h-4 w-4 mr-2" />
                    )}
                    Hapus Data Wajah
                  </Button>
                )}
              </div>

              {/* Info */}
              <p className="text-xs text-muted-foreground">
                Data wajah akan digunakan untuk verifikasi saat check-in dan check-out.
                Pastikan foto yang digunakan menunjukkan wajah dengan jelas.
              </p>
            </CardContent>
          </Card>

          {/* Actions */}
          <div className="flex justify-end gap-3">
            <Button
              type="button"
              variant="outline"
              onClick={() => navigate({ to: '/employees' })}
            >
              Batal
            </Button>
            <Button type="submit" disabled={isLoading}>
              {isLoading ? (
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
              employeeId={mockEmployee.id}
              employeeName={mockEmployee.name}
              existingPhotoUrl={mockEmployee.photo_url}
              onSuccess={handleEnrollmentSuccess}
              onCancel={() => setIsEnrollmentDialogOpen(false)}
              isUpdate={hasFaceData}
            />
          ) : (
            <FaceEnrollmentWizard
              employeeId={mockEmployee.id}
              employeeName={mockEmployee.name}
              onSuccess={handleEnrollmentSuccess}
              onCancel={() => setIsEnrollmentDialogOpen(false)}
              isUpdate={hasFaceData}
            />
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
