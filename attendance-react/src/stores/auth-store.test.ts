import { describe, it, expect, beforeEach, vi } from 'vitest';
import { useAuthStore } from './auth-store';
import type { User } from '@/types/auth';

// Mock the auth API
vi.mock('@/lib/api/auth', () => ({
  login: vi.fn(),
  logout: vi.fn(),
  getUser: vi.fn(),
}));

import * as authApi from '@/lib/api/auth';

const mockUser: User = {
  id: 1,
  name: 'Test User',
  email: 'test@example.com',
  role: 'admin',
  employee_id: 1,
  permissions: ['employees.view', 'employees.create'],
  created_at: '2023-01-01T00:00:00',
  updated_at: '2023-01-01T00:00:00',
};

describe('Auth Store', () => {
  beforeEach(() => {
    // Reset the store before each test
    useAuthStore.getState().reset();
    vi.clearAllMocks();
  });

  describe('Initial State', () => {
    it('should have correct initial state', () => {
      const state = useAuthStore.getState();

      expect(state.user).toBeNull();
      expect(state.token).toBeNull();
      expect(state.isAuthenticated).toBe(false);
      expect(state.isLoading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('setUser', () => {
    it('should set user and mark as authenticated', () => {
      useAuthStore.getState().setUser(mockUser);

      const state = useAuthStore.getState();
      expect(state.user).toEqual(mockUser);
      expect(state.isAuthenticated).toBe(true);
    });

    it('should clear authentication when user is null', () => {
      useAuthStore.getState().setUser(mockUser);
      useAuthStore.getState().setUser(null);

      const state = useAuthStore.getState();
      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
    });
  });

  describe('setError', () => {
    it('should set error message', () => {
      useAuthStore.getState().setError('Test error');

      expect(useAuthStore.getState().error).toBe('Test error');
    });

    it('should clear error when set to null', () => {
      useAuthStore.getState().setError('Test error');
      useAuthStore.getState().setError(null);

      expect(useAuthStore.getState().error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error', () => {
      useAuthStore.getState().setError('Test error');
      useAuthStore.getState().clearError();

      expect(useAuthStore.getState().error).toBeNull();
    });
  });

  describe('hasPermission', () => {
    it('should return false when no user', () => {
      expect(useAuthStore.getState().hasPermission('employees.view')).toBe(false);
    });

    it('should return true for super-admin for any permission', () => {
      useAuthStore.getState().setUser({ ...mockUser, role: 'super-admin' });

      expect(useAuthStore.getState().hasPermission('employees.view')).toBe(true);
      // Super-admin should have access to settings.edit even if not in permissions array
      expect(useAuthStore.getState().hasPermission('settings.edit')).toBe(true);
    });

    it('should return true if user has permission', () => {
      useAuthStore.getState().setUser(mockUser);

      expect(useAuthStore.getState().hasPermission('employees.view')).toBe(true);
    });

    it('should return false if user does not have permission', () => {
      useAuthStore.getState().setUser(mockUser);

      expect(useAuthStore.getState().hasPermission('payroll.view')).toBe(false);
    });
  });

  describe('hasRole', () => {
    it('should return false when no user', () => {
      expect(useAuthStore.getState().hasRole('admin')).toBe(false);
    });

    it('should return true if user has matching role', () => {
      useAuthStore.getState().setUser(mockUser);

      expect(useAuthStore.getState().hasRole('admin')).toBe(true);
    });

    it('should return false if user has different role', () => {
      useAuthStore.getState().setUser(mockUser);

      expect(useAuthStore.getState().hasRole('super-admin')).toBe(false);
    });

    it('should accept array of roles', () => {
      useAuthStore.getState().setUser(mockUser);

      expect(useAuthStore.getState().hasRole(['admin', 'manager'])).toBe(true);
      expect(useAuthStore.getState().hasRole(['super-admin', 'manager'])).toBe(false);
    });
  });

  describe('login', () => {
    it('should login successfully', async () => {
      const loginResponse = { user: mockUser, token: 'test-token', message: 'Login successful' };
      vi.mocked(authApi.login).mockResolvedValueOnce(loginResponse);

      await useAuthStore.getState().login({ email: 'test@example.com', password: 'password' });

      const state = useAuthStore.getState();
      expect(state.user).toEqual(mockUser);
      expect(state.token).toBe('test-token');
      expect(state.isAuthenticated).toBe(true);
      expect(state.isLoading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('should handle login failure', async () => {
      vi.mocked(authApi.login).mockRejectedValueOnce(new Error('Invalid credentials'));

      await expect(
        useAuthStore.getState().login({ email: 'test@example.com', password: 'wrong' })
      ).rejects.toThrow('Invalid credentials');

      const state = useAuthStore.getState();
      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
      expect(state.isLoading).toBe(false);
      expect(state.error).toBe('Login failed');
    });

    it('should set loading state during login', async () => {
      vi.mocked(authApi.login).mockImplementation(
        () => new Promise((resolve) => setTimeout(() => resolve({ user: mockUser, token: 'token', message: 'Login successful' }), 100))
      );

      const loginPromise = useAuthStore.getState().login({ email: 'test@example.com', password: 'password' });

      expect(useAuthStore.getState().isLoading).toBe(true);

      await loginPromise;

      expect(useAuthStore.getState().isLoading).toBe(false);
    });
  });

  describe('logout', () => {
    it('should logout successfully', async () => {
      useAuthStore.getState().setUser(mockUser);
      vi.mocked(authApi.logout).mockResolvedValueOnce();

      await useAuthStore.getState().logout();

      const state = useAuthStore.getState();
      expect(state.user).toBeNull();
      expect(state.token).toBeNull();
      expect(state.isAuthenticated).toBe(false);
    });

    it('should clear state even if logout API fails', async () => {
      useAuthStore.getState().setUser(mockUser);
      vi.mocked(authApi.logout).mockRejectedValueOnce(new Error('Network error'));

      // The error will propagate but state should still be cleared via finally block
      try {
        await useAuthStore.getState().logout();
      } catch {
        // Expected to throw
      }

      const state = useAuthStore.getState();
      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
    });
  });

  describe('fetchUser', () => {
    it('should fetch and set user', async () => {
      vi.mocked(authApi.getUser).mockResolvedValueOnce(mockUser);

      await useAuthStore.getState().fetchUser();

      const state = useAuthStore.getState();
      expect(state.user).toEqual(mockUser);
      expect(state.isAuthenticated).toBe(true);
      expect(state.isLoading).toBe(false);
    });

    it('should reset state if fetch fails', async () => {
      useAuthStore.getState().setUser(mockUser);
      vi.mocked(authApi.getUser).mockRejectedValueOnce(new Error('Unauthorized'));

      await useAuthStore.getState().fetchUser();

      const state = useAuthStore.getState();
      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
    });
  });

  describe('reset', () => {
    it('should reset to initial state', () => {
      useAuthStore.getState().setUser(mockUser);
      useAuthStore.getState().setError('Some error');

      useAuthStore.getState().reset();

      const state = useAuthStore.getState();
      expect(state.user).toBeNull();
      expect(state.token).toBeNull();
      expect(state.isAuthenticated).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
