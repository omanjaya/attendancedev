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

  // Detection loop - simplified for DeepFace backend
  // Since actual detection is done server-side, this just provides a visual guide
  const detectLoop = useCallback(async () => {
    if (!isDetectingRef.current || !videoRef.current || !canvasRef.current) {
      return;
    }

    try {
      // For DeepFace backend, we simulate detection being "active" when camera is ready
      // Actual face detection/recognition happens server-side when image is captured
      const displaySize = faceDetectionService.getDisplaySize(videoRef.current);

      // Draw face guide on canvas (oval outline to help user position face)
      faceDetectionService.drawFaceGuide(canvasRef.current, displaySize, true);

      // Simulate detection status based on camera being active
      setDetectionStatus('detected');
      setConfidence(0.9); // High confidence when camera is active
      setDetections([]);

      onDetection?.([]);
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

  // Capture face descriptor - now just captures image for server-side processing
  const captureDescriptor = useCallback(
    async (): Promise<FaceData> => {
      if (!videoRef.current) {
        throw new Error('Video not ready');
      }

      setIsProcessing(true);
      try {
        // Capture image file for server-side processing
        const imageFile = await faceDetectionService.captureImage(videoRef.current);

        // Return face data structure (descriptor will be computed server-side)
        return {
          descriptor: [], // Empty - will be computed by DeepFace
          confidence: 0.9,
          face_bounds: { x: 0, y: 0, width: 0, height: 0 },
          timestamp: Date.now(),
          landmarks: undefined,
          expressions: undefined,
          imageData: await fileToBase64(imageFile),
        } as FaceData & { imageData: string };
      } finally {
        setIsProcessing(false);
      }
    },
    []
  );

  // Recognize face - now returns a placeholder since actual recognition is server-side
  const recognizeFace = useCallback(
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    async (_knownDescriptors: KnownFaceDescriptor[]): Promise<RecognitionResult> => {
      if (!videoRef.current) {
        return { success: false, message: 'Video not ready', confidence: 0 };
      }

      setIsProcessing(true);
      try {
        // Face recognition is handled server-side with DeepFace
        // This function is kept for interface compatibility
        return {
          success: false,
          message: 'Use server-side face recognition via API',
          confidence: 0,
        };
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

// Helper function to convert File to base64
async function fileToBase64(file: File): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result as string);
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

export default useFaceDetection;
