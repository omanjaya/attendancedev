<?php

namespace App\Http\Controllers\Api;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Holiday;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceReportExport;
use Illuminate\Support\Str;

class ReportsApiController extends BaseApiController
{
    public function data(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $data = [
            'attendance_summary' => $this->getAttendanceSummary($startDate, $endDate),
            'monthly_trend' => $this->getMonthlyTrend($startDate, $endDate),
            'department_breakdown' => $this->getDepartmentBreakdown($startDate, $endDate),
            'leave_distribution' => $this->getLeaveDistribution($startDate, $endDate),
        ];

        return $this->apiResponse($data, 'Report data retrieved');
    }

    public function dashboard(Request $request)
    {
        $today = now()->format('Y-m-d');
        
        // 1. Summary
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('is_active', true)->count();
        $inactiveEmployees = Employee::where('is_active', false)->count();
        
        // Attendance today
        $attendanceToday = Attendance::forDate($today)->get();
        $present = $attendanceToday->where('status', 'present')->count();
        $late = $attendanceToday->where('status', 'late')->count();
        $absent = $attendanceToday->where('status', 'absent')->count();
        
        // On leave today
        $onLeaveToday = Leave::where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();
            
        // Pending leaves
        $pendingLeaves = Leave::where('status', 'pending')->count();
        
        // Upcoming holidays (next 30 days)
        $upcomingHolidays = Holiday::where('date', '>=', $today)
            ->where('date', '<=', now()->addDays(30))
            ->count();

        // Department stats for summary
        $departmentStats = $this->getDepartmentBreakdown(now()->startOfMonth(), now());
        $byDepartment = [];
        foreach($departmentStats as $stat) {
            $byDepartment[$stat['department']] = $stat['count'];
        }
        
        // Leave stats
        $leaveStats = [
            'pending' => $pendingLeaves,
            'approved' => Leave::where('status', 'approved')->count(),
            'rejected' => Leave::where('status', 'rejected')->count(),
        ];

        // Schedule stats
        $scheduleStats = [
            'active' => \App\Models\MonthlySchedule::where('is_active', true)->count(),
            'draft' => \App\Models\MonthlySchedule::where('is_active', false)->count(),
            'upcoming' => \App\Models\MonthlySchedule::where('month', '>', now()->month)->count(),
        ];

        // Payroll stats (Mock for now)
        $payrollStats = [
            'pending' => 5,
            'processed' => 145,
            'total' => 2450000,
        ];

        $summary = [
            'attendance' => [
                'total_employees' => $activeEmployees,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'on_leave' => $onLeaveToday,
                'today' => $present + $late + $absent, // Total attendance records today
                'attendance_rate' => $activeEmployees > 0 ? round(($present + $late) / $activeEmployees * 100, 1) : 0,
            ],
            'employees' => [
                'total' => $totalEmployees,
                'active' => $activeEmployees,
                'inactive' => $inactiveEmployees,
                'on_leave' => $onLeaveToday,
                'by_department' => $byDepartment,
            ],
            'leave' => $leaveStats,
            'schedules' => $scheduleStats,
            'payroll' => $payrollStats,
            'pending_leaves' => $pendingLeaves,
            'upcoming_holidays' => $upcomingHolidays,
        ];

        // 2. Recent Activity
        $recentActivity = [];
        
        // Recent check-ins/outs
        $recentAttendances = Attendance::with('employee')
            ->forDate($today)
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();
            
        foreach($recentAttendances as $att) {
            if ($att->check_in_time && $att->updated_at->diffInMinutes($att->check_in_time) < 5) {
                 $recentActivity[] = [
                    'id' => $att->id,
                    'type' => 'check_in',
                    'employee_id' => $att->employee_id,
                    'employee_name' => $att->employee->full_name ?? 'Unknown',
                    'description' => 'Check-in' . ($att->status == 'late' ? ' (Late)' : ''),
                    'timestamp' => $att->check_in_time->toIsoString(),
                 ];
            } elseif ($att->check_out_time) {
                 $recentActivity[] = [
                    'id' => $att->id,
                    'type' => 'check_out',
                    'employee_id' => $att->employee_id,
                    'employee_name' => $att->employee->full_name ?? 'Unknown',
                    'description' => 'Check-out',
                    'timestamp' => $att->check_out_time->toIsoString(),
                 ];
            }
        }
        
        // Recent leaves
        $recentLeaves = Leave::with('employee')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            
        foreach($recentLeaves as $leave) {
             $recentActivity[] = [
                'id' => $leave->id,
                'type' => 'leave_request',
                'employee_id' => $leave->employee_id,
                'employee_name' => $leave->employee->full_name ?? 'Unknown',
                'description' => 'Leave Request: ' . $leave->reason,
                'timestamp' => $leave->created_at->toIsoString(),
             ];
        }
        
        // Sort and slice
        usort($recentActivity, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        $recentActivity = array_slice($recentActivity, 0, 10);

        // 3. Attendance Trends (Last 7 days)
        $attendanceTrends = [];
        for($i=6; $i>=0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $stats = Attendance::forDate($dateStr)
                ->selectRaw("
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
                ")->first();
                
            $onLeave = Leave::where('status', 'approved')
                ->whereDate('start_date', '<=', $dateStr)
                ->whereDate('end_date', '>=', $dateStr)
                ->count();
                
            $attendanceTrends[] = [
                'date' => $dateStr,
                'present' => (int)($stats->present ?? 0),
                'late' => (int)($stats->late ?? 0),
                'absent' => (int)($stats->absent ?? 0),
                'on_leave' => $onLeave,
            ];
        }

        return $this->apiResponse([
            'summary' => $summary,
            'recent_activity' => $recentActivity,
            'attendance_trends' => $attendanceTrends,
            'today_schedule' => null,
        ], 'Dashboard data retrieved');
    }

    public function summary(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $totalEmployees = Employee::where('is_active', true)->count();
        $workDays = $this->calculateWorkDays($startDate, $endDate);

        // PostgreSQL compatible timestamp diff
        $avgHoursQuery = "AVG(EXTRACT(EPOCH FROM (check_out_time::timestamp - check_in_time::timestamp)) / 3600)";
        if (DB::connection()->getDriverName() === 'sqlite') {
            $avgHoursQuery = "AVG((strftime('%s', check_out_time) - strftime('%s', check_in_time)) / 3600)";
        } elseif (DB::connection()->getDriverName() === 'mysql') {
            $avgHoursQuery = "AVG(TIMESTAMPDIFF(HOUR, check_in_time, check_out_time))";
        }

        $attendanceStats = Attendance::whereBetween('date', [$startDate, $endDate])
            ->selectRaw("
                COUNT(*) as total_records,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                {$avgHoursQuery} as avg_hours
            ")
            ->first();

        $summary = [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_employees' => $totalEmployees,
            'work_days' => $workDays,
            'attendance' => [
                'total_records' => $attendanceStats->total_records ?? 0,
                'present' => $attendanceStats->present ?? 0,
                'late' => $attendanceStats->late ?? 0,
                'absent' => $attendanceStats->absent ?? 0,
                'rate' => $totalEmployees > 0 && $workDays > 0
                    ? round(($attendanceStats->present + $attendanceStats->late) / ($totalEmployees * $workDays) * 100, 1)
                    : 0,
            ],
            'avg_work_hours' => round($attendanceStats->avg_hours ?? 0, 1),
        ];

        return $this->apiResponse($summary, 'Summary retrieved');
    }

    /**
     * Get attendance summary for current employee (employee-only view)
     */
    public function myAttendanceSummary(Request $request)
    {
        $user = $request->user();
        
        // Get employee record
        $employee = $user->employee;
        if (!$employee) {
            return $this->errorResponse('Employee record not found', 404);
        }

        // Date range (default: current month)
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Calculate work days
        $workDays = $this->calculateWorkDays($startDate, $endDate);

        // Get employee's attendance records
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Calculate statistics
        $present = $attendances->where('status', 'present')->count();
        $late = $attendances->where('status', 'late')->count();
        $absent = $attendances->where('status', 'absent')->count();
        $totalRecords = $attendances->count();

        // Calculate average work hours
        $totalHours = $attendances->where('total_hours', '>', 0)->sum('total_hours');
        $avgHours = $totalRecords > 0 ? round($totalHours / $totalRecords, 1) : 0;

        // Attendance rate
        $attendanceRate = $workDays > 0 
            ? round((($present + $late) / $workDays) * 100, 1) 
            : 0;

        // Get recent attendance (last 7 days)
        $recentAttendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [now()->subDays(6), now()])
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($att) {
                return [
                    'date' => $att->date,
                    'check_in' => $att->check_in_time ? Carbon::parse($att->check_in_time)->format('H:i') : null,
                    'check_out' => $att->check_out_time ? Carbon::parse($att->check_out_time)->format('H:i') : null,
                    'status' => $att->status,
                    'work_hours' => $att->total_hours ? round($att->total_hours, 1) : 0,
                    'late_duration' => $att->late_duration_minutes ?? 0,
                ];
            });

        // Get current month attendance by date
        $monthlyAttendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->orderBy('date')
            ->get()
            ->map(function ($att) {
                return [
                    'date' => $att->date,
                    'day' => Carbon::parse($att->date)->format('D'),
                    'status' => $att->status,
                    'check_in' => $att->check_in_time ? Carbon::parse($att->check_in_time)->format('H:i') : null,
                    'check_out' => $att->check_out_time ? Carbon::parse($att->check_out_time)->format('H:i') : null,
                ];
            });

        $summary = [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
                'work_days' => $workDays,
            ],
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'employee_code' => $employee->employee_code,
            ],
            'statistics' => [
                'total_records' => $totalRecords,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'attendance_rate' => $attendanceRate,
                'avg_work_hours' => $avgHours,
            ],
            'recent_attendance' => $recentAttendance,
            'monthly_calendar' => $monthlyAttendance,
        ];

        return $this->apiResponse($summary, 'Your attendance summary retrieved');
    }

    public function monthlyAttendance(Request $request)
    {
        $year = $request->get('year', now()->year);
        $months = range(1, 12);

        $data = [];

        foreach ($months as $month) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $stats = Attendance::whereBetween('date', [$startDate, $endDate])
                ->selectRaw("
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
                ")
                ->first();

            $data[] = [
                'month' => $startDate->format('M'),
                'month_num' => $month,
                'present' => $stats->present ?? 0,
                'late' => $stats->late ?? 0,
                'absent' => $stats->absent ?? 0,
            ];
        }

        return $this->apiResponse($data, 'Monthly attendance retrieved');
    }

    public function weeklyTrend(Request $request)
    {
        $weeks = $request->get('weeks', 4);
        $data = [];

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();

            $stats = Attendance::whereBetween('date', [$weekStart, $weekEnd])
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
                ")
                ->first();

            $total = ($stats->present ?? 0) + ($stats->late ?? 0) + ($stats->absent ?? 0);

            $data[] = [
                'week' => 'W' . $weekStart->weekOfYear,
                'week_start' => $weekStart->format('Y-m-d'),
                'attendance_rate' => $total > 0
                    ? round((($stats->present ?? 0) + ($stats->late ?? 0)) / $total * 100, 1)
                    : 0,
            ];
        }

        return $this->apiResponse($data, 'Weekly trend retrieved');
    }

    public function departmentStats(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Check if department column exists, otherwise return empty or mock
        try {
            $departments = Employee::select('department')
                ->distinct()
                ->whereNotNull('department')
                ->pluck('department');
        } catch (\Exception $e) {
            // Fallback if department column doesn't exist
            return $this->apiResponse([], 'Department stats not available (column missing)');
        }

        $data = [];

        foreach ($departments as $dept) {
            $employeeIds = Employee::where('department', $dept)->pluck('id');

            $stats = Attendance::whereIn('employee_id', $employeeIds)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                ")
                ->first();

            $total = $stats->total ?? 1;

            $data[] = [
                'department' => $dept,
                'employee_count' => $employeeIds->count(),
                'attendance_rate' => round((($stats->present ?? 0) + ($stats->late ?? 0)) / max($total, 1) * 100, 1),
                'present' => $stats->present ?? 0,
                'late' => $stats->late ?? 0,
            ];
        }

        return $this->apiResponse($data, 'Department stats retrieved');
    }

    public function leaveStats(Request $request)
    {
        $year = $request->get('year', now()->year);

        // PostgreSQL compatible datediff
        $dateDiffQuery = "(end_date - start_date + 1)";
        if (DB::connection()->getDriverName() === 'sqlite') {
            $dateDiffQuery = "(julianday(end_date) - julianday(start_date) + 1)";
        } elseif (DB::connection()->getDriverName() === 'mysql') {
            $dateDiffQuery = "DATEDIFF(end_date, start_date) + 1";
        }

        $stats = Leave::whereYear('start_date', $year)
            ->where('status', 'approved')
            ->select('leave_type_id') // Use leave_type_id
            ->selectRaw('COUNT(*) as count')
            ->selectRaw("SUM({$dateDiffQuery}) as total_days")
            ->groupBy('leave_type_id')
            ->with('leaveType') // Load relationship to get name
            ->get()
            ->map(function ($item) {
                return [
                    'leave_type' => $item->leaveType->name ?? 'Unknown',
                    'count' => $item->count,
                    'total_days' => $item->total_days
                ];
            });

        return $this->apiResponse($stats, 'Leave stats retrieved');
    }

    public function generate(Request $request)
    {
        // 1. SECURITY: Admin-only access for export
        // Check if user has admin/HR role (pegawai shouldn't export)
        $userRoles = $request->user()->roles->pluck('name')->toArray();
        $allowedRoles = ['Super Admin', 'Admin', 'admin', 'HR'];
        
        if (!array_intersect($userRoles, $allowedRoles)) {
            Log::warning('Unauthorized export attempt', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email,
                'roles' => $userRoles
            ]);
            
            return $this->errorResponse('Unauthorized. Only administrators can export reports.', 403);
        }
        
        // 2. SECURITY: Rate limiting (handled by middleware)
        // Applied in routes/api.php: ->middleware('throttle:report-export')
        
        // 3. VALIDATION
        $validated = $request->validate([
            'type' => 'required|in:attendance,leave,payroll,summary',
            'format' => 'required|in:pdf,excel',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'filters' => 'nullable|array',
        ]);

        $reportType = $validated['type'];
        $format = $validated['format'];
        $filters = $request->all();
        $selectedColumns = $filters['filters']['columns'] ?? [];

        // 3. PERFORMANCE: Check cache for identical recent exports
        $cacheKey = 'report:' . md5(json_encode($validated) . Auth::id());
        $cachedReport = \Illuminate\Support\Facades\Cache::get($cacheKey);
        
        if ($cachedReport && file_exists(storage_path('app/public/' . str_replace('storage/', '', $cachedReport['file_path'])))) {
            Log::info('Returning cached report', [
                'user_id' => Auth::id(),
                'cache_key' => $cacheKey
            ]);
            
            return $this->apiResponse([
                'report' => $cachedReport['report'],
                'download_url' => $cachedReport['download_url'],
                'expires_at' => $cachedReport['expires_at'],
                'cached' => true
            ], 'Report retrieved from cache');
        }

        // 4. ESTIMATE: Calculate expected dataset size
        $estimatedRows = $this->estimateRowCount($reportType, $validated['start_date'], $validated['end_date']);
        
        Log::info('Report generation requested', [
            'user_id' => Auth::id(),
            'type' => $reportType,
            'format' => $format,
            'estimated_rows' => $estimatedRows
        ]);

        // 5. SMART ROUTING: Auto-queue for large exports
        if ($estimatedRows > 1000) {
            return $this->generateAsync($validated, $reportType, $format, $selectedColumns, $filters);
        }

        // 6. SYNCHRONOUS GENERATION for small datasets
        return $this->generateSync($validated, $reportType, $format, $selectedColumns, $filters, $cacheKey);
    }

    /**
     * Generate report synchronously (for small datasets)
     */
    private function generateSync($validated, $reportType, $format, $selectedColumns, $filters, $cacheKey)
    {
        // SECURITY: Set execution limits
        set_time_limit(60); // 1 minute max
        ini_set('memory_limit', '256M');

        try {
            // 1. Fetch Data with Chunking
            $reportData = $this->getReportData($reportType, $validated['start_date'], $validated['end_date'], $selectedColumns);
            
            $data = $reportData['data'];
            $headings = $reportData['headings'];
            $columns = $reportData['columns'];

            // 2. Generate File
            $extension = $format === 'pdf' ? 'pdf' : 'xlsx';
            $writerType = $format === 'pdf' ? \Maatwebsite\Excel\Excel::DOMPDF : \Maatwebsite\Excel\Excel::XLSX;
            
            $filename = "{$reportType}_" . now()->format('Ymd_His') . ".{$extension}";
            $path = "exports/{$filename}";

            // Ensure directory exists
            if (!file_exists(storage_path('app/public/exports'))) {
                mkdir(storage_path('app/public/exports'), 0755, true);
            }

            // Store file
            Excel::store(
                new AttendanceReportExport($data, $headings, $columns),
                $path,
                'public',
                $writerType
            );

            // 3. Save to Database
            $report = Report::create([
                'type' => $reportType,
                'format' => $format,
                'filename' => $filename,
                'file_path' => "storage/{$path}",
                'status' => 'completed',
                'filters' => $filters,
                'generated_by' => Auth::id(),
                'expires_at' => now()->addDays(7),
            ]);

            $appUrl = config('app.url');
            $downloadUrl = "{$appUrl}/storage/{$path}";

            // 4. PERFORMANCE: Cache the result (5 minutes)
            \Illuminate\Support\Facades\Cache::put($cacheKey, [
                'report' => $report,
                'file_path' => $report->file_path,
                'download_url' => $downloadUrl,
                'expires_at' => $report->expires_at
            ], 300); // 5 minutes

            Log::info('Report generated successfully (sync)', [
                'user_id' => Auth::id(),
                'report_id' => $report->id,
                'rows' => count($data)
            ]);

            return $this->apiResponse([
                'report' => $report,
                'download_url' => $downloadUrl,
                'expires_at' => $report->expires_at,
                'generated_sync' => true
            ], 'Report generated successfully', 201);

        } catch (\Exception $e) {
            Log::error('Sync report generation failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to generate report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate report asynchronously (for large datasets)
     */
    private function generateAsync($validated, $reportType, $format, $selectedColumns, $filters)
    {
        try {
            // Create pending report record
            $report = Report::create([
                'type' => $reportType,
                'format' => $format,
                'status' => 'pending',
                'filters' => $filters,
                'generated_by' => Auth::id(),
                'expires_at' => now()->addDays(7),
            ]);

            // Dispatch queue job
            \App\Jobs\GenerateReportJob::dispatch(
                $report,
                $reportType,
                $validated['start_date'],
                $validated['end_date'],
                $selectedColumns,
                $format
            );

            Log::info('Report queued for async generation', [
                'user_id' => Auth::id(),
                'report_id' => $report->id
            ]);

            return $this->apiResponse([
                'report' => $report,
                'message' => 'Large export queued for processing. You will be notified when ready.',
                'status' => 'pending',
                'generated_async' => true
            ], 'Report queued for generation', 202);

        } catch (\Exception $e) {
            Log::error('Failed to queue report', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to queue report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Estimate number of rows for smart routing
     */
    private function estimateRowCount($type, $startDate, $endDate)
    {
        switch ($type) {
            case 'attendance':
                return \App\Models\Attendance::whereBetween('date', [$startDate, $endDate])->count();
            case 'leave':
                return \App\Models\Leave::whereBetween('start_date', [$startDate, $endDate])->count();
            case 'payroll':
                return \App\Models\Employee::where('is_active', true)->count();
            default:
                return 0;
        }
    }

    private function getReportData($type, $startDate, $endDate, $selectedColumns = [])
    {
        switch ($type) {
            case 'leave':
                return $this->getLeaveReportData($startDate, $endDate, $selectedColumns);
            case 'payroll':
                return $this->getPayrollReportData($startDate, $endDate, $selectedColumns);
            case 'attendance':
            default:
                return $this->getAttendanceReportData($startDate, $endDate, $selectedColumns);
        }
    }

    private function getAttendanceReportData($startDate, $endDate, $selectedColumns)
    {
        $employees = Employee::orderBy('full_name')->get();
        $data = [];

        // Define available columns and their headers
        $columnMap = [
            'employee_name' => 'Nama Karyawan',
            'employee_code' => 'NIK',
            'date' => 'Tanggal',
            'check_in' => 'Jam Masuk',
            'check_out' => 'Jam Pulang',
            'status' => 'Status',
            'work_hours' => 'Jam Kerja',
            'late_duration' => 'Terlambat (Menit)',
            'notes' => 'Catatan',
        ];

        // If no columns selected, use default
        if (empty($selectedColumns)) {
            $selectedColumns = ['employee_name', 'date', 'check_in', 'check_out', 'status', 'work_hours'];
        }

        // Filter columns to only valid ones
        $validColumns = array_intersect($selectedColumns, array_keys($columnMap));
        $headings = array_map(fn($col) => $columnMap[$col], $validColumns);

        // Fetch attendance records with employee data
        $attendances = Attendance::with('employee')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('employee_id') // We can't order by joined table easily without join, so sort by ID then PHP sort if needed
            ->get();

        foreach ($attendances as $att) {
            $row = [
                'employee_name' => $att->employee->full_name ?? 'Unknown',
                'employee_code' => $att->employee->employee_code ?? '-',
                'date' => $att->date,
                'check_in' => $att->check_in_time ? Carbon::parse($att->check_in_time)->format('H:i:s') : '-',
                'check_out' => $att->check_out_time ? Carbon::parse($att->check_out_time)->format('H:i:s') : '-',
                'status' => ucfirst($att->status),
                'work_hours' => $att->total_hours ? round($att->total_hours, 2) : 0,
                'late_duration' => $att->late_duration_minutes ?? 0,
                'notes' => $att->notes ?? '-',
            ];
            $data[] = $row;
        }

        return [
            'data' => $data,
            'headings' => $headings,
            'columns' => $validColumns
        ];
    }

    private function getLeaveReportData($startDate, $endDate, $selectedColumns)
    {
        $columnMap = [
            'employee_name' => 'Nama Karyawan',
            'employee_code' => 'NIK',
            'leave_type' => 'Tipe Cuti',
            'start_date' => 'Mulai',
            'end_date' => 'Selesai',
            'duration' => 'Durasi (Hari)',
            'reason' => 'Alasan',
            'status' => 'Status',
            'approved_by' => 'Disetujui Oleh',
        ];

        if (empty($selectedColumns)) {
            $selectedColumns = ['employee_name', 'leave_type', 'start_date', 'end_date', 'duration', 'status'];
        }

        $validColumns = array_intersect($selectedColumns, array_keys($columnMap));
        $headings = array_map(fn($col) => $columnMap[$col], $validColumns);

        $leaves = Leave::with(['employee', 'leaveType', 'approver'])
            ->whereBetween('start_date', [$startDate, $endDate])
            ->orderBy('start_date')
            ->get();

        $data = [];
        foreach ($leaves as $leave) {
            // Calculate duration
            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);
            $duration = $start->diffInDays($end) + 1;

            $data[] = [
                'employee_name' => $leave->employee->full_name ?? 'Unknown',
                'employee_code' => $leave->employee->employee_code ?? '-',
                'leave_type' => $leave->leaveType->name ?? '-',
                'start_date' => $leave->start_date,
                'end_date' => $leave->end_date,
                'duration' => $duration,
                'reason' => $leave->reason,
                'status' => ucfirst($leave->status),
                'approved_by' => $leave->approver->name ?? '-',
            ];
        }

        return [
            'data' => $data,
            'headings' => $headings,
            'columns' => $validColumns
        ];
    }

    private function getPayrollReportData($startDate, $endDate, $selectedColumns)
    {
        // Mock Payroll Data for now as Payroll module is not fully implemented
        $columnMap = [
            'employee_name' => 'Nama Karyawan',
            'employee_code' => 'NIK',
            'period' => 'Periode',
            'basic_salary' => 'Gaji Pokok',
            'allowances' => 'Tunjangan',
            'deductions' => 'Potongan',
            'net_salary' => 'Gaji Bersih',
            'status' => 'Status',
        ];

        if (empty($selectedColumns)) {
            $selectedColumns = ['employee_name', 'period', 'basic_salary', 'net_salary', 'status'];
        }

        $validColumns = array_intersect($selectedColumns, array_keys($columnMap));
        $headings = array_map(fn($col) => $columnMap[$col], $validColumns);

        $employees = Employee::where('is_active', true)->get();
        $data = [];

        foreach ($employees as $emp) {
            $basic = rand(3000000, 8000000);
            $allowance = rand(500000, 2000000);
            $deduction = rand(100000, 500000);
            
            $data[] = [
                'employee_name' => $emp->full_name,
                'employee_code' => $emp->employee_code,
                'period' => Carbon::parse($startDate)->format('M Y'),
                'basic_salary' => $basic,
                'allowances' => $allowance,
                'deductions' => $deduction,
                'net_salary' => $basic + $allowance - $deduction,
                'status' => 'Paid',
            ];
        }

        return [
            'data' => $data,
            'headings' => $headings,
            'columns' => $validColumns
        ];
    }

    public function templates()
    {
        $templates = [
            ['id' => '1', 'name' => 'Laporan Kehadiran Bulanan', 'type' => 'attendance', 'description' => 'Rekap kehadiran per bulan'],
            ['id' => '2', 'name' => 'Laporan Cuti', 'type' => 'leave', 'description' => 'Rekap pengajuan cuti'],
            ['id' => '3', 'name' => 'Laporan Ringkasan', 'type' => 'summary', 'description' => 'Ringkasan keseluruhan'],
        ];

        return $this->apiResponse($templates, 'Templates retrieved');
    }

    public function generatedReports()
    {
        $reports = Report::where('generated_by', Auth::id())
            ->where('expires_at', '>', now())
            ->latest()
            ->take(20)
            ->get();

        return $this->apiResponse($reports, 'Generated reports retrieved');
    }

    public function showGeneratedReport($id)
    {
        $report = Report::where('id', $id)
            ->where('generated_by', Auth::id())
            ->firstOrFail();

        // Add download URL if file exists
        if (file_exists(storage_path('app/public/' . str_replace('storage/', '', $report->file_path)))) {
            $report->download_url = url($report->file_path);
        }

        return $this->apiResponse($report, 'Report details retrieved');
    }

    private function getAttendanceSummary($startDate, $endDate)
    {
        return Attendance::whereBetween('date', [$startDate, $endDate])
            ->selectRaw("
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as on_leave
            ")
            ->first();
    }

    private function getMonthlyTrend($startDate, $endDate)
    {
        $monthQuery = "strftime('%Y-%m', date)";
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $monthQuery = "DATE_FORMAT(date, '%Y-%m')";
        }

        return Attendance::whereBetween('date', [$startDate, $endDate])
            ->selectRaw("{$monthQuery} as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    private function getDepartmentBreakdown($startDate, $endDate)
    {
        try {
            return Employee::select('department')
                ->selectRaw('COUNT(*) as count')
                ->whereNotNull('department')
                ->groupBy('department')
                ->get();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getLeaveDistribution($startDate, $endDate)
    {
        return Leave::whereBetween('start_date', [$startDate, $endDate])
            ->select('leave_type_id')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('leave_type_id')
            ->with('leaveType')
            ->get()
            ->map(function ($item) {
                return [
                    'leave_type' => $item->leaveType->name ?? 'Unknown',
                    'count' => $item->count
                ];
            });
    }

    private function calculateWorkDays($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $workDays = 0;

        while ($start <= $end) {
            if (!$start->isWeekend()) {
                $workDays++;
            }
            $start->addDay();
        }

        return $workDays;
    }

    /**
     * Get monthly attendance recap with A/I/S/D/C breakdown
     * 
     * H = Hadir (Present on time)
     * T = Terlambat (Late)
     * A = Alpha (Absent without notice)
     * I = Izin (Permission)
     * S = Sakit (Sick)
     * D = Dinas (Official duty)
     * C = Cuti (Leave/Vacation)
     */
    public function monthlyRecap(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $departmentFilter = $request->get('department', null);

        // Calculate date range
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        // Only count up to today if current month
        if ($endDate->isFuture()) {
            $endDate = Carbon::today();
        }

        // Calculate working days (exclude weekends and holidays)
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        $workingDays = 0;
        $current = $startDate->copy();
        while ($current <= $endDate) {
            if (!$current->isWeekend() && !in_array($current->format('Y-m-d'), $holidays)) {
                $workingDays++;
            }
            $current->addDay();
        }

        // Get employees
        $employeesQuery = Employee::where('is_active', true)
            ->orderBy('full_name');
        
        if ($departmentFilter) {
            $employeesQuery->where('department', $departmentFilter);
        }
        
        $employees = $employeesQuery->get();

        // Define leave type codes mapping
        // Map common leave type names/codes to our categories
        $leaveTypeMapping = [
            'izin' => 'I',
            'permission' => 'I',
            'sakit' => 'S',
            'sick' => 'S',
            'dinas' => 'D',
            'duty' => 'D',
            'official' => 'D',
            'cuti' => 'C',
            'leave' => 'C',
            'annual' => 'C',
            'vacation' => 'C',
        ];

        // Get all leave types for mapping
        $leaveTypes = \App\Models\LeaveType::all();
        $leaveTypeCategories = [];
        foreach ($leaveTypes as $lt) {
            $code = strtolower($lt->code ?? '');
            $name = strtolower($lt->name ?? '');
            
            // Try to match to category
            foreach ($leaveTypeMapping as $keyword => $category) {
                if (str_contains($code, $keyword) || str_contains($name, $keyword)) {
                    $leaveTypeCategories[$lt->id] = $category;
                    break;
                }
            }
            // Default to C (Cuti) if no match
            if (!isset($leaveTypeCategories[$lt->id])) {
                $leaveTypeCategories[$lt->id] = 'C';
            }
        }

        $recapData = [];
        $totals = [
            'hadir' => 0,
            'terlambat' => 0,
            'alpha' => 0,
            'izin' => 0,
            'sakit' => 0,
            'dinas' => 0,
            'cuti' => 0,
        ];

        foreach ($employees as $employee) {
            // Get attendance records for this employee in the month
            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            // Count by status
            $hadir = $attendances->where('status', 'present')->count();
            $terlambat = $attendances->where('status', 'late')->count();
            $absent = $attendances->where('status', 'absent')->count();

            // Get approved leaves for this employee in the month
            $leaves = Leave::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q2) use ($startDate, $endDate) {
                          $q2->where('start_date', '<=', $startDate)
                             ->where('end_date', '>=', $endDate);
                      });
                })
                ->with('leaveType')
                ->get();

            // Count leave days by category
            $izin = 0;
            $sakit = 0;
            $dinas = 0;
            $cuti = 0;

            foreach ($leaves as $leave) {
                // Calculate overlap days within the month
                $leaveStart = Carbon::parse($leave->start_date);
                $leaveEnd = Carbon::parse($leave->end_date);
                
                $overlapStart = $leaveStart->lt($startDate) ? $startDate->copy() : $leaveStart->copy();
                $overlapEnd = $leaveEnd->gt($endDate) ? $endDate->copy() : $leaveEnd->copy();
                
                // Count working days in overlap period
                $leaveDays = 0;
                $checkDate = $overlapStart->copy();
                while ($checkDate <= $overlapEnd) {
                    if (!$checkDate->isWeekend() && !in_array($checkDate->format('Y-m-d'), $holidays)) {
                        $leaveDays++;
                    }
                    $checkDate->addDay();
                }

                // Categorize
                $category = $leaveTypeCategories[$leave->leave_type_id] ?? 'C';
                switch ($category) {
                    case 'I':
                        $izin += $leaveDays;
                        break;
                    case 'S':
                        $sakit += $leaveDays;
                        break;
                    case 'D':
                        $dinas += $leaveDays;
                        break;
                    case 'C':
                    default:
                        $cuti += $leaveDays;
                        break;
                }
            }

            // Calculate alpha (days not accounted for)
            $accountedDays = $hadir + $terlambat + $absent + $izin + $sakit + $dinas + $cuti;
            $alpha = max(0, $workingDays - $accountedDays);

            // Calculate attendance percentage
            // (Hadir + Terlambat + Dinas) / Hari Kerja * 100
            $attendanceRate = $workingDays > 0 
                ? round((($hadir + $terlambat + $dinas) / $workingDays) * 100, 1) 
                : 0;

            $recapData[] = [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'employee_name' => $employee->full_name,
                'department' => $employee->department ?? '-',
                'hadir' => $hadir,           // H
                'terlambat' => $terlambat,   // T
                'alpha' => $alpha,           // A
                'izin' => $izin,             // I
                'sakit' => $sakit,           // S
                'dinas' => $dinas,           // D
                'cuti' => $cuti,             // C
                'working_days' => $workingDays,
                'attendance_rate' => $attendanceRate,
            ];

            // Add to totals
            $totals['hadir'] += $hadir;
            $totals['terlambat'] += $terlambat;
            $totals['alpha'] += $alpha;
            $totals['izin'] += $izin;
            $totals['sakit'] += $sakit;
            $totals['dinas'] += $dinas;
            $totals['cuti'] += $cuti;
        }

        // Calculate overall attendance rate
        $totalEmployees = count($employees);
        $totalPossibleDays = $workingDays * $totalEmployees;
        $totalAttendedDays = $totals['hadir'] + $totals['terlambat'] + $totals['dinas'];
        $overallRate = $totalPossibleDays > 0 
            ? round(($totalAttendedDays / $totalPossibleDays) * 100, 1) 
            : 0;

        return $this->apiResponse([
            'period' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'month_name' => Carbon::create($year, $month, 1)->translatedFormat('F'),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'working_days' => $workingDays,
            'total_employees' => $totalEmployees,
            'holidays_count' => count($holidays),
            'data' => $recapData,
            'totals' => [
                'hadir' => $totals['hadir'],
                'terlambat' => $totals['terlambat'],
                'alpha' => $totals['alpha'],
                'izin' => $totals['izin'],
                'sakit' => $totals['sakit'],
                'dinas' => $totals['dinas'],
                'cuti' => $totals['cuti'],
                'overall_attendance_rate' => $overallRate,
            ],
            'legend' => [
                'H' => 'Hadir (Tepat Waktu)',
                'T' => 'Terlambat',
                'A' => 'Alpha (Tanpa Keterangan)',
                'I' => 'Izin',
                'S' => 'Sakit',
                'D' => 'Dinas Luar',
                'C' => 'Cuti',
            ],
        ], 'Monthly attendance recap retrieved');
    }
}

