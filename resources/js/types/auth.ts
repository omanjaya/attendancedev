// Auth Types
export interface User {
  id: string
  email: string
  first_name: string
  last_name: string
  employee_id?: string
  avatar?: string
  roles: string[]
  permissions: string[]
  email_verified_at?: string
  two_factor_enabled: boolean
  last_login_at?: string
  created_at: string
  updated_at: string

  // Employee related fields
  employee?: {
    id: string
    employee_number: string
    department: string
    position: string
    phone?: string
    address?: string
    date_of_birth?: string
    hire_date: string
    salary?: number
    status: 'active' | 'inactive' | 'suspended'
  }
}

export interface LoginCredentials {
  email: string
  password: string
  remember?: boolean
  device_name?: string
}

export interface AuthState {
  user: User | null
  token: string | null
  isLoading: boolean
  error: string | null
  isAuthenticated: boolean
}

export interface TwoFactorChallenge {
  recovery_codes?: string[]
  secret_key?: string
  qr_code?: string
}

export interface TwoFactorVerification {
  code: string
  recovery_code?: string
}

export interface PasswordReset {
  email: string
  token?: string
  password?: string
  password_confirmation?: string
}

export interface DeviceInfo {
  id: string
  name: string
  platform: string
  browser: string
  ip_address: string
  is_current: boolean
  last_used_at: string
  created_at: string
}

export interface SessionInfo {
  id: string
  user_id: string
  ip_address: string
  user_agent: string
  last_activity: string
  is_current: boolean
}
