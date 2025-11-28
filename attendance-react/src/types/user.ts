// Admin User Management Types

export type AdminUserRole = 'superadmin' | 'admin' | 'manager' | 'teacher' | 'employee' | 'user';

export type AdminUserStatus = 'active' | 'inactive' | 'locked' | 'pending';

export interface AdminUser {
  id: string;
  name: string;
  email: string;
  phone?: string;
  avatar_url?: string;
  role: AdminUserRole;
  roles: AdminUserRole[];
  is_active: boolean;
  account_locked: boolean;
  force_password_change: boolean;
  two_factor_enabled: boolean;
  last_login_at?: string;
  last_login_ip?: string;
  failed_login_attempts: number;
  locked_until?: string;
  password_changed_at?: string;
  email_verified_at?: string;
  created_at: string;
  updated_at: string;
  employee?: {
    id: string;
    nip: string;
    department: string;
    position: string;
  };
}

export interface AdminUserDevice {
  id: string;
  user_id: string;
  device_name: string;
  device_type: 'desktop' | 'mobile' | 'tablet';
  browser: string;
  os: string;
  ip_address: string;
  last_used_at: string;
  is_trusted: boolean;
  created_at: string;
}

export interface AdminUserSecurityStatus {
  is_locked: boolean;
  has_2fa: boolean;
  requires_2fa: boolean;
  needs_password_change: boolean;
  security_level: number;
  security_score: number;
  failed_attempts: number;
  last_login?: {
    at?: string;
    ip?: string;
    device?: string;
  };
  recommendations: string[];
}

export interface AdminUserFormData {
  name: string;
  email: string;
  phone?: string;
  password?: string;
  role: AdminUserRole;
  is_active: boolean;
  force_password_change: boolean;
}

export interface AdminUserStatistics {
  total_users: number;
  active_users: number;
  inactive_users: number;
  locked_users: number;
  users_with_2fa: number;
  users_needing_password_change: number;
  new_users_this_month: number;
  logins_today: number;
}

// Labels
export const adminUserRoleLabels: Record<AdminUserRole, string> = {
  superadmin: 'Super Admin',
  admin: 'Admin',
  manager: 'Manager',
  teacher: 'Guru',
  employee: 'Pegawai',
  user: 'User',
};

export const adminUserRoleColors: Record<AdminUserRole, string> = {
  superadmin: '#DC2626',
  admin: '#7C3AED',
  manager: '#2563EB',
  teacher: '#059669',
  employee: '#D97706',
  user: '#6B7280',
};

export const adminUserStatusColors: Record<AdminUserStatus, string> = {
  active: '#10B981',
  inactive: '#6B7280',
  locked: '#EF4444',
  pending: '#F59E0B',
};
