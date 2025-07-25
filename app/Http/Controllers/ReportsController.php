<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\Payroll;
use App\Services\ExportService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    use ApiResponseTrait;

    protected $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Main reports dashboard
     */
    public function index()
    {
        $reportTypes = $this->getReportTypes();
        $quickStats = $this->getQuickStats();

        return view('pages.reports.index', [
            'reportTypes' => $reportTypes,
            'quickStats' => $quickStats,
        ]);
    }

    /**
     * Interactive report builder
     */
    public function builder()
    {
        $filterOptions = $this->getFilterOptions();

        return view('pages.reports.builder', [
            'filterOptions' => $filterOptions,
        ]);
    }

    /**
     * Attendance Reports
     */
    public function attendance(Request $request)
    {
        $filters = $this->getAttendanceFilters($request);
        $attendanceData = $this->getAttendanceReportData($filters);

        if ($request->wantsJson()) {
            return $this->successResponse([
                'data' => $attendanceData,
                'filters' => $filters,
            ], 'Attendance report generated successfully');
        }

        return view('pages.reports.attendance', [
            'data' => $attendanceData,
            'filters' => $filters,
            'filterOptions' => $this->getFilterOptions(),
        ]);
    }

    /**
     * Leave Reports
     */
    public function leave(Request $request)
    {
        $filters = $this->getLeaveFilters($request);
        $leaveData = $this->getLeaveReportData($filters);

        if ($request->wantsJson()) {
            return $this->successResponse([
                'data' => $leaveData,
                'filters' => $filters,
            ], 'Leave report generated successfully');
        }

        return view('pages.reports.leave', [
            'data' => $leaveData,
            'filters' => $filters,
            'filterOptions' => $this->getFilterOptions(),
        ]);
    }

    /**
     * Payroll Reports
     */
    public function payroll(Request $request)
    {
        $filters = $this->getPayrollFilters($request);
        $payrollData = $this->getPayrollReportData($filters);

        if ($request->wantsJson()) {
            return $this->successResponse([
                'data' => $payrollData,
                'filters' => $filters,
            ], 'Payroll report generated successfully');
        }

        return view('pages.reports.payroll', [
            'data' => $payrollData,
            'filters' => $filters,
            'filterOptions' => $this->getFilterOptions(),
        ]);
    }

    /**
     * Employee Reports
     */
    public function employees(Request $request)
    {
        $filters = $this->getEmployeeFilters($request);
        $employeeData = $this->getEmployeeReportData($filters);

        if ($request->wantsJson()) {
            return $this->successResponse([
                'data' => $employeeData,
                'filters' => $filters,
            ], 'Employee report generated successfully');
        }

        return view('pages.reports.employees', [
            'data' => $employeeData,
            'filters' => $filters,
            'filterOptions' => $this->getFilterOptions(),
        ]);
    }

    /**
     * Summary Reports
     */
    public function summary(Request $request)
    {
        $filters = $this->getSummaryFilters($request);
        $summaryData = $this->getSummaryReportData($filters);

        if ($request->wantsJson()) {
            return $this->successResponse([
                'data' => $summaryData,
                'filters' => $filters,
            ], 'Summary report generated successfully');
        }

        return view('pages.reports.summary', [
            'data' => $summaryData,
            'filters' => $filters,
            'filterOptions' => $this->getFilterOptions(),
        ]);
    }

    /**
     * Custom Report Builder - Generate Report
     */
    public function generateCustomReport(Request $request): JsonResponse
    {
        $request->validate([
            'report_type' => 'required|in:attendance,leave,payroll,employee,summary',
            'date_range' => 'required|array',
            'date_range.start' => 'required|date',
            'date_range.end' => 'required|date|after_or_equal:date_range.start',
            'filters' => 'array',
            'columns' => 'array',
            'grouping' => 'string',
            'sorting' => 'array',
        ]);

        $reportType = $request->input('report_type');
        $dateRange = $request->input('date_range');
        $filters = $request->input('filters', []);
        $columns = $request->input('columns', []);
        $grouping = $request->input('grouping');
        $sorting = $request->input('sorting', []);

        try {
            $data = $this->buildCustomReport(
                $reportType,
                $dateRange,
                $filters,
                $columns,
                $grouping,
                $sorting,
            );

            return $this->successResponse([
                'data' => $data,
                'report_config' => [
                    'type' => $reportType,
                    'date_range' => $dateRange,
                    'filters' => $filters,
                    'columns' => $columns,
                    'grouping' => $grouping,
                    'sorting' => $sorting,
                ],
            ], 'Custom report generated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate report: '.$e->getMessage());
        }
    }

    /**
     * Export Reports
     */
    public function exportAttendance(Request $request)
    {
        $filters = $this->getAttendanceFilters($request);
        $format = $request->get('format', 'csv');

        if (! $this->exportService->isValidFormat($format)) {
            return $this->errorResponse('Invalid export format', 400);
        }

        return $this->exportService->exportAttendance($filters, $format);
    }

    public function exportLeave(Request $request)
    {
        $filters = $this->getLeaveFilters($request);
        $format = $request->get('format', 'csv');

        if (! $this->exportService->isValidFormat($format)) {
            return $this->errorResponse('Invalid export format', 400);
        }

        return $this->exportService->exportLeave($filters, $format);
    }

    public function exportPayroll(Request $request)
    {
        $filters = $this->getPayrollFilters($request);
        $format = $request->get('format', 'csv');

        if (! $this->exportService->isValidFormat($format)) {
            return $this->errorResponse('Invalid export format', 400);
        }

        return $this->exportService->exportPayroll($filters, $format);
    }

    public function exportEmployee(Request $request)
    {
        $filters = $this->getEmployeeFilters($request);
        $format = $request->get('format', 'csv');

        if (! $this->exportService->isValidFormat($format)) {
            return $this->errorResponse('Invalid export format', 400);
        }

        return $this->exportService->exportEmployees($filters, $format);
    }

    public function attendanceSummary(Request $request)
    {
        $filters = $this->getAttendanceFilters($request);
        $summaryData = $this->getAttendanceSummaryData($filters);

        if ($request->wantsJson()) {
            return $this->successResponse($summaryData, 'Attendance summary generated successfully');
        }

        return view('pages.reports.attendance-summary', [
            'data' => $summaryData,
            'filters' => $filters,
        ]);
    }

    public function leaveAnalytics(Request $request)
    {
        $filters = $this->getLeaveFilters($request);
        $analyticsData = $this->getLeaveAnalyticsData($filters);

        if ($request->wantsJson()) {
            return $this->successResponse($analyticsData, 'Leave analytics generated successfully');
        }

        return view('pages.reports.leave-analytics', [
            'data' => $analyticsData,
            'filters' => $filters,
        ]);
    }

    public function employeePerformance(Request $request)
    {
        $filters = $this->getEmployeeFilters($request);
        $performanceData = $this->getEmployeePerformanceData($filters);

        if ($request->wantsJson()) {
            return $this->successResponse($performanceData, 'Employee performance data generated successfully');
        }

        return view('pages.reports.employee-performance', [
            'data' => $performanceData,
            'filters' => $filters,
        ]);
    }

    public function payrollSummary(Request $request)
    {
        $filters = $this->getPayrollFilters($request);
        $summaryData = $this->getPayrollSummaryData($filters);

        if ($request->wantsJson()) {
            return $this->successResponse($summaryData, 'Payroll summary generated successfully');
        }

        return view('pages.reports.payroll-summary', [
            'data' => $summaryData,
            'filters' => $filters,
        ]);
    }

    /**
     * Schedule automated reports
     */
    public function scheduleReport(Request $request): JsonResponse
    {
        $request->validate([
            'report_type' => 'required|in:attendance,leave,payroll,employee,summary',
            'schedule_type' => 'required|in:daily,weekly,monthly,quarterly',
            'format' => 'required|in:csv,excel,pdf',
            'recipients' => 'required|array',
            'recipients.*' => 'email',
            'filters' => 'array',
        ]);

        // In a full implementation, this would store the schedule in the database
        // and set up a cron job or queue job to generate and send reports

        return $this->successResponse([
            'schedule_id' => uniqid('schedule_'),
        ], 'Report scheduled successfully');
    }

    /**
     * Get scheduled reports
     */
    public function getScheduledReports(): JsonResponse
    {
        // In a full implementation, this would fetch from database
        $scheduledReports = [
            [
                'id' => 'schedule_1',
                'report_type' => 'attendance',
                'schedule_type' => 'weekly',
                'format' => 'pdf',
                'recipients' => ['admin@company.com'],
                'next_run' => Carbon::now()->addWeek(),
                'status' => 'active',
            ],
        ];

        return $this->successResponse($scheduledReports, 'Scheduled reports retrieved successfully');
    }

    // Private Methods for Data Processing

    private function getAttendanceFilters(Request $request): array
    {
        return [
            'start_date' => $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
            'end_date' => $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
            'employee_ids' => $request->get('employee_ids', []),
            'status' => $request->get('status', []),
            'location_ids' => $request->get('location_ids', []),
            'department_ids' => $request->get('department_ids', []),
        ];
    }

    private function getLeaveFilters(Request $request): array
    {
        return [
            'start_date' => $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
            'end_date' => $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
            'employee_ids' => $request->get('employee_ids', []),
            'status' => $request->get('status', []),
            'leave_type_ids' => $request->get('leave_type_ids', []),
            'approved_by' => $request->get('approved_by', []),
        ];
    }

    private function getPayrollFilters(Request $request): array
    {
        return [
            'start_date' => $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
            'end_date' => $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
            'employee_ids' => $request->get('employee_ids', []),
            'status' => $request->get('status', []),
            'min_salary' => $request->get('min_salary'),
            'max_salary' => $request->get('max_salary'),
        ];
    }

    private function getEmployeeFilters(Request $request): array
    {
        return [
            'employee_type' => $request->get('employee_type', []),
            'is_active' => $request->get('is_active'),
            'location_ids' => $request->get('location_ids', []),
            'hire_date_start' => $request->get('hire_date_start'),
            'hire_date_end' => $request->get('hire_date_end'),
        ];
    }

    private function getSummaryFilters(Request $request): array
    {
        return [
            'start_date' => $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
            'end_date' => $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
            'grouping' => $request->get('grouping', 'monthly'),
            'metrics' => $request->get('metrics', ['attendance', 'leave', 'payroll']),
        ];
    }

    private function getAttendanceReportData(array $filters): array
    {
        $query = Attendance::with(['employee']);

        // Apply filters
        $query->whereBetween('date', [$filters['start_date'], $filters['end_date']]);

        if (! empty($filters['employee_ids'])) {
            $query->whereIn('employee_id', $filters['employee_ids']);
        }

        if (! empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        $records = $query->orderBy('date', 'desc')->get();

        // Calculate statistics
        $stats = [
            'total_records' => $records->count(),
            'present_count' => $records->whereIn('status', ['present', 'late'])->count(),
            'late_count' => $records->where('status', 'late')->count(),
            'absent_count' => $records->where('status', 'absent')->count(),
            'total_hours' => $records->sum('total_hours'),
            'average_hours' => $records->avg('total_hours'),
        ];

        // Daily breakdown
        $dailyBreakdown = $records
            ->groupBy(function ($item) {
                return $item->date->format('Y-m-d');
            })
            ->map(function ($dayRecords) {
                return [
                    'date' => $dayRecords->first()->date->format('Y-m-d'),
                    'total' => $dayRecords->count(),
                    'present' => $dayRecords->whereIn('status', ['present', 'late'])->count(),
                    'late' => $dayRecords->where('status', 'late')->count(),
                    'absent' => $dayRecords->where('status', 'absent')->count(),
                    'total_hours' => $dayRecords->sum('total_hours'),
                ];
            })
            ->values();

        return [
            'records' => $records,
            'stats' => $stats,
            'daily_breakdown' => $dailyBreakdown,
        ];
    }

    private function getLeaveReportData(array $filters): array
    {
        $query = Leave::with(['employee', 'leaveType', 'approver']);

        // Apply filters
        $query->whereBetween('start_date', [$filters['start_date'], $filters['end_date']]);

        if (! empty($filters['employee_ids'])) {
            $query->whereIn('employee_id', $filters['employee_ids']);
        }

        if (! empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        if (! empty($filters['leave_type_ids'])) {
            $query->whereIn('leave_type_id', $filters['leave_type_ids']);
        }

        $records = $query->orderBy('start_date', 'desc')->get();

        // Calculate statistics
        $stats = [
            'total_requests' => $records->count(),
            'approved_requests' => $records->where('status', 'approved')->count(),
            'pending_requests' => $records->where('status', 'pending')->count(),
            'rejected_requests' => $records->where('status', 'rejected')->count(),
            'total_days' => $records->sum('days_requested'),
            'approved_days' => $records->where('status', 'approved')->sum('days_requested'),
        ];

        // Leave type breakdown
        $typeBreakdown = $records
            ->groupBy('leave_type_id')
            ->map(function ($typeRecords) {
                return [
                    'type' => $typeRecords->first()->leaveType->name ?? 'Unknown',
                    'count' => $typeRecords->count(),
                    'total_days' => $typeRecords->sum('days_requested'),
                    'approved_count' => $typeRecords->where('status', 'approved')->count(),
                ];
            })
            ->values();

        return [
            'records' => $records,
            'stats' => $stats,
            'type_breakdown' => $typeBreakdown,
        ];
    }

    private function getPayrollReportData(array $filters): array
    {
        $query = Payroll::with(['employee']);

        // Apply filters
        $query->whereBetween('payroll_period_start', [$filters['start_date'], $filters['end_date']]);

        if (! empty($filters['employee_ids'])) {
            $query->whereIn('employee_id', $filters['employee_ids']);
        }

        if (! empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        $records = $query->orderBy('payroll_period_start', 'desc')->get();

        // Calculate statistics
        $stats = [
            'total_records' => $records->count(),
            'total_gross_salary' => $records->sum('gross_salary'),
            'total_deductions' => $records->sum('total_deductions'),
            'total_bonuses' => $records->sum('total_bonuses'),
            'total_net_salary' => $records->sum('net_salary'),
            'total_hours' => $records->sum('worked_hours'),
            'total_overtime' => $records->sum('overtime_hours'),
            'average_salary' => $records->avg('net_salary'),
        ];

        return [
            'records' => $records,
            'stats' => $stats,
        ];
    }

    private function getEmployeeReportData(array $filters): array
    {
        $query = Employee::with(['user', 'location']);

        // Apply filters
        if (! empty($filters['employee_type'])) {
            $query->whereIn('employee_type', $filters['employee_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (! empty($filters['location_ids'])) {
            $query->whereIn('location_id', $filters['location_ids']);
        }

        $records = $query->orderBy('first_name')->get();

        // Calculate statistics
        $stats = [
            'total_employees' => $records->count(),
            'active_employees' => $records->where('is_active', true)->count(),
            'inactive_employees' => $records->where('is_active', false)->count(),
            'average_salary' => $records->avg('salary_amount'),
            'total_salary_cost' => $records->sum('salary_amount'),
        ];

        return [
            'records' => $records,
            'stats' => $stats,
        ];
    }

    private function getSummaryReportData(array $filters): array
    {
        $startDate = Carbon::parse($filters['start_date']);
        $endDate = Carbon::parse($filters['end_date']);
        $grouping = $filters['grouping'];

        $summaryData = [];

        // Group data based on grouping parameter
        switch ($grouping) {
            case 'daily':
                $current = $startDate->copy();
                while ($current <= $endDate) {
                    $summaryData[] = $this->getDailySummary($current);
                    $current->addDay();
                }
                break;
            case 'weekly':
                $current = $startDate->copy()->startOfWeek();
                while ($current <= $endDate) {
                    $weekEnd = $current->copy()->endOfWeek();
                    if ($weekEnd > $endDate) {
                        $weekEnd = $endDate;
                    }
                    $summaryData[] = $this->getWeeklySummary($current, $weekEnd);
                    $current->addWeek();
                }
                break;
            case 'monthly':
                $current = $startDate->copy()->startOfMonth();
                while ($current <= $endDate) {
                    $monthEnd = $current->copy()->endOfMonth();
                    if ($monthEnd > $endDate) {
                        $monthEnd = $endDate;
                    }
                    $summaryData[] = $this->getMonthlySummary($current, $monthEnd);
                    $current->addMonth();
                }
                break;
        }

        return [
            'summary_data' => $summaryData,
            'grouping' => $grouping,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
        ];
    }

    private function getDailySummary(Carbon $date): array
    {
        $attendance = Attendance::whereDate('date', $date)->get();
        $leaves = Leave::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('status', 'approved')
            ->get();

        return [
            'date' => $date->format('Y-m-d'),
            'attendance_count' => $attendance->count(),
            'present_count' => $attendance->whereIn('status', ['present', 'late'])->count(),
            'leave_count' => $leaves->count(),
            'total_hours' => $attendance->sum('total_hours'),
        ];
    }

    private function getWeeklySummary(Carbon $startDate, Carbon $endDate): array
    {
        $attendance = Attendance::whereBetween('date', [$startDate, $endDate])->get();
        $leaves = Leave::where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->where('status', 'approved')
            ->get();

        return [
            'week_start' => $startDate->format('Y-m-d'),
            'week_end' => $endDate->format('Y-m-d'),
            'attendance_count' => $attendance->count(),
            'present_count' => $attendance->whereIn('status', ['present', 'late'])->count(),
            'leave_count' => $leaves->count(),
            'total_hours' => $attendance->sum('total_hours'),
        ];
    }

    private function getMonthlySummary(Carbon $startDate, Carbon $endDate): array
    {
        $attendance = Attendance::whereBetween('date', [$startDate, $endDate])->get();
        $leaves = Leave::where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->where('status', 'approved')
            ->get();
        $payrolls = Payroll::whereBetween('payroll_period_start', [$startDate, $endDate])->get();

        return [
            'month' => $startDate->format('Y-m'),
            'month_name' => $startDate->format('F Y'),
            'attendance_count' => $attendance->count(),
            'present_count' => $attendance->whereIn('status', ['present', 'late'])->count(),
            'leave_count' => $leaves->count(),
            'payroll_count' => $payrolls->count(),
            'total_hours' => $attendance->sum('total_hours'),
            'total_payroll_amount' => $payrolls->sum('net_salary'),
        ];
    }

    private function buildCustomReport(
        string $reportType,
        array $dateRange,
        array $filters,
        array $columns,
        ?string $grouping = null,
        array $sorting = [],
    ): array {
        switch ($reportType) {
            case 'attendance':
                return $this->getAttendanceReportData(array_merge($dateRange, $filters));
            case 'leave':
                return $this->getLeaveReportData(array_merge($dateRange, $filters));
            case 'payroll':
                return $this->getPayrollReportData(array_merge($dateRange, $filters));
            case 'employee':
                return $this->getEmployeeReportData($filters);
            case 'summary':
                return $this->getSummaryReportData(
                    array_merge($dateRange, $filters, ['grouping' => $grouping]),
                );
            default:
                throw new \InvalidArgumentException('Invalid report type');
        }
    }

    private function getReportTypes(): array
    {
        return [
            'attendance' => [
                'name' => 'Attendance Reports',
                'description' => 'Track employee attendance, punctuality, and working hours',
                'icon' => 'calendar-check',
            ],
            'leave' => [
                'name' => 'Leave Reports',
                'description' => 'Analyze leave requests, approvals, and patterns',
                'icon' => 'calendar-x',
            ],
            'payroll' => [
                'name' => 'Payroll Reports',
                'description' => 'Monitor salary payments, deductions, and bonuses',
                'icon' => 'currency-dollar',
            ],
            'employee' => [
                'name' => 'Employee Reports',
                'description' => 'Comprehensive employee information and statistics',
                'icon' => 'users',
            ],
            'summary' => [
                'name' => 'Summary Reports',
                'description' => 'High-level overview of all system metrics',
                'icon' => 'chart-bar',
            ],
        ];
    }

    private function getQuickStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'todays_attendance' => Attendance::whereDate('date', $today)->count(),
            'pending_leaves' => Leave::where('status', 'pending')->count(),
            'monthly_payrolls' => Payroll::whereBetween('payroll_period_start', [
                $thisMonth,
                $today,
            ])->count(),
            'total_employees' => Employee::where('is_active', true)->count(),
        ];
    }

    private function getAttendanceSummaryData(array $filters): array
    {
        $attendanceData = $this->getAttendanceReportData($filters);

        // Add additional summary calculations
        $employeeStats = $attendanceData['records']
            ->groupBy('employee_id')
            ->map(function ($employeeRecords) {
                return [
                    'employee_name' => $employeeRecords->first()->employee->full_name,
                    'total_days' => $employeeRecords->count(),
                    'present_days' => $employeeRecords->whereIn('status', ['present', 'late'])->count(),
                    'late_days' => $employeeRecords->where('status', 'late')->count(),
                    'absent_days' => $employeeRecords->where('status', 'absent')->count(),
                    'total_hours' => $employeeRecords->sum('total_hours'),
                    'attendance_rate' => $employeeRecords->count() > 0 ?
                      ($employeeRecords->whereIn('status', ['present', 'late'])->count() / $employeeRecords->count()) * 100 : 0,
                ];
            })
            ->values();

        return array_merge($attendanceData, ['employee_stats' => $employeeStats]);
    }

    private function getLeaveAnalyticsData(array $filters): array
    {
        $leaveData = $this->getLeaveReportData($filters);

        // Add trend analysis
        $monthlyTrends = $leaveData['records']
            ->groupBy(function ($leave) {
                return $leave->start_date->format('Y-m');
            })
            ->map(function ($monthLeaves) {
                return [
                    'month' => $monthLeaves->first()->start_date->format('Y-m'),
                    'total_requests' => $monthLeaves->count(),
                    'approved_requests' => $monthLeaves->where('status', 'approved')->count(),
                    'total_days' => $monthLeaves->sum('days_requested'),
                ];
            })
            ->values();

        return array_merge($leaveData, ['monthly_trends' => $monthlyTrends]);
    }

    private function getEmployeePerformanceData(array $filters): array
    {
        $employeeData = $this->getEmployeeReportData($filters);

        // Add performance metrics for each employee
        $performanceMetrics = $employeeData['records']->map(function ($employee) {
            $attendanceCount = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', '>=', Carbon::now()->subDays(30))
                ->count();

            $presentCount = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', '>=', Carbon::now()->subDays(30))
                ->whereIn('status', ['present', 'late'])
                ->count();

            $leaveCount = Leave::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '>=', Carbon::now()->subDays(30))
                ->count();

            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'attendance_days' => $attendanceCount,
                'present_days' => $presentCount,
                'leave_days' => $leaveCount,
                'attendance_rate' => $attendanceCount > 0 ? ($presentCount / $attendanceCount) * 100 : 0,
                'performance_score' => $attendanceCount > 0 ?
                  min(100, (($presentCount / $attendanceCount) * 0.8 + (max(0, 30 - $leaveCount) / 30) * 0.2) * 100) : 0,
            ];
        });

        return array_merge($employeeData, ['performance_metrics' => $performanceMetrics]);
    }

    private function getPayrollSummaryData(array $filters): array
    {
        $payrollData = $this->getPayrollReportData($filters);

        // Add department-wise breakdown
        $departmentBreakdown = $payrollData['records']
            ->groupBy(function ($payroll) {
                return $payroll->employee->department ?? 'Unknown';
            })
            ->map(function ($deptPayrolls) {
                return [
                    'department' => $deptPayrolls->first()->employee->department ?? 'Unknown',
                    'employee_count' => $deptPayrolls->count(),
                    'total_gross' => $deptPayrolls->sum('gross_salary'),
                    'total_deductions' => $deptPayrolls->sum('total_deductions'),
                    'total_net' => $deptPayrolls->sum('net_salary'),
                    'average_salary' => $deptPayrolls->avg('net_salary'),
                ];
            })
            ->values();

        return array_merge($payrollData, ['department_breakdown' => $departmentBreakdown]);
    }

    private function getFilterOptions(): array
    {
        return [
            'employees' => Employee::where('is_active', true)
                ->select('id', 'first_name', 'last_name', 'employee_id')
                ->get(),
            'locations' => Location::where('is_active', true)->select('id', 'name')->get(),
            'leave_types' => LeaveType::where('is_active', true)->select('id', 'name')->get(),
            'attendance_statuses' => ['present', 'late', 'absent', 'incomplete'],
            'leave_statuses' => ['pending', 'approved', 'rejected', 'cancelled'],
            'payroll_statuses' => ['draft', 'pending', 'approved', 'processed', 'paid', 'cancelled'],
            'employee_types' => ['full_time', 'part_time', 'contract', 'temporary'],
            'export_formats' => $this->exportService->getSupportedFormats(),
        ];
    }
}
