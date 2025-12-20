import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import * as Sentry from '@sentry/react';
import type { User, LoginCredentials, Permission } from '@/types/auth';
import * as authApi from '@/lib/api/auth';

/**
 * SECURITY NOTE: Token Storage
 *
 * Tokens are stored in localStorage for persistence across sessions.
 *
 * Security considerations:
 * 1. Current implementation is acceptable for internal corporate applications with:
 *    - Short token expiration (7 days - configured in backend)
 *    - HTTPS only
 *    - XSS protection via CSP (Content Security Policy)
 *
 * For higher security requirements, consider:
 * 1. Using httpOnly cookies (requires backend changes)
 * 2. Using sessionStorage (clears on tab close)
 * 3. Implementing token refresh mechanism with short-lived access tokens
 *
 * Trade-offs:
 * - localStorage: Vulnerable to XSS but persists across sessions
 * - sessionStorage: More secure but less convenient (user needs to login per tab)
 * - httpOnly cookies: Most secure but requires backend session management
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
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
);
