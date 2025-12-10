import { useState } from 'react';
import { useNavigate, Link } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Loader2, Eye, EyeOff, LogIn, Clock, Fingerprint, MapPin } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useAuthStore, useNotificationStore } from '@/stores';
import { getDefaultRedirect } from '@/lib/auth/guards';

// Form validation schema
const loginSchema = z.object({
  email: z.string().email('Email tidak valid'),
  password: z.string().min(6, 'Password minimal 6 karakter'),
  remember: z.boolean().optional(),
});

type LoginForm = z.infer<typeof loginSchema>;

export default function LoginPage() {
  const navigate = useNavigate();
  const { login, isLoading, error } = useAuthStore();
  const { error: showError } = useNotificationStore();
  const [showPassword, setShowPassword] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginForm>({
    resolver: zodResolver(loginSchema),
    defaultValues: {
      email: '',
      password: '',
      remember: false,
    },
  });

  const onSubmit = async (data: LoginForm) => {
    try {
      await login(data);

      // Double check security: Force password change if EITHER condition is true
      const user = useAuthStore.getState().user;
      const mustChangePassword =
        user?.force_password_change === true || // Admin forced password change
        user?.password_changed_at === null;     // User never changed password

      if (mustChangePassword) {
        navigate({ to: '/auth/change-password' });
      } else {
        navigate({ to: getDefaultRedirect(user) });
      }
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Login gagal';
      showError('Login Gagal', message);
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
                Sistem Absensi Modern
              </h2>
              <p className="text-muted-foreground">
                Kelola kehadiran karyawan dengan mudah menggunakan teknologi terkini
              </p>
            </div>

            {/* Feature Cards */}
            <div className="space-y-4">
              <div className="flex items-start gap-4 p-4 rounded-xl bg-background/80 backdrop-blur border border-border/50">
                <div className="p-2.5 rounded-lg bg-primary/10">
                  <Fingerprint className="h-5 w-5 text-primary" />
                </div>
                <div>
                  <h3 className="font-semibold text-foreground">Face Recognition</h3>
                  <p className="text-sm text-muted-foreground">
                    Verifikasi wajah otomatis untuk keamanan absensi
                  </p>
                </div>
              </div>

              <div className="flex items-start gap-4 p-4 rounded-xl bg-background/80 backdrop-blur border border-border/50">
                <div className="p-2.5 rounded-lg bg-success/10">
                  <MapPin className="h-5 w-5 text-success" />
                </div>
                <div>
                  <h3 className="font-semibold text-foreground">GPS Verification</h3>
                  <p className="text-sm text-muted-foreground">
                    Validasi lokasi untuk memastikan kehadiran di tempat
                  </p>
                </div>
              </div>

              <div className="flex items-start gap-4 p-4 rounded-xl bg-background/80 backdrop-blur border border-border/50">
                <div className="p-2.5 rounded-lg bg-warning/10">
                  <Clock className="h-5 w-5 text-warning" />
                </div>
                <div>
                  <h3 className="font-semibold text-foreground">Real-time Tracking</h3>
                  <p className="text-sm text-muted-foreground">
                    Monitor kehadiran secara langsung dan akurat
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Right Panel - Login Form */}
        <div className="flex w-full lg:w-1/2 items-center justify-center p-6">
          <div className="flex flex-col items-center gap-6 w-full max-w-sm">
            {/* Logo */}
            <div className="flex flex-col items-center gap-2">
              <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary shadow-lg">
                <LogIn className="h-7 w-7 text-primary-foreground" />
              </div>
              <div className="text-center">
                <h1 className="text-2xl font-bold text-foreground">Attendance System</h1>
                <p className="text-sm text-muted-foreground">Masuk ke akun Anda</p>
              </div>
            </div>

            {/* Login Card */}
            <div className="w-full border border-border bg-background rounded-xl px-6 py-8 shadow-lg">
              <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
                {/* Error message */}
                {error && (
                  <Alert variant="destructive">
                    <AlertDescription>{error}</AlertDescription>
                  </Alert>
                )}

                {/* Email field */}
                <div className="space-y-2">
                  <Label htmlFor="email">Email</Label>
                  <Input
                    id="email"
                    type="email"
                    autoComplete="email"
                    placeholder="nama@email.com"
                    className="h-11"
                    {...register('email')}
                  />
                  {errors.email && (
                    <p className="text-xs text-destructive">{errors.email.message}</p>
                  )}
                </div>

                {/* Password field */}
                <div className="space-y-2">
                  <Label htmlFor="password">Password</Label>
                  <div className="relative">
                    <Input
                      id="password"
                      type={showPassword ? 'text' : 'password'}
                      autoComplete="current-password"
                      placeholder="Masukkan password"
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

                {/* Remember me & Forgot */}
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <Checkbox id="remember" {...register('remember')} />
                    <Label htmlFor="remember" className="text-sm font-normal text-muted-foreground cursor-pointer">
                      Ingat saya
                    </Label>
                  </div>
                  <Link to="/auth/forgot-password" className="text-sm text-primary font-medium hover:underline">
                    Lupa password?
                  </Link>
                </div>

                {/* Submit button */}
                <Button type="submit" className="w-full h-11" disabled={isLoading}>
                  {isLoading ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Memproses...
                    </>
                  ) : (
                    'Masuk'
                  )}
                </Button>
              </form>
            </div>

            {/* Footer */}
            <p className="text-xs text-muted-foreground text-center">
              &copy; {new Date().getFullYear()} Attendance System. All rights reserved.
            </p>
          </div>
        </div>
      </div>
    </section>
  );
}
