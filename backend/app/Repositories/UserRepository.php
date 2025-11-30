<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * User Repository
 *
 * Handles all user-related database operations
 */
class UserRepository extends BaseRepository
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    /**
     * Get active users
     */
    public function getActiveUsers(): Collection
    {
        $cacheKey = $this->getCacheKey('active_users');

        return cache()->remember($cacheKey, 3600, function () {
            return $this->model
                ->where('is_active', true)
                ->with(['roles', 'employee.location'])
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get users for DataTables
     */
    public function getUsersForDataTable()
    {
        return $this->model
            ->select([
                'id', 'name', 'email', 'email_verified_at',
                'is_active', 'last_login_at', 'created_at',
            ])
            ->with([
                'roles:id,name',
                'employee:id,user_id,employee_id,full_name,employee_type,location_id',
                'employee.location:id,name',
            ])
            ->withCount(['roles']);
    }

    /**
     * Get user by email
     */
    public function getByEmail(string $email): ?User
    {
        $cacheKey = $this->getCacheKey('by_email', [$email]);

        return cache()->remember($cacheKey, 1800, function () use ($email) {
            return $this->model
                ->where('email', $email)
                ->with(['roles', 'employee.location'])
                ->first();
        });
    }

    /**
     * Get users by role
     */
    public function getByRole(string $roleName): Collection
    {
        $cacheKey = $this->getCacheKey('by_role', [$roleName]);

        return cache()->remember($cacheKey, 1800, function () use ($roleName) {
            return $this->model
                ->whereHas('roles', function ($query) use ($roleName) {
                    $query->where('name', $roleName);
                })
                ->where('is_active', true)
                ->with(['roles', 'employee.location'])
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get users by multiple roles
     */
    public function getByRoles(array $roleNames): Collection
    {
        $cacheKey = $this->getCacheKey('by_roles', $roleNames);

        return cache()->remember($cacheKey, 1800, function () use ($roleNames) {
            return $this->model
                ->whereHas('roles', function ($query) use ($roleNames) {
                    $query->whereIn('name', $roleNames);
                })
                ->where('is_active', true)
                ->with(['roles', 'employee.location'])
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get users with employee records
     */
    public function getUsersWithEmployees(): Collection
    {
        $cacheKey = $this->getCacheKey('with_employees');

        return cache()->remember($cacheKey, 1800, function () {
            return $this->model
                ->whereHas('employee')
                ->where('is_active', true)
                ->with(['roles', 'employee.location'])
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get users without employee records
     */
    public function getUsersWithoutEmployees(): Collection
    {
        $cacheKey = $this->getCacheKey('without_employees');

        return cache()->remember($cacheKey, 1800, function () {
            return $this->model
                ->whereDoesntHave('employee')
                ->where('is_active', true)
                ->with(['roles'])
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get users by location
     */
    public function getByLocation(string $locationId): Collection
    {
        $cacheKey = $this->getCacheKey('by_location', [$locationId]);

        return cache()->remember($cacheKey, 1800, function () use ($locationId) {
            return $this->model
                ->whereHas('employee', function ($query) use ($locationId) {
                    $query->where('location_id', $locationId);
                })
                ->where('is_active', true)
                ->with(['roles', 'employee.location'])
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get recently logged in users
     */
    public function getRecentlyLoggedIn(int $days = 30): Collection
    {
        $cacheKey = $this->getCacheKey('recently_logged_in', [$days]);

        return cache()->remember($cacheKey, 1800, function () use ($days) {
            return $this->model
                ->where('last_login_at', '>=', now()->subDays($days))
                ->where('is_active', true)
                ->with(['roles', 'employee.location'])
                ->orderBy('last_login_at', 'desc')
                ->get();
        });
    }

    /**
     * Get users who haven't logged in recently
     */
    public function getInactiveUsers(int $days = 30): Collection
    {
        $cacheKey = $this->getCacheKey('inactive_users', [$days]);

        return cache()->remember($cacheKey, 1800, function () use ($days) {
            return $this->model
                ->where(function ($query) use ($days) {
                    $query->where('last_login_at', '<', now()->subDays($days))
                        ->orWhereNull('last_login_at');
                })
                ->where('is_active', true)
                ->with(['roles', 'employee.location'])
                ->orderBy('last_login_at', 'asc')
                ->get();
        });
    }

    /**
     * Get user statistics
     */
    public function getStatistics(): array
    {
        $cacheKey = $this->getCacheKey('statistics');

        return cache()->remember($cacheKey, 3600, function () {
            $total = $this->model->count();
            $active = $this->model->where('is_active', true)->count();
            $inactive = $total - $active;
            $verified = $this->model->whereNotNull('email_verified_at')->count();
            $unverified = $total - $verified;

            $withEmployees = $this->model->whereHas('employee')->count();
            $withoutEmployees = $total - $withEmployees;

            $roleDistribution = [];
            $roles = Role::withCount('users')->get();
            foreach ($roles as $role) {
                $roleDistribution[$role->name] = $role->users_count;
            }

            $recentRegistrations = $this->model
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            $recentLogins = $this->model
                ->where('last_login_at', '>=', now()->subDays(7))
                ->count();

            return [
                'total_users' => $total,
                'active_users' => $active,
                'inactive_users' => $inactive,
                'verified_users' => $verified,
                'unverified_users' => $unverified,
                'users_with_employees' => $withEmployees,
                'users_without_employees' => $withoutEmployees,
                'role_distribution' => $roleDistribution,
                'recent_registrations' => $recentRegistrations,
                'recent_logins' => $recentLogins,
                'verification_rate' => $total > 0 ? round(($verified / $total) * 100, 1) : 0,
                'employee_association_rate' => $total > 0 ? round(($withEmployees / $total) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Create user with role
     */
    public function createWithRole(array $userData, array $roleIds): User
    {
        return DB::transaction(function () use ($userData, $roleIds) {
            if (isset($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            }

            $user = $this->create($userData);

            $roles = Role::whereIn('id', $roleIds)->get();
            $user->assignRole($roles);

            return $user->load('roles');
        });
    }

    /**
     * Update user with roles
     */
    public function updateWithRoles(string $userId, array $userData, array $roleIds = []): User
    {
        return DB::transaction(function () use ($userId, $userData, $roleIds) {
            $user = $this->findOrFail($userId);

            if (isset($userData['password']) && ! empty($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            } else {
                unset($userData['password']);
            }

            $user->update($userData);

            if (! empty($roleIds)) {
                $roles = Role::whereIn('id', $roleIds)->get();
                $user->syncRoles($roles);
            }

            return $user->fresh(['roles', 'employee.location']);
        });
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(string $userId): bool
    {
        $user = $this->findOrFail($userId);
        $result = $user->update(['is_active' => ! $user->is_active]);

        $this->clearCache();

        return $result;
    }

    /**
     * Reset user password
     */
    public function resetPassword(string $userId, string $newPassword): bool
    {
        $user = $this->findOrFail($userId);
        $result = $user->update(['password' => Hash::make($newPassword)]);

        $this->clearCache();

        return $result;
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(string $userId): bool
    {
        $user = $this->findOrFail($userId);
        $result = $user->update(['last_login_at' => now()]);

        // Don't clear cache for this operation as it's frequent
        $this->forgetCache($this->getCacheKey('find', [$userId]));

        return $result;
    }

    /**
     * Verify user email
     */
    public function verifyEmail(string $userId): bool
    {
        $user = $this->findOrFail($userId);
        $result = $user->update(['email_verified_at' => now()]);

        $this->clearCache();

        return $result;
    }

    /**
     * Search users by name or email
     */
    public function search(string $query): Collection
    {
        $cacheKey = $this->getCacheKey('search', [$query]);

        return cache()->remember($cacheKey, 600, function () use ($query) {
            return $this->model
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('email', 'LIKE', "%{$query}%");
                })
                ->where('is_active', true)
                ->with(['roles', 'employee.location'])
                ->orderBy('name')
                ->limit(20)
                ->get();
        });
    }

    /**
     * Get users for dropdown/select
     */
    public function getUsersForSelect(): Collection
    {
        $cacheKey = $this->getCacheKey('for_select');

        return cache()->remember($cacheKey, 3600, function () {
            return $this->model
                ->select(['id', 'name', 'email'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'text' => $user->name.' ('.$user->email.')',
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                });
        });
    }

    /**
     * Get admins and superadmins
     */
    public function getAdministrators(): Collection
    {
        return $this->getByRoles(['admin', 'superadmin']);
    }

    /**
     * Get teachers and staff
     */
    public function getTeachersAndStaff(): Collection
    {
        return $this->getByRoles(['teacher', 'guru', 'staff', 'pegawai']);
    }

    /**
     * Get users with specific permission
     */
    public function getWithPermission(string $permissionName): Collection
    {
        $cacheKey = $this->getCacheKey('with_permission', [$permissionName]);

        return cache()->remember($cacheKey, 1800, function () use ($permissionName) {
            return $this->model
                ->whereHas('roles.permissions', function ($query) use ($permissionName) {
                    $query->where('name', $permissionName);
                })
                ->orWhereHas('permissions', function ($query) use ($permissionName) {
                    $query->where('name', $permissionName);
                })
                ->where('is_active', true)
                ->with(['roles', 'employee.location'])
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get users created in date range
     */
    public function getCreatedInDateRange(string $startDate, string $endDate): Collection
    {
        $cacheKey = $this->getCacheKey('created_in_range', [$startDate, $endDate]);

        return cache()->remember($cacheKey, 1800, function () use ($startDate, $endDate) {
            return $this->model
                ->whereBetween('created_at', [$startDate, $endDate])
                ->with(['roles', 'employee.location'])
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Get user login history summary
     */
    public function getLoginHistorySummary(int $days = 30): array
    {
        $cacheKey = $this->getCacheKey('login_history', [$days]);

        return cache()->remember($cacheKey, 3600, function () use ($days) {
            $startDate = now()->subDays($days);

            $totalUsers = $this->model->where('is_active', true)->count();
            $loggedInUsers = $this->model
                ->where('last_login_at', '>=', $startDate)
                ->where('is_active', true)
                ->count();

            $dailyLogins = DB::table('users')
                ->selectRaw('DATE(last_login_at) as date, COUNT(*) as count')
                ->where('last_login_at', '>=', $startDate)
                ->where('is_active', true)
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return [
                'total_active_users' => $totalUsers,
                'users_logged_in' => $loggedInUsers,
                'users_not_logged_in' => $totalUsers - $loggedInUsers,
                'login_rate' => $totalUsers > 0 ? round(($loggedInUsers / $totalUsers) * 100, 1) : 0,
                'daily_logins' => $dailyLogins->map(function ($login) {
                    return [
                        'date' => $login->date,
                        'count' => $login->count,
                    ];
                })->toArray(),
            ];
        });
    }

    /**
     * Bulk deactivate users
     */
    public function bulkDeactivate(array $userIds): int
    {
        $updated = $this->model
            ->whereIn('id', $userIds)
            ->where('id', '!=', auth()->id()) // Don't deactivate current user
            ->update(['is_active' => false]);

        $this->clearCache();

        return $updated;
    }

    /**
     * Bulk activate users
     */
    public function bulkActivate(array $userIds): int
    {
        $updated = $this->model
            ->whereIn('id', $userIds)
            ->update(['is_active' => true]);

        $this->clearCache();

        return $updated;
    }
}
