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
  detail: (id: number) => `/employees/${id}`,
  search: '/employees/search',
  statistics: '/employees/statistics',
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
export async function getEmployee(id: number): Promise<Employee> {
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
  id: number,
  data: Partial<EmployeeFormData>
): Promise<Employee> {
  const response = await apiClient.put<{ data: Employee }>(ENDPOINTS.detail(id), data);
  return response.data.data;
}

// Delete employee
export async function deleteEmployee(id: number): Promise<void> {
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
