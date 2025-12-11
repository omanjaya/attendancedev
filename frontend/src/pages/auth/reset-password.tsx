import { useState, useEffect } from 'react';
import { Link, useNavigate, useSearch } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Loader2, Eye, EyeOff, KeyRound, CheckCircle, AlertCircle } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
  } = useForm<ResetPasswordForm>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: {
      email: emailFromUrl,
    },
  });

  // Pre-fill email from URL if available
  useEffect(() => {
    if (emailFromUrl) {
      setValue('email', emailFromUrl);
    }
  }, [emailFromUrl, setValue]);

  const onSubmit = async (data: ResetPasswordForm) => {
    if (!token) {
      setError('Token reset password tidak ditemukan. Silakan gunakan link dari email Anda.');
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
        setTimeout(() => {
          navigate({ to: '/login' });
        }, 2000);
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
      <section className="bg-muted min-h-screen flex items-center justify-center p-6">
        <div className="w-full max-w-md">
          <div className="flex flex-col items-center gap-6">
            <div className="flex flex-col items-center gap-2">
              <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-destructive/10 shadow-lg">
                <AlertCircle className="h-7 w-7 text-destructive" />
              </div>
              <div className="text-center">
                <h1 className="text-2xl font-bold text-foreground">Link Tidak Valid</h1>
                <p className="text-sm text-muted-foreground">
                  Token reset password tidak ditemukan
                </p>
              </div>
            </div>

            <div className="w-full border border-border bg-background rounded-xl px-6 py-8 shadow-lg">
              <div className="text-center space-y-4">
                <p className="text-sm text-muted-foreground">
                  Link reset password tidak valid atau sudah kadaluarsa.
                  Silakan minta link reset password baru.
                </p>
                <div className="flex flex-col gap-2">
                  <Button asChild className="w-full">
                    <Link to="/auth/forgot-password">
                      Minta Link Baru
                    </Link>
                  </Button>
                  <Button asChild variant="outline" className="w-full">
                    <Link to="/login">
                      Kembali ke Login
                    </Link>
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    );
  }

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
                {/* Error Alert */}
                {error && (
                  <Alert variant="destructive">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>{error}</AlertDescription>
                  </Alert>
                )}

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
                    disabled={isLoading}
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
                      disabled={isLoading}
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
                      disabled={isLoading}
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
