<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Payroll Repository
 *
 * Handles all payroll-related database operations
 */
class PayrollRepository extends BaseRepository
{
    public function __construct(Payroll $payroll)
    {
        parent::__construct($payroll);
    }

    /**
     * Get payroll records for an employee
     */
    public function getEmployeePayrolls(string $employeeId, ?string $year = null): Collection
    {
        $year = $year ?? now()->year;
        $cacheKey = $this->getCacheKey('employee_payrolls', [$employeeId, $year]);

        return cache()->remember($cacheKey, 1800, function () use ($employeeId, $year) {
            return $this->model
                ->where('employee_id', $employeeId)
                ->whereYear('payroll_period_start', $year)
                ->with(['employee.user', 'payrollItems', 'approver', 'processor'])
                ->orderBy('payroll_period_start', 'desc')
                ->get();
        });
    }

    /**
     * Get payroll records by status
     */
    public function getPayrollsByStatus(string $status, ?string $locationId = null): Collection
    {
        $cacheKey = $this->getCacheKey('payrolls_by_status', [$status, $locationId]);

        return cache()->remember($cacheKey, 1800, function () use ($status, $locationId) {
            $query = $this->model
                ->where('status', $status)
                ->with(['employee.user', 'employee.location', 'approver', 'processor']);

            if ($locationId) {
                $query->whereHas('employee', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            }

            return $query->orderBy('payroll_period_start', 'desc')->get();
        });
    }

    /**
     * Get payroll records for a specific period
     */
    public function getPayrollsForPeriod(string $startDate, string $endDate, ?string $locationId = null): Collection
    {
        $cacheKey = $this->getCacheKey('payrolls_for_period', [$startDate, $endDate, $locationId]);

        return cache()->remember($cacheKey, 1800, function () use ($startDate, $endDate, $locationId) {
            $query = $this->model
                ->whereBetween('payroll_period_start', [$startDate, $endDate])
                ->with(['employee.user', 'employee.location', 'payrollItems', 'approver', 'processor']);

            if ($locationId) {
                $query->whereHas('employee', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            }

            return $query->orderBy('payroll_period_start', 'desc')->get();
        });
    }

    /**
     * Get pending payroll approvals
     */
    public function getPendingApprovals(?string $locationId = null): Collection
    {
        $cacheKey = $this->getCacheKey('pending_approvals', [$locationId]);

        return cache()->remember($cacheKey, 600, function () use ($locationId) {
            $query = $this->model
                ->where('status', 'pending_approval')
                ->with(['employee.user', 'employee.location', 'payrollItems']);

            if ($locationId) {
                $query->whereHas('employee', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            }

            return $query->orderBy('created_at', 'asc')->get();
        });
    }

    /**
     * Get payroll statistics
     */
    public function getPayrollStatistics(?string $year = null): array
    {
        $year = $year ?? now()->year;
        $cacheKey = $this->getCacheKey('payroll_statistics', [$year]);

        return cache()->remember($cacheKey, 3600, function () use ($year) {
            $startDate = Carbon::createFromDate($year, 1, 1);
            $endDate = Carbon::createFromDate($year, 12, 31);

            $payrolls = $this->model
                ->whereBetween('payroll_period_start', [$startDate, $endDate])
                ->with(['employee', 'payrollItems'])
                ->get();

            $totalPayrolls = $payrolls->count();
            $processedPayrolls = $payrolls->where('status', 'processed')->count();
            $paidPayrolls = $payrolls->where('status', 'paid')->count();
            $pendingPayrolls = $payrolls->where('status', 'pending_approval')->count();

            $totalGrossAmount = $payrolls->sum('gross_salary');
            $totalNetAmount = $payrolls->sum('net_salary');
            $totalDeductions = $payrolls->sum('total_deductions');
            $totalBonuses = $payrolls->sum('total_bonuses');

            $employeeTypeStats = $payrolls->groupBy('employee.employee_type')
                ->map(function ($payrolls, $type) {
                    return [
                        'type' => $type,
                        'count' => $payrolls->count(),
                        'total_gross' => $payrolls->sum('gross_salary'),
                        'total_net' => $payrolls->sum('net_salary'),
                        'average_gross' => $payrolls->avg('gross_salary'),
                        'average_net' => $payrolls->avg('net_salary'),
                    ];
                })
                ->values()
                ->toArray();

            $monthlyStats = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthlyPayrolls = $payrolls->filter(function ($payroll) use ($month) {
                    return $payroll->payroll_period_start->month == $month;
                });

                $monthlyStats[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate(null, $month, 1)->format('F'),
                    'count' => $monthlyPayrolls->count(),
                    'total_gross' => $monthlyPayrolls->sum('gross_salary'),
                    'total_net' => $monthlyPayrolls->sum('net_salary'),
                    'average_gross' => $monthlyPayrolls->avg('gross_salary'),
                    'average_net' => $monthlyPayrolls->avg('net_salary'),
                ];
            }

            return [
                'total_payrolls' => $totalPayrolls,
                'processed_payrolls' => $processedPayrolls,
                'paid_payrolls' => $paidPayrolls,
                'pending_payrolls' => $pendingPayrolls,
                'processing_rate' => $totalPayrolls > 0 ? round(($processedPayrolls / $totalPayrolls) * 100, 1) : 0,
                'payment_rate' => $totalPayrolls > 0 ? round(($paidPayrolls / $totalPayrolls) * 100, 1) : 0,
                'total_gross_amount' => $totalGrossAmount,
                'total_net_amount' => $totalNetAmount,
                'total_deductions' => $totalDeductions,
                'total_bonuses' => $totalBonuses,
                'average_gross_salary' => $totalPayrolls > 0 ? round($totalGrossAmount / $totalPayrolls, 2) : 0,
                'average_net_salary' => $totalPayrolls > 0 ? round($totalNetAmount / $totalPayrolls, 2) : 0,
                'employee_type_stats' => $employeeTypeStats,
                'monthly_stats' => $monthlyStats,
            ];
        });
    }

    /**
     * Get payroll summary for period
     */
    public function getPayrollSummary(string $startDate, string $endDate): array
    {
        $cacheKey = $this->getCacheKey('payroll_summary', [$startDate, $endDate]);

        return cache()->remember($cacheKey, 1800, function () use ($startDate, $endDate) {
            $payrolls = $this->model
                ->whereBetween('payroll_period_start', [$startDate, $endDate])
                ->with(['employee', 'payrollItems'])
                ->get();

            $summary = [
                'total_employees' => $payrolls->count(),
                'total_gross_salary' => $payrolls->sum('gross_salary'),
                'total_net_salary' => $payrolls->sum('net_salary'),
                'total_deductions' => $payrolls->sum('total_deductions'),
                'total_bonuses' => $payrolls->sum('total_bonuses'),
                'average_gross_salary' => $payrolls->avg('gross_salary'),
                'average_net_salary' => $payrolls->avg('net_salary'),
            ];

            // Group by employee type
            $byEmployeeType = $payrolls->groupBy('employee.employee_type')
                ->map(function ($payrolls, $type) {
                    return [
                        'employee_type' => $type,
                        'count' => $payrolls->count(),
                        'total_gross' => $payrolls->sum('gross_salary'),
                        'total_net' => $payrolls->sum('net_salary'),
                        'average_gross' => $payrolls->avg('gross_salary'),
                        'average_net' => $payrolls->avg('net_salary'),
                    ];
                })
                ->values()
                ->toArray();

            $summary['by_employee_type'] = $byEmployeeType;

            return $summary;
        });
    }

    /**
     * Get payroll items for a payroll
     */
    public function getPayrollItems(string $payrollId): Collection
    {
        $cacheKey = $this->getCacheKey('payroll_items', [$payrollId]);

        return cache()->remember($cacheKey, 1800, function () use ($payrollId) {
            return PayrollItem::where('payroll_id', $payrollId)
                ->orderBy('item_type')
                ->orderBy('item_name')
                ->get();
        });
    }

    /**
     * Create payroll with items
     */
    public function createPayrollWithItems(array $payrollData, array $items): Payroll
    {
        return DB::transaction(function () use ($payrollData, $items) {
            $payroll = $this->create($payrollData);

            foreach ($items as $item) {
                $item['payroll_id'] = $payroll->id;
                PayrollItem::create($item);
            }

            // Recalculate totals
            $payroll->recalculateTotals();

            return $payroll->load('payrollItems');
        });
    }

    /**
     * Update payroll status
     */
    public function updatePayrollStatus(string $payrollId, string $status, ?string $userId = null): bool
    {
        return DB::transaction(function () use ($payrollId, $status, $userId) {
            $payroll = $this->findOrFail($payrollId);

            $updateData = ['status' => $status];

            switch ($status) {
                case 'approved':
                    $updateData['approved_by'] = $userId;
                    $updateData['approved_at'] = now();
                    break;
                case 'processed':
                    $updateData['processed_by'] = $userId;
                    $updateData['processed_at'] = now();
                    break;
                case 'paid':
                    $updateData['paid_at'] = now();
                    break;
            }

            $result = $payroll->update($updateData);

            $this->clearCache();

            return $result;
        });
    }

    /**
     * Approve payroll
     */
    public function approvePayroll(string $payrollId, string $approverId, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($payrollId, $approverId, $notes) {
            $payroll = $this->findOrFail($payrollId);

            $metadata = $payroll->metadata ?? [];
            if ($notes) {
                $metadata['approval_notes'] = $notes;
            }

            $result = $payroll->update([
                'status' => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
                'metadata' => $metadata,
            ]);

            $this->clearCache();

            return $result;
        });
    }

    /**
     * Process payroll
     */
    public function processPayroll(string $payrollId, string $processorId): bool
    {
        return DB::transaction(function () use ($payrollId, $processorId) {
            $payroll = $this->findOrFail($payrollId);

            $result = $payroll->update([
                'status' => 'processed',
                'processed_by' => $processorId,
                'processed_at' => now(),
            ]);

            $this->clearCache();

            return $result;
        });
    }

    /**
     * Mark payroll as paid
     */
    public function markAsPaid(string $payrollId, ?string $paymentReference = null): bool
    {
        return DB::transaction(function () use ($payrollId, $paymentReference) {
            $payroll = $this->findOrFail($payrollId);

            $metadata = $payroll->metadata ?? [];
            if ($paymentReference) {
                $metadata['payment_reference'] = $paymentReference;
            }
            $metadata['paid_at'] = now()->toISOString();

            $result = $payroll->update([
                'status' => 'paid',
                'paid_at' => now(),
                'metadata' => $metadata,
            ]);

            $this->clearCache();

            return $result;
        });
    }

    /**
     * Get employee payroll history
     */
    public function getEmployeePayrollHistory(string $employeeId, int $months = 12): Collection
    {
        $cacheKey = $this->getCacheKey('employee_payroll_history', [$employeeId, $months]);

        return cache()->remember($cacheKey, 1800, function () use ($employeeId, $months) {
            $startDate = now()->subMonths($months)->startOfMonth();

            return $this->model
                ->where('employee_id', $employeeId)
                ->where('payroll_period_start', '>=', $startDate)
                ->with(['payrollItems'])
                ->orderBy('payroll_period_start', 'desc')
                ->get();
        });
    }

    /**
     * Get overdue payrolls
     */
    public function getOverduePayrolls(?string $locationId = null): Collection
    {
        $cacheKey = $this->getCacheKey('overdue_payrolls', [$locationId]);

        return cache()->remember($cacheKey, 600, function () use ($locationId) {
            $query = $this->model
                ->where('status', '!=', 'paid')
                ->where('payroll_period_end', '<', now()->subDays(30))
                ->with(['employee.user', 'employee.location']);

            if ($locationId) {
                $query->whereHas('employee', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            }

            return $query->orderBy('payroll_period_end', 'asc')->get();
        });
    }

    /**
     * Search payrolls
     */
    public function searchPayrolls(string $query, ?string $status = null, ?string $locationId = null): Collection
    {
        $cacheKey = $this->getCacheKey('search_payrolls', [$query, $status, $locationId]);

        return cache()->remember($cacheKey, 600, function () use ($query, $status, $locationId) {
            $q = $this->model
                ->whereHas('employee', function ($q) use ($query) {
                    $q->where('full_name', 'LIKE', "%{$query}%")
                        ->orWhere('employee_id', 'LIKE', "%{$query}%");
                })
                ->with(['employee.user', 'employee.location', 'approver', 'processor']);

            if ($status) {
                $q->where('status', $status);
            }

            if ($locationId) {
                $q->whereHas('employee', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            }

            return $q->orderBy('payroll_period_start', 'desc')->limit(20)->get();
        });
    }

    /**
     * Get payroll export data
     */
    public function getPayrollExportData(string $startDate, string $endDate, ?string $locationId = null): Collection
    {
        $cacheKey = $this->getCacheKey('payroll_export_data', [$startDate, $endDate, $locationId]);

        return cache()->remember($cacheKey, 1800, function () use ($startDate, $endDate, $locationId) {
            $query = $this->model
                ->whereBetween('payroll_period_start', [$startDate, $endDate])
                ->with(['employee.user', 'employee.location', 'payrollItems'])
                ->where('status', '!=', 'draft');

            if ($locationId) {
                $query->whereHas('employee', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            }

            return $query->orderBy('payroll_period_start', 'desc')
                ->orderBy('employee_id', 'asc')
                ->get();
        });
    }

    /**
     * Get payroll calendar data
     */
    public function getPayrollCalendar(string $year): array
    {
        $cacheKey = $this->getCacheKey('payroll_calendar', [$year]);

        return cache()->remember($cacheKey, 3600, function () use ($year) {
            $startDate = Carbon::createFromDate($year, 1, 1);
            $endDate = Carbon::createFromDate($year, 12, 31);

            $payrolls = $this->model
                ->whereBetween('payroll_period_start', [$startDate, $endDate])
                ->with(['employee'])
                ->get();

            $calendar = [];

            foreach ($payrolls as $payroll) {
                $monthKey = $payroll->payroll_period_start->format('Y-m');

                if (! isset($calendar[$monthKey])) {
                    $calendar[$monthKey] = [
                        'month' => $payroll->payroll_period_start->format('F Y'),
                        'payrolls' => [],
                        'total_gross' => 0,
                        'total_net' => 0,
                        'count' => 0,
                    ];
                }

                $calendar[$monthKey]['payrolls'][] = [
                    'id' => $payroll->id,
                    'employee_name' => $payroll->employee->full_name,
                    'gross_salary' => $payroll->gross_salary,
                    'net_salary' => $payroll->net_salary,
                    'status' => $payroll->status,
                ];

                $calendar[$monthKey]['total_gross'] += $payroll->gross_salary;
                $calendar[$monthKey]['total_net'] += $payroll->net_salary;
                $calendar[$monthKey]['count']++;
            }

            return $calendar;
        });
    }
}
