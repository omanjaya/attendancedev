import { useEffect, useRef, useState, useCallback } from 'react';
import { CameraOff, CheckCircle2, XCircle, Loader2, Smile } from 'lucide-react';
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
    confidenceThreshold?: number;
    requireSmile?: boolean;
    className?: string;
}

type LivenessStep = 'idle' | 'detecting' | 'smile_prompt' | 'capturing' | 'success' | 'failed';

export function AutoCaptureFace({
    onCapture,
    onError,
    autoCapture = true,
    confidenceThreshold = 0.5,
    requireSmile = true,
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
        isSmiling,
        smileScore,
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
    const [showFlash, setShowFlash] = useState(false);

    const hasCapturedRef = useRef(false);
    const [videoReady, setVideoReady] = useState(false);

    // Initialize camera after video element is mounted and ready
    useEffect(() => {
        if (!videoReady) return;

        const init = async () => {
            try {
                if (!isInitialized) {
                    await initialize();
                }
                await startCamera();
            } catch (err) {
                console.error('Camera init failed:', err);
                onError?.(err instanceof Error ? err.message : 'Gagal mengakses kamera. Pastikan izin kamera diaktifkan.');
            }
        };

        init();

        return () => {
            stopDetection();
            stopCamera();
        };
    }, [videoReady]);

    // Start detection only after camera is active
    useEffect(() => {
        if (cameraStatus === 'active' && isInitialized && livenessStep === 'idle') {
            setLivenessStep('detecting');
            startDetection();
        }
    }, [cameraStatus, isInitialized, livenessStep, startDetection]);

    // Callback ref to detect when video element is mounted
    const handleVideoRef = useCallback((node: HTMLVideoElement | null) => {
        if (node) {
            (videoRef as React.MutableRefObject<HTMLVideoElement | null>).current = node;
            setVideoReady(true);
        }
    }, [videoRef]);

    // Analyze face quality
    const analyzeFaceQuality = useCallback((video: HTMLVideoElement): FaceQuality => {
        if (!video.videoWidth || !video.videoHeight) {
            return {
                hasFace: false,
                faceCount: 0,
                confidence: 0,
                brightness: 0,
                isStable: false,
                message: 'Menunggu kamera...',
                isGood: false,
            };
        }

        const hasFace = detectionStatus === 'detected';
        const faceConfidence = confidence;
        const isConfidenceGood = faceConfidence >= confidenceThreshold;

        let message = 'Mencari wajah...';
        let isGood = false;

        if (!hasFace) {
            message = '👤 Hadapkan wajah ke kamera';
        } else if (!isConfidenceGood) {
            message = '📸 Posisikan wajah lebih jelas';
        } else {
            message = '✅ Wajah terdeteksi';
            isGood = true;
        }

        return {
            hasFace,
            faceCount: hasFace ? 1 : 0,
            confidence: faceConfidence,
            brightness: 128, // Not checking brightness anymore for speed
            isStable: isGood,
            message,
            isGood,
        };
    }, [detectionStatus, confidence, confidenceThreshold]);

    // Main detection logic
    useEffect(() => {
        if (!videoRef.current || !autoCapture || hasCapturedRef.current) {
            return;
        }

        const quality = analyzeFaceQuality(videoRef.current);
        setFaceQuality(quality);

        // Step 1: Face detected -> prompt smile
        if (livenessStep === 'detecting' && quality.isGood) {
            if (requireSmile) {
                setLivenessStep('smile_prompt');
            } else {
                // No smile required, capture immediately
                handleAutoCapture();
            }
        }

        // Step 2: Waiting for smile -> capture when smile detected
        if (livenessStep === 'smile_prompt') {
            if (!quality.isGood) {
                // Lost face, go back to detecting
                setLivenessStep('detecting');
            } else if (isSmiling) {
                // Smile detected! Capture immediately
                handleAutoCapture();
            }
        }
    }, [detectionStatus, confidence, isSmiling, autoCapture, livenessStep, analyzeFaceQuality, requireSmile]);

    const handleAutoCapture = useCallback(async () => {
        if (!videoRef.current || hasCapturedRef.current) return;

        hasCapturedRef.current = true;
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
        } catch (err) {
            console.error('Capture error:', err);
            setLivenessStep('failed');
            onError?.(err instanceof Error ? err.message : 'Gagal mengambil foto');

            // Reset for retry
            setTimeout(() => {
                setLivenessStep('detecting');
                hasCapturedRef.current = false;
            }, 2000);
        }
    }, [onCapture, onError]);

    const smileProgress = Math.min((smileScore || 0) * 100 / 0.05, 100); // 5% threshold

    return (
        <div className={cn('space-y-4 w-full max-w-md mx-auto', className)}>
            {/* Camera View */}
            <div className="relative aspect-[3/4] sm:aspect-square w-full overflow-hidden rounded-2xl bg-neutral-900 border-4 border-neutral-800 shadow-xl">
                <video
                    ref={handleVideoRef}
                    autoPlay
                    playsInline
                    muted
                    className={cn(
                        "h-full w-full object-cover scale-x-[-1]",
                        cameraStatus !== 'active' && "hidden"
                    )}
                />
                <canvas
                    ref={canvasRef}
                    className={cn(
                        "absolute inset-0 h-full w-full scale-x-[-1]",
                        cameraStatus !== 'active' && "hidden"
                    )}
                />

                {cameraStatus === 'active' ? (
                    <>
                        {/* Flash Effect */}
                        {showFlash && (
                            <div className="absolute inset-0 bg-white animate-pulse z-50" />
                        )}

                        {/* Status Overlay */}
                        {livenessStep !== 'capturing' && livenessStep !== 'success' && (
                            <div className="absolute inset-x-0 bottom-0 p-6 bg-gradient-to-t from-black/80 to-transparent flex flex-col items-center gap-3">
                                {/* Detecting */}
                                {livenessStep === 'detecting' && (
                                    <div className="flex flex-col items-center gap-2">
                                        <Loader2 className="w-6 h-6 text-blue-400 animate-spin" />
                                        <Badge
                                            variant="secondary"
                                            className="px-4 py-1 text-sm"
                                        >
                                            {faceQuality.message}
                                        </Badge>
                                    </div>
                                )}

                                {/* Smile Prompt */}
                                {livenessStep === 'smile_prompt' && (
                                    <div className="flex flex-col items-center gap-3 animate-in fade-in slide-in-from-bottom-3">
                                        <div className={cn(
                                            "p-3 rounded-full border-2",
                                            isSmiling
                                                ? "bg-green-500/20 border-green-500"
                                                : "bg-yellow-500/20 border-yellow-500 animate-bounce"
                                        )}>
                                            <Smile className={cn(
                                                "w-8 h-8",
                                                isSmiling ? "text-green-400" : "text-yellow-400"
                                            )} />
                                        </div>
                                        <Badge
                                            variant="default"
                                            className={cn(
                                                "px-4 py-1 text-sm",
                                                isSmiling ? "bg-green-600" : "bg-yellow-600"
                                            )}
                                        >
                                            {isSmiling ? '😊 Senyum terdeteksi!' : '😊 Silakan Senyum!'}
                                        </Badge>
                                        {/* Smile progress */}
                                        <div className="w-32">
                                            <Progress value={smileProgress} className="h-2" />
                                        </div>
                                    </div>
                                )}

                                {/* Failed */}
                                {livenessStep === 'failed' && (
                                    <Badge variant="destructive" className="px-4 py-1 text-sm">
                                        Gagal, mencoba lagi...
                                    </Badge>
                                )}
                            </div>
                        )}

                        {/* Processing */}
                        {livenessStep === 'capturing' && (
                            <div className="absolute inset-0 flex flex-col items-center justify-center bg-black/60 backdrop-blur-sm z-40">
                                <Loader2 className="h-12 w-12 text-primary animate-spin" />
                                <p className="mt-4 text-lg font-medium text-white">Memproses...</p>
                            </div>
                        )}

                        {/* Success */}
                        {livenessStep === 'success' && (
                            <div className="absolute inset-0 flex flex-col items-center justify-center bg-green-600/80 backdrop-blur-sm z-40 animate-in fade-in zoom-in">
                                <CheckCircle2 className="h-20 w-20 text-white drop-shadow-lg" />
                                <p className="mt-4 text-xl font-bold text-white">Berhasil!</p>
                            </div>
                        )}
                    </>
                ) : (
                    <div className="flex h-full flex-col items-center justify-center gap-4">
                        <CameraOff className="h-12 w-12 text-muted-foreground" />
                        <p className="text-muted-foreground">Memuat model deteksi wajah...</p>
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
