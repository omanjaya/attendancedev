import { useState, useRef } from 'react';
import { useNavigate, Link } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Loader2, Eye, EyeOff } from 'lucide-react';
import { Turnstile, type TurnstileInstance } from '@marsidev/react-turnstile';

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

// Use test key in test/development mode (always passes)
// Production key: 0x4AAAAAACGDmeZdHpqwVQJX
// Test key (always passes): 1x00000000000000000000AA
const TURNSTILE_SITE_KEY = import.meta.env.VITE_TURNSTILE_SITE_KEY ||
  (import.meta.env.MODE === 'test' ? '1x00000000000000000000AA' : '0x4AAAAAACGDmeZdHpqwVQJX');

export default function LoginPage() {
  const navigate = useNavigate();
  const { login, isLoading, error } = useAuthStore();
  const { error: showError } = useNotificationStore();
  const [showPassword, setShowPassword] = useState(false);
  const [turnstileToken, setTurnstileToken] = useState<string | null>(null);
  const turnstileRef = useRef<TurnstileInstance>(null);

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
    // Validate turnstile token
    if (!turnstileToken) {
      showError('Verifikasi Gagal', 'Silakan selesaikan verifikasi keamanan');
      return;
    }

    try {
      await login({ ...data, turnstile_token: turnstileToken });

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
      // Reset turnstile on error
      turnstileRef.current?.reset();
      setTurnstileToken(null);

      const message = err instanceof Error ? err.message : 'Login gagal';
      showError('Login Gagal', message);
    }
  };

  return (
    <div className="min-h-screen relative flex items-center justify-center p-4 overflow-hidden">
      {/* Animated Gradient Background */}
      <div className="absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
        {/* Animated Orbs */}
        <div className="absolute top-1/4 -left-20 w-96 h-96 bg-emerald-500/30 rounded-full blur-3xl animate-pulse" />
        <div className="absolute bottom-1/4 -right-20 w-96 h-96 bg-green-500/20 rounded-full blur-3xl animate-pulse delay-1000" />
        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-emerald-500/10 rounded-full blur-3xl" />

        {/* Grid Pattern Overlay */}
        <div
          className="absolute inset-0 opacity-20"
          style={{
            backgroundImage: `linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px)`,
            backgroundSize: '50px 50px'
          }}
        />
      </div>

      {/* Glassmorphism Login Card */}
      <div className="relative w-full max-w-md">
        {/* Glow effect behind card */}
        <div className="absolute -inset-1 bg-gradient-to-r from-emerald-500/20 via-green-500/20 to-emerald-500/20 rounded-3xl blur-xl opacity-70" />

        <div className="relative backdrop-blur-xl bg-white/10 dark:bg-white/5 border border-white/20 rounded-2xl shadow-2xl p-8">
          {/* Logo & Header */}
          <div className="flex flex-col items-center mb-8">
            <div className="relative">
              {/* Logo glow */}
              <div className="absolute inset-0 bg-emerald-400/30 rounded-full blur-xl" />
              <img
                src="/logo-school.png"
                alt="Logo Sekolah"
                className="relative h-24 w-24 object-contain drop-shadow-2xl"
              />
            </div>
            <h1 className="mt-4 text-2xl font-bold text-white">
              Sistem Absensi
            </h1>
            <p className="text-sm text-white/70">
              Masuk ke akun Anda
            </p>
          </div>

          {/* Login Form */}
          <form noValidate onSubmit={handleSubmit(onSubmit)} className="space-y-5">
            {/* Error message */}
            {error && (
              <Alert variant="destructive" className="bg-red-500/20 border-red-500/30 text-red-200">
                <AlertDescription>{error}</AlertDescription>
              </Alert>
            )}

            {/* Email field */}
            <div className="space-y-2">
              <Label htmlFor="email" className="text-white/80 text-sm font-medium">
                Email
              </Label>
              <Input
                id="email"
                type="email"
                autoComplete="email"
                placeholder="nama@email.com"
                className="h-12 bg-white/10 border-white/20 text-white placeholder:text-white/50
                           focus:bg-white/15 focus:border-emerald-400/50 focus:ring-emerald-400/20
                           transition-all duration-300"
                {...register('email')}
              />
              {errors.email && (
                <p className="text-xs text-red-400">{errors.email.message}</p>
              )}
            </div>

            {/* Password field */}
            <div className="space-y-2">
              <Label htmlFor="password" className="text-white/80 text-sm font-medium">
                Password
              </Label>
              <div className="relative">
                <Input
                  id="password"
                  type={showPassword ? 'text' : 'password'}
                  autoComplete="current-password"
                  placeholder="Masukkan password"
                  className="h-12 bg-white/10 border-white/20 text-white placeholder:text-white/50
                             focus:bg-white/15 focus:border-emerald-400/50 focus:ring-emerald-400/20
                             transition-all duration-300 pr-12"
                  {...register('password')}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-4 top-1/2 -translate-y-1/2 text-white/50 hover:text-white/80 transition-colors"
                  aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}
                >
                  {showPassword ? <EyeOff className="h-5 w-5" aria-hidden="true" /> : <Eye className="h-5 w-5" aria-hidden="true" />}
                </button>
              </div>
              {errors.password && (
                <p className="text-xs text-red-400">{errors.password.message}</p>
              )}
            </div>

            {/* Remember me & Forgot */}
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Checkbox
                  id="remember"
                  {...register('remember')}
                  className="border-white/30 data-[state=checked]:bg-emerald-500 data-[state=checked]:border-emerald-500"
                />
                <Label htmlFor="remember" className="text-sm text-white/70 cursor-pointer">
                  Ingat saya
                </Label>
              </div>
              <Link
                to="/auth/forgot-password"
                className="text-sm text-emerald-400 hover:text-emerald-300 transition-colors"
              >
                Lupa password?
              </Link>
            </div>

            {/* Turnstile CAPTCHA */}
            <div className="flex justify-center py-2">
              <Turnstile
                ref={turnstileRef}
                siteKey={TURNSTILE_SITE_KEY}
                onSuccess={(token: string) => setTurnstileToken(token)}
                onError={() => {
                  setTurnstileToken(null);
                  showError('Verifikasi Gagal', 'Terjadi kesalahan saat verifikasi');
                }}
                onExpire={() => setTurnstileToken(null)}
                options={{
                  theme: 'dark',
                  size: 'normal',
                  execution: 'render',
                  refreshExpired: 'auto',
                }}
              />
            </div>

            {/* Submit button */}
            <Button
              type="submit"
              className="w-full h-12 bg-gradient-to-r from-emerald-500 to-emerald-600
                         hover:from-emerald-400 hover:to-emerald-500
                         text-white font-semibold rounded-xl
                         shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40
                         transition-all duration-300 transform hover:scale-[1.02]"
              disabled={isLoading}
            >
              {isLoading ? (
                <>
                  <Loader2 className="mr-2 h-5 w-5 animate-spin" aria-hidden="true" />
                  Memproses...
                </>
              ) : (
                'Masuk'
              )}
            </Button>
          </form>

          {/* Footer */}
          <p className="mt-8 text-xs text-white/60 text-center">
            &copy; {new Date().getFullYear()} SMP Saraswati. All rights reserved.
          </p>
        </div>
      </div>

      {/* Floating particles effect */}
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        {[...Array(20)].map((_, i) => (
          <div
            key={i}
            className="absolute w-1 h-1 bg-white/20 rounded-full animate-float"
            style={{
              left: `${Math.random() * 100}%`,
              top: `${Math.random() * 100}%`,
              animationDelay: `${Math.random() * 5}s`,
              animationDuration: `${5 + Math.random() * 10}s`,
            }}
          />
        ))}
      </div>
    </div>
  );
}
