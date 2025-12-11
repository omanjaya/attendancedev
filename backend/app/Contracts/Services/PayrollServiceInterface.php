<?php

namespace App\Contracts\Services;

use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Support\Collection;
use Carbon\Carbon;

interface PayrollServiceInterface
{
    /**
     * Calculate payroll for an employee
     */
    public function calculatePayroll(
        Employee $employee,
        Carbon $startDate,
        Carbon $endDate,
        bool $force = false
    ): Payroll;

    /**
     * Calculate payroll for multiple employees
     */
    public function calculateBulkPayroll(
        Collection $employees,
        Carbon $startDate,
        Carbon $endDate
    ): Collection;

    /**
     * Get payroll summary
     */
    public function getPayrollSummary(Carbon $month): array;

    /**
     * Calculate basic salary
     */
    public function calculateBasicSalary(Employee $employee, array $attendanceData): float;

    /**
     * Calculate overtime pay
     */
    public function calculateOvertime(Employee $employee, float $overtimeHours): float;

    /**
     * Calculate deductions
     */
    public function calculateDeductions(Employee $employee, float $grossSalary): array;

    /**
     * Calculate allowances
     */
    public function calculateAllowances(Employee $employee, array $attendanceData): array;

    /**
     * Process payroll payment
     */
    public function processPayment(Payroll $payroll): bool;

    /**
     * Generate pay slip
     */
    public function generatePaySlip(Payroll $payroll, string $format = 'pdf'): string;

    /**
     * Export payroll data
     */
    public function exportPayroll(Carbon $month, string $format = 'xlsx'): string;

    /**
     * Validate payroll calculation
     */
    public function validatePayroll(Payroll $payroll): array;
}