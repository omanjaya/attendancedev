<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Leave Repository
 *
 * Handles all leave-related database operations
 */
class LeaveRepository extends BaseRepository
{
    public function __construct(Leave $leave)
    {
        parent::__construct($leave);
    }

    /**
     * Get leaves for an employee
     */
    public function getEmployeeLeaves(string $employeeId, ?string $status = null): Collection
    {
        $cacheKey = $this->getCacheKey('employee_leaves', [$employeeId, $status]);

        return cache()->remember($cacheKey, 1800, function () use ($employeeId, $status) {
            $query = $this->model
                ->where('employee_id', $employeeId)
                ->with(['leaveType', 'approver', 'employee']);

            if ($status) {
                $query->where('status', $status);
            }

            return $query->orderBy('start_date', 'desc')->get();
        });
    }

    /**
     * Get pending leaves for approval
     */
    public function getPendingLeaves(?string $locationId = null): Collection
    {
        $cacheKey = $this->getCacheKey('pending_leaves', [$locationId]);

        return cache()->remember($cacheKey, 600, function () use ($locationId) {
            $query = $this->model
                ->where('status', Leave::STATUS_PENDING)
                ->with(['employee.user', 'employee.location', 'leaveType']);

            if ($locationId) {
                $query->whereHas('employee', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            }

            return $query->orderBy('created_at', 'asc')->get();
        });
    }

    /**
     * Get leaves for a date range
     */
    public function getLeavesForDateRange(string $startDate, string $endDate, ?string $employeeId = null): Collection
    {
        $cacheKey = $this->getCacheKey('leaves_date_range', [$startDate, $endDate, $employeeId]);

        return cache()->remember($cacheKey, 1800, function () use ($startDate, $endDate, $employeeId) {
            $query = $this->model
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })
                ->with(['employee.user', 'employee.location', 'leaveType', 'approver']);

            if ($employeeId) {
                $query->where('employee_id', $employeeId);
            }

            return $query->orderBy('start_date', 'desc')->get();
        });
    }

    /**
     * Get leaves by status
     */
    public function getLeavesByStatus(string $status, ?string $locationId = null): Collection
    {
        $cacheKey = $this->getCacheKey('leaves_by_status', [$status, $locationId]);

        return cache()->remember($cacheKey, 1800, function () use ($status, $locationId) {
            $query = $this->model
                ->where('status', $status)
                ->with(['employee.user', 'employee.location', 'leaveType', 'approver']);

            if ($locationId) {
                $query->whereHas('employee', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            }

            return $query->orderBy('start_date', 'desc')->get();
        });
    }

    /**
     * Get upcoming leaves
     */
    public function getUpcomingLeaves(int $days = 7, ?string $locationId = null): Collection
    {
        $cacheKey = $this->getCacheKey('upcoming_leaves', [$days, $locationId]);

        return cache()->remember($cacheKey, 3600, function () use ($days, $locationId) {
            $startDate = now()->format('Y-m-d');
            $endDate = now()->addDays($days)->format('Y-m-d');

            $query = $this->model
                ->where('status', Leave::STATUS_APPROVED)
                ->whereBetween('start_date', [$startDate, $endDate])
                ->with(['employee.user', 'employee.location', 'leaveType']);

            if ($locationId) {
                $query->whereHas('employee', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            }

            return $query->orderBy('start_date', 'asc')->get();
        });
    }

    /**
     * Get leave statistics
     */
    public function getLeaveStatistics(?string $year = null): array
    {
        $year = $year ?? now()->year;
        $cacheKey = $this->getCacheKey('leave_statistics', [$year]);

        return cache()->remember($cacheKey, 3600, function () use ($year) {
            $startDate = Carbon::createFromDate($year, 1, 1);
            $endDate = Carbon::createFromDate($year, 12, 31);

            $leaves = $this->model
                ->whereBetween('start_date', [$startDate, $endDate])
                ->with(['leaveType'])
                ->get();

            $totalLeaves = $leaves->count();
            $approvedLeaves = $leaves->where('status', Leave::STATUS_APPROVED)->count();
            $pendingLeaves = $leaves->where('status', Leave::STATUS_PENDING)->count();
            $rejectedLeaves = $leaves->where('status', Leave::STATUS_REJECTED)->count();

            $leaveTypeStats = $leaves->groupBy('leave_type_id')
                ->map(function ($leaves, $typeId) {
                    $leaveType = $leaves->first()->leaveType;

                    return [
                        'type' => $leaveType->name,
                        'count' => $leaves->count(),
                        'total_days' => $leaves->sum('days_requested'),
                    ];
                })
                ->values()
                ->toArray();

            $monthlyStats = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthlyLeaves = $leaves->filter(function ($leave) use ($month) {
                    return $leave->start_date->month == $month;
                });

                $monthlyStats[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate(null, $month, 1)->format('F'),
                    'count' => $monthlyLeaves->count(),
                    'total_days' => $monthlyLeaves->sum('days_requested'),
                ];
            }

            return [
                'total_leaves' => $totalLeaves,
                'approved_leaves' => $approvedLeaves,
                'pending_leaves' => $pendingLeaves,
                'rejected_leaves' => $rejectedLeaves,
                'approval_rate' => $totalLeaves > 0 ? round(($approvedLeaves / $totalLeaves) * 100, 1) : 0,
                'total_days_requested' => $leaves->sum('days_requested'),
                'average_days_per_leave' => $totalLeaves > 0 ? round($leaves->avg('days_requested'), 1) : 0,
                'leave_type_stats' => $leaveTypeStats,
                'monthly_stats' => $monthlyStats,
            ];
        });
    }

    /**
     * Get employees on leave today
     */
    public function getEmployeesOnLeaveToday(): Collection
    {
        $cacheKey = $this->getCacheKey('employees_on_leave_today', [today()->format('Y-m-d')]);

        return cache()->remember($cacheKey, 1800, function () {
            return $this->model
                ->where('status', Leave::STATUS_APPROVED)
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->with(['employee.user', 'employee.location', 'leaveType'])
                ->get();
        });
    }

    /**
     * Get overlapping leaves for an employee
     */
    public function getOverlappingLeaves(string $employeeId, string $startDate, string $endDate, ?string $excludeId = null): Collection
    {
        $cacheKey = $this->getCacheKey('overlapping_leaves', [$employeeId, $startDate, $endDate, $excludeId]);

        return cache()->remember($cacheKey, 600, function () use ($employeeId, $startDate, $endDate, $excludeId) {
            $query = $this->model
                ->where('employee_id', $employeeId)
                ->where('status', '!=', Leave::STATUS_REJECTED)
                ->where('status', '!=', Leave::STATUS_CANCELLED)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                });

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            return $query->with(['leaveType'])->get();
        });
    }

    /**
     * Approve leave
     */
    public function approveLeave(string $leaveId, string $approverId, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($leaveId, $approverId, $notes) {
            $leave = $this->findOrFail($leaveId);

            $result = $leave->update([
                'status' => Leave::STATUS_APPROVED,
                'approved_by' => $approverId,
                'approved_at' => now(),
                'approval_notes' => $notes,
            ]);

            $this->clearCache();

            return $result;
        });
    }

    /**
     * Reject leave
     */
    public function rejectLeave(string $leaveId, string $approverId, string $reason): bool
    {
        return DB::transaction(function () use ($leaveId, $approverId, $reason) {
            $leave = $this->findOrFail($leaveId);

            $result = $leave->update([
                'status' => Leave::STATUS_REJECTED,
                'approved_by' => $approverId,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->clearCache();

            return $result;
        });
    }

    /**
     * Cancel leave
     */
    public function cancelLeave(string $leaveId, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($leaveId, $reason) {
            $leave = $this->findOrFail($leaveId);

            $metadata = $leave->metadata ?? [];
            $metadata['cancelled_at'] = now()->toISOString();
            $metadata['cancelled_by'] = auth()->id();
            if ($reason) {
                $metadata['cancellation_reason'] = $reason;
            }

            $result = $leave->update([
                'status' => Leave::STATUS_CANCELLED,
                'metadata' => $metadata,
            ]);

            $this->clearCache();

            return $result;
        });
    }

    /**
     * Get leave balance impact
     */
    public function getLeaveBalanceImpact(string $employeeId, string $leaveTypeId, string $startDate, string $endDate): array
    {
        $cacheKey = $this->getCacheKey('leave_balance_impact', [$employeeId, $leaveTypeId, $startDate, $endDate]);

        return cache()->remember($cacheKey, 1800, function () use ($employeeId, $leaveTypeId, $startDate, $endDate) {
            // Calculate working days between dates
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $workingDays = $start->diffInDaysFiltered(function (Carbon $date) {
                return $date->isWeekday();
            }, $end) + 1;

            // Get current year's approved leaves of this type
            $yearStart = now()->startOfYear();
            $yearEnd = now()->endOfYear();

            $usedDays = $this->model
                ->where('employee_id', $employeeId)
                ->where('leave_type_id', $leaveTypeId)
                ->where('status', Leave::STATUS_APPROVED)
                ->whereBetween('start_date', [$yearStart, $yearEnd])
                ->sum('days_requested');

            // Get leave type allowance (this would typically come from LeaveBalance)
            $leaveType = LeaveType::find($leaveTypeId);
            $allowance = $leaveType->default_days ?? 12; // Default to 12 days if not set

            return [
                'requested_days' => $workingDays,
                'used_days' => $usedDays,
                'allowance' => $allowance,
                'remaining_after' => $allowance - $usedDays - $workingDays,
                'sufficient_balance' => ($allowance - $usedDays - $workingDays) >= 0,
            ];
        });
    }

    /**
     * Get leave history for employee
     */
    public function getEmployeeLeaveHistory(string $employeeId, int $limit = 10): Collection
    {
        $cacheKey = $this->getCacheKey('employee_leave_history', [$employeeId, $limit]);

        return cache()->remember($cacheKey, 1800, function () use ($employeeId, $limit) {
            return $this->model
                ->where('employee_id', $employeeId)
                ->with(['leaveType', 'approver'])
                ->orderBy('start_date', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Search leaves
     */
    public function searchLeaves(string $query, ?string $status = null, ?string $locationId = null): Collection
    {
        $cacheKey = $this->getCacheKey('search_leaves', [$query, $status, $locationId]);

        return cache()->remember($cacheKey, 600, function () use ($query, $status, $locationId) {
            $q = $this->model
                ->whereHas('employee', function ($q) use ($query) {
                    $q->where('full_name', 'LIKE', "%{$query}%")
                        ->orWhere('employee_id', 'LIKE', "%{$query}%");
                })
                ->orWhere('reason', 'LIKE', "%{$query}%")
                ->with(['employee.user', 'employee.location', 'leaveType', 'approver']);

            if ($status) {
                $q->where('status', $status);
            }

            if ($locationId) {
                $q->whereHas('employee', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            }

            return $q->orderBy('start_date', 'desc')->limit(20)->get();
        });
    }

    /**
     * Get leave calendar data
     */
    public function getLeaveCalendarData(string $month, string $year): array
    {
        $cacheKey = $this->getCacheKey('leave_calendar', [$month, $year]);

        return cache()->remember($cacheKey, 3600, function () use ($month, $year) {
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

            $leaves = $this->model
                ->where('status', Leave::STATUS_APPROVED)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })
                ->with(['employee.user', 'leaveType'])
                ->get();

            $calendar = [];
            $current = $startDate->copy();

            while ($current <= $endDate) {
                $dayLeaves = $leaves->filter(function ($leave) use ($current) {
                    return $current->between($leave->start_date, $leave->end_date);
                });

                $calendar[$current->format('Y-m-d')] = [
                    'date' => $current->format('Y-m-d'),
                    'day' => $current->format('d'),
                    'leaves' => $dayLeaves->map(function ($leave) {
                        return [
                            'id' => $leave->id,
                            'employee_name' => $leave->employee->full_name,
                            'leave_type' => $leave->leaveType->name,
                            'leave_type_color' => $leave->leaveType->color ?? '#3B82F6',
                        ];
                    })->toArray(),
                ];

                $current->addDay();
            }

            return $calendar;
        });
    }
}
