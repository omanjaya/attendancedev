import { useState, useEffect } from 'react';
import { WifiOff, Wifi, AlertCircle } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { cn } from '@/lib/utils';

interface NetworkStatusProps {
  /**
   * Show indicator even when online
   * Default: false (hanya tampil saat offline)
   */
  showWhenOnline?: boolean;

  /**
   * Position of the indicator
   * Default: 'bottom'
   */
  position?: 'top' | 'bottom';

  /**
   * Custom className
   */
  className?: string;

  /**
   * Callback saat network status berubah
   */
  onStatusChange?: (isOnline: boolean) => void;
}

/**
 * Network Status Indicator Component
 *
 * Menampilkan status koneksi internet secara real-time:
 * - Offline indicator dengan peringatan
 * - Online indicator (optional)
 * - Auto-detect browser online/offline events
 * - Periodic ping check untuk verify connectivity
 *
 * @example
 * ```tsx
 * // Basic usage (shows only when offline)
 * <NetworkStatus />
 *
 * // Show online status juga
 * <NetworkStatus showWhenOnline />
 *
 * // Custom position
 * <NetworkStatus position="top" />
 *
 * // With callback
 * <NetworkStatus onStatusChange={(isOnline) => console.log(isOnline)} />
 * ```
 */
export function NetworkStatus({
  showWhenOnline = false,
  position = 'bottom',
  className,
  onStatusChange,
}: NetworkStatusProps) {
  const [isOnline, setIsOnline] = useState(navigator.onLine);
  const [wasOffline, setWasOffline] = useState(false);

  useEffect(() => {
    // Handler untuk online event
    const handleOnline = () => {
      setIsOnline(true);
      setWasOffline(true);
      onStatusChange?.(true);

      // Hide "back online" message after 5 seconds
      setTimeout(() => {
        setWasOffline(false);
      }, 5000);
    };

    // Handler untuk offline event
    const handleOffline = () => {
      setIsOnline(false);
      onStatusChange?.(false);
    };

    // Add event listeners
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    // Periodic connectivity check (every 30 seconds)
    // Checks against a lightweight endpoint atau image
    const checkConnectivity = async () => {
      try {
        // Try to fetch a small resource
        // Using a common image CDN as fallback
        await fetch('https://www.google.com/favicon.ico', {
          mode: 'no-cors',
          cache: 'no-store',
        });

        if (!isOnline) {
          handleOnline();
        }
      } catch (error) {
        if (isOnline) {
          handleOffline();
        }
      }
    };

    const intervalId = setInterval(checkConnectivity, 30000);

    // Cleanup
    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
      clearInterval(intervalId);
    };
  }, [isOnline, onStatusChange]);

  // Don't render jika online dan showWhenOnline = false (dan tidak baru online)
  if (isOnline && !showWhenOnline && !wasOffline) {
    return null;
  }

  const positionClasses = {
    top: 'top-0',
    bottom: 'bottom-0',
  };

  return (
    <div
      className={cn(
        'fixed left-0 right-0 z-50 px-4 py-2',
        positionClasses[position],
        className
      )}
    >
      {!isOnline && (
        <Alert
          variant="destructive"
          className="border-destructive/50 bg-destructive/10 backdrop-blur-sm animate-in slide-in-from-top-2"
        >
          <WifiOff className="h-4 w-4" />
          <AlertDescription className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <AlertCircle className="h-4 w-4" />
              <span className="font-medium">Tidak ada koneksi internet</span>
            </div>
            <span className="text-xs text-destructive/70">
              Beberapa fitur mungkin tidak tersedia
            </span>
          </AlertDescription>
        </Alert>
      )}

      {isOnline && wasOffline && (
        <Alert className="border-success/50 bg-success/10 backdrop-blur-sm animate-in slide-in-from-top-2">
          <Wifi className="h-4 w-4 text-success" />
          <AlertDescription className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <span className="font-medium text-success">Kembali online</span>
            </div>
            <span className="text-xs text-success/70">
              Koneksi internet telah pulih
            </span>
          </AlertDescription>
        </Alert>
      )}

      {isOnline && showWhenOnline && !wasOffline && (
        <Alert className="border-success/30 bg-success/5 backdrop-blur-sm">
          <Wifi className="h-4 w-4 text-success" />
          <AlertDescription className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <span className="text-sm text-success/80">Online</span>
            </div>
            <span className="text-xs text-success/50">
              Koneksi stabil
            </span>
          </AlertDescription>
        </Alert>
      )}
    </div>
  );
}

/**
 * Hook untuk cek network status tanpa UI
 * Berguna untuk conditional logic based on connectivity
 *
 * @example
 * ```tsx
 * const isOnline = useNetworkStatus();
 *
 * if (!isOnline) {
 *   return <OfflineMessage />;
 * }
 * ```
 */
export function useNetworkStatus(): boolean {
  const [isOnline, setIsOnline] = useState(navigator.onLine);

  useEffect(() => {
    const handleOnline = () => setIsOnline(true);
    const handleOffline = () => setIsOnline(false);

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, []);

  return isOnline;
}
