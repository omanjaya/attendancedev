import { useState, useCallback } from 'react';
import type {
  AdminUser,
  AdminUserRole,
  AdminUserFormData,
  AdminUserStatistics,
  AdminUserDevice,
} from '@/types/user';

// Mock data
const mockUsers: AdminUser[] = [
  {
    id: '1',
    name: 'Super Admin',
    email: 'superadmin@school.id',
    phone: '081234567890',
    role: 'superadmin',
    roles: ['superadmin'],
    is_active: true,
    account_locked: false,
    force_password_change: false,
    two_factor_enabled: true,
    last_login_at: '2024-03-28T08:00:00',
    last_login_ip: '192.168.1.100',
    failed_login_attempts: 0,
    created_at: '2023-01-01T00:00:00',
    updated_at: '2024-03-28T08:00:00',
  },
  {
    id: '2',
    name: 'Admin Sekolah',
    email: 'admin@school.id',
    phone: '081234567891',
    role: 'admin',
    roles: ['admin'],
    is_active: true,
    account_locked: false,
    force_password_change: false,
    two_factor_enabled: true,
    last_login_at: '2024-03-28T09:30:00',
    last_login_ip: '192.168.1.101',
    failed_login_attempts: 0,
    created_at: '2023-01-15T00:00:00',
    updated_at: '2024-03-28T09:30:00',
    employee: {
      id: 'e1',
      nip: '198001012010011001',
      department: 'IT',
      position: 'System Administrator',
    },
  },
  {
    id: '3',
    name: 'Kepala Sekolah',
    email: 'kepsek@school.id',
    phone: '081234567892',
    role: 'manager',
    roles: ['manager'],
    is_active: true,
    account_locked: false,
    force_password_change: false,
    two_factor_enabled: false,
    last_login_at: '2024-03-27T14:00:00',
    last_login_ip: '192.168.1.102',
    failed_login_attempts: 0,
    created_at: '2023-02-01T00:00:00',
    updated_at: '2024-03-27T14:00:00',
    employee: {
      id: 'e2',
      nip: '197505152000011001',
      department: 'Manajemen',
      position: 'Kepala Sekolah',
    },
  },
  {
    id: '4',
    name: 'Budi Santoso',
    email: 'budi.santoso@school.id',
    phone: '081234567893',
    role: 'teacher',
    roles: ['teacher'],
    is_active: true,
    account_locked: false,
    force_password_change: true,
    two_factor_enabled: false,
    last_login_at: '2024-03-28T07:45:00',
    last_login_ip: '192.168.1.103',
    failed_login_attempts: 0,
    created_at: '2023-03-01T00:00:00',
    updated_at: '2024-03-28T07:45:00',
    employee: {
      id: 'e3',
      nip: '198512202015011001',
      department: 'Matematika',
      position: 'Guru',
    },
  },
  {
    id: '5',
    name: 'Siti Rahayu',
    email: 'siti.rahayu@school.id',
    phone: '081234567894',
    role: 'employee',
    roles: ['employee'],
    is_active: true,
    account_locked: true,
    force_password_change: false,
    two_factor_enabled: false,
    last_login_at: '2024-03-25T10:00:00',
    last_login_ip: '192.168.1.104',
    failed_login_attempts: 5,
    locked_until: '2024-03-28T10:00:00',
    created_at: '2023-04-01T00:00:00',
    updated_at: '2024-03-25T10:00:00',
    employee: {
      id: 'e4',
      nip: '199001012020011001',
      department: 'TU',
      position: 'Staff Administrasi',
    },
  },
  {
    id: '6',
    name: 'Dewi Anggraini',
    email: 'dewi@school.id',
    phone: '081234567895',
    role: 'teacher',
    roles: ['teacher'],
    is_active: false,
    account_locked: false,
    force_password_change: false,
    two_factor_enabled: false,
    failed_login_attempts: 0,
    created_at: '2023-05-01T00:00:00',
    updated_at: '2024-01-15T00:00:00',
    employee: {
      id: 'e5',
      nip: '198807152018012001',
      department: 'Bahasa Inggris',
      position: 'Guru',
    },
  },
];

const mockDevices: AdminUserDevice[] = [
  {
    id: 'd1',
    user_id: '1',
    device_name: 'MacBook Pro',
    device_type: 'desktop',
    browser: 'Chrome 122',
    os: 'macOS 14.3',
    ip_address: '192.168.1.100',
    last_used_at: '2024-03-28T08:00:00',
    is_trusted: true,
    created_at: '2024-01-01T00:00:00',
  },
  {
    id: 'd2',
    user_id: '1',
    device_name: 'iPhone 15',
    device_type: 'mobile',
    browser: 'Safari',
    os: 'iOS 17.3',
    ip_address: '192.168.1.150',
    last_used_at: '2024-03-27T20:00:00',
    is_trusted: true,
    created_at: '2024-02-15T00:00:00',
  },
];

export function useUsers() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [users, setUsers] = useState<AdminUser[]>(mockUsers);
  const [selectedUser, setSelectedUser] = useState<AdminUser | null>(null);

  // Fetch users
  const fetchUsers = useCallback(async (filters?: {
    role?: AdminUserRole;
    is_active?: boolean;
    search?: string;
  }) => {
    setIsLoading(true);
    setError(null);

    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      let filtered = [...mockUsers];

      if (filters?.role) {
        filtered = filtered.filter((u) => u.role === filters.role);
      }
      if (filters?.is_active !== undefined) {
        filtered = filtered.filter((u) => u.is_active === filters.is_active);
      }
      if (filters?.search) {
        const search = filters.search.toLowerCase();
        filtered = filtered.filter(
          (u) =>
            u.name.toLowerCase().includes(search) ||
            u.email.toLowerCase().includes(search)
        );
      }

      setUsers(filtered);
    } catch (err) {
      setError('Gagal memuat data pengguna');
      console.error('Error fetching users:', err);
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Create user
  const createUser = useCallback(async (data: AdminUserFormData) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      const newUser: AdminUser = {
        id: `new-${Date.now()}`,
        name: data.name,
        email: data.email,
        phone: data.phone,
        role: data.role,
        roles: [data.role],
        is_active: data.is_active,
        account_locked: false,
        force_password_change: data.force_password_change,
        two_factor_enabled: false,
        failed_login_attempts: 0,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      };

      setUsers((prev) => [newUser, ...prev]);
      return newUser;
    } catch (err) {
      setError('Gagal membuat pengguna');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Update user
  const updateUser = useCallback(async (id: string, data: Partial<AdminUserFormData>) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      setUsers((prev) =>
        prev.map((user) =>
          user.id === id
            ? {
                ...user,
                ...data,
                roles: data.role ? [data.role] : user.roles,
                updated_at: new Date().toISOString(),
              }
            : user
        )
      );
    } catch (err) {
      setError('Gagal mengupdate pengguna');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Delete user
  const deleteUser = useCallback(async (id: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));
      setUsers((prev) => prev.filter((u) => u.id !== id));
    } catch (err) {
      setError('Gagal menghapus pengguna');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Toggle user status
  const toggleUserStatus = useCallback(async (id: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 300));

      setUsers((prev) =>
        prev.map((user) =>
          user.id === id
            ? { ...user, is_active: !user.is_active, updated_at: new Date().toISOString() }
            : user
        )
      );
    } catch (err) {
      setError('Gagal mengubah status pengguna');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Lock/unlock user
  const toggleLock = useCallback(async (id: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 300));

      setUsers((prev) =>
        prev.map((user) =>
          user.id === id
            ? {
                ...user,
                account_locked: !user.account_locked,
                locked_until: user.account_locked ? undefined : undefined,
                failed_login_attempts: user.account_locked ? 0 : user.failed_login_attempts,
                updated_at: new Date().toISOString(),
              }
            : user
        )
      );
    } catch (err) {
      setError('Gagal mengubah status lock pengguna');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Reset password
  const resetPassword = useCallback(async (id: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      setUsers((prev) =>
        prev.map((user) =>
          user.id === id
            ? { ...user, force_password_change: true, updated_at: new Date().toISOString() }
            : user
        )
      );
    } catch (err) {
      setError('Gagal reset password');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Get user devices
  const getUserDevices = useCallback(async (userId: string): Promise<AdminUserDevice[]> => {
    await new Promise((resolve) => setTimeout(resolve, 300));
    return mockDevices.filter((d) => d.user_id === userId);
  }, []);

  // Get statistics
  const getStatistics = useCallback(async (): Promise<AdminUserStatistics> => {
    await new Promise((resolve) => setTimeout(resolve, 300));

    return {
      total_users: users.length,
      active_users: users.filter((u) => u.is_active).length,
      inactive_users: users.filter((u) => !u.is_active).length,
      locked_users: users.filter((u) => u.account_locked).length,
      users_with_2fa: users.filter((u) => u.two_factor_enabled).length,
      users_needing_password_change: users.filter((u) => u.force_password_change).length,
      new_users_this_month: 2,
      logins_today: 15,
    };
  }, [users]);

  return {
    // State
    isLoading,
    error,
    users,
    selectedUser,

    // Actions
    fetchUsers,
    createUser,
    updateUser,
    deleteUser,
    toggleUserStatus,
    toggleLock,
    resetPassword,
    getUserDevices,
    getStatistics,
    setSelectedUser,
    clearError: () => setError(null),
  };
}
