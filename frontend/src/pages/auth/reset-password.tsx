import { useState } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Loader2, Eye, EyeOff, KeyRound, CheckCircle } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { LoadingState } from '@/components/states';
import { Input } from '@/components/ui/input';
import { useNotificationStore } from '@/stores';

const resetPasswordSchema = z.object({
  email: z.string().email('Email tidak valid'),
  password: z.string().min(8, 'Password minimal 8 karakter'),
  password_confirmation: z.string(),
}).refine((data) => data.password === data.password_confirmation, {
  message: 'Password tidak cocok',
  path: ['password_confirmation'],
});

type ResetPasswordForm = z.infer<typeof resetPasswordSchema>;

export default function ResetPasswordPage() {
  const navigate = useNavigate();
  const { success } = useNotificationStore();
  const [isLoading, setIsLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<ResetPasswordForm>({
    resolver: zodResolver(resetPasswordSchema),
  });

  const onSubmit = async () => {
    setIsLoading(true);
    try {
      // Demo mode - simulate API call
      await new Promise(resolve => setTimeout(resolve, 1500));
      setIsSuccess(true);
      success('Password Direset', 'Password Anda berhasil diubah.');
      setTimeout(() => {
        navigate({ to: '/login' });
      }, 2000);
    } catch {
      // Handle error
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <section className="bg-muted min-h-screen flex items-center justify-center p-6">
      <div className="w-full max-w-md">
        <div className="flex flex-col items-center gap-6">
          {/* Logo */}
          <div className="flex flex-col items-center gap-2">
            <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary shadow-lg">
              <KeyRound className="h-7 w-7 text-primary-foreground" />
            </div>
            <div className="text-center">
              <h1 className="text-2xl font-bold text-foreground">Password Baru</h1>
              <p className="text-sm text-muted-foreground">
                Buat password baru untuk akun Anda
              </p>
            </div>
          </div>

          {/* Card */}
          <div className="w-full border border-border bg-background rounded-xl px-6 py-8 shadow-lg">
            {isSuccess ? (
              <div className="text-center space-y-4">
                <div className="flex justify-center">
                  <div className="h-16 w-16 rounded-full bg-success/10 flex items-center justify-center">
                    <CheckCircle className="h-8 w-8 text-success" />
                  </div>
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-foreground">Password Berhasil Direset!</h3>
                  <p className="text-sm text-muted-foreground mt-2">
                    Anda akan dialihkan ke halaman login...
                  </p>
                </div>
              </div>
            ) : (
              <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                {/* Email */}
                <div className="space-y-2">
                  <label htmlFor="email" className="text-sm font-medium text-foreground">
                    Email
                  </label>
                  <Input
                    id="email"
                    type="email"
                    placeholder="nama@email.com"
                    className="h-11"
                    {...register('email')}
                  />
                  {errors.email && (
                    <p className="text-xs text-destructive">{errors.email.message}</p>
                  )}
                </div>

                {/* Password */}
                <div className="space-y-2">
                  <label htmlFor="password" className="text-sm font-medium text-foreground">
                    Password Baru
                  </label>
                  <div className="relative">
                    <Input
                      id="password"
                      type={showPassword ? 'text' : 'password'}
                      placeholder="Buat password baru"
                      className="h-11 pr-11"
                      {...register('password')}
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                    >
                      {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </button>
                  </div>
                  {errors.password && (
                    <p className="text-xs text-destructive">{errors.password.message}</p>
                  )}
                </div>

                {/* Confirm Password */}
                <div className="space-y-2">
                  <label htmlFor="password_confirmation" className="text-sm font-medium text-foreground">
                    Konfirmasi Password
                  </label>
                  <div className="relative">
                    <Input
                      id="password_confirmation"
                      type={showConfirmPassword ? 'text' : 'password'}
                      placeholder="Ulangi password baru"
                      className="h-11 pr-11"
                      {...register('password_confirmation')}
                    />
                    <button
                      type="button"
                      onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                    >
                      {showConfirmPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </button>
                  </div>
                  {errors.password_confirmation && (
                    <p className="text-xs text-destructive">{errors.password_confirmation.message}</p>
                  )}
                </div>

                {/* Submit */}
                <Button type="submit" className="w-full h-11" disabled={isLoading}>
                  {isLoading ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Menyimpan...
                    </>
                  ) : (
                    'Reset Password'
                  )}
                </Button>
              </form>
            )}
          </div>

          {/* Back to Login */}
          <Link
            to="/login"
            className="text-sm text-muted-foreground hover:text-foreground transition-colors"
          >
            Kembali ke halaman login
          </Link>
        </div>
      </div>
    </section>
  );
}
