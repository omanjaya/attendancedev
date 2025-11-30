// User roles
export type UserRole = 'super-admin' | 'admin' | 'kepala-sekolah' | 'guru' | 'pegawai';

// User interface
export interface User {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  avatar?: string;
  employee_id?: number;
  permissions: string[];
  created_at: string;
  updated_at: string;
}

// Login credentials
export interface LoginCredentials {
  email: string;
  password: string;
  remember?: boolean;
}

// Registration data
export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

// Generic API response
export interface ApiResponse<T = unknown> {
  success: boolean;
  message: string;
  data?: T;
}

// Validation error response
export interface ValidationError {
  message: string;
  errors: Record<string, string[]>;
}

// Auth state
export interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
}

// Permission check helper type
export type Permission =
  | 'dashboard.view'
  | 'employees.view'
  | 'employees.create'
  | 'employees.edit'
  | 'employees.delete'
  | 'attendance.view'
  | 'attendance.create'
  | 'attendance.edit'
  | 'leave.view'
  | 'leave.create'
  | 'leave.approve'
  | 'schedules.view'
  | 'schedules.create'
  | 'schedules.edit'
  | 'payroll.view'
  | 'payroll.create'
  | 'payroll.edit'
  | 'reports.view'
  | 'reports.export'
  | 'settings.view'
  | 'settings.edit';
