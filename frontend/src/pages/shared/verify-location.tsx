import { useState, useEffect } from 'react';
import { useNavigate, useSearch } from '@tanstack/react-router';
import { MapPin, Loader2, CheckCircle2, XCircle, Navigation, ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { verifyLocation, type LocationVerificationResponse } from '@/lib/api/attendance';
import { useAuthStore } from '@/stores';
import { LocationMap } from '@/components/maps/LocationMap';
import { cn } from '@/lib/utils';
import { Card } from '@/components/ui/card';

interface LocationState {
  latitude: number | null;
  longitude: number | null;
  error: string | null;
  loading: boolean;
}

export function VerifyLocationPage() {
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const search = useSearch({ strict: false }) as { type?: 'check-in' | 'check-out'; overwrite?: boolean };
  const type = search.type || 'check-in';

  const [locationState, setLocationState] = useState<LocationState>({
    latitude: null,
    longitude: null,
    error: null,
    loading: true,
  });

  const [verification, setVerification] = useState<LocationVerificationResponse | null>(null);
  const [verifying, setVerifying] = useState(false);

  // Get user's current location
  useEffect(() => {
    if ('geolocation' in navigator) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          setLocationState({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            error: null,
            loading: false,
          });
        },
        (_error) => {
          console.error("GPS Error", _error);
          setLocationState({
            latitude: null,
            longitude: null,
            error: 'Tidak dapat mengakses lokasi. Pastikan GPS dan izin lokasi aktif.',
            loading: false,
          });
        },
        {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 0,
        }
      );
    } else {
      setLocationState({
        latitude: null,
        longitude: null,
        error: 'Browser tidak mendukung GPS',
        loading: false,
      });
    }
  }, []);

  // Auto-verify location when GPS is ready
  useEffect(() => {
    if (locationState.latitude && locationState.longitude && !verification && !verifying) {
      handleVerifyLocation();
    }
  }, [locationState.latitude, locationState.longitude]);

  const handleVerifyLocation = async () => {
    if (!locationState.latitude || !locationState.longitude) return;

    setVerifying(true);
    try {
      const result = await verifyLocation({
        latitude: locationState.latitude,
        longitude: locationState.longitude,
      });
      setVerification(result);
    } catch (error: any) {
      setLocationState({
        ...locationState,
        error: error.response?.data?.message || 'Gagal memverifikasi lokasi',
      });
    } finally {
      setVerifying(false);
    }
  };

  const handleNext = () => {
    if (!verification?.verified || !locationState.latitude || !locationState.longitude) return;

    // Navigate to face recognition page with location data
    navigate({
      to: '/shared/verify-face',
      search: {
        type,
        latitude: locationState.latitude,
        longitude: locationState.longitude,
        overwrite: search.overwrite,
      },
    });
  };

  const handleCancel = () => {
    const isAdmin = user?.role === 'admin' || user?.role === 'super-admin' || user?.role === 'kepala-sekolah';
    navigate({ to: isAdmin ? '/admin/attendance' : '/employee/attendance' });
  };

  return (
    <div className="relative h-screen w-full bg-background overflow-hidden flex flex-col">
      {/* Header Overlay */}
      <div className="absolute top-0 left-0 right-0 z-20 p-4 bg-gradient-to-b from-black/50 to-transparent pointer-events-none">
        <div className="flex items-center gap-3 pointer-events-auto">
          <Button
            size="icon"
            variant="secondary"
            className="h-10 w-10 rounded-full bg-white/90 shadow-sm hover:bg-white"
            onClick={handleCancel}
          >
            <ArrowLeft className="h-5 w-5 text-foreground" />
          </Button>
          <div className="bg-white/90 backdrop-blur-md px-4 py-2 rounded-full shadow-sm">
            <h1 className="text-sm font-bold text-foreground">
              {type === 'check-in' ? 'Absensi Datang' : 'Absensi Pulang'}
            </h1>
          </div>
        </div>
      </div>

      {/* Main Map Area */}
      <div className="flex-1 relative bg-muted">
        {locationState.latitude && locationState.longitude ? (
          <LocationMap
            userLocation={{
              latitude: locationState.latitude,
              longitude: locationState.longitude,
            }}
            officeLocation={verification?.location ? {
              latitude: verification.location.latitude,
              longitude: verification.location.longitude,
              name: verification.location.name,
              address: verification.location.address,
              radius: verification.location.radius_meters,
            } : undefined}
            isWithinRadius={verification?.verified}
            distance={verification?.distance}
            className="h-full w-full rounded-none border-0"
          />
        ) : (
          <div className="h-full w-full flex items-center justify-center bg-muted/20">
            {locationState.loading ? (
              <div className="text-center">
                <Loader2 className="h-10 w-10 animate-spin text-primary mx-auto mb-3" />
                <p className="text-muted-foreground text-sm">Mencari lokasi...</p>
              </div>
            ) : (
              <div className="text-center p-6">
                <div className="bg-destructive/10 p-4 rounded-full w-fit mx-auto mb-4">
                  <MapPin className="h-8 w-8 text-destructive" />
                </div>
                <p className="text-destructive font-medium mb-1">Lokasi Tidak Ditemukan</p>
                <p className="text-xs text-muted-foreground mb-4 max-w-[200px] mx-auto">
                  {locationState.error || "Pastikan GPS aktif"}
                </p>
                <Button onClick={() => window.location.reload()} size="sm" variant="outline">
                  Coba Lagi
                </Button>
              </div>
            )}
          </div>
        )}
      </div>

      {/* Bottom Sheet Card */}
      <div className="absolute bottom-0 left-0 right-0 z-20 p-4">
        <Card className="p-5 shadow-2xl border-t border-x border-b-0 rounded-t-3xl rounded-b-xl backdrop-blur-xl bg-background/95 supports-[backdrop-filter]:bg-background/80">
          {verifying ? (
            <div className="py-4 text-center space-y-3">
              <Loader2 className="h-8 w-8 animate-spin text-primary mx-auto" />
              <p className="font-medium text-sm">Memverifikasi radius kantor...</p>
            </div>
          ) : verification ? (
            <div className="space-y-5">
              {/* Status Header */}
              <div className="flex items-center gap-4">
                <div className={cn(
                  "h-12 w-12 rounded-full flex items-center justify-center shrink-0 shadow-sm",
                  verification.verified
                    ? "bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-400"
                    : "bg-destructive/10 text-destructive"
                )}>
                  {verification.verified ? (
                    <CheckCircle2 className="h-6 w-6" />
                  ) : (
                    <XCircle className="h-6 w-6" />
                  )}
                </div>
                <div>
                  <h2 className="font-bold text-lg">
                    {verification.verified ? 'Dalam Jangkauan' : 'Di Luar Jangkauan'}
                  </h2>
                  <p className="text-sm text-muted-foreground">
                    {verification.verified ? 'Lokasi Anda sesuai' : 'Anda terlalu jauh dari kantor'}
                  </p>
                </div>
              </div>

              {/* Details Grid */}
              <div className="grid grid-cols-2 gap-3">
                <div className="bg-muted/50 p-3 rounded-xl border border-border/50">
                  <p className="text-[10px] uppercase text-muted-foreground font-semibold mb-1">Jarak Anda</p>
                  <p className={cn("text-lg font-bold font-mono",
                    verification.verified ? "text-emerald-600 dark:text-emerald-400" : "text-destructive"
                  )}>
                    {verification.distance.toFixed(0)}m
                  </p>
                </div>
                <div className="bg-muted/50 p-3 rounded-xl border border-border/50">
                  <p className="text-[10px] uppercase text-muted-foreground font-semibold mb-1">Maks. Radius</p>
                  <p className="text-lg font-bold font-mono text-foreground">
                    {verification.location?.radius_meters || '-'}m
                  </p>
                </div>
              </div>

              {/* Location Name */}
              {verification.location && (
                <div className="flex items-start gap-2 px-1">
                  <MapPin className="h-4 w-4 text-muted-foreground mt-0.5 shrink-0" />
                  <p className="text-xs text-muted-foreground">
                    {verification.location.name} - {verification.location.address}
                  </p>
                </div>
              )}

              {/* Actions */}
              <div className="pt-2">
                {verification.verified ? (
                  <Button className="w-full bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-500/20" size="lg" onClick={handleNext}>
                    Lanjut ke Foto Wajah
                  </Button>
                ) : (
                  <Button className="w-full" variant="outline" size="lg" onClick={handleVerifyLocation}>
                    <Navigation className="h-4 w-4 mr-2" />
                    Cek Lokasi Lagi
                  </Button>
                )}
              </div>
            </div>
          ) : null}
        </Card>
      </div>
    </div>
  );
}

export default VerifyLocationPage;
