import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
    getAttendanceCorrections,
    getCorrectionStatistics,
    getAttendanceCorrection,
    createAttendanceCorrection,
    cancelAttendanceCorrection,
    approveAttendanceCorrection,
    rejectAttendanceCorrection,
    type CorrectionFilters,
    type CreateCorrectionData,
} from '@/lib/api/attendance-corrections';

const QUERY_KEYS = {
    corrections: 'attendance-corrections',
    correction: 'attendance-correction',
    statistics: 'correction-statistics',
};

/**
 * Hook to fetch list of attendance corrections
 */
export function useAttendanceCorrections(filters: CorrectionFilters = {}) {
    return useQuery({
        queryKey: [QUERY_KEYS.corrections, filters],
        queryFn: () => getAttendanceCorrections(filters),
    });
}

/**
 * Hook to fetch correction statistics (admin only)
 */
export function useCorrectionStatistics() {
    return useQuery({
        queryKey: [QUERY_KEYS.statistics],
        queryFn: () => getCorrectionStatistics(),
    });
}

/**
 * Hook to fetch a single correction
 */
export function useAttendanceCorrection(id: string) {
    return useQuery({
        queryKey: [QUERY_KEYS.correction, id],
        queryFn: () => getAttendanceCorrection(id),
        enabled: !!id,
    });
}

/**
 * Hook to create a new correction request
 */
export function useCreateCorrection() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: CreateCorrectionData) => createAttendanceCorrection(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.corrections] });
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.statistics] });
        },
    });
}

/**
 * Hook to cancel a correction request
 */
export function useCancelCorrection() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (id: string) => cancelAttendanceCorrection(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.corrections] });
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.statistics] });
        },
    });
}

/**
 * Hook to approve a correction request (admin only)
 */
export function useApproveCorrection() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ id, notes }: { id: string; notes?: string }) =>
            approveAttendanceCorrection(id, notes),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.corrections] });
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.statistics] });
        },
    });
}

/**
 * Hook to reject a correction request (admin only)
 */
export function useRejectCorrection() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ id, notes }: { id: string; notes: string }) =>
            rejectAttendanceCorrection(id, notes),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.corrections] });
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.statistics] });
        },
    });
}
