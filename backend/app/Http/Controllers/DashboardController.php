<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardDataService;
use App\Services\Dashboard\DashboardHealthService;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService,
        private DashboardDataService $dataService,
        private DashboardHealthService $healthService
    ) {
    }

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
            return view('pages.dashboard.modern', compact('dashboardData'));
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

        return view('pages.dashboard.modern', compact('dashboardData'));
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
                'system_health' => $this->healthService->calculateSystemHealthScore(),
                'present_today_change' => $this->healthService->calculatePresentTodayChange(),
                'attendance_rate_change' => $this->healthService->calculateAttendanceRateChange(),
                'pending_leaves_change' => $this->healthService->calculatePendingLeavesChange()
            ],
            'activities' => $this->dataService->getRecentActivities(),
            'schedule' => $this->dataService->getTodaySchedule($user),
            'system_status' => $this->healthService->getSystemStatus(),
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
     * Get attendance dashboard data for Vue components
     * (Consolidated from AttendanceDashboardController)
     */
    public function getAttendanceDashboard(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', now()->toDateString());
            $endDate = $request->get('end_date', now()->toDateString());
            $length = $request->get('length', 50);

            // Get today's attendance data
            $attendanceRecords = \App\Models\Attendance::with(['employee'])
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('check_in_time', 'desc')
                ->limit($length)
                ->get()
                ->map(function ($attendance) {
                    return [
                        'id' => $attendance->id,
                        'employee_id' => $attendance->employee_id,
                        'employee_name' => $attendance->employee ?
                            $attendance->employee->first_name . ' ' . $attendance->employee->last_name : 'Unknown',
                        'check_in_time' => $attendance->check_in_time,
                        'check_out_time' => $attendance->check_out_time,
                        'check_in_formatted' => $attendance->check_in_time ?
                            $attendance->check_in_time->format('H:i') : '-',
                        'check_out_formatted' => $attendance->check_out_time ?
                            $attendance->check_out_time->format('H:i') : '-',
                        'working_hours_formatted' => $attendance->working_hours ?
                            floor($attendance->working_hours) . 'j ' . (($attendance->working_hours - floor($attendance->working_hours)) * 60) . 'm' : '0j 0m',
                        'status' => $attendance->status,
                        'date' => $attendance->date,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $attendanceRecords,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance data',
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
                'statistics' => $stats, // For compatibility with JavaScript that expects response.statistics
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
