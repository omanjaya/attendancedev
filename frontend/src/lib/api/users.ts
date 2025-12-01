import apiClient from './client';

export interface CreateUserAccountData {
  employee_id: number;
  email: string;
  password: string;
  role: 'pegawai' | 'guru' | 'admin' | 'kepala-sekolah';
}

export interface UserAccount {
  id: number;
  name: string;
  email: string;
  role: string;
  employee_id: number;
}

// Create user account for an employee (Admin only)
export async function createUserAccount(data: CreateUserAccountData): Promise<UserAccount> {
  const response = await apiClient.post<{ data: { user: UserAccount } }>(
    '/v1/users/create-account',
    data
  );
  return response.data.data.user;
}

// Get user by employee ID
export async function getUserByEmployeeId(employeeId: number): Promise<UserAccount | null> {
  try {
    const response = await apiClient.get<{ data: { user: UserAccount } }>(
      `/v1/users/employee/${employeeId}`
    );
    return response.data.data.user;
  } catch {
    return null;
  }
}

// Check if employee has user account
export async function checkEmployeeHasUser(employeeId: number): Promise<boolean> {
  const response = await apiClient.get<{ data: { has_user: boolean } }>(
    `/v1/users/check-employee/${employeeId}`
  );
  return response.data.data.has_user;
}
