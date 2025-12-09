/**
 * Route guards for role-based access control
 * Used with TanStack Router beforeLoad hooks
 */

import { redirect } from '@tanstack/react-router';
import type { User } from '@/types/auth';

/**
 * Check if user has a specific role
 */
export function hasRole(user: User | null, roleName: string): boolean {
  if (!user || !user.role) return false;
  // Handle both kebab-case (frontend) and Title Case/snake_case (backend)
  const normalizedUserRole = user.role.toLowerCase().replace(/_/g, '-').replace(/ /g, '-');
  const normalizedRoleName = roleName.toLowerCase().replace(/_/g, '-').replace(/ /g, '-');
  return normalizedUserRole === normalizedRoleName;
}

/**
 * Check if user has any of the specified roles
 */
export function hasAnyRole(user: User | null, roleNames: string[]): boolean {
  if (!user || !user.role) return false;
  return roleNames.some((role) => hasRole(user, role));
}

/**
 * Check if user has all of the specified roles
 */
export function hasAllRoles(user: User | null, roleNames: string[]): boolean {
  if (!user || !user.role) return false;
  return roleNames.every((role) => hasRole(user, role));
}

/**
 * Check if user has a specific permission
 */
export function hasPermission(user: User | null, permissionName: string): boolean {
  if (!user) return false;

  // Check direct permissions (array of strings)
  if (user.permissions?.includes(permissionName)) {
    return true;
  }

  return false;
}

/**
 * Check if user has any of the specified permissions
 */
export function hasAnyPermission(user: User | null, permissionNames: string[]): boolean {
  return permissionNames.some((permission) => hasPermission(user, permission));
}

/**
 * Route guard: Require authentication
 * Redirects to /login if not authenticated
 */
export interface RouteContext {
  auth: {
    user: User | null;
    isAuthenticated: boolean;
  };
}

export function requireAuth({ context }: { context: RouteContext }) {
  const { user, isAuthenticated } = context.auth;

  if (!isAuthenticated || !user) {
    throw redirect({
      to: '/login',
      search: {
        redirect: window.location.pathname,
      },
    });
  }
}

/**
 * Route guard: Require admin role
 * Redirects to /employee/dashboard if not admin
 * Redirects to /login if not authenticated
 * Also enforces mandatory password change
 */
export function requireAdmin({ context }: { context: RouteContext }) {
  const { user, isAuthenticated } = context.auth;

  // First check authentication
  if (!isAuthenticated || !user) {
    throw redirect({
      to: '/login',
      search: {
        redirect: window.location.pathname,
      },
    });
  }

  // Security: Force password change if required
  const mustChangePassword =
    user.force_password_change === true ||
    user.password_changed_at === null;

  if (mustChangePassword && window.location.pathname !== '/auth/change-password') {
    throw redirect({ to: '/auth/change-password' });
  }

  // Then check admin role (allow super-admin, admin, and kepala-sekolah)
  if (!hasAnyRole(user, ['admin', 'super-admin', 'kepala-sekolah'])) {
    throw redirect({
      to: '/employee/dashboard',
    });
  }
}

/**
 * Route guard: Require employee role (non-admin)
 * Redirects to /admin/dashboard if admin
 * Redirects to /login if not authenticated
 * Also enforces mandatory password change
 */
export function requireEmployee({ context }: { context: RouteContext }) {
  const { user, isAuthenticated } = context.auth;

  // First check authentication
  if (!isAuthenticated || !user) {
    throw redirect({
      to: '/login',
      search: {
        redirect: window.location.pathname,
      },
    });
  }

  // Security: Force password change if required
  const mustChangePassword =
    user.force_password_change === true ||
    user.password_changed_at === null;

  if (mustChangePassword && window.location.pathname !== '/auth/change-password') {
    throw redirect({ to: '/auth/change-password' });
  }

  // Redirect admin users to admin area
  if (hasAnyRole(user, ['admin', 'super-admin', 'kepala-sekolah'])) {
    throw redirect({
      to: '/admin/dashboard',
    });
  }
}

/**
 * Route guard: Require specific permission
 * Redirects to /unauthorized if user doesn't have permission
 * Redirects to /login if not authenticated
 */
export function requirePermission(permissionName: string) {
  return ({ context }: { context: RouteContext }) => {
    const { user, isAuthenticated } = context.auth;

    // First check authentication
    if (!isAuthenticated || !user) {
      throw redirect({
        to: '/login',
        search: {
          redirect: window.location.pathname,
        },
      });
    }

    // Then check permission
    if (!hasPermission(user, permissionName)) {
      throw redirect({
        to: '/unauthorized',
      });
    }
  };
}

/**
 * Route guard: Guest only (not authenticated)
 * Redirects to dashboard based on role if already logged in
 */
export function requireGuest(context: RouteContext) {
  const { user, isAuthenticated } = context.auth;

  if (isAuthenticated && user) {
    // Redirect to appropriate dashboard based on role
    if (hasAnyRole(user, ['admin', 'super-admin', 'kepala-sekolah'])) {
      throw redirect({ to: '/admin/dashboard' });
    } else {
      throw redirect({ to: '/employee/dashboard' });
    }
  }
}

/**
 * Get default redirect path based on user role
 */
export function getDefaultRedirect(user: User | null): string {
  if (!user) return '/login';

  if (hasAnyRole(user, ['admin', 'super-admin', 'kepala-sekolah'])) {
    return '/admin/dashboard';
  }

  return '/employee/dashboard';
}

/**
 * Check if current route is accessible by user
 */
export function canAccessRoute(user: User | null, route: string): boolean {
  if (!user) return false;

  // Admin routes - allow all admin roles
  if (route.startsWith('/admin/')) {
    return hasAnyRole(user, ['admin', 'super-admin', 'kepala-sekolah']);
  }

  // Employee routes - non-admin only
  if (route.startsWith('/employee/')) {
    return !hasAnyRole(user, ['admin', 'super-admin', 'kepala-sekolah']);
  }

  // Shared routes
  return true;
}
