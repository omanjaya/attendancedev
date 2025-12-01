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
  if (!user || !user.roles) return false;
  return user.roles.some((role) => role.name === roleName);
}

/**
 * Check if user has any of the specified roles
 */
export function hasAnyRole(user: User | null, roleNames: string[]): boolean {
  if (!user || !user.roles) return false;
  return user.roles.some((role) => roleNames.includes(role.name));
}

/**
 * Check if user has all of the specified roles
 */
export function hasAllRoles(user: User | null, roleNames: string[]): boolean {
  if (!user || !user.roles) return false;
  return roleNames.every((roleName) =>
    user.roles!.some((role) => role.name === roleName)
  );
}

/**
 * Check if user has a specific permission
 */
export function hasPermission(user: User | null, permissionName: string): boolean {
  if (!user) return false;

  // Check direct permissions
  if (user.permissions?.some((p) => p.name === permissionName)) {
    return true;
  }

  // Check permissions through roles
  if (user.roles) {
    return user.roles.some((role) =>
      role.permissions?.some((p) => p.name === permissionName)
    );
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

export function requireAuth(context: RouteContext) {
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
 */
export function requireAdmin(context: RouteContext) {
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

  // Then check admin role
  if (!hasRole(user, 'admin')) {
    throw redirect({
      to: '/employee/dashboard',
    });
  }
}

/**
 * Route guard: Require employee role (non-admin)
 * Redirects to /admin/dashboard if admin
 * Redirects to /login if not authenticated
 */
export function requireEmployee(context: RouteContext) {
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

  // Redirect admin users to admin area
  if (hasRole(user, 'admin')) {
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
  return (context: RouteContext) => {
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
    if (hasRole(user, 'admin')) {
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

  if (hasRole(user, 'admin')) {
    return '/admin/dashboard';
  }

  return '/employee/dashboard';
}

/**
 * Check if current route is accessible by user
 */
export function canAccessRoute(user: User | null, route: string): boolean {
  if (!user) return false;

  // Admin routes
  if (route.startsWith('/admin/')) {
    return hasRole(user, 'admin');
  }

  // Employee routes
  if (route.startsWith('/employee/')) {
    return !hasRole(user, 'admin'); // Non-admin only
  }

  // Shared routes
  return true;
}
