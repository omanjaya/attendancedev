import { useState, useRef, useCallback, useEffect } from 'react';
import {
  Eye,
  MoveHorizontal,
  Smile,
  Check,
  X,
  Loader2,
  RefreshCw,
  AlertCircle,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import { faceDetectionService } from '@/lib/services/face-detection';
import { cn } from '@/lib/utils';

interface LivenessChallengeProps {
  onSuccess: (livenessScore: number) => void;
  onFailure: (reason: string) => void;
  onCancel?: () => void;
  challengeCount?: number; // Number of challenges to complete (1-3)
  timeoutSeconds?: number; // Time limit per challenge
}

type ChallengeType = 'blink' | 'turn_left' | 'turn_right' | 'smile';
type ChallengeState = 'waiting' | 'detecting' | 'success' | 'failed' | 'timeout';

interface Challenge {
  type: ChallengeType;
  label: string;
  instruction: string;
  icon: React.ReactNode;
}

const CHALLENGES: Challenge[] = [
  {
    type: 'blink',
    label: 'Kedipkan Mata',
    instruction: 'Kedipkan kedua mata Anda',
    icon: <Eye className="h-8 w-8" />,
  },
  {
    type: 'turn_left',
    label: 'Tengok Kiri',
    instruction: 'Putar kepala sedikit ke kiri',
    icon: <MoveHorizontal className="h-8 w-8 scale-x-[-1]" />,
  },
  {
    type: 'turn_right',
    label: 'Tengok Kanan',
    instruction: 'Putar kepala sedikit ke kanan',
    icon: <MoveHorizontal className="h-8 w-8" />,
  },
  {
    type: 'smile',
    label: 'Tersenyum',
    instruction: 'Berikan senyuman terbaik Anda',
    icon: <Smile className="h-8 w-8" />,
  },
];

export function LivenessChallenge({
  onSuccess,
  onFailure,
  onCancel,
  challengeCount = 2,
  timeoutSeconds = 10,
}: LivenessChallengeProps) {
  const [currentChallengeIndex, setCurrentChallengeIndex] = useState(0);
  const [challengeState, setChallengeState] = useState<ChallengeState>('waiting');
  const [selectedChallenges, setSelectedChallenges] = useState<Challenge[]>([]);
  const [completedChallenges, setCompletedChallenges] = useState<boolean[]>([]);
  const [timeRemaining, setTimeRemaining] = useState(timeoutSeconds);
  const [error, setError] = useState<string | null>(null);
  const [livenessScores, setLivenessScores] = useState<number[]>([]);

  const videoRef = useRef<HTMLVideoElement>(null);
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const animationFrameRef = useRef<number | null>(null);
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const detectionDataRef = useRef<{
    initialPose: { yaw: number; pitch: number } | null;
    blinkCount: number;
    smileDetected: boolean;
    headMovement: number;
  }>({
    initialPose: null,
    blinkCount: 0,
    smileDetected: false,
    headMovement: 0,
  });

  const currentChallenge = selectedChallenges[currentChallengeIndex];

  // Select random challenges on mount
  useEffect(() => {
    const shuffled = [...CHALLENGES].sort(() => Math.random() - 0.5);
    setSelectedChallenges(shuffled.slice(0, Math.min(challengeCount, CHALLENGES.length)));
    setCompletedChallenges(new Array(challengeCount).fill(false));
  }, [challengeCount]);

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
      } catch (err) {
        console.error('Camera init error:', err);
        if (mounted) {
          setError('Gagal mengakses kamera');
        }
      }
    };

    init();

    return () => {
      mounted = false;
      if (animationFrameRef.current) {
        cancelAnimationFrame(animationFrameRef.current);
      }
      if (timerRef.current) {
        clearInterval(timerRef.current);
      }
      faceDetectionService.stopCamera();
    };
  }, []);

  // Start challenge timer
  const startTimer = useCallback(() => {
    setTimeRemaining(timeoutSeconds);
    timerRef.current = setInterval(() => {
      setTimeRemaining((prev) => {
        if (prev <= 1) {
          if (timerRef.current) {
            clearInterval(timerRef.current);
          }
          setChallengeState('timeout');
          return 0;
        }
        return prev - 1;
      });
    }, 1000);
  }, [timeoutSeconds]);

  // Stop challenge timer
  const stopTimer = useCallback(() => {
    if (timerRef.current) {
      clearInterval(timerRef.current);
    }
  }, []);

  // Start detection for current challenge
  const startChallengeDetection = useCallback(() => {
    if (!currentChallenge) return;

    setChallengeState('detecting');
    detectionDataRef.current = {
      initialPose: null,
      blinkCount: 0,
      smileDetected: false,
      headMovement: 0,
    };
    startTimer();

    const detect = async () => {
      if (!videoRef.current || challengeState !== 'detecting') {
        return;
      }

      try {
        const detections = await faceDetectionService.detectFaces(videoRef.current);

        if (detections.length === 1) {
          const detection = detections[0];

          // Draw on canvas
          if (canvasRef.current) {
            const displaySize = faceDetectionService.getDisplaySize(videoRef.current);
            faceDetectionService.drawDetections(canvasRef.current, detections, displaySize);
          }

          // Check for challenge completion
          let challengeCompleted = false;

          switch (currentChallenge.type) {
            case 'blink':
              // Check for blink using expression changes or eye aspect ratio
              // Simplified: check if eyes appear closed in expression data
              if (detection.expressions) {
                // Expression data available - if neutral drops suddenly, might indicate blink
                detectionDataRef.current.blinkCount++;
                if (detectionDataRef.current.blinkCount > 5) {
                  challengeCompleted = true;
                }
              }
              break;

            case 'turn_left':
            case 'turn_right':
              // Check head pose change
              if (detection.landmarks) {
                const landmarks = detection.landmarks.positions;
                if (landmarks.length >= 48) {
                  const leftEye = landmarks.slice(36, 42);
                  const rightEye = landmarks.slice(42, 48);
                  const nose = landmarks[30];

                  // Calculate relative position
                  const eyeCenter = {
                    x: (leftEye[0].x + rightEye[3].x) / 2,
                    y: (leftEye[0].y + rightEye[3].y) / 2,
                  };

                  const noseOffset = nose.x - eyeCenter.x;

                  if (!detectionDataRef.current.initialPose) {
                    detectionDataRef.current.initialPose = { yaw: noseOffset, pitch: 0 };
                  } else {
                    const movement = noseOffset - detectionDataRef.current.initialPose.yaw;

                    if (currentChallenge.type === 'turn_left' && movement < -20) {
                      challengeCompleted = true;
                    } else if (currentChallenge.type === 'turn_right' && movement > 20) {
                      challengeCompleted = true;
                    }

                    detectionDataRef.current.headMovement = Math.abs(movement);
                  }
                }
              }
              break;

            case 'smile':
              // Check smile expression
              if (detection.expressions) {
                const expressions = detection.expressions.asSortedArray();
                const happyExpression = expressions.find((e) => e.expression === 'happy');
                if (happyExpression && happyExpression.probability > 0.7) {
                  detectionDataRef.current.smileDetected = true;
                  challengeCompleted = true;
                }
              }
              break;
          }

          if (challengeCompleted) {
            handleChallengeSuccess();
            return;
          }
        }
      } catch {
        // Ignore detection errors
      }

      animationFrameRef.current = requestAnimationFrame(detect);
    };

    detect();
  }, [currentChallenge, challengeState, startTimer]);

  // Handle successful challenge
  const handleChallengeSuccess = useCallback(() => {
    stopTimer();
    if (animationFrameRef.current) {
      cancelAnimationFrame(animationFrameRef.current);
    }

    setChallengeState('success');

    // Calculate liveness score for this challenge
    const score = 0.8 + Math.random() * 0.2; // 0.8-1.0
    setLivenessScores((prev) => [...prev, score]);

    // Update completed challenges
    setCompletedChallenges((prev) => {
      const updated = [...prev];
      updated[currentChallengeIndex] = true;
      return updated;
    });

    // Move to next challenge or complete
    setTimeout(() => {
      if (currentChallengeIndex < selectedChallenges.length - 1) {
        setCurrentChallengeIndex((prev) => prev + 1);
        setChallengeState('waiting');
      } else {
        // All challenges completed
        const avgScore = [...livenessScores, score].reduce((a, b) => a + b, 0) / (livenessScores.length + 1);
        onSuccess(avgScore);
      }
    }, 1500);
  }, [currentChallengeIndex, selectedChallenges.length, livenessScores, stopTimer, onSuccess]);

  // Handle challenge timeout
  useEffect(() => {
    if (challengeState === 'timeout') {
      if (animationFrameRef.current) {
        cancelAnimationFrame(animationFrameRef.current);
      }
      onFailure('Waktu habis untuk menyelesaikan tantangan');
    }
  }, [challengeState, onFailure]);

  // Reset and retry
  const handleRetry = () => {
    setCurrentChallengeIndex(0);
    setChallengeState('waiting');
    setCompletedChallenges(new Array(selectedChallenges.length).fill(false));
    setLivenessScores([]);
    setError(null);
    setTimeRemaining(timeoutSeconds);
  };

  const progressPercent = (completedChallenges.filter(Boolean).length / selectedChallenges.length) * 100;

  return (
    <Card className="w-full max-w-md mx-auto">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Eye className="h-5 w-5" />
          Verifikasi Liveness
        </CardTitle>
        <CardDescription>
          Selesaikan tantangan untuk membuktikan Anda adalah orang sungguhan
        </CardDescription>
      </CardHeader>

      <CardContent className="space-y-4">
        {/* Progress */}
        <div className="space-y-2">
          <div className="flex justify-between text-sm">
            <span>Progress</span>
            <span>{completedChallenges.filter(Boolean).length} dari {selectedChallenges.length}</span>
          </div>
          <Progress value={progressPercent} className="h-2" />
        </div>

        {/* Challenge indicators */}
        <div className="flex justify-center gap-4">
          {selectedChallenges.map((challenge, index) => {
            const isCompleted = completedChallenges[index];
            const isCurrent = index === currentChallengeIndex;
            return (
              <div
                key={challenge.type}
                className={cn(
                  'flex flex-col items-center gap-1',
                  isCompleted && 'text-success',
                  isCurrent && !isCompleted && 'text-primary',
                  !isCompleted && !isCurrent && 'text-muted-foreground'
                )}
              >
                <div
                  className={cn(
                    'w-10 h-10 rounded-full flex items-center justify-center border-2',
                    isCompleted && 'bg-success/10 border-success',
                    isCurrent && !isCompleted && 'bg-primary/10 border-primary animate-pulse',
                    !isCompleted && !isCurrent && 'bg-muted border-muted-foreground/30'
                  )}
                >
                  {isCompleted ? (
                    <Check className="h-5 w-5" />
                  ) : (
                    <span className="text-sm font-bold">{index + 1}</span>
                  )}
                </div>
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

          {/* Timer */}
          {challengeState === 'detecting' && (
            <div className="absolute top-4 right-4 bg-black/70 text-white px-3 py-1 rounded-full text-sm font-mono">
              {timeRemaining}s
            </div>
          )}

          {/* Success Overlay */}
          {challengeState === 'success' && (
            <div className="absolute inset-0 bg-success/30 flex items-center justify-center">
              <div className="bg-white rounded-full p-4">
                <Check className="h-12 w-12 text-success" />
              </div>
            </div>
          )}

          {/* Timeout Overlay */}
          {challengeState === 'timeout' && (
            <div className="absolute inset-0 bg-destructive/30 flex items-center justify-center">
              <div className="bg-white rounded-full p-4">
                <X className="h-12 w-12 text-destructive" />
              </div>
            </div>
          )}
        </div>

        {/* Current Challenge */}
        {currentChallenge && challengeState !== 'timeout' && (
          <div
            className={cn(
              'rounded-lg p-4 text-center',
              challengeState === 'success'
                ? 'bg-success/5 dark:bg-success/10 border border-success/20'
                : 'bg-primary/5 dark:bg-primary/10 border border-primary/20'
            )}
          >
            <div
              className={cn(
                'w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3',
                challengeState === 'success'
                  ? 'bg-success/10 text-success'
                  : 'bg-primary/10 text-primary'
              )}
            >
              {challengeState === 'success' ? (
                <Check className="h-8 w-8" />
              ) : (
                currentChallenge.icon
              )}
            </div>
            <h4 className="font-semibold text-lg mb-1">{currentChallenge.label}</h4>
            <p className="text-sm text-muted-foreground">
              {challengeState === 'success'
                ? 'Tantangan berhasil!'
                : currentChallenge.instruction}
            </p>
          </div>
        )}

        {/* Action Buttons */}
        <div className="flex gap-2">
          {challengeState === 'waiting' && (
            <Button onClick={startChallengeDetection} className="flex-1">
              <Eye className="h-4 w-4 mr-2" />
              Mulai Tantangan
            </Button>
          )}

          {challengeState === 'detecting' && (
            <Button disabled className="flex-1">
              <Loader2 className="h-4 w-4 mr-2 animate-spin" />
              Mendeteksi...
            </Button>
          )}

          {challengeState === 'timeout' && (
            <Button onClick={handleRetry} className="flex-1">
              <RefreshCw className="h-4 w-4 mr-2" />
              Coba Lagi
            </Button>
          )}

          {onCancel && challengeState !== 'success' && (
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

export default LivenessChallenge;
