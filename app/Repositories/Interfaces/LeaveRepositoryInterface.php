<?php

namespace App\Repositories\Interfaces;

use App\Models\Employee;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Leave Repository Interface
 *
 * Defines the contract for leave management data operations.
 */
interface LeaveRepositoryInterface
{
    /**
     * Get leave requests with filtering and pagination.
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find leave by ID.
     */
    public function findById(string $id): ?Leave;

    /**
     * Get leave requests for specific employee.
     */
    public function getEmployeeLeaves(Employee $employee, array $filters = []): Collection;

    /**
     * Get pending leave requests.
     */
    public function getPendingRequests(): Collection;

    /**
     * Get approved leave requests for date range.
     */
    public function getApprovedInRange(Carbon $startDate, Carbon $endDate): Collection;

    /**
     * Create new leave request.
     */
    public function create(array $data): Leave;

    /**
     * Update leave request.
     */
    public function update(Leave $leave, array $data): Leave;

    /**
     * Delete leave request.
     */
    public function delete(Leave $leave): bool;

    /**
     * Approve leave request.
     */
    public function approve(Leave $leave, Employee $approver, ?string $notes = null): Leave;

    /**
     * Reject leave request.
     */
    public function reject(Leave $leave, Employee $approver, string $reason): Leave;

    /**
     * Cancel leave request.
     */
    public function cancel(Leave $leave, string $reason): Leave;

    /**
     * Get leave balance for employee.
     */
    public function getEmployeeLeaveBalance(Employee $employee): array;

    /**
     * Update leave balance.
     */
    public function updateLeaveBalance(Employee $employee, string $leaveType, float $balance): bool;

    /**
     * Get leave statistics.
     */
    public function getStatistics(): array;

    /**
     * Get employees on leave for a specific date.
     */
    public function getEmployeesOnLeave(Carbon $date): Collection;

    /**
     * Check if employee has overlapping leave.
     */
    public function hasOverlappingLeave(
        Employee $employee,
        Carbon $startDate,
        Carbon $endDate,
        ?string $excludeLeaveId = null,
    ): bool;

    /**
     * Get leave calendar data for date range.
     */
    public function getLeaveCalendarData(Carbon $startDate, Carbon $endDate): array;

    /**
     * Get leave trends for analytics.
     */
    public function getLeaveTrends(int $months = 12): array;

    /**
     * Get leave requests requiring approval by manager.
     */
    public function getRequestsForApproval(Employee $manager): Collection;

    /**
     * Get leave usage summary for employee.
     */
    public function getLeaveUsageSummary(Employee $employee, ?int $year = null): array;

    /**
     * Get popular leave periods (for planning).
     */
    public function getPopularLeavePeriods(): array;

    /**
     * Bulk approve multiple leave requests.
     */
    public function bulkApprove(array $leaveIds, Employee $approver): bool;

    /**
     * Get leave expiry alerts.
     */
    public function getLeaveExpiryAlerts(int $daysThreshold = 30): Collection;

    /**
     * Calculate leave entitlement for employee.
     */
    public function calculateLeaveEntitlement(Employee $employee, string $leaveType): float;
}
