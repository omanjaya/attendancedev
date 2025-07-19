// Face Recognition Types
export interface FaceDetectionResult {
  confidence: number
  liveness: number
  boundingBox: BoundingBox | null
  landmarks?: FaceLandmark[]
  embeddings?: number[]
}

export interface BoundingBox {
  x: number
  y: number
  width: number
  height: number
}

export interface FaceLandmark {
  x: number
  y: number
  z?: number
}

export interface CameraStatus {
  title: string
  message: string
}

export interface DetectionStatus {
  type: 'success' | 'error' | 'warning' | 'info'
  message: string
  confidence?: number
}

export interface Statistics {
  facesDetected: number
  confidenceSum: number
  processingTimes: number[]
  averageConfidence: number
  averageProcessingTime: number
}

export interface AttendanceData {
  employeeId: string
  timestamp: Date
  confidence: number
  liveness: number
  location?: GeolocationPosition
  faceEmbeddings: number[]
}

export type DetectionMethod = 'face-api' | 'mediapipe'

export interface FaceRecognitionProps {
  detectionMethod?: DetectionMethod
  employeeId?: string
  confidenceThreshold?: number
  livenessThreshold?: number
}

export interface FaceRecognitionEmits {
  'face-detected': [data: FaceDetectionResult]
  'attendance-processed': [data: AttendanceData]
  'simulate-attendance': []
  error: [error: { type: string; message: string }]
}
