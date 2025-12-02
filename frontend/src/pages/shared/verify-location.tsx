import { useState, useEffect } from 'react';
import { useNavigate, useSearch } from '@tanstack/react-router';
import { MapPin, Loader2, CheckCircle2, XCircle, Navigation } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { verifyLocation, type LocationVerificationResponse } from '@/lib/api/attendance';
import { useAuthStore } from '@/stores';
import { LocationMap } from '@/components/maps/LocationMap';

interface LocationState {
  latitude: number | null;
  longitude: number | null;
  error: string | null;
  loading: boolean;
}

export function VerifyLocationPage() {
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const search = useSearch({ strict: false }) as { type?: 'check-in' | 'check-out' };
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
        (error) => {
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
      },
    });
  };

  const handleCancel = () => {
    const isAdmin = user?.role === 'admin' || user?.role === 'super-admin' || user?.role === 'kepala-sekolah';
    navigate({ to: isAdmin ? '/admin/attendance' : '/employee/attendance' });
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900">
      {/* Header */}
      <div className="px-4 pt-6 pb-4">
        <div className="flex items-center gap-3 mb-4">
          <button
            onClick={handleCancel}
            className="p-2 hover:bg-muted rounded-lg transition-colors"
          >
            <XCircle className="h-5 w-5" />
          </button>
          <div>
            <h1 className="text-lg font-bold">Verifikasi Lokasi</h1>
            <p className="text-xs text-muted-foreground">
              {type === 'check-in' ? 'Absensi Datang' : 'Absensi Pulang'}
            </p>
          </div>
        </div>
      </div>

      {/* Content */}
      <div className="px-4 pb-4">
        <div className="bg-card rounded-3xl p-6 shadow-xl border border-border/50 space-y-6">
          {/* Loading GPS */}
          {locationState.loading && (
            <div className="text-center py-12">
              <Loader2 className="h-12 w-12 animate-spin text-primary mx-auto mb-4" />
              <p className="text-sm font-medium">Mengambil lokasi GPS...</p>
              <p className="text-xs text-muted-foreground mt-1">
                Mohon izinkan akses lokasi
              </p>
            </div>
          )}

          {/* GPS Error */}
          {locationState.error && !locationState.loading && (
            <div className="text-center py-12">
              <div className="bg-destructive/10 rounded-full p-4 w-fit mx-auto mb-4">
                <XCircle className="h-12 w-12 text-destructive" />
              </div>
              <p className="text-sm font-semibold mb-2">Gagal Mengakses GPS</p>
              <p className="text-xs text-muted-foreground mb-6">{locationState.error}</p>
              <Button onClick={() => window.location.reload()} variant="outline">
                <Navigation className="h-4 w-4 mr-2" />
                Coba Lagi
              </Button>
            </div>
          )}

          {/* Verifying Location */}
          {verifying && !locationState.error && (
            <div className="text-center py-12">
              <Loader2 className="h-12 w-12 animate-spin text-primary mx-auto mb-4" />
              <p className="text-sm font-medium">Memverifikasi lokasi...</p>
            </div>
          )}

          {/* Verification Result */}
          {verification && !verifying && !locationState.error && (
            <div className="space-y-6">
              {/* Alert if no location assigned */}
              {!verification.location && (
                <div className="bg-amber-50 dark:bg-amber-950/30 border-2 border-amber-200 dark:border-amber-900 rounded-xl p-4">
                  <p className="text-sm font-semibold text-amber-800 dark:text-amber-400 mb-2">
                    ⚠️ Belum Ada Lokasi Terdaftar
                  </p>
                  <p className="text-xs text-amber-700 dark:text-amber-500">
                    Hubungi admin untuk mengatur lokasi kerja Anda.
                  </p>
                </div>
              )}

              {/* Map with Radius */}
              {locationState.latitude && locationState.longitude && (
                <LocationMap
                  userLocation={{
                    latitude: locationState.latitude,
                    longitude: locationState.longitude,
                  }}
                  officeLocation={verification.location ? {
                    latitude: verification.location.latitude,
                    longitude: verification.location.longitude,
                    name: verification.location.name,
                    address: verification.location.address,
                    radius: verification.location.radius_meters,
                  } : undefined}
                  isWithinRadius={verification.verified}
                  distance={verification.distance}
                />
              )}

              {/* Status Icon */}
              <div className="text-center">
                {verification.verified ? (
                  <div className="bg-emerald-50 dark:bg-emerald-950/50 rounded-full p-4 w-fit mx-auto mb-4 border-4 border-emerald-100 dark:border-emerald-900">
                    <CheckCircle2 className="h-16 w-16 text-emerald-600 dark:text-emerald-400" />
                  </div>
                ) : (
                  <div className="bg-destructive/10 rounded-full p-4 w-fit mx-auto mb-4 border-4 border-destructive/20">
                    <XCircle className="h-16 w-16 text-destructive" />
                  </div>
                )}

                <h2 className="text-xl font-bold mb-2">
                  {verification.verified ? 'Lokasi Terverifikasi' : 'Di Luar Jangkauan'}
                </h2>
                <p className="text-sm text-muted-foreground">
                  {verification.message}
                </p>
              </div>

              {/* Location Info */}
              {verification.location && (
                <div className="bg-muted/30 rounded-xl p-4 space-y-3">
                  <div className="flex items-start gap-3">
                    <MapPin className="h-5 w-5 text-primary mt-0.5 shrink-0" />
                    <div className="flex-1">
                      <p className="text-sm font-semibold">{verification.location.name}</p>
                      <p className="text-xs text-muted-foreground mt-1">
                        {verification.location.address}
                      </p>
                    </div>
                  </div>

                  <div className="flex items-center justify-between pt-3 border-t border-border/50">
                    <span className="text-xs text-muted-foreground">Jarak Anda</span>
                    <span
                      className={`text-sm font-bold ${verification.verified
                        ? 'text-emerald-600 dark:text-emerald-400'
                        : 'text-destructive'
                        }`}
                    >
                      {verification.distance.toFixed(0)} meter
                    </span>
                  </div>

                  <div className="flex items-center justify-between pb-0 border-t border-border/50 pt-3">
                    <span className="text-xs text-muted-foreground">Radius Maksimal</span>
                    <span className="text-sm font-semibold">
                      {verification.location.radius_meters} meter
                    </span>
                  </div>
                </div>
              )}

              {/* Coordinates */}
              <div className="bg-muted/20 rounded-lg p-3">
                <p className="text-xs text-muted-foreground mb-1">Koordinat Anda:</p>
                <p className="text-xs font-mono">
                  {locationState.latitude?.toFixed(6)}, {locationState.longitude?.toFixed(6)}
                </p>
              </div>
            </div>
          )}

          {/* Actions */}
          {verification && !verifying && (
            <div className="flex gap-3 pt-4">
              <Button onClick={handleCancel} variant="outline" className="flex-1">
                Batal
              </Button>
              <Button
                onClick={handleNext}
                disabled={!verification.verified}
                className="flex-1"
              >
                Selanjutnya
              </Button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export default VerifyLocationPage;
