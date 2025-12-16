import { useState, useEffect } from 'react';
import { useNavigate, useSearch } from '@tanstack/react-router';
import { Loader2, CheckCircle2, XCircle, User, AlertTriangle, Camera } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { verifyFaceDeepFace } from '@/lib/api/face-recognition';
import { checkIn, checkOut } from '@/lib/api/attendance';
import { useAuthStore } from '@/stores';
import { AutoCaptureFace } from '@/components/attendance/auto-capture-face';
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

  const [faceState, setFaceState] = useState<FaceVerificationState>({
    status: 'camera',
    message: 'Mengaktifkan kamera...',
  });

  const [showOverwriteConfirm, setShowOverwriteConfirm] = useState(false);

  // Auto-submit effect
  useEffect(() => {
    let timer: ReturnType<typeof setTimeout>;
    if (faceState.status === 'verified' && !showOverwriteConfirm) {
      timer = setTimeout(() => {
        handleConfirm();
      }, 2000); // 2 seconds delay
    }
    return () => clearTimeout(timer);
  }, [faceState.status, showOverwriteConfirm]);

  const handleCancel = () => {
    const isAdmin = user?.role === 'admin' || user?.role === 'super-admin' || user?.role === 'kepala-sekolah';
    navigate({ to: isAdmin ? '/admin/attendance' : '/employee/attendance' });
  };

  const handleRetry = () => {
    setFaceState({
      status: 'camera',
      message: 'Mengaktifkan kamera...',
    });
  };

  const onFaceCaptured = async (videoElement: HTMLVideoElement): Promise<void> => {
    setFaceState({
      status: 'verifying',
      message: 'Memverifikasi wajah...',
    });

    // Capture image from video
    const canvas = document.createElement('canvas');
    canvas.width = videoElement.videoWidth;
    canvas.height = videoElement.videoHeight;
    const ctx = canvas.getContext('2d');

    if (!ctx) {
      setFaceState({ status: 'failed', message: 'Gagal memproses gambar', error: 'Canvas context error' });
      throw new Error('Canvas context error');
    }

    ctx.drawImage(videoElement, 0, 0);

    // Convert to file using Promise wrapper (so we properly await the backend call)
    const blob = await new Promise<Blob | null>((resolve) => {
      canvas.toBlob((b) => resolve(b), 'image/jpeg', 0.95);
    });

    if (!blob) {
      setFaceState({ status: 'failed', message: 'Gagal mengambil gambar', error: 'Blob conversion failed' });
      throw new Error('Blob conversion failed');
    }

    const imageFile = new File([blob], "capture.jpg", { type: "image/jpeg" });

    try {
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
          message: 'Wajah terverifikasi!',
          employeeName: employee.name || employee.full_name,
          employeeCode: employee.employee_code || employee.employee_id,
          similarity: similarity,
          distance: distance,
          confidence: confidence,
          liveness_passed: livenessPassed,
        });
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
        throw new Error(errorMessage);
      }
    } catch (error: any) {
      console.error('DeepFace verification error:', error);
      setFaceState({
        status: 'failed',
        message: 'Gagal memverifikasi wajah',
        error: error.response?.data?.message || error.message || 'Terjadi kesalahan saat verifikasi',
      });
      throw error;
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
        face_confidence: faceState.confidence ?? faceState.similarity ?? 0,
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

  return (
    <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900">

      {/* Content */}
      <div className="px-4 py-8">
        <div className="bg-card rounded-3xl p-6 shadow-xl border border-border/50 space-y-6 max-w-lg mx-auto">

          <div className="text-center space-y-2">
            <h1 className="text-2xl font-bold">Verifikasi Wajah</h1>
            <p className="text-muted-foreground text-sm">Validasi kehadiran Anda</p>
          </div>

          {/* Camera/Active Verification */}
          {(faceState.status === 'camera' || faceState.status === 'ready' || faceState.status === 'capturing' || faceState.status === 'verifying') && (
            <AutoCaptureFace
              onCapture={onFaceCaptured}
              onError={(err) => setFaceState({ status: 'failed', message: 'Gagal kamera', error: err })}
              autoCapture={true}
              className="w-full"
            />
          )}

          {/* Success State */}
          {faceState.status === 'verified' && !showOverwriteConfirm && (
            <div className="text-center py-8">
              <div className="bg-emerald-50 dark:bg-emerald-950/50 rounded-full p-4 w-fit mx-auto mb-4 border-4 border-emerald-100 dark:border-emerald-900 animate-in zoom-in spin-in-3">
                <CheckCircle2 className="h-16 w-16 text-emerald-600 dark:text-emerald-400" />
              </div>
              <h2 className="text-xl font-bold mb-1">Identitas Terverifikasi!</h2>
              <p className="text-sm text-emerald-600 dark:text-emerald-400 font-medium mb-4 animate-pulse">
                Mengirim absen otomatis...
              </p>

              {faceState.employeeName && (
                <div className="bg-muted/30 rounded-xl p-4 mt-6 space-y-3">
                  <div className="flex items-center gap-3 justify-center">
                    <User className="h-5 w-5 text-primary" />
                    <div className="text-left">
                      <p className="text-xs text-muted-foreground">Karyawan</p>
                      <p className="text-sm font-semibold">{faceState.employeeName}</p>
                    </div>
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
                Anda sudah absen. Absen ulang?
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

          {/* Failed State */}
          {faceState.status === 'failed' && !showOverwriteConfirm && (
            <div className="text-center py-12">
              <div className="bg-destructive/10 rounded-full p-4 w-fit mx-auto mb-4">
                <XCircle className="h-12 w-12 text-destructive" />
              </div>
              <p className="text-sm font-semibold mb-2">{faceState.message}</p>
              {faceState.error && (
                <p className="text-xs text-muted-foreground mb-6 max-w-[250px] mx-auto overflow-hidden text-ellipsis">{faceState.error}</p>
              )}
              <Button onClick={handleRetry} variant="outline">
                <Camera className="h-4 w-4 mr-2" />
                Coba Lagi
              </Button>
            </div>
          )}

          {/* Loading State (Submitting) */}
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
                Konfirmasi
              </Button>
            </div>
          )}

          {/* Failed Actions */}
          {faceState.status === 'failed' && !showOverwriteConfirm && (
            <div className="flex gap-3 pt-4">
              <Button onClick={handleCancel} variant="outline" className="flex-1">
                Kembali
              </Button>
            </div>
          )}

        </div>
      </div>
    </div>
  );
}

export default VerifyFacePage;
