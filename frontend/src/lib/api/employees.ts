import apiClient from './client';
import type {
  Employee,
  EmployeeFormData,
  EmployeeFilters,
  EmployeeStatistics,
  PaginatedResponse,
} from '@/types';

const ENDPOINTS = {
  list: '/employees',
  detail: (id: string) => `/employees/${id}`,
  search: '/employees/search',
  statistics: '/employees/statistics',
  dashboard: '/employees/dashboard',
  dashboardById: (id: string) => `/employees/${id}/dashboard`,
} as const;

// Get all employees with pagination and filters
export async function getEmployees(
  filters?: EmployeeFilters
): Promise<PaginatedResponse<Employee>> {
  const response = await apiClient.get<PaginatedResponse<Employee>>(ENDPOINTS.list, {
    params: filters,
  });
  return response.data;
}

// Get single employee by ID
export async function getEmployee(id: string): Promise<Employee> {
  const response = await apiClient.get<{ data: Employee }>(ENDPOINTS.detail(id));
  return response.data.data;
}

// Create new employee
export async function createEmployee(data: EmployeeFormData): Promise<Employee> {
  const response = await apiClient.post<{ data: Employee }>(ENDPOINTS.list, data);
  return response.data.data;
}

// Update employee
export async function updateEmployee(
  id: string,
  data: Partial<EmployeeFormData>
): Promise<Employee> {
  const response = await apiClient.put<{ data: Employee }>(ENDPOINTS.detail(id), data);
  return response.data.data;
}

// Delete employee
export async function deleteEmployee(id: string): Promise<void> {
  await apiClient.delete(ENDPOINTS.detail(id));
}

// Search employees
export async function searchEmployees(query: string): Promise<Employee[]> {
  const response = await apiClient.get<{ data: Employee[] }>(ENDPOINTS.search, {
    params: { q: query },
  });
  return response.data.data;
}

// Get employee statistics
export async function getEmployeeStatistics(): Promise<EmployeeStatistics> {
  const response = await apiClient.get<{ data: EmployeeStatistics }>(ENDPOINTS.statistics);
  return response.data.data;
}

// Get employee dashboard data (for current user)
export async function getEmployeeDashboardData(): Promise<any> {
  const response = await apiClient.get(ENDPOINTS.dashboard);
  return response.data.data;
}

// Employee dashboard data types
export interface EmployeeDashboardData {
  attendance: {
    thisMonth: number;
    present: number;
    late: number;
    absent: number;
    todayStatus: string | null;
    checkIn: string | null;
    checkOut: string | null;
  };
  leave: {
    balance: number;
    used: number;
    pending: number;
  };
  schedule: {
    today: {
      shift: string;
      time: string;
      location: string;
      can_attend: boolean;
      message: string;
      schedule_type: string;
    };
    nextShift: {
      date: string;
      shift: string;
      time: string;
    } | null;
  };
  payroll: {
    lastPayment: {
      amount: number;
      date: string;
      status: string;
    };
    nextPayment: {
      date: string;
      estimated: number;
    };
  };
  recent_attendance: {
    date: string;
    check_in: string;
    check_out: string;
    status: string;
    is_late: boolean;
    late_minutes: number;
  }[];
}

// Get employee dashboard data by ID (admin only)
export async function getEmployeeDashboardById(id: string): Promise<EmployeeDashboardData> {
  const response = await apiClient.get<{ data: EmployeeDashboardData }>(ENDPOINTS.dashboardById(id));
  return response.data.data;
}

// Upload employee avatar
export async function uploadEmployeeAvatar(
  employeeId: string,
  file: File
): Promise<{ avatar_url: string }> {
  const formData = new FormData();
  formData.append('avatar', file);

  const response = await apiClient.post<{ data: { avatar_url: string } }>(
    `/employees/${employeeId}/avatar`,
    formData,
    {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    }
  );
  return response.data.data;
}

// Delete employee avatar
export async function deleteEmployeeAvatar(employeeId: string): Promise<void> {
  await apiClient.delete(`/employees/${employeeId}/avatar`);
}

// Reset employee password (Admin only)
export interface ResetPasswordResponse {
  employee_id: string;
  email: string;
  temporary_password: string;
  force_password_change: boolean;
  message: string;
}

export async function resetEmployeePassword(
  employeeId: string,
  newPassword?: string
): Promise<ResetPasswordResponse> {
  const response = await apiClient.post<{ data: ResetPasswordResponse }>(
    `/employees/${employeeId}/reset-password`,
    newPassword ? { new_password: newPassword } : {}
  );
  return response.data.data;
}

// Bulk action types
export type BulkAction = 'delete' | 'reset_password' | 'activate' | 'deactivate';

export interface BulkActionResult {
  success: number;
  failed: number;
  errors: string[];
  reset_passwords?: {
    employee_id: string;
    name: string;
    email: string;
    temporary_password: string;
  }[];
}

// Execute bulk action on multiple employees
export async function bulkEmployeeAction(
  action: BulkAction,
  employeeIds: string[]
): Promise<BulkActionResult> {
  const response = await apiClient.post<{ data: BulkActionResult }>(
    '/employees/bulk',
    { action, employee_ids: employeeIds }
  );
  return response.data.data;
}

// Check unique field (email or employee_code) - for async form validation
export interface CheckUniqueParams {
  field: 'email' | 'employee_code';
  value: string;
  employee_id?: string; // For updates, exclude current employee
}

export interface CheckUniqueResponse {
  field: string;
  value: string;
  is_available: boolean;
  message: string;
}

export async function checkUnique(params: CheckUniqueParams): Promise<CheckUniqueResponse> {
  const response = await apiClient.post<{ data: CheckUniqueResponse }>(
    '/employees/check-unique',
    params
  );
  return response.data.data;
}
