import apiClient from './client';
import type {
  TwoFactorSetup,
  SecurityDevice,
  SecuritySession,
  AuditLog,
  SecurityOverview,
  SecurityAlert,
  AuditAction,
} from '@/types/security';

// ============================================
// Two-Factor Authentication API
// ============================================

export async function get2FAStatus(): Promise<{
  enabled: boolean;
  required: boolean;
  verified: boolean;
  has_recovery_codes: boolean;
  recovery_codes_count: number;
}> {
  const response = await apiClient.get('/two-factor/status');
  return response.data;
}

interface TwoFactorInitResponse {
  secret_key?: string;
  secret?: string;
  qr_code_url: string;
  recovery_codes?: string[];
}

export async function initialize2FA(): Promise<TwoFactorSetup> {
  const response = await apiClient.post<{ success: boolean; data: TwoFactorInitResponse }>(
    '/two-factor/setup/initialize'
  );
  return {
    secret: response.data.data.secret_key || response.data.data.secret || '',
    qr_code_url: response.data.data.qr_code_url,
    recovery_codes: response.data.data.recovery_codes || [],
  };
}

export async function enable2FA(code: string): Promise<{ success: boolean; recovery_codes: string[] }> {
  const response = await apiClient.post<{ success: boolean; recovery_codes: string[] }>(
    '/two-factor/setup/verify',
    { code }
  );
  return response.data;
}

export async function disable2FA(password: string): Promise<{ success: boolean }> {
  const response = await apiClient.delete<{ success: boolean }>('/two-factor/disable', {
    data: { password },
  });
  return response.data;
}

export async function regenerateRecoveryCodes(password: string): Promise<{ success: boolean; recovery_codes: string[] }> {
  const response = await apiClient.post<{ success: boolean; recovery_codes: string[] }>(
    '/two-factor/recovery-codes/regenerate',
    { password }
  );
  return response.data;
}

export async function verify2FACode(code: string, type: 'totp' | 'recovery' | 'sms' = 'totp'): Promise<{
  success: boolean;
  redirect?: string;
}> {
  const response = await apiClient.post('/two-factor/verify', { code, type });
  return response.data;
}

// ============================================
// Security Overview API (Admin)
// ============================================

export async function getSecurityOverview(): Promise<SecurityOverview> {
  const response = await apiClient.get<{ success: boolean; data: SecurityOverview }>('/security/overview');
  return response.data.data;
}

export async function getSecurityMetrics(): Promise<SecurityOverview> {
  const response = await apiClient.get<{ success: boolean; data: SecurityOverview }>('/security/metrics');
  return response.data.data;
}

export async function getSecurityStatistics(): Promise<{
  login_attempts: number;
  failed_attempts: number;
  success_rate: number;
  password_changes: number;
  two_factor_changes: number;
  period: string;
}> {
  const response = await apiClient.get<{ success: boolean; data: {
    login_attempts: number;
    failed_attempts: number;
    success_rate: number;
    password_changes: number;
    two_factor_changes: number;
    period: string;
  } }>('/security/statistics');
  return response.data.data;
}

export async function get2FAReport(): Promise<{
  total_users: number;
  enabled_count: number;
  disabled_count: number;
  adoption_rate: number;
}> {
  const response = await apiClient.get<{ success: boolean; data: {
    total_users: number;
    enabled_count: number;
    disabled_count: number;
    adoption_rate: number;
  } }>('/security/2fa-report');
  return response.data.data;
}

// ============================================
// Device Management API (User)
// ============================================

export async function getDevices(): Promise<SecurityDevice[]> {
  const response = await apiClient.get<{ success: boolean; data: SecurityDevice[] }>('/security/user/devices');
  return response.data.data;
}

export async function toggleDeviceTrust(deviceId: string): Promise<{ is_trusted: boolean }> {
  const response = await apiClient.post<{ success: boolean; data: { is_trusted: boolean } }>(
    `/security/user/devices/${deviceId}/trust`
  );
  return response.data.data;
}

export async function removeDevice(deviceId: string): Promise<void> {
  await apiClient.delete(`/security/user/devices/${deviceId}`);
}

// ============================================
// Session Management API (User)
// ============================================

export async function getSessions(): Promise<SecuritySession[]> {
  const response = await apiClient.get<{ success: boolean; data: SecuritySession[] }>('/security/user/sessions');
  return response.data.data;
}

export async function terminateSession(sessionId: string): Promise<void> {
  await apiClient.delete(`/security/user/sessions/${sessionId}`);
}

export async function terminateAllSessions(): Promise<void> {
  await apiClient.delete('/security/user/sessions');
}

// ============================================
// Audit Logs API (Admin)
// ============================================

export interface AuditLogFilters {
  action?: AuditAction;
  user_id?: string;
  start_date?: string;
  end_date?: string;
  limit?: number;
}

export async function getAuditLogs(filters?: AuditLogFilters): Promise<AuditLog[]> {
  const response = await apiClient.get<{ success: boolean; data: AuditLog[] }>('/security/audit-logs', {
    params: filters,
  });
  return response.data.data;
}

export async function getSecurityEvents(filters?: AuditLogFilters): Promise<AuditLog[]> {
  const response = await apiClient.get<{ success: boolean; data: AuditLog[] }>('/security/events', {
    params: filters,
  });
  return response.data.data;
}

// ============================================
// Security Alerts API (Admin)
// ============================================

export async function getSecurityAlerts(): Promise<SecurityAlert[]> {
  const response = await apiClient.get<{ success: boolean; data: SecurityAlert[] }>('/security/alerts');
  return response.data.data;
}

export async function acknowledgeAlert(alertId: string): Promise<void> {
  await apiClient.post(`/security/alerts/${alertId}/acknowledge`);
}

// ============================================
// Security Report API (Admin)
// ============================================

export async function downloadSecurityReport(): Promise<{
  generated_at: string;
  overview: SecurityOverview;
  statistics: {
    login_attempts: number;
    failed_attempts: number;
    success_rate: number;
    password_changes: number;
    two_factor_changes: number;
  };
}> {
  const response = await apiClient.get<{ success: boolean; data: {
    generated_at: string;
    overview: SecurityOverview;
    statistics: {
      login_attempts: number;
      failed_attempts: number;
      success_rate: number;
      password_changes: number;
      two_factor_changes: number;
    };
  } }>('/security/report/download');
  return response.data.data;
}

// ============================================
// Activity Log API
// ============================================

export interface ActivityLogEntry {
  id: string;
  event_type: string;
  action: string;
  description: string;
  resource_type: string;
  resource_id?: string;
  ip_address?: string;
  user_agent?: string;
  metadata?: Record<string, unknown>;
  created_at: string;
  formatted_time: string;
}

export interface ActivitySummary {
  total_logins: number;
  total_check_ins: number;
  total_check_outs: number;
  failed_logins: number;
  last_login?: string;
  last_activity?: string;
  period: string;
}

export interface EmployeeActivityLogResponse {
  employee: {
    id: string;
    name: string;
    employee_code: string;
  };
  summary: ActivitySummary;
  logs: ActivityLogEntry[];
}

// Get my activity log (personal)
export async function getMyActivityLog(limit?: number): Promise<ActivityLogEntry[]> {
  const response = await apiClient.get<{ success: boolean; data: ActivityLogEntry[] }>(
    '/security/user/activity-log',
    { params: { limit } }
  );
  return response.data.data;
}

// Get employee activity log (admin)
export async function getEmployeeActivityLog(
  employeeId: string,
  filters?: {
    event_type?: string;
    start_date?: string;
    end_date?: string;
    limit?: number;
  }
): Promise<EmployeeActivityLogResponse> {
  const response = await apiClient.get<{ success: boolean; data: EmployeeActivityLogResponse }>(
    `/employees/${employeeId}/activity-log`,
    { params: filters }
  );
  return response.data.data;
}
