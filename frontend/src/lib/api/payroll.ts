import apiClient from './client';
import type {
  PayrollPeriod,
  PayrollEmployee,
  PayrollSummary,
  PayrollStatistics,
  PayrollConfig,
  PayrollStatus,
  PayrollCalculateFormData,
} from '@/types/payroll';
import type { PaginatedResponse } from '@/types';

export interface PayrollFilters {
  status?: PayrollStatus;
  year?: number;
  month?: number;
  page?: number;
  per_page?: number;
}

export interface PayrollEmployeeFilters {
  period_id: string;
  department?: string;
  status?: PayrollStatus;
  search?: string;
  page?: number;
  per_page?: number;
}

const ENDPOINTS = {
  periods: '/payroll/periods',
  periodDetail: (id: string) => `/payroll/periods/${id}`,
  periodEmployees: (id: string) => `/payroll/periods/${id}/employees`,
  employeePayroll: (periodId: string, employeeId: string) =>
    `/payroll/periods/${periodId}/employees/${employeeId}`,
  calculate: (id: string) => `/payroll/periods/${id}/calculate`,
  approve: (id: string) => `/payroll/periods/${id}/approve`,
  reject: (id: string) => `/payroll/periods/${id}/reject`,
  markPaid: (id: string) => `/payroll/periods/${id}/mark-paid`,
  cancel: (id: string) => `/payroll/periods/${id}/cancel`,
  summary: (id: string) => `/payroll/periods/${id}/summary`,
  statistics: '/payroll/statistics',
  config: '/payroll/config',
  exportPayslips: (id: string) => `/payroll/periods/${id}/export`,
  generatePayslip: (periodId: string, employeeId: string) =>
    `/payroll/periods/${periodId}/employees/${employeeId}/payslip`,
} as const;

// Get all payroll periods with pagination and filters
export async function getPayrollPeriods(
  filters?: PayrollFilters
): Promise<PaginatedResponse<PayrollPeriod>> {
  const response = await apiClient.get<PaginatedResponse<PayrollPeriod>>(ENDPOINTS.periods, {
    params: filters,
  });
  return response.data;
}

// Get single payroll period by ID
export async function getPayrollPeriod(id: string): Promise<PayrollPeriod> {
  const response = await apiClient.get<{ data: PayrollPeriod }>(ENDPOINTS.periodDetail(id));
  return response.data.data;
}

// Create new payroll period
export async function createPayrollPeriod(data: {
  name: string;
  type: 'monthly' | 'weekly' | 'biweekly';
  start_date: string;
  end_date: string;
  pay_date: string;
}): Promise<PayrollPeriod> {
  const response = await apiClient.post<{ data: PayrollPeriod }>(ENDPOINTS.periods, data);
  return response.data.data;
}

// Update payroll period
export async function updatePayrollPeriod(
  id: string,
  data: Partial<{
    name: string;
    pay_date: string;
    notes: string;
  }>
): Promise<PayrollPeriod> {
  const response = await apiClient.put<{ data: PayrollPeriod }>(ENDPOINTS.periodDetail(id), data);
  return response.data.data;
}

// Delete payroll period (only if status is draft)
export async function deletePayrollPeriod(id: string): Promise<void> {
  await apiClient.delete(ENDPOINTS.periodDetail(id));
}

// Get employees for a payroll period
export async function getPayrollEmployees(
  filters: PayrollEmployeeFilters
): Promise<PaginatedResponse<PayrollEmployee>> {
  const { period_id, ...params } = filters;
  const response = await apiClient.get<PaginatedResponse<PayrollEmployee>>(
    ENDPOINTS.periodEmployees(period_id),
    { params }
  );
  return response.data;
}

// Get single employee payroll details
export async function getPayrollEmployee(
  periodId: string,
  employeeId: string
): Promise<PayrollEmployee> {
  const response = await apiClient.get<{ data: PayrollEmployee }>(
    ENDPOINTS.employeePayroll(periodId, employeeId)
  );
  return response.data.data;
}

// Update employee payroll (adjustments)
export async function updatePayrollEmployee(
  periodId: string,
  employeeId: string,
  data: Partial<{
    bonus: number;
    other_allowances: number;
    loan_deduction: number;
    other_deductions: number;
    notes: string;
  }>
): Promise<PayrollEmployee> {
  const response = await apiClient.put<{ data: PayrollEmployee }>(
    ENDPOINTS.employeePayroll(periodId, employeeId),
    data
  );
  return response.data.data;
}

// Calculate payroll for a period
export async function calculatePayroll(
  id: string,
  data: PayrollCalculateFormData
): Promise<PayrollPeriod> {
  const response = await apiClient.post<{ data: PayrollPeriod }>(ENDPOINTS.calculate(id), data);
  return response.data.data;
}

// Approve payroll period
export async function approvePayroll(id: string, notes?: string): Promise<PayrollPeriod> {
  const response = await apiClient.post<{ data: PayrollPeriod }>(ENDPOINTS.approve(id), { notes });
  return response.data.data;
}

// Reject payroll period
export async function rejectPayroll(id: string, reason: string): Promise<PayrollPeriod> {
  const response = await apiClient.post<{ data: PayrollPeriod }>(ENDPOINTS.reject(id), { reason });
  return response.data.data;
}

// Mark payroll as paid
export async function markPayrollPaid(
  id: string,
  data: { payment_date: string; payment_method: string; notes?: string }
): Promise<PayrollPeriod> {
  const response = await apiClient.post<{ data: PayrollPeriod }>(ENDPOINTS.markPaid(id), data);
  return response.data.data;
}

// Cancel payroll period
export async function cancelPayroll(id: string, reason: string): Promise<PayrollPeriod> {
  const response = await apiClient.post<{ data: PayrollPeriod }>(ENDPOINTS.cancel(id), { reason });
  return response.data.data;
}

// Get payroll summary for a period
export async function getPayrollSummary(periodId: string): Promise<PayrollSummary> {
  const response = await apiClient.get<{ data: PayrollSummary }>(ENDPOINTS.summary(periodId));
  return response.data.data;
}

// Get payroll statistics
export async function getPayrollStatistics(): Promise<PayrollStatistics> {
  const response = await apiClient.get<{ data: PayrollStatistics }>(ENDPOINTS.statistics);
  return response.data.data;
}

// Get payroll configuration
export async function getPayrollConfig(): Promise<PayrollConfig> {
  const response = await apiClient.get<{ data: PayrollConfig }>(ENDPOINTS.config);
  return response.data.data;
}

// Update payroll configuration
export async function updatePayrollConfig(data: Partial<PayrollConfig>): Promise<PayrollConfig> {
  const response = await apiClient.put<{ data: PayrollConfig }>(ENDPOINTS.config, data);
  return response.data.data;
}

// Export payslips (returns blob URL)
export async function exportPayslips(
  periodId: string,
  format: 'pdf' | 'excel' = 'pdf'
): Promise<Blob> {
  const response = await apiClient.get(ENDPOINTS.exportPayslips(periodId), {
    params: { format },
    responseType: 'blob',
  });
  return response.data;
}

// Generate single payslip
export async function generatePayslip(periodId: string, employeeId: string): Promise<Blob> {
  const response = await apiClient.get(ENDPOINTS.generatePayslip(periodId, employeeId), {
    responseType: 'blob',
  });
  return response.data;
}

// Employee payroll types
export interface EmployeePayrollItem {
  id: number;
  month: string;
  year: number;
  period_start: string;
  period_end: string;
  pay_date: string | null;
  gross_salary: number;
  total_deductions: number;
  total_bonuses: number;
  net_salary: number;
  status: 'draft' | 'calculated' | 'approved' | 'paid' | 'cancelled';
  approved_at: string | null;
  processed_at: string | null;
}

// Get employee's own payroll history
export async function getMyPayroll(year?: number): Promise<EmployeePayrollItem[]> {
  const response = await apiClient.get<{ data: EmployeePayrollItem[] }>('/payroll/employee', {
    params: { year },
  });
  return response.data.data;
}

// Download employee's payslip
export async function downloadMyPayslip(payrollId: number): Promise<Blob> {
  const response = await apiClient.get(`/payroll/${payrollId}/download`, {
    responseType: 'blob',
  });
  return response.data;
}
