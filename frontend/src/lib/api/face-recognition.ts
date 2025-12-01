import apiClient from './client';
import type { KnownFaceDescriptor } from '@/types/face-recognition';

// API Response Types
export interface FaceRegistrationResponse {
  success: boolean;
  message: string;
  data: {
    employee_id: string;
    confidence: number;
    quality_score: number;
    image_stored: boolean;
  };
}

export interface FaceVerificationResponse {
  success: boolean;
  message: string;
  data: {
    success: boolean;
    employee?: {
      id: string;
      name: string;
      employee_id: string;
      employee_type: string;
      department?: string;
      photo_url?: string;
    };
    confidence: number;
    liveness_score?: number;
    quality_score?: number;
    timestamp: string;
  };
}

export interface FaceDataResponse {
  success: boolean;
  data: {
    employee_id: string;
    has_face_data: boolean;
    descriptor?: number[];
    confidence?: number;
    quality_score?: number;
    registered_at?: string;
    updated_at?: string;
  };
}

export interface RegisteredFacesResponse {
  success: boolean;
  data: Array<{
    employee_id: string;
    name: string;
    descriptor: number[];
    photo_url?: string;
  }>;
}

export interface LivenessCheckResponse {
  success: boolean;
  data: {
    is_live: boolean;
    score: number;
    checks_performed: string[];
  };
}

export interface FaceStatisticsResponse {
  success: boolean;
  data: {
    total_registered: number;
    total_verifications: number;
    successful_verifications: number;
    average_confidence: number;
    registrations_today: number;
    verifications_today: number;
  };
}

// API Request Types
export interface RegisterFaceRequest {
  employee_id: string;
  descriptor: number[];
  confidence: number;
  algorithm?: string;
  model_version?: string;
  image?: string; // base64 encoded
  device_info?: Record<string, string>;
}

export interface VerifyFaceRequest {
  descriptor: number[];
  confidence: number;
  employee_id?: string; // Optional: for 1:1 verification
  threshold?: number;
  require_liveness?: boolean;
  liveness_data?: {
    blink_detected: boolean;
    head_movement: number;
    expressions?: number[];
    texture_score?: number;
  };
}

export interface UpdateFaceRequest {
  employee_id: string;
  descriptor: number[];
  confidence: number;
  image?: string;
}

export interface LivenessCheckRequest {
  blink_detected: boolean;
  head_movement: number;
  expressions?: Record<string, number>;
  texture_score?: number;
}

// API Endpoints
const ENDPOINTS = {
  register: '/face-recognition/register',
  verify: '/face-recognition/verify',
  update: '/face-recognition/update',
  delete: '/face-recognition/delete',
  getData: '/face-recognition/get-data',
  batchVerify: '/face-recognition/batch-verify',
  checkLiveness: '/face-recognition/check-liveness',
  statistics: '/face-recognition/statistics',
  // Additional endpoints for fetching all registered faces
  registeredFaces: '/employees/with-face-data',
  // Server-side processing endpoints (Python service proxy)
  extractEncodingServer: '/face-recognition/extract-encoding-server',
  verifyServer: '/face-recognition/verify-server',
} as const;

/**
 * Register a new face for an employee
 */
export async function registerFace(
  data: RegisterFaceRequest
): Promise<FaceRegistrationResponse> {
  const response = await apiClient.post<FaceRegistrationResponse>(
    ENDPOINTS.register,
    {
      employee_id: data.employee_id,
      descriptor: data.descriptor,
      confidence: data.confidence,
      algorithm: data.algorithm || 'face-api.js',
      model_version: data.model_version || '1.0',
      image: data.image,
      device_info: data.device_info || {
        browser: navigator.userAgent,
        platform: navigator.platform,
      },
    }
  );
  return response.data;
}

/**
 * Verify a face against registered employees (1:N identification)
 * If employee_id is provided, performs 1:1 verification
 */
export async function verifyFace(
  data: VerifyFaceRequest
): Promise<FaceVerificationResponse> {
  const response = await apiClient.post<FaceVerificationResponse>(
    ENDPOINTS.verify,
    {
      descriptor: data.descriptor,
      confidence: data.confidence,
      employee_id: data.employee_id,
      threshold: data.threshold || 0.6,
      require_liveness: data.require_liveness || false,
      liveness_data: data.liveness_data,
    }
  );
  return response.data;
}

/**
 * Update existing face data for an employee
 */
export async function updateFace(
  data: UpdateFaceRequest
): Promise<FaceRegistrationResponse> {
  const response = await apiClient.post<FaceRegistrationResponse>(
    ENDPOINTS.update,
    {
      employee_id: data.employee_id,
      descriptor: data.descriptor,
      confidence: data.confidence,
      image: data.image,
    }
  );
  return response.data;
}

/**
 * Delete face data for an employee
 */
export async function deleteFace(employeeId: string): Promise<{ success: boolean; message: string }> {
  const response = await apiClient.post<{ success: boolean; message: string }>(
    ENDPOINTS.delete,
    { employee_id: employeeId }
  );
  return response.data;
}

/**
 * Get face data for a specific employee
 */
export async function getFaceData(employeeId: string): Promise<FaceDataResponse> {
  const response = await apiClient.post<FaceDataResponse>(
    ENDPOINTS.getData,
    { employee_id: employeeId }
  );
  return response.data;
}

/**
 * Get all registered faces for local matching
 */
export async function getRegisteredFaces(): Promise<KnownFaceDescriptor[]> {
  try {
    // Try the dedicated endpoint first
    const response = await apiClient.get<RegisteredFacesResponse>(
      ENDPOINTS.registeredFaces
    );

    if (response.data.success && response.data.data) {
      return response.data.data.map((face) => ({
        employeeId: face.employee_id,
        name: face.name,
        descriptor: face.descriptor,
      }));
    }
    return [];
  } catch {
    // Fallback: fetch from statistics or return empty
    console.warn('Failed to fetch registered faces, using fallback');
    return [];
  }
}

/**
 * Batch verify multiple faces
 */
export async function batchVerifyFaces(
  faces: Array<{ descriptor: number[]; confidence: number }>
): Promise<FaceVerificationResponse[]> {
  const response = await apiClient.post<{ success: boolean; data: FaceVerificationResponse[] }>(
    ENDPOINTS.batchVerify,
    { faces }
  );
  return response.data.data;
}

/**
 * Check liveness of face data
 */
export async function checkLiveness(
  data: LivenessCheckRequest
): Promise<LivenessCheckResponse> {
  const response = await apiClient.post<LivenessCheckResponse>(
    ENDPOINTS.checkLiveness,
    data
  );
  return response.data;
}

/**
 * Get face recognition statistics
 */
export async function getFaceStatistics(): Promise<FaceStatisticsResponse> {
  const response = await apiClient.get<FaceStatisticsResponse>(
    ENDPOINTS.statistics
  );
  return response.data;
}

// Helper function to convert Float32Array to number[] for API
export function descriptorToArray(descriptor: Float32Array): number[] {
  return Array.from(descriptor);
}

// Helper function to convert number[] back to Float32Array for matching
export function arrayToDescriptor(array: number[]): Float32Array {
  return new Float32Array(array);
}

// Calculate cosine similarity between two descriptors (for local matching)
export function calculateSimilarity(
  descriptor1: number[] | Float32Array,
  descriptor2: number[] | Float32Array
): number {
  const arr1 = descriptor1 instanceof Float32Array ? Array.from(descriptor1) : descriptor1;
  const arr2 = descriptor2 instanceof Float32Array ? Array.from(descriptor2) : descriptor2;

  if (arr1.length !== arr2.length || arr1.length !== 128) {
    return 0;
  }

  let dotProduct = 0;
  let norm1 = 0;
  let norm2 = 0;

  for (let i = 0; i < 128; i++) {
    dotProduct += arr1[i] * arr2[i];
    norm1 += arr1[i] * arr1[i];
    norm2 += arr2[i] * arr2[i];
  }

  const magnitude = Math.sqrt(norm1) * Math.sqrt(norm2);
  return magnitude === 0 ? 0 : dotProduct / magnitude;
}

// Find best match from registered faces
export function findBestMatch(
  descriptor: number[] | Float32Array,
  registeredFaces: KnownFaceDescriptor[],
  threshold: number = 0.6
): { match: KnownFaceDescriptor | null; similarity: number } {
  let bestMatch: KnownFaceDescriptor | null = null;
  let bestSimilarity = 0;

  for (const face of registeredFaces) {
    const faceDescriptor = face.descriptor instanceof Float32Array
      ? face.descriptor
      : new Float32Array(face.descriptor);

    const similarity = calculateSimilarity(descriptor, faceDescriptor);

    if (similarity > bestSimilarity && similarity >= threshold) {
      bestSimilarity = similarity;
      bestMatch = face;
    }
  }

  return { match: bestMatch, similarity: bestSimilarity };
}

/* ============================================================================
 * Server-Side Processing (Python Service Proxy)
 * ============================================================================
 * These functions use full server-side face recognition processing via
 * Python service (dlib-based). Images are uploaded as compressed base64.
 */

export interface ServerExtractEncodingRequest {
  /** Base64 encoded image (with or without data URL prefix) */
  image: string;
}

export interface ServerExtractEncodingResponse {
  success: boolean;
  message: string;
  data?: {
    /** 128-d face encoding from dlib */
    encoding: number[];
    /** Confidence score (0-1) */
    confidence: number;
    /** Algorithm used */
    algorithm: string;
    /** Model version */
    model_version: string;
  };
}

export interface ServerVerifyFaceRequest {
  /** Base64 encoded image (with or without data URL prefix) */
  image: string;
  /** Face matching tolerance (0.3-0.9, default: 0.6) */
  tolerance?: number;
}

export interface ServerVerifyFaceResponse {
  success: boolean;
  matched: boolean;
  message: string;
  data?: {
    /** Matched employee info (if matched) */
    employee?: {
      employee_id: number;
      employee_code: string;
      name: string;
    };
    /** Euclidean distance (lower = better match) */
    distance?: number;
    /** Similarity percentage (0-1) */
    similarity?: number;
    /** Detection confidence */
    confidence: number;
    /** Tolerance used for matching */
    tolerance_used: number;
    /** Algorithm used */
    algorithm: string;
  };
}

/**
 * Extract face encoding using server-side processing (Python service)
 *
 * Uploads image to Laravel proxy which forwards to Python face recognition
 * service. The service extracts a 128-d face descriptor using dlib.
 *
 * @param data - Request with base64 image
 * @returns Extracted encoding and metadata
 */
export async function extractEncodingServer(
  data: ServerExtractEncodingRequest
): Promise<ServerExtractEncodingResponse> {
  const response = await apiClient.post<ServerExtractEncodingResponse>(
    ENDPOINTS.extractEncodingServer,
    { image: data.image }
  );
  return response.data;
}

/**
 * Verify face using server-side processing (Python service)
 *
 * Uploads image to Laravel proxy which forwards to Python face recognition
 * service. The service:
 * 1. Extracts face encoding from uploaded image
 * 2. Compares against all registered employee encodings
 * 3. Returns matched employee or no match
 *
 * @param data - Request with base64 image and optional tolerance
 * @returns Verification result with matched employee (if found)
 */
export async function verifyFaceServer(
  data: ServerVerifyFaceRequest
): Promise<ServerVerifyFaceResponse> {
  const response = await apiClient.post<ServerVerifyFaceResponse>(
    ENDPOINTS.verifyServer,
    {
      image: data.image,
      tolerance: data.tolerance || 0.6,
    }
  );
  return response.data;
}
