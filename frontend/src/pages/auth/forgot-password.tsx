import { useState, useRef } from 'react';
import { Link } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Loader2, Mail, ArrowLeft, CheckCircle, AlertCircle, RefreshCw } from 'lucide-react';
import { Turnstile, type TurnstileInstance } from '@marsidev/react-turnstile';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { forgotPassword } from '@/lib/api/auth';
import { toast } from 'sonner';

const forgotPasswordSchema = z.object({
  email: z.string().email('Email tidak valid'),
});

type ForgotPasswordForm = z.infer<typeof forgotPasswordSchema>;

const TURNSTILE_SITE_KEY = '0x4AAAAAACGDmeZdHpqwVQJX';

export default function ForgotPasswordPage() {
  const [isLoading, setIsLoading] = useState(false);
  const [isEmailSent, setIsEmailSent] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [turnstileToken, setTurnstileToken] = useState<string | null>(null);
  const [cooldown, setCooldown] = useState(0);
  const turnstileRef = useRef<TurnstileInstance>(null);

  const {
    register,
    handleSubmit,
    formState: { errors },
    getValues,
    reset,
  } = useForm<ForgotPasswordForm>({
    resolver: zodResolver(forgotPasswordSchema),
  });

  // Cooldown timer for resend
  const startCooldown = () => {
    setCooldown(60);
    const timer = setInterval(() => {
      setCooldown((prev) => {
        if (prev <= 1) {
          clearInterval(timer);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);
  };

  const onSubmit = async (data: ForgotPasswordForm) => {
    if (!turnstileToken) {
      setError('Silakan selesaikan verifikasi keamanan');
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const response = await forgotPassword(data.email, turnstileToken);

      if (response.success) {
        setIsEmailSent(true);
        startCooldown();
        toast.success('Email terkirim!', {
          description: 'Silakan periksa inbox email Anda.',
        });
      } else {
        setError(response.message || 'Gagal mengirim email reset password.');
        turnstileRef.current?.reset();
        setTurnstileToken(null);
      }
    } catch (err: unknown) {
      const errorMessage = err instanceof Error
        ? err.message
        : 'Terjadi kesalahan. Silakan coba lagi.';
      setError(errorMessage);
      turnstileRef.current?.reset();
      setTurnstileToken(null);
      toast.error('Gagal mengirim email', {
        description: errorMessage,
      });
    } finally {
      setIsLoading(false);
    }
  };

  const handleResend = async () => {
    if (cooldown > 0) return;

    const email = getValues('email');
    if (!email || !turnstileToken) {
      turnstileRef.current?.reset();
      setTurnstileToken(null);
      setIsEmailSent(false);
      return;
    }

    setIsLoading(true);
    try {
      const response = await forgotPassword(email, turnstileToken);
      if (response.success) {
        startCooldown();
        toast.success('Email terkirim ulang!');
      }
    } catch {
      toast.error('Gagal mengirim ulang email');
    } finally {
      setIsLoading(false);
      turnstileRef.current?.reset();
      setTurnstileToken(null);
    }
  };

  const handleReset = () => {
    setIsEmailSent(false);
    setError(null);
    setCooldown(0);
    turnstileRef.current?.reset();
    setTurnstileToken(null);
    reset();
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

      {/* Glassmorphism Card */}
      <div className="relative w-full max-w-md">
        {/* Glow effect behind card */}
        <div className="absolute -inset-1 bg-gradient-to-r from-emerald-500/20 via-green-500/20 to-emerald-500/20 rounded-3xl blur-xl opacity-70" />

        <div className="relative backdrop-blur-xl bg-white/10 dark:bg-white/5 border border-white/20 rounded-2xl shadow-2xl p-8">
          {/* Logo & Header */}
          <div className="flex flex-col items-center mb-8">
            <div className="relative">
              <div className="absolute inset-0 bg-emerald-400/30 rounded-full blur-xl" />
              <div className="relative flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-lg">
                <Mail className="h-8 w-8 text-white" />
              </div>
            </div>
            <h1 className="mt-4 text-2xl font-bold text-white">
              Reset Password
            </h1>
            <p className="text-sm text-white/60 text-center">
              {isEmailSent
                ? 'Email berhasil dikirim!'
                : 'Masukkan email untuk menerima link reset'}
            </p>
          </div>

          {isEmailSent ? (
            /* Success State */
            <div className="space-y-6">
              <div className="flex justify-center">
                <div className="relative">
                  <div className="absolute inset-0 bg-emerald-400/30 rounded-full blur-xl animate-pulse" />
                  <div className="relative h-20 w-20 rounded-full bg-emerald-500/20 flex items-center justify-center border border-emerald-500/30">
                    <CheckCircle className="h-10 w-10 text-emerald-400" />
                  </div>
                </div>
              </div>

              <div className="text-center space-y-2">
                <h3 className="text-lg font-semibold text-white">Email Terkirim!</h3>
                <p className="text-sm text-white/60">
                  Link reset password telah dikirim ke
                </p>
                <p className="text-sm font-medium text-emerald-400">
                  {getValues('email')}
                </p>
              </div>

              <Alert className="bg-white/5 border-white/10">
                <AlertDescription className="text-sm text-white/70">
                  Periksa folder spam jika email tidak ditemukan. Link akan kadaluarsa dalam 60 menit.
                </AlertDescription>
              </Alert>

              {/* Resend with cooldown */}
              <div className="space-y-3">
                <Button
                  variant="outline"
                  className="w-full h-11 bg-white/5 border-white/20 text-white hover:bg-white/10 hover:text-white"
                  onClick={handleResend}
                  disabled={isLoading || cooldown > 0}
                >
                  {isLoading ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Mengirim...
                    </>
                  ) : cooldown > 0 ? (
                    <>
                      <RefreshCw className="mr-2 h-4 w-4" />
                      Kirim Ulang ({cooldown}s)
                    </>
                  ) : (
                    <>
                      <RefreshCw className="mr-2 h-4 w-4" />
                      Kirim Ulang Email
                    </>
                  )}
                </Button>

                <Button
                  variant="ghost"
                  className="w-full h-11 text-white/60 hover:text-white hover:bg-white/5"
                  onClick={handleReset}
                >
                  Gunakan Email Lain
                </Button>
              </div>

              {/* Turnstile for resend */}
              {cooldown === 0 && (
                <div className="flex justify-center pt-2">
                  <Turnstile
                    ref={turnstileRef}
                    siteKey={TURNSTILE_SITE_KEY}
                    onSuccess={(token: string) => setTurnstileToken(token)}
                    onError={() => setTurnstileToken(null)}
                    onExpire={() => setTurnstileToken(null)}
                    options={{
                      theme: 'dark',
                      size: 'compact',
                      execution: 'render',
                      refreshExpired: 'auto',
                    }}
                  />
                </div>
              )}
            </div>
          ) : (
            /* Form State */
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
              <p className="text-sm text-white/60 text-center">
                Lupa password? Tidak masalah. Masukkan email Anda dan kami akan mengirimkan link reset.
              </p>

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
                  {...register('email')}
                  disabled={isLoading}
                />
                {errors.email && (
                  <p className="text-xs text-red-400">{errors.email.message}</p>
                )}
              </div>

              {/* Turnstile CAPTCHA */}
              <div className="flex justify-center py-2">
                <Turnstile
                  ref={turnstileRef}
                  siteKey={TURNSTILE_SITE_KEY}
                  onSuccess={(token: string) => setTurnstileToken(token)}
                  onError={() => {
                    setTurnstileToken(null);
                    setError('Verifikasi keamanan gagal');
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
                    Mengirim...
                  </>
                ) : (
                  <>
                    <Mail className="mr-2 h-5 w-5" />
                    Kirim Link Reset
                  </>
                )}
              </Button>
            </form>
          )}

          {/* Back to Login */}
          <div className="mt-6 text-center">
            <Link
              to="/login"
              className="inline-flex items-center gap-2 text-sm text-white/50 hover:text-white transition-colors"
            >
              <ArrowLeft className="h-4 w-4" />
              Kembali ke halaman login
            </Link>
          </div>
        </div>
      </div>

      {/* Floating particles effect */}
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
