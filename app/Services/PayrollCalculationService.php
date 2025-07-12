<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class PayrollCalculationService
{
    /**
     * Calculate payroll for an employee for a specific period.
     */
    public function calculatePayroll(Employee $employee, Carbon $periodStart, Carbon $periodEnd, array $options = []): Payroll
    {
        // Check if payroll already exists for this period
        $existingPayroll = $employee->payrolls()
            ->where('payroll_period_start', $periodStart->toDateString())
            ->where('payroll_period_end', $periodEnd->toDateString())
            ->first();

        if ($existingPayroll && !($options['force_recalculate'] ?? false)) {
            return $existingPayroll;
        }

        // Create or update payroll record
        $payroll = $existingPayroll ?: new Payroll();
        $payroll->fill([
            'employee_id' => $employee->id,
            'payroll_period_start' => $periodStart->toDateString(),
            'payroll_period_end' => $periodEnd->toDateString(),
            'pay_date' => $this->calculatePayDate($periodEnd),
            'status' => Payroll::STATUS_DRAFT,
        ]);

        // Calculate attendance data
        $attendanceData = $this->calculateAttendanceData($employee, $periodStart, $periodEnd);
        $payroll->worked_hours = $attendanceData['worked_hours'];
        $payroll->overtime_hours = $attendanceData['overtime_hours'];

        // Calculate leave data
        $leaveData = $this->calculateLeaveData($employee, $periodStart, $periodEnd);
        $payroll->leave_days_taken = $leaveData['total_days'];
        $payroll->leave_days_paid = $leaveData['paid_days'];
        $payroll->leave_days_unpaid = $leaveData['unpaid_days'];

        $payroll->save();

        // Clear existing payroll items if recalculating
        if ($existingPayroll) {
            $payroll->payrollItems()->delete();
        }

        // Calculate and create payroll items
        $this->calculateBasicSalary($payroll, $attendanceData, $leaveData, $options);
        $this->calculateOvertimePay($payroll, $attendanceData, $options);
        $this->calculateLeaveAdjustments($payroll, $leaveData, $options);
        $this->calculateBonuses($payroll, $options);
        $this->calculateDeductions($payroll, $options);

        // Recalculate totals
        $payroll->recalculateTotals();

        return $payroll;
    }

    /**
     * Calculate attendance data for the payroll period.
     */
    protected function calculateAttendanceData(Employee $employee, Carbon $periodStart, Carbon $periodEnd): array
    {
        $attendances = $employee->attendances()
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->where('status', '!=', 'absent')
            ->get();

        $workedHours = $attendances->sum('total_hours') ?? 0;
        $overtimeHours = 0;

        // Calculate overtime based on standard work hours
        $standardHoursPerDay = $this->getPayrollConfig('calculations.standard_hours_per_day', 8);
        $workingDays = $this->getWorkingDays($periodStart, $periodEnd);
        $standardHours = $workingDays * $standardHoursPerDay;

        if ($workedHours > $standardHours) {
            $overtimeHours = $workedHours - $standardHours;
        }

        return [
            'worked_hours' => $workedHours,
            'overtime_hours' => $overtimeHours,
            'attendances' => $attendances,
            'working_days' => $workingDays,
            'standard_hours' => $standardHours,
        ];
    }

    /**
     * Calculate leave data for the payroll period.
     */
    protected function calculateLeaveData(Employee $employee, Carbon $periodStart, Carbon $periodEnd): array
    {
        $leaves = $employee->leaves()
            ->where('status', Leave::STATUS_APPROVED)
            ->where(function ($query) use ($periodStart, $periodEnd) {
                $query->whereBetween('start_date', [$periodStart, $periodEnd])
                    ->orWhereBetween('end_date', [$periodStart, $periodEnd])
                    ->orWhere(function ($q) use ($periodStart, $periodEnd) {
                        $q->where('start_date', '<=', $periodStart)
                          ->where('end_date', '>=', $periodEnd);
                    });
            })
            ->with('leaveType')
            ->get();

        $totalDays = 0;
        $paidDays = 0;
        $unpaidDays = 0;

        foreach ($leaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date)->max($periodStart);
            $leaveEnd = Carbon::parse($leave->end_date)->min($periodEnd);
            $leaveDays = $this->getWorkingDaysBetween($leaveStart, $leaveEnd);

            $totalDays += $leaveDays;

            if ($leave->leaveType && $leave->leaveType->is_paid) {
                $paidDays += $leaveDays;
            } else {
                $unpaidDays += $leaveDays;
            }
        }

        return [
            'total_days' => $totalDays,
            'paid_days' => $paidDays,
            'unpaid_days' => $unpaidDays,
            'leaves' => $leaves,
        ];
    }

    /**
     * Calculate basic salary based on employee salary type.
     */
    protected function calculateBasicSalary(Payroll $payroll, array $attendanceData, array $leaveData, array $options): void
    {
        $employee = $payroll->employee;
        $basicSalaryAmount = 0;

        switch ($employee->salary_type) {
            case 'monthly':
                $basicSalaryAmount = $employee->salary_amount ?? 0;
                break;

            case 'hourly':
                $regularHours = min($attendanceData['worked_hours'], $attendanceData['standard_hours']);
                $basicSalaryAmount = $regularHours * ($employee->hourly_rate ?? 0);
                break;

            case 'fixed':
                $basicSalaryAmount = $employee->salary_amount ?? 0;
                break;
        }

        if ($basicSalaryAmount > 0) {
            PayrollItem::createBasicSalaryItem(
                $payroll->id,
                $basicSalaryAmount,
                'Basic Salary - ' . ucfirst($employee->salary_type)
            );
        }
    }

    /**
     * Calculate overtime pay.
     */
    protected function calculateOvertimePay(Payroll $payroll, array $attendanceData, array $options): void
    {
        if ($attendanceData['overtime_hours'] <= 0) {
            return;
        }

        $employee = $payroll->employee;
        $overtimeRate = $this->calculateOvertimeRate($employee);

        if ($overtimeRate > 0) {
            PayrollItem::createOvertimeItem(
                $payroll->id,
                $attendanceData['overtime_hours'],
                $overtimeRate,
                'Overtime Pay'
            );
        }
    }

    /**
     * Calculate overtime rate based on employee salary structure.
     */
    protected function calculateOvertimeRate(Employee $employee): float
    {
        $baseRate = 0;

        switch ($employee->salary_type) {
            case 'hourly':
                $baseRate = $employee->hourly_rate ?? 0;
                break;

            case 'monthly':
                // Convert monthly salary to hourly rate
                $workingDaysPerMonth = Config::get('payroll.calculations.working_days_per_month', 22);
                $standardHoursPerDay = Config::get('payroll.calculations.standard_hours_per_day', 8);
                $monthlyHours = $workingDaysPerMonth * $standardHoursPerDay;
                $baseRate = ($employee->salary_amount ?? 0) / $monthlyHours;
                break;

            case 'fixed':
                // For fixed salary, overtime might not apply or use a default rate
                $baseRate = $employee->hourly_rate ?? 0;
                break;
        }

        // Overtime rate multiplier from config
        $overtimeMultiplier = Config::get('payroll.calculations.overtime_multiplier', 1.5);
        return $baseRate * $overtimeMultiplier;
    }

    /**
     * Calculate leave adjustments (paid leave and unpaid leave deductions).
     */
    protected function calculateLeaveAdjustments(Payroll $payroll, array $leaveData, array $options): void
    {
        $employee = $payroll->employee;

        // Add paid leave if applicable
        if ($leaveData['paid_days'] > 0) {
            $dailyRate = $this->calculateDailyRate($employee);
            $paidLeaveAmount = $leaveData['paid_days'] * $dailyRate;

            PayrollItem::create([
                'payroll_id' => $payroll->id,
                'type' => PayrollItem::TYPE_EARNING,
                'category' => PayrollItem::CATEGORY_VACATION_PAY,
                'description' => 'Paid Leave (' . $leaveData['paid_days'] . ' days)',
                'amount' => $paidLeaveAmount,
                'quantity' => $leaveData['paid_days'],
                'rate' => $dailyRate,
                'calculation_method' => PayrollItem::CALCULATION_DAILY,
                'is_taxable' => true,
                'is_statutory' => false
            ]);
        }

        // Deduct unpaid leave if applicable
        if ($leaveData['unpaid_days'] > 0) {
            $dailyRate = $this->calculateDailyRate($employee);
            PayrollItem::createUnpaidLeaveItem(
                $payroll->id,
                $leaveData['unpaid_days'],
                $dailyRate,
                'Unpaid Leave Deduction (' . $leaveData['unpaid_days'] . ' days)'
            );
        }
    }

    /**
     * Calculate daily rate for an employee.
     */
    protected function calculateDailyRate(Employee $employee): float
    {
        $workingDaysPerMonth = $this->getPayrollConfig('calculations.working_days_per_month', 22);
        $standardHoursPerDay = $this->getPayrollConfig('calculations.standard_hours_per_day', 8);
        
        switch ($employee->salary_type) {
            case 'monthly':
                return ($employee->salary_amount ?? 0) / $workingDaysPerMonth;

            case 'hourly':
                return ($employee->hourly_rate ?? 0) * $standardHoursPerDay;

            case 'fixed':
                // For fixed salary, use monthly equivalent
                return ($employee->salary_amount ?? 0) / $workingDaysPerMonth;

            default:
                return 0;
        }
    }

    /**
     * Calculate bonuses for the payroll period.
     */
    protected function calculateBonuses(Payroll $payroll, array $options): void
    {
        // Add any specific bonuses passed in options
        if (isset($options['bonuses']) && is_array($options['bonuses'])) {
            foreach ($options['bonuses'] as $bonus) {
                PayrollItem::createBonusItem(
                    $payroll->id,
                    $bonus['amount'],
                    $bonus['description'] ?? 'Bonus'
                );
            }
        }

        // Add performance bonuses, attendance bonuses, etc. based on business rules
        $this->calculatePerformanceBonuses($payroll, $options);
        $this->calculateAttendanceBonuses($payroll, $options);
    }

    /**
     * Calculate performance bonuses.
     */
    protected function calculatePerformanceBonuses(Payroll $payroll, array $options): void
    {
        // Implement performance bonus logic based on your business rules
        // This is a placeholder for future implementation
    }

    /**
     * Calculate attendance bonuses.
     */
    protected function calculateAttendanceBonuses(Payroll $payroll, array $options): void
    {
        // Perfect attendance bonus
        $attendances = $payroll->attendanceRecords()->get();
        $workingDays = $this->getWorkingDays($payroll->payroll_period_start, $payroll->payroll_period_end);
        
        $perfectAttendanceBonusConfig = Config::get('payroll.bonuses.perfect_attendance');
        
        if ($perfectAttendanceBonusConfig['enabled'] && 
            $attendances->count() >= $workingDays && 
            $attendances->where('status', 'present')->count() >= $workingDays) {
            
            PayrollItem::createBonusItem(
                $payroll->id,
                $perfectAttendanceBonusConfig['amount'],
                'Perfect Attendance Bonus'
            );
        }
    }

    /**
     * Calculate deductions for the payroll period.
     */
    protected function calculateDeductions(Payroll $payroll, array $options): void
    {
        // Calculate tax deductions
        $this->calculateTaxDeductions($payroll, $options);

        // Add any specific deductions passed in options
        if (isset($options['deductions']) && is_array($options['deductions'])) {
            foreach ($options['deductions'] as $deduction) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type' => PayrollItem::TYPE_DEDUCTION,
                    'category' => $deduction['category'] ?? PayrollItem::CATEGORY_OTHER,
                    'description' => $deduction['description'] ?? 'Deduction',
                    'amount' => $deduction['amount'],
                    'calculation_method' => PayrollItem::CALCULATION_FIXED,
                    'is_taxable' => false,
                    'is_statutory' => $deduction['is_statutory'] ?? false
                ]);
            }
        }
    }

    /**
     * Calculate tax deductions.
     */
    protected function calculateTaxDeductions(Payroll $payroll, array $options): void
    {
        // Get taxable income
        $taxableEarnings = $payroll->payrollItems()
            ->where('type', PayrollItem::TYPE_EARNING)
            ->where('is_taxable', true)
            ->sum('amount');

        $taxableBonuses = $payroll->payrollItems()
            ->where('type', PayrollItem::TYPE_BONUS)
            ->where('is_taxable', true)
            ->sum('amount');

        $taxableIncome = $taxableEarnings + $taxableBonuses;

        if ($taxableIncome > 0) {
            // Simple tax calculation - implement your tax brackets here
            $taxRate = $this->calculateTaxRate($taxableIncome, $payroll->employee);
            
            if ($taxRate > 0) {
                PayrollItem::createTaxItem(
                    $payroll->id,
                    $taxableIncome,
                    $taxRate,
                    'Income Tax'
                );
            }

            // Add other statutory deductions (social security, etc.)
            $this->calculateStatutoryDeductions($payroll, $taxableIncome, $options);
        }
    }

    /**
     * Calculate tax rate based on income and employee type.
     */
    protected function calculateTaxRate(float $taxableIncome, Employee $employee): float
    {
        $taxBrackets = Config::get('payroll.tax.brackets', []);
        
        foreach ($taxBrackets as $bracket) {
            $min = $bracket['min'];
            $max = $bracket['max'];
            
            if ($taxableIncome >= $min && ($max === null || $taxableIncome <= $max)) {
                return $bracket['rate'];
            }
        }
        
        // Default to 0% if no bracket matches
        return 0;
    }

    /**
     * Calculate statutory deductions.
     */
    protected function calculateStatutoryDeductions(Payroll $payroll, float $taxableIncome, array $options): void
    {
        $statutoryDeductions = Config::get('payroll.statutory_deductions', []);
        
        // Social Security
        if ($statutoryDeductions['social_security']['enabled']) {
            $socialSecurityRate = $statutoryDeductions['social_security']['rate'];
            $socialSecurityCap = $statutoryDeductions['social_security']['cap'];
            $socialSecurityAmount = $taxableIncome * $socialSecurityRate / 100;
            
            if ($socialSecurityCap) {
                $socialSecurityAmount = min($socialSecurityAmount, $socialSecurityCap);
            }

            if ($socialSecurityAmount > 0) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type' => PayrollItem::TYPE_DEDUCTION,
                    'category' => PayrollItem::CATEGORY_INSURANCE,
                    'description' => "Social Security ({$socialSecurityRate}%)",
                    'amount' => $socialSecurityAmount,
                    'rate' => $socialSecurityRate,
                    'calculation_method' => PayrollItem::CALCULATION_PERCENTAGE,
                    'is_taxable' => false,
                    'is_statutory' => true
                ]);
            }
        }

        // Medicare
        if ($statutoryDeductions['medicare']['enabled']) {
            $medicareRate = $statutoryDeductions['medicare']['rate'];
            $medicareAmount = $taxableIncome * $medicareRate / 100;

            if ($medicareAmount > 0) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type' => PayrollItem::TYPE_DEDUCTION,
                    'category' => PayrollItem::CATEGORY_INSURANCE,
                    'description' => "Medicare ({$medicareRate}%)",
                    'amount' => $medicareAmount,
                    'rate' => $medicareRate,
                    'calculation_method' => PayrollItem::CALCULATION_PERCENTAGE,
                    'is_taxable' => false,
                    'is_statutory' => true
                ]);
            }
        }
    }

    /**
     * Calculate pay date based on period end date.
     */
    protected function calculatePayDate(Carbon $periodEnd): Carbon
    {
        $payDateDay = Config::get('payroll.calculations.pay_date_day', 15);
        
        // Pay on the configured day of the following month, or next business day
        $payDate = $periodEnd->copy()->addMonth()->day($payDateDay);
        
        // If it falls on weekend, move to next Monday
        if ($payDate->isWeekend()) {
            $payDate = $payDate->next(Carbon::MONDAY);
        }
        
        return $payDate;
    }

    /**
     * Get number of working days between two dates.
     */
    protected function getWorkingDays(Carbon $startDate, Carbon $endDate): int
    {
        return $this->getWorkingDaysBetween($startDate, $endDate);
    }

    /**
     * Get number of working days between two dates (excluding weekends).
     */
    protected function getWorkingDaysBetween(Carbon $startDate, Carbon $endDate): int
    {
        $workingDays = 0;
        $current = $startDate->copy();

        while ($current <= $endDate) {
            if (!$current->isWeekend()) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Calculate payroll for multiple employees.
     */
    public function calculatePayrollForEmployees(Collection $employees, Carbon $periodStart, Carbon $periodEnd, array $options = []): Collection
    {
        $payrolls = collect();

        foreach ($employees as $employee) {
            try {
                $payroll = $this->calculatePayroll($employee, $periodStart, $periodEnd, $options);
                $payrolls->push($payroll);
            } catch (\Exception $e) {
                // Log error and continue with next employee
                \Log::error("Failed to calculate payroll for employee {$employee->id}: " . $e->getMessage());
            }
        }

        return $payrolls;
    }

    /**
     * Calculate monthly payroll for all active employees.
     */
    public function calculateMonthlyPayrollForAllEmployees(int $year, int $month, array $options = []): Collection
    {
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth();

        $employees = Employee::where('is_active', true)->get();

        return $this->calculatePayrollForEmployees($employees, $periodStart, $periodEnd, $options);
    }

    /**
     * Get payroll configuration value with fallback.
     */
    protected function getPayrollConfig(string $key, $default = null)
    {
        return Config::get("payroll.{$key}", $default);
    }

    /**
     * Validate payroll calculation against business rules.
     */
    protected function validatePayrollCalculation(Payroll $payroll): array
    {
        $errors = [];
        $config = Config::get('payroll.validation', []);
        
        // Validate minimum wage
        if (isset($config['minimum_wage'])) {
            $hourlyEquivalent = $payroll->gross_salary / max($payroll->worked_hours, 1);
            if ($hourlyEquivalent < $config['minimum_wage']) {
                $errors[] = "Calculated hourly rate ({$hourlyEquivalent}) is below minimum wage ({$config['minimum_wage']})";
            }
        }
        
        // Validate maximum hours
        if (isset($config['maximum_hours_per_period']) && $payroll->worked_hours > $config['maximum_hours_per_period']) {
            $errors[] = "Worked hours ({$payroll->worked_hours}) exceed maximum allowed ({$config['maximum_hours_per_period']})";
        }
        
        // Validate overtime hours
        if (isset($config['maximum_overtime_hours']) && $payroll->overtime_hours > $config['maximum_overtime_hours']) {
            $errors[] = "Overtime hours ({$payroll->overtime_hours}) exceed maximum allowed ({$config['maximum_overtime_hours']})";
        }
        
        return $errors;
    }

    /**
     * Get payroll summary for a period.
     */
    public function getPayrollSummary(Carbon $periodStart, Carbon $periodEnd): array
    {
        $payrolls = Payroll::whereBetween('payroll_period_start', [$periodStart, $periodEnd])
            ->with('employee')
            ->get();

        return [
            'total_employees' => $payrolls->count(),
            'total_gross_salary' => $payrolls->sum('gross_salary'),
            'total_deductions' => $payrolls->sum('total_deductions'),
            'total_bonuses' => $payrolls->sum('total_bonuses'),
            'total_net_salary' => $payrolls->sum('net_salary'),
            'total_worked_hours' => $payrolls->sum('worked_hours'),
            'total_overtime_hours' => $payrolls->sum('overtime_hours'),
            'payrolls' => $payrolls,
        ];
    }
}