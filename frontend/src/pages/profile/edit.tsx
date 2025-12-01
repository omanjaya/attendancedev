import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import {
  User,
  Mail,
  Phone,
  MapPin,
  Lock,
  Trash2,
  Loader2,
  Save,
  Eye,
  EyeOff,
  AlertTriangle,
  Camera,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { LoadingState } from '@/components/states';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
import { useNotificationStore, useAuthStore } from '@/stores';

const profileSchema = z.object({
  name: z.string().min(2, 'Nama minimal 2 karakter'),
  email: z.string().email('Email tidak valid'),
  phone: z.string().optional(),
  address: z.string().optional(),
});

const passwordSchema = z.object({
  current_password: z.string().min(1, 'Masukkan password saat ini'),
  password: z.string().min(8, 'Password minimal 8 karakter'),
  password_confirmation: z.string(),
}).refine((data) => data.password === data.password_confirmation, {
  message: 'Konfirmasi password tidak cocok',
  path: ['password_confirmation'],
});

type ProfileForm = z.infer<typeof profileSchema>;
type PasswordForm = z.infer<typeof passwordSchema>;

export default function ProfileEditPage() {
  const { success, error: showError } = useNotificationStore();
  const { user } = useAuthStore();
  const [isProfileLoading, setIsProfileLoading] = useState(false);
  const [isPasswordLoading, setIsPasswordLoading] = useState(false);
  const [showCurrentPassword, setShowCurrentPassword] = useState(false);
  const [showNewPassword, setShowNewPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [deleteConfirmation, setDeleteConfirmation] = useState('');

  const profileForm = useForm<ProfileForm>({
    resolver: zodResolver(profileSchema),
    defaultValues: {
      name: user?.name || '',
      email: user?.email || '',
      phone: '',
      address: '',
    },
  });

  const passwordForm = useForm<PasswordForm>({
    resolver: zodResolver(passwordSchema),
  });

  const onProfileSubmit = async (data: ProfileForm) => {
    setIsProfileLoading(true);
    try {
      console.log('Updating profile:', data);
      await new Promise(resolve => setTimeout(resolve, 1000));
      success('Berhasil', 'Profil berhasil diperbarui');
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal memperbarui profil';
      showError('Error', message);
    } finally {
      setIsProfileLoading(false);
    }
  };

  const onPasswordSubmit = async (_data: PasswordForm) => {
    setIsPasswordLoading(true);
    try {
      console.log('Updating password');
      await new Promise(resolve => setTimeout(resolve, 1000));
      success('Berhasil', 'Password berhasil diperbarui');
      passwordForm.reset();
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal memperbarui password';
      showError('Error', message);
    } finally {
      setIsPasswordLoading(false);
    }
  };

  return (
    <div className="p-4 sm:p-6 max-w-3xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-foreground">Pengaturan Profil</h1>
        <p className="text-sm text-muted-foreground">
          Kelola informasi akun dan keamanan Anda
        </p>
      </div>

      <div className="space-y-6">
        {/* Profile Photo */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <Camera className="h-5 w-5 text-primary" />
              Foto Profil
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-6">
              <Avatar className="h-24 w-24">
                <AvatarImage src="" />
                <AvatarFallback className="text-2xl bg-primary/10 text-primary">
                  {user?.name?.split(' ').map(n => n[0]).join('') || 'U'}
                </AvatarFallback>
              </Avatar>
              <div className="space-y-2">
                <div className="flex gap-2">
                  <Button variant="outline" size="sm">
                    <Camera className="h-4 w-4 mr-2" />
                    Upload Foto
                  </Button>
                  <Button variant="ghost" size="sm" className="text-destructive">
                    Hapus
                  </Button>
                </div>
                <p className="text-xs text-muted-foreground">
                  JPG, PNG maksimal 2MB. Rekomendasi 400x400px.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Profile Information */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <User className="h-5 w-5 text-primary" />
              Informasi Pribadi
            </CardTitle>
            <CardDescription>
              Perbarui informasi dasar akun Anda
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={profileForm.handleSubmit(onProfileSubmit)} className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <label className="text-sm font-medium">Nama Lengkap</label>
                  <Input {...profileForm.register('name')} />
                  {profileForm.formState.errors.name && (
                    <p className="text-xs text-destructive">
                      {profileForm.formState.errors.name.message}
                    </p>
                  )}
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium">Email</label>
                  <div className="relative">
                    <Mail className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input className="pl-10" {...profileForm.register('email')} />
                  </div>
                  {profileForm.formState.errors.email && (
                    <p className="text-xs text-destructive">
                      {profileForm.formState.errors.email.message}
                    </p>
                  )}
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium">Nomor Telepon</label>
                <div className="relative">
                  <Phone className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                  <Input className="pl-10" {...profileForm.register('phone')} />
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium">Alamat</label>
                <div className="relative">
                  <MapPin className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                  <Textarea className="pl-10 min-h-[80px]" {...profileForm.register('address')} />
                </div>
              </div>

              <div className="flex justify-end">
                <Button type="submit" disabled={isProfileLoading}>
                  {isProfileLoading ? (
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
          </CardContent>
        </Card>

        {/* Change Password */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <Lock className="h-5 w-5 text-primary" />
              Ubah Password
            </CardTitle>
            <CardDescription>
              Pastikan password Anda kuat dan aman
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={passwordForm.handleSubmit(onPasswordSubmit)} className="space-y-4">
              <div className="space-y-2">
                <label className="text-sm font-medium">Password Saat Ini</label>
                <div className="relative">
                  <Input
                    type={showCurrentPassword ? 'text' : 'password'}
                    {...passwordForm.register('current_password')}
                  />
                  <button
                    type="button"
                    onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                  >
                    {showCurrentPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </button>
                </div>
                {passwordForm.formState.errors.current_password && (
                  <p className="text-xs text-destructive">
                    {passwordForm.formState.errors.current_password.message}
                  </p>
                )}
              </div>

              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <label className="text-sm font-medium">Password Baru</label>
                  <div className="relative">
                    <Input
                      type={showNewPassword ? 'text' : 'password'}
                      {...passwordForm.register('password')}
                    />
                    <button
                      type="button"
                      onClick={() => setShowNewPassword(!showNewPassword)}
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                    >
                      {showNewPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </button>
                  </div>
                  {passwordForm.formState.errors.password && (
                    <p className="text-xs text-destructive">
                      {passwordForm.formState.errors.password.message}
                    </p>
                  )}
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium">Konfirmasi Password</label>
                  <div className="relative">
                    <Input
                      type={showConfirmPassword ? 'text' : 'password'}
                      {...passwordForm.register('password_confirmation')}
                    />
                    <button
                      type="button"
                      onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                    >
                      {showConfirmPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </button>
                  </div>
                  {passwordForm.formState.errors.password_confirmation && (
                    <p className="text-xs text-destructive">
                      {passwordForm.formState.errors.password_confirmation.message}
                    </p>
                  )}
                </div>
              </div>

              <div className="flex justify-end">
                <Button type="submit" disabled={isPasswordLoading}>
                  {isPasswordLoading ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Menyimpan...
                    </>
                  ) : (
                    <>
                      <Lock className="mr-2 h-4 w-4" />
                      Update Password
                    </>
                  )}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        {/* Delete Account */}
        <Card className="border-destructive/50">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg text-destructive">
              <Trash2 className="h-5 w-5" />
              Hapus Akun
            </CardTitle>
            <CardDescription>
              Hapus akun Anda secara permanen. Tindakan ini tidak dapat dibatalkan.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="p-4 rounded-lg bg-destructive/10 border border-destructive/20 mb-4">
              <div className="flex items-start gap-3">
                <AlertTriangle className="h-5 w-5 text-destructive mt-0.5" />
                <div>
                  <p className="font-medium text-destructive">Perhatian!</p>
                  <p className="text-sm text-muted-foreground">
                    Menghapus akun akan menghapus semua data termasuk riwayat absensi,
                    data payroll, dan informasi lainnya secara permanen.
                  </p>
                </div>
              </div>
            </div>

            <AlertDialog>
              <AlertDialogTrigger asChild>
                <Button variant="destructive">
                  <Trash2 className="h-4 w-4 mr-2" />
                  Hapus Akun Saya
                </Button>
              </AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>Hapus Akun?</AlertDialogTitle>
                  <AlertDialogDescription>
                    Ketik "HAPUS" untuk mengkonfirmasi penghapusan akun Anda.
                  </AlertDialogDescription>
                </AlertDialogHeader>
                <Input
                  placeholder="Ketik HAPUS"
                  value={deleteConfirmation}
                  onChange={(e) => setDeleteConfirmation(e.target.value)}
                />
                <AlertDialogFooter>
                  <AlertDialogCancel onClick={() => setDeleteConfirmation('')}>
                    Batal
                  </AlertDialogCancel>
                  <AlertDialogAction
                    className="bg-destructive text-destructive-foreground"
                    disabled={deleteConfirmation !== 'HAPUS'}
                  >
                    Hapus Akun
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
