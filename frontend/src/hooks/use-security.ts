import { useState, useCallback } from 'react';
import type {
  TwoFactorSetup,
  SecurityDevice,
  SecuritySession,
  AuditLog,
  AuditAction,
  SecurityOverview,
  SecurityAlert,
} from '@/types/security';
import * as securityApi from '@/lib/api/security';

export function useSecurity() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [devices, setDevices] = useState<SecurityDevice[]>([]);
  const [sessions, setSessions] = useState<SecuritySession[]>([]);
  const [auditLogs, setAuditLogs] = useState<AuditLog[]>([]);
  const [alerts, setAlerts] = useState<SecurityAlert[]>([]);
  const [twoFactorEnabled, setTwoFactorEnabled] = useState(false);

  // Get security overview
  const getOverview = useCallback(async (): Promise<SecurityOverview> => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await securityApi.getSecurityOverview();
      return data;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal memuat overview keamanan';
      setError(message);
      // Return default values on error
      return {
        total_users: 0,
        users_with_2fa: 0,
        users_without_2fa: 0,
        locked_accounts: 0,
        active_sessions: 0,
        failed_logins_today: 0,
        suspicious_activities: 0,
      };
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Check 2FA status
  const check2FAStatus = useCallback(async () => {
    try {
      const status = await securityApi.get2FAStatus();
      setTwoFactorEnabled(status.enabled);
      return status;
    } catch (err) {
      console.error('Failed to check 2FA status:', err);
      return null;
    }
  }, []);

  // Enable 2FA - Step 1: Generate secret
  const initiate2FA = useCallback(async (): Promise<TwoFactorSetup> => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await securityApi.initialize2FA();
      return data;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal menginisialisasi 2FA';
      setError(message);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Enable 2FA - Step 2: Verify and enable
  const enable2FA = useCallback(async (code: string): Promise<boolean> => {
    setIsLoading(true);
    setError(null);
    try {
      const result = await securityApi.enable2FA(code);
      if (result.success) {
        setTwoFactorEnabled(true);
        return true;
      }
      throw new Error('Kode tidak valid');
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal mengaktifkan 2FA';
      setError(message);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Disable 2FA
  const disable2FA = useCallback(async (password: string): Promise<boolean> => {
    setIsLoading(true);
    setError(null);
    try {
      const result = await securityApi.disable2FA(password);
      if (result.success) {
        setTwoFactorEnabled(false);
        return true;
      }
      throw new Error('Password tidak valid');
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal menonaktifkan 2FA';
      setError(message);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Regenerate recovery codes
  const regenerateRecoveryCodes = useCallback(async (password: string): Promise<string[]> => {
    setIsLoading(true);
    setError(null);
    try {
      const result = await securityApi.regenerateRecoveryCodes(password);
      return result.recovery_codes;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal regenerate recovery codes';
      setError(message);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Fetch devices
  const fetchDevices = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await securityApi.getDevices();
      setDevices(data);
      return data;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal memuat daftar perangkat';
      setError(message);
      return [];
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Remove device
  const removeDevice = useCallback(async (deviceId: string) => {
    setIsLoading(true);
    setError(null);
    try {
      await securityApi.removeDevice(deviceId);
      setDevices((prev) => prev.filter((d) => d.id !== deviceId));
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal menghapus perangkat';
      setError(message);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Trust/untrust device
  const toggleDeviceTrust = useCallback(async (deviceId: string) => {
    setIsLoading(true);
    setError(null);
    try {
      const result = await securityApi.toggleDeviceTrust(deviceId);
      setDevices((prev) =>
        prev.map((d) =>
          d.id === deviceId ? { ...d, is_trusted: result.is_trusted } : d
        )
      );
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal mengubah status kepercayaan perangkat';
      setError(message);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Fetch sessions
  const fetchSessions = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await securityApi.getSessions();
      setSessions(data);
      return data;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal memuat daftar sesi';
      setError(message);
      return [];
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Terminate session
  const terminateSession = useCallback(async (sessionId: string) => {
    setIsLoading(true);
    setError(null);
    try {
      await securityApi.terminateSession(sessionId);
      setSessions((prev) => prev.filter((s) => s.id !== sessionId));
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal mengakhiri sesi';
      setError(message);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Terminate all sessions except current
  const terminateAllSessions = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      await securityApi.terminateAllSessions();
      setSessions((prev) => prev.filter((s) => s.is_current));
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal mengakhiri semua sesi';
      setError(message);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Fetch audit logs
  const fetchAuditLogs = useCallback(async (filters?: {
    action?: AuditAction;
    user_id?: string;
    start_date?: string;
    end_date?: string;
    limit?: number;
  }) => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await securityApi.getAuditLogs(filters);
      setAuditLogs(data);
      return data;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal memuat log audit';
      setError(message);
      return [];
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Fetch alerts
  const fetchAlerts = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await securityApi.getSecurityAlerts();
      setAlerts(data);
      return data;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal memuat peringatan keamanan';
      setError(message);
      return [];
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Dismiss/acknowledge alert
  const dismissAlert = useCallback(async (alertId: string) => {
    try {
      await securityApi.acknowledgeAlert(alertId);
      setAlerts((prev) =>
        prev.map((a) =>
          a.id === alertId ? { ...a, resolved_at: new Date().toISOString() } : a
        )
      );
    } catch (err) {
      console.error('Failed to acknowledge alert:', err);
    }
  }, []);

  // Get 2FA report (admin)
  const get2FAReport = useCallback(async () => {
    try {
      return await securityApi.get2FAReport();
    } catch (err) {
      console.error('Failed to get 2FA report:', err);
      return null;
    }
  }, []);

  // Get statistics (admin)
  const getStatistics = useCallback(async () => {
    try {
      return await securityApi.getSecurityStatistics();
    } catch (err) {
      console.error('Failed to get security statistics:', err);
      return null;
    }
  }, []);

  // Download security report
  const downloadReport = useCallback(async () => {
    try {
      return await securityApi.downloadSecurityReport();
    } catch (err) {
      console.error('Failed to download security report:', err);
      return null;
    }
  }, []);

  return {
    // State
    isLoading,
    error,
    devices,
    sessions,
    auditLogs,
    alerts,
    twoFactorEnabled,

    // Actions
    getOverview,
    check2FAStatus,
    initiate2FA,
    enable2FA,
    disable2FA,
    regenerateRecoveryCodes,
    fetchDevices,
    removeDevice,
    toggleDeviceTrust,
    fetchSessions,
    terminateSession,
    terminateAllSessions,
    fetchAuditLogs,
    fetchAlerts,
    dismissAlert,
    get2FAReport,
    getStatistics,
    downloadReport,
    clearError: () => setError(null),
  };
}
