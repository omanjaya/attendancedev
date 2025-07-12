<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use League\Csv\Writer;

class ExportService
{
    /**
     * Export attendance data
     */
    public function exportAttendance(array $filters, string $format = 'csv')
    {
        $query = Attendance::with(['employee']);

        // Apply filters
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('date', [$filters['start_date'], $filters['end_date']]);
        }

        if (isset($filters['employee_ids']) && !empty($filters['employee_ids'])) {
            $query->whereIn('employee_id', $filters['employee_ids']);
        }

        if (isset($filters['status']) && !empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        $attendanceRecords = $query->orderBy('date', 'desc')->get();

        switch ($format) {
            case 'csv':
                return $this->exportAttendanceCSV($attendanceRecords);
            case 'excel':
                return $this->exportAttendanceExcel($attendanceRecords);
            case 'pdf':
                return $this->exportAttendancePDF($attendanceRecords, $filters);
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    /**
     * Export leave data
     */
    public function exportLeave(array $filters, string $format = 'csv')
    {
        $query = Leave::with(['employee', 'leaveType', 'approver']);

        // Apply filters
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('start_date', [$filters['start_date'], $filters['end_date']]);
        }

        if (isset($filters['employee_ids']) && !empty($filters['employee_ids'])) {
            $query->whereIn('employee_id', $filters['employee_ids']);
        }

        if (isset($filters['status']) && !empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        if (isset($filters['leave_type_ids']) && !empty($filters['leave_type_ids'])) {
            $query->whereIn('leave_type_id', $filters['leave_type_ids']);
        }

        $leaveRecords = $query->orderBy('start_date', 'desc')->get();

        switch ($format) {
            case 'csv':
                return $this->exportLeaveCSV($leaveRecords);
            case 'excel':
                return $this->exportLeaveExcel($leaveRecords);
            case 'pdf':
                return $this->exportLeavePDF($leaveRecords, $filters);
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    /**
     * Export payroll data
     */
    public function exportPayroll(array $filters, string $format = 'csv')
    {
        $query = Payroll::with(['employee', 'payrollItems']);

        // Apply filters
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('payroll_period_start', [$filters['start_date'], $filters['end_date']]);
        }

        if (isset($filters['employee_ids']) && !empty($filters['employee_ids'])) {
            $query->whereIn('employee_id', $filters['employee_ids']);
        }

        if (isset($filters['status']) && !empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        $payrollRecords = $query->orderBy('payroll_period_start', 'desc')->get();

        switch ($format) {
            case 'csv':
                return $this->exportPayrollCSV($payrollRecords);
            case 'excel':
                return $this->exportPayrollExcel($payrollRecords);
            case 'pdf':
                return $this->exportPayrollPDF($payrollRecords, $filters);
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    /**
     * Export employee data
     */
    public function exportEmployees(array $filters, string $format = 'csv')
    {
        $query = Employee::with(['user', 'location']);

        // Apply filters
        if (isset($filters['employee_type']) && !empty($filters['employee_type'])) {
            $query->whereIn('employee_type', $filters['employee_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['location_ids']) && !empty($filters['location_ids'])) {
            $query->whereIn('location_id', $filters['location_ids']);
        }

        $employees = $query->orderBy('first_name')->get();

        switch ($format) {
            case 'csv':
                return $this->exportEmployeesCSV($employees);
            case 'excel':
                return $this->exportEmployeesExcel($employees);
            case 'pdf':
                return $this->exportEmployeesPDF($employees, $filters);
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    /**
     * Export analytics summary
     */
    public function exportAnalyticsSummary(array $data, string $format = 'pdf')
    {
        switch ($format) {
            case 'pdf':
                return $this->exportAnalyticsSummaryPDF($data);
            case 'csv':
                return $this->exportAnalyticsSummaryCSV($data);
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    // Attendance Export Methods

    private function exportAttendanceCSV(Collection $records)
    {
        $csv = Writer::createFromString('');
        
        // Add headers
        $csv->insertOne([
            'Date',
            'Employee ID',
            'Employee Name',
            'Check In',
            'Check Out',
            'Total Hours',
            'Status',
            'Late (Minutes)',
            'Notes'
        ]);

        // Add data rows
        foreach ($records as $record) {
            $checkInTime = $record->check_in_time ? $record->check_in_time->format('H:i:s') : '';
            $checkOutTime = $record->check_out_time ? $record->check_out_time->format('H:i:s') : '';
            $lateMinutes = $this->calculateLateMinutes($record);

            $csv->insertOne([
                $record->date->format('Y-m-d'),
                $record->employee->employee_id ?? '',
                $record->employee->full_name ?? '',
                $checkInTime,
                $checkOutTime,
                number_format($record->total_hours ?? 0, 2),
                ucfirst($record->status),
                $lateMinutes,
                $record->check_in_notes . ' ' . $record->check_out_notes
            ]);
        }

        $filename = 'attendance_report_' . date('Y-m-d_H-i-s') . '.csv';
        
        return response($csv->toString())
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function exportAttendanceExcel(Collection $records)
    {
        // For now, return CSV format with .xlsx extension
        // In a full implementation, you'd use a library like PhpSpreadsheet
        $csv = $this->exportAttendanceCSV($records);
        $filename = 'attendance_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return $csv->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function exportAttendancePDF(Collection $records, array $filters)
    {
        $html = view('exports.attendance-pdf', [
            'records' => $records,
            'filters' => $filters,
            'generated_at' => now(),
            'generated_by' => auth()->user()->name
        ])->render();

        return $this->generatePDF($html, 'attendance_report_' . date('Y-m-d_H-i-s') . '.pdf');
    }

    // Leave Export Methods

    private function exportLeaveCSV(Collection $records)
    {
        $csv = Writer::createFromString('');
        
        // Add headers
        $csv->insertOne([
            'Employee ID',
            'Employee Name',
            'Leave Type',
            'Start Date',
            'End Date',
            'Days Requested',
            'Status',
            'Reason',
            'Applied Date',
            'Approved By',
            'Approved Date',
            'Approval Notes'
        ]);

        // Add data rows
        foreach ($records as $record) {
            $csv->insertOne([
                $record->employee->employee_id ?? '',
                $record->employee->full_name ?? '',
                $record->leaveType->name ?? '',
                $record->start_date->format('Y-m-d'),
                $record->end_date->format('Y-m-d'),
                $record->days_requested,
                ucfirst($record->status),
                $record->reason,
                $record->created_at->format('Y-m-d'),
                $record->approver->full_name ?? '',
                $record->approved_at ? $record->approved_at->format('Y-m-d') : '',
                $record->approval_notes ?? ''
            ]);
        }

        $filename = 'leave_report_' . date('Y-m-d_H-i-s') . '.csv';
        
        return response($csv->toString())
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function exportLeaveExcel(Collection $records)
    {
        $csv = $this->exportLeaveCSV($records);
        $filename = 'leave_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return $csv->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function exportLeavePDF(Collection $records, array $filters)
    {
        $html = view('exports.leave-pdf', [
            'records' => $records,
            'filters' => $filters,
            'generated_at' => now(),
            'generated_by' => auth()->user()->name
        ])->render();

        return $this->generatePDF($html, 'leave_report_' . date('Y-m-d_H-i-s') . '.pdf');
    }

    // Payroll Export Methods

    private function exportPayrollCSV(Collection $records)
    {
        $csv = Writer::createFromString('');
        
        // Add headers
        $csv->insertOne([
            'Employee ID',
            'Employee Name',
            'Period Start',
            'Period End',
            'Pay Date',
            'Gross Salary',
            'Total Deductions',
            'Total Bonuses',
            'Net Salary',
            'Worked Hours',
            'Overtime Hours',
            'Status'
        ]);

        // Add data rows
        foreach ($records as $record) {
            $csv->insertOne([
                $record->employee->employee_id ?? '',
                $record->employee->full_name ?? '',
                $record->payroll_period_start->format('Y-m-d'),
                $record->payroll_period_end->format('Y-m-d'),
                $record->pay_date ? $record->pay_date->format('Y-m-d') : '',
                number_format($record->gross_salary, 2),
                number_format($record->total_deductions, 2),
                number_format($record->total_bonuses, 2),
                number_format($record->net_salary, 2),
                number_format($record->worked_hours, 2),
                number_format($record->overtime_hours, 2),
                ucfirst($record->status)
            ]);
        }

        $filename = 'payroll_report_' . date('Y-m-d_H-i-s') . '.csv';
        
        return response($csv->toString())
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function exportPayrollExcel(Collection $records)
    {
        $csv = $this->exportPayrollCSV($records);
        $filename = 'payroll_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return $csv->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function exportPayrollPDF(Collection $records, array $filters)
    {
        $html = view('exports.payroll-pdf', [
            'records' => $records,
            'filters' => $filters,
            'generated_at' => now(),
            'generated_by' => auth()->user()->name,
            'totals' => [
                'gross_salary' => $records->sum('gross_salary'),
                'total_deductions' => $records->sum('total_deductions'),
                'total_bonuses' => $records->sum('total_bonuses'),
                'net_salary' => $records->sum('net_salary'),
                'worked_hours' => $records->sum('worked_hours'),
                'overtime_hours' => $records->sum('overtime_hours'),
            ]
        ])->render();

        return $this->generatePDF($html, 'payroll_report_' . date('Y-m-d_H-i-s') . '.pdf');
    }

    // Employee Export Methods

    private function exportEmployeesCSV(Collection $records)
    {
        $csv = Writer::createFromString('');
        
        // Add headers
        $csv->insertOne([
            'Employee ID',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Employee Type',
            'Hire Date',
            'Salary Type',
            'Salary Amount',
            'Hourly Rate',
            'Location',
            'Status'
        ]);

        // Add data rows
        foreach ($records as $record) {
            $csv->insertOne([
                $record->employee_id,
                $record->first_name,
                $record->last_name,
                $record->user->email ?? '',
                $record->phone ?? '',
                ucfirst($record->employee_type),
                $record->hire_date ? $record->hire_date->format('Y-m-d') : '',
                ucfirst($record->salary_type),
                number_format($record->salary_amount ?? 0, 2),
                number_format($record->hourly_rate ?? 0, 2),
                $record->location->name ?? '',
                $record->is_active ? 'Active' : 'Inactive'
            ]);
        }

        $filename = 'employees_report_' . date('Y-m-d_H-i-s') . '.csv';
        
        return response($csv->toString())
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function exportEmployeesExcel(Collection $records)
    {
        $csv = $this->exportEmployeesCSV($records);
        $filename = 'employees_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return $csv->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function exportEmployeesPDF(Collection $records, array $filters)
    {
        $html = view('exports.employees-pdf', [
            'records' => $records,
            'filters' => $filters,
            'generated_at' => now(),
            'generated_by' => auth()->user()->name
        ])->render();

        return $this->generatePDF($html, 'employees_report_' . date('Y-m-d_H-i-s') . '.pdf');
    }

    // Analytics Export Methods

    private function exportAnalyticsSummaryPDF(array $data)
    {
        $html = view('exports.analytics-summary-pdf', [
            'data' => $data,
            'generated_at' => now(),
            'generated_by' => auth()->user()->name
        ])->render();

        return $this->generatePDF($html, 'analytics_summary_' . date('Y-m-d_H-i-s') . '.pdf');
    }

    private function exportAnalyticsSummaryCSV(array $data)
    {
        $csv = Writer::createFromString('');
        
        // Add KPI section
        $csv->insertOne(['KEY PERFORMANCE INDICATORS']);
        $csv->insertOne(['Metric', 'Value']);
        
        if (isset($data['kpis']['attendance'])) {
            $csv->insertOne(['Attendance Rate (%)', $data['kpis']['attendance']['rate']]);
            $csv->insertOne(['Punctuality Rate (%)', $data['kpis']['attendance']['punctuality_rate']]);
            $csv->insertOne(['Absenteeism Rate (%)', $data['kpis']['attendance']['absenteeism_rate']]);
        }
        
        if (isset($data['kpis']['leave'])) {
            $csv->insertOne(['Leave Approval Rate (%)', $data['kpis']['leave']['approval_rate']]);
            $csv->insertOne(['Total Leave Requests', $data['kpis']['leave']['total_requests']]);
        }
        
        if (isset($data['kpis']['payroll'])) {
            $csv->insertOne(['Total Payroll Amount', number_format($data['kpis']['payroll']['total_amount'], 2)]);
            $csv->insertOne(['Average Salary', number_format($data['kpis']['payroll']['average_salary'], 2)]);
        }

        $csv->insertOne(['']); // Empty row

        $filename = 'analytics_summary_' . date('Y-m-d_H-i-s') . '.csv';
        
        return response($csv->toString())
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    // Helper Methods

    private function generatePDF(string $html, string $filename)
    {
        // For this implementation, we'll return the HTML as a PDF-like response
        // In a full implementation, you'd use a library like DomPDF or wkhtmltopdf
        
        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function calculateLateMinutes($attendance)
    {
        if (!$attendance->check_in_time || $attendance->status !== 'late') {
            return 0;
        }

        // Assuming standard start time is 9:00 AM
        $standardStartTime = Carbon::parse($attendance->date->format('Y-m-d') . ' 09:00:00');
        $checkInTime = Carbon::parse($attendance->check_in_time);

        if ($checkInTime->isAfter($standardStartTime)) {
            return $checkInTime->diffInMinutes($standardStartTime);
        }

        return 0;
    }

    /**
     * Get supported export formats
     */
    public function getSupportedFormats(): array
    {
        return ['csv', 'excel', 'pdf'];
    }

    /**
     * Validate export format
     */
    public function isValidFormat(string $format): bool
    {
        return in_array($format, $this->getSupportedFormats());
    }
}