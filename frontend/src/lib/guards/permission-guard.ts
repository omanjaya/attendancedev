import { redirect } from '@tanstack/react-router';
import { useAuthStore } from '@/stores';
import type { Permission, UserRole } from '@/types/auth';

/**
 * Route-level permission guard
 * Checks if user has required permission or role before allowing access to route
 */
export const requirePermission = (permission?: Permission, roles?: UserRole[]) => {
  return () => {
    const { user, isAuthenticated } = useAuthStore.getState();

    // First check authentication
    if (!isAuthenticated || !user) {
      throw redirect({ to: '/login' });
    }

    // Super admin bypasses all checks
    if (user.role === 'super-admin') {
      return;
    }

    // Check role restriction if specified
    if (roles && roles.length > 0) {
      if (!roles.includes(user.role)) {
        console.warn(`[Permission Guard] Access denied. Required roles: ${roles.join(', ')}, User role: ${user.role}`);
        throw redirect({ to: '/dashboard', search: { error: 'unauthorized' } });
      }
    }

    // Check permission if specified
    if (permission) {
      const userPermissions = user.permissions || [];
      if (!userPermissions.includes(permission)) {
        console.warn(`[Permission Guard] Access denied. Required permission: ${permission}, User permissions:`, userPermissions);
        throw redirect({ to: '/dashboard', search: { error: 'unauthorized' } });
      }
    }
  };
};

/**
 * Check if user has specific permission (for use in components)
 */
export const hasPermission = (permission: Permission): boolean => {
  const { user } = useAuthStore.getState();

  if (!user) return false;
  if (user.role === 'super-admin') return true;

  return user.permissions?.includes(permission) || false;
};

/**
 * Check if user has specific role (for use in components)
 */
export const hasRole = (roles: UserRole | UserRole[]): boolean => {
  const { user } = useAuthStore.getState();

  if (!user) return false;
  if (user.role === 'super-admin') return true;

  const roleArray = Array.isArray(roles) ? roles : [roles];
  return roleArray.includes(user.role);
};

/**
 * Check if user has any of the specified permissions
 */
export const hasAnyPermission = (permissions: Permission[]): boolean => {
  const { user } = useAuthStore.getState();

  if (!user) return false;
  if (user.role === 'super-admin') return true;

  return permissions.some(permission => user.permissions?.includes(permission) || false);
};

/**
 * Check if user has all of the specified permissions
 */
export const hasAllPermissions = (permissions: Permission[]): boolean => {
  const { user } = useAuthStore.getState();

  if (!user) return false;
  if (user.role === 'super-admin') return true;

  return permissions.every(permission => user.permissions?.includes(permission) || false);
};
