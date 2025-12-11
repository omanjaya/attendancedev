<?php

namespace App\Services\User;

use App\Models\User;
use Spatie\Permission\Models\Role;

class UserStatisticsService
{
    /**
     * Get user statistics for dashboard
     */
    public function getStatistics(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsers = User::where('is_active', false)->count();
        $usersWithEmployees = User::whereHas('employee')->count();

        // Role distribution
        $roleDistribution = [];
        $roles = Role::withCount('users')->get();
        foreach ($roles as $role) {
            $roleDistribution[$role->name] = $role->users_count;
        }

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'inactive_users' => $inactiveUsers,
            'users_with_employees' => $usersWithEmployees,
            'role_distribution' => $roleDistribution,
            'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /**
     * Get stats for index page cards
     */
    public function getIndexStats(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'users_with_employees' => User::whereHas('employee')->count(),
            'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }
}
