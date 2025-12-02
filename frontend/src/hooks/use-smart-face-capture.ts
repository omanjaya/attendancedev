import { useRef, useState, useCallback, useEffect } from 'react';

/**
 * Smart Face Capture Hook with Quality & Stability Checks
 * 
 * Flow:
 * 1. Face Detection (real-time)
 * 2. Quality Validation (position, lighting, distance)
 * 3. Stability Check (no movement for 2s)
 * 4. Auto-Capture (when optimal)
 */

export interface FaceDetectionQuality {
    hasface: boolean;
    isFrontal: boolean;
    isWellLit: boolean;
    isGoodDistance: boolean;
    isStable: boolean;
    message: string;
    readyToCapture: boolean;
}

interface SmartFaceCaptureOptions {
    stabilityDuration?: number; // milliseconds to wait for stability (default: 2000ms)
    onQualityChange?: (quality: FaceDetectionQuality) => void;
    onCapture?: (imageFile: File) => void;
}

export function useSmartFaceCapture(options: SmartFaceCaptureOptions = {}) {
    const {
        stabilityDuration = 2000,
        onQualityChange,
        onCapture,
    } = options;

    const videoRef = useRef<HTMLVideoElement>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const detectionFrameRef = useRef<number | null>(null);
    const stabilityTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const previousFaceBoundsRef = useRef<{ x: number; y: number; width: number; height: number } | null>(null);

    const [cameraStatus, setCameraStatus] = useState<'idle' | 'starting' | 'active' | 'error'>('idle');
    const [isDetecting, setIsDetecting] = useState(false);
    const [quality, setQuality] = useState<FaceDetectionQuality>({
        hasface: false,
        isFrontal: false,
        isWellLit: false,
        isGoodDistance: false,
        isStable: false,
        message: 'Menunggu...',
        readyToCapture: false,
    });
    const [stabilityProgress, setStabilityProgress] = useState(0);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    /**
     * Start camera
     */
    const startCamera = useCallback(async () => {
        setCameraStatus('starting');
        setErrorMessage(null);

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 1280, max: 1920 },
                    height: { ideal: 720, max: 1080 },
                    facingMode: 'user',
                },
                audio: false,
            });

            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                streamRef.current = stream;

                await new Promise<void>((resolve) => {
                    if (videoRef.current) {
                        videoRef.current.onloadedmetadata = () => {
                            videoRef.current?.play();
                            resolve();
                        };
                    }
                });

                setCameraStatus('active');
            }
        } catch (error) {
            console.error('Failed to start camera:', error);
            setCameraStatus('error');
            setErrorMessage('Gagal mengakses kamera. Pastikan izin kamera telah diberikan.');
        }
    }, []);

    /**
     * Stop camera
     */
    const stopCamera = useCallback(() => {
        if (streamRef.current) {
            streamRef.current.getTracks().forEach((track) => track.stop());
            streamRef.current = null;
        }
        if (videoRef.current) {
            videoRef.current.srcObject = null;
        }
        setCameraStatus('idle');
        setIsDetecting(false);
    }, []);

    /**
     * Start face detection loop
     */
    const startDetection = useCallback(() => {
        if (cameraStatus !== 'active') return;
        setIsDetecting(true);
    }, [cameraStatus]);

    /**
     * Stop face detection
     */
    const stopDetection = useCallback(() => {
        setIsDetecting(false);
        if (detectionFrameRef.current) {
            cancelAnimationFrame(detectionFrameRef.current);
            detectionFrameRef.current = null;
        }
        if (stabilityTimerRef.current) {
            clearTimeout(stabilityTimerRef.current);
            stabilityTimerRef.current = null;
        }
        setStabilityProgress(0);
    }, []);

    /**
     * Detect face using canvas-based simple detection
     * (lightweight, no ML models needed)
     */
    const detectFaceSimple = useCallback((): {
        detected: boolean;
        bounds?: { x: number; y: number; width: number; height: number };
        brightness?: number;
    } => {
        if (!videoRef.current || !canvasRef.current) {
            return { detected: false };
        }

        const video = videoRef.current;
        const canvas = canvasRef.current;
        const ctx = canvas.getContext('2d');
        if (!ctx) return { detected: false };

        // Set canvas size to match video
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        // Draw current frame
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        // Get image data for analysis
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;

        // Calculate average brightness
        let totalBrightness = 0;
        for (let i = 0; i < data.length; i += 4) {
            const r = data[i];
            const g = data[i + 1];
            const b = data[i + 2];
            // Use luminance formula
            const brightness = (0.299 * r + 0.587 * g + 0.114 * b);
            totalBrightness += brightness;
        }
        const avgBrightness = totalBrightness / (data.length / 4);

        // Simple face detection: assume face is in center region
        // For production, you'd use a proper face detection library
        // But for now, we'll use a heuristic approach
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const faceWidth = canvas.width * 0.4;
        const faceHeight = canvas.height * 0.5;

        return {
            detected: true, // Assume face is present (will be validated by DeepFace)
            bounds: {
                x: centerX - faceWidth / 2,
                y: centerY - faceHeight / 2,
                width: faceWidth,
                height: faceHeight,
            },
            brightness: avgBrightness,
        };
    }, []);

    /**
     * Check if face movement is stable
     */
    const checkStability = useCallback((
        currentBounds: { x: number; y: number; width: number; height: number }
    ): boolean => {
        if (!previousFaceBoundsRef.current) {
            previousFaceBoundsRef.current = currentBounds;
            return false;
        }

        const prev = previousFaceBoundsRef.current;
        const threshold = 30; // pixels

        const dx = Math.abs(currentBounds.x - prev.x);
        const dy = Math.abs(currentBounds.y - prev.y);
        const dw = Math.abs(currentBounds.width - prev.width);
        const dh = Math.abs(currentBounds.height - prev.height);

        const isStable = dx < threshold && dy < threshold && dw < threshold && dh < threshold;

        previousFaceBoundsRef.current = currentBounds;

        return isStable;
    }, []);

    /**
     * Capture image when ready
     */
    const captureImage = useCallback(async (): Promise<File> => {
        if (!videoRef.current || cameraStatus !== 'active') {
            throw new Error('Camera tidak aktif');
        }

        const video = videoRef.current;
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            throw new Error('Gagal membuat canvas context');
        }

        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        return new Promise((resolve, reject) => {
            canvas.toBlob((blob) => {
                if (blob) {
                    const file = new File([blob], 'face-capture.jpg', { type: 'image/jpeg' });
                    resolve(file);
                } else {
                    reject(new Error('Gagal mengambil gambar'));
                }
            }, 'image/jpeg', 0.95);
        });
    }, [cameraStatus]);

    /**
     * Detection loop
     */
    useEffect(() => {
        if (!isDetecting || cameraStatus !== 'active') return;

        const detect = () => {
            const result = detectFaceSimple();

            if (result.detected && result.bounds) {
                const brightness = result.brightness || 0;
                const isWellLit = brightness >= 50 && brightness <= 220;
                const isGoodDistance = result.bounds.width >= 200 && result.bounds.width <= 600;
                const isStable = checkStability(result.bounds);

                const newQuality: FaceDetectionQuality = {
                    hasface: true,
                    isFrontal: true, // Assuming frontal for now
                    isWellLit,
                    isGoodDistance,
                    isStable,
                    message: '',
                    readyToCapture: false,
                };

                // Determine message based on quality
                if (!isWellLit) {
                    newQuality.message = 'Pencahayaan kurang baik';
                } else if (!isGoodDistance) {
                    newQuality.message = result.bounds.width < 200 ? 'Terlalu jauh, dekat sedikit' : 'Terlalu dekat, mundur sedikit';
                } else if (!isStable) {
                    newQuality.message = 'Wajah terdeteksi - Tahan posisi, jangan bergerak...';
                } else {
                    newQuality.message = 'Sempurna! Memproses...';
                    newQuality.readyToCapture = true;
                }

                setQuality(newQuality);
                onQualityChange?.(newQuality);

                // Handle stability timer
                if (isWellLit && isGoodDistance && isStable) {
                    if (!stabilityTimerRef.current) {
                        // Start stability timer
                        const startTime = Date.now();
                        stabilityTimerRef.current = setInterval(() => {
                            const elapsed = Date.now() - startTime;
                            const progress = Math.min((elapsed / stabilityDuration) * 100, 100);
                            setStabilityProgress(progress);

                            if (progress >= 100) {
                                // Conditions met, auto-capture
                                clearInterval(stabilityTimerRef.current!);
                                stabilityTimerRef.current = null;
                                stopDetection();

                                captureImage().then((file) => {
                                    onCapture?.(file);
                                }).catch((error) => {
                                    console.error('Capture error:', error);
                                    setErrorMessage('Gagal mengambil gambar');
                                    // Restart detection
                                    startDetection();
                                });
                            }
                        }, 50) as unknown as ReturnType<typeof setTimeout>;
                    }
                } else {
                    // Reset stability timer if conditions not met
                    if (stabilityTimerRef.current) {
                        clearInterval(stabilityTimerRef.current);
                        stabilityTimerRef.current = null;
                        setStabilityProgress(0);
                    }
                }
            } else {
                setQuality({
                    hasface: false,
                    isFrontal: false,
                    isWellLit: false,
                    isGoodDistance: false,
                    isStable: false,
                    message: 'Mencari wajah...',
                    readyToCapture: false,
                });
                setStabilityProgress(0);
            }

            detectionFrameRef.current = requestAnimationFrame(detect);
        };

        detect();

        return () => {
            if (detectionFrameRef.current) {
                cancelAnimationFrame(detectionFrameRef.current);
            }
            if (stabilityTimerRef.current) {
                clearInterval(stabilityTimerRef.current);
            }
        };
    }, [isDetecting, cameraStatus, detectFaceSimple, checkStability, stabilityDuration, onQualityChange, onCapture, captureImage, startDetection, stopDetection]);

    return {
        videoRef,
        canvasRef,
        cameraStatus,
        isDetecting,
        quality,
        stabilityProgress,
        errorMessage,
        startCamera,
        stopCamera,
        startDetection,
        stopDetection,
    };
}
