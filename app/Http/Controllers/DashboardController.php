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
                'system_health' => $this->calculateSystemHealthScore(),
                'present_today_change' => $this->calculatePresentTodayChange(),
                'attendance_rate_change' => $this->calculateAttendanceRateChange(),
                'pending_leaves_change' => $this->calculatePendingLeavesChange()
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
        $recentAttendances = Attendance::with('employee.user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $activities = [];

        foreach ($recentAttendances as $attendance) {
            if ($attendance->check_in_time) {
                $activities[] = [
                    'id' => $attendance->id . '_checkin',
                    'type' => 'check-in',
                    'user' => ['name' => $attendance->employee->first_name . ' ' . $attendance->employee->last_name],
                    'description' => 'checked in',
                    'details' => $attendance->status === 'late' ? 'Late arrival' : 'On time',
                    'location' => $attendance->location ?? 'Unknown',
                    'device' => 'Attendance System',
                    'status' => $attendance->status === 'late' ? 'warning' : 'success',
                    'timestamp' => $attendance->check_in_time?->toISOString(),
                ];
            }

            if ($attendance->check_out_time) {
                $activities[] = [
                    'id' => $attendance->id . '_checkout',
                    'type' => 'check-out',
                    'user' => ['name' => $attendance->employee->first_name . ' ' . $attendance->employee->last_name],
                    'description' => 'checked out',
                    'details' => 'Working hours: ' . ($attendance->total_hours ? round($attendance->total_hours, 1) . 'h' : 'N/A'),
                    'location' => $attendance->location ?? 'Unknown',
                    'status' => 'success',
                    'timestamp' => $attendance->check_out_time?->toISOString(),
                ];
            }
        }

        // Add recent leave requests
        $recentLeaves = \App\Models\Leave::with('employee.user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($recentLeaves as $leave) {
            $activities[] = [
                'id' => 'leave_' . $leave->id,
                'type' => 'leave-request',
                'user' => ['name' => $leave->employee->first_name . ' ' . $leave->employee->last_name],
                'description' => 'submitted a leave request',
                'details' => ucfirst($leave->leave_type) . ' leave for ' . $leave->start_date->diffInDays($leave->end_date) + 1 . ' day(s)',
                'status' => $leave->status,
                'timestamp' => $leave->created_at->toISOString(),
            ];
        }

        // Sort by timestamp and return latest 10
        usort($activities, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($activities, 0, 10);
    }

    /**
     * Get today's schedule for user
     */
    private function getTodaySchedule($user): array
    {
        $today = now()->format('Y-m-d');
        $employee = $user->employee;
        
        if (!$employee) {
            return [];
        }

        // Get teaching schedules for today
        $schedules = [];
        
        // Check if employee schedules table exists and get today's schedule
        try {
            $employeeSchedules = \DB::table('employee_schedules')
                ->join('periods', 'employee_schedules.period_id', '=', 'periods.id')
                ->join('subjects', 'employee_schedules.subject_id', '=', 'subjects.id')
                ->where('employee_schedules.employee_id', $employee->id)
                ->where('employee_schedules.day_of_week', now()->dayOfWeek)
                ->select([
                    'employee_schedules.id',
                    'subjects.name as subject_name',
                    'periods.name as period_name',
                    'periods.start_time',
                    'periods.end_time',
                    'employee_schedules.class_name'
                ])
                ->orderBy('periods.start_time')
                ->get();

            foreach ($employeeSchedules as $schedule) {
                $currentTime = now()->format('H:i');
                $status = 'upcoming';
                
                if ($currentTime >= $schedule->start_time && $currentTime <= $schedule->end_time) {
                    $status = 'ongoing';
                } elseif ($currentTime > $schedule->end_time) {
                    $status = 'completed';
                }

                $schedules[] = [
                    'id' => $schedule->id,
                    'title' => $schedule->subject_name,
                    'description' => 'Class: ' . ($schedule->class_name ?? 'Not specified'),
                    'start_time' => \Carbon\Carbon::parse($schedule->start_time)->format('H:i'),
                    'end_time' => \Carbon\Carbon::parse($schedule->end_time)->format('H:i'),
                    'location' => $schedule->class_name ?? 'Classroom',
                    'period' => $schedule->period_name,
                    'type' => 'Teaching',
                    'status' => $status,
                    'actions' => [
                        ['id' => 'view', 'label' => 'View Details', 'icon' => 'eye', 'type' => 'secondary'],
                    ],
                ];
            }
        } catch (\Exception $e) {
            // If schedule tables don't exist, return empty array
            return [];
        }

        // Add meetings from events table if exists
        try {
            $meetings = \DB::table('events')
                ->where('date', $today)
                ->where(function($query) use ($employee) {
                    $query->whereNull('employee_id')
                          ->orWhere('employee_id', $employee->id);
                })
                ->get();

            foreach ($meetings as $meeting) {
                $schedules[] = [
                    'id' => 'meeting_' . $meeting->id,
                    'title' => $meeting->title,
                    'description' => $meeting->description ?? 'No description',
                    'start_time' => \Carbon\Carbon::parse($meeting->start_time)->format('H:i'),
                    'end_time' => \Carbon\Carbon::parse($meeting->end_time)->format('H:i'),
                    'location' => $meeting->location ?? 'TBD',
                    'type' => 'Meeting',
                    'status' => 'upcoming',
                    'actions' => [
                        ['id' => 'join', 'label' => 'Join', 'icon' => 'play', 'type' => 'primary'],
                    ],
                ];
            }
        } catch (\Exception $e) {
            // Events table doesn't exist, continue without meetings
        }

        // Sort by start time
        usort($schedules, function($a, $b) {
            return strcmp($a['start_time'], $b['start_time']);
        });

        return $schedules;
    }

    /**
     * Get system status information
     */
    private function getSystemStatus(): array
    {
        $services = [];
        
        // Check database
        $dbStatus = $this->checkDatabaseHealth();
        $services[] = [
            'id' => 'database',
            'name' => 'Database',
            'status' => $dbStatus['status'],
            'uptime' => $dbStatus['uptime'],
            'responseTime' => $dbStatus['responseTime'],
        ];
        
        // Check file storage
        $storageStatus = $this->checkStorageHealth();
        $services[] = [
            'id' => 'storage',
            'name' => 'File Storage',
            'status' => $storageStatus['status'],
            'uptime' => $storageStatus['uptime'],
            'responseTime' => $storageStatus['responseTime'],
        ];
        
        // Check face recognition (if enabled)
        $faceStatus = $this->checkFaceRecognitionHealth();
        $services[] = [
            'id' => 'face-recognition',
            'name' => 'Face Recognition',
            'status' => $faceStatus['status'],
            'uptime' => $faceStatus['uptime'],
            'responseTime' => $faceStatus['responseTime'],
        ];
        
        // Determine overall status
        $operationalCount = collect($services)->where('status', 'operational')->count();
        $overall = $operationalCount === count($services) ? 'healthy' : 
                  ($operationalCount > count($services) / 2 ? 'degraded' : 'critical');
        
        return [
            'overall' => $overall,
            'services' => $services,
            'lastUpdated' => now()->toISOString(),
        ];
    }
    
    private function checkDatabaseHealth(): array
    {
        $start = microtime(true);
        try {
            \DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000);
            
            return [
                'status' => 'operational',
                'uptime' => 99.9,
                'responseTime' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'uptime' => 0,
                'responseTime' => 0,
            ];
        }
    }
    
    private function checkStorageHealth(): array
    {
        $start = microtime(true);
        try {
            // Check if storage directory is writable
            $storagePath = storage_path('app');
            if (is_writable($storagePath)) {
                $responseTime = round((microtime(true) - $start) * 1000);
                return [
                    'status' => 'operational',
                    'uptime' => 99.5,
                    'responseTime' => $responseTime,
                ];
            }
        } catch (\Exception $e) {
            // Fall through to error case
        }
        
        return [
            'status' => 'degraded',
            'uptime' => 85.0,
            'responseTime' => 1000,
        ];
    }
    
    private function checkFaceRecognitionHealth(): array
    {
        // Simple check - count recent face recognition attempts
        $recentAttempts = \App\Models\Attendance::where('created_at', '>=', now()->subHour())
            ->whereNotNull('metadata')
            ->count();
            
        return [
            'status' => $recentAttempts > 0 ? 'operational' : 'idle',
            'uptime' => 95.0,
            'responseTime' => 250,
        ];
    }

    /**
     * Calculate system health score based on actual system metrics
     */
    private function calculateSystemHealthScore(): int
    {
        $score = 100;
        
        // Check database health
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            $score -= 30;
        }
        
        // Check if storage is writable
        if (!is_writable(storage_path('app'))) {
            $score -= 20;
        }
        
        // Check for recent errors in logs
        $recentErrors = \DB::table('audit_logs')
            ->where('level', 'error')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
            
        if ($recentErrors > 10) {
            $score -= 15;
        } elseif ($recentErrors > 5) {
            $score -= 10;
        }
        
        return max(0, $score);
    }

    /**
     * Calculate change in present employees from yesterday
     */
    private function calculatePresentTodayChange(): int
    {
        $today = \Carbon\Carbon::today();
        $yesterday = \Carbon\Carbon::yesterday();
        
        $presentToday = \App\Models\Attendance::whereDate('date', $today)
            ->whereNotNull('check_in_time')
            ->distinct('employee_id')
            ->count();
            
        $presentYesterday = \App\Models\Attendance::whereDate('date', $yesterday)
            ->whereNotNull('check_in_time')
            ->distinct('employee_id')
            ->count();
            
        return $presentToday - $presentYesterday;
    }

    /**
     * Calculate attendance rate change from last week
     */
    private function calculateAttendanceRateChange(): float
    {
        $thisWeek = \Carbon\Carbon::now()->startOfWeek();
        $lastWeek = \Carbon\Carbon::now()->subWeek()->startOfWeek();
        $lastWeekEnd = $lastWeek->copy()->endOfWeek();
        
        $totalEmployees = \App\Models\Employee::active()->count();
        
        if ($totalEmployees === 0) return 0;
        
        // This week's rate
        $thisWeekAttendance = \App\Models\Attendance::whereBetween('date', [$thisWeek, now()])
            ->whereNotNull('check_in_time')
            ->count();
        $thisWeekDays = $thisWeek->diffInDays(now()) + 1;
        $thisWeekRate = ($thisWeekAttendance / ($totalEmployees * $thisWeekDays)) * 100;
        
        // Last week's rate
        $lastWeekAttendance = \App\Models\Attendance::whereBetween('date', [$lastWeek, $lastWeekEnd])
            ->whereNotNull('check_in_time')
            ->count();
        $lastWeekRate = ($lastWeekAttendance / ($totalEmployees * 7)) * 100;
        
        return round($thisWeekRate - $lastWeekRate, 1);
    }

    /**
     * Calculate change in pending leaves from last week
     */
    private function calculatePendingLeavesChange(): int
    {
        $thisWeek = \App\Models\Leave::where('status', 'pending')
            ->where('created_at', '>=', \Carbon\Carbon::now()->startOfWeek())
            ->count();
            
        $lastWeek = \App\Models\Leave::where('status', 'pending')
            ->whereBetween('created_at', [
                \Carbon\Carbon::now()->subWeek()->startOfWeek(),
                \Carbon\Carbon::now()->subWeek()->endOfWeek()
            ])
            ->count();
            
        return $thisWeek - $lastWeek;
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
