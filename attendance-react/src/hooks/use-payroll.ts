import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  getPayrollPeriods,
  getPayrollPeriod,
  createPayrollPeriod,
  updatePayrollPeriod,
  deletePayrollPeriod,
  getPayrollEmployees,
  getPayrollEmployee,
  updatePayrollEmployee,
  calculatePayroll,
  approvePayroll,
  rejectPayroll,
  markPayrollPaid,
  cancelPayroll,
  getPayrollSummary,
  getPayrollStatistics,
  getPayrollConfig,
  updatePayrollConfig,
  type PayrollFilters,
  type PayrollEmployeeFilters,
} from '@/lib/api/payroll';
import type { PayrollCalculateFormData, PayrollConfig } from '@/types/payroll';
import { useNotificationStore } from '@/stores';

// Query keys
export const payrollKeys = {
  all: ['payroll'] as const,
  periods: () => [...payrollKeys.all, 'periods'] as const,
  periodList: (filters: PayrollFilters) => [...payrollKeys.periods(), 'list', filters] as const,
  periodDetail: (id: string) => [...payrollKeys.periods(), 'detail', id] as const,
  employees: (periodId: string) => [...payrollKeys.all, 'employees', periodId] as const,
  employeeList: (filters: PayrollEmployeeFilters) =>
    [...payrollKeys.employees(filters.period_id), 'list', filters] as const,
  employeeDetail: (periodId: string, employeeId: string) =>
    [...payrollKeys.employees(periodId), 'detail', employeeId] as const,
  summary: (periodId: string) => [...payrollKeys.all, 'summary', periodId] as const,
  statistics: () => [...payrollKeys.all, 'statistics'] as const,
  config: () => [...payrollKeys.all, 'config'] as const,
};

// Get payroll periods list
export function usePayrollPeriods(filters?: PayrollFilters) {
  return useQuery({
    queryKey: payrollKeys.periodList(filters || {}),
    queryFn: () => getPayrollPeriods(filters),
  });
}

// Get single payroll period
export function usePayrollPeriod(id: string) {
  return useQuery({
    queryKey: payrollKeys.periodDetail(id),
    queryFn: () => getPayrollPeriod(id),
    enabled: !!id,
  });
}

// Get employees for a payroll period
export function usePayrollEmployees(filters: PayrollEmployeeFilters) {
  return useQuery({
    queryKey: payrollKeys.employeeList(filters),
    queryFn: () => getPayrollEmployees(filters),
    enabled: !!filters.period_id,
  });
}

// Get single employee payroll
export function usePayrollEmployee(periodId: string, employeeId: string) {
  return useQuery({
    queryKey: payrollKeys.employeeDetail(periodId, employeeId),
    queryFn: () => getPayrollEmployee(periodId, employeeId),
    enabled: !!periodId && !!employeeId,
  });
}

// Get payroll summary
export function usePayrollSummary(periodId: string) {
  return useQuery({
    queryKey: payrollKeys.summary(periodId),
    queryFn: () => getPayrollSummary(periodId),
    enabled: !!periodId,
  });
}

// Get payroll statistics
export function usePayrollStatistics() {
  return useQuery({
    queryKey: payrollKeys.statistics(),
    queryFn: getPayrollStatistics,
  });
}

// Get payroll configuration
export function usePayrollConfig() {
  return useQuery({
    queryKey: payrollKeys.config(),
    queryFn: getPayrollConfig,
    staleTime: 1000 * 60 * 30, // Cache for 30 minutes
  });
}

// Create payroll period mutation
export function useCreatePayrollPeriod() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (data: {
      name: string;
      type: 'monthly' | 'weekly' | 'biweekly';
      start_date: string;
      end_date: string;
      pay_date: string;
    }) => createPayrollPeriod(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: payrollKeys.periods() });
      queryClient.invalidateQueries({ queryKey: payrollKeys.statistics() });
      success('Berhasil', 'Periode penggajian berhasil dibuat');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal membuat periode penggajian');
    },
  });
}

// Update payroll period mutation
export function useUpdatePayrollPeriod() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: ({
      id,
      data,
    }: {
      id: string;
      data: Partial<{ name: string; pay_date: string; notes: string }>;
    }) => updatePayrollPeriod(id, data),
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: payrollKeys.periodDetail(id) });
      queryClient.invalidateQueries({ queryKey: payrollKeys.periods() });
      success('Berhasil', 'Periode penggajian berhasil diperbarui');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal memperbarui periode penggajian');
    },
  });
}

// Delete payroll period mutation
export function useDeletePayrollPeriod() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (id: string) => deletePayrollPeriod(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: payrollKeys.periods() });
      queryClient.invalidateQueries({ queryKey: payrollKeys.statistics() });
      success('Berhasil', 'Periode penggajian berhasil dihapus');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal menghapus periode penggajian');
    },
  });
}

// Update employee payroll mutation
export function useUpdatePayrollEmployee() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: ({
      periodId,
      employeeId,
      data,
    }: {
      periodId: string;
      employeeId: string;
      data: Partial<{
        bonus: number;
        other_allowances: number;
        loan_deduction: number;
        other_deductions: number;
        notes: string;
      }>;
    }) => updatePayrollEmployee(periodId, employeeId, data),
    onSuccess: (_, { periodId, employeeId }) => {
      queryClient.invalidateQueries({ queryKey: payrollKeys.employeeDetail(periodId, employeeId) });
      queryClient.invalidateQueries({ queryKey: payrollKeys.employees(periodId) });
      queryClient.invalidateQueries({ queryKey: payrollKeys.summary(periodId) });
      success('Berhasil', 'Data gaji karyawan berhasil diperbarui');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal memperbarui data gaji karyawan');
    },
  });
}

// Calculate payroll mutation
export function useCalculatePayroll() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: PayrollCalculateFormData }) =>
      calculatePayroll(id, data),
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: payrollKeys.periodDetail(id) });
      queryClient.invalidateQueries({ queryKey: payrollKeys.periods() });
      queryClient.invalidateQueries({ queryKey: payrollKeys.employees(id) });
      queryClient.invalidateQueries({ queryKey: payrollKeys.summary(id) });
      success('Berhasil', 'Penggajian berhasil dihitung');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal menghitung penggajian');
    },
  });
}

// Approve payroll mutation
export function useApprovePayroll() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: ({ id, notes }: { id: string; notes?: string }) => approvePayroll(id, notes),
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: payrollKeys.periodDetail(id) });
      queryClient.invalidateQueries({ queryKey: payrollKeys.periods() });
      queryClient.invalidateQueries({ queryKey: payrollKeys.statistics() });
      success('Berhasil', 'Penggajian berhasil disetujui');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal menyetujui penggajian');
    },
  });
}

// Reject payroll mutation
export function useRejectPayroll() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: ({ id, reason }: { id: string; reason: string }) => rejectPayroll(id, reason),
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: payrollKeys.periodDetail(id) });
      queryClient.invalidateQueries({ queryKey: payrollKeys.periods() });
      success('Berhasil', 'Penggajian berhasil ditolak');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal menolak penggajian');
    },
  });
}

// Mark payroll as paid mutation
export function useMarkPayrollPaid() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: ({
      id,
      data,
    }: {
      id: string;
      data: { payment_date: string; payment_method: string; notes?: string };
    }) => markPayrollPaid(id, data),
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: payrollKeys.periodDetail(id) });
      queryClient.invalidateQueries({ queryKey: payrollKeys.periods() });
      queryClient.invalidateQueries({ queryKey: payrollKeys.employees(id) });
      queryClient.invalidateQueries({ queryKey: payrollKeys.statistics() });
      success('Berhasil', 'Penggajian berhasil ditandai sebagai dibayar');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal menandai penggajian sebagai dibayar');
    },
  });
}

// Cancel payroll mutation
export function useCancelPayroll() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: ({ id, reason }: { id: string; reason: string }) => cancelPayroll(id, reason),
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: payrollKeys.periodDetail(id) });
      queryClient.invalidateQueries({ queryKey: payrollKeys.periods() });
      queryClient.invalidateQueries({ queryKey: payrollKeys.statistics() });
      success('Berhasil', 'Penggajian berhasil dibatalkan');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal membatalkan penggajian');
    },
  });
}

// Update payroll config mutation
export function useUpdatePayrollConfig() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (data: Partial<PayrollConfig>) => updatePayrollConfig(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: payrollKeys.config() });
      success('Berhasil', 'Konfigurasi penggajian berhasil diperbarui');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal memperbarui konfigurasi penggajian');
    },
  });
}

// Legacy hook for backward compatibility with existing pages
export function usePayroll() {
  const currentYear = new Date().getFullYear();
  const currentMonth = new Date().getMonth() + 1;

  const periodsQuery = usePayrollPeriods({ year: currentYear });
  const statisticsQuery = usePayrollStatistics();

  // Get the current/latest period
  const periods = periodsQuery.data?.data ?? [];
  const currentPeriod = periods.find(
    (p) =>
      new Date(p.start_date).getMonth() + 1 === currentMonth &&
      new Date(p.start_date).getFullYear() === currentYear
  ) || periods[0];

  const employeesQuery = usePayrollEmployees({
    period_id: currentPeriod?.id ?? '',
  });

  const summaryQuery = usePayrollSummary(currentPeriod?.id ?? '');

  return {
    // Data
    payrollPeriods: periods,
    payrollEmployees: employeesQuery.data?.data ?? [],
    selectedPeriod: currentPeriod,
    summary: summaryQuery.data,
    statistics: statisticsQuery.data,

    // Loading states
    isLoading: periodsQuery.isLoading || statisticsQuery.isLoading,
    isLoadingEmployees: employeesQuery.isLoading,
    isLoadingSummary: summaryQuery.isLoading,

    // Errors
    error: periodsQuery.error?.message || statisticsQuery.error?.message || null,

    // Refetch functions
    fetchPayrollPeriods: periodsQuery.refetch,
    fetchPayrollEmployees: employeesQuery.refetch,
    refetchSummary: summaryQuery.refetch,

    // Clear error (no-op for React Query, handled automatically)
    clearError: () => {},
  };
}
