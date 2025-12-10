<?php

namespace App\Services\Reports;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\Payroll;
use App\Services\ExportService;
use Carbon\Carbon;

class ReportHelperService
{
    public function __construct(
        private ExportService $exportService
    ) {}

    public function getReportTypes(): array
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

    public function getQuickStats(): array
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

    public function getFilterOptions(): array
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
