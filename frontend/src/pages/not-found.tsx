import { useNavigate } from '@tanstack/react-router';
import { Button } from '@/components/ui/button';
import { Home } from 'lucide-react';
import { useAuthStore } from '@/stores';
import { getDefaultRedirect } from '@/lib/auth';

export default function NotFoundPage() {
  const navigate = useNavigate();
  const { user } = useAuthStore();

  const handleGoHome = () => {
    navigate({ to: getDefaultRedirect(user) });
  };

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-background">
      <div className="text-center">
        <h1 className="text-4xl font-bold text-foreground">404</h1>
        <p className="mt-4 text-lg text-muted-foreground">
          Halaman tidak ditemukan
        </p>
        <p className="mt-2 text-sm text-muted-foreground">
          Maaf, halaman yang Anda cari tidak ada atau telah dipindahkan.
        </p>
        <Button onClick={handleGoHome} className="mt-8">
          <Home className="mr-2 h-4 w-4" />
          Kembali ke Dashboard
        </Button>
      </div>
    </div>
  );
}
