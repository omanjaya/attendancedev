import { useState } from 'react';
import { useNavigate } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Loader2, Eye, EyeOff, KeyRound, Shield, CheckCircle2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useAuthStore, useNotificationStore } from '@/stores';
import apiClient from '@/lib/api/client';
import { getDefaultRedirect } from '@/lib/auth';

// Form validation schema
const changePasswordSchema = z.object({
  current_password: z.string().min(1, 'Password saat ini harus diisi'),
  new_password: z.string().min(8, 'Password baru minimal 8 karakter'),
  confirm_password: z.string().min(1, 'Konfirmasi password harus diisi'),
}).refine((data) => data.new_password === data.confirm_password, {
  message: 'Password tidak cocok',
  path: ['confirm_password'],
});

type ChangePasswordForm = z.infer<typeof changePasswordSchema>;

export default function ChangePasswordPage() {
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const { success, error: showError } = useNotificationStore();
  const [isLoading, setIsLoading] = useState(false);
  const [showCurrentPassword, setShowCurrentPassword] = useState(false);
  const [showNewPassword, setShowNewPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<ChangePasswordForm>({
    resolver: zodResolver(changePasswordSchema),
  });

  const onSubmit = async (data: ChangePasswordForm) => {
    setIsLoading(true);
    try {
      await apiClient.post('/auth/change-password', {
        current_password: data.current_password,
        password: data.new_password,
        password_confirmation: data.confirm_password,
      });

      success('Berhasil!', 'Password berhasil diubah. Silakan login kembali dengan password baru.');

      // Backend already revokes all tokens, so we just need to clear local state
      // Don't call logout() API as token is already invalid
      setTimeout(() => {
        // Clear auth state directly without API call
        useAuthStore.getState().reset();
        // Use window.location for hard redirect to ensure clean state
        window.location.href = '/login';
      }, 1500);
    } catch (err: any) {
      const message = err.response?.data?.message || 'Gagal mengubah password';
      showError('Gagal', message);
      setIsLoading(false);
    }
  };

  const handleCancel = async () => {
    // If must change password, logout instead of cancel
    if (user?.force_password_change) {
      try {
        // Try to call logout API, but it may fail if token already invalid
        const { logout } = useAuthStore.getState();
        await logout();
      } catch {
        // If logout API fails, just reset local state
        useAuthStore.getState().reset();
      }
      window.location.href = '/login';
    } else {
      navigate({ to: getDefaultRedirect(user) });
    }
  };

  return (
    <section className="bg-muted min-h-screen">
      <div className="flex min-h-screen items-center justify-center p-6">
        <div className="w-full max-w-md space-y-6">
          {/* Header */}
          <div className="flex flex-col items-center gap-4">
            <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary shadow-lg">
              <KeyRound className="h-7 w-7 text-primary-foreground" />
            </div>
            <div className="text-center">
              <h1 className="text-2xl font-bold text-foreground">Ubah Password</h1>
              <p className="text-sm text-muted-foreground">
                {user?.force_password_change
                  ? 'Anda harus mengubah password untuk melanjutkan'
                  : 'Buat password baru untuk akun Anda'}
              </p>
            </div>
          </div>

          {/* Warning Alert for first-time login */}
          {user?.force_password_change && (
            <Alert>
              <Shield className="h-4 w-4" />
              <AlertDescription>
                Untuk keamanan akun, Anda harus mengubah password yang diberikan oleh admin.
                Password baru minimal 8 karakter.
              </AlertDescription>
            </Alert>
          )}

          {/* Form Card */}
          <div className="w-full border border-border bg-background rounded-xl px-6 py-8 shadow-lg">
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
              {/* Current Password */}
              <div className="space-y-2">
                <Label htmlFor="current_password">Password Saat Ini</Label>
                <div className="relative">
                  <Input
                    id="current_password"
                    type={showCurrentPassword ? 'text' : 'password'}
                    placeholder="Masukkan password saat ini"
                    className="h-11 pr-11"
                    {...register('current_password')}
                  />
                  <button
                    type="button"
                    onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                  >
                    {showCurrentPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </button>
                </div>
                {errors.current_password && (
                  <p className="text-xs text-destructive">{errors.current_password.message}</p>
                )}
              </div>

              {/* New Password */}
              <div className="space-y-2">
                <Label htmlFor="new_password">Password Baru</Label>
                <div className="relative">
                  <Input
                    id="new_password"
                    type={showNewPassword ? 'text' : 'password'}
                    placeholder="Minimal 8 karakter"
                    className="h-11 pr-11"
                    {...register('new_password')}
                  />
                  <button
                    type="button"
                    onClick={() => setShowNewPassword(!showNewPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                  >
                    {showNewPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </button>
                </div>
                {errors.new_password && (
                  <p className="text-xs text-destructive">{errors.new_password.message}</p>
                )}
              </div>

              {/* Confirm Password */}
              <div className="space-y-2">
                <Label htmlFor="confirm_password">Konfirmasi Password Baru</Label>
                <div className="relative">
                  <Input
                    id="confirm_password"
                    type={showConfirmPassword ? 'text' : 'password'}
                    placeholder="Ulangi password baru"
                    className="h-11 pr-11"
                    {...register('confirm_password')}
                  />
                  <button
                    type="button"
                    onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                  >
                    {showConfirmPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </button>
                </div>
                {errors.confirm_password && (
                  <p className="text-xs text-destructive">{errors.confirm_password.message}</p>
                )}
              </div>

              {/* Password Requirements */}
              <div className="rounded-lg bg-muted/50 p-4">
                <p className="text-xs font-medium text-foreground mb-2">Password harus memenuhi:</p>
                <ul className="text-xs text-muted-foreground space-y-1">
                  <li className="flex items-center gap-2">
                    <CheckCircle2 className="h-3 w-3" />
                    Minimal 8 karakter
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle2 className="h-3 w-3" />
                    Kombinasi huruf besar, kecil, angka, dan simbol (disarankan)
                  </li>
                </ul>
              </div>

              {/* Buttons */}
              <div className="flex flex-col gap-3 pt-2">
                <Button type="submit" className="w-full h-11" disabled={isLoading}>
                  {isLoading ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Mengubah Password...
                    </>
                  ) : (
                    'Ubah Password'
                  )}
                </Button>
                {!user?.force_password_change && (
                  <Button
                    type="button"
                    variant="outline"
                    className="w-full h-11"
                    onClick={handleCancel}
                    disabled={isLoading}
                  >
                    Batal
                  </Button>
                )}
              </div>
            </form>
          </div>

          {user?.force_password_change && (
            <p className="text-xs text-muted-foreground text-center">
              Tidak bisa mengubah password?{' '}
              <button
                onClick={handleCancel}
                className="text-primary font-medium hover:underline"
              >
                Logout
              </button>
            </p>
          )}
        </div>
      </div>
    </section>
  );
}
