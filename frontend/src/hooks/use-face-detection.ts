import { useState, useCallback, useRef, useEffect } from 'react';
import { clientFaceDetection } from '@/lib/services/client-face-detection';
import type {
  FaceData,
  CameraStatus,
  DetectionStatus,
  RecognitionResult,
  KnownFaceDescriptor,
} from '@/types/face-recognition';

interface UseFaceDetectionOptions {
  autoStart?: boolean;
  onDetection?: (detected: boolean, confidence: number, isSmiling?: boolean, smileScore?: number) => void;
  onError?: (error: string) => void;
}

interface UseFaceDetectionReturn {
  // Refs
  videoRef: React.RefObject<HTMLVideoElement | null>;
  canvasRef: React.RefObject<HTMLCanvasElement | null>;

  // State
  isInitialized: boolean;
  cameraStatus: CameraStatus;
  detectionStatus: DetectionStatus;
  error: string | null;
  confidence: number;
  isProcessing: boolean;
  isSmiling: boolean;
  smileScore: number;

  // Actions
  initialize: () => Promise<void>;
  startCamera: () => Promise<void>;
  stopCamera: () => void;
  startDetection: () => void;
  stopDetection: () => void;
  captureDescriptor: (employeeId: string | number, requireLiveness?: boolean) => Promise<FaceData>;
  recognizeFace: (knownDescriptors: KnownFaceDescriptor[]) => Promise<RecognitionResult>;
  clearError: () => void;
}

export function useFaceDetection(options: UseFaceDetectionOptions = {}): UseFaceDetectionReturn {
  const { onDetection, onError } = options;

  const videoRef = useRef<HTMLVideoElement>(null);
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const animationFrameRef = useRef<number | null>(null);
  const isDetectingRef = useRef(false);

  const [isInitialized, setIsInitialized] = useState(false);
  const [cameraStatus, setCameraStatus] = useState<CameraStatus>('idle');
  const [detectionStatus, setDetectionStatus] = useState<DetectionStatus>('idle');
  const [error, setError] = useState<string | null>(null);
  const [confidence, setConfidence] = useState(0);
  const [isProcessing, setIsProcessing] = useState(false);
  const [isSmiling, setIsSmiling] = useState(false);
  const [smileScore, setSmileScore] = useState(0);

  // Initialize face detection (client-side model loading)
  const initialize = useCallback(async () => {
    try {
      setIsProcessing(true);
      await clientFaceDetection.initialize();
      setIsInitialized(true);
      setError(null);
      console.log('Client-side face detection initialized');
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to initialize';
      setError(message);
      onError?.(message);
    } finally {
      setIsProcessing(false);
    }
  }, [onError]);

  // Start camera
  const startCamera = useCallback(async () => {
    if (!videoRef.current) {
      setError('Video element not ready');
      return;
    }

    try {
      setCameraStatus('starting');
      await clientFaceDetection.startCamera(videoRef.current);
      setCameraStatus('active');
      setError(null);
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to start camera';
      setError(message);
      setCameraStatus('error');
      onError?.(message);
    }
  }, [onError]);

  // Stop camera
  const stopCamera = useCallback(() => {
    clientFaceDetection.stopCamera();
    setCameraStatus('stopped');
    stopDetection();
  }, []);

  // Detection loop - CLIENT-SIDE only, no API calls!
  const detectLoop = useCallback(async () => {
    // Check refs at start AND before any canvas operations
    if (!isDetectingRef.current || !videoRef.current) {
      return;
    }

    try {
      const displaySize = clientFaceDetection.getDisplaySize(videoRef.current);

      // Client-side face detection with expression (instant, no API call)
      const result = await clientFaceDetection.detectFaces(videoRef.current);

      // Re-check refs after async operation (component may have unmounted)
      if (!isDetectingRef.current || !canvasRef.current) {
        return;
      }

      if (result.detected && result.confidence > 0.5) {
        setDetectionStatus('detected');
        setConfidence(result.confidence);
        setIsSmiling(result.isSmiling || false);
        setSmileScore(result.smileScore || 0);
        clientFaceDetection.drawFaceGuide(canvasRef.current, displaySize, true, result.faces);
        onDetection?.(true, result.confidence, result.isSmiling, result.smileScore);
      } else {
        setDetectionStatus('no_face');
        setConfidence(0);
        setIsSmiling(false);
        setSmileScore(0);
        clientFaceDetection.drawFaceGuide(canvasRef.current, displaySize, false);
        onDetection?.(false, 0, false, 0);
      }
    } catch (err) {
      // Only log if still detecting (ignore errors from unmounted component)
      if (isDetectingRef.current) {
        console.error('Detection error:', err);
      }
    }

    // Continue loop with requestAnimationFrame - check ref again
    if (isDetectingRef.current) {
      // Throttle to ~8 detections per second for performance with expressions
      setTimeout(() => {
        if (isDetectingRef.current) {
          animationFrameRef.current = requestAnimationFrame(detectLoop);
        }
      }, 120);
    }
  }, [onDetection]);

  // Start detection
  const startDetection = useCallback(() => {
    if (!isInitialized || cameraStatus !== 'active') {
      setError('Camera not ready');
      return;
    }

    isDetectingRef.current = true;
    setDetectionStatus('detecting');
    detectLoop();
  }, [isInitialized, cameraStatus, detectLoop]);

  // Stop detection
  const stopDetection = useCallback(() => {
    isDetectingRef.current = false;
    if (animationFrameRef.current) {
      cancelAnimationFrame(animationFrameRef.current);
      animationFrameRef.current = null;
    }
    setDetectionStatus('idle');
  }, []);

  // Capture face descriptor - captures image for server-side processing
  const captureDescriptor = useCallback(
    async (): Promise<FaceData> => {
      if (!videoRef.current) {
        throw new Error('Video not ready');
      }

      setIsProcessing(true);
      try {
        const imageFile = await clientFaceDetection.captureImage(videoRef.current);
        const imageData = clientFaceDetection.captureBase64(videoRef.current);

        return {
          descriptor: [], // Will be computed by DeepFace server-side
          confidence: confidence,
          face_bounds: { x: 0, y: 0, width: 0, height: 0 },
          timestamp: Date.now(),
          landmarks: undefined,
          expressions: undefined,
          imageData: imageData,
          imageFile: imageFile,
        } as FaceData & { imageData: string; imageFile: File };
      } finally {
        setIsProcessing(false);
      }
    },
    [confidence]
  );

  // Recognize face - placeholder, actual recognition is server-side
  const recognizeFace = useCallback(
    async (_knownDescriptors: KnownFaceDescriptor[]): Promise<RecognitionResult> => {
      if (!videoRef.current) {
        return { success: false, message: 'Video not ready', confidence: 0 };
      }

      return {
        success: false,
        message: 'Use server-side face recognition via API',
        confidence: 0,
      };
    },
    []
  );

  // Clear error
  const clearError = useCallback(() => {
    setError(null);
  }, []);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      stopCamera();
      clientFaceDetection.destroy();
    };
  }, [stopCamera]);

  return {
    videoRef,
    canvasRef,
    isInitialized,
    cameraStatus,
    detectionStatus,
    error,
    confidence,
    isProcessing,
    isSmiling,
    smileScore,
    initialize,
    startCamera,
    stopCamera,
    startDetection,
    stopDetection,
    captureDescriptor,
    recognizeFace,
    clearError,
  };
}

export default useFaceDetection;
