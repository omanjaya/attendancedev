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

// Mock data
const mockDevices: SecurityDevice[] = [
  {
    id: '1',
    name: 'MacBook Pro',
    type: 'desktop',
    browser: 'Chrome 122',
    os: 'macOS 14.3',
    ip_address: '192.168.1.100',
    location: 'Jakarta, Indonesia',
    last_used_at: new Date().toISOString(),
    is_current: true,
    is_trusted: true,
    created_at: '2024-01-01T00:00:00',
  },
  {
    id: '2',
    name: 'iPhone 15 Pro',
    type: 'mobile',
    browser: 'Safari',
    os: 'iOS 17.3',
    ip_address: '192.168.1.150',
    location: 'Jakarta, Indonesia',
    last_used_at: '2024-03-27T20:00:00',
    is_current: false,
    is_trusted: true,
    created_at: '2024-02-15T00:00:00',
  },
  {
    id: '3',
    name: 'Windows PC',
    type: 'desktop',
    browser: 'Firefox 123',
    os: 'Windows 11',
    ip_address: '192.168.1.200',
    location: 'Bandung, Indonesia',
    last_used_at: '2024-03-25T10:00:00',
    is_current: false,
    is_trusted: false,
    created_at: '2024-03-20T00:00:00',
  },
];

const mockSessions: SecuritySession[] = [
  {
    id: '1',
    device_name: 'MacBook Pro - Chrome',
    ip_address: '192.168.1.100',
    location: 'Jakarta, Indonesia',
    started_at: new Date(Date.now() - 3600000).toISOString(),
    last_activity: new Date().toISOString(),
    is_current: true,
  },
  {
    id: '2',
    device_name: 'iPhone - Safari',
    ip_address: '192.168.1.150',
    location: 'Jakarta, Indonesia',
    started_at: new Date(Date.now() - 86400000).toISOString(),
    last_activity: new Date(Date.now() - 7200000).toISOString(),
    is_current: false,
  },
];

const mockAuditLogs: AuditLog[] = [
  {
    id: '1',
    user_id: '1',
    user_name: 'Super Admin',
    user_email: 'superadmin@school.id',
    action: 'login',
    resource_type: 'auth',
    description: 'Login berhasil',
    ip_address: '192.168.1.100',
    user_agent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
    created_at: new Date().toISOString(),
  },
  {
    id: '2',
    user_id: '2',
    user_name: 'Admin Sekolah',
    user_email: 'admin@school.id',
    action: 'create',
    resource_type: 'employee',
    resource_id: 'emp-123',
    description: 'Membuat data karyawan baru: Ahmad Rizki',
    ip_address: '192.168.1.101',
    created_at: new Date(Date.now() - 1800000).toISOString(),
  },
  {
    id: '3',
    user_id: '3',
    user_name: 'Manager HR',
    user_email: 'hr@school.id',
    action: 'approve',
    resource_type: 'leave',
    resource_id: 'leave-456',
    description: 'Menyetujui cuti: Siti Nurhaliza',
    ip_address: '192.168.1.102',
    created_at: new Date(Date.now() - 3600000).toISOString(),
  },
  {
    id: '4',
    user_id: '1',
    user_name: 'Super Admin',
    user_email: 'superadmin@school.id',
    action: '2fa_enabled',
    resource_type: 'security',
    description: 'Mengaktifkan 2FA',
    ip_address: '192.168.1.100',
    created_at: new Date(Date.now() - 7200000).toISOString(),
  },
  {
    id: '5',
    user_id: '5',
    user_name: 'Budi Santoso',
    user_email: 'budi@school.id',
    action: 'login_failed',
    resource_type: 'auth',
    description: 'Login gagal: password salah',
    ip_address: '192.168.1.200',
    created_at: new Date(Date.now() - 10800000).toISOString(),
  },
  {
    id: '6',
    user_id: '2',
    user_name: 'Admin Sekolah',
    user_email: 'admin@school.id',
    action: 'update',
    resource_type: 'schedule',
    resource_id: 'sch-789',
    description: 'Mengupdate jadwal pelajaran kelas 10A',
    ip_address: '192.168.1.101',
    created_at: new Date(Date.now() - 14400000).toISOString(),
  },
  {
    id: '7',
    user_id: '1',
    user_name: 'Super Admin',
    user_email: 'superadmin@school.id',
    action: 'export',
    resource_type: 'report',
    description: 'Export laporan absensi bulan Maret',
    ip_address: '192.168.1.100',
    created_at: new Date(Date.now() - 86400000).toISOString(),
  },
  {
    id: '8',
    user_id: '4',
    user_name: 'Dewi Anggraini',
    user_email: 'dewi@school.id',
    action: 'password_change',
    resource_type: 'security',
    description: 'Mengganti password',
    ip_address: '192.168.1.103',
    created_at: new Date(Date.now() - 172800000).toISOString(),
  },
];

const mockAlerts: SecurityAlert[] = [
  {
    id: '1',
    type: 'warning',
    title: 'Multiple Failed Logins',
    message: '5 percobaan login gagal dari IP 192.168.1.200 dalam 10 menit terakhir',
    action_required: true,
    created_at: new Date(Date.now() - 600000).toISOString(),
  },
  {
    id: '2',
    type: 'info',
    title: 'New Device Login',
    message: 'Login dari perangkat baru terdeteksi untuk user admin@school.id',
    action_required: false,
    created_at: new Date(Date.now() - 3600000).toISOString(),
  },
];

export function useSecurity() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [devices, setDevices] = useState<SecurityDevice[]>(mockDevices);
  const [sessions, setSessions] = useState<SecuritySession[]>(mockSessions);
  const [auditLogs, setAuditLogs] = useState<AuditLog[]>(mockAuditLogs);
  const [alerts, setAlerts] = useState<SecurityAlert[]>(mockAlerts);
  const [twoFactorEnabled, setTwoFactorEnabled] = useState(false);

  // Get security overview
  const getOverview = useCallback(async (): Promise<SecurityOverview> => {
    await new Promise((resolve) => setTimeout(resolve, 300));

    return {
      total_users: 45,
      users_with_2fa: 12,
      users_without_2fa: 33,
      locked_accounts: 2,
      active_sessions: sessions.length,
      failed_logins_today: 5,
      suspicious_activities: 1,
      last_security_scan: new Date(Date.now() - 86400000).toISOString(),
    };
  }, [sessions]);

  // Enable 2FA - Step 1: Generate secret
  const initiate2FA = useCallback(async (): Promise<TwoFactorSetup> => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      return {
        secret: 'JBSWY3DPEHPK3PXP',
        qr_code_url: 'otpauth://totp/AttendanceSystem:user@example.com?secret=JBSWY3DPEHPK3PXP&issuer=AttendanceSystem',
        recovery_codes: [
          'A1B2C3D4',
          'E5F6G7H8',
          'I9J0K1L2',
          'M3N4O5P6',
          'Q7R8S9T0',
          'U1V2W3X4',
          'Y5Z6A7B8',
          'C9D0E1F2',
        ],
      };
    } catch (err) {
      setError('Gagal menginisialisasi 2FA');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Enable 2FA - Step 2: Verify and enable
  const enable2FA = useCallback(async (code: string): Promise<boolean> => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      // Mock verification - in real app, verify the TOTP code
      if (code.length === 6) {
        setTwoFactorEnabled(true);
        return true;
      }
      throw new Error('Kode tidak valid');
    } catch (err) {
      setError('Gagal mengaktifkan 2FA');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Disable 2FA
  const disable2FA = useCallback(async (password: string): Promise<boolean> => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      if (password.length > 0) {
        setTwoFactorEnabled(false);
        return true;
      }
      throw new Error('Password tidak valid');
    } catch (err) {
      setError('Gagal menonaktifkan 2FA');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Fetch devices
  const fetchDevices = useCallback(async () => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));
      setDevices(mockDevices);
    } catch (err) {
      setError('Gagal memuat daftar perangkat');
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Remove device
  const removeDevice = useCallback(async (deviceId: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 300));
      setDevices((prev) => prev.filter((d) => d.id !== deviceId));
    } catch (err) {
      setError('Gagal menghapus perangkat');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Trust/untrust device
  const toggleDeviceTrust = useCallback(async (deviceId: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 300));
      setDevices((prev) =>
        prev.map((d) =>
          d.id === deviceId ? { ...d, is_trusted: !d.is_trusted } : d
        )
      );
    } catch (err) {
      setError('Gagal mengubah status kepercayaan perangkat');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Fetch sessions
  const fetchSessions = useCallback(async () => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));
      setSessions(mockSessions);
    } catch (err) {
      setError('Gagal memuat daftar sesi');
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Terminate session
  const terminateSession = useCallback(async (sessionId: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 300));
      setSessions((prev) => prev.filter((s) => s.id !== sessionId));
    } catch (err) {
      setError('Gagal mengakhiri sesi');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Terminate all sessions except current
  const terminateAllSessions = useCallback(async () => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));
      setSessions((prev) => prev.filter((s) => s.is_current));
    } catch (err) {
      setError('Gagal mengakhiri semua sesi');
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
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      let filtered = [...mockAuditLogs];

      if (filters?.action) {
        filtered = filtered.filter((l) => l.action === filters.action);
      }
      if (filters?.user_id) {
        filtered = filtered.filter((l) => l.user_id === filters.user_id);
      }
      if (filters?.limit) {
        filtered = filtered.slice(0, filters.limit);
      }

      setAuditLogs(filtered);
    } catch (err) {
      setError('Gagal memuat log audit');
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Fetch alerts
  const fetchAlerts = useCallback(async () => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 300));
      setAlerts(mockAlerts);
    } catch (err) {
      setError('Gagal memuat peringatan keamanan');
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Dismiss alert
  const dismissAlert = useCallback(async (alertId: string) => {
    setAlerts((prev) =>
      prev.map((a) =>
        a.id === alertId ? { ...a, resolved_at: new Date().toISOString() } : a
      )
    );
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
    initiate2FA,
    enable2FA,
    disable2FA,
    fetchDevices,
    removeDevice,
    toggleDeviceTrust,
    fetchSessions,
    terminateSession,
    terminateAllSessions,
    fetchAuditLogs,
    fetchAlerts,
    dismissAlert,
    clearError: () => setError(null),
  };
}
