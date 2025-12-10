<?php

namespace App\Http\Controllers\Api;

use App\Services\Reports\DashboardReportService;
use App\Services\Reports\AttendanceReportService;
use App\Services\Reports\EmployeeReportService;
use App\Services\Reports\DepartmentReportService;
use App\Services\Reports\LeaveReportService;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportsApiController extends BaseApiController
{
    public function __construct(
        private DashboardReportService $dashboardService,
        private AttendanceReportService $attendanceService,
        private EmployeeReportService $employeeService,
        private DepartmentReportService $departmentService,
        private LeaveReportService $leaveService,
        private ReportExportService $exportService
    ) {}

    /**
     * Get dashboard statistics
     */
    public function dashboard(Request $request)
    {
        $data = $this->dashboardService->getDashboard();
        return $this->apiResponse($data, 'Dashboard data retrieved');
    }

    /**
     * Get summary statistics for a date range
     */
    public function summary(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $data = $this->dashboardService->getSummary($startDate, $endDate);
        return $this->apiResponse($data, 'Summary retrieved');
    }

    /**
     * Get attendance summary for current employee (employee-only view)
     */
    public function myAttendanceSummary(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->errorResponse('Employee record not found', 404);
        }

        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $data = $this->employeeService->getMyAttendanceSummary($employee, $startDate, $endDate);
        return $this->apiResponse($data, 'Your attendance summary retrieved');
    }

    /**
     * Get monthly attendance data for charts
     */
    public function monthlyAttendance(Request $request)
    {
        $year = $request->get('year', now()->year);
        $data = $this->attendanceService->getMonthlyAttendance($year);
        return $this->apiResponse($data, 'Monthly attendance retrieved');
    }

    /**
     * Get weekly attendance trend
     */
    public function weeklyTrend(Request $request)
    {
        $weeks = $request->get('weeks', 4);
        $data = $this->attendanceService->getWeeklyTrend($weeks);
        return $this->apiResponse($data, 'Weekly trend retrieved');
    }

    /**
     * Get department statistics
     */
    public function departmentStats(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $data = $this->departmentService->getDepartmentStats($startDate, $endDate);
        return $this->apiResponse($data, 'Department stats retrieved');
    }

    /**
     * Get leave statistics
     */
    public function leaveStats(Request $request)
    {
        $year = $request->get('year', now()->year);
        $data = $this->leaveService->getLeaveStats($year);
        return $this->apiResponse($data, 'Leave stats retrieved');
    }

    /**
     * Get monthly attendance recap with A/I/S/D/C breakdown
     */
    public function monthlyRecap(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $departmentFilter = $request->get('department', null);

        $data = $this->attendanceService->getMonthlyRecap($month, $year, $departmentFilter);
        return $this->apiResponse($data, 'Monthly attendance recap retrieved');
    }

    /**
     * Generate report (PDF/Excel export)
     */
    public function generate(Request $request)
    {
        // 1. SECURITY: Admin-only access for export
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

        // 2. VALIDATION
        $validated = $request->validate([
            'type' => 'required|in:attendance,leave,payroll,summary',
            'format' => 'required|in:pdf,excel',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'filters' => 'nullable|array',
        ]);

        try {
            $result = $this->exportService->generate($validated, $request->all(), $request->user()->id);

            $statusCode = $result['generated_async'] ?? false ? 202 : 201;
            $message = $result['cached'] ?? false
                ? 'Report retrieved from cache'
                : ($result['generated_async'] ?? false ? 'Report queued for generation' : 'Report generated successfully');

            return $this->apiResponse($result, $message, $statusCode);

        } catch (\Exception $e) {
            Log::error('Report generation failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Failed to generate report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get list of report templates
     */
    public function templates()
    {
        $templates = [
            ['id' => '1', 'name' => 'Laporan Kehadiran Bulanan', 'type' => 'attendance', 'description' => 'Rekap kehadiran per bulan'],
            ['id' => '2', 'name' => 'Laporan Cuti', 'type' => 'leave', 'description' => 'Rekap pengajuan cuti'],
            ['id' => '3', 'name' => 'Laporan Ringkasan', 'type' => 'summary', 'description' => 'Ringkasan keseluruhan'],
        ];

        return $this->apiResponse($templates, 'Templates retrieved');
    }

    /**
     * Get list of generated reports
     */
    public function generatedReports(Request $request)
    {
        $reports = $this->exportService->getGeneratedReports($request->user()->id);
        return $this->apiResponse($reports, 'Generated reports retrieved');
    }

    /**
     * Get specific generated report
     */
    public function showGeneratedReport(Request $request, string $id)
    {
        $report = $this->exportService->getGeneratedReport($id, $request->user()->id);

        if (!$report) {
            return $this->errorResponse('Report not found', 404);
        }

        return $this->apiResponse($report, 'Report details retrieved');
    }

    /**
     * Legacy method: Get full report data
     * @deprecated Use specific methods instead
     */
    public function data(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $data = [
            'attendance_summary' => $this->attendanceService->getMonthlyAttendance(now()->year),
            'department_breakdown' => $this->departmentService->getDepartmentStats($startDate, $endDate),
            'leave_distribution' => $this->leaveService->getLeaveStats(now()->year),
        ];

        return $this->apiResponse($data, 'Report data retrieved');
    }
}
