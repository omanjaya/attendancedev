<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportFilterService
{
    public function getAttendanceFilters(Request $request): array
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

    public function getLeaveFilters(Request $request): array
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

    public function getPayrollFilters(Request $request): array
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

    public function getEmployeeFilters(Request $request): array
    {
        return [
            'employee_type' => $request->get('employee_type', []),
            'is_active' => $request->get('is_active'),
            'location_ids' => $request->get('location_ids', []),
            'hire_date_start' => $request->get('hire_date_start'),
            'hire_date_end' => $request->get('hire_date_end'),
        ];
    }

    public function getSummaryFilters(Request $request): array
    {
        return [
            'start_date' => $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
            'end_date' => $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
            'grouping' => $request->get('grouping', 'monthly'),
            'metrics' => $request->get('metrics', ['attendance', 'leave', 'payroll']),
        ];
    }
}
