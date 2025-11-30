<?php

namespace App\Services;

use App\Contracts\Services\PayrollServiceInterface;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Attendance;
use App\Models\Leave;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Optimized Payroll Service with performance improvements
 */
class OptimizedPayrollService implements PayrollServiceInterface
{
    // Cache keys
    private const CACHE_WORKING_DAYS = 'payroll:working_days:%s:%s';
    private const CACHE_TAX_RATES = 'payroll:tax_rates';
    private const CACHE_DEDUCTION_RATES = 'payroll:deduction_rates';

    /**
     * Calculate payroll for an employee - Optimized version
     */
    public function calculatePayroll(
        Employee $employee,
        Carbon $startDate,
        Carbon $endDate,
        bool $force = false
    ): Payroll {
        return DB::transaction(function () use ($employee, $startDate, $endDate, $force) {
            // Use eager loading to prevent N+1 queries
            $employee->load(['location', 'schedules', 'leaveBalances']);

            // Check existing payroll with single query
            $existing = Payroll::where('employee_id', $employee->id)
                ->where('payroll_period_start', $startDate->toDateString())
                ->where('payroll_period_end', $endDate->toDateString())
                ->first();

            if ($existing && !$force) {
                return $existing->load('payrollItems');
            }

            // Batch load all required data
            $data = $this->batchLoadPayrollData($employee, $startDate, $endDate);

            // Create or update payroll
            $payroll = $this->createOrUpdatePayroll($employee, $startDate, $endDate, $data, $existing);

            // Batch create payroll items
            $this->batchCreatePayrollItems($payroll, $data);

            // Update totals
            $this->updatePayrollTotals($payroll);

            return $payroll->fresh('payrollItems');
        });
    }

    /**
     * Calculate payroll for multiple employees - Optimized bulk operation
     */
    public function calculateBulkPayroll(
        Collection $employees,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        // Pre-load all required data for all employees
        $employeeIds = $employees->pluck('id');
        
        // Batch load attendance data
        $attendanceData = $this->batchLoadAttendanceForEmployees($employeeIds, $startDate, $endDate);
        
        // Batch load leave data
        $leaveData = $this->batchLoadLeaveForEmployees($employeeIds, $startDate, $endDate);
        
        // Process in chunks to manage memory
        return $employees->chunk(50)->flatMap(function ($chunk) use ($startDate, $endDate, $attendanceData, $leaveData) {
            return DB::transaction(function () use ($chunk, $startDate, $endDate, $attendanceData, $leaveData) {
                $payrolls = collect();
                
                foreach ($chunk as $employee) {
                    $data = [
                        'attendance' => $attendanceData->get($employee->id, collect()),
                        'leaves' => $leaveData->get($employee->id, collect()),
                    ];
                    
                    $payroll = $this->processEmployeePayroll($employee, $startDate, $endDate, $data);
                    $payrolls->push($payroll);
                }
                
                return $payrolls;
            });
        });
    }

    /**
     * Get payroll summary - Optimized with caching
     */
    public function getPayrollSummary(Carbon $month): array
    {
        $cacheKey = "payroll:summary:{$month->format('Y-m')}";
        
        return Cache::remember($cacheKey, 3600, function () use ($month) {
            $startDate = $month->copy()->startOfMonth();
            $endDate = $month->copy()->endOfMonth();

            // Use database aggregation for performance
            $summary = DB::table('payrolls')
                ->whereBetween('payroll_period_start', [$startDate, $endDate])
                ->select(
                    DB::raw('COUNT(*) as total_employees'),
                    DB::raw('SUM(total_earnings) as total_earnings'),
                    DB::raw('SUM(total_deductions) as total_deductions'),
                    DB::raw('SUM(net_amount) as net_amount'),
                    DB::raw('AVG(net_amount) as average_salary'),
                    DB::raw('COUNT(CASE WHEN status = "processed" THEN 1 END) as processed_count'),
                    DB::raw('COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_count')
                )
                ->first();

            // Get department-wise breakdown
            $departmentBreakdown = DB::table('payrolls')
                ->join('employees', 'payrolls.employee_id', '=', 'employees.id')
                ->whereBetween('payroll_period_start', [$startDate, $endDate])
                ->groupBy('employees.department')
                ->select(
                    'employees.department',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(net_amount) as total_amount'),
                    DB::raw('AVG(net_amount) as average_amount')
                )
                ->get();

            return [
                'month' => $month->format('F Y'),
                'total_employees' => $summary->total_employees ?? 0,
                'total_earnings' => $summary->total_earnings ?? 0,
                'total_deductions' => $summary->total_deductions ?? 0,
                'net_amount' => $summary->net_amount ?? 0,
                'average_salary' => $summary->average_salary ?? 0,
                'processed_count' => $summary->processed_count ?? 0,
                'pending_count' => $summary->pending_count ?? 0,
                'department_breakdown' => $departmentBreakdown,
            ];
        });
    }

    /**
     * Calculate basic salary - Optimized
     */
    public function calculateBasicSalary(Employee $employee, array $attendanceData): float
    {
        return match ($employee->salary_type) {
            'monthly' => $this->calculateMonthlySalary($employee, $attendanceData),
            'hourly' => $this->calculateHourlySalary($employee, $attendanceData),
            'daily' => $this->calculateDailySalary($employee, $attendanceData),
            default => $employee->salary_amount ?? 0,
        };
    }

    /**
     * Calculate overtime pay - Optimized
     */
    public function calculateOvertime(Employee $employee, float $overtimeHours): float
    {
        if ($overtimeHours <= 0) {
            return 0;
        }

        $overtimeRate = $this->getOvertimeRate($employee);
        
        // Apply different rates for different overtime brackets
        $regularOvertime = min($overtimeHours, 2);
        $extraOvertime = max(0, $overtimeHours - 2);
        
        return ($regularOvertime * $overtimeRate * 1.5) + 
               ($extraOvertime * $overtimeRate * 2.0);
    }

    /**
     * Calculate deductions - Optimized with caching
     */
    public function calculateDeductions(Employee $employee, float $grossSalary): array
    {
        $deductions = [];
        
        // Get cached deduction rates
        $rates = Cache::remember(self::CACHE_DEDUCTION_RATES, 86400, function () {
            return config('payroll.deductions');
        });

        // Calculate each deduction
        foreach ($rates as $key => $rate) {
            if ($this->isDeductionApplicable($key, $employee, $grossSalary)) {
                $amount = $this->calculateDeductionAmount($key, $rate, $grossSalary);
                if ($amount > 0) {
                    $deductions[$key] = [
                        'name' => $rate['name'] ?? $key,
                        'amount' => $amount,
                        'percentage' => $rate['percentage'] ?? 0,
                    ];
                }
            }
        }

        return $deductions;
    }

    /**
     * Calculate allowances - Optimized
     */
    public function calculateAllowances(Employee $employee, array $attendanceData): array
    {
        $allowances = [];
        
        // Perfect attendance bonus
        if ($this->qualifiesForPerfectAttendance($attendanceData)) {
            $allowances['perfect_attendance'] = [
                'name' => 'Perfect Attendance Bonus',
                'amount' => config('payroll.bonuses.perfect_attendance', 0),
            ];
        }

        // Transport allowance
        if ($employee->metadata['transport_allowance'] ?? false) {
            $allowances['transport'] = [
                'name' => 'Transport Allowance',
                'amount' => $employee->metadata['transport_amount'] ?? 0,
            ];
        }

        // Meal allowance based on worked days
        if ($attendanceData['worked_days'] > 0) {
            $allowances['meal'] = [
                'name' => 'Meal Allowance',
                'amount' => $attendanceData['worked_days'] * config('payroll.allowances.meal_per_day', 0),
            ];
        }

        return $allowances;
    }

    /**
     * Process payroll payment
     */
    public function processPayment(Payroll $payroll): bool
    {
        if ($payroll->status !== Payroll::STATUS_APPROVED) {
            throw new \Exception('Payroll must be approved before processing payment');
        }

        return DB::transaction(function () use ($payroll) {
            $payroll->update([
                'status' => Payroll::STATUS_PROCESSED,
                'processed_at' => Carbon::now(),
                'processed_by' => auth()->id(),
            ]);

            // Create payment record
            // Integration with payment gateway would go here

            return true;
        });
    }

    /**
     * Generate pay slip
     */
    public function generatePaySlip(Payroll $payroll, string $format = 'pdf'): string
    {
        $payroll->load(['employee.user', 'employee.location', 'payrollItems']);
        
        $filename = "payslip_{$payroll->employee->employee_id}_{$payroll->payroll_period_start}.{$format}";
        
        // Generate PDF or other format
        // Implementation would depend on the chosen PDF library
        
        return storage_path("app/payslips/{$filename}");
    }

    /**
     * Export payroll data
     */
    public function exportPayroll(Carbon $month, string $format = 'xlsx'): string
    {
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        $payrolls = Payroll::with(['employee', 'payrollItems'])
            ->whereBetween('payroll_period_start', [$startDate, $endDate])
            ->get();

        $filename = "payroll_{$month->format('Y-m')}.{$format}";
        
        // Export logic would go here
        
        return storage_path("app/exports/{$filename}");
    }

    /**
     * Validate payroll calculation
     */
    public function validatePayroll(Payroll $payroll): array
    {
        $errors = [];
        
        // Validate minimum wage
        if ($payroll->net_amount < config('payroll.minimum_wage', 0)) {
            $errors[] = 'Net amount is below minimum wage';
        }

        // Validate deductions don't exceed limits
        $deductionPercentage = ($payroll->total_deductions / $payroll->total_earnings) * 100;
        if ($deductionPercentage > config('payroll.max_deduction_percentage', 50)) {
            $errors[] = 'Total deductions exceed maximum allowed percentage';
        }

        // Validate required items exist
        if (!$payroll->payrollItems()->where('type', 'earning')->exists()) {
            $errors[] = 'No earnings found in payroll';
        }

        return $errors;
    }

    /**
     * Private helper methods
     */
    private function batchLoadPayrollData(Employee $employee, Carbon $startDate, Carbon $endDate): array
    {
        // Load all attendance records in one query
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Load all leaves in one query
        $leaves = Leave::where('employee_id', $employee->id)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->where('status', Leave::STATUS_APPROVED)
            ->with('leaveType')
            ->get();

        // Calculate aggregated data
        $attendanceData = $this->aggregateAttendanceData($attendance, $employee);
        $leaveData = $this->aggregateLeaveData($leaves, $startDate, $endDate);

        return compact('attendance', 'leaves', 'attendanceData', 'leaveData');
    }

    private function batchLoadAttendanceForEmployees(Collection $employeeIds, Carbon $startDate, Carbon $endDate): Collection
    {
        return Attendance::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');
    }

    private function batchLoadLeaveForEmployees(Collection $employeeIds, Carbon $startDate, Carbon $endDate): Collection
    {
        return Leave::whereIn('employee_id', $employeeIds)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->where('status', Leave::STATUS_APPROVED)
            ->with('leaveType')
            ->get()
            ->groupBy('employee_id');
    }

    private function aggregateAttendanceData(Collection $attendance, Employee $employee): array
    {
        $totalHours = $attendance->sum('working_hours');
        $overtimeHours = $attendance->sum('overtime_hours');
        $workedDays = $attendance->whereNotNull('check_in')->count();
        $lateDays = $attendance->where('status', 'late')->count();
        
        $standardHours = $workedDays * 8; // Assuming 8 hours per day

        return [
            'worked_hours' => $totalHours,
            'overtime_hours' => $overtimeHours,
            'worked_days' => $workedDays,
            'late_days' => $lateDays,
            'standard_hours' => $standardHours,
            'attendance_rate' => $workedDays > 0 ? ($workedDays / 22) * 100 : 0, // Assuming 22 working days
        ];
    }

    private function aggregateLeaveData(Collection $leaves, Carbon $startDate, Carbon $endDate): array
    {
        $totalDays = 0;
        $paidDays = 0;
        $unpaidDays = 0;

        foreach ($leaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date)->max($startDate);
            $leaveEnd = Carbon::parse($leave->end_date)->min($endDate);
            
            $days = $this->getWorkingDaysBetween($leaveStart, $leaveEnd);
            $totalDays += $days;

            if ($leave->leaveType && $leave->leaveType->is_paid) {
                $paidDays += $days;
            } else {
                $unpaidDays += $days;
            }
        }

        return [
            'total_days' => $totalDays,
            'paid_days' => $paidDays,
            'unpaid_days' => $unpaidDays,
        ];
    }

    private function getWorkingDaysBetween(Carbon $start, Carbon $end): int
    {
        $cacheKey = sprintf(self::CACHE_WORKING_DAYS, $start->toDateString(), $end->toDateString());
        
        return Cache::remember($cacheKey, 3600, function () use ($start, $end) {
            $period = CarbonPeriod::create($start, $end);
            $workingDays = 0;

            foreach ($period as $date) {
                if (!$date->isWeekend()) {
                    $workingDays++;
                }
            }

            return $workingDays;
        });
    }

    private function batchCreatePayrollItems(Payroll $payroll, array $data): void
    {
        $items = [];
        $timestamp = now();

        // Basic salary
        $basicSalary = $this->calculateBasicSalary($payroll->employee, $data['attendanceData']);
        if ($basicSalary > 0) {
            $items[] = [
                'payroll_id' => $payroll->id,
                'type' => 'earning',
                'category' => 'basic_salary',
                'description' => 'Basic Salary',
                'amount' => $basicSalary,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        // Overtime
        $overtime = $this->calculateOvertime($payroll->employee, $data['attendanceData']['overtime_hours']);
        if ($overtime > 0) {
            $items[] = [
                'payroll_id' => $payroll->id,
                'type' => 'earning',
                'category' => 'overtime',
                'description' => 'Overtime Pay',
                'amount' => $overtime,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        // Allowances
        $allowances = $this->calculateAllowances($payroll->employee, $data['attendanceData']);
        foreach ($allowances as $key => $allowance) {
            $items[] = [
                'payroll_id' => $payroll->id,
                'type' => 'earning',
                'category' => 'allowance',
                'description' => $allowance['name'],
                'amount' => $allowance['amount'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        // Calculate gross before deductions
        $grossSalary = collect($items)->where('type', 'earning')->sum('amount');

        // Deductions
        $deductions = $this->calculateDeductions($payroll->employee, $grossSalary);
        foreach ($deductions as $key => $deduction) {
            $items[] = [
                'payroll_id' => $payroll->id,
                'type' => 'deduction',
                'category' => $key,
                'description' => $deduction['name'],
                'amount' => $deduction['amount'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        // Batch insert all items
        if (!empty($items)) {
            PayrollItem::insert($items);
        }
    }

    private function updatePayrollTotals(Payroll $payroll): void
    {
        $totals = DB::table('payroll_items')
            ->where('payroll_id', $payroll->id)
            ->groupBy('type')
            ->select(
                'type',
                DB::raw('SUM(amount) as total')
            )
            ->pluck('total', 'type');

        $totalEarnings = $totals['earning'] ?? 0;
        $totalDeductions = $totals['deduction'] ?? 0;

        $payroll->update([
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'net_amount' => $totalEarnings - $totalDeductions,
        ]);
    }

    private function calculateMonthlySalary(Employee $employee, array $attendanceData): float
    {
        $monthlySalary = $employee->salary_amount ?? 0;
        
        // Deduct for unpaid leaves
        if ($attendanceData['worked_days'] < 22) {
            $dailyRate = $monthlySalary / 22;
            $deduction = (22 - $attendanceData['worked_days']) * $dailyRate;
            return max(0, $monthlySalary - $deduction);
        }

        return $monthlySalary;
    }

    private function calculateHourlySalary(Employee $employee, array $attendanceData): float
    {
        $hourlyRate = $employee->hourly_rate ?? 0;
        $regularHours = min($attendanceData['worked_hours'], $attendanceData['standard_hours']);
        
        return $regularHours * $hourlyRate;
    }

    private function calculateDailySalary(Employee $employee, array $attendanceData): float
    {
        $dailyRate = $employee->daily_rate ?? 0;
        
        return $attendanceData['worked_days'] * $dailyRate;
    }

    private function getOvertimeRate(Employee $employee): float
    {
        return match ($employee->salary_type) {
            'monthly' => ($employee->salary_amount ?? 0) / 22 / 8,
            'hourly' => $employee->hourly_rate ?? 0,
            'daily' => ($employee->daily_rate ?? 0) / 8,
            default => 0,
        };
    }

    private function isDeductionApplicable(string $key, Employee $employee, float $grossSalary): bool
    {
        // Check minimum salary threshold
        $threshold = config("payroll.deductions.{$key}.minimum_salary", 0);
        if ($grossSalary < $threshold) {
            return false;
        }

        // Check employee eligibility
        if ($key === 'pension' && Carbon::parse($employee->hire_date)->diffInYears() < 1) {
            return false;
        }

        return true;
    }

    private function calculateDeductionAmount(string $key, array $rate, float $grossSalary): float
    {
        if (isset($rate['fixed'])) {
            return $rate['fixed'];
        }

        if (isset($rate['percentage'])) {
            return ($grossSalary * $rate['percentage']) / 100;
        }

        // Progressive calculation for tax
        if ($key === 'tax' && isset($rate['brackets'])) {
            return $this->calculateProgressiveTax($grossSalary, $rate['brackets']);
        }

        return 0;
    }

    private function calculateProgressiveTax(float $salary, array $brackets): float
    {
        $tax = 0;
        $previousLimit = 0;

        foreach ($brackets as $bracket) {
            $limit = $bracket['limit'] ?? PHP_FLOAT_MAX;
            $rate = $bracket['rate'] ?? 0;

            if ($salary <= $previousLimit) {
                break;
            }

            $taxableAmount = min($salary - $previousLimit, $limit - $previousLimit);
            $tax += ($taxableAmount * $rate) / 100;

            $previousLimit = $limit;
        }

        return $tax;
    }

    private function qualifiesForPerfectAttendance(array $attendanceData): bool
    {
        return $attendanceData['worked_days'] >= 22 && 
               $attendanceData['late_days'] === 0;
    }

    private function processEmployeePayroll(Employee $employee, Carbon $startDate, Carbon $endDate, array $preloadedData): Payroll
    {
        $attendanceData = $this->aggregateAttendanceData($preloadedData['attendance'], $employee);
        $leaveData = $this->aggregateLeaveData($preloadedData['leaves'], $startDate, $endDate);

        $data = [
            'attendanceData' => $attendanceData,
            'leaveData' => $leaveData,
        ];

        $payroll = $this->createOrUpdatePayroll($employee, $startDate, $endDate, $data, null);
        $this->batchCreatePayrollItems($payroll, $data);
        $this->updatePayrollTotals($payroll);

        return $payroll;
    }

    private function createOrUpdatePayroll(Employee $employee, Carbon $startDate, Carbon $endDate, array $data, ?Payroll $existing): Payroll
    {
        $payrollData = [
            'employee_id' => $employee->id,
            'payroll_period_start' => $startDate->toDateString(),
            'payroll_period_end' => $endDate->toDateString(),
            'pay_date' => $endDate->addDays(5)->toDateString(),
            'worked_hours' => $data['attendanceData']['worked_hours'],
            'overtime_hours' => $data['attendanceData']['overtime_hours'],
            'leave_days_taken' => $data['leaveData']['total_days'],
            'leave_days_paid' => $data['leaveData']['paid_days'],
            'leave_days_unpaid' => $data['leaveData']['unpaid_days'],
            'status' => Payroll::STATUS_DRAFT,
        ];

        if ($existing) {
            $existing->update($payrollData);
            $existing->payrollItems()->delete();
            return $existing;
        }

        return Payroll::create($payrollData);
    }
}