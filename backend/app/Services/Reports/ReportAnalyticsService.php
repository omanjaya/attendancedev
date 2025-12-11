<?php

namespace App\Services\Reports;

use App\Models\Attendance;
use App\Models\Leave;
use Carbon\Carbon;

class ReportAnalyticsService
{
    public function __construct(
        private ReportDataService $dataService
    ) {}

    public function getAttendanceSummaryData(array $filters): array
    {
        $attendanceData = $this->dataService->getAttendanceReportData($filters);

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

    public function getLeaveAnalyticsData(array $filters): array
    {
        $leaveData = $this->dataService->getLeaveReportData($filters);

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

    public function getEmployeePerformanceData(array $filters): array
    {
        $employeeData = $this->dataService->getEmployeeReportData($filters);

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

    public function getPayrollSummaryData(array $filters): array
    {
        $payrollData = $this->dataService->getPayrollReportData($filters);

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
}
