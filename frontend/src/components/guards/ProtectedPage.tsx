import { useEffect } from 'react';
import { useNavigate } from '@tanstack/react-router';
import { useAuthStore } from '@/stores';
import { useNotificationStore } from '@/stores';
import type { Permission, UserRole } from '@/types/auth';
import { Shield, AlertTriangle } from 'lucide-react';

interface ProtectedPageProps {
  children: React.ReactNode;
  permission?: Permission;
  roles?: UserRole[];
  fallback?: React.ReactNode;
}

/**
 * Page-level protection component
 * Wraps page content and checks permissions before rendering
 */
export function ProtectedPage({
  children,
  permission,
  roles,
  fallback,
}: ProtectedPageProps) {
  const { user } = useAuthStore();
  const { error: showError } = useNotificationStore();
  const navigate = useNavigate();

  useEffect(() => {
    if (!user) {
      showError('Akses Ditolak', 'Anda harus login terlebih dahulu');
      navigate({ to: '/login' });
      return;
    }

    // Super admin bypasses all checks
    if (user.role === 'super-admin') {
      return;
    }

    // Check role restriction
    if (roles && roles.length > 0) {
      if (!roles.includes(user.role)) {
        showError(
          'Akses Ditolak',
          `Halaman ini hanya dapat diakses oleh: ${roles.join(', ')}`
        );
        navigate({ to: '/dashboard' });
        return;
      }
    }

    // Check permission
    if (permission) {
      const userPermissions = user.permissions || [];
      if (!userPermissions.includes(permission)) {
        showError(
          'Akses Ditolak',
          'Anda tidak memiliki permission untuk mengakses halaman ini'
        );
        navigate({ to: '/dashboard' });
        return;
      }
    }
  }, [user, permission, roles, navigate, showError]);

  // If no user, show nothing (will redirect)
  if (!user) {
    return null;
  }

  // Super admin can see everything
  if (user.role === 'super-admin') {
    return <>{children}</>;
  }

  // Check role restriction
  if (roles && roles.length > 0 && !roles.includes(user.role)) {
    return fallback || <UnauthorizedFallback />;
  }

  // Check permission
  if (permission) {
    const userPermissions = user.permissions || [];
    if (!userPermissions.includes(permission)) {
      return fallback || <UnauthorizedFallback />;
    }
  }

  // All checks passed, render children
  return <>{children}</>;
}

/**
 * Default fallback UI when access is denied
 */
function UnauthorizedFallback() {
  const navigate = useNavigate();

  return (
    <div className="min-h-[60vh] flex items-center justify-center p-6">
      <div className="text-center max-w-md space-y-6">
        <div className="flex justify-center">
          <div className="rounded-full bg-destructive/10 p-6">
            <Shield className="h-16 w-16 text-destructive" />
          </div>
        </div>

        <div className="space-y-2">
          <h1 className="text-3xl font-bold text-foreground">Akses Ditolak</h1>
          <p className="text-muted-foreground">
            Anda tidak memiliki permission untuk mengakses halaman ini.
          </p>
        </div>

        <div className="flex items-start gap-3 p-4 rounded-lg bg-warning/10 border border-warning/20">
          <AlertTriangle className="h-5 w-5 text-warning shrink-0 mt-0.5" />
          <div className="text-sm text-left">
            <p className="font-medium text-warning mb-1">Mengapa ini terjadi?</p>
            <p className="text-muted-foreground">
              Role Anda tidak memiliki akses ke fitur ini. Silakan hubungi administrator
              jika Anda merasa ini adalah kesalahan.
            </p>
          </div>
        </div>

        <button
          onClick={() => navigate({ to: '/dashboard' })}
          className="inline-flex items-center justify-center rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
        >
          Kembali ke Dashboard
        </button>
      </div>
    </div>
  );
}
