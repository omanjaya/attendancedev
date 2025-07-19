<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    /**
     * Display the main dashboard
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $role = $user->roles->first()->name ?? 'guru';
        $dashboardData = $this->dashboardService->getDashboardData($user);

        // Check if user wants modern dashboard
        if ($request->get('modern') === '1' || $request->get('view') === 'modern') {
            return view('pages.dashboard-modern', compact('dashboardData'));
        }

        // Route to role-specific dashboard view
        $view = match ($role) {
            'superadmin', 'super_admin', 'Super Admin' => 'pages.dashboard.super-admin',
            'admin', 'Admin' => 'pages.dashboard.admin',
            'kepala_sekolah', 'principal' => 'pages.dashboard.kepala-sekolah',
            'guru', 'teacher' => 'pages.dashboard.guru',
            'pegawai', 'staff' => 'pages.dashboard.pegawai',
            default => 'pages.dashboard.guru',
        };

        return view($view, compact('dashboardData'));
    }

    /**
     * Display the modern dashboard
     */
    public function modern(Request $request): View
    {
        $user = $request->user();
        $dashboardData = $this->dashboardService->getDashboardData($user);

        return view('pages.dashboard-modern', compact('dashboardData'));
    }

    /**
     * Get real-time dashboard data via API
     */
    public function getData(Request $request): JsonResponse
    {
        $user = $request->user();
        $dashboardData = $this->dashboardService->getDashboardData($user);

        // Transform data for modern dashboard API
        $modernData = [
            'stats' => [
                'present_today' => $dashboardData['realtime_status']['checked_in_today'] ?? 0,
                'total_employees' => $dashboardData['realtime_status']['total_employees'] ?? 0,
                'attendance_rate' => $dashboardData['realtime_status']['attendance_rate'] ?? 0,
                'pending_leaves' => $dashboardData['leave_management']['pending_requests'] ?? 0,
                'system_health' => 100, // Calculate actual system health
                'present_today_change' => 5, // Calculate from yesterday
                'attendance_rate_change' => 2, // Calculate from last period
                'pending_leaves_change' => -1, // Calculate from last period
            ],
            'activities' => $this->getRecentActivities(),
            'schedule' => $this->getTodaySchedule($user),
            'system_status' => $this->getSystemStatus(),
        ];

        return response()->json([
            'success' => true,
            'data' => $modernData,
            'stats' => $modernData['stats'],
            'activities' => $modernData['activities'],
            'schedule' => $modernData['schedule'],
            'system_status' => $modernData['system_status'],
            'role' => $user->roles->first()->name ?? 'guru',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get specific widget data
     */
    public function getWidgetData(Request $request, string $widget): JsonResponse
    {
        $user = $request->user();

        try {
            $data = match ($widget) {
                'realtime-status' => $this->dashboardService->getRealtimeAttendanceStatus(),
                'attendance-trends' => $this->dashboardService->getAttendanceTrends(30),
                'leave-management' => $this->dashboardService->getLeaveManagement(),
                'teacher-status' => $this->dashboardService->getTeacherAttendanceStatus(),
                default => throw new \InvalidArgumentException("Widget '{$widget}' not found")
            };

            return response()->json([
                'success' => true,
                'widget' => $widget,
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get chart data for modern dashboard
     */
    public function getChartData(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');
        
        try {
            $chartData = match ($period) {
                'week' => $this->dashboardService->getWeeklyAttendanceChart(),
                'month' => $this->dashboardService->getMonthlyAttendanceChart(),
                'quarter' => $this->dashboardService->getQuarterlyAttendanceChart(),
                default => $this->dashboardService->getWeeklyAttendanceChart(),
            };

            return response()->json([
                'success' => true,
                'period' => $period,
                'labels' => $chartData['labels'] ?? [],
                'datasets' => $chartData['datasets'] ?? [],
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get recent activities for dashboard
     */
    private function getRecentActivities(): array
    {
        // Mock data for now - replace with actual implementation
        return [
            [
                'id' => '1',
                'type' => 'check-in',
                'user' => ['name' => 'John Doe'],
                'description' => 'checked in',
                'details' => 'Face recognition confidence: 95%',
                'location' => 'Main Office',
                'device' => 'Mobile App',
                'status' => 'success',
                'confidence' => 0.95,
                'timestamp' => now()->subMinutes(5)->toISOString(),
            ],
            [
                'id' => '2',
                'type' => 'leave-request',
                'user' => ['name' => 'Jane Smith'],
                'description' => 'submitted a leave request',
                'details' => 'Annual leave for 3 days',
                'status' => 'pending',
                'timestamp' => now()->subMinutes(15)->toISOString(),
            ],
            [
                'id' => '3',
                'type' => 'check-out',
                'user' => ['name' => 'Mike Johnson'],
                'description' => 'checked out',
                'details' => 'Working hours: 8h 30m',
                'location' => 'Branch Office',
                'status' => 'success',
                'timestamp' => now()->subMinutes(25)->toISOString(),
            ],
        ];
    }

    /**
     * Get today's schedule for user
     */
    private function getTodaySchedule($user): array
    {
        // Mock data for now - replace with actual implementation
        return [
            [
                'id' => '1',
                'title' => 'Team Meeting',
                'description' => 'Weekly team sync and project updates',
                'start_time' => '09:00',
                'end_time' => '10:00',
                'location' => 'Conference Room A',
                'participants' => 8,
                'type' => 'Meeting',
                'status' => 'upcoming',
                'actions' => [
                    ['id' => 'join', 'label' => 'Join', 'icon' => 'play', 'type' => 'primary'],
                ],
            ],
            [
                'id' => '2',
                'title' => 'Code Review',
                'description' => 'Review pull requests and discuss improvements',
                'start_time' => '14:00',
                'end_time' => '15:30',
                'location' => 'Development Office',
                'participants' => 4,
                'type' => 'Review',
                'status' => 'ongoing',
                'progress' => 65,
                'actions' => [
                    ['id' => 'view', 'label' => 'View', 'icon' => 'view', 'type' => 'secondary'],
                ],
            ],
        ];
    }

    /**
     * Get system status information
     */
    private function getSystemStatus(): array
    {
        // Mock data for now - replace with actual implementation
        return [
            'overall' => 'healthy',
            'services' => [
                [
                    'id' => 'database',
                    'name' => 'Database',
                    'status' => 'operational',
                    'uptime' => 99.9,
                    'responseTime' => 12,
                ],
                [
                    'id' => 'face-api',
                    'name' => 'Face Recognition API',
                    'status' => 'operational',
                    'uptime' => 98.5,
                    'responseTime' => 245,
                ],
                [
                    'id' => 'storage',
                    'name' => 'File Storage',
                    'status' => 'operational',
                    'uptime' => 99.8,
                    'responseTime' => 45,
                ],
                [
                    'id' => 'notifications',
                    'name' => 'Notification Service',
                    'status' => 'degraded',
                    'uptime' => 95.2,
                    'responseTime' => 1250,
                ],
            ],
            'lastUpdated' => now()->toISOString(),
        ];
    }

    /**
     * Get attendance dashboard data for Vue components
     * (Consolidated from AttendanceDashboardController)
     */
    public function getAttendanceDashboard(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $dashboardData = $this->dashboardService->getDashboardData($user);

            return response()->json([
                'success' => true,
                'data' => $dashboardData,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get attendance stats for Vue components
     * (Consolidated from AttendanceDashboardController)
     */
    public function getAttendanceStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $stats = $this->dashboardService->getAttendanceStats($user);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
