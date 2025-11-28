import { useState, useRef, useCallback, useEffect } from 'react';
import {
  Camera,
  Check,
  X,
  RefreshCw,
  AlertCircle,
  Loader2,
  ChevronRight,
  ArrowLeft,
  ArrowRight,
  CircleDot,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import { faceDetectionService } from '@/lib/services/face-detection';
import { useRegisterFace, useUpdateFace } from '@/hooks/use-face-recognition-api';
import { useFaceStore } from '@/stores';
import { cn } from '@/lib/utils';

interface FaceEnrollmentWizardProps {
  employeeId: string;
  employeeName: string;
  onSuccess?: () => void;
  onCancel?: () => void;
  isUpdate?: boolean;
  requireLiveness?: boolean;
}

type CaptureStep = 'frontal' | 'left' | 'right';
type WizardState = 'initializing' | 'ready' | 'capturing' | 'processing' | 'completed' | 'error';

interface CaptureData {
  step: CaptureStep;
  descriptor: number[];
  confidence: number;
  timestamp: number;
}

const CAPTURE_STEPS: { step: CaptureStep; label: string; instruction: string; icon: React.ReactNode }[] = [
  {
    step: 'frontal',
    label: 'Hadap Depan',
    instruction: 'Posisikan wajah Anda menghadap langsung ke kamera',
    icon: <CircleDot className="h-6 w-6" />,
  },
  {
    step: 'left',
    label: 'Sedikit ke Kiri',
    instruction: 'Putar kepala sedikit ke kiri (sekitar 15-20 derajat)',
    icon: <ArrowLeft className="h-6 w-6" />,
  },
  {
    step: 'right',
    label: 'Sedikit ke Kanan',
    instruction: 'Putar kepala sedikit ke kanan (sekitar 15-20 derajat)',
    icon: <ArrowRight className="h-6 w-6" />,
  },
];

export function FaceEnrollmentWizard({
  employeeId,
  employeeName,
  onSuccess,
  onCancel,
  isUpdate = false,
  requireLiveness = false,
}: FaceEnrollmentWizardProps) {
  const [state, setState] = useState<WizardState>('initializing');
  const [currentStepIndex, setCurrentStepIndex] = useState(0);
  const [captures, setCaptures] = useState<CaptureData[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [countdown, setCountdown] = useState<number | null>(null);
  const [detectionStatus, setDetectionStatus] = useState<'none' | 'detecting' | 'detected'>('none');

  const videoRef = useRef<HTMLVideoElement>(null);
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const animationFrameRef = useRef<number | null>(null);

  const registerFace = useRegisterFace();
  const updateFace = useUpdateFace();
  const { addRegisteredFace, setEnrollmentStep, addEnrollmentDescriptor, clearEnrollmentData, getAverageDescriptor } = useFaceStore();

  const currentStep = CAPTURE_STEPS[currentStepIndex];

  // Initialize camera and face detection
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
        startDetectionLoop();
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
      faceDetectionService.stopCamera();
      clearEnrollmentData();
    };
  }, [clearEnrollmentData]);

  // Detection loop
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
          setDetectionStatus('detecting');
          // Multiple faces - draw warning
          const ctx = canvasRef.current.getContext('2d');
          if (ctx) {
            ctx.clearRect(0, 0, canvasRef.current.width, canvasRef.current.height);
            ctx.fillStyle = 'rgba(239, 68, 68, 0.8)';
            ctx.font = '16px Inter, system-ui, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('Terdeteksi lebih dari satu wajah', canvasRef.current.width / 2, 30);
          }
        }
      } catch {
        // Ignore detection errors in loop
      }

      animationFrameRef.current = requestAnimationFrame(detect);
    };

    detect();
  }, []);

  // Capture face for current step
  const captureFace = useCallback(async () => {
    if (!videoRef.current || detectionStatus !== 'detected') return;

    setState('capturing');
    setError(null);

    // Countdown
    for (let i = 3; i > 0; i--) {
      setCountdown(i);
      await new Promise((resolve) => setTimeout(resolve, 1000));
    }
    setCountdown(null);

    try {
      const faceData = await faceDetectionService.captureFaceDescriptor(
        videoRef.current,
        employeeId,
        { requireLiveness: requireLiveness && currentStepIndex === 0 }
      );

      if (faceData.confidence < 0.6) {
        setError('Kualitas deteksi kurang baik. Pastikan pencahayaan cukup dan wajah terlihat jelas.');
        setState('ready');
        return;
      }

      const captureData: CaptureData = {
        step: currentStep.step,
        descriptor: faceData.descriptor,
        confidence: faceData.confidence,
        timestamp: Date.now(),
      };

      setCaptures((prev) => [...prev, captureData]);
      addEnrollmentDescriptor(faceData.descriptor);

      // Move to next step or complete
      if (currentStepIndex < CAPTURE_STEPS.length - 1) {
        setCurrentStepIndex((prev) => prev + 1);
        setEnrollmentStep(CAPTURE_STEPS[currentStepIndex + 1].step);
        setState('ready');
      } else {
        // All captures complete
        setEnrollmentStep('processing');
        await saveFaceData();
      }
    } catch (err) {
      console.error('Capture error:', err);
      setError(err instanceof Error ? err.message : 'Gagal mengambil foto wajah');
      setState('ready');
    }
  }, [currentStep, currentStepIndex, detectionStatus, employeeId, requireLiveness, addEnrollmentDescriptor, setEnrollmentStep]);

  // Save face data to backend
  const saveFaceData = async () => {
    setState('processing');

    try {
      // Calculate average descriptor from all captures
      const averageDescriptor = getAverageDescriptor();
      if (!averageDescriptor) {
        throw new Error('Tidak ada data wajah yang tersimpan');
      }

      // Calculate average confidence
      const avgConfidence = captures.reduce((sum, c) => sum + c.confidence, 0) / captures.length;

      const mutation = isUpdate ? updateFace : registerFace;
      await mutation.mutateAsync({
        employee_id: employeeId,
        descriptor: averageDescriptor,
        confidence: avgConfidence,
      });

      // Update local cache
      addRegisteredFace({
        employeeId,
        name: employeeName,
        descriptor: averageDescriptor,
      });

      setEnrollmentStep('completed');
      setState('completed');

      // Auto call success after brief delay
      setTimeout(() => {
        onSuccess?.();
      }, 2000);
    } catch (err) {
      console.error('Save error:', err);
      setError('Gagal menyimpan data wajah. Silakan coba lagi.');
      setState('error');
    }
  };

  // Reset wizard
  const handleReset = () => {
    setCaptures([]);
    setCurrentStepIndex(0);
    setError(null);
    setState('ready');
    clearEnrollmentData();
    setEnrollmentStep('frontal');
  };

  // Get progress percentage
  const progressPercent = (captures.length / CAPTURE_STEPS.length) * 100;

  return (
    <Card className="w-full max-w-lg mx-auto">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Camera className="h-5 w-5" />
          {isUpdate ? 'Update Wajah' : 'Pendaftaran Wajah'}
        </CardTitle>
        <CardDescription>
          {state === 'completed'
            ? `Wajah ${employeeName} berhasil didaftarkan`
            : `Ikuti instruksi untuk mendaftarkan wajah ${employeeName}`}
        </CardDescription>
      </CardHeader>

      <CardContent className="space-y-4">
        {/* Progress */}
        <div className="space-y-2">
          <div className="flex justify-between text-sm">
            <span>Progress</span>
            <span>{captures.length} dari {CAPTURE_STEPS.length} foto</span>
          </div>
          <Progress value={progressPercent} className="h-2" />
        </div>

        {/* Step indicators */}
        <div className="flex justify-center gap-4">
          {CAPTURE_STEPS.map((step, index) => {
            const isCompleted = index < captures.length;
            const isCurrent = index === currentStepIndex;
            return (
              <div
                key={step.step}
                className={cn(
                  'flex flex-col items-center gap-1',
                  isCompleted && 'text-green-600',
                  isCurrent && !isCompleted && 'text-blue-600',
                  !isCompleted && !isCurrent && 'text-muted-foreground'
                )}
              >
                <div
                  className={cn(
                    'w-10 h-10 rounded-full flex items-center justify-center border-2',
                    isCompleted && 'bg-green-100 border-green-600',
                    isCurrent && !isCompleted && 'bg-blue-100 border-blue-600',
                    !isCompleted && !isCurrent && 'bg-muted border-muted-foreground/30'
                  )}
                >
                  {isCompleted ? <Check className="h-5 w-5" /> : step.icon}
                </div>
                <span className="text-xs font-medium">{step.label}</span>
              </div>
            );
          })}
        </div>

        {/* Error Alert */}
        {error && (
          <Alert variant="destructive">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>{error}</AlertDescription>
          </Alert>
        )}

        {/* Camera View */}
        {state !== 'completed' && (
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

            {/* Guide Overlay */}
            <div className="absolute inset-0 pointer-events-none">
              {/* Face guide oval */}
              <svg className="w-full h-full" viewBox="0 0 640 480">
                <defs>
                  <mask id="faceMask">
                    <rect width="100%" height="100%" fill="white" />
                    <ellipse cx="320" cy="220" rx="120" ry="160" fill="black" />
                  </mask>
                </defs>
                <rect
                  width="100%"
                  height="100%"
                  fill="rgba(0,0,0,0.5)"
                  mask="url(#faceMask)"
                />
                <ellipse
                  cx="320"
                  cy="220"
                  rx="120"
                  ry="160"
                  fill="none"
                  stroke={detectionStatus === 'detected' ? '#22c55e' : '#3b82f6'}
                  strokeWidth="3"
                  strokeDasharray={detectionStatus === 'detected' ? 'none' : '10 5'}
                />
              </svg>
            </div>

            {/* Countdown Overlay */}
            {countdown !== null && (
              <div className="absolute inset-0 flex items-center justify-center bg-black/50">
                <div className="text-white text-8xl font-bold animate-pulse">{countdown}</div>
              </div>
            )}

            {/* Status Badge */}
            <div
              className={cn(
                'absolute top-4 left-4 px-3 py-1 rounded-full text-sm font-medium',
                detectionStatus === 'detected' && 'bg-green-500 text-white',
                detectionStatus === 'none' && 'bg-red-500 text-white',
                detectionStatus === 'detecting' && 'bg-yellow-500 text-white'
              )}
            >
              {detectionStatus === 'detected' && 'Wajah Terdeteksi'}
              {detectionStatus === 'none' && 'Tidak Ada Wajah'}
              {detectionStatus === 'detecting' && 'Mendeteksi...'}
            </div>

            {/* Loading Overlay */}
            {(state === 'initializing' || state === 'processing') && (
              <div className="absolute inset-0 flex flex-col items-center justify-center bg-black/70">
                <Loader2 className="h-10 w-10 text-white animate-spin mb-2" />
                <p className="text-white">
                  {state === 'initializing' ? 'Memuat kamera...' : 'Menyimpan data wajah...'}
                </p>
              </div>
            )}
          </div>
        )}

        {/* Completed State */}
        {state === 'completed' && (
          <div className="text-center py-8">
            <div className="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
              <Check className="h-10 w-10 text-green-600" />
            </div>
            <h3 className="text-lg font-semibold text-green-600 mb-2">Pendaftaran Berhasil!</h3>
            <p className="text-muted-foreground">
              Wajah {employeeName} telah berhasil didaftarkan dalam sistem.
            </p>
          </div>
        )}

        {/* Current Step Instruction */}
        {state === 'ready' && currentStep && (
          <div className="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div className="flex items-center gap-3">
              <div className="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                {currentStep.icon}
              </div>
              <div>
                <h4 className="font-medium text-blue-800 dark:text-blue-200">
                  Langkah {currentStepIndex + 1}: {currentStep.label}
                </h4>
                <p className="text-sm text-blue-600 dark:text-blue-400">
                  {currentStep.instruction}
                </p>
              </div>
            </div>
          </div>
        )}

        {/* Action Buttons */}
        <div className="flex gap-2">
          {state === 'ready' && (
            <>
              <Button
                onClick={captureFace}
                className="flex-1"
                disabled={detectionStatus !== 'detected'}
              >
                <Camera className="h-4 w-4 mr-2" />
                Ambil Foto
                <ChevronRight className="h-4 w-4 ml-2" />
              </Button>
              {captures.length > 0 && (
                <Button variant="outline" onClick={handleReset}>
                  <RefreshCw className="h-4 w-4" />
                </Button>
              )}
            </>
          )}

          {state === 'error' && (
            <Button onClick={handleReset} className="flex-1">
              <RefreshCw className="h-4 w-4 mr-2" />
              Mulai Ulang
            </Button>
          )}

          {onCancel && state !== 'completed' && (
            <Button variant="ghost" onClick={onCancel}>
              <X className="h-4 w-4 mr-2" />
              Batal
            </Button>
          )}
        </div>
      </CardContent>
    </Card>
  );
}

export default FaceEnrollmentWizard;
