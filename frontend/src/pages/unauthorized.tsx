import { XCircle, Home, ArrowLeft } from 'lucide-react';
import { useNavigate } from '@tanstack/react-router';
import { useAuthStore } from '@/stores';
import { getDefaultRedirect } from '@/lib/auth';

/**
 * Unauthorized Page
 * Shown when user tries to access a page they don't have permission for
 */
export default function UnauthorizedPage() {
  const navigate = useNavigate();
  const { user } = useAuthStore();

  const handleGoBack = () => {
    navigate({ to: -1 as any });
  };

  const handleGoHome = () => {
    const defaultPath = getDefaultRedirect(user);
    navigate({ to: defaultPath });
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-b from-muted/30 to-background p-4">
      <div className="text-center max-w-md">
        <div className="bg-destructive/10 rounded-full p-6 w-fit mx-auto mb-6">
          <XCircle className="h-16 w-16 text-destructive" />
        </div>

        <h1 className="text-2xl font-bold mb-2">Akses Ditolak</h1>
        <p className="text-muted-foreground mb-6">
          Anda tidak memiliki izin untuk mengakses halaman ini.
          Silakan hubungi administrator jika Anda yakin ini adalah kesalahan.
        </p>

        <div className="flex gap-3 justify-center">
          <button
            onClick={handleGoBack}
            className="inline-flex items-center gap-2 px-4 py-2 border border-border rounded-lg hover:bg-muted transition-colors"
          >
            <ArrowLeft className="h-4 w-4" />
            Kembali
          </button>
          <button
            onClick={handleGoHome}
            className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors"
          >
            <Home className="h-4 w-4" />
            Ke Dashboard
          </button>
        </div>
      </div>
    </div>
  );
}
