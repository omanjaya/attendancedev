<?php

namespace App\Repositories\Interfaces;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

/**
 * Attendance Repository Interface
 * 
 * Defines the contract for attendance data operations.
 */
interface AttendanceRepositoryInterface
{
    /**
     * Get attendance records with filtering and pagination.
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find attendance by ID.
     */
    public function findById(string $id): ?Attendance;

    /**
     * Get today's attendance for all employees.
     */
    public function getTodayAttendance(): Collection;

    /**
     * Get attendance for specific employee and date.
     */
    public function getEmployeeAttendanceByDate(Employee $employee, Carbon $date): ?Attendance;

    /**
     * Get attendance for employee within date range.
     */
    public function getEmployeeAttendanceInRange(Employee $employee, Carbon $startDate, Carbon $endDate): Collection;

    /**
     * Create new attendance record.
     */
    public function create(array $data): Attendance;

    /**
     * Update attendance record.
     */
    public function update(Attendance $attendance, array $data): Attendance;

    /**
     * Delete attendance record.
     */
    public function delete(Attendance $attendance): bool;

    /**
     * Check in employee.
     */
    public function checkIn(Employee $employee, array $data): Attendance;

    /**
     * Check out employee.
     */
    public function checkOut(Attendance $attendance, array $data): Attendance;

    /**
     * Get attendance statistics for today.
     */
    public function getTodayStatistics(): array;

    /**
     * Get attendance statistics for date range.
     */
    public function getStatisticsInRange(Carbon $startDate, Carbon $endDate): array;

    /**
     * Get late arrivals for a date.
     */
    public function getLateArrivals(Carbon $date): Collection;

    /**
     * Get early departures for a date.
     */
    public function getEarlyDepartures(Carbon $date): Collection;

    /**
     * Get absent employees for a date.
     */
    public function getAbsentEmployees(Carbon $date): Collection;

    /**
     * Get overtime records for date range.
     */
    public function getOvertimeInRange(Carbon $startDate, Carbon $endDate): Collection;

    /**
     * Get attendance trends data for charts.
     */
    public function getAttendanceTrends(int $days = 30): array;

    /**
     * Get employee attendance summary.
     */
    public function getEmployeeAttendanceSummary(Employee $employee, Carbon $startDate, Carbon $endDate): array;

    /**
     * Check if employee has checked in today.
     */
    public function hasCheckedInToday(Employee $employee): bool;

    /**
     * Check if employee has checked out today.
     */
    public function hasCheckedOutToday(Employee $employee): bool;

    /**
     * Get incomplete attendance records (checked in but not out).
     */
    public function getIncompleteAttendance(Carbon $date): Collection;

    /**
     * Calculate working hours for attendance record.
     */
    public function calculateWorkingHours(Attendance $attendance): float;

    /**
     * Get attendance pattern analysis for employee.
     */
    public function getAttendancePattern(Employee $employee, int $days = 30): array;
}