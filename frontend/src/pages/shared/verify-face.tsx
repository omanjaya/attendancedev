import { useState, useEffect } from 'react';
import { useNavigate, useSearch } from '@tanstack/react-router';
import { Loader2, CheckCircle2, XCircle, Camera, User, AlertTriangle } from 'lucide-react';
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
    overwrite?: boolean;
  };
  const type = search.type || 'check-in';
  const overwrite = search.overwrite;

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

  const [showOverwriteConfirm, setShowOverwriteConfirm] = useState(false);

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

    return () => {
      stopCamera();
    };
  }, []);

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

      // Extract data from nested response or top-level (fallback)
      const resultData = result.data || result;
      const employee = result.employee || resultData.employee || resultData.employee_data;
      const confidence = result.confidence || resultData.confidence;
      const distance = result.distance || resultData.distance;
      const similarity = result.similarity || resultData.similarity;
      const livenessPassed = result.liveness_passed || resultData.liveness_passed;
      const isMatched = result.matched || resultData.matched;

      if (result.success && isMatched && employee) {
        setFaceState({
          status: 'verified',
          message: 'Wajah terverifikasi dengan DeepFace!',
          employeeName: employee.name || employee.full_name,
          employeeCode: employee.employee_code || employee.employee_id,
          similarity: similarity,
          distance: distance,
          confidence: confidence,
          liveness_passed: livenessPassed,
        });

        stopCamera();
      } else {
        let errorMessage = result.message || 'Silakan coba lagi atau hubungi admin untuk registrasi wajah';
        let statusMessage = 'Wajah tidak dikenali';

        // Handle case where face is matched but employee data is missing in response
        if (isMatched && !employee) {
          statusMessage = 'Data Karyawan Tidak Ditemukan';
          errorMessage = 'Wajah dikenali di sistem tetapi data karyawan terkait tidak ditemukan.';
        }

        setFaceState({
          status: 'failed',
          message: statusMessage,
          error: errorMessage,
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

  const getCurrentLocation = (): Promise<{ latitude: number; longitude: number }> => {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        reject(new Error('Geolocation not supported'));
        return;
      }
      navigator.geolocation.getCurrentPosition(
        (position) => {
          resolve({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
          });
        },
        (error) => {
          reject(error);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    });
  };

  const submitAttendance = async (forceOverwrite: boolean = false) => {
    if (faceState.status !== 'verified') {
      return;
    }

    setFaceState({
      ...faceState,
      status: 'submitting',
      message: 'Memverifikasi lokasi terkini...',
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
      // Force fresh location capture to prevent spoofing
      const location = await getCurrentLocation();

      setFaceState(prev => ({
        ...prev,
        message: 'Menyimpan absensi...',
      }));

      const attendanceData: CheckRequest = {
        employee_id: user.employee.id,
        action: type === 'check-in' ? 'check_in' : 'check_out',
        location: {
          latitude: location.latitude,
          longitude: location.longitude,
        },
        type: type === 'check-in' ? 'check_in' : 'check_out',
        face_confidence: Number(faceState.confidence || faceState.similarity || 0),
        latitude: location.latitude,
        longitude: location.longitude,
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
        overwrite: forceOverwrite || overwrite,
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

      // Handle geolocation errors specifically
      if (error.code === 1 || error.message?.includes('Geolocation')) {
        setFaceState({
          ...faceState,
          status: 'failed',
          message: 'Gagal mendapatkan lokasi terkini. Pastikan GPS aktif.',
          error: error.message
        });
        return;
      }

      const errorMessage = error.response?.data?.message || 'Gagal menyimpan absensi';

      if (errorMessage.includes('Already checked out') || errorMessage.includes('Already checked in')) {
        setShowOverwriteConfirm(true);
        setFaceState({
          ...faceState,
          status: 'verified', // Keep verified status so we can retry
          message: 'Konfirmasi Absen Ulang',
        });
      } else {
        setFaceState({
          ...faceState,
          status: 'failed',
          error: errorMessage,
        });
      }
    }
  };

  const handleConfirm = () => {
    submitAttendance(false);
  };

  const handleForceOverwrite = () => {
    setShowOverwriteConfirm(false);
    submitAttendance(true);
  };

  const handleCancelOverwrite = () => {
    setShowOverwriteConfirm(false);
    handleCancel();
  };

  // ... (existing handleCancel, handleRetry)

  return (
    <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900">
      {/* ... (existing header) */}

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
          {faceState.status === 'verified' && !showOverwriteConfirm && (
            <div className="text-center py-8">
              {/* ... (existing success UI) */}
              <div className="bg-emerald-50 dark:bg-emerald-950/50 rounded-full p-4 w-fit mx-auto mb-4 border-4 border-emerald-100 dark:border-emerald-900">
                <CheckCircle2 className="h-16 w-16 text-emerald-600 dark:text-emerald-400" />
              </div>
              <h2 className="text-xl font-bold mb-2">Wajah Ditemukan!</h2>
              <p className="text-sm text-muted-foreground mb-4">{faceState.message}</p>

              {faceState.employeeName && (
                <div className="bg-muted/30 rounded-xl p-4 mt-6 space-y-3">
                  {/* ... (existing employee info) */}
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

          {/* Overwrite Confirmation State */}
          {showOverwriteConfirm && (
            <div className="text-center py-8">
              <div className="bg-yellow-50 dark:bg-yellow-950/50 rounded-full p-4 w-fit mx-auto mb-4 border-4 border-yellow-100 dark:border-yellow-900">
                <AlertTriangle className="h-16 w-16 text-yellow-600 dark:text-yellow-400" />
              </div>
              <h2 className="text-xl font-bold mb-2">Sudah Absen</h2>
              <p className="text-sm text-muted-foreground mb-6">
                Anda sudah melakukan absensi {type === 'check-in' ? 'datang' : 'pulang'} hari ini. Apakah Anda ingin melakukan absen ulang?
              </p>

              <div className="flex gap-3">
                <Button onClick={handleCancelOverwrite} variant="outline" className="flex-1">
                  Batal
                </Button>
                <Button onClick={handleForceOverwrite} className="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white">
                  Absen Ulang
                </Button>
              </div>
            </div>
          )}

          {/* ... (existing failed state) */}
          {faceState.status === 'failed' && !showOverwriteConfirm && (
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

          {/* ... (existing submitting state) */}
          {faceState.status === 'submitting' && (
            <div className="text-center py-12">
              <Loader2 className="h-12 w-12 animate-spin text-primary mx-auto mb-4" />
              <p className="text-sm font-medium">{faceState.message}</p>
            </div>
          )}

          {/* Actions */}
          {faceState.status === 'verified' && !showOverwriteConfirm && (
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

          {/* ... (existing failed actions) */}
          {faceState.status === 'failed' && !showOverwriteConfirm && (
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
