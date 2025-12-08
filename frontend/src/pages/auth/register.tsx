import { useState } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Loader2, Eye, EyeOff, UserPlus, Shield, Users, CheckCircle } from 'lucide-react';

import { Button } from '@/components/ui/button';

import { Input } from '@/components/ui/input';
import { useNotificationStore } from '@/stores';

const registerSchema = z.object({
  name: z.string().min(2, 'Nama minimal 2 karakter'),
  email: z.string().email('Email tidak valid'),
  password: z.string().min(8, 'Password minimal 8 karakter'),
  password_confirmation: z.string(),
}).refine((data) => data.password === data.password_confirmation, {
  message: 'Password tidak cocok',
  path: ['password_confirmation'],
});

type RegisterForm = z.infer<typeof registerSchema>;

export default function RegisterPage() {
  const navigate = useNavigate();
  const { success, error: showError } = useNotificationStore();
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<RegisterForm>({
    resolver: zodResolver(registerSchema),
  });

  const onSubmit = async (_data: RegisterForm) => {
    setIsLoading(true);
    try {
      // Demo mode - simulate registration
      await new Promise(resolve => setTimeout(resolve, 1000));
      success('Registrasi Berhasil', 'Akun Anda telah dibuat. Silakan login.');
      navigate({ to: '/login' });
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Registrasi gagal';
      showError('Registrasi Gagal', message);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <section className="bg-muted min-h-screen">
      <div className="flex min-h-screen">
        {/* Left Panel - Features */}
        <div className="hidden lg:flex lg:w-1/2 items-center justify-center bg-gradient-to-br from-primary/10 via-primary/5 to-background p-12">
          <div className="max-w-md space-y-8">
            <div>
              <h2 className="text-3xl font-bold text-foreground mb-2">
                Bergabung dengan Tim Anda
              </h2>
              <p className="text-muted-foreground">
                Daftar untuk mulai menggunakan sistem absensi modern
              </p>
            </div>

            {/* Benefits */}
            <div className="space-y-4">
              <div className="flex items-start gap-4 p-4 rounded-xl bg-background/80 backdrop-blur border border-border/50">
                <div className="p-2.5 rounded-lg bg-primary/10">
                  <CheckCircle className="h-5 w-5 text-primary" />
                </div>
                <div>
                  <h3 className="font-semibold text-foreground">Mudah Digunakan</h3>
                  <p className="text-sm text-muted-foreground">
                    Interface intuitif untuk semua level pengguna
                  </p>
                </div>
              </div>

              <div className="flex items-start gap-4 p-4 rounded-xl bg-background/80 backdrop-blur border border-border/50">
                <div className="p-2.5 rounded-lg bg-success/10">
                  <Shield className="h-5 w-5 text-success" />
                </div>
                <div>
                  <h3 className="font-semibold text-foreground">Aman & Terpercaya</h3>
                  <p className="text-sm text-muted-foreground">
                    Data Anda dilindungi dengan enkripsi terbaik
                  </p>
                </div>
              </div>

              <div className="flex items-start gap-4 p-4 rounded-xl bg-background/80 backdrop-blur border border-border/50">
                <div className="p-2.5 rounded-lg bg-warning/10">
                  <Users className="h-5 w-5 text-warning" />
                </div>
                <div>
                  <h3 className="font-semibold text-foreground">Kolaborasi Tim</h3>
                  <p className="text-sm text-muted-foreground">
                    Kelola tim dengan mudah dan efisien
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Right Panel - Register Form */}
        <div className="flex w-full lg:w-1/2 items-center justify-center p-6">
          <div className="flex flex-col items-center gap-6 w-full max-w-sm">
            {/* Logo */}
            <div className="flex flex-col items-center gap-2">
              <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary shadow-lg">
                <UserPlus className="h-7 w-7 text-primary-foreground" />
              </div>
              <div className="text-center">
                <h1 className="text-2xl font-bold text-foreground">Buat Akun</h1>
                <p className="text-sm text-muted-foreground">Daftar untuk memulai</p>
              </div>
            </div>

            {/* Register Card */}
            <div className="w-full border border-border bg-background rounded-xl px-6 py-8 shadow-lg">
              <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                {/* Name */}
                <div className="space-y-2">
                  <label htmlFor="name" className="text-sm font-medium text-foreground">
                    Nama Lengkap
                  </label>
                  <Input
                    id="name"
                    type="text"
                    placeholder="Masukkan nama lengkap"
                    className="h-11"
                    {...register('name')}
                  />
                  {errors.name && (
                    <p className="text-xs text-destructive">{errors.name.message}</p>
                  )}
                </div>

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
                    Password
                  </label>
                  <div className="relative">
                    <Input
                      id="password"
                      type={showPassword ? 'text' : 'password'}
                      placeholder="Buat password yang kuat"
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
                      placeholder="Ulangi password"
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
                      Mendaftar...
                    </>
                  ) : (
                    'Buat Akun'
                  )}
                </Button>
              </form>
            </div>

            {/* Login Link */}
            <p className="text-sm text-muted-foreground">
              Sudah punya akun?{' '}
              <Link to="/login" className="text-primary font-medium hover:underline">
                Masuk
              </Link>
            </p>
          </div>
        </div>
      </div>
    </section>
  );
}
