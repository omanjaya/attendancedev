import { useState, useEffect, useRef } from 'react';
import { useNavigate, useSearch } from '@tanstack/react-router';
import { ScanFace, Loader2, CheckCircle2, XCircle, Camera, User, Zap } from 'lucide-react';
import { Button } from '@/components/ui/card';
import { verifyFaceServer } from '@/lib/api/face-recognition';
import { checkIn, checkOut } from '@/lib/api/attendance';
import { captureAndCompress } from '@/lib/utils/imageCompression';
import { useAuthStore } from '@/stores';

interface FaceVerificationState {
  status: 'ready' | 'capturing' | 'verifying' | 'verified' | 'failed' | 'submitting';
  message: string;
  employeeName?: string;
  employeeCode?: string;
  similarity?: number;
  distance?: number;
  confidence?: number;
  error?: string;
  imageSize?: string;
}

export function VerifyFacePage() {
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const search = useSearch({ from: '/_authenticated/attendance/verify-face' }) as {
    type?: 'check-in' | 'check-out';
    latitude?: number;
    longitude?: number;
  };
  const type = search.type || 'check-in';
  const latitude = search.latitude;
  const longitude = search.longitude;

  const videoRef = useRef<HTMLVideoElement>(null);
  const streamRef = useRef<MediaStream | null>(null);
  const [capturedImage, setCapturedImage] = useState<string | null>(null);

  const [faceState, setFaceState] = useState<FaceVerificationState>({
    status: 'ready',
    message: 'Siap untuk scan wajah',
  });

  const [isAutoCapturing, setIsAutoCapturing] = useState(false);

  // Start camera on mount
  useEffect(() => {
    startCamera();
    return () => {
      // Cleanup camera stream
      if (streamRef.current) {
        streamRef.current.getTracks().forEach((track) => track.stop());
      }
    };
  }, []);

  // Auto-capture after 2 seconds if ready
  useEffect(() => {
    if (faceState.status === 'ready' && !isAutoCapturing) {
      const timer = setTimeout(() => {
        handleCapture();
      }, 2000); // Auto-capture after 2 seconds

      return () => clearTimeout(timer);
    }
  }, [faceState.status, isAutoCapturing]);

  const startCamera = async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: 'user',
          width: { ideal: 640 },
          height: { ideal: 480 },
        },
      });

      if (videoRef.current) {
        videoRef.current.srcObject = stream;
        streamRef.current = stream;

        // Wait for video to be ready
        videoRef.current.onloadedmetadata = () => {
          if (videoRef.current) {
            videoRef.current.play();
          }
        };
      }

      setFaceState({
        status: 'ready',
        message: 'Posisikan wajah Anda, scanning otomatis dalam 2 detik...',
      });
    } catch (error) {
      console.error('Error accessing camera:', error);
      setFaceState({
        status: 'failed',
        message: 'Tidak dapat mengakses kamera',
        error: 'Pastikan izin kamera telah diberikan',
      });
    }
  };

  const handleCapture = async () => {
    if (!videoRef.current || faceState.status !== 'ready') return;

    setIsAutoCapturing(true);

    try {
      setFaceState({
        status: 'capturing',
        message: 'Mengambil gambar...',
      });

      // Capture and compress image
      const { dataUrl, formattedSize } = await captureAndCompress(videoRef.current, {
        maxWidth: 640,
        maxHeight: 480,
        quality: 0.8,
        mimeType: 'image/jpeg',
      });

      setCapturedImage(dataUrl);

      setFaceState({
        status: 'verifying',
        message: 'Memverifikasi wajah dengan server...',
        imageSize: formattedSize,
      });

      // Verify face with server
      const result = await verifyFaceServer({
        image: dataUrl,
        tolerance: 0.6,
      });

      if (result.matched && result.data?.employee) {
        const { employee, distance, similarity, confidence } = result.data;

        setFaceState({
          status: 'verified',
          message: 'Wajah terverifikasi!',
          employeeName: employee.name,
          employeeCode: employee.employee_code,
          similarity: similarity,
          distance: distance,
          confidence: confidence,
        });

        // Stop camera after verification
        if (streamRef.current) {
          streamRef.current.getTracks().forEach((track) => track.stop());
        }
      } else {
        setFaceState({
          status: 'failed',
          message: 'Wajah tidak dikenali',
          error: result.message || 'Silakan coba lagi atau hubungi admin untuk registrasi wajah',
        });
        setIsAutoCapturing(false);
      }
    } catch (error: any) {
      console.error('Verification error:', error);
      setFaceState({
        status: 'failed',
        message: 'Gagal memverifikasi wajah',
        error: error.response?.data?.message || error.message || 'Terjadi kesalahan saat verifikasi',
      });
      setIsAutoCapturing(false);
    }
  };

  const handleConfirm = async () => {
    if (faceState.status !== 'verified' || !latitude || !longitude) return;

    setFaceState({
      ...faceState,
      status: 'submitting',
      message: 'Menyimpan absensi...',
    });

    try {
      const attendanceData = {
        face_confidence: faceState.confidence || faceState.similarity || 0,
        latitude,
        longitude,
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
      alert(`Absensi ${type === 'check-in' ? 'datang' : 'pulang'} berhasil!`);
      navigate({ to: '/attendance' });
    } catch (error: any) {
      console.error('Attendance submission error:', error);
      alert(error.response?.data?.message || 'Gagal menyimpan absensi');
      setFaceState({
        ...faceState,
        status: 'failed',
        error: error.response?.data?.message || 'Gagal menyimpan absensi',
      });
    }
  };

  const handleCancel = () => {
    if (streamRef.current) {
      streamRef.current.getTracks().forEach((track) => track.stop());
    }
    navigate({ to: '/attendance' });
  };

  const handleRetry = () => {
    setCapturedImage(null);
    setIsAutoCapturing(false);
    setFaceState({
      status: 'ready',
      message: 'Siap untuk scan wajah',
    });
    startCamera();
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
          {faceState.imageSize && (
            <div className="flex items-center gap-1 text-xs text-muted-foreground bg-primary/10 px-2 py-1 rounded-full">
              <Zap className="h-3 w-3" />
              {faceState.imageSize}
            </div>
          )}
        </div>
      </div>

      {/* Content */}
      <div className="px-4 pb-4">
        <div className="bg-card rounded-3xl p-6 shadow-xl border border-border/50 space-y-6">
          {/* Camera/Captured Image View */}
          {(faceState.status === 'ready' || faceState.status === 'capturing' || faceState.status === 'verifying') && (
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
                    {/* Scan overlay - only show when ready */}
                    {faceState.status === 'ready' && (
                      <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div className="w-48 h-48 border-4 border-primary rounded-full animate-pulse" />
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
              <div className="absolute bottom-4 left-0 right-0 flex justify-center">
                <div className="bg-black/70 backdrop-blur-sm px-4 py-2 rounded-full flex items-center gap-2">
                  {(faceState.status === 'capturing' || faceState.status === 'verifying') && (
                    <Loader2 className="h-3 w-3 animate-spin text-white" />
                  )}
                  <p className="text-white text-xs text-center">
                    {faceState.message}
                  </p>
                </div>
              </div>
            </div>
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
                    {faceState.distance !== undefined && (
                      <div className="flex justify-between items-center">
                        <span className="text-xs text-muted-foreground">Distance</span>
                        <span className="text-sm font-mono">
                          {faceState.distance.toFixed(3)}
                        </span>
                      </div>
                    )}
                    <div className="flex justify-between items-center pt-2 border-t border-border/30">
                      <span className="text-xs text-muted-foreground">Algorithm</span>
                      <span className="text-xs font-semibold">dlib (server-side)</span>
                    </div>
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

          {faceState.status === 'ready' && !isAutoCapturing && (
            <div className="flex gap-3 pt-4">
              <Button onClick={handleCancel} variant="outline" className="flex-1">
                Batal
              </Button>
              <Button onClick={handleCapture} className="flex-1">
                <ScanFace className="h-4 w-4 mr-2" />
                Scan Sekarang
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
