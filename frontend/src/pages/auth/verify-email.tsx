import { useState } from 'react';
import { Link } from '@tanstack/react-router';
import { Mail, RefreshCw, CheckCircle, ArrowLeft, Loader2 } from 'lucide-react';

import { Button } from '@/components/ui/button';

import { useNotificationStore } from '@/stores';

export default function VerifyEmailPage() {
  const { success } = useNotificationStore();
  const [isResending, setIsResending] = useState(false);
  const [resent, setResent] = useState(false);

  const handleResend = async () => {
    setIsResending(true);
    try {
      await new Promise(resolve => setTimeout(resolve, 1500));
      setResent(true);
      success('Email Terkirim', 'Link verifikasi telah dikirim ulang ke email Anda');
    } finally {
      setIsResending(false);
    }
  };

  return (
    <section className="bg-muted min-h-screen flex items-center justify-center p-6">
      <div className="w-full max-w-md">
        {/* Card */}
        <div className="bg-background rounded-xl border border-border px-8 py-10 shadow-lg text-center">
          {/* Icon */}
          <div className="mx-auto w-20 h-20 rounded-full bg-primary/10 flex items-center justify-center mb-6">
            <Mail className="h-10 w-10 text-primary" />
          </div>

          {/* Title */}
          <h1 className="text-2xl font-bold text-foreground mb-2">
            Verifikasi Email Anda
          </h1>
          <p className="text-muted-foreground mb-6">
            Kami telah mengirim link verifikasi ke email Anda.
            Silakan periksa inbox dan klik link untuk mengaktifkan akun.
          </p>

          {/* Status */}
          {resent && (
            <div className="flex items-center justify-center gap-2 p-3 rounded-lg bg-success/10 text-success mb-6">
              <CheckCircle className="h-4 w-4" />
              <span className="text-sm">Email verifikasi telah dikirim ulang</span>
            </div>
          )}

          {/* Actions */}
          <div className="space-y-3">
            <Button
              onClick={handleResend}
              disabled={isResending}
              className="w-full"
            >
              {isResending ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Mengirim...
                </>
              ) : (
                <>
                  <RefreshCw className="mr-2 h-4 w-4" />
                  Kirim Ulang Email
                </>
              )}
            </Button>

            <Button variant="outline" className="w-full" asChild>
              <Link to="/login">
                <ArrowLeft className="mr-2 h-4 w-4" />
                Kembali ke Login
              </Link>
            </Button>
          </div>

          {/* Tips */}
          <div className="mt-8 p-4 rounded-lg bg-muted text-left">
            <p className="text-sm font-medium mb-2">Tidak menerima email?</p>
            <ul className="text-xs text-muted-foreground space-y-1">
              <li>- Periksa folder spam atau junk</li>
              <li>- Pastikan alamat email yang Anda masukkan benar</li>
              <li>- Tunggu beberapa menit dan coba kirim ulang</li>
            </ul>
          </div>
        </div>

        {/* Footer */}
        <p className="text-center text-sm text-muted-foreground mt-6">
          Butuh bantuan?{' '}
          <a href="#" className="text-primary hover:underline">
            Hubungi Support
          </a>
        </p>
      </div>
    </section>
  );
}
