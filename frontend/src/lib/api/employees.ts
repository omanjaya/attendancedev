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

// Get employee dashboard data
export async function getEmployeeDashboardData(): Promise<any> {
  const response = await apiClient.get(ENDPOINTS.dashboard);
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
