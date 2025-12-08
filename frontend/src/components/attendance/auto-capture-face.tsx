import { useEffect, useRef, useState, useCallback } from 'react';
import { CameraOff, CheckCircle2, XCircle, Loader2, Smile } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import { useFaceDetection } from '@/hooks/use-face-detection';
import { faceDetectionService } from '@/lib/services/face-detection';
import { cn } from '@/lib/utils';

export interface FaceQuality {
    hasFace: boolean;
    faceCount: number;
    confidence: number;
    brightness: number;
    isStable: boolean;
    message: string;
    isGood: boolean;
}

export interface AutoCaptureFaceProps {
    onCapture: (videoElement: HTMLVideoElement) => Promise<void>;
    onError?: (error: string) => void;
    autoCapture?: boolean;
    stabilityDuration?: number; // milliseconds to wait for stable face
    confidenceThreshold?: number;
    className?: string;
}

type LivenessStep = 'idle' | 'detecting' | 'smile_check' | 'capturing' | 'success' | 'failed';

export function AutoCaptureFace({
    onCapture,
    onError,
    autoCapture = true,
    stabilityDuration = 1000,
    confidenceThreshold = 0.7,
    className,
}: AutoCaptureFaceProps) {
    const {
        videoRef,
        canvasRef,
        isInitialized,
        cameraStatus,
        detectionStatus,
        error,
        confidence,
        initialize,
        startCamera,
        stopCamera,
        startDetection,
        stopDetection,
    } = useFaceDetection({
        onError: (err) => {
            console.error('Face detection error:', err);
            onError?.(err);
        },
    });

    const [faceQuality, setFaceQuality] = useState<FaceQuality>({
        hasFace: false,
        faceCount: 0,
        confidence: 0,
        brightness: 0,
        isStable: false,
        message: 'Mencari wajah...',
        isGood: false,
    });

    const [livenessStep, setLivenessStep] = useState<LivenessStep>('idle');
    const [captureProgress, setCaptureProgress] = useState(0);
    const [showFlash, setShowFlash] = useState(false);
    const [smileScore, setSmileScore] = useState(0);

    const stableStartTimeRef = useRef<number | null>(null);
    const hasCapturedRef = useRef(false);
    const smileCheckIntervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

    // Initialize camera on mount
    useEffect(() => {
        const init = async () => {
            if (!isInitialized) {
                await initialize();
            }
            await startCamera();
            setLivenessStep('detecting');
            startDetection();
        };

        init();

        return () => {
            stopDetection();
            stopCamera();
            if (smileCheckIntervalRef.current) {
                clearInterval(smileCheckIntervalRef.current);
            }
        };
    }, []);

    // Analyze face quality (brightness & basic stability)
    const analyzeFaceQuality = useCallback((video: HTMLVideoElement): FaceQuality => {
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');

        if (!ctx) {
            return {
                hasFace: false,
                faceCount: 0,
                confidence: 0,
                brightness: 0,
                isStable: false,
                message: 'Error analyzing video',
                isGood: false,
            };
        }

        ctx.drawImage(video, 0, 0);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;

        // Calculate brightness
        let totalBrightness = 0;
        for (let i = 0; i < data.length; i += 4) {
            const r = data[i];
            const g = data[i + 1];
            const b = data[i + 2];
            totalBrightness += (r + g + b) / 3;
        }
        const brightness = totalBrightness / (data.length / 4);

        const hasFace = detectionStatus === 'detected';
        const faceConfidence = confidence;
        const isBrightnessGood = brightness > 40 && brightness < 220; // Relaxed limits
        const isConfidenceGood = faceConfidence >= confidenceThreshold;

        let message = 'Mencari wajah...';
        let isGood = false;

        if (!hasFace) {
            message = '💡 Hadap ke kamera';
        } else if (!isBrightnessGood) {
            if (brightness <= 40) {
                message = '💡 Terlalu gelap';
            } else {
                message = '☀️ Terlalu terang';
            }
        } else if (!isConfidenceGood) {
            message = '📸 Posisi wajah kurang jelas';
        } else {
            message = '✅ Wajah terdeteksi';
            isGood = true;
        }

        return {
            hasFace,
            faceCount: hasFace ? 1 : 0,
            confidence: faceConfidence,
            brightness,
            isStable: isGood,
            message,
            isGood,
        };
    }, [detectionStatus, confidence, confidenceThreshold]);

    // Check for smile (Liveness Detection)
    const checkSmile = async () => {
        if (!videoRef.current || livenessStep !== 'smile_check') return;

        try {
            // We use the full detection here to get expressions
            const detections = await faceDetectionService.detectFaces(videoRef.current);

            if (detections.length > 0) {
                const expressions = detections[0].expressions;
                const happyScore = expressions?.asSortedArray().find(e => e.expression === 'happy')?.probability || 0;
                setSmileScore(happyScore);

                if (happyScore > 0.7) {
                    // Smile detected!
                    if (smileCheckIntervalRef.current) {
                        clearInterval(smileCheckIntervalRef.current);
                    }
                    handleAutoCapture();
                }
            }
        } catch (err) {
            console.warn('Smile check failed', err);
        }
    };

    // Main Face Detection Loop & Logic
    useEffect(() => {
        if (!videoRef.current || !autoCapture || hasCapturedRef.current) {
            return;
        }

        // 1. Analyze Quality
        const quality = analyzeFaceQuality(videoRef.current);
        setFaceQuality(quality);

        // 2. Handle Steps
        if (livenessStep === 'detecting') {
            if (quality.isGood) {
                // If stable for duration, move to smile check
                if (stableStartTimeRef.current === null) {
                    stableStartTimeRef.current = Date.now();
                }

                const elapsed = Date.now() - stableStartTimeRef.current;
                const progress = Math.min((elapsed / stabilityDuration) * 100, 100);
                setCaptureProgress(progress);

                if (elapsed >= stabilityDuration) {
                    setLivenessStep('smile_check');
                    stableStartTimeRef.current = null;
                    setCaptureProgress(0);

                    // Start smile check interval
                    smileCheckIntervalRef.current = setInterval(checkSmile, 200);
                }
            } else {
                stableStartTimeRef.current = null;
                setCaptureProgress(0);
            }
        }
    }, [detectionStatus, confidence, autoCapture, livenessStep, analyzeFaceQuality, stabilityDuration]);

    // Clean up interval when step changes
    useEffect(() => {
        if (livenessStep !== 'smile_check' && smileCheckIntervalRef.current) {
            clearInterval(smileCheckIntervalRef.current);
            smileCheckIntervalRef.current = null;
        }
    }, [livenessStep]);


    const handleAutoCapture = async () => {
        if (!videoRef.current) return;

        setLivenessStep('capturing');
        setShowFlash(true);

        // Haptic feedback
        if (navigator.vibrate) {
            navigator.vibrate([100, 50, 100]);
        }

        setTimeout(() => setShowFlash(false), 200);

        try {
            await onCapture(videoRef.current);
            setLivenessStep('success');
            hasCapturedRef.current = true;
        } catch (err) {
            console.error('Capture error:', err);
            setLivenessStep('failed');
            onError?.(err instanceof Error ? err.message : 'Gagal mengambil foto');

            // Reset for retry
            setTimeout(() => {
                setLivenessStep('detecting');
                hasCapturedRef.current = false;
                stableStartTimeRef.current = null;
                setCaptureProgress(0);
                setSmileScore(0);
            }, 2000);
        }
    };

    return (
        <div className={cn('space-y-4 w-full max-w-md mx-auto', className)}>
            {/* Camera View - Enlarged */}
            <div className="relative aspect-[3/4] sm:aspect-square w-full overflow-hidden rounded-2xl bg-neutral-900 border-4 border-neutral-800 shadow-xl">
                {cameraStatus === 'active' ? (
                    <>
                        <video
                            ref={videoRef}
                            autoPlay
                            playsInline
                            muted
                            className="h-full w-full object-cover scale-x-[-1]" // Flip horizontal
                        />
                        <canvas
                            ref={canvasRef}
                            className="absolute inset-0 h-full w-full scale-x-[-1]" // Flip canvas too
                        />

                        {/* Flash Effect */}
                        {showFlash && (
                            <div className="absolute inset-0 bg-white animate-pulse z-50" />
                        )}

                        {/* Guides / Overlays */}
                        {livenessStep !== 'capturing' && livenessStep !== 'success' && (
                            <div className="absolute inset-x-0 bottom-0 p-6 bg-gradient-to-t from-black/80 to-transparent flex flex-col items-center gap-3">

                                {/* Step 1: Detecting Face */}
                                {livenessStep === 'detecting' && (
                                    <>
                                        <Badge
                                            variant={faceQuality.isGood ? "default" : "destructive"}
                                            className={cn("px-4 py-1 text-sm transition-all",
                                                faceQuality.isGood ? "bg-blue-500 hover:bg-blue-600" : ""
                                            )}
                                        >
                                            {faceQuality.message}
                                        </Badge>

                                        {/* Stability Progress */}
                                        {faceQuality.isGood && (
                                            <div className="w-full max-w-[200px] space-y-1">
                                                <div className="flex justify-between text-xs text-white/80 px-1">
                                                    <span>Tahan posisi...</span>
                                                </div>
                                                <Progress value={captureProgress} className="h-2" />
                                            </div>
                                        )}
                                    </>
                                )}

                                {/* Step 2: Smile Check */}
                                {livenessStep === 'smile_check' && (
                                    <div className="animate-in slide-in-from-bottom-5 fade-in duration-300 flex flex-col items-center gap-3">
                                        <div className="p-3 bg-yellow-500/20 backdrop-blur-md rounded-full border border-yellow-500/50 animate-bounce">
                                            <Smile className="w-8 h-8 text-yellow-400" />
                                        </div>
                                        <div className="text-center space-y-1">
                                            <h3 className="text-xl font-bold text-white tracking-wide drop-shadow-md">
                                                Silakan Senyum! 😊
                                            </h3>
                                            <p className="text-sm text-white/70">
                                                Validasi kehidupan
                                            </p>
                                        </div>
                                        {smileScore > 0 && (
                                            <Progress value={smileScore * 100} className="w-32 h-2 mt-2" />
                                        )}
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Processing */}
                        {livenessStep === 'capturing' && (
                            <div className="absolute inset-0 flex flex-col items-center justify-center bg-black/60 backdrop-blur-sm z-40">
                                <Loader2 className="h-12 w-12 text-primary animate-spin" />
                                <p className="mt-4 text-lg font-medium text-white">Memverifikasi...</p>
                            </div>
                        )}

                        {/* Success */}
                        {livenessStep === 'success' && (
                            <div className="absolute inset-0 flex flex-col items-center justify-center bg-success/80 backdrop-blur-sm z-40 animate-in fade-in zoom-in">
                                <CheckCircle2 className="h-20 w-20 text-white drop-shadow-lg" />
                                <p className="mt-4 text-xl font-bold text-white">Berhasil!</p>
                            </div>
                        )}

                        {/* Failed */}
                        {livenessStep === 'failed' && (
                            <div className="absolute inset-0 flex flex-col items-center justify-center bg-destructive/80 backdrop-blur-sm z-40 animate-in fade-in zoom-in">
                                <XCircle className="h-20 w-20 text-white drop-shadow-lg" />
                                <p className="mt-4 text-xl font-bold text-white">Gagal</p>
                                <p className="text-white/80">Silakan coba lagi</p>
                            </div>
                        )}
                    </>
                ) : (
                    <div className="flex h-full flex-col items-center justify-center gap-4">
                        <CameraOff className="h-12 w-12 text-muted-foreground" />
                        <p className="text-muted-foreground">Mengaktifkan kamera...</p>
                        <Loader2 className="h-6 w-6 animate-spin text-primary" />
                    </div>
                )}
            </div>

            {/* Error Alert */}
            {error && (
                <Alert variant="destructive">
                    <XCircle className="h-4 w-4" />
                    <AlertTitle>Error Kamera</AlertTitle>
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}
        </div>
    );
}
