import { useState, useCallback, useRef, useEffect } from 'react';
import { faceDetectionService } from '@/lib/services/face-detection';
import type {
  FaceDetectionResult,
  FaceData,
  CameraStatus,
  DetectionStatus,
  RecognitionResult,
  KnownFaceDescriptor,
} from '@/types/face-recognition';

interface UseFaceDetectionOptions {
  autoStart?: boolean;
  onDetection?: (detections: FaceDetectionResult[]) => void;
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
  detections: FaceDetectionResult[];
  error: string | null;
  confidence: number;
  isProcessing: boolean;

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
  const [detections, setDetections] = useState<FaceDetectionResult[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [confidence, setConfidence] = useState(0);
  const [isProcessing, setIsProcessing] = useState(false);

  // Initialize face detection models
  const initialize = useCallback(async () => {
    try {
      setIsProcessing(true);
      await faceDetectionService.initialize();
      setIsInitialized(true);
      setError(null);
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
      await faceDetectionService.startCamera(videoRef.current);
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
    faceDetectionService.stopCamera();
    setCameraStatus('stopped');
    stopDetection();
  }, []);

  // Detection loop
  const detectLoop = useCallback(async () => {
    if (!isDetectingRef.current || !videoRef.current || !canvasRef.current) {
      return;
    }

    try {
      // Use fast detection for the preview loop
      const detected = await faceDetectionService.detectFacesFast(videoRef.current);
      setDetections(detected);

      if (detected.length === 0) {
        setDetectionStatus('no_face');
        setConfidence(0);
      } else if (detected.length > 1) {
        setDetectionStatus('multiple_faces');
        setConfidence(0);
      } else {
        setDetectionStatus('detected');
        setConfidence(detected[0].detection.score);
      }

      // Draw detections on canvas
      const displaySize = faceDetectionService.getDisplaySize(videoRef.current);
      canvasRef.current.width = displaySize.width;
      canvasRef.current.height = displaySize.height;
      faceDetectionService.drawDetections(canvasRef.current, detected, displaySize);

      onDetection?.(detected);
    } catch (err) {
      setDetectionStatus('error');
      console.error('Detection error:', err);
    }

    // Continue loop
    if (isDetectingRef.current) {
      animationFrameRef.current = requestAnimationFrame(detectLoop);
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
    setDetections([]);
  }, []);

  // Capture face descriptor
  const captureDescriptor = useCallback(
    async (employeeId: string | number, requireLiveness = false): Promise<FaceData> => {
      if (!videoRef.current) {
        throw new Error('Video not ready');
      }

      setIsProcessing(true);
      try {
        const faceData = await faceDetectionService.captureFaceDescriptor(
          videoRef.current,
          employeeId,
          { requireLiveness }
        );
        return faceData;
      } finally {
        setIsProcessing(false);
      }
    },
    []
  );

  // Recognize face
  const recognizeFace = useCallback(
    async (knownDescriptors: KnownFaceDescriptor[]): Promise<RecognitionResult> => {
      if (!videoRef.current) {
        return { success: false, message: 'Video not ready', confidence: 0 };
      }

      setIsProcessing(true);
      try {
        return await faceDetectionService.recognizeFace(videoRef.current, knownDescriptors);
      } finally {
        setIsProcessing(false);
      }
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
      faceDetectionService.destroy();
    };
  }, [stopCamera]);

  return {
    videoRef,
    canvasRef,
    isInitialized,
    cameraStatus,
    detectionStatus,
    detections,
    error,
    confidence,
    isProcessing,
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
