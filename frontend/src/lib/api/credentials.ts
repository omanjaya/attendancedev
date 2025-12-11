import apiClient from './client';

const ENDPOINTS = {
  stats: '/employees/credentials/stats',
  withoutUsers: '/employees/credentials/without-users',
  withUsers: '/employees/credentials/with-users',
  createUsers: '/employees/credentials/create-users',
  resetPasswords: '/employees/credentials/reset-passwords',
} as const;

export interface CredentialStats {
  total_employees: number;
  with_users: number;
  without_users: number;
  percentage_with_users: number;
}

export interface EmployeeCredential {
  id: number;
  full_name: string;
  email: string;
  employee_type: string;
  location?: string;
  hire_date?: string;
  role?: string;
  last_login?: string;
  created_at?: string;
}

export interface CreateUserResult {
  employee_name: string;
  email: string;
  password: string;
  success: boolean;
}

export async function getCredentialStats(): Promise<CredentialStats> {
  const response = await apiClient.get<{ data: CredentialStats }>(ENDPOINTS.stats);
  return response.data.data;
}

export async function getEmployeesWithoutUsers(): Promise<EmployeeCredential[]> {
  const response = await apiClient.get<{ data: EmployeeCredential[] }>(ENDPOINTS.withoutUsers);
  return response.data.data;
}

export async function getEmployeesWithUsers(): Promise<EmployeeCredential[]> {
  const response = await apiClient.get<{ data: EmployeeCredential[] }>(ENDPOINTS.withUsers);
  return response.data.data;
}

export async function createUsersForEmployees(employeeIds: number[]): Promise<CreateUserResult[]> {
  const response = await apiClient.post<{ data: CreateUserResult[] }>(ENDPOINTS.createUsers, {
    employee_ids: employeeIds,
  });
  return response.data.data;
}

export async function resetPasswordsForEmployees(employeeIds: number[]): Promise<CreateUserResult[]> {
  const response = await apiClient.post<{ data: CreateUserResult[] }>(ENDPOINTS.resetPasswords, {
    employee_ids: employeeIds,
  });
  return response.data.data;
}
