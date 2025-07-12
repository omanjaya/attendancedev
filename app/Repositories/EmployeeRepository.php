<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Repositories\Interfaces\EmployeeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Employee Repository Implementation
 * 
 * Concrete implementation of employee data operations.
 */
class EmployeeRepository implements EmployeeRepositoryInterface
{
    /**
     * Get all employees with optional filtering and pagination.
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Employee::with(['user', 'location']);

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('full_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('employee_code', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        if (!empty($filters['position'])) {
            $query->where('position', $filters['position']);
        }

        if (!empty($filters['employment_type'])) {
            $query->where('employment_type', $filters['employment_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get all active employees.
     */
    public function getActive(): Collection
    {
        return Employee::with(['user'])
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Find employee by ID.
     */
    public function findById(string $id): ?Employee
    {
        return Employee::with(['user', 'location', 'attendances' => function ($query) {
            $query->latest()->take(5);
        }])->find($id);
    }

    /**
     * Find employee by employee code.
     */
    public function findByEmployeeCode(string $code): ?Employee
    {
        return Employee::with(['user'])->where('employee_code', $code)->first();
    }

    /**
     * Find employee by user ID.
     */
    public function findByUserId(int $userId): ?Employee
    {
        return Employee::with(['user'])->where('user_id', $userId)->first();
    }

    /**
     * Create new employee.
     */
    public function create(array $data): Employee
    {
        // Generate employee code if not provided
        if (empty($data['employee_code'])) {
            $data['employee_code'] = $this->generateEmployeeCode();
        }

        return Employee::create($data);
    }

    /**
     * Update employee.
     */
    public function update(Employee $employee, array $data): Employee
    {
        $employee->update($data);
        return $employee->fresh();
    }

    /**
     * Delete employee (soft delete).
     */
    public function delete(Employee $employee): bool
    {
        return $employee->delete();
    }

    /**
     * Restore soft deleted employee.
     */
    public function restore(Employee $employee): bool
    {
        return $employee->restore();
    }

    /**
     * Get employees by department.
     */
    public function getByDepartment(string $department): Collection
    {
        return Employee::with(['user'])
            ->where('department', $department)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Get employees by role.
     */
    public function getByRole(string $role): Collection
    {
        return Employee::with(['user'])
            ->whereHas('user', function ($query) use ($role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Get employees present today.
     */
    public function getPresentToday(): Collection
    {
        return Employee::with(['user', 'attendances' => function ($query) {
            $query->whereDate('check_in_time', today());
        }])
        ->whereHas('attendances', function ($query) {
            $query->whereDate('check_in_time', today());
        })
        ->where('is_active', true)
        ->get();
    }

    /**
     * Get employee statistics.
     */
    public function getStatistics(): array
    {
        $total = Employee::count();
        $active = Employee::where('is_active', true)->count();
        $inactive = Employee::where('is_active', false)->count();
        $presentToday = $this->getPresentToday()->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'present_today' => $presentToday,
            'absent_today' => $active - $presentToday,
            'attendance_rate' => $active > 0 ? round(($presentToday / $active) * 100, 2) : 0
        ];
    }

    /**
     * Search employees by name or employee code.
     */
    public function search(string $query): Collection
    {
        return Employee::with(['user'])
            ->where(function ($q) use ($query) {
                $q->where('full_name', 'like', '%' . $query . '%')
                  ->orWhere('employee_code', 'like', '%' . $query . '%')
                  ->orWhere('email', 'like', '%' . $query . '%');
            })
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Bulk create employees.
     */
    public function bulkCreate(array $employeesData): Collection
    {
        $employees = collect();

        foreach ($employeesData as $data) {
            if (empty($data['employee_code'])) {
                $data['employee_code'] = $this->generateEmployeeCode();
            }
            $employees->push(Employee::create($data));
        }

        return $employees;
    }

    /**
     * Bulk update employees.
     */
    public function bulkUpdate(array $employeesData): bool
    {
        try {
            foreach ($employeesData as $data) {
                $employee = Employee::find($data['id']);
                if ($employee) {
                    $employee->update($data);
                }
            }
            return true;
        } catch (\Exception $e) {
            \Log::error('Bulk update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get employees with their attendance for a date range.
     */
    public function getWithAttendance(string $startDate, string $endDate): Collection
    {
        return Employee::with(['user', 'attendances' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('check_in_time', [$startDate, $endDate])
                  ->orderBy('check_in_time', 'desc');
        }])
        ->where('is_active', true)
        ->orderBy('full_name')
        ->get();
    }

    /**
     * Get employees with their leave balances.
     */
    public function getWithLeaveBalances(): Collection
    {
        return Employee::with(['user', 'leaveBalances'])
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Generate unique employee code.
     */
    private function generateEmployeeCode(): string
    {
        $prefix = 'EMP';
        $year = date('Y');
        
        // Get the last employee code for this year
        $lastEmployee = Employee::where('employee_code', 'like', $prefix . $year . '%')
            ->orderBy('employee_code', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_code, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}