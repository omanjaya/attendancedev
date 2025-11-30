<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasRoleBasedFiltering
 *
 * Provides consistent role-based data filtering across controllers and services
 * Ensures users can only access data appropriate to their role
 */
trait HasRoleBasedFiltering
{
    /**
     * Apply role-based filtering to employee queries
     */
    protected function applyEmployeeFiltering(Builder $query): Builder
    {
        $user = auth()->user();

        // Super Admin sees all data
        if ($user->hasRole('superadmin')) {
            return $query;
        }

        // Admin sees all operational data
        if ($user->hasRole('admin')) {
            return $query;
        }

        // Kepala Sekolah sees employees in their school/location
        if ($user->hasRole('kepala_sekolah')) {
            $userLocationId = $user->employee?->location_id;
            if ($userLocationId) {
                return $query->where('location_id', $userLocationId);
            } else {
                // If no location assigned, see no data
                return $query->whereRaw('1 = 0');
            }
        }

        // Teachers and Staff can only see their own data
        if ($user->hasRole(['guru', 'teacher', 'pegawai', 'staff'])) {
            return $query->where('id', $user->employee?->id ?? 0);
        }

        // Default: no access for unrecognized roles
        return $query->whereRaw('1 = 0');
    }

    /**
     * Apply role-based filtering to attendance queries
     */
    protected function applyAttendanceFiltering(Builder $query): Builder
    {
        $user = auth()->user();

        // Super Admin and Admin see all data
        if ($user->hasRole(['superadmin', 'admin'])) {
            return $query;
        }

        // Kepala Sekolah sees attendance for their school location
        if ($user->hasRole('kepala_sekolah')) {
            $userLocationId = $user->employee?->location_id;
            if ($userLocationId) {
                return $query->whereHas('employee', function ($q) use ($userLocationId) {
                    $q->where('location_id', $userLocationId);
                });
            } else {
                // If no location assigned, see no data
                return $query->whereRaw('1 = 0');
            }
        }

        // Teachers and staff can only see their own attendance
        if ($user->hasRole(['guru', 'teacher', 'pegawai', 'staff'])) {
            return $query->where('employee_id', $user->employee?->id ?? 0);
        }

        // Default: no access for unrecognized roles
        return $query->whereRaw('1 = 0');
    }

    /**
     * Apply role-based filtering to user queries
     */
    protected function applyUserFiltering(Builder $query): Builder
    {
        $user = auth()->user();

        // Super Admin sees all users
        if ($user->hasRole('superadmin')) {
            return $query;
        }

        // Admin can see teachers and staff, but not other admins or superadmins
        if ($user->hasRole('admin')) {
            return $query->whereHas('roles', function ($q) {
                $q->whereIn('name', ['teacher', 'guru', 'staff', 'pegawai']);
            });
        }

        // Principal can see users in their school location
        if ($user->hasRole('kepala_sekolah')) {
            $userLocationId = $user->employee?->location_id;
            if ($userLocationId) {
                return $query->whereHas('employee', function ($q) use ($userLocationId) {
                    $q->where('location_id', $userLocationId);
                })->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['teacher', 'guru', 'staff', 'pegawai']);
                });
            } else {
                // If no location assigned, see no data
                return $query->whereRaw('1 = 0');
            }
        }

        // Teachers and staff can only see themselves
        if ($user->hasRole(['guru', 'teacher', 'pegawai', 'staff'])) {
            return $query->where('id', $user->id);
        }

        // Default: no access for unrecognized roles
        return $query->whereRaw('1 = 0');
    }

    /**
     * Apply role-based filtering to leave queries
     */
    protected function applyLeaveFiltering(Builder $query): Builder
    {
        $user = auth()->user();

        // Super Admin and Admin see all leave data
        if ($user->hasRole(['superadmin', 'admin'])) {
            return $query;
        }

        // Kepala Sekolah sees leave requests for their school location
        if ($user->hasRole('kepala_sekolah')) {
            $userLocationId = $user->employee?->location_id;
            if ($userLocationId) {
                return $query->whereHas('employee', function ($q) use ($userLocationId) {
                    $q->where('location_id', $userLocationId);
                });
            } else {
                // If no location assigned, see no data
                return $query->whereRaw('1 = 0');
            }
        }

        // Teachers and staff can only see their own leave requests
        if ($user->hasRole(['guru', 'teacher', 'pegawai', 'staff'])) {
            return $query->where('employee_id', $user->employee?->id ?? 0);
        }

        // Default: no access for unrecognized roles
        return $query->whereRaw('1 = 0');
    }

    /**
     * Check if user can view specific employee data
     */
    protected function canViewEmployeeData(int $employeeId): bool
    {
        $user = auth()->user();

        // Super Admin and Admin can view all
        if ($user->hasRole(['superadmin', 'admin'])) {
            return true;
        }

        // Users can always view their own data
        if ($user->employee?->id === $employeeId) {
            return true;
        }

        // Principal can view employees in their location
        if ($user->hasRole('kepala_sekolah')) {
            $userLocationId = $user->employee?->location_id;
            if ($userLocationId) {
                $employee = \App\Models\Employee::find($employeeId);

                return $employee && $employee->location_id === $userLocationId;
            }
        }

        return false;
    }

    /**
     * Get user's accessible location IDs
     */
    protected function getAccessibleLocationIds(): array
    {
        $user = auth()->user();

        // Super Admin and Admin can access all locations
        if ($user->hasRole(['superadmin', 'admin'])) {
            return \App\Models\Location::pluck('id')->toArray();
        }

        // Principal can access their school location
        if ($user->hasRole('kepala_sekolah')) {
            $userLocationId = $user->employee?->location_id;

            return $userLocationId ? [$userLocationId] : [];
        }

        // Teachers and staff can only access their own location
        if ($user->hasRole(['guru', 'teacher', 'pegawai', 'staff'])) {
            $userLocationId = $user->employee?->location_id;

            return $userLocationId ? [$userLocationId] : [];
        }

        return [];
    }
}
