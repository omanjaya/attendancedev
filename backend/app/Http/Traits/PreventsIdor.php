<?php

namespace App\Http\Traits;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Trait untuk mencegah IDOR (Insecure Direct Object Reference) attacks
 * 
 * Usage:
 * 1. Use trait di controller: `use PreventsIdor;`
 * 2. Check access sebelum operasi: `if (!$this->canAccessEmployee($employee)) { abort(403); }`
 * 3. IMPORTANT: Untuk self-service endpoints (absensi, leave request sendiri), 
 *    gunakan getAuthenticatedEmployeeId() bukan $request->employee_id
 */
trait PreventsIdor
{
    /**
     * Get the authenticated user's employee ID
     * 
     * CRITICAL: Use this instead of $request->employee_id for self-service operations
     * This prevents users from spoofing employee_id to perform actions as other users
     * 
     * @return string|null
     */
    protected function getAuthenticatedEmployeeId(): ?string
    {
        $user = Auth::user();
        return $user?->employee?->id;
    }

    /**
     * Get the authenticated user's employee
     * 
     * @return Employee|null
     */
    protected function getAuthenticatedEmployee(): ?Employee
    {
        $user = Auth::user();
        return $user?->employee;
    }

    /**
     * Validate and get the employee ID from request
     * 
     * For ADMIN operations: Uses request employee_id if admin has access
     * For SELF-SERVICE operations: Always uses authenticated user's employee_id
     * 
     * @param Request $request
     * @param bool $allowAdminOverride - If true, admins can specify any employee_id
     * @return string|null
     */
    protected function getValidatedEmployeeId(Request $request, bool $allowAdminOverride = false): ?string
    {
        $user = Auth::user();
        
        if (!$user) {
            return null;
        }

        // If admin override is allowed and user is admin
        if ($allowAdminOverride) {
            $isAdmin = $user->hasRole('superadmin') || $user->hasRole('super-admin') || 
                       $user->hasRole('Super Admin') || $user->hasRole('admin') || 
                       $user->hasRole('Admin') || $user->hasRole('kepala-sekolah');
            
            if ($isAdmin && $request->has('employee_id')) {
                // Verify admin can access this employee
                $employee = Employee::find($request->employee_id);
                if ($employee && $this->canAccessEmployee($employee)) {
                    return $request->employee_id;
                }
            }
        }

        // Default: Return authenticated user's employee ID
        return $user->employee?->id;
    }

    /**
     * Check if the current user can access the employee data
     * 
     * Access Rules:
     * - Super Admin: Can access ALL employees
     * - Admin: Can access employees in their location
     * - Kepala Sekolah: Can access employees in their location
     * - Regular User: Can only access their own employee data
     *
     * @param Employee $employee
     * @return bool
     */
    protected function canAccessEmployee(Employee $employee): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // Super admin can access all employees
        if ($user->hasRole('superadmin') || $user->hasRole('super-admin') || $user->hasRole('Super Admin')) {
            return true;
        }

        // Admin and Kepala Sekolah can access employees in their location
        if ($user->hasRole('admin') || $user->hasRole('Admin') || 
            $user->hasRole('kepala-sekolah') || $user->hasRole('Kepala Sekolah')) {
            // If admin has no employee record, allow all (system admin)
            if (!$user->employee) {
                return true;
            }
            // Otherwise, check location
            return $user->employee->location_id === $employee->location_id;
        }

        // Users can only access their own employee data
        return $user->employee && $user->employee->id === $employee->id;
    }

    /**
     * Check if the current user can access another user's data
     *
     * @param User $targetUser
     * @return bool
     */
    protected function canAccessUser(User $targetUser): bool
    {
        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        
        if (!$currentUser) {
            return false;
        }

        // Super admin can access all users
        if ($currentUser->hasRole('superadmin') || $currentUser->hasRole('super-admin') || $currentUser->hasRole('Super Admin')) {
            return true;
        }

        // Admin can access most users but not other admins
        if ($currentUser->hasRole('admin')) {
            // Admin cannot modify super admins
            if ($targetUser->hasRole('superadmin') || $targetUser->hasRole('super-admin') || $targetUser->hasRole('Super Admin')) {
                return false;
            }
            return true;
        }

        // Users can only access their own data
        return $currentUser->id === $targetUser->id;
    }

    /**
     * Check if current user can access a resource by owner ID
     *
     * @param int $ownerId
     * @return bool
     */
    protected function canAccessResourceByOwnerId(int $ownerId): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // Super admin can access all
        if ($user->hasRole('superadmin') || $user->hasRole('super-admin') || $user->hasRole('Super Admin')) {
            return true;
        }

        // Admin can access all
        if ($user->hasRole('admin')) {
            return true;
        }

        // User must be the owner
        return $user->id === $ownerId;
    }

    /**
     * Check if current user is an admin (Super Admin, Admin, Kepala Sekolah)
     * 
     * @return bool
     */
    protected function isAdmin(): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        return $user->hasRole('superadmin') || $user->hasRole('super-admin') || 
               $user->hasRole('Super Admin') || $user->hasRole('admin') || 
               $user->hasRole('Admin') || $user->hasRole('kepala-sekolah') ||
               $user->hasRole('Kepala Sekolah');
    }

    /**
     * Abort with 403 if user cannot access employee
     *
     * @param Employee $employee
     * @param string $message
     * @return void
     */
    protected function authorizeEmployeeAccess(Employee $employee, string $message = 'Unauthorized access to employee data'): void
    {
        if (!$this->canAccessEmployee($employee)) {
            abort(403, $message);
        }
    }

    /**
     * Abort with 403 if user cannot access another user
     *
     * @param User $targetUser
     * @param string $message
     * @return void
     */
    protected function authorizeUserAccess(User $targetUser, string $message = 'Unauthorized access to user data'): void
    {
        if (!$this->canAccessUser($targetUser)) {
            abort(403, $message);
        }
    }

    /**
     * Ensure employee_id in request matches authenticated user's employee
     * 
     * @param Request $request
     * @param bool $allowAdminOverride
     * @return bool Returns true if valid, false if tampered
     */
    protected function validateRequestEmployeeId(Request $request, bool $allowAdminOverride = false): bool
    {
        if (!$request->has('employee_id')) {
            return true; // No employee_id in request, will use authenticated user's
        }

        $requestEmployeeId = $request->employee_id;
        $authenticatedEmployeeId = $this->getAuthenticatedEmployeeId();

        // If employee_id matches authenticated user's employee - OK
        if ($requestEmployeeId === $authenticatedEmployeeId) {
            return true;
        }

        // If admin override allowed, check admin access
        if ($allowAdminOverride && $this->isAdmin()) {
            $employee = Employee::find($requestEmployeeId);
            return $employee && $this->canAccessEmployee($employee);
        }

        // Tampering detected!
        return false;
    }
}

