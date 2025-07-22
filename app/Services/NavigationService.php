<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserDevice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class NavigationService
{
    /**
     * Cache duration for navigation data in minutes
     */
    const CACHE_DURATION = 60; // 1 hour

    /**
     * Get complete navigation structure for the authenticated user
     */
    public function getNavigation(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $cacheKey = "navigation_user_{$user->id}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user) {
            return $this->buildNavigationStructure($user);
        });
    }

    /**
     * Build the navigation structure based on user permissions
     */
    private function buildNavigationStructure(User $user): array
    {
        $role = $user->getRoleNames()->first();
        $cacheKey = "navigation_{$user->id}_{$role}";

        // Cache navigation for 30 minutes
        return cache()->remember($cacheKey, 1800, function () use ($user, $role) {
            // Build role-specific navigation
            return match ($role) {
                'superadmin', 'super_admin', 'Super Admin' => $this->buildSuperAdminNavigation($user),
                'admin', 'Admin' => $this->buildAdminNavigation($user),
                'kepala_sekolah', 'principal' => $this->buildKepalaSekolahNavigation($user),
                'guru', 'teacher' => $this->buildGuruNavigation($user),
                'pegawai', 'staff' => $this->buildPegawaiNavigation($user),
                default => $this->buildDefaultNavigation($user)
            };
        });
    }

    /**
     * Build Super Admin navigation - Full system access
     */
    private function buildSuperAdminNavigation(User $user): array
    {
        try {
            return [
                // Main Section
                [
                    'id' => 'main',
                    'name' => 'Main',
                    'icon' => 'home',
                    'type' => 'section',
                    'priority' => 1,
                    'children' => [
                        [
                            'id' => 'dashboard',
                            'name' => 'System Dashboard',
                            'icon' => 'computer-desktop',
                            'route' => 'dashboard',
                            'badge' => null,
                            'type' => 'single',
                            'priority' => 1,
                        ],
                    ],
                ],
                // Management Section
                [
                    'id' => 'management',
                    'name' => 'Management',
                    'icon' => 'briefcase',
                    'type' => 'section',
                    'priority' => 2,
                    'children' => [
                        [
                            'id' => 'employees',
                            'name' => 'Employee Management',
                            'icon' => 'users',
                            'type' => 'dropdown',
                            'priority' => 1,
                            'children' => [
                                [
                                    'id' => 'employees_list',
                                    'name' => 'Employee List',
                                    'icon' => 'user-group',
                                    'route' => 'employees.index',
                                    'badge' => null,
                                    'type' => 'single',
                                    'priority' => 1,
                                ],
                                [
                                    'id' => 'user_credentials',
                                    'name' => 'User Credentials',
                                    'icon' => 'key',
                                    'route' => 'employees.credentials.index',
                                    'badge' => null,
                                    'type' => 'single',
                                    'priority' => 2,
                                ],
                            ],
                        ],
                        [
                            'id' => 'attendance',
                            'name' => 'Attendance System',
                            'icon' => 'clock',
                            'route' => 'attendance.index',
                            'badge' => null, // Temporarily disable badges to test
                            'type' => 'single',
                            'priority' => 2,
                        ],
                        [
                            'id' => 'schedules',
                            'name' => 'Schedule Management',
                            'icon' => 'calendar',
                            'route' => 'schedule-management.index',
                            'badge' => null,
                            'type' => 'single',
                            'priority' => 3,
                        ],
                        [
                            'id' => 'locations',
                            'name' => 'Location Management',
                            'icon' => 'map-pin',
                            'route' => 'locations.index',
                            'badge' => null,
                            'type' => 'single',
                            'priority' => 3.5,
                        ],
                        [
                            'id' => 'leave',
                            'name' => 'Leave Management',
                            'icon' => 'calendar-days',
                            'route' => 'leave.index',
                            'badge' => null, // Temporarily disable badges to test
                            'type' => 'single',
                            'priority' => 4,
                        ],
                        [
                            'id' => 'payroll',
                            'name' => 'Payroll Management',
                            'icon' => 'banknotes',
                            'route' => 'payroll.index',
                            'badge' => null, // Temporarily disable badges to test
                            'type' => 'single',
                            'priority' => 5,
                        ],
                    ],
                ],
                // System Administration
                [
                    'id' => 'system',
                    'name' => 'System',
                    'icon' => 'cog-6-tooth',
                    'type' => 'section',
                    'priority' => 3,
                    'children' => [
                        [
                            'id' => 'reports',
                            'name' => 'Reports & Analytics',
                            'icon' => 'chart-bar',
                            'route' => 'reports.index',
                            'badge' => null,
                            'type' => 'single',
                            'priority' => 1,
                        ],
                        [
                            'id' => 'settings',
                            'name' => 'System Settings',
                            'icon' => 'cog-6-tooth',
                            'route' => 'system.settings',
                            'badge' => null,
                            'type' => 'single',
                            'priority' => 2,
                        ],
                    ],
                ],
                // Profile Section
                [
                    'id' => 'profile_section',
                    'name' => 'Profile',
                    'icon' => 'user-circle',
                    'type' => 'section',
                    'priority' => 4,
                    'children' => [
                        [
                            'id' => 'profile',
                            'name' => 'My Profile',
                            'icon' => 'user-circle',
                            'route' => 'profile.edit',
                            'badge' => null,
                            'type' => 'single',
                            'priority' => 1,
                        ],
                    ],
                ],
            ];
        } catch (\Exception $e) {
            \Log::error('Error building SuperAdmin navigation', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
            // Fallback to simple navigation
            return $this->buildDefaultNavigation($user);
        }
    }

    /**
     * Build Admin navigation - School management focus
     */
    private function buildAdminNavigation(User $user): array
    {
        return [
            // Dashboard
            [
                'id' => 'dashboard',
                'name' => 'Admin Dashboard',
                'icon' => 'home',
                'route' => 'dashboard',
                'badge' => null,
                'type' => 'single',
                'priority' => 1,
                'section' => 'main',
            ],
            // Employee Management
            [
                'id' => 'employees',
                'name' => 'Employee Management',
                'icon' => 'users',
                'badge' => $this->getEmployeeBadge(),
                'type' => 'dropdown',
                'priority' => 2,
                'section' => 'management',
                'children' => [
                    [
                        'id' => 'employees_list',
                        'name' => 'Employee List',
                        'icon' => 'user-group',
                        'route' => 'employees.index',
                        'badge' => null,
                        'type' => 'single',
                        'priority' => 1,
                        'section' => 'management',
                    ],
                    [
                        'id' => 'user_credentials',
                        'name' => 'User Credentials',
                        'icon' => 'key',
                        'route' => 'employees.credentials.index',
                        'badge' => null,
                        'type' => 'single',
                        'priority' => 2,
                        'section' => 'management',
                    ],
                ],
            ],
            // Attendance Management
            [
                'id' => 'attendance',
                'name' => 'Attendance Management',
                'icon' => 'clock',
                'route' => 'attendance.index',
                'badge' => $this->getAttendanceBadge(),
                'type' => 'single',
                'priority' => 3,
                'section' => 'management',
            ],
            // Quick Check-in
            [
                'id' => 'quick_checkin',
                'name' => 'Quick Check-in',
                'icon' => 'finger-print',
                'route' => 'attendance.check-in',
                'badge' => null,
                'type' => 'single',
                'priority' => 4,
                'section' => 'actions',
                'highlight' => true,
            ],
            // Schedule Management
            [
                'id' => 'schedules',
                'name' => 'Schedule Management',
                'icon' => 'calendar',
                'route' => 'schedule-management.index',
                'badge' => null,
                'type' => 'single',
                'priority' => 5,
                'section' => 'management',
            ],
            // Location Management
            [
                'id' => 'locations',
                'name' => 'Location Management',
                'icon' => 'map-pin',
                'route' => 'locations.index',
                'badge' => null,
                'type' => 'single',
                'priority' => 5.5,
                'section' => 'management',
            ],
            // Leave Management
            [
                'id' => 'leave',
                'name' => 'Leave Management',
                'icon' => 'calendar-days',
                'route' => 'leave.index',
                'badge' => $this->getLeaveBadge(),
                'type' => 'single',
                'priority' => 6,
                'section' => 'management',
            ],
            // Payroll Management
            [
                'id' => 'payroll',
                'name' => 'Payroll Management',
                'icon' => 'banknotes',
                'route' => 'payroll.index',
                'badge' => $this->getPayrollBadge(),
                'type' => 'single',
                'priority' => 7,
                'section' => 'management',
            ],
            // Reports
            [
                'id' => 'reports',
                'name' => 'Reports & Analytics',
                'icon' => 'chart-bar',
                'route' => 'reports.index',
                'badge' => null,
                'type' => 'single',
                'priority' => 8,
                'section' => 'analytics',
            ],
            // Settings (Limited)
            [
                'id' => 'settings',
                'name' => 'Settings',
                'icon' => 'cog-6-tooth',
                'route' => 'system.settings',
                'badge' => null,
                'type' => 'single',
                'priority' => 9,
                'section' => 'system',
            ],
            // Profile
            [
                'id' => 'profile',
                'name' => 'Profile',
                'icon' => 'user-circle',
                'route' => 'profile.edit',
                'badge' => null,
                'type' => 'single',
                'priority' => 10,
                'section' => 'profile',
            ],
        ];
    }

    /**
     * Build Kepala Sekolah navigation - Strategic oversight
     */
    private function buildKepalaSekolahNavigation(User $user): array
    {
        return [
            // Dashboard
            [
                'id' => 'dashboard',
                'name' => 'Executive Dashboard',
                'icon' => 'home',
                'route' => 'dashboard',
                'badge' => null,
                'type' => 'single',
                'priority' => 1,
                'section' => 'main',
            ],
            // School Analytics
            [
                'id' => 'analytics',
                'name' => 'School Analytics',
                'icon' => 'chart-bar',
                'route' => 'reports.index',
                'badge' => null,
                'type' => 'single',
                'priority' => 2,
                'section' => 'analytics',
            ],
            // Staff Overview
            [
                'id' => 'staff_overview',
                'name' => 'Staff Overview',
                'icon' => 'users',
                'route' => 'employees.index',
                'badge' => $this->getEmployeeBadge(),
                'type' => 'single',
                'priority' => 3,
                'section' => 'management',
            ],
            // Attendance Overview
            [
                'id' => 'attendance_overview',
                'name' => 'Attendance Overview',
                'icon' => 'clock',
                'route' => 'attendance.index',
                'badge' => $this->getAttendanceBadge(),
                'type' => 'single',
                'priority' => 4,
                'section' => 'management',
            ],
            // Leave Approvals
            [
                'id' => 'leave_approvals',
                'name' => 'Leave Approvals',
                'icon' => 'calendar-days',
                'route' => 'leave.index',
                'badge' => $this->getLeaveBadge(),
                'type' => 'single',
                'priority' => 5,
                'section' => 'management',
            ],
            // Schedule Overview
            [
                'id' => 'schedule_overview',
                'name' => 'Schedule Overview',
                'icon' => 'calendar',
                'route' => 'schedule-management.index',
                'badge' => null,
                'type' => 'single',
                'priority' => 6,
                'section' => 'management',
            ],
            // Payroll Overview
            [
                'id' => 'payroll_overview',
                'name' => 'Payroll Overview',
                'icon' => 'banknotes',
                'route' => 'payroll.index',
                'badge' => $this->getPayrollBadge(),
                'type' => 'single',
                'priority' => 7,
                'section' => 'management',
            ],
            // Personal Check-in
            [
                'id' => 'my_attendance',
                'name' => 'My Attendance',
                'icon' => 'finger-print',
                'route' => 'attendance.check-in',
                'badge' => null,
                'type' => 'single',
                'priority' => 8,
                'section' => 'personal',
                'highlight' => true,
            ],
            // Profile
            [
                'id' => 'profile',
                'name' => 'Profile',
                'icon' => 'user-circle',
                'route' => 'profile.edit',
                'badge' => null,
                'type' => 'single',
                'priority' => 9,
                'section' => 'profile',
            ],
        ];
    }

    /**
     * Build Guru navigation - Teaching focused
     */
    private function buildGuruNavigation(User $user): array
    {
        return [
            // Dashboard
            [
                'id' => 'dashboard',
                'name' => 'Teacher Dashboard',
                'icon' => 'home',
                'route' => 'dashboard',
                'badge' => null,
                'type' => 'single',
                'priority' => 1,
                'section' => 'main',
            ],
            // My Attendance
            [
                'id' => 'my_attendance',
                'name' => 'My Attendance',
                'icon' => 'finger-print',
                'route' => 'attendance.check-in',
                'badge' => null,
                'type' => 'single',
                'priority' => 2,
                'section' => 'personal',
                'highlight' => true,
            ],
            // My Schedule
            [
                'id' => 'my_schedule',
                'name' => 'My Schedule',
                'icon' => 'calendar',
                'route' => 'schedule-management.index',
                'badge' => null,
                'type' => 'single',
                'priority' => 3,
                'section' => 'personal',
            ],
            // My Leave
            [
                'id' => 'my_leave',
                'name' => 'My Leave',
                'icon' => 'calendar-days',
                'route' => 'leave.index',
                'badge' => null,
                'type' => 'single',
                'priority' => 4,
                'section' => 'personal',
            ],
            // My Payroll
            [
                'id' => 'my_payroll',
                'name' => 'My Payroll',
                'icon' => 'banknotes',
                'route' => 'payroll.index',
                'badge' => null,
                'type' => 'single',
                'priority' => 5,
                'section' => 'personal',
            ],
            // Attendance Records
            [
                'id' => 'attendance_records',
                'name' => 'Attendance Records',
                'icon' => 'clock',
                'route' => 'attendance.index',
                'badge' => null,
                'type' => 'single',
                'priority' => 6,
                'section' => 'records',
            ],
            // Reports
            [
                'id' => 'reports',
                'name' => 'Reports',
                'icon' => 'chart-bar',
                'route' => 'reports.index',
                'badge' => null,
                'type' => 'single',
                'priority' => 7,
                'section' => 'records',
            ],
            // Profile
            [
                'id' => 'profile',
                'name' => 'Profile',
                'icon' => 'user-circle',
                'route' => 'profile.edit',
                'badge' => null,
                'type' => 'single',
                'priority' => 8,
                'section' => 'profile',
            ],
        ];
    }

    /**
     * Build Pegawai navigation - Employee focused
     */
    private function buildPegawaiNavigation(User $user): array
    {
        return [
            // Dashboard
            [
                'id' => 'dashboard',
                'name' => 'Employee Dashboard',
                'icon' => 'home',
                'route' => 'dashboard',
                'badge' => null,
                'type' => 'single',
                'priority' => 1,
                'section' => 'main',
            ],
            // My Attendance
            [
                'id' => 'my_attendance',
                'name' => 'My Attendance',
                'icon' => 'finger-print',
                'route' => 'attendance.check-in',
                'badge' => null,
                'type' => 'single',
                'priority' => 2,
                'section' => 'personal',
                'highlight' => true,
            ],
            // My Schedule
            [
                'id' => 'my_schedule',
                'name' => 'My Schedule',
                'icon' => 'calendar',
                'route' => 'schedule-management.index',
                'badge' => null,
                'type' => 'single',
                'priority' => 3,
                'section' => 'personal',
            ],
            // My Leave
            [
                'id' => 'my_leave',
                'name' => 'My Leave',
                'icon' => 'calendar-days',
                'route' => 'leave.index',
                'badge' => null,
                'type' => 'single',
                'priority' => 4,
                'section' => 'personal',
            ],
            // My Payroll
            [
                'id' => 'my_payroll',
                'name' => 'My Payroll',
                'icon' => 'banknotes',
                'route' => 'payroll.index',
                'badge' => null,
                'type' => 'single',
                'priority' => 5,
                'section' => 'personal',
            ],
            // Attendance History
            [
                'id' => 'attendance_history',
                'name' => 'Attendance History',
                'icon' => 'clock',
                'route' => 'attendance.index',
                'badge' => null,
                'type' => 'single',
                'priority' => 6,
                'section' => 'records',
            ],
            // Profile
            [
                'id' => 'profile',
                'name' => 'Profile',
                'icon' => 'user-circle',
                'route' => 'profile.edit',
                'badge' => null,
                'type' => 'single',
                'priority' => 7,
                'section' => 'profile',
            ],
        ];
    }

    /**
     * Build default navigation for unknown roles
     */
    private function buildDefaultNavigation(User $user): array
    {
        return [
            [
                'id' => 'dashboard',
                'name' => 'Dashboard',
                'icon' => 'home',
                'route' => 'dashboard',
                'badge' => null,
                'type' => 'single',
                'priority' => 1,
                'section' => 'main',
            ],
            [
                'id' => 'profile',
                'name' => 'Profile',
                'icon' => 'user-circle',
                'route' => 'profile.edit',
                'badge' => null,
                'type' => 'single',
                'priority' => 2,
                'section' => 'profile',
            ],
        ];
    }

    /**
     * Get employee badge count
     */
    private function getEmployeeBadge(): ?array
    {
        try {
            $user = Auth::user();

            if (! $user || ! $user->can('view_employees')) {
                return null;
            }

            // New employees this month
            $newEmployees = \App\Models\Employee::where('created_at', '>=', Carbon::now()->startOfMonth())
                ->count();

            if ($newEmployees > 0) {
                return [
                    'count' => $newEmployees,
                    'type' => 'info',
                    'label' => __('employees.statistics.new_this_month'),
                ];
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Error getting employee badge', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Get attendance badge count
     */
    private function getAttendanceBadge(): ?array
    {
        $user = Auth::user();

        if (! $user->can('view_attendance_own')) {
            return null;
        }

        // Pending attendance reviews
        $pendingReviews = \App\Models\Attendance::whereNull('reviewed_at')
            ->where('created_at', '>=', Carbon::now()->startOfWeek())
            ->count();

        if ($pendingReviews > 0) {
            return [
                'count' => $pendingReviews,
                'type' => 'warning',
                'label' => 'Pending reviews',
            ];
        }

        return null;
    }

    /**
     * Get leave badge count
     */
    private function getLeaveBadge(): ?array
    {
        $user = Auth::user();

        if (! $user->can('view_leave_all')) {
            return null;
        }

        // Pending leave requests
        $pendingLeaves = \App\Models\Leave::where('status', 'pending')
            ->count();

        if ($pendingLeaves > 0) {
            return [
                'count' => $pendingLeaves,
                'type' => 'info',
                'label' => 'Pending approvals',
            ];
        }

        return null;
    }

    /**
     * Get security badge count
     */
    private function getSecurityBadge(): ?array
    {
        $user = Auth::user();

        // High-risk security events in last 7 days
        $securityEvents = AuditLog::where('user_id', $user->id)
            ->where('risk_level', 'high')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        if ($securityEvents > 0) {
            return [
                'count' => $securityEvents,
                'type' => 'danger',
                'label' => 'Security alerts',
            ];
        }

        return null;
    }

    /**
     * Get devices badge count
     */
    private function getDevicesBadge(): ?array
    {
        $user = Auth::user();

        // New untrusted devices
        $newDevices = UserDevice::where('user_id', $user->id)
            ->where('is_trusted', false)
            ->count();

        if ($newDevices > 0) {
            return [
                'count' => $newDevices,
                'type' => 'warning',
                'label' => 'New devices',
            ];
        }

        return null;
    }

    /**
     * Get notifications badge count
     */
    private function getNotificationsBadge(): ?array
    {
        $user = Auth::user();

        // Unread security notifications
        $unreadNotifications = $user->unreadNotifications()
            ->whereIn('type', [
                'App\Notifications\NewDeviceLogin',
                'App\Notifications\SecurityAlert',
                'App\Notifications\SuspiciousActivity',
            ])
            ->count();

        if ($unreadNotifications > 0) {
            return [
                'count' => $unreadNotifications,
                'type' => 'info',
                'label' => 'Unread notifications',
            ];
        }

        return null;
    }

    /**
     * Check if current route is active for navigation item
     */
    public function isActiveRoute(array $navigationItem): bool
    {
        if (! isset($navigationItem['route'])) {
            return false;
        }

        $routeName = $navigationItem['route'];

        // Check exact match
        if (Route::currentRouteName() === $routeName) {
            return true;
        }

        // Check pattern match
        $patterns = [
            'dashboard' => ['dashboard*'],
            'attendance.check-in' => ['attendance.check-in*'],
            'employees.index' => ['employees*'],
            'employees.credentials.index' => ['employees.credentials*'],
            'departments.index' => ['departments*'],
            'positions.index' => ['positions*'],
            'locations.index' => ['locations*'],
            'schedule-management.index' => ['schedules*'],
            'holidays.index' => ['holidays*'],
            'attendance.index' => ['attendance.index*'],
            'attendance.realtime' => ['attendance.realtime*'],
            'attendance.manual' => ['attendance.manual*'],
            'attendance.overtime' => ['attendance.overtime*'],
            'leave.index' => ['leave.index*'],
            'leave.approvals' => ['leave.approvals*'],
            'leave.balance' => ['leave.balance*'],
            'leave.types' => ['leave.types*'],
            'payroll.index' => ['payroll.index*'],
            'payroll.bulk-calculate' => ['payroll.bulk-calculate*'],
            'payroll.summary' => ['payroll.summary*'],
            'payroll.reports' => ['payroll.reports*'],
            'reports.attendance' => ['reports.attendance*'],
            'reports.leave' => ['reports.leave*'],
            'analytics.dashboard' => ['analytics*'],
            'users.index' => ['users*'],
            'roles.index' => ['roles*'],
            'audit.logs' => ['audit*'],
            'settings.index' => ['settings*'],
            'backups.index' => ['backups*'],
            'security.dashboard' => ['security.dashboard*'],
            'security.devices' => ['security.devices*'],
            'security.notifications' => ['security.notifications*'],
            'security.sessions' => ['security.sessions*'],
            'security.events' => ['security.events*'],
            'profile.edit' => ['profile*'],
        ];

        if (isset($patterns[$routeName])) {
            foreach ($patterns[$routeName] as $pattern) {
                if (request()->routeIs($pattern)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get mobile navigation items (simplified for mobile)
     */
    public function getMobileNavigation(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $role = $user->getRoleNames()->first();

        // Get simplified mobile navigation based on role
        $mobileNav = match ($role) {
            'superadmin', 'super_admin' => [
                ['id' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'priority' => 1],
                ['id' => 'users', 'name' => 'Users', 'icon' => 'users', 'route' => 'users.index', 'priority' => 2],
                ['id' => 'attendance', 'name' => 'Attendance', 'icon' => 'clock', 'route' => 'attendance.index', 'priority' => 3],
                ['id' => 'security', 'name' => 'Security', 'icon' => 'shield-check', 'route' => 'security.dashboard', 'priority' => 4],
                ['id' => 'profile', 'name' => 'Profile', 'icon' => 'user', 'route' => 'profile.edit', 'priority' => 5],
            ],
            'admin' => [
                ['id' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'priority' => 1],
                ['id' => 'employees', 'name' => 'Employees', 'icon' => 'users', 'route' => 'employees.index', 'priority' => 2],
                ['id' => 'attendance', 'name' => 'Attendance', 'icon' => 'clock', 'route' => 'attendance.index', 'priority' => 3],
                ['id' => 'quick_checkin', 'name' => 'Check-in', 'icon' => 'finger-print', 'route' => 'attendance.check-in', 'priority' => 4, 'highlight' => true],
                ['id' => 'profile', 'name' => 'Profile', 'icon' => 'user', 'route' => 'profile.edit', 'priority' => 5],
            ],
            'kepala_sekolah' => [
                ['id' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'priority' => 1],
                ['id' => 'analytics', 'name' => 'Analytics', 'icon' => 'chart-bar', 'route' => 'reports.index', 'priority' => 2],
                ['id' => 'attendance', 'name' => 'Attendance', 'icon' => 'clock', 'route' => 'attendance.index', 'priority' => 3],
                ['id' => 'my_attendance', 'name' => 'My Check-in', 'icon' => 'finger-print', 'route' => 'attendance.check-in', 'priority' => 4, 'highlight' => true],
                ['id' => 'profile', 'name' => 'Profile', 'icon' => 'user', 'route' => 'profile.edit', 'priority' => 5],
            ],
            'guru', 'teacher' => [
                ['id' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'priority' => 1],
                ['id' => 'my_attendance', 'name' => 'Check-in', 'icon' => 'finger-print', 'route' => 'attendance.check-in', 'priority' => 2, 'highlight' => true],
                ['id' => 'my_schedule', 'name' => 'Schedule', 'icon' => 'calendar', 'route' => 'schedule-management.index', 'priority' => 3],
                ['id' => 'my_leave', 'name' => 'Leave', 'icon' => 'calendar-days', 'route' => 'leave.index', 'priority' => 4],
                ['id' => 'profile', 'name' => 'Profile', 'icon' => 'user', 'route' => 'profile.edit', 'priority' => 5],
            ],
            'pegawai', 'staff' => [
                ['id' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'priority' => 1],
                ['id' => 'my_attendance', 'name' => 'Check-in', 'icon' => 'finger-print', 'route' => 'attendance.check-in', 'priority' => 2, 'highlight' => true],
                ['id' => 'my_schedule', 'name' => 'Schedule', 'icon' => 'calendar', 'route' => 'schedule-management.index', 'priority' => 3],
                ['id' => 'my_leave', 'name' => 'Leave', 'icon' => 'calendar-days', 'route' => 'leave.index', 'priority' => 4],
                ['id' => 'profile', 'name' => 'Profile', 'icon' => 'user', 'route' => 'profile.edit', 'priority' => 5],
            ],
            default => [
                ['id' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'priority' => 1],
                ['id' => 'profile', 'name' => 'Profile', 'icon' => 'user', 'route' => 'profile.edit', 'priority' => 2],
            ]
        };

        // Add badges and proper structure
        return array_map(function ($item) {
            $item['badge'] = null;
            $item['type'] = $item['highlight'] ?? false ? 'primary' : 'single';

            return $item;
        }, $mobileNav);
    }

    /**
     * Clear navigation cache for user
     */
    public function clearCache(?User $user = null): void
    {
        $user = $user ?? Auth::user();

        if ($user) {
            Cache::forget("navigation_user_{$user->id}");
        }
    }

    /**
     * Get live attendance badge count
     */
    private function getLiveAttendanceBadge(): ?array
    {
        $user = Auth::user();

        if (! $user->can('view_attendance_own')) {
            return null;
        }

        // Currently active employees (checked in but not out)
        $activeEmployees = \App\Models\Attendance::whereDate('date', Carbon::today())
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->count();

        if ($activeEmployees > 0) {
            return [
                'count' => $activeEmployees,
                'type' => 'success',
                'label' => 'Currently active',
            ];
        }

        return null;
    }

    /**
     * Get overtime badge count
     */
    private function getOvertimeBadge(): ?array
    {
        $user = Auth::user();

        if (! $user->can('view_attendance_own')) {
            return null;
        }

        // Pending overtime approvals this week
        $overtimeRequests = \App\Models\Attendance::where('overtime_status', 'pending')
            ->whereBetween('date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->count();

        if ($overtimeRequests > 0) {
            return [
                'count' => $overtimeRequests,
                'type' => 'warning',
                'label' => 'Pending approvals',
            ];
        }

        return null;
    }

    /**
     * Get pending approvals badge count
     */
    private function getPendingApprovalsBadge(): ?array
    {
        try {
            $user = Auth::user();

            if (! $user || ! $user->can('approve_leave')) {
                return null;
            }

            // Pending leave approvals - simplified to count all pending leaves for now
            // In a real system, this would be filtered by manager-employee relationships
            $pendingApprovals = \App\Models\Leave::where('status', 'pending')->count();

            if ($pendingApprovals > 0) {
                return [
                    'count' => $pendingApprovals,
                    'type' => 'warning',
                    'label' => 'Pending approvals',
                ];
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Error getting pending approvals badge', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Get payroll badge count
     */
    private function getPayrollBadge(): ?array
    {
        $user = Auth::user();

        if (! $user->can('view_payroll_own') && ! $user->can('view_all_payroll')) {
            return null;
        }

        // Pending payroll for this month
        $pendingPayroll = \App\Models\Payroll::where('status', 'draft')
            ->where('period_month', Carbon::now()->month)
            ->where('period_year', Carbon::now()->year)
            ->count();

        if ($pendingPayroll > 0) {
            return [
                'count' => $pendingPayroll,
                'type' => 'info',
                'label' => 'Pending payroll',
            ];
        }

        return null;
    }

    /**
     * Get users badge count
     */
    private function getUsersBadge(): ?array
    {
        $user = Auth::user();

        if (! $user->can('manage_users')) {
            return null;
        }

        // New users this week
        $newUsers = User::where('created_at', '>=', Carbon::now()->startOfWeek())
            ->count();

        if ($newUsers > 0) {
            return [
                'count' => $newUsers,
                'type' => 'info',
                'label' => 'New users',
            ];
        }

        return null;
    }

    /**
     * Get backup badge count
     */
    private function getBackupBadge(): ?array
    {
        $user = Auth::user();

        if (! $user->can('manage_backups')) {
            return null;
        }

        // Check if last backup is older than 7 days
        $lastBackup = Cache::get('last_backup_date');

        if (! $lastBackup || Carbon::parse($lastBackup)->lt(Carbon::now()->subDays(7))) {
            return [
                'count' => 1,
                'type' => 'danger',
                'label' => 'Backup needed',
            ];
        }

        return null;
    }

    /**
     * Get security events badge count
     */
    private function getSecurityEventsBadge(): ?array
    {
        $user = Auth::user();

        if (! $user->can('view_security_logs')) {
            return null;
        }

        // High-risk security events in last 24 hours
        $recentEvents = AuditLog::where('risk_level', 'high')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->count();

        if ($recentEvents > 0) {
            return [
                'count' => $recentEvents,
                'type' => 'danger',
                'label' => 'High-risk events',
            ];
        }

        return null;
    }

    /**
     * Clear navigation cache for all users
     */
    public function clearAllCache(): void
    {
        Cache::flush();
    }

    /**
     * Filter navigation items based on user permissions
     */
    private function filterNavigationByPermissions(array $navigation, User $user): array
    {
        return array_filter(array_map(function ($item) use ($user) {
            // For section items with children, filter the children first
            if (isset($item['children']) && is_array($item['children'])) {
                $item['children'] = $this->filterNavigationByPermissions($item['children'], $user);

                // Hide section if no children remain after filtering
                if (empty($item['children'])) {
                    return null;
                }

                return $item;
            }

            // For regular items, check permissions if specified
            if (isset($item['permission']) && $item['permission'] !== null) {
                return $user->can($item['permission']) ? $item : null;
            }

            // Role-based navigation is pre-filtered, so show all items
            return $item;
        }, $navigation));
    }
}
