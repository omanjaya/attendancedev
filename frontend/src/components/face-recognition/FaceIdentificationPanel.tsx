import { useState, useRef, useCallback, useEffect } from 'react';
import {
  Camera,
  User,
  AlertCircle,
  Loader2,
  RefreshCw,
  UserCheck,
  UserX,
  Scan,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { faceDetectionService } from '@/lib/services/face-detection';
import { useRegisteredFaces, useVerifyFaceDeepFace } from '@/hooks/use-face-recognition-api';
import { useFaceStore } from '@/stores';
import { cn } from '@/lib/utils';
import type { KnownFaceDescriptor } from '@/types/face-recognition';

interface FaceIdentificationPanelProps {
  onIdentified?: (employeeId: string, employeeName: string, confidence: number) => void;
  onNotRecognized?: () => void;
  autoStart?: boolean;
  showHistory?: boolean;
  className?: string;
}

type IdentificationState = 'initializing' | 'ready' | 'scanning' | 'identified' | 'not_recognized' | 'error';

interface IdentifiedEmployee {
  id: string;
  name: string;
  similarity: number;
  timestamp: number;
  photoUrl?: string;
}

export function FaceIdentificationPanel({
  onIdentified,
  onNotRecognized,
  autoStart = true,
  showHistory = true,
  className,
}: FaceIdentificationPanelProps) {
  const [state, setState] = useState<IdentificationState>('initializing');
  const [error, setError] = useState<string | null>(null);
  const [identifiedEmployee, setIdentifiedEmployee] = useState<IdentifiedEmployee | null>(null);
  const [scanProgress, setScanProgress] = useState(0);
  const [detectionStatus, setDetectionStatus] = useState<'none' | 'detected' | 'multiple'>('none');

  const videoRef = useRef<HTMLVideoElement>(null);
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const animationFrameRef = useRef<number | null>(null);
  const scanIntervalRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Fetch registered faces from API
  const { data: apiRegisteredFaces, isLoading: isLoadingFaces, refetch: refetchFaces } = useRegisteredFaces();

  const verifyFaceMutation = useVerifyFaceDeepFace();

  // Local store for caching
  const {
    registeredFaces: cachedFaces,
    setRegisteredFaces,
    addToIdentificationHistory,
    identificationHistory,

  } = useFaceStore();

  // Use cached faces or API faces
  const registeredFaces: KnownFaceDescriptor[] = cachedFaces.length > 0 ? cachedFaces : (apiRegisteredFaces || []);

  // Update cache when API data changes
  useEffect(() => {
    if (apiRegisteredFaces && apiRegisteredFaces.length > 0) {
      setRegisteredFaces(apiRegisteredFaces);
    }
  }, [apiRegisteredFaces, setRegisteredFaces]);

  // Initialize camera
  useEffect(() => {
    let mounted = true;

    const init = async () => {
      try {
        await faceDetectionService.initialize();
        if (!mounted) return;

        if (videoRef.current) {
          await faceDetectionService.startCamera(videoRef.current);
        }

        setState('ready');
        if (autoStart) {
          startDetectionLoop();
        }
      } catch (err) {
        console.error('Initialization error:', err);
        if (mounted) {
          setError('Gagal mengakses kamera. Pastikan izin kamera telah diberikan.');
          setState('error');
        }
      }
    };

    init();

    return () => {
      mounted = false;
      if (animationFrameRef.current) {
        cancelAnimationFrame(animationFrameRef.current);
      }
      if (scanIntervalRef.current) {
        clearInterval(scanIntervalRef.current);
      }
      faceDetectionService.stopCamera();
    };
  }, [autoStart]);

  // Detection loop for face tracking
  const startDetectionLoop = useCallback(() => {
    const detect = async () => {
      if (!videoRef.current || !canvasRef.current) {
        animationFrameRef.current = requestAnimationFrame(detect);
        return;
      }

      try {
        const detections = await faceDetectionService.detectFaces(videoRef.current);

        if (detections.length === 1) {
          setDetectionStatus('detected');
          const displaySize = faceDetectionService.getDisplaySize(videoRef.current);
          faceDetectionService.drawDetections(canvasRef.current, detections, displaySize);
        } else if (detections.length === 0) {
          setDetectionStatus('none');
          const ctx = canvasRef.current.getContext('2d');
          ctx?.clearRect(0, 0, canvasRef.current.width, canvasRef.current.height);
        } else {
          setDetectionStatus('multiple');
        }
      } catch {
        // Ignore detection errors
      }

      animationFrameRef.current = requestAnimationFrame(detect);
    };

    detect();
  }, []);

  // Identify face
  const identifyFace = useCallback(async () => {
    if (!videoRef.current || detectionStatus !== 'detected') {
      setError('Tidak ada wajah terdeteksi');
      return;
    }

    if (registeredFaces.length === 0) {
      setError('Tidak ada wajah terdaftar dalam sistem');
      return;
    }

    setState('scanning');
    setError(null);
    setScanProgress(0);

    try {
      // Simulate scanning progress
      const progressInterval = setInterval(() => {
        setScanProgress((prev) => Math.min(prev + 10, 90));
      }, 100);

      // Capture image for DeepFace verification
      const imageFile = await faceDetectionService.captureImage(videoRef.current);

      clearInterval(progressInterval);
      setScanProgress(100);

      // Verify using DeepFace (Server-side 1:N matching)
      try {
        const response = await verifyFaceMutation.mutateAsync({
          image: imageFile,
        });

        if (response.success && response.matched && response.employee) {
          const identified: IdentifiedEmployee = {
            id: response.employee.employee_id, // Note: API returns employee_id (string)
            name: response.employee.name,
            similarity: response.similarity || response.confidence || 0,
            timestamp: Date.now(),
            // photoUrl: response.employee.photo_url, // API might not return photo_url yet
          };

          setIdentifiedEmployee(identified);
          addToIdentificationHistory({
            employeeId: identified.id,
            employeeName: identified.name,
            similarity: identified.similarity,
            timestamp: identified.timestamp,
          });

          setState('identified');
          onIdentified?.(identified.id, identified.name, identified.similarity);
        } else {
          setState('not_recognized');
          setIdentifiedEmployee(null);
          onNotRecognized?.();
        }
      } catch {
        setState('not_recognized');
        setIdentifiedEmployee(null);
        onNotRecognized?.();
      }

    } catch (err) {
      console.error('Identification error:', err);
      setError(err instanceof Error ? err.message : 'Gagal mengidentifikasi wajah');
      setState('error');
    }
  }, [
    detectionStatus,
    registeredFaces,
    verifyFaceMutation,
    addToIdentificationHistory,
    onIdentified,
    onNotRecognized,
  ]);

  // Reset to ready state
  const handleReset = () => {
    setState('ready');
    setIdentifiedEmployee(null);
    setError(null);
    setScanProgress(0);
  };

  // Get similarity color
  const getSimilarityColor = (similarity: number): string => {
    if (similarity >= 0.9) return 'text-success';
    if (similarity >= 0.7) return 'text-primary';
    if (similarity >= 0.6) return 'text-warning';
    return 'text-destructive';
  };

  return (
    <Card className={cn('w-full max-w-lg', className)}>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Scan className="h-5 w-5" />
          Identifikasi Wajah
        </CardTitle>
        <CardDescription>
          {isLoadingFaces
            ? 'Memuat data wajah terdaftar...'
            : `${registeredFaces.length} wajah terdaftar dalam sistem`}
        </CardDescription>
      </CardHeader>

      <CardContent className="space-y-4">
        {/* Error Alert */}
        {error && (
          <Alert variant="destructive">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>{error}</AlertDescription>
          </Alert>
        )}

        {/* Camera View */}
        <div className="relative aspect-[4/3] bg-black rounded-lg overflow-hidden">
          <video
            ref={videoRef}
            autoPlay
            playsInline
            muted
            className="w-full h-full object-cover"
          />
          <canvas
            ref={canvasRef}
            className="absolute inset-0 pointer-events-none"
            width={640}
            height={480}
          />

          {/* Detection Status Badge */}
          <div
            className={cn(
              'absolute top-4 left-4 px-3 py-1 rounded-full text-sm font-medium',
              detectionStatus === 'detected' && 'bg-success text-success-foreground',
              detectionStatus === 'none' && 'bg-muted text-muted-foreground',
              detectionStatus === 'multiple' && 'bg-warning text-warning-foreground'
            )}
          >
            {detectionStatus === 'detected' && 'Wajah Terdeteksi'}
            {detectionStatus === 'none' && 'Tidak Ada Wajah'}
            {detectionStatus === 'multiple' && 'Beberapa Wajah'}
          </div>

          {/* Scanning Overlay */}
          {state === 'scanning' && (
            <div className="absolute inset-0 bg-black/50 flex flex-col items-center justify-center">
              <Loader2 className="h-10 w-10 text-white animate-spin mb-4" />
              <p className="text-white mb-2">Mengidentifikasi wajah...</p>
              <div className="w-48">
                <Progress value={scanProgress} className="h-2" />
              </div>
            </div>
          )}

          {/* Loading Overlay */}
          {state === 'initializing' && (
            <div className="absolute inset-0 bg-black/70 flex flex-col items-center justify-center">
              <Loader2 className="h-10 w-10 text-white animate-spin mb-2" />
              <p className="text-white">Memuat kamera...</p>
            </div>
          )}
        </div>

        {/* Identification Result */}
        {state === 'identified' && identifiedEmployee && (
          <div className="bg-success/5 dark:bg-success/10 border border-success/20 rounded-lg p-4">
            <div className="flex items-center gap-4">
              <Avatar className="h-16 w-16">
                <AvatarImage src={identifiedEmployee.photoUrl} />
                <AvatarFallback className="bg-success/10 text-success text-lg">
                  {identifiedEmployee.name.charAt(0)}
                </AvatarFallback>
              </Avatar>
              <div className="flex-1">
                <div className="flex items-center gap-2">
                  <UserCheck className="h-5 w-5 text-success" />
                  <span className="font-semibold text-success">
                    Teridentifikasi
                  </span>
                </div>
                <h3 className="text-lg font-bold text-foreground">
                  {identifiedEmployee.name}
                </h3>
                <p className={cn('text-sm font-medium', getSimilarityColor(identifiedEmployee.similarity))}>
                  Kecocokan: {Math.round(identifiedEmployee.similarity * 100)}%
                </p>
              </div>
            </div>
          </div>
        )}

        {/* Not Recognized Result */}
        {state === 'not_recognized' && (
          <div className="bg-destructive/5 dark:bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <div className="flex items-center gap-4">
              <div className="h-16 w-16 rounded-full bg-destructive/10 flex items-center justify-center">
                <UserX className="h-8 w-8 text-destructive" />
              </div>
              <div>
                <h3 className="font-semibold text-destructive">
                  Wajah Tidak Dikenali
                </h3>
                <p className="text-sm text-destructive/80">
                  Wajah tidak cocok dengan data terdaftar
                </p>
              </div>
            </div>
          </div>
        )}

        {/* Action Buttons */}
        <div className="flex gap-2">
          {(state === 'ready' || state === 'identified' || state === 'not_recognized') && (
            <Button
              onClick={identifyFace}
              className="flex-1"
              disabled={detectionStatus !== 'detected' || isLoadingFaces}
            >
              <Camera className="h-4 w-4 mr-2" />
              {state === 'ready' ? 'Identifikasi' : 'Scan Ulang'}
            </Button>
          )}

          {(state === 'identified' || state === 'not_recognized') && (
            <Button variant="outline" onClick={handleReset}>
              <RefreshCw className="h-4 w-4" />
            </Button>
          )}

          {state === 'error' && (
            <Button onClick={handleReset} className="flex-1">
              <RefreshCw className="h-4 w-4 mr-2" />
              Coba Lagi
            </Button>
          )}

          <Button variant="ghost" onClick={() => refetchFaces()}>
            <RefreshCw className={cn('h-4 w-4', isLoadingFaces && 'animate-spin')} />
          </Button>
        </div>

        {/* Recent History */}
        {showHistory && identificationHistory.length > 0 && (
          <div className="border-t pt-4">
            <h4 className="text-sm font-medium mb-2">Riwayat Identifikasi</h4>
            <div className="space-y-2 max-h-32 overflow-y-auto">
              {identificationHistory.slice(0, 5).map((item) => (
                <div
                  key={`${item.employeeId}-${item.timestamp}`}
                  className="flex items-center justify-between text-sm py-1"
                >
                  <div className="flex items-center gap-2">
                    <User className="h-4 w-4 text-muted-foreground" />
                    <span>{item.employeeName}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className={getSimilarityColor(item.similarity)}>
                      {Math.round(item.similarity * 100)}%
                    </span>
                    <span className="text-muted-foreground text-xs">
                      {new Date(item.timestamp).toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit',
                      })}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}

export default FaceIdentificationPanel;
