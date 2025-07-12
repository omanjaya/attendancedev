<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class NavigationService
{
    /**
     * Cache duration for navigation structure (5 minutes)
     */
    private const CACHE_DURATION = 300;

    /**
     * Get optimized navigation structure dengan caching
     */
    public function getMainNavigation(string $currentRoute = null, $user = null): array
    {
        $currentRoute = $currentRoute ?? request()->route()?->getName() ?? '';
        $userId = $user?->id ?? auth()->id();
        
        // Cache key berdasarkan user permissions dan current route
        $userPermissions = $user ? $user->getAllPermissions()->pluck('name')->sort()->join(',') : '';
        $cacheKey = "navigation.main.{$userId}." . md5($userPermissions . $currentRoute);
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($currentRoute, $user) {
            return $this->buildNavigationStructure($currentRoute, $user);
        });
    }

    /**
     * Build navigation structure dengan pre-computed routes dan permissions
     */
    private function buildNavigationStructure(string $currentRoute, $user): array
    {
        $baseNavigation = $this->getBaseNavigationConfig();
        $processedNavigation = [];

        foreach ($baseNavigation as $item) {
            // Skip jika user tidak punya permission
            if (isset($item['permission']) && $user && !$user->can($item['permission'])) {
                continue;
            }

            // Pre-compute route URLs untuk performance
            $processedItem = [
                'name' => $item['name'],
                'href' => $this->resolveRoute($item['route'], $item['params'] ?? []),
                'icon' => $item['icon'],
                'current' => $this->isCurrentRoute($currentRoute, $item['route_pattern']),
                'badge' => $this->getBadgeData($item['badge_key'] ?? null, $user),
                'children' => [],
            ];

            // Process children dengan permission check
            if (isset($item['children'])) {
                foreach ($item['children'] as $child) {
                    if (isset($child['permission']) && $user && !$user->can($child['permission'])) {
                        continue;
                    }

                    $processedItem['children'][] = [
                        'name' => $child['name'],
                        'href' => $this->resolveRoute($child['route'], $child['params'] ?? []),
                        'current' => $this->isCurrentRoute($currentRoute, $child['route_pattern']),
                        'badge' => $this->getBadgeData($child['badge_key'] ?? null, $user),
                    ];
                }
            }

            $processedNavigation[] = $processedItem;
        }

        return $processedNavigation;
    }

    /**
     * Get bottom navigation (Settings, etc.)
     */
    public function getBottomNavigation(string $currentRoute = null, $user = null): array
    {
        $currentRoute = $currentRoute ?? request()->route()?->getName() ?? '';
        $userId = $user?->id ?? auth()->id();
        
        $cacheKey = "navigation.bottom.{$userId}." . md5($currentRoute);
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($currentRoute, $user) {
            $items = [
                [
                    'name' => 'Settings',
                    'route' => 'system.settings',
                    'icon' => 'cog',
                    'route_pattern' => 'system',
                    'permission' => 'manage_system',
                ],
            ];

            $processed = [];
            foreach ($items as $item) {
                if (isset($item['permission']) && $user && !$user->can($item['permission'])) {
                    continue;
                }

                $processed[] = [
                    'name' => $item['name'],
                    'href' => $this->resolveRoute($item['route']),
                    'icon' => $item['icon'],
                    'current' => $this->isCurrentRoute($currentRoute, $item['route_pattern']),
                ];
            }

            return $processed;
        });
    }

    /**
     * Base navigation configuration
     */
    private function getBaseNavigationConfig(): array
    {
        return [
            [
                'name' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'dashboard',
                'route_pattern' => 'dashboard',
                'permission' => null,
            ],
            [
                'name' => 'Attendance',
                'route' => 'attendance.index',
                'icon' => 'clock',
                'route_pattern' => 'attendance',
                'permission' => 'view_attendance',
                'badge_key' => 'pending_check_ins',
                'children' => [
                    [
                        'name' => 'Overview',
                        'route' => 'attendance.index',
                        'route_pattern' => 'attendance.index',
                    ],
                    [
                        'name' => 'Check In/Out',
                        'route' => 'attendance.check-in',
                        'route_pattern' => 'attendance.check-in',
                    ],
                    [
                        'name' => 'History',
                        'route' => 'attendance.history',
                        'route_pattern' => 'attendance.history',
                    ],
                    [
                        'name' => 'Reports',
                        'route' => 'attendance.reports',
                        'route_pattern' => 'attendance.reports',
                        'permission' => 'view_attendance_reports',
                    ],
                ]
            ],
            [
                'name' => 'Employees',
                'route' => 'employees.index',
                'icon' => 'users',
                'route_pattern' => 'employees',
                'permission' => 'view_employees',
                'badge_key' => 'new_employees',
                'children' => [
                    [
                        'name' => 'All Employees',
                        'route' => 'employees.index',
                        'route_pattern' => 'employees.index',
                    ],
                    [
                        'name' => 'Add Employee',
                        'route' => 'employees.create',
                        'route_pattern' => 'employees.create',
                        'permission' => 'create_employees',
                    ],
                ]
            ],
            [
                'name' => 'Schedules',
                'route' => 'schedules.index',
                'icon' => 'calendar',
                'route_pattern' => 'schedules',
                'permission' => 'view_schedules',
                'children' => [
                    [
                        'name' => 'Schedule Management',
                        'route' => 'schedules.index',
                        'route_pattern' => 'schedules.index',
                    ],
                    [
                        'name' => 'Calendar View',
                        'route' => 'schedules.calendar',
                        'route_pattern' => 'schedules.calendar',
                    ],
                ]
            ],
            [
                'name' => 'Leave Management',
                'route' => 'leave.index',
                'icon' => 'clipboard',
                'route_pattern' => 'leave',
                'permission' => 'view_leave',
                'badge_key' => 'pending_leave_requests',
                'children' => [
                    [
                        'name' => 'My Requests',
                        'route' => 'leave.index',
                        'route_pattern' => 'leave.index',
                    ],
                    [
                        'name' => 'Request Leave',
                        'route' => 'leave.create',
                        'route_pattern' => 'leave.create',
                    ],
                    [
                        'name' => 'Leave Balance',
                        'route' => 'leave.balance.index',
                        'route_pattern' => 'leave.balance',
                    ],
                    [
                        'name' => 'Approvals',
                        'route' => 'leave.approvals.index',
                        'route_pattern' => 'leave.approvals',
                        'permission' => 'approve_leave',
                        'badge_key' => 'pending_approvals',
                    ],
                ]
            ],
            [
                'name' => 'Payroll',
                'route' => 'payroll.index',
                'icon' => 'banknotes',
                'route_pattern' => 'payroll',
                'permission' => 'view_payroll',
                'children' => [
                    [
                        'name' => 'Payroll Records',
                        'route' => 'payroll.index',
                        'route_pattern' => 'payroll.index',
                    ],
                    [
                        'name' => 'Calculate Payroll',
                        'route' => 'payroll.create',
                        'route_pattern' => 'payroll.create',
                        'permission' => 'create_payroll',
                    ],
                    [
                        'name' => 'Bulk Calculate',
                        'route' => 'payroll.bulk-calculate',
                        'route_pattern' => 'payroll.bulk-calculate',
                        'permission' => 'create_payroll',
                    ],
                ]
            ],
            [
                'name' => 'Academic Schedules',
                'route' => 'academic.schedules.index',
                'icon' => 'academic-cap',
                'route_pattern' => 'academic',
                'permission' => 'manage_schedules',
                'children' => [
                    [
                        'name' => 'Schedule Grid',
                        'route' => 'academic.schedules.index',
                        'route_pattern' => 'academic.schedules.index',
                    ],
                    [
                        'name' => 'Calendar View',
                        'route' => 'academic.schedules.calendar',
                        'route_pattern' => 'academic.schedules.calendar',
                    ],
                ]
            ],
            [
                'name' => 'Reports & Analytics',
                'route' => 'attendance.reports',
                'icon' => 'chart-bar',
                'route_pattern' => 'reports',
                'permission' => 'view_attendance_reports',
                'children' => [
                    [
                        'name' => 'Attendance Reports',
                        'route' => 'attendance.reports',
                        'route_pattern' => 'attendance.reports',
                        'permission' => 'view_attendance_reports',
                    ],
                    [
                        'name' => 'Payroll Reports',
                        'route' => 'payroll.index',
                        'route_pattern' => 'payroll.reports',
                        'permission' => 'view_payroll',
                    ],
                    [
                        'name' => 'Leave Analytics',
                        'route' => 'leave.balance.index',
                        'route_pattern' => 'leave.analytics',
                        'permission' => 'view_leave_analytics',
                    ],
                ]
            ],
            [
                'name' => 'System Management',
                'route' => 'audit.index',
                'icon' => 'cog',
                'route_pattern' => 'system',
                'permission' => 'admin_access',
                'badge_key' => 'system_alerts',
                'children' => [
                    [
                        'name' => 'User Management',
                        'route' => 'employees.index',
                        'route_pattern' => 'users',
                        'permission' => 'manage_users',
                    ],
                    [
                        'name' => 'Location Management',
                        'route' => 'locations.index',
                        'route_pattern' => 'locations',
                        'permission' => 'manage_locations',
                    ],
                    [
                        'name' => 'Audit Logs',
                        'route' => 'audit.index',
                        'route_pattern' => 'audit',
                        'permission' => 'admin_access',
                    ],
                    [
                        'name' => 'System Backup',
                        'route' => 'backup.index',
                        'route_pattern' => 'backup',
                        'permission' => 'admin_access',
                    ],
                ]
            ],
            [
                'name' => 'Security',
                'route' => 'security.dashboard',
                'icon' => 'shield',
                'route_pattern' => 'security',
                'permission' => 'admin_access',
                'badge_key' => 'security_alerts',
                'children' => [
                    [
                        'name' => 'Security Dashboard',
                        'route' => 'security.dashboard',
                        'route_pattern' => 'security.dashboard',
                    ],
                    [
                        'name' => 'Device Management',
                        'route' => 'security.devices',
                        'route_pattern' => 'security.devices',
                    ],
                    [
                        'name' => 'Notification Preferences',
                        'route' => 'security.notifications',
                        'route_pattern' => 'security.notifications',
                    ],
                    [
                        'name' => 'Security Events',
                        'route' => 'security.events',
                        'route_pattern' => 'security.events',
                        'permission' => 'admin_access',
                    ],
                    [
                        'name' => '2FA Settings',
                        'route' => 'security.two-factor',
                        'route_pattern' => 'security.two-factor',
                    ],
                ]
            ],
        ];
    }

    /**
     * Resolve route dengan error handling
     */
    private function resolveRoute(string $routeName, array $params = []): string
    {
        try {
            return route($routeName, $params);
        } catch (\Exception $e) {
            // Fallback ke dashboard jika route tidak ada
            return route('dashboard');
        }
    }

    /**
     * Check if current route matches pattern
     */
    private function isCurrentRoute(string $currentRoute, string $pattern): bool
    {
        return str_starts_with($currentRoute, $pattern);
    }

    /**
     * Get badge data untuk notifications
     */
    private function getBadgeData(?string $badgeKey, $user): ?array
    {
        if (!$badgeKey || !$user) {
            return null;
        }

        // Cache badge data selama 1 menit untuk real-time feeling
        return Cache::remember("badge.{$badgeKey}.{$user->id}", 60, function () use ($badgeKey, $user) {
            return match ($badgeKey) {
                'pending_check_ins' => $this->getPendingCheckIns($user),
                'pending_leave_requests' => $this->getPendingLeaveRequests($user),
                'pending_approvals' => $this->getPendingApprovals($user),
                'new_employees' => $this->getNewEmployees($user),
                'security_alerts' => $this->getSecurityAlerts($user),
                'system_alerts' => $this->getSystemAlerts($user),
                default => null,
            };
        });
    }

    /**
     * Badge data methods
     */
    private function getPendingCheckIns($user): ?array
    {
        // Implementasi berdasarkan business logic
        $count = 0; // Placeholder
        return $count > 0 ? ['count' => $count, 'variant' => 'warning'] : null;
    }

    private function getPendingLeaveRequests($user): ?array
    {
        $count = 0; // Placeholder - implementasi dengan model
        return $count > 0 ? ['count' => $count, 'variant' => 'info'] : null;
    }

    private function getPendingApprovals($user): ?array
    {
        $count = 0; // Placeholder - implementasi dengan model
        return $count > 0 ? ['count' => $count, 'variant' => 'danger'] : null;
    }

    private function getNewEmployees($user): ?array
    {
        $count = 0; // Placeholder - implementasi dengan model
        return $count > 0 ? ['count' => $count, 'variant' => 'success'] : null;
    }

    private function getSecurityAlerts($user): ?array
    {
        // Get unacknowledged security alerts count
        try {
            // Check if SecurityAlert model exists and get count
            if (class_exists('\App\Models\SecurityAlert')) {
                $count = \App\Models\SecurityAlert::where('acknowledged_at', null)->count();
                return $count > 0 ? ['count' => $count, 'variant' => 'danger'] : null;
            }
            
            // Fallback: Check audit logs for security events in last 24 hours
            if (class_exists('\App\Models\AuditLog')) {
                $securityEvents = \App\Models\AuditLog::where('event_type', 'security')
                    ->where('created_at', '>', now()->subHours(24))
                    ->whereJsonContains('tags', 'high_risk')
                    ->count();
                return $securityEvents > 0 ? ['count' => $securityEvents, 'variant' => 'danger'] : null;
            }
        } catch (\Exception $e) {
            // Fail silently for navigation
        }
        
        return null;
    }

    /**
     * Clear navigation cache
     */
    public function clearCache(?int $userId = null): void
    {
        if ($userId) {
            Cache::forget("navigation.main.{$userId}.*");
            Cache::forget("navigation.bottom.{$userId}.*");
        } else {
            // Clear all navigation cache
            Cache::flush(); // Atau implementasi yang lebih specific
        }
    }

    /**
     * Get user favorites dari database/cache
     */
    public function getUserFavorites($user): array
    {
        if (!$user) {
            return [];
        }

        return Cache::remember("favorites.{$user->id}", 600, function () use ($user) {
            // Implementasi untuk get user favorites dari database
            return [
                // ['name' => 'Quick Check-in', 'route' => 'attendance.check-in', 'icon' => 'clock'],
            ];
        });
    }

    /**
     * Search navigation items
     */
    public function searchNavigation(string $query, $user): array
    {
        $navigation = $this->getMainNavigation(user: $user);
        $results = [];

        foreach ($navigation as $item) {
            // Search dalam main items
            if (stripos($item['name'], $query) !== false) {
                $results[] = [
                    'name' => $item['name'],
                    'href' => $item['href'],
                    'icon' => $item['icon'],
                    'type' => 'main',
                ];
            }

            // Search dalam children
            foreach ($item['children'] as $child) {
                if (stripos($child['name'], $query) !== false) {
                    $results[] = [
                        'name' => $child['name'],
                        'href' => $child['href'],
                        'icon' => $item['icon'], // Use parent icon
                        'parent' => $item['name'],
                        'type' => 'child',
                    ];
                }
            }
        }

        return $results;
    }

    private function getSystemAlerts($user): ?array
    {
        // Get system alerts count (backup failures, performance issues, etc)
        $alertCount = 0;
        
        try {
            // Check for backup failures (last 24 hours)
            $backupAlerts = 0; // Could check backup logs
            
            // Check for performance issues
            $performanceAlerts = 0; // Could check performance metrics
            
            // Check for pending audit log cleanup
            if (class_exists('\App\Models\AuditLog')) {
                $oldLogs = \App\Models\AuditLog::where('created_at', '<', now()->subDays(30))->count();
                if ($oldLogs > 10000) {
                    $alertCount++;
                }
            }
            
            $alertCount += $backupAlerts + $performanceAlerts;
            
        } catch (\Exception $e) {
            // Fail silently for navigation
        }
        
        return $alertCount > 0 ? ['count' => $alertCount, 'variant' => 'warning'] : null;
    }
}