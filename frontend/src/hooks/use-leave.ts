import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  getLeaveRequests,
  getLeaveRequest,
  createLeaveRequest,
  cancelLeaveRequest,
  approveLeaveRequest,
  rejectLeaveRequest,
  getLeaveBalance,
  getLeaveBalanceByEmployee,
  getLeaveStatistics,
  getPendingApprovals,
  getAffectedSchedules,
  previewWorkingDays,
  type LeaveFilters,
} from '@/lib/api/leave';
import type { LeaveRequestFormData } from '@/types/leave';
import { useNotificationStore } from '@/stores';

// Query keys
export const leaveKeys = {
  all: ['leave'] as const,
  lists: () => [...leaveKeys.all, 'list'] as const,
  list: (filters: LeaveFilters) => [...leaveKeys.lists(), filters] as const,
  details: () => [...leaveKeys.all, 'detail'] as const,
  detail: (id: string) => [...leaveKeys.details(), id] as const,
  balance: () => [...leaveKeys.all, 'balance'] as const,
  balanceByEmployee: (employeeId: string) => [...leaveKeys.balance(), employeeId] as const,
  statistics: () => [...leaveKeys.all, 'statistics'] as const,
  pending: () => [...leaveKeys.all, 'pending'] as const,
  affectedSchedules: (id: string) => [...leaveKeys.all, 'affected-schedules', id] as const,
  workingDaysPreview: (startDate: string, endDate: string) =>
    [...leaveKeys.all, 'working-days-preview', startDate, endDate] as const,
};

// Get leave requests list
export function useLeaveRequests(filters?: LeaveFilters) {
  return useQuery({
    queryKey: leaveKeys.list(filters || {}),
    queryFn: () => getLeaveRequests(filters),
  });
}

// Get single leave request
export function useLeaveRequest(id: string) {
  return useQuery({
    queryKey: leaveKeys.detail(id),
    queryFn: () => getLeaveRequest(id),
    enabled: !!id,
  });
}

// Get leave balance for current user
export function useLeaveBalance() {
  return useQuery({
    queryKey: leaveKeys.balance(),
    queryFn: getLeaveBalance,
  });
}

// Get leave balance for specific employee
export function useLeaveBalanceByEmployee(employeeId: string) {
  return useQuery({
    queryKey: leaveKeys.balanceByEmployee(employeeId),
    queryFn: () => getLeaveBalanceByEmployee(employeeId),
    enabled: !!employeeId,
  });
}

// Get leave statistics
export function useLeaveStatistics() {
  return useQuery({
    queryKey: leaveKeys.statistics(),
    queryFn: getLeaveStatistics,
  });
}

// Get pending approvals
export function usePendingApprovals() {
  return useQuery({
    queryKey: leaveKeys.pending(),
    queryFn: getPendingApprovals,
  });
}

// Create leave request mutation
export function useCreateLeaveRequest() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (data: LeaveRequestFormData) => createLeaveRequest(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: leaveKeys.lists() });
      queryClient.invalidateQueries({ queryKey: leaveKeys.balance() });
      queryClient.invalidateQueries({ queryKey: leaveKeys.statistics() });
      success('Berhasil', 'Pengajuan cuti berhasil dibuat');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal membuat pengajuan cuti');
    },
  });
}

// Cancel leave request mutation
export function useCancelLeaveRequest() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: (id: string) => cancelLeaveRequest(id),
    onSuccess: async (_, id) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: leaveKeys.lists() }),
        queryClient.invalidateQueries({ queryKey: leaveKeys.detail(id) }),
        queryClient.invalidateQueries({ queryKey: leaveKeys.balance() }),
        queryClient.invalidateQueries({ queryKey: leaveKeys.statistics() }),
      ]);
      success('Berhasil', 'Pengajuan cuti berhasil dibatalkan');
    },
    // onError removed - global error handler will catch it
  });
}

// Approve leave request mutation
export function useApproveLeaveRequest() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: ({ id, notes }: { id: string; notes?: string }) => approveLeaveRequest(id, notes),
    onSuccess: async (_, { id }) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: leaveKeys.lists() }),
        queryClient.invalidateQueries({ queryKey: leaveKeys.detail(id) }),
        queryClient.invalidateQueries({ queryKey: leaveKeys.pending() }),
        queryClient.invalidateQueries({ queryKey: leaveKeys.statistics() }),
      ]);
      success('Berhasil', 'Pengajuan cuti berhasil disetujui');
    },
    // onError removed - global error handler will catch it
  });
}

// Reject leave request mutation
export function useRejectLeaveRequest() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: ({ id, reason }: { id: string; reason: string }) => rejectLeaveRequest(id, reason),
    onSuccess: async (_, { id }) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: leaveKeys.lists() }),
        queryClient.invalidateQueries({ queryKey: leaveKeys.detail(id) }),
        queryClient.invalidateQueries({ queryKey: leaveKeys.pending() }),
        queryClient.invalidateQueries({ queryKey: leaveKeys.statistics() }),
      ]);
      success('Berhasil', 'Pengajuan cuti berhasil ditolak');
    },
    // onError removed - global error handler will catch it
  });
}

// Get affected teaching schedules for a leave request
export function useAffectedSchedules(id: string, enabled = true) {
  return useQuery({
    queryKey: leaveKeys.affectedSchedules(id),
    queryFn: () => getAffectedSchedules(id),
    enabled: !!id && enabled,
  });
}

// Preview working days calculation
export function useWorkingDaysPreview(startDate: string, endDate: string) {
  return useQuery({
    queryKey: leaveKeys.workingDaysPreview(startDate, endDate),
    queryFn: () => previewWorkingDays(startDate, endDate),
    enabled: !!startDate && !!endDate && startDate <= endDate,
  });
}
