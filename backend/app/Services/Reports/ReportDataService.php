<?php

namespace App\Services\Reports;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;

class ReportDataService
{
    public function getAttendanceReportData(array $filters): array
    {
        $query = Attendance::with(['employee']);

        // Apply filters
        $query->whereBetween('date', [$filters['start_date'], $filters['end_date']]);

        if (!empty($filters['employee_ids'])) {
            $query->whereIn('employee_id', $filters['employee_ids']);
        }

        if (!empty($filters['status'])) {
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

    public function getLeaveReportData(array $filters): array
    {
        $query = Leave::with(['employee', 'leaveType', 'approver']);

        // Apply filters
        $query->whereBetween('start_date', [$filters['start_date'], $filters['end_date']]);

        if (!empty($filters['employee_ids'])) {
            $query->whereIn('employee_id', $filters['employee_ids']);
        }

        if (!empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        if (!empty($filters['leave_type_ids'])) {
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

    public function getPayrollReportData(array $filters): array
    {
        $query = Payroll::with(['employee']);

        // Apply filters
        $query->whereBetween('payroll_period_start', [$filters['start_date'], $filters['end_date']]);

        if (!empty($filters['employee_ids'])) {
            $query->whereIn('employee_id', $filters['employee_ids']);
        }

        if (!empty($filters['status'])) {
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

    public function getEmployeeReportData(array $filters): array
    {
        $query = Employee::with(['user', 'location']);

        // Apply filters
        if (!empty($filters['employee_type'])) {
            $query->whereIn('employee_type', $filters['employee_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['location_ids'])) {
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

    public function buildCustomReport(
        string $reportType,
        array $dateRange,
        array $filters,
        array $columns,
        ?string $grouping = null,
        array $sorting = []
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
                // Will be handled by ReportSummaryService
                return [];
            default:
                throw new \InvalidArgumentException('Invalid report type');
        }
    }
}
