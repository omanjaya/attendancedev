import apiClient from './client';
import type {
  LeaveRequest,
  LeaveBalance,
  LeaveStatistics,
  LeaveRequestFormData,
  LeaveStatus,
  LeaveType,
  PaginatedResponse,
} from '@/types';

export interface LeaveFilters {
  status?: LeaveStatus;
  type?: LeaveType;
  employee_id?: string;
  start_date?: string;
  end_date?: string;
  page?: number;
  per_page?: number;
}

const ENDPOINTS = {
  list: '/leave-requests',
  detail: (id: string) => `/leave-requests/${id}`,
  balance: '/leave/balance',
  balanceByEmployee: (employeeId: string) => `/leave/balance/${employeeId}`,
  statistics: '/leave/statistics',
  approve: (id: string) => `/leave-requests/${id}/approve`,
  reject: (id: string) => `/leave-requests/${id}/reject`,
  cancel: (id: string) => `/leave-requests/${id}/cancel`,
  pending: '/leave-requests/pending',
} as const;

// Get all leave requests with pagination and filters
export async function getLeaveRequests(
  filters?: LeaveFilters
): Promise<PaginatedResponse<LeaveRequest>> {
  const response = await apiClient.get<PaginatedResponse<LeaveRequest>>(ENDPOINTS.list, {
    params: filters,
  });
  return response.data;
}

// Get single leave request by ID
export async function getLeaveRequest(id: string): Promise<LeaveRequest> {
  const response = await apiClient.get<{ data: LeaveRequest }>(ENDPOINTS.detail(id));
  return response.data.data;
}

// Create new leave request
export async function createLeaveRequest(data: LeaveRequestFormData): Promise<LeaveRequest> {
  const formData = new FormData();
  formData.append('type', data.type);
  formData.append('start_date', data.start_date);
  formData.append('end_date', data.end_date);
  formData.append('duration_type', data.duration_type);
  formData.append('reason', data.reason);

  if (data.emergency_contact) {
    formData.append('emergency_contact', data.emergency_contact);
  }
  if (data.emergency_phone) {
    formData.append('emergency_phone', data.emergency_phone);
  }
  if (data.attachment) {
    formData.append('attachment', data.attachment);
  }

  const response = await apiClient.post<{ data: LeaveRequest }>(ENDPOINTS.list, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return response.data.data;
}

// Cancel leave request
export async function cancelLeaveRequest(id: string): Promise<LeaveRequest> {
  const response = await apiClient.post<{ data: LeaveRequest }>(ENDPOINTS.cancel(id));
  return response.data.data;
}

// Approve leave request
export async function approveLeaveRequest(id: string, notes?: string): Promise<LeaveRequest> {
  const response = await apiClient.post<{ data: LeaveRequest }>(ENDPOINTS.approve(id), { notes });
  return response.data.data;
}

// Reject leave request
export async function rejectLeaveRequest(id: string, reason: string): Promise<LeaveRequest> {
  const response = await apiClient.post<{ data: LeaveRequest }>(ENDPOINTS.reject(id), {
    rejection_reason: reason
  });
  return response.data.data;
}

// Get leave balance for current user
export async function getLeaveBalance(): Promise<LeaveBalance> {
  const response = await apiClient.get<{ data: LeaveBalance }>(ENDPOINTS.balance);
  return response.data.data;
}

// Get leave balance for specific employee
export async function getLeaveBalanceByEmployee(employeeId: string): Promise<LeaveBalance> {
  const response = await apiClient.get<{ data: LeaveBalance }>(ENDPOINTS.balanceByEmployee(employeeId));
  return response.data.data;
}

// Get leave statistics
export async function getLeaveStatistics(): Promise<LeaveStatistics> {
  const response = await apiClient.get<{ data: LeaveStatistics }>(ENDPOINTS.statistics);
  return response.data.data;
}

// Get pending approvals
export async function getPendingApprovals(): Promise<LeaveRequest[]> {
  const response = await apiClient.get<{ data: LeaveRequest[] }>(ENDPOINTS.pending);
  return response.data.data;
}
