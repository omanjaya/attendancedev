import { useState } from 'react';
import { Link } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Loader2, Mail, ArrowLeft, CheckCircle } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Alert, AlertDescription } from '@/components/ui/alert';

const forgotPasswordSchema = z.object({
  email: z.string().email('Email tidak valid'),
});

type ForgotPasswordForm = z.infer<typeof forgotPasswordSchema>;

export default function ForgotPasswordPage() {
  const [isLoading, setIsLoading] = useState(false);
  const [isEmailSent, setIsEmailSent] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
    getValues,
  } = useForm<ForgotPasswordForm>({
    resolver: zodResolver(forgotPasswordSchema),
  });

  const onSubmit = async () => {
    setIsLoading(true);
    try {
      // Demo mode - simulate API call
      await new Promise(resolve => setTimeout(resolve, 1500));
      setIsEmailSent(true);
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
              <Mail className="h-7 w-7 text-primary-foreground" />
            </div>
            <div className="text-center">
              <h1 className="text-2xl font-bold text-foreground">Reset Password</h1>
              <p className="text-sm text-muted-foreground">
                Masukkan email Anda untuk menerima link reset
              </p>
            </div>
          </div>

          {/* Card */}
          <div className="w-full border border-border bg-background rounded-xl px-6 py-8 shadow-lg">
            {isEmailSent ? (
              <div className="text-center space-y-4">
                <div className="flex justify-center">
                  <div className="h-16 w-16 rounded-full bg-success/10 flex items-center justify-center">
                    <CheckCircle className="h-8 w-8 text-success" />
                  </div>
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-foreground">Email Terkirim!</h3>
                  <p className="text-sm text-muted-foreground mt-2">
                    Kami telah mengirimkan link reset password ke{' '}
                    <span className="font-medium text-foreground">{getValues('email')}</span>
                  </p>
                </div>
                <Alert>
                  <AlertDescription className="text-sm">
                    Periksa folder spam jika email tidak ditemukan di inbox Anda.
                  </AlertDescription>
                </Alert>
                <Button
                  variant="outline"
                  className="w-full"
                  onClick={() => setIsEmailSent(false)}
                >
                  Kirim Ulang Email
                </Button>
              </div>
            ) : (
              <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
                <p className="text-sm text-muted-foreground text-center">
                  Lupa password? Tidak masalah. Masukkan email Anda dan kami akan mengirimkan
                  link untuk membuat password baru.
                </p>

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

                {/* Submit */}
                <Button type="submit" className="w-full h-11" disabled={isLoading}>
                  {isLoading ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Mengirim...
                    </>
                  ) : (
                    <>
                      <Mail className="mr-2 h-4 w-4" />
                      Kirim Link Reset
                    </>
                  )}
                </Button>
              </form>
            )}
          </div>

          {/* Back to Login */}
          <Link
            to="/login"
            className="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors"
          >
            <ArrowLeft className="h-4 w-4" />
            Kembali ke halaman login
          </Link>
        </div>
      </div>
    </section>
  );
}
