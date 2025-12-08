import { useState } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import {
  ArrowLeft,
  User,
  Shield,
  Loader2,
  Save,
  Mail,
  Lock,
  Eye,
  EyeOff,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { useNotificationStore } from '@/stores';

const userSchema = z.object({
  name: z.string().min(2, 'Nama minimal 2 karakter'),
  email: z.string().email('Email tidak valid'),
  password: z.string().min(8, 'Password minimal 8 karakter'),
  password_confirmation: z.string(),
  role: z.string().min(1, 'Pilih role'),
}).refine((data) => data.password === data.password_confirmation, {
  message: 'Konfirmasi password tidak cocok',
  path: ['password_confirmation'],
});

type UserForm = z.infer<typeof userSchema>;

const roles = [
  { id: 'super-admin', name: 'Super Admin', description: 'Akses penuh ke semua fitur' },
  { id: 'admin', name: 'Admin', description: 'Kelola karyawan dan absensi' },
  { id: 'hr', name: 'HR Manager', description: 'Kelola cuti dan payroll' },
  { id: 'manager', name: 'Manager', description: 'Lihat laporan tim' },
  { id: 'employee', name: 'Karyawan', description: 'Absensi dan lihat data sendiri' },
];

const permissions = [
  { id: 'dashboard.view', label: 'Lihat Dashboard' },
  { id: 'employees.view', label: 'Lihat Karyawan' },
  { id: 'employees.create', label: 'Tambah Karyawan' },
  { id: 'employees.edit', label: 'Edit Karyawan' },
  { id: 'employees.delete', label: 'Hapus Karyawan' },
  { id: 'attendance.view', label: 'Lihat Absensi' },
  { id: 'attendance.manage', label: 'Kelola Absensi' },
  { id: 'leave.view', label: 'Lihat Cuti' },
  { id: 'leave.approve', label: 'Approve Cuti' },
  { id: 'payroll.view', label: 'Lihat Payroll' },
  { id: 'payroll.manage', label: 'Kelola Payroll' },
  { id: 'reports.view', label: 'Lihat Laporan' },
  { id: 'settings.manage', label: 'Kelola Pengaturan' },
];

export default function UserCreatePage() {
  const navigate = useNavigate();
  const { success, error: showError } = useNotificationStore();
  const [isLoading, setIsLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [selectedPermissions, setSelectedPermissions] = useState<string[]>([]);

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
  } = useForm<UserForm>({
    resolver: zodResolver(userSchema),
  });

  const selectedRole = watch('role');

  const togglePermission = (permissionId: string) => {
    setSelectedPermissions(prev =>
      prev.includes(permissionId)
        ? prev.filter(p => p !== permissionId)
        : [...prev, permissionId]
    );
  };

  const onSubmit = async (data: UserForm) => {
    setIsLoading(true);
    try {
      console.log('Creating user:', { ...data, permissions: selectedPermissions });
      await new Promise(resolve => setTimeout(resolve, 1000));
      success('Berhasil', 'User berhasil dibuat');
      navigate({ to: '/admin/users' });
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal membuat user';
      showError('Error', message);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="p-4 sm:p-6 max-w-3xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/admin/users"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar user
        </Link>
        <h1 className="text-2xl font-bold text-foreground">Tambah User</h1>
        <p className="text-sm text-muted-foreground">
          Buat akun user baru dengan role dan permission
        </p>
      </div>

      <form onSubmit={handleSubmit(onSubmit)}>
        <div className="space-y-6">
          {/* Basic Info */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <User className="h-5 w-5 text-primary" />
                Informasi Akun
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
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

              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Password <span className="text-destructive">*</span>
                  </label>
                  <div className="relative">
                    <Lock className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                      type={showPassword ? 'text' : 'password'}
                      placeholder="Minimal 8 karakter"
                      className="pl-10 pr-10"
                      {...register('password')}
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                    >
                      {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </button>
                  </div>
                  {errors.password && (
                    <p className="text-xs text-destructive">{errors.password.message}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Konfirmasi Password <span className="text-destructive">*</span>
                  </label>
                  <div className="relative">
                    <Lock className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                      type={showConfirmPassword ? 'text' : 'password'}
                      placeholder="Ulangi password"
                      className="pl-10 pr-10"
                      {...register('password_confirmation')}
                    />
                    <button
                      type="button"
                      onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                    >
                      {showConfirmPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </button>
                  </div>
                  {errors.password_confirmation && (
                    <p className="text-xs text-destructive">{errors.password_confirmation.message}</p>
                  )}
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Role */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Shield className="h-5 w-5 text-primary" />
                Role & Akses
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">
                  Role <span className="text-destructive">*</span>
                </label>
                <Select onValueChange={(value) => setValue('role', value)}>
                  <SelectTrigger>
                    <SelectValue placeholder="Pilih role" />
                  </SelectTrigger>
                  <SelectContent>
                    {roles.map((role) => (
                      <SelectItem key={role.id} value={role.id}>
                        <div>
                          <p className="font-medium">{role.name}</p>
                          <p className="text-xs text-muted-foreground">{role.description}</p>
                        </div>
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.role && (
                  <p className="text-xs text-destructive">{errors.role.message}</p>
                )}
              </div>

              {selectedRole && (
                <div className="space-y-3">
                  <label className="text-sm font-medium">Permission Tambahan</label>
                  <div className="grid grid-cols-2 gap-2">
                    {permissions.map((permission) => (
                      <label
                        key={permission.id}
                        className={`
                          flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition-colors
                          ${selectedPermissions.includes(permission.id)
                            ? 'bg-primary/10 border-primary'
                            : 'bg-background hover:bg-muted'
                          }
                        `}
                      >
                        <Checkbox
                          checked={selectedPermissions.includes(permission.id)}
                          onCheckedChange={() => togglePermission(permission.id)}
                        />
                        <span className="text-sm">{permission.label}</span>
                      </label>
                    ))}
                  </div>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Actions */}
          <div className="flex justify-end gap-3">
            <Button
              type="button"
              variant="outline"
              onClick={() => navigate({ to: '/admin/users' })}
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
                  Simpan User
                </>
              )}
            </Button>
          </div>
        </div>
      </form>
    </div>
  );
}
