// Security Types

export interface TwoFactorSetup {
  secret: string;
  qr_code_url: string;
  recovery_codes: string[];
}

export interface SecurityDevice {
  id: string;
  name: string;
  type: 'desktop' | 'mobile' | 'tablet';
  browser: string;
  os: string;
  ip_address: string;
  location?: string;
  last_used_at: string;
  is_current: boolean;
  is_trusted: boolean;
  created_at: string;
}

export interface SecuritySession {
  id: string;
  device_name: string;
  ip_address: string;
  location?: string;
  started_at: string;
  last_activity: string;
  is_current: boolean;
}

export interface AuditLog {
  id: string;
  user_id: string;
  user_name: string;
  user_email: string;
  action: AuditAction;
  resource_type: string;
  resource_id?: string;
  description: string;
  ip_address?: string;
  user_agent?: string;
  metadata?: Record<string, unknown>;
  created_at: string;
}

export type AuditAction =
  | 'login'
  | 'logout'
  | 'login_failed'
  | 'password_change'
  | 'password_reset'
  | '2fa_enabled'
  | '2fa_disabled'
  | 'profile_update'
  | 'create'
  | 'update'
  | 'delete'
  | 'export'
  | 'import'
  | 'approve'
  | 'reject';

export interface SecurityOverview {
  total_users: number;
  users_with_2fa: number;
  users_without_2fa: number;
  locked_accounts: number;
  active_sessions: number;
  failed_logins_today: number;
  suspicious_activities: number;
  last_security_scan?: string;
}

export interface SecurityAlert {
  id: string;
  type: 'warning' | 'critical' | 'info';
  title: string;
  message: string;
  action_required: boolean;
  created_at: string;
  resolved_at?: string;
}

export interface PasswordPolicy {
  min_length: number;
  require_uppercase: boolean;
  require_lowercase: boolean;
  require_numbers: boolean;
  require_special_chars: boolean;
  max_age_days: number;
  prevent_reuse_count: number;
}

// Labels
export const auditActionLabels: Record<AuditAction, string> = {
  login: 'Login',
  logout: 'Logout',
  login_failed: 'Login Gagal',
  password_change: 'Ganti Password',
  password_reset: 'Reset Password',
  '2fa_enabled': '2FA Diaktifkan',
  '2fa_disabled': '2FA Dinonaktifkan',
  profile_update: 'Update Profil',
  create: 'Buat',
  update: 'Update',
  delete: 'Hapus',
  export: 'Export',
  import: 'Import',
  approve: 'Setujui',
  reject: 'Tolak',
};

export const auditActionColors: Record<AuditAction, string> = {
  login: '#10B981',
  logout: '#6B7280',
  login_failed: '#EF4444',
  password_change: '#F59E0B',
  password_reset: '#F59E0B',
  '2fa_enabled': '#10B981',
  '2fa_disabled': '#EF4444',
  profile_update: '#3B82F6',
  create: '#10B981',
  update: '#3B82F6',
  delete: '#EF4444',
  export: '#8B5CF6',
  import: '#8B5CF6',
  approve: '#10B981',
  reject: '#EF4444',
};
