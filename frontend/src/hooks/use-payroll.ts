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
  // New A1 & A2 imports
  getPeriodEmployees,
  getEmployeePayrollDetail,
  updateEmployeePayrollItems,
  getPayrollItems,
  createPayrollItem,
  updatePayrollItem,
  deletePayrollItem,
  getPayrollItemCategories,
  type PayrollFilters,
  type PayrollEmployeeFilters,
  type PayrollItemInput,
  type PayrollItemUpdateInput,
} from '@/lib/api/payroll';
import {
  getPayrollFormulas,
  getPayrollFormula,
  createPayrollFormula,
  updatePayrollFormula,
  deletePayrollFormula,
  toggleFormulaStatus,
  previewFormula,
  getFormulaConfig,
  type PayrollFormulaInput,
  type FormulaType,
  type FormulaPreviewContext,
} from '@/lib/api/payroll-formulas';
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
  // New keys for A1 & A2
  periodEmployees: (periodId: string, filters?: { search?: string; department?: string }) =>
    [...payrollKeys.all, 'period-employees', periodId, filters] as const,
  employeePayrollDetail: (periodId: string, employeeId: string) =>
    [...payrollKeys.all, 'employee-payroll-detail', periodId, employeeId] as const,
  payrollItems: (payrollId: string) => [...payrollKeys.all, 'items', payrollId] as const,
  itemCategories: () => [...payrollKeys.all, 'item-categories'] as const,
  // Formula keys
  formulas: (filters?: { type?: FormulaType; is_active?: boolean; search?: string }) =>
    [...payrollKeys.all, 'formulas', filters] as const,
  formula: (id: string) => [...payrollKeys.all, 'formula', id] as const,
  formulaConfig: () => [...payrollKeys.all, 'formula-config'] as const,
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
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: ({
      id,
      data,
    }: {
      id: string;
      data: Partial<{ name: string; pay_date: string; notes: string }>;
    }) => updatePayrollPeriod(id, data),
    onSuccess: async (_, { id }) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: payrollKeys.periodDetail(id) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.periods() }),
      ]);
      success('Berhasil', 'Periode penggajian berhasil diperbarui');
    },
    // onError removed - global error handler will catch it
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
  const { success } = useNotificationStore();

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
    onSuccess: async (_, { periodId, employeeId }) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: payrollKeys.employeeDetail(periodId, employeeId) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.employees(periodId) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.summary(periodId) }),
      ]);
      success('Berhasil', 'Data gaji karyawan berhasil diperbarui');
    },
    // onError removed - global error handler will catch it
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
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: ({ id, notes }: { id: string; notes?: string }) => approvePayroll(id, notes),
    onSuccess: async (_, { id }) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: payrollKeys.periodDetail(id) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.periods() }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.statistics() }),
      ]);
      success('Berhasil', 'Penggajian berhasil disetujui');
    },
    // onError removed - global error handler will catch it
  });
}

// Reject payroll mutation
export function useRejectPayroll() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: ({ id, reason }: { id: string; reason: string }) => rejectPayroll(id, reason),
    onSuccess: async (_, { id }) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: payrollKeys.periodDetail(id) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.periods() }),
      ]);
      success('Berhasil', 'Penggajian berhasil ditolak');
    },
    // onError removed - global error handler will catch it
  });
}

// Mark payroll as paid mutation
export function useMarkPayrollPaid() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: ({
      id,
      data,
    }: {
      id: string;
      data: { payment_date: string; payment_method: string; notes?: string };
    }) => markPayrollPaid(id, data),
    onSuccess: async (_, { id }) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: payrollKeys.periodDetail(id) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.periods() }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.employees(id) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.statistics() }),
      ]);
      success('Berhasil', 'Penggajian berhasil ditandai sebagai dibayar');
    },
    // onError removed - global error handler will catch it
  });
}

// Cancel payroll mutation
export function useCancelPayroll() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: ({ id, reason }: { id: string; reason: string }) => cancelPayroll(id, reason),
    onSuccess: async (_, { id }) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: payrollKeys.periodDetail(id) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.periods() }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.statistics() }),
      ]);
      success('Berhasil', 'Penggajian berhasil dibatalkan');
    },
    // onError removed - global error handler will catch it
  });
}

// Update payroll config mutation
export function useUpdatePayrollConfig() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: (data: Partial<PayrollConfig>) => updatePayrollConfig(data),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: payrollKeys.config() });
      success('Berhasil', 'Konfigurasi penggajian berhasil diperbarui');
    },
    // onError removed - global error handler will catch it
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

// ============================================
// New hooks for A1 & A2: Payroll Item Management
// ============================================

// Get all employees with their payroll for a specific period
export function usePeriodEmployees(
  periodId: string,
  filters?: { search?: string; department?: string }
) {
  return useQuery({
    queryKey: payrollKeys.periodEmployees(periodId, filters),
    queryFn: () => getPeriodEmployees(periodId, filters),
    enabled: !!periodId,
  });
}

// Get detailed employee payroll (with earnings, deductions, bonuses breakdown)
export function useEmployeePayrollDetail(periodId: string, employeeId: string) {
  return useQuery({
    queryKey: payrollKeys.employeePayrollDetail(periodId, employeeId),
    queryFn: () => getEmployeePayrollDetail(periodId, employeeId),
    enabled: !!periodId && !!employeeId,
  });
}

// Get payroll items for a specific payroll
export function usePayrollItems(payrollId: string) {
  return useQuery({
    queryKey: payrollKeys.payrollItems(payrollId),
    queryFn: () => getPayrollItems(payrollId),
    enabled: !!payrollId,
  });
}

// Get item categories
export function usePayrollItemCategories() {
  return useQuery({
    queryKey: payrollKeys.itemCategories(),
    queryFn: getPayrollItemCategories,
    staleTime: 1000 * 60 * 60, // Cache for 1 hour
  });
}

// Update employee payroll with items (bulk update)
export function useUpdateEmployeePayrollItems() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: ({
      periodId,
      employeeId,
      data,
    }: {
      periodId: string;
      employeeId: string;
      data: {
        notes?: string;
        items?: PayrollItemUpdateInput[];
      };
    }) => updateEmployeePayrollItems(periodId, employeeId, data),
    onSuccess: async (_, { periodId, employeeId }) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: payrollKeys.employeePayrollDetail(periodId, employeeId) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.periodEmployees(periodId, undefined) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.employees(periodId) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.summary(periodId) }),
      ]);
      success('Berhasil', 'Data gaji karyawan berhasil diperbarui');
    },
  });
}

// Create payroll item
export function useCreatePayrollItem() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: ({
      payrollId,
      data,
    }: {
      payrollId: string;
      periodId: string;
      employeeId: string;
      data: PayrollItemInput;
    }) => createPayrollItem(payrollId, data),
    onSuccess: async (_, { payrollId, periodId, employeeId }) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: payrollKeys.payrollItems(payrollId) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.employeePayrollDetail(periodId, employeeId) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.periodEmployees(periodId, undefined) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.summary(periodId) }),
      ]);
      success('Berhasil', 'Item gaji berhasil ditambahkan');
    },
  });
}

// Update payroll item
export function useUpdatePayrollItem() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: ({
      payrollId,
      itemId,
      data,
    }: {
      payrollId: string;
      itemId: string;
      periodId: string;
      employeeId: string;
      data: Partial<PayrollItemInput>;
    }) => updatePayrollItem(payrollId, itemId, data),
    onSuccess: async (_, { payrollId, periodId, employeeId }) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: payrollKeys.payrollItems(payrollId) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.employeePayrollDetail(periodId, employeeId) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.periodEmployees(periodId, undefined) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.summary(periodId) }),
      ]);
      success('Berhasil', 'Item gaji berhasil diperbarui');
    },
  });
}

// Delete payroll item
export function useDeletePayrollItem() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: ({
      payrollId,
      itemId,
    }: {
      payrollId: string;
      itemId: string;
      periodId: string;
      employeeId: string;
    }) => deletePayrollItem(payrollId, itemId),
    onSuccess: async (_, { payrollId, periodId, employeeId }) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: payrollKeys.payrollItems(payrollId) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.employeePayrollDetail(periodId, employeeId) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.periodEmployees(periodId, undefined) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.summary(periodId) }),
      ]);
      success('Berhasil', 'Item gaji berhasil dihapus');
    },
  });
}

// ============================================
// Formula Hooks (A3 & A5)
// ============================================

// Get all formulas
export function usePayrollFormulas(filters?: { type?: FormulaType; is_active?: boolean; search?: string }) {
  return useQuery({
    queryKey: payrollKeys.formulas(filters),
    queryFn: () => getPayrollFormulas(filters),
  });
}

// Get single formula
export function usePayrollFormula(id: string) {
  return useQuery({
    queryKey: payrollKeys.formula(id),
    queryFn: () => getPayrollFormula(id),
    enabled: !!id,
  });
}

// Get formula config
export function useFormulaConfig() {
  return useQuery({
    queryKey: payrollKeys.formulaConfig(),
    queryFn: getFormulaConfig,
    staleTime: 1000 * 60 * 60, // Cache for 1 hour
  });
}

// Create formula
export function useCreateFormula() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: (data: PayrollFormulaInput) => createPayrollFormula(data),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: payrollKeys.formulas(undefined) });
      success('Berhasil', 'Formula berhasil dibuat');
    },
  });
}

// Update formula
export function useUpdateFormula() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<PayrollFormulaInput> }) =>
      updatePayrollFormula(id, data),
    onSuccess: async (_, { id }) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: payrollKeys.formula(id) }),
        queryClient.invalidateQueries({ queryKey: payrollKeys.formulas(undefined) }),
      ]);
      success('Berhasil', 'Formula berhasil diperbarui');
    },
  });
}

// Delete formula
export function useDeleteFormula() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: (id: string) => deletePayrollFormula(id),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: payrollKeys.formulas(undefined) });
      success('Berhasil', 'Formula berhasil dihapus');
    },
  });
}

// Toggle formula status
export function useToggleFormulaStatus() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: (id: string) => toggleFormulaStatus(id),
    onSuccess: async (result) => {
      await queryClient.invalidateQueries({ queryKey: payrollKeys.formulas(undefined) });
      success('Berhasil', result.is_active ? 'Formula diaktifkan' : 'Formula dinonaktifkan');
    },
  });
}

// Preview formula
export function usePreviewFormula() {
  return useMutation({
    mutationFn: ({ id, context }: { id: string; context?: FormulaPreviewContext }) =>
      previewFormula(id, context),
  });
}
