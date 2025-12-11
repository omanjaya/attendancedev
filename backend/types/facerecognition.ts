export type FaceVerificationLog = {
  id: string;
  employee: {
    // Employee info
    full_name: string;
    employee_id: string;
    email: string;
    photo?: string;
  };
  timestamp: string;
  action: string;
  user_agent: string;
  ip_address?: string;
  status: string;
  metadata?: object;
}

export interface FaceVerificationHistory {
    employee: FaceVerificationLog;
  timestamp: string;
}

export interface FaceImageSettings {
  model: string;
  minSize: number;
  maxSize: number;
  settings: object;
}

export interface FaceImage {
  id: string;
  image: string;
  confidence: number;
  isLivenessCheck: boolean;
  settings: FaceImageSettings;
  face_encodings?: number[];
  metadata?: object;
}

export interface FaceDetectionResult {
  success: boolean;
  error?: string;
  message?: string;
  data?: {
    encoding: string;
    filename: string;
    device: string;
    timestamp: string;
    confidence: number;
    distance: number;
    distance_threshold: number;
    isLive: boolean;
  };
}
