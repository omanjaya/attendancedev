import { useState } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Lock, Loader2, Eye, EyeOff, Shield, ArrowLeft } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { LoadingState } from '@/components/states';
import { Input } from '@/components/ui/input';
import { useNotificationStore, useAuthStore } from '@/stores';
import { getDefaultRedirect } from '@/lib/auth';

const confirmSchema = z.object({
  password: z.string().min(1, 'Masukkan password'),
});

type ConfirmForm = z.infer<typeof confirmSchema>;

export default function ConfirmPasswordPage() {
  const navigate = useNavigate();
  const { error: showError } = useNotificationStore();
  const { user } = useAuthStore();
  const [showPassword, setShowPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<ConfirmForm>({
    resolver: zodResolver(confirmSchema),
  });

  const onSubmit = async (_data: ConfirmForm) => {
    setIsLoading(true);
    try {
      await new Promise(resolve => setTimeout(resolve, 1000));
      // Navigate to the protected action
      navigate({ to: getDefaultRedirect(user) });
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Password tidak valid';
      showError('Konfirmasi Gagal', message);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <section className="bg-muted min-h-screen flex items-center justify-center p-6">
      <div className="w-full max-w-md">
        {/* Card */}
        <div className="bg-background rounded-xl border border-border px-8 py-10 shadow-lg">
          {/* Icon */}
          <div className="mx-auto w-16 h-16 rounded-full bg-warning/10 flex items-center justify-center mb-6">
            <Shield className="h-8 w-8 text-warning" />
          </div>

          {/* Title */}
          <div className="text-center mb-6">
            <h1 className="text-2xl font-bold text-foreground mb-2">
              Konfirmasi Password
            </h1>
            <p className="text-muted-foreground">
              Ini adalah area yang dilindungi. Silakan konfirmasi password Anda sebelum melanjutkan.
            </p>
          </div>

          {/* Form */}
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <div className="space-y-2">
              <label htmlFor="password" className="text-sm font-medium text-foreground">
                Password
              </label>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  id="password"
                  type={showPassword ? 'text' : 'password'}
                  placeholder="Masukkan password Anda"
                  className="h-11 pl-10 pr-11"
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

            <Button type="submit" className="w-full h-11" disabled={isLoading}>
              {isLoading ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Memverifikasi...
                </>
              ) : (
                'Konfirmasi'
              )}
            </Button>
          </form>

          {/* Back Link */}
          <div className="mt-6 text-center">
            <Link
              to="/dashboard"
              className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground"
            >
              <ArrowLeft className="mr-2 h-4 w-4" />
              Kembali ke Dashboard
            </Link>
          </div>
        </div>
      </div>
    </section>
  );
}
