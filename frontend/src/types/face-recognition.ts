// Face Detection Types

export interface FaceBox {
  x: number;
  y: number;
  width: number;
  height: number;
}

export interface FaceLandmark {
  x: number;
  y: number;
}

export interface FaceExpression {
  expression: string;
  probability: number;
}

export interface FacePose {
  yaw: number;
  pitch: number;
  roll: number;
}

export interface FaceDetectionResult {
  detection: {
    box: FaceBox;
    score: number;
  };
  landmarks?: {
    positions: FaceLandmark[];
  };
  descriptor?: Float32Array;
  expressions?: {
    asSortedArray: () => FaceExpression[];
  };
}

export interface FaceData {
  descriptor: number[];
  landmarks?: FaceLandmark[];
  confidence: number;
  pose?: FacePose | null;
  expressions?: FaceExpression[] | null;
  face_bounds: FaceBox;
  timestamp: number;
  liveness?: LivenessResult;
}

export interface LivenessResult {
  blink_detected: boolean;
  head_movement: number;
  gesture_completed: boolean;
  liveness_score: number;
  checks_performed: string[];
  is_live: boolean;
}

export interface ImageQuality {
  lighting_score: number;
  blur_score: number;
  noise_level: number;
}

export interface RecognitionResult {
  success: boolean;
  message?: string;
  employeeId?: string | number;
  confidence: number;
  landmarks?: FaceLandmark[];
}

export interface KnownFaceDescriptor {
  employeeId: string | number;
  descriptor: Float32Array | number[];
  name?: string;
}

export type CameraStatus = 'idle' | 'starting' | 'active' | 'error' | 'stopped';
export type DetectionStatus = 'idle' | 'detecting' | 'detected' | 'no_face' | 'multiple_faces' | 'error';

export interface FaceDetectionState {
  isInitialized: boolean;
  cameraStatus: CameraStatus;
  detectionStatus: DetectionStatus;
  currentDetections: FaceDetectionResult[];
  error: string | null;
  confidence: number;
  isProcessing: boolean;
}

export interface FaceEnrollmentData {
  employee_id: number;
  employee_name: string;
  descriptors: number[][];
  enrolled_at: string;
  updated_at: string;
}

export interface FaceRecognitionSettings {
  confidence_threshold: number;
  liveness_threshold: number;
  max_detection_distance: number;
  enable_liveness: boolean;
  enable_expressions: boolean;
  camera_resolution: 'low' | 'medium' | 'high';
}
