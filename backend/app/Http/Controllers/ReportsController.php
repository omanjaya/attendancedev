<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\Payroll;
use App\Services\ExportService;
use App\Services\Reports\ReportFilterService;
use App\Services\Reports\ReportDataService;
use App\Services\Reports\ReportSummaryService;
use App\Services\Reports\ReportAnalyticsService;
use App\Services\Reports\ReportHelperService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private ExportService $exportService,
        private ReportFilterService $filterService,
        private ReportDataService $dataService,
        private ReportSummaryService $summaryService,
        private ReportAnalyticsService $analyticsService,
        private ReportHelperService $helperService
    ) {}

    /**
     * Main reports dashboard
     */
    public function index()
    {
        $reportTypes = $this->helperService->getReportTypes();
        $quickStats = $this->helperService->getQuickStats();

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
        $filterOptions = $this->helperService->getFilterOptions();

        return view('pages.reports.builder', [
            'filterOptions' => $filterOptions,
        ]);
    }

    /**
     * Attendance Reports
     */
    public function attendance(Request $request)
    {
        $filters = $this->filterService->getAttendanceFilters($request);
        $attendanceData = $this->dataService->getAttendanceReportData($filters);

        if ($request->wantsJson()) {
            return $this->successResponse([
                'data' => $attendanceData,
                'filters' => $filters,
            ], 'Attendance report generated successfully');
        }

        return view('pages.reports.attendance', [
            'data' => $attendanceData,
            'filters' => $filters,
            'filterOptions' => $this->helperService->getFilterOptions(),
        ]);
    }

    /**
     * Leave Reports
     */
    public function leave(Request $request)
    {
        $filters = $this->filterService->getLeaveFilters($request);
        $leaveData = $this->dataService->getLeaveReportData($filters);

        if ($request->wantsJson()) {
            return $this->successResponse([
                'data' => $leaveData,
                'filters' => $filters,
            ], 'Leave report generated successfully');
        }

        return view('pages.reports.leave', [
            'data' => $leaveData,
            'filters' => $filters,
            'filterOptions' => $this->helperService->getFilterOptions(),
        ]);
    }

    /**
     * Payroll Reports
     */
    public function payroll(Request $request)
    {
        $filters = $this->filterService->getPayrollFilters($request);
        $payrollData = $this->dataService->getPayrollReportData($filters);

        if ($request->wantsJson()) {
            return $this->successResponse([
                'data' => $payrollData,
                'filters' => $filters,
            ], 'Payroll report generated successfully');
        }

        return view('pages.reports.payroll', [
            'data' => $payrollData,
            'filters' => $filters,
            'filterOptions' => $this->helperService->getFilterOptions(),
        ]);
    }

    /**
     * Employee Reports
     */
    public function employees(Request $request)
    {
        $filters = $this->filterService->getEmployeeFilters($request);
        $employeeData = $this->dataService->getEmployeeReportData($filters);

        if ($request->wantsJson()) {
            return $this->successResponse([
                'data' => $employeeData,
                'filters' => $filters,
            ], 'Employee report generated successfully');
        }

        return view('pages.reports.employees', [
            'data' => $employeeData,
            'filters' => $filters,
            'filterOptions' => $this->helperService->getFilterOptions(),
        ]);
    }

    /**
     * Summary Reports
     */
    public function summary(Request $request)
    {
        $filters = $this->filterService->getSummaryFilters($request);
        $summaryData = $this->summaryService->getSummaryReportData($filters);

        if ($request->wantsJson()) {
            return $this->successResponse([
                'data' => $summaryData,
                'filters' => $filters,
            ], 'Summary report generated successfully');
        }

        return view('pages.reports.summary', [
            'data' => $summaryData,
            'filters' => $filters,
            'filterOptions' => $this->helperService->getFilterOptions(),
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
            $data = $this->dataService->buildCustomReport(
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
        $filters = $this->filterService->getAttendanceFilters($request);
        $format = $request->get('format', 'csv');

        if (! $this->exportService->isValidFormat($format)) {
            return $this->errorResponse('Invalid export format', 400);
        }

        return $this->exportService->exportAttendance($filters, $format);
    }

    public function exportLeave(Request $request)
    {
        $filters = $this->filterService->getLeaveFilters($request);
        $format = $request->get('format', 'csv');

        if (! $this->exportService->isValidFormat($format)) {
            return $this->errorResponse('Invalid export format', 400);
        }

        return $this->exportService->exportLeave($filters, $format);
    }

    public function exportPayroll(Request $request)
    {
        $filters = $this->filterService->getPayrollFilters($request);
        $format = $request->get('format', 'csv');

        if (! $this->exportService->isValidFormat($format)) {
            return $this->errorResponse('Invalid export format', 400);
        }

        return $this->exportService->exportPayroll($filters, $format);
    }

    public function exportEmployee(Request $request)
    {
        $filters = $this->filterService->getEmployeeFilters($request);
        $format = $request->get('format', 'csv');

        if (! $this->exportService->isValidFormat($format)) {
            return $this->errorResponse('Invalid export format', 400);
        }

        return $this->exportService->exportEmployees($filters, $format);
    }

    public function attendanceSummary(Request $request)
    {
        $filters = $this->filterService->getAttendanceFilters($request);
        $summaryData = $this->analyticsService->getAttendanceSummaryData($filters);

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
        $filters = $this->filterService->getLeaveFilters($request);
        $analyticsData = $this->analyticsService->getLeaveAnalyticsData($filters);

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
        $filters = $this->filterService->getEmployeeFilters($request);
        $performanceData = $this->analyticsService->getEmployeePerformanceData($filters);

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
        $filters = $this->filterService->getPayrollFilters($request);
        $summaryData = $this->analyticsService->getPayrollSummaryData($filters);

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

}
