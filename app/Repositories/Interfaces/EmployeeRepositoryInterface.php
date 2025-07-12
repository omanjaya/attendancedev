<?php

namespace App\Repositories\Interfaces;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Employee Repository Interface
 * 
 * Defines the contract for employee data operations.
 * This interface allows for easy testing and different implementations.
 */
interface EmployeeRepositoryInterface
{
    /**
     * Get all employees with optional filtering and pagination.
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get all active employees.
     */
    public function getActive(): Collection;

    /**
     * Find employee by ID.
     */
    public function findById(string $id): ?Employee;

    /**
     * Find employee by employee code.
     */
    public function findByEmployeeCode(string $code): ?Employee;

    /**
     * Find employee by user ID.
     */
    public function findByUserId(int $userId): ?Employee;

    /**
     * Create new employee.
     */
    public function create(array $data): Employee;

    /**
     * Update employee.
     */
    public function update(Employee $employee, array $data): Employee;

    /**
     * Delete employee (soft delete).
     */
    public function delete(Employee $employee): bool;

    /**
     * Restore soft deleted employee.
     */
    public function restore(Employee $employee): bool;

    /**
     * Get employees by department.
     */
    public function getByDepartment(string $department): Collection;

    /**
     * Get employees by role.
     */
    public function getByRole(string $role): Collection;

    /**
     * Get employees present today.
     */
    public function getPresentToday(): Collection;

    /**
     * Get employee statistics.
     */
    public function getStatistics(): array;

    /**
     * Search employees by name or employee code.
     */
    public function search(string $query): Collection;

    /**
     * Bulk create employees.
     */
    public function bulkCreate(array $employeesData): Collection;

    /**
     * Bulk update employees.
     */
    public function bulkUpdate(array $employeesData): bool;

    /**
     * Get employees with their attendance for a date range.
     */
    public function getWithAttendance(string $startDate, string $endDate): Collection;

    /**
     * Get employees with their leave balances.
     */
    public function getWithLeaveBalances(): Collection;
}