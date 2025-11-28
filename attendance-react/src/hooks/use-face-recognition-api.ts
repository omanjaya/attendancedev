import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  registerFace,
  verifyFace,
  updateFace,
  deleteFace,
  getFaceData,
  getRegisteredFaces,
  batchVerifyFaces,
  checkLiveness,
  getFaceStatistics,
  type RegisterFaceRequest,
  type VerifyFaceRequest,
  type UpdateFaceRequest,
  type LivenessCheckRequest,
} from '@/lib/api/face-recognition';
import { useNotificationStore } from '@/stores';

// Query keys for cache management
export const faceRecognitionKeys = {
  all: ['face-recognition'] as const,
  registeredFaces: () => [...faceRecognitionKeys.all, 'registered-faces'] as const,
  statistics: () => [...faceRecognitionKeys.all, 'statistics'] as const,
  faceData: (employeeId: string) => [...faceRecognitionKeys.all, 'face-data', employeeId] as const,
};

/**
 * Hook to fetch all registered faces for local matching
 * Cached for 5 minutes to reduce API calls
 */
export function useRegisteredFaces() {
  return useQuery({
    queryKey: faceRecognitionKeys.registeredFaces(),
    queryFn: getRegisteredFaces,
    staleTime: 5 * 60 * 1000, // 5 minutes
    gcTime: 10 * 60 * 1000, // 10 minutes (formerly cacheTime)
    refetchOnWindowFocus: false,
  });
}

/**
 * Hook to fetch face data for a specific employee
 */
export function useFaceData(employeeId: string) {
  return useQuery({
    queryKey: faceRecognitionKeys.faceData(employeeId),
    queryFn: () => getFaceData(employeeId),
    enabled: !!employeeId,
  });
}

/**
 * Hook to fetch face recognition statistics
 */
export function useFaceStatistics() {
  return useQuery({
    queryKey: faceRecognitionKeys.statistics(),
    queryFn: getFaceStatistics,
    staleTime: 60 * 1000, // 1 minute
  });
}

/**
 * Hook to register a new face
 */
export function useRegisterFace() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (data: RegisterFaceRequest) => registerFace(data),
    onSuccess: (_response, variables) => {
      // Invalidate queries to refresh data
      queryClient.invalidateQueries({ queryKey: faceRecognitionKeys.registeredFaces() });
      queryClient.invalidateQueries({ queryKey: faceRecognitionKeys.statistics() });
      queryClient.invalidateQueries({ queryKey: faceRecognitionKeys.faceData(variables.employee_id) });

      success('Berhasil', 'Wajah berhasil didaftarkan');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal mendaftarkan wajah');
    },
  });
}

/**
 * Hook to verify a face (1:N identification or 1:1 verification)
 */
export function useVerifyFace() {
  const { error } = useNotificationStore();

  return useMutation({
    mutationFn: (data: VerifyFaceRequest) => verifyFace(data),
    onError: (err: Error) => {
      error('Verifikasi Gagal', err.message || 'Gagal memverifikasi wajah');
    },
  });
}

/**
 * Hook to update existing face data
 */
export function useUpdateFace() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (data: UpdateFaceRequest) => updateFace(data),
    onSuccess: (_response, variables) => {
      queryClient.invalidateQueries({ queryKey: faceRecognitionKeys.registeredFaces() });
      queryClient.invalidateQueries({ queryKey: faceRecognitionKeys.faceData(variables.employee_id) });

      success('Berhasil', 'Data wajah berhasil diperbarui');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal memperbarui data wajah');
    },
  });
}

/**
 * Hook to delete face data
 */
export function useDeleteFace() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (employeeId: string) => deleteFace(employeeId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: faceRecognitionKeys.registeredFaces() });
      queryClient.invalidateQueries({ queryKey: faceRecognitionKeys.statistics() });

      success('Berhasil', 'Data wajah berhasil dihapus');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal menghapus data wajah');
    },
  });
}

/**
 * Hook to check liveness
 */
export function useCheckLiveness() {
  return useMutation({
    mutationFn: (data: LivenessCheckRequest) => checkLiveness(data),
  });
}

/**
 * Hook to batch verify multiple faces
 */
export function useBatchVerify() {
  return useMutation({
    mutationFn: (faces: Array<{ descriptor: number[]; confidence: number }>) =>
      batchVerifyFaces(faces),
  });
}

/**
 * Hook to manually refetch registered faces
 */
export function useRefreshRegisteredFaces() {
  const queryClient = useQueryClient();

  return () => {
    queryClient.invalidateQueries({ queryKey: faceRecognitionKeys.registeredFaces() });
  };
}

/**
 * Hook to prefetch registered faces (useful on app initialization)
 */
export function usePrefetchRegisteredFaces() {
  const queryClient = useQueryClient();

  return () => {
    queryClient.prefetchQuery({
      queryKey: faceRecognitionKeys.registeredFaces(),
      queryFn: getRegisteredFaces,
      staleTime: 5 * 60 * 1000,
    });
  };
}
