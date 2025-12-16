import { useState, useEffect } from 'react';
import { Link, useNavigate, useSearch } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Loader2, Eye, EyeOff, KeyRound, CheckCircle, AlertCircle, ArrowLeft, ShieldCheck } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { resetPassword } from '@/lib/api/auth';
import { toast } from 'sonner';

const resetPasswordSchema = z.object({
  email: z.string().email('Email tidak valid'),
  password: z.string().min(8, 'Password minimal 8 karakter'),
  password_confirmation: z.string(),
}).refine((data) => data.password === data.password_confirmation, {
  message: 'Password tidak cocok',
  path: ['password_confirmation'],
});

type ResetPasswordForm = z.infer<typeof resetPasswordSchema>;

interface SearchParams {
  token?: string;
  email?: string;
}

export default function ResetPasswordPage() {
  const navigate = useNavigate();
  const search = useSearch({ strict: false }) as SearchParams;
  const [isLoading, setIsLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [countdown, setCountdown] = useState(5);

  // Get token and email from URL query params
  const token = search?.token || '';
  const emailFromUrl = search?.email || '';

  // Validate token format (should be at least 20 chars, alphanumeric)
  const isValidTokenFormat = token.length >= 20 && /^[a-zA-Z0-9]+$/.test(token);

  const {
    register,
    handleSubmit,
    formState: { errors },
    setValue,
    watch,
  } = useForm<ResetPasswordForm>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: {
      email: emailFromUrl,
    },
  });

  const password = watch('password', '');

  // Password strength checker
  const getPasswordStrength = (pwd: string) => {
    let strength = 0;
    if (pwd.length >= 8) strength++;
    if (pwd.match(/[a-z]/)) strength++;
    if (pwd.match(/[A-Z]/)) strength++;
    if (pwd.match(/[0-9]/)) strength++;
    if (pwd.match(/[^a-zA-Z0-9]/)) strength++;
    return strength;
  };

  const passwordStrength = getPasswordStrength(password);
  const strengthLabels = ['Sangat Lemah', 'Lemah', 'Cukup', 'Kuat', 'Sangat Kuat'];
  const strengthColors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-emerald-500', 'bg-emerald-400'];

  // Pre-fill email from URL if available
  useEffect(() => {
    if (emailFromUrl) {
      setValue('email', emailFromUrl);
    }
  }, [emailFromUrl, setValue]);

  // Countdown for redirect after success
  useEffect(() => {
    if (isSuccess && countdown > 0) {
      const timer = setTimeout(() => setCountdown(countdown - 1), 1000);
      return () => clearTimeout(timer);
    } else if (isSuccess && countdown === 0) {
      navigate({ to: '/login' });
    }
  }, [isSuccess, countdown, navigate]);

  const onSubmit = async (data: ResetPasswordForm) => {
    if (!token) {
      setError('Token reset password tidak ditemukan.');
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const response = await resetPassword({
        token,
        email: data.email,
        password: data.password,
        password_confirmation: data.password_confirmation,
      });

      if (response.success) {
        setIsSuccess(true);
        toast.success('Password Berhasil Direset', {
          description: 'Silakan login dengan password baru Anda.',
        });
      } else {
        setError(response.message || 'Gagal mereset password.');
      }
    } catch (err: unknown) {
      const errorMessage = err instanceof Error
        ? err.message
        : 'Terjadi kesalahan. Silakan coba lagi.';
      setError(errorMessage);
      toast.error('Gagal mereset password', {
        description: errorMessage,
      });
    } finally {
      setIsLoading(false);
    }
  };

  // Show error if no token or invalid format
  if (!token || !isValidTokenFormat) {
    return (
      <div className="min-h-screen relative flex items-center justify-center p-4 overflow-hidden">
        {/* Background */}
        <div className="absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
          <div className="absolute top-1/4 -left-20 w-96 h-96 bg-red-500/20 rounded-full blur-3xl animate-pulse" />
          <div className="absolute bottom-1/4 -right-20 w-96 h-96 bg-orange-500/20 rounded-full blur-3xl animate-pulse delay-1000" />
        </div>

        {/* Error Card */}
        <div className="relative w-full max-w-md">
          <div className="absolute -inset-1 bg-gradient-to-r from-red-500/20 via-orange-500/20 to-red-500/20 rounded-3xl blur-xl opacity-70" />

          <div className="relative backdrop-blur-xl bg-white/10 border border-white/20 rounded-2xl shadow-2xl p-8">
            <div className="flex flex-col items-center mb-6">
              <div className="relative">
                <div className="absolute inset-0 bg-red-400/30 rounded-full blur-xl" />
                <div className="relative flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-red-400 to-red-600 shadow-lg">
                  <AlertCircle className="h-8 w-8 text-white" />
                </div>
              </div>
              <h1 className="mt-4 text-2xl font-bold text-white">Link Tidak Valid</h1>
              <p className="text-sm text-white/60 text-center mt-2">
                Token reset password tidak ditemukan atau sudah kadaluarsa.
              </p>
            </div>

            <div className="space-y-4">
              <Alert className="bg-white/5 border-white/10">
                <AlertDescription className="text-sm text-white/70">
                  Link reset password hanya berlaku selama 60 menit. Silakan minta link baru.
                </AlertDescription>
              </Alert>

              <div className="flex flex-col gap-3">
                <Button
                  asChild
                  className="w-full h-11 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500"
                >
                  <Link to="/auth/forgot-password">Minta Link Baru</Link>
                </Button>
                <Button
                  asChild
                  variant="outline"
                  className="w-full h-11 bg-white/5 border-white/20 text-white hover:bg-white/10 hover:text-white"
                >
                  <Link to="/login">Kembali ke Login</Link>
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen relative flex items-center justify-center p-4 overflow-hidden">
      {/* Animated Gradient Background */}
      <div className="absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
        <div className="absolute top-1/4 -left-20 w-96 h-96 bg-emerald-500/30 rounded-full blur-3xl animate-pulse" />
        <div className="absolute bottom-1/4 -right-20 w-96 h-96 bg-green-500/20 rounded-full blur-3xl animate-pulse delay-1000" />
        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-emerald-500/10 rounded-full blur-3xl" />

        <div
          className="absolute inset-0 opacity-20"
          style={{
            backgroundImage: `linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px)`,
            backgroundSize: '50px 50px'
          }}
        />
      </div>

      {/* Glassmorphism Card */}
      <div className="relative w-full max-w-md">
        <div className="absolute -inset-1 bg-gradient-to-r from-emerald-500/20 via-green-500/20 to-emerald-500/20 rounded-3xl blur-xl opacity-70" />

        <div className="relative backdrop-blur-xl bg-white/10 dark:bg-white/5 border border-white/20 rounded-2xl shadow-2xl p-8">
          {/* Header */}
          <div className="flex flex-col items-center mb-8">
            <div className="relative">
              <div className="absolute inset-0 bg-emerald-400/30 rounded-full blur-xl" />
              <div className="relative flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-lg">
                {isSuccess ? (
                  <ShieldCheck className="h-8 w-8 text-white" />
                ) : (
                  <KeyRound className="h-8 w-8 text-white" />
                )}
              </div>
            </div>
            <h1 className="mt-4 text-2xl font-bold text-white">
              {isSuccess ? 'Password Berhasil Direset!' : 'Buat Password Baru'}
            </h1>
            <p className="text-sm text-white/60 text-center">
              {isSuccess
                ? 'Anda akan dialihkan ke halaman login'
                : 'Buat password baru untuk akun Anda'}
            </p>
          </div>

          {isSuccess ? (
            /* Success State */
            <div className="space-y-6">
              <div className="flex justify-center">
                <div className="relative">
                  <div className="absolute inset-0 bg-emerald-400/30 rounded-full blur-xl animate-pulse" />
                  <div className="relative h-24 w-24 rounded-full bg-emerald-500/20 flex items-center justify-center border border-emerald-500/30">
                    <CheckCircle className="h-12 w-12 text-emerald-400" />
                  </div>
                </div>
              </div>

              <div className="text-center space-y-2">
                <p className="text-white/70">
                  Password Anda telah berhasil diubah.
                </p>
                <p className="text-sm text-white/50">
                  Mengalihkan ke halaman login dalam {countdown} detik...
                </p>
              </div>

              <Button
                asChild
                className="w-full h-11 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500"
              >
                <Link to="/login">Login Sekarang</Link>
              </Button>
            </div>
          ) : (
            /* Form */
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
              {/* Error Alert */}
              {error && (
                <Alert className="bg-red-500/20 border-red-500/30">
                  <AlertCircle className="h-4 w-4 text-red-400" />
                  <AlertDescription className="text-red-200">{error}</AlertDescription>
                </Alert>
              )}

              {/* Email Field */}
              <div className="space-y-2">
                <Label htmlFor="email" className="text-white/80 text-sm font-medium">
                  Email
                </Label>
                <Input
                  id="email"
                  type="email"
                  placeholder="nama@email.com"
                  className="h-12 bg-white/10 border-white/20 text-white placeholder:text-white/40
                             focus:bg-white/15 focus:border-emerald-400/50 focus:ring-emerald-400/20
                             transition-all duration-300"
                  disabled={isLoading}
                  {...register('email')}
                />
                {errors.email && (
                  <p className="text-xs text-red-400">{errors.email.message}</p>
                )}
              </div>

              {/* Password Field */}
              <div className="space-y-2">
                <Label htmlFor="password" className="text-white/80 text-sm font-medium">
                  Password Baru
                </Label>
                <div className="relative">
                  <Input
                    id="password"
                    type={showPassword ? 'text' : 'password'}
                    placeholder="Minimal 8 karakter"
                    className="h-12 bg-white/10 border-white/20 text-white placeholder:text-white/40
                               focus:bg-white/15 focus:border-emerald-400/50 focus:ring-emerald-400/20
                               transition-all duration-300 pr-12"
                    disabled={isLoading}
                    {...register('password')}
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-4 top-1/2 -translate-y-1/2 text-white/50 hover:text-white/80 transition-colors"
                  >
                    {showPassword ? <EyeOff className="h-5 w-5" /> : <Eye className="h-5 w-5" />}
                  </button>
                </div>
                {errors.password && (
                  <p className="text-xs text-red-400">{errors.password.message}</p>
                )}

                {/* Password Strength Indicator */}
                {password && (
                  <div className="space-y-2">
                    <div className="flex gap-1">
                      {[...Array(5)].map((_, i) => (
                        <div
                          key={i}
                          className={`h-1 flex-1 rounded-full transition-all duration-300 ${
                            i < passwordStrength ? strengthColors[passwordStrength - 1] : 'bg-white/10'
                          }`}
                        />
                      ))}
                    </div>
                    <p className="text-xs text-white/50">
                      Kekuatan: <span className={`font-medium ${passwordStrength >= 4 ? 'text-emerald-400' : passwordStrength >= 3 ? 'text-yellow-400' : 'text-red-400'}`}>
                        {strengthLabels[passwordStrength - 1] || 'Sangat Lemah'}
                      </span>
                    </p>
                  </div>
                )}
              </div>

              {/* Confirm Password Field */}
              <div className="space-y-2">
                <Label htmlFor="password_confirmation" className="text-white/80 text-sm font-medium">
                  Konfirmasi Password
                </Label>
                <div className="relative">
                  <Input
                    id="password_confirmation"
                    type={showConfirmPassword ? 'text' : 'password'}
                    placeholder="Ulangi password baru"
                    className="h-12 bg-white/10 border-white/20 text-white placeholder:text-white/40
                               focus:bg-white/15 focus:border-emerald-400/50 focus:ring-emerald-400/20
                               transition-all duration-300 pr-12"
                    disabled={isLoading}
                    {...register('password_confirmation')}
                  />
                  <button
                    type="button"
                    onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                    className="absolute right-4 top-1/2 -translate-y-1/2 text-white/50 hover:text-white/80 transition-colors"
                  >
                    {showConfirmPassword ? <EyeOff className="h-5 w-5" /> : <Eye className="h-5 w-5" />}
                  </button>
                </div>
                {errors.password_confirmation && (
                  <p className="text-xs text-red-400">{errors.password_confirmation.message}</p>
                )}
              </div>

              {/* Submit Button */}
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
                    <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                    Menyimpan...
                  </>
                ) : (
                  'Reset Password'
                )}
              </Button>
            </form>
          )}

          {/* Back to Login */}
          {!isSuccess && (
            <div className="mt-6 text-center">
              <Link
                to="/login"
                className="inline-flex items-center gap-2 text-sm text-white/50 hover:text-white transition-colors"
              >
                <ArrowLeft className="h-4 w-4" />
                Kembali ke halaman login
              </Link>
            </div>
          )}
        </div>
      </div>

      {/* Floating particles */}
      <div className="absolute inset-0 overflow-hidden pointer-events-none">
        {[...Array(15)].map((_, i) => (
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
