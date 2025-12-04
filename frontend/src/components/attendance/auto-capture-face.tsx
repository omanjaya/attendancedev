import { useEffect, useRef, useState, useCallback } from 'react';
import { Camera, CameraOff, CheckCircle2, XCircle, Loader2, AlertTriangle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import { useFaceDetection } from '@/hooks/use-face-detection';
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

export function AutoCaptureFace({
    onCapture,
    onError,
    autoCapture = true,
    stabilityDuration = 1500,
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

    const [captureStatus, setCaptureStatus] = useState<'idle' | 'capturing' | 'success' | 'failed'>('idle');
    const [captureProgress, setCaptureProgress] = useState(0);
    const [showFlash, setShowFlash] = useState(false);

    const stableStartTimeRef = useRef<number | null>(null);
    const captureTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const hasCapturedRef = useRef(false);

    // Initialize camera on mount
    useEffect(() => {
        const init = async () => {
            if (!isInitialized) {
                await initialize();
            }
            await startCamera();
            startDetection();
        };

        init();

        return () => {
            stopDetection();
            stopCamera();
            if (captureTimeoutRef.current) {
                clearTimeout(captureTimeoutRef.current);
            }
        };
    }, []);

    // Analyze face quality
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
        const isBrightnessGood = brightness > 50 && brightness < 200;
        const isConfidenceGood = faceConfidence >= confidenceThreshold;

        let message = 'Mencari wajah...';
        let isGood = false;

        if (!hasFace) {
            message = '💡 Hadap ke kamera';
        } else if (!isBrightnessGood) {
            if (brightness <= 50) {
                message = '💡 Terlalu gelap';
            } else {
                message = '☀️ Terlalu terang';
            }
        } else if (!isConfidenceGood) {
            message = '📸 Posisi wajah kurang jelas';
        } else {
            message = '✅ Wajah terdeteksi dengan baik';
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

    // Monitor face quality and trigger auto-capture
    useEffect(() => {
        if (!videoRef.current || !autoCapture || hasCapturedRef.current || captureStatus !== 'idle') {
            return;
        }

        const quality = analyzeFaceQuality(videoRef.current);
        setFaceQuality(quality);

        if (quality.isGood) {
            // Face is good quality
            if (stableStartTimeRef.current === null) {
                stableStartTimeRef.current = Date.now();
            }

            const elapsed = Date.now() - stableStartTimeRef.current;
            const progress = Math.min((elapsed / stabilityDuration) * 100, 100);
            setCaptureProgress(progress);

            if (elapsed >= stabilityDuration) {
                // Trigger auto-capture
                hasCapturedRef.current = true;
                handleAutoCapture();
            }
        } else {
            // Reset stability timer
            stableStartTimeRef.current = null;
            setCaptureProgress(0);
        }
    }, [detectionStatus, confidence, autoCapture, captureStatus, analyzeFaceQuality, stabilityDuration]);

    const handleAutoCapture = async () => {
        if (!videoRef.current) return;

        setCaptureStatus('capturing');
        setShowFlash(true);

        // Haptic feedback if available
        if (navigator.vibrate) {
            navigator.vibrate(100);
        }

        // Flash effect
        setTimeout(() => setShowFlash(false), 200);

        try {
            await onCapture(videoRef.current);
            setCaptureStatus('success');
        } catch (err) {
            console.error('Capture error:', err);
            setCaptureStatus('failed');
            onError?.(err instanceof Error ? err.message : 'Gagal mengambil foto');

            // Reset for retry
            setTimeout(() => {
                setCaptureStatus('idle');
                hasCapturedRef.current = false;
                stableStartTimeRef.current = null;
                setCaptureProgress(0);
            }, 2000);
        }
    };

    return (
        <div className={cn('space-y-4', className)}>
            {/* Camera View */}
            <div className="relative aspect-video overflow-hidden rounded-lg bg-neutral-900">
                {cameraStatus === 'active' ? (
                    <>
                        <video
                            ref={videoRef}
                            autoPlay
                            playsInline
                            muted
                            className="h-full w-full object-cover"
                        />
                        <canvas
                            ref={canvasRef}
                            className="absolute inset-0 h-full w-full"
                        />

                        {/* Flash Effect */}
                        {showFlash && (
                            <div className="absolute inset-0 bg-white animate-pulse" />
                        )}

                        {/* Status Overlays */}
                        {captureStatus === 'idle' && (
                            <>
                                {/* No face detected */}
                                {!faceQuality.hasFace && (
                                    <div className="absolute inset-0 flex items-center justify-center bg-black/50">
                                        <div className="text-center">
                                            <Camera className="mx-auto h-12 w-12 text-white/70 animate-pulse" />
                                            <p className="mt-2 text-sm text-white/70">{faceQuality.message}</p>
                                        </div>
                                    </div>
                                )}

                                {/* Face detected - show quality feedback */}
                                {faceQuality.hasFace && (
                                    <div className="absolute bottom-4 left-1/2 -translate-x-1/2 space-y-2">
                                        <Badge
                                            className={cn(
                                                'gap-1 border-0',
                                                faceQuality.isGood
                                                    ? 'bg-success'
                                                    : 'bg-warning'
                                            )}
                                        >
                                            {faceQuality.isGood ? (
                                                <CheckCircle2 className="h-3 w-3" />
                                            ) : (
                                                <AlertTriangle className="h-3 w-3" />
                                            )}
                                            {faceQuality.message}
                                        </Badge>

                                        {/* Stability Progress */}
                                        {faceQuality.isGood && captureProgress > 0 && (
                                            <div className="bg-black/50 backdrop-blur-sm rounded-full px-3 py-1">
                                                <Progress value={captureProgress} className="h-1 w-32" />
                                            </div>
                                        )}
                                    </div>
                                )}
                            </>
                        )}

                        {/* Capturing */}
                        {captureStatus === 'capturing' && (
                            <div className="absolute inset-0 flex items-center justify-center bg-black/60">
                                <div className="text-center">
                                    <Loader2 className="mx-auto h-12 w-12 text-primary animate-spin" />
                                    <p className="mt-2 text-sm text-white">Memproses...</p>
                                </div>
                            </div>
                        )}

                        {/* Success */}
                        {captureStatus === 'success' && (
                            <div className="absolute inset-0 flex items-center justify-center bg-success/30">
                                <div className="text-center">
                                    <CheckCircle2 className="mx-auto h-16 w-16 text-success" />
                                    <p className="mt-2 text-lg font-semibold text-white">Foto diambil!</p>
                                </div>
                            </div>
                        )}

                        {/* Failed */}
                        {captureStatus === 'failed' && (
                            <div className="absolute inset-0 flex items-center justify-center bg-destructive/30">
                                <div className="text-center">
                                    <XCircle className="mx-auto h-16 w-16 text-destructive" />
                                    <p className="mt-2 text-lg font-semibold text-white">Gagal</p>
                                </div>
                            </div>
                        )}
                    </>
                ) : (
                    <div className="flex h-full flex-col items-center justify-center gap-4">
                        <CameraOff className="h-12 w-12 text-muted-foreground" />
                        <p className="text-muted-foreground">Mengaktifkan kamera...</p>
                    </div>
                )}
            </div>

            {/* Error Alert */}
            {error && (
                <Alert variant="destructive">
                    <XCircle className="h-4 w-4" />
                    <AlertTitle>Error</AlertTitle>
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}
        </div>
    );
}
