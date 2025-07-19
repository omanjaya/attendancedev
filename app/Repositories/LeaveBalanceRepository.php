<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Leave Balance Repository
 *
 * Handles all leave balance-related database operations
 */
class LeaveBalanceRepository extends BaseRepository
{
    public function __construct(LeaveBalance $leaveBalance)
    {
        parent::__construct($leaveBalance);
    }

    /**
     * Get employee leave balances
     */
    public function getEmployeeLeaveBalances(string $employeeId, ?string $year = null): Collection
    {
        $year = $year ?? now()->year;
        $cacheKey = $this->getCacheKey('employee_leave_balances', [$employeeId, $year]);

        return cache()->remember($cacheKey, 1800, function () use ($employeeId, $year) {
            return $this->model
                ->where('employee_id', $employeeId)
                ->where('year', $year)
                ->with(['leaveType', 'employee'])
                ->orderBy('leave_type_id')
                ->get();
        });
    }

    /**
     * Get leave balance for specific type
     */
    public function getLeaveBalance(string $employeeId, string $leaveTypeId, ?string $year = null): ?LeaveBalance
    {
        $year = $year ?? now()->year;
        $cacheKey = $this->getCacheKey('leave_balance', [$employeeId, $leaveTypeId, $year]);

        return cache()->remember($cacheKey, 1800, function () use ($employeeId, $leaveTypeId, $year) {
            return $this->model
                ->where('employee_id', $employeeId)
                ->where('leave_type_id', $leaveTypeId)
                ->where('year', $year)
                ->with(['leaveType', 'employee'])
                ->first();
        });
    }

    /**
     * Get all leave balances for a year
     */
    public function getLeaveBalancesByYear(string $year): Collection
    {
        $cacheKey = $this->getCacheKey('leave_balances_by_year', [$year]);

        return cache()->remember($cacheKey, 3600, function () use ($year) {
            return $this->model
                ->where('year', $year)
                ->with(['employee.user', 'employee.location', 'leaveType'])
                ->orderBy('employee_id')
                ->orderBy('leave_type_id')
                ->get();
        });
    }

    /**
     * Get leave balance statistics
     */
    public function getLeaveBalanceStatistics(?string $year = null): array
    {
        $year = $year ?? now()->year;
        $cacheKey = $this->getCacheKey('leave_balance_statistics', [$year]);

        return cache()->remember($cacheKey, 3600, function () use ($year) {
            $balances = $this->model
                ->where('year', $year)
                ->with(['leaveType', 'employee'])
                ->get();

            $totalEmployees = $balances->groupBy('employee_id')->count();
            $totalBalances = $balances->count();

            $totalAllocated = $balances->sum('allocated_days');
            $totalUsed = $balances->sum('used_days');
            $totalRemaining = $balances->sum('remaining_days');
            $totalCarriedForward = $balances->sum('carried_forward_days');

            // By leave type
            $byLeaveType = $balances->groupBy('leave_type_id')
                ->map(function ($balances, $typeId) {
                    $leaveType = $balances->first()->leaveType;

                    return [
                        'leave_type' => $leaveType->name,
                        'color' => $leaveType->color,
                        'total_allocated' => $balances->sum('allocated_days'),
                        'total_used' => $balances->sum('used_days'),
                        'total_remaining' => $balances->sum('remaining_days'),
                        'total_carried_forward' => $balances->sum('carried_forward_days'),
                        'employee_count' => $balances->count(),
                        'utilization_rate' => $balances->sum('allocated_days') > 0 ?
                            round(($balances->sum('used_days') / $balances->sum('allocated_days')) * 100, 1) : 0,
                    ];
                })
                ->values()
                ->toArray();

            // By employee type
            $byEmployeeType = $balances->groupBy('employee.employee_type')
                ->map(function ($balances, $type) {
                    return [
                        'employee_type' => $type,
                        'total_allocated' => $balances->sum('allocated_days'),
                        'total_used' => $balances->sum('used_days'),
                        'total_remaining' => $balances->sum('remaining_days'),
                        'employee_count' => $balances->groupBy('employee_id')->count(),
                        'utilization_rate' => $balances->sum('allocated_days') > 0 ?
                            round(($balances->sum('used_days') / $balances->sum('allocated_days')) * 100, 1) : 0,
                    ];
                })
                ->values()
                ->toArray();

            return [
                'year' => $year,
                'total_employees' => $totalEmployees,
                'total_balances' => $totalBalances,
                'total_allocated' => $totalAllocated,
                'total_used' => $totalUsed,
                'total_remaining' => $totalRemaining,
                'total_carried_forward' => $totalCarriedForward,
                'overall_utilization_rate' => $totalAllocated > 0 ? round(($totalUsed / $totalAllocated) * 100, 1) : 0,
                'by_leave_type' => $byLeaveType,
                'by_employee_type' => $byEmployeeType,
            ];
        });
    }

    /**
     * Get employees with low leave balances
     */
    public function getEmployeesWithLowBalances(float $threshold = 5.0): Collection
    {
        $cacheKey = $this->getCacheKey('employees_low_balances', [$threshold]);

        return cache()->remember($cacheKey, 1800, function () use ($threshold) {
            return $this->model
                ->where('year', now()->year)
                ->where('remaining_days', '<=', $threshold)
                ->where('remaining_days', '>', 0)
                ->with(['employee.user', 'employee.location', 'leaveType'])
                ->orderBy('remaining_days')
                ->get();
        });
    }

    /**
     * Get employees with expired balances
     */
    public function getEmployeesWithExpiredBalances(): Collection
    {
        $cacheKey = $this->getCacheKey('employees_expired_balances');

        return cache()->remember($cacheKey, 3600, function () {
            return $this->model
                ->where('year', now()->year)
                ->where('expiry_date', '<', now())
                ->where('remaining_days', '>', 0)
                ->with(['employee.user', 'employee.location', 'leaveType'])
                ->orderBy('expiry_date')
                ->get();
        });
    }

    /**
     * Update leave balance after leave approval
     */
    public function updateBalanceAfterLeave(string $employeeId, string $leaveTypeId, float $days, string $action = 'deduct'): bool
    {
        return DB::transaction(function () use ($employeeId, $leaveTypeId, $days, $action) {
            $balance = $this->getLeaveBalance($employeeId, $leaveTypeId, now()->year);

            if (! $balance) {
                throw new \Exception('Leave balance not found for employee');
            }

            if ($action === 'deduct') {
                if ($balance->remaining_days < $days) {
                    throw new \Exception('Insufficient leave balance');
                }

                $balance->update([
                    'used_days' => $balance->used_days + $days,
                    'remaining_days' => $balance->remaining_days - $days,
                ]);
            } elseif ($action === 'restore') {
                $balance->update([
                    'used_days' => max(0, $balance->used_days - $days),
                    'remaining_days' => min($balance->allocated_days, $balance->remaining_days + $days),
                ]);
            }

            $this->clearCache();

            return true;
        });
    }

    /**
     * Initialize leave balances for new year
     */
    public function initializeYearlyBalances(string $year): int
    {
        return DB::transaction(function () use ($year) {
            $employees = Employee::where('is_active', true)->get();
            $leaveTypes = LeaveType::where('is_active', true)->get();

            $created = 0;

            foreach ($employees as $employee) {
                foreach ($leaveTypes as $leaveType) {
                    // Check if balance already exists
                    $exists = $this->model
                        ->where('employee_id', $employee->id)
                        ->where('leave_type_id', $leaveType->id)
                        ->where('year', $year)
                        ->exists();

                    if (! $exists) {
                        // Calculate carry forward from previous year
                        $previousBalance = $this->model
                            ->where('employee_id', $employee->id)
                            ->where('leave_type_id', $leaveType->id)
                            ->where('year', $year - 1)
                            ->first();

                        $carriedForward = 0;
                        if ($previousBalance && $leaveType->can_carry_forward) {
                            $carriedForward = min(
                                $previousBalance->remaining_days,
                                $leaveType->max_carry_forward ?? $previousBalance->remaining_days
                            );
                        }

                        $allocatedDays = $this->calculateAllocationForEmployee($employee, $leaveType);

                        $this->model->create([
                            'employee_id' => $employee->id,
                            'leave_type_id' => $leaveType->id,
                            'year' => $year,
                            'allocated_days' => $allocatedDays,
                            'used_days' => 0,
                            'remaining_days' => $allocatedDays + $carriedForward,
                            'carried_forward_days' => $carriedForward,
                            'expiry_date' => $this->calculateExpiryDate($leaveType, $year),
                        ]);

                        $created++;
                    }
                }
            }

            $this->clearCache();

            return $created;
        });
    }

    /**
     * Calculate allocation for employee based on leave type and employee details
     */
    private function calculateAllocationForEmployee(Employee $employee, LeaveType $leaveType): float
    {
        // Base allocation
        $allocation = $leaveType->default_days;

        // Adjust based on employee type
        if ($employee->employee_type === 'permanent') {
            $allocation = $leaveType->default_days;
        } elseif ($employee->employee_type === 'contract') {
            $allocation = $leaveType->default_days * 0.8; // 80% for contract
        } elseif ($employee->employee_type === 'part_time') {
            $allocation = $leaveType->default_days * 0.5; // 50% for part-time
        }

        // Pro-rate based on join date if joined this year
        if ($employee->hire_date && $employee->hire_date->year == now()->year) {
            $monthsWorked = 12 - $employee->hire_date->month + 1;
            $allocation = ($allocation / 12) * $monthsWorked;
        }

        return round($allocation, 1);
    }

    /**
     * Calculate expiry date for leave type
     */
    private function calculateExpiryDate(LeaveType $leaveType, string $year): ?Carbon
    {
        if (! $leaveType->has_expiry) {
            return null;
        }

        if ($leaveType->expiry_type === 'end_of_year') {
            return Carbon::createFromDate($year, 12, 31);
        } elseif ($leaveType->expiry_type === 'anniversary') {
            return Carbon::createFromDate($year + 1, 12, 31);
        }

        return null;
    }

    /**
     * Get leave balance summary for employee
     */
    public function getEmployeeBalanceSummary(string $employeeId, ?string $year = null): array
    {
        $year = $year ?? now()->year;
        $cacheKey = $this->getCacheKey('employee_balance_summary', [$employeeId, $year]);

        return cache()->remember($cacheKey, 1800, function () use ($employeeId, $year) {
            $balances = $this->getEmployeeLeaveBalances($employeeId, $year);

            $totalAllocated = $balances->sum('allocated_days');
            $totalUsed = $balances->sum('used_days');
            $totalRemaining = $balances->sum('remaining_days');
            $totalCarriedForward = $balances->sum('carried_forward_days');

            $byType = $balances->map(function ($balance) {
                return [
                    'leave_type' => $balance->leaveType->name,
                    'color' => $balance->leaveType->color,
                    'allocated' => $balance->allocated_days,
                    'used' => $balance->used_days,
                    'remaining' => $balance->remaining_days,
                    'carried_forward' => $balance->carried_forward_days,
                    'utilization_rate' => $balance->allocated_days > 0 ?
                        round(($balance->used_days / $balance->allocated_days) * 100, 1) : 0,
                    'expires_at' => $balance->expiry_date?->format('Y-m-d'),
                ];
            })->toArray();

            return [
                'year' => $year,
                'total_allocated' => $totalAllocated,
                'total_used' => $totalUsed,
                'total_remaining' => $totalRemaining,
                'total_carried_forward' => $totalCarriedForward,
                'utilization_rate' => $totalAllocated > 0 ? round(($totalUsed / $totalAllocated) * 100, 1) : 0,
                'by_type' => $byType,
            ];
        });
    }

    /**
     * Get balance adjustment history
     */
    public function getBalanceAdjustmentHistory(string $employeeId, ?string $leaveTypeId = null): Collection
    {
        $cacheKey = $this->getCacheKey('balance_adjustment_history', [$employeeId, $leaveTypeId]);

        return cache()->remember($cacheKey, 1800, function () {
            // This would track adjustments - for now return empty
            // In a full implementation, this would query a leave_balance_adjustments table
            return collect([]);
        });
    }

    /**
     * Adjust leave balance
     */
    public function adjustBalance(string $employeeId, string $leaveTypeId, float $adjustment, string $reason, ?string $year = null): bool
    {
        $year = $year ?? now()->year;

        return DB::transaction(function () use ($employeeId, $leaveTypeId, $adjustment, $reason, $year) {
            $balance = $this->getLeaveBalance($employeeId, $leaveTypeId, $year);

            if (! $balance) {
                throw new \Exception('Leave balance not found');
            }

            $newAllocated = $balance->allocated_days + $adjustment;
            $newRemaining = $balance->remaining_days + $adjustment;

            if ($newAllocated < 0 || $newRemaining < 0) {
                throw new \Exception('Adjustment would result in negative balance');
            }

            $balance->update([
                'allocated_days' => $newAllocated,
                'remaining_days' => $newRemaining,
            ]);

            // Log the adjustment (would be stored in adjustments table)
            $this->logBalanceAdjustment($balance, $adjustment, $reason);

            $this->clearCache();

            return true;
        });
    }

    /**
     * Log balance adjustment
     */
    private function logBalanceAdjustment(LeaveBalance $balance, float $adjustment, string $reason): void
    {
        // In a full implementation, this would create an adjustment record
        // For now, we'll add it to the balance metadata
        $metadata = $balance->metadata ?? [];
        $metadata['adjustments'] = $metadata['adjustments'] ?? [];
        $metadata['adjustments'][] = [
            'adjustment' => $adjustment,
            'reason' => $reason,
            'adjusted_by' => auth()->id(),
            'adjusted_at' => now()->toISOString(),
        ];

        $balance->update(['metadata' => $metadata]);
    }

    /**
     * Get employees with balances expiring soon
     */
    public function getEmployeesWithExpiringBalances(int $days = 30): Collection
    {
        $cacheKey = $this->getCacheKey('employees_expiring_balances', [$days]);

        return cache()->remember($cacheKey, 3600, function () use ($days) {
            $expiryDate = now()->addDays($days);

            return $this->model
                ->where('year', now()->year)
                ->where('expiry_date', '<=', $expiryDate)
                ->where('expiry_date', '>', now())
                ->where('remaining_days', '>', 0)
                ->with(['employee.user', 'employee.location', 'leaveType'])
                ->orderBy('expiry_date')
                ->get();
        });
    }

    /**
     * Transfer leave balance between employees
     */
    public function transferBalance(string $fromEmployeeId, string $toEmployeeId, string $leaveTypeId, float $days, string $reason): bool
    {
        return DB::transaction(function () use ($fromEmployeeId, $toEmployeeId, $leaveTypeId, $days, $reason) {
            $fromBalance = $this->getLeaveBalance($fromEmployeeId, $leaveTypeId);
            $toBalance = $this->getLeaveBalance($toEmployeeId, $leaveTypeId);

            if (! $fromBalance || ! $toBalance) {
                throw new \Exception('Leave balance not found for one or both employees');
            }

            if ($fromBalance->remaining_days < $days) {
                throw new \Exception('Insufficient balance to transfer');
            }

            // Deduct from source
            $fromBalance->update([
                'allocated_days' => $fromBalance->allocated_days - $days,
                'remaining_days' => $fromBalance->remaining_days - $days,
            ]);

            // Add to destination
            $toBalance->update([
                'allocated_days' => $toBalance->allocated_days + $days,
                'remaining_days' => $toBalance->remaining_days + $days,
            ]);

            // Log the transfer
            $this->logBalanceTransfer($fromBalance, $toBalance, $days, $reason);

            $this->clearCache();

            return true;
        });
    }

    /**
     * Log balance transfer
     */
    private function logBalanceTransfer(LeaveBalance $from, LeaveBalance $to, float $days, string $reason): void
    {
        // Log in both balances
        $transferData = [
            'type' => 'transfer',
            'days' => $days,
            'reason' => $reason,
            'transferred_by' => auth()->id(),
            'transferred_at' => now()->toISOString(),
        ];

        // From balance
        $fromMetadata = $from->metadata ?? [];
        $fromMetadata['transfers'] = $fromMetadata['transfers'] ?? [];
        $fromMetadata['transfers'][] = array_merge($transferData, [
            'direction' => 'out',
            'counterpart_employee_id' => $to->employee_id,
        ]);
        $from->update(['metadata' => $fromMetadata]);

        // To balance
        $toMetadata = $to->metadata ?? [];
        $toMetadata['transfers'] = $toMetadata['transfers'] ?? [];
        $toMetadata['transfers'][] = array_merge($transferData, [
            'direction' => 'in',
            'counterpart_employee_id' => $from->employee_id,
        ]);
        $to->update(['metadata' => $toMetadata]);
    }
}
