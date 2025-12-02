import { useState, useEffect } from 'react';
import { useNavigate, useSearch } from '@tanstack/react-router';
import { Loader2, CheckCircle2, XCircle, Camera, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { verifyFaceDeepFace } from '@/lib/api/face-recognition';
import { checkIn, checkOut } from '@/lib/api/attendance';
import { useAuthStore } from '@/stores';
import { useCameraCapture } from '@/hooks/use-camera-capture';
import type { CheckRequest } from '@/types/attendance';

interface FaceVerificationState {
  status: 'camera' | 'ready' | 'capturing' | 'verifying' | 'verified' | 'failed' | 'submitting';
  message: string;
  employeeName?: string;
  employeeCode?: string;
  similarity?: number;
  distance?: number;
  confidence?: number;
  error?: string;
  liveness_passed?: boolean;
}

export function VerifyFacePage() {
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const search = useSearch({ strict: false }) as {
    type?: 'check-in' | 'check-out';
    latitude?: number;
    longitude?: number;
  };
  const type = search.type || 'check-in';
  const latitude = search.latitude;
  const longitude = search.longitude;

  const [capturedImage, setCapturedImage] = useState<string | null>(null);
  const [faceState, setFaceState] = useState<FaceVerificationState>({
    status: 'camera',
    message: 'Mengaktifkan kamera...',
  });

  const {
    videoRef,
    errorMessage,
    startCamera,
    stopCamera,
    captureImage,
  } = useCameraCapture();

  const [currentLocation, setCurrentLocation] = useState<{ latitude: number; longitude: number } | null>(null);

  // Initialize and start camera
  useEffect(() => {
    const init = async () => {
      try {
        await startCamera();
        setFaceState({ status: 'ready', message: 'Siap! Tekan tombol untuk verifikasi wajah' });
      } catch (error) {
        console.error('Camera error:', error);
        setFaceState({
          status: 'failed',
          message: 'Gagal mengakses kamera',
          error: errorMessage || 'Tidak dapat mengakses kamera',
        });
      }
    };

    init();

    // Get location if not provided
    if (!latitude || !longitude) {
      if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(
          (position) => {
            setCurrentLocation({
              latitude: position.coords.latitude,
              longitude: position.coords.longitude,
            });
          },
          (error) => {
            console.error('Geolocation error:', error);
          }
        );
      }
    }

    return () => {
      stopCamera();
    };
  }, []);

  const handleCapture = async () => {
    if (faceState.status !== 'ready') return;

    try {
      setFaceState({
        status: 'capturing',
        message: 'Mengambil gambar...',
      });

      // Capture image from video
      const imageFile = await captureImage();

      // Convert to data URL for preview
      const reader = new FileReader();
      reader.onload = () => {
        setCapturedImage(reader.result as string);
      };
      reader.readAsDataURL(imageFile);

      setFaceState({
        status: 'verifying',
        message: 'Memverifikasi dengan DeepFace ArcFace...',
      });

      // Verify face with DeepFace (includes liveness detection)
      const result = await verifyFaceDeepFace(imageFile);

      if (result.success && result.matched && result.employee) {
        const { employee, confidence, distance, similarity, liveness_passed } = result;

        setFaceState({
          status: 'verified',
          message: 'Wajah terverifikasi dengan DeepFace!',
          employeeName: employee.name,
          employeeCode: employee.employee_code,
          similarity: similarity,
          distance: distance,
          confidence: confidence,
          liveness_passed: liveness_passed,
        });

        stopCamera();
      } else {
        setFaceState({
          status: 'failed',
          message: 'Wajah tidak dikenali',
          error: result.message || 'Silakan coba lagi atau hubungi admin untuk registrasi wajah',
        });
      }
    } catch (error: any) {
      console.error('DeepFace verification error:', error);
      setFaceState({
        status: 'failed',
        message: 'Gagal memverifikasi wajah',
        error: error.response?.data?.message || error.message || 'Terjadi kesalahan saat verifikasi',
      });
    }
  };

  const handleConfirm = async () => {
    const finalLatitude = latitude || currentLocation?.latitude;
    const finalLongitude = longitude || currentLocation?.longitude;

    if (faceState.status !== 'verified' || !finalLatitude || !finalLongitude) {
      if (!finalLatitude || !finalLongitude) {
        alert('Lokasi tidak ditemukan. Pastikan GPS aktif.');
      }
      return;
    }

    setFaceState({
      ...faceState,
      status: 'submitting',
      message: 'Menyimpan absensi...',
    });

    if (!user?.employee?.id) {
      setFaceState({
        ...faceState,
        status: 'failed',
        message: 'Data karyawan tidak ditemukan. Silakan login ulang.',
      });
      return;
    }

    try {
      const attendanceData: CheckRequest = {
        employee_id: user.employee.id,
        action: type === 'check-in' ? 'check_in' : 'check_out',
        location: {
          latitude: finalLatitude,
          longitude: finalLongitude,
        },
        type: type === 'check-in' ? 'check_in' : 'check_out',
        face_confidence: Number(faceState.confidence || faceState.similarity || 0),
        latitude: finalLatitude,
        longitude: finalLongitude,
        notes: `${type === 'check-in' ? 'Check-in' : 'Check-out'} via server-side face recognition`,
        metadata: {
          device: navigator.userAgent,
          platform: navigator.platform,
          type: type,
          face_verification: {
            similarity: faceState.similarity,
            distance: faceState.distance,
            confidence: faceState.confidence,
            algorithm: 'dlib',
            server_side: true,
          },
        },
      };

      if (type === 'check-in') {
        await checkIn(attendanceData);
      } else {
        await checkOut(attendanceData);
      }

      // Success - navigate back to attendance page
      const isAdmin = user?.role === 'admin' || user?.role === 'super-admin' || user?.role === 'kepala-sekolah';
      navigate({ to: isAdmin ? '/admin/attendance' : '/employee/attendance' });
    } catch (error: any) {
      console.error('Attendance submission error:', error);
      setFaceState({
        ...faceState,
        status: 'failed',
        error: error.response?.data?.message || 'Gagal menyimpan absensi',
      });
    }
  };

  const handleCancel = () => {
    stopCamera();
    const isAdmin = user?.role === 'admin' || user?.role === 'super-admin' || user?.role === 'kepala-sekolah';
    navigate({ to: isAdmin ? '/admin/attendance' : '/employee/attendance' });
  };

  const handleRetry = async () => {
    setCapturedImage(null);
    setFaceState({
      status: 'ready',
      message: 'Siap! Tekan tombol untuk verifikasi wajah',
    });
    await startCamera();
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
          <div className="flex-1">
            <h1 className="text-lg font-bold">Verifikasi Wajah</h1>
            <p className="text-xs text-muted-foreground">
              {type === 'check-in' ? 'Absensi Datang' : 'Absensi Pulang'}
            </p>
          </div>
        </div>
      </div>

      {/* Content */}
      <div className="px-4 pb-4">
        <div className="bg-card rounded-3xl p-6 shadow-xl border border-border/50 space-y-6">
          {/* Camera/Captured Image View */}
          {(faceState.status === 'camera' || faceState.status === 'ready' || faceState.status === 'capturing' || faceState.status === 'verifying') && (
            <div className="relative">
              <div className="relative rounded-2xl overflow-hidden bg-black aspect-[4/3]">
                {!capturedImage ? (
                  <>
                    <video
                      ref={videoRef}
                      className="w-full h-full object-cover"
                      autoPlay
                      muted
                      playsInline
                    />

                    {/* Camera Loading Overlay */}
                    {faceState.status === 'camera' && (
                      <div className="absolute inset-0 flex items-center justify-center bg-black/60 z-10">
                        <div className="text-center text-white">
                          <Loader2 className="h-8 w-8 mx-auto mb-2 animate-spin" />
                          <p className="text-sm">{faceState.message}</p>
                        </div>
                      </div>
                    )}

                    {/* Ready State - Show Capture Button Hint */}
                    {faceState.status === 'ready' && (
                      <div className="absolute top-4 left-0 right-0 flex justify-center z-20">
                        <div className="bg-emerald-500/90 text-white px-4 py-2 rounded-full text-sm font-medium shadow-lg">
                          Siap - Tekan tombol di bawah
                        </div>
                      </div>
                    )}
                  </>
                ) : (
                  <img
                    src={capturedImage}
                    alt="Captured"
                    className="w-full h-full object-cover"
                  />
                )}
              </div>

              {/* Status message */}
              {(faceState.status === 'capturing' || faceState.status === 'verifying') && (
                <div className="absolute bottom-4 left-0 right-0 flex justify-center z-20">
                  <div className="bg-black/70 backdrop-blur-sm px-4 py-2 rounded-full flex items-center gap-2">
                    <Loader2 className="h-3 w-3 animate-spin text-white" />
                    <p className="text-white text-xs text-center">
                      {faceState.message}
                    </p>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Capture Button - Only show when ready */}
          {faceState.status === 'ready' && (
            <Button
              onClick={handleCapture}
              className="w-full bg-emerald-600 hover:bg-emerald-700"
              size="lg"
            >
              <Camera className="h-5 w-5 mr-2" />
              Verifikasi Wajah Sekarang
            </Button>
          )}

          {/* Success State */}
          {faceState.status === 'verified' && (
            <div className="text-center py-8">
              <div className="bg-emerald-50 dark:bg-emerald-950/50 rounded-full p-4 w-fit mx-auto mb-4 border-4 border-emerald-100 dark:border-emerald-900">
                <CheckCircle2 className="h-16 w-16 text-emerald-600 dark:text-emerald-400" />
              </div>
              <h2 className="text-xl font-bold mb-2">Wajah Ditemukan!</h2>
              <p className="text-sm text-muted-foreground mb-4">{faceState.message}</p>

              {faceState.employeeName && (
                <div className="bg-muted/30 rounded-xl p-4 mt-6 space-y-3">
                  <div className="flex items-center gap-3 justify-center">
                    <User className="h-5 w-5 text-primary" />
                    <div className="text-left">
                      <p className="text-xs text-muted-foreground">Nama Karyawan</p>
                      <p className="text-sm font-semibold">{faceState.employeeName}</p>
                      {faceState.employeeCode && (
                        <p className="text-xs text-muted-foreground">{faceState.employeeCode}</p>
                      )}
                    </div>
                  </div>

                  {/* Verification metrics */}
                  <div className="pt-3 border-t border-border/50 space-y-2">
                    {faceState.similarity !== undefined && (
                      <div className="flex justify-between items-center">
                        <span className="text-xs text-muted-foreground">Similarity</span>
                        <span className="text-sm font-bold text-emerald-600 dark:text-emerald-400">
                          {(faceState.similarity * 100).toFixed(1)}%
                        </span>
                      </div>
                    )}
                    {faceState.confidence !== undefined && (
                      <div className="flex justify-between items-center">
                        <span className="text-xs text-muted-foreground">Confidence</span>
                        <span className="text-sm font-semibold">
                          {(faceState.confidence * 100).toFixed(0)}%
                        </span>
                      </div>
                    )}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Failed State */}
          {faceState.status === 'failed' && (
            <div className="text-center py-12">
              <div className="bg-destructive/10 rounded-full p-4 w-fit mx-auto mb-4">
                <XCircle className="h-12 w-12 text-destructive" />
              </div>
              <p className="text-sm font-semibold mb-2">{faceState.message}</p>
              {faceState.error && (
                <p className="text-xs text-muted-foreground mb-6">{faceState.error}</p>
              )}
              <Button onClick={handleRetry} variant="outline">
                <Camera className="h-4 w-4 mr-2" />
                Coba Lagi
              </Button>
            </div>
          )}

          {/* Submitting State */}
          {faceState.status === 'submitting' && (
            <div className="text-center py-12">
              <Loader2 className="h-12 w-12 animate-spin text-primary mx-auto mb-4" />
              <p className="text-sm font-medium">{faceState.message}</p>
            </div>
          )}

          {/* Actions */}
          {faceState.status === 'verified' && (
            <div className="flex gap-3 pt-4">
              <Button onClick={handleCancel} variant="outline" className="flex-1">
                Batal
              </Button>
              <Button onClick={handleConfirm} className="flex-1 bg-emerald-600 hover:bg-emerald-700">
                <CheckCircle2 className="h-4 w-4 mr-2" />
                Konfirmasi Kehadiran
              </Button>
            </div>
          )}

          {faceState.status === 'failed' && (
            <div className="flex gap-3 pt-4">
              <Button onClick={handleCancel} variant="outline" className="flex-1">
                Batal
              </Button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export default VerifyFacePage;
