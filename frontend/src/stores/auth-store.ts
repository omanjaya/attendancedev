import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import * as Sentry from '@sentry/react';
import type { User, LoginCredentials, Permission } from '@/types/auth';
import * as authApi from '@/lib/api/auth';

/**
 * SECURITY NOTE: Token Storage
 *
 * Tokens are stored in sessionStorage for improved security:
 * - Cleared automatically when browser/tab closes
 * - Not accessible via XSS attacks from other tabs
 * - Still vulnerable to XSS within the same tab, but with limited exposure
 *
 * Security measures in place:
 * 1. Session-only storage (clears on browser close)
 * 2. Short token expiration (7 days - configured in backend)
 * 3. HTTPS only in production
 * 4. XSS protection via CSP headers
 * 5. Token not exposed in URLs or logs
 */
interface AuthState {
  // State
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;

  // Actions
  login: (credentials: LoginCredentials) => Promise<void>;
  logout: () => Promise<void>;
  fetchUser: () => Promise<void>;
  setUser: (user: User | null) => void;
  setError: (error: string | null) => void;
  clearError: () => void;
  hasPermission: (permission: Permission) => boolean;
  hasRole: (role: string | string[]) => boolean;
  reset: () => void;
}

const initialState = {
  user: null,
  token: null,
  isAuthenticated: false,
  isLoading: false,
  error: null,
};

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      ...initialState,

      login: async (credentials: LoginCredentials) => {
        set({ isLoading: true, error: null });
        try {
          const response = await authApi.login(credentials);
          set({
            user: response.user,
            token: response.token,
            isAuthenticated: true,
            isLoading: false,
            error: null,
          });
          // Set Sentry user context for error tracking
          Sentry.setUser({
            id: response.user.id,
            email: response.user.email,
            username: response.user.name,
          });
          // Clear PWA install dismissal flag on successful login
          sessionStorage.removeItem('pwa-install-dismissed');
        } catch (error: any) {
          const message = error.response?.data?.message || 'Login failed';
          set({
            error: message,
            isLoading: false,
            isAuthenticated: false,
            user: null,
            token: null,
          });
          throw error;
        }
      },

      logout: async () => {
        set({ isLoading: true });
        try {
          await authApi.logout();
        } finally {
          // Clear all sensitive data from storage
          localStorage.removeItem('auth-storage');
          sessionStorage.removeItem('pwa-install-dismissed');

          // Clear Sentry user context
          Sentry.setUser(null);

          // Reset state
          set({
            ...initialState,
            isLoading: false,
          });
        }
      },

      fetchUser: async () => {
        set({ isLoading: true, error: null });
        try {
          const user = await authApi.getUser();
          set({
            user,
            isAuthenticated: true,
            isLoading: false,
          });
          // Set Sentry user context for error tracking
          Sentry.setUser({
            id: user.id,
            email: user.email,
            username: user.name,
          });
        } catch {
          Sentry.setUser(null);
          set({
            ...initialState,
            isLoading: false,
          });
        }
      },

      setUser: (user: User | null) => {
        set({
          user,
          isAuthenticated: !!user,
        });
      },

      setError: (error: string | null) => {
        set({ error });
      },

      clearError: () => {
        set({ error: null });
      },

      hasPermission: (permission: Permission) => {
        const { user } = get();
        if (!user) return false;

        // Super admin has all permissions
        if (user.role === 'super-admin') return true;

        // Handle case where permissions might be undefined (old localStorage data)
        const permissions = user.permissions || [];
        return permissions.includes(permission);
      },

      hasRole: (role: string | string[]) => {
        const { user } = get();
        if (!user) return false;

        if (Array.isArray(role)) {
          return role.includes(user.role);
        }

        return user.role === role;
      },

      reset: () => {
        set(initialState);
      },
    }),
    {
      name: 'auth-storage',
      storage: createJSONStorage(() => sessionStorage), // Use sessionStorage for security
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
);
