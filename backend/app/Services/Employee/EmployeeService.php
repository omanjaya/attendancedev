<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class EmployeeService
{
    /**
     * Get paginated list of employees with filtering
     */
    public function getEmployees(Request $request): array
    {
        $query = Employee::query()
            ->with(['user:id,name,email', 'location:id,name']);

        // Apply search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('email', 'like', "%{$search}%");
                    });
            });
        }

        // Apply department filter
        if ($department = $request->get('department')) {
            $query->whereJsonContains('metadata->department', $department);
        }

        // Apply position filter
        if ($position = $request->get('position')) {
            $query->whereJsonContains('metadata->position', $position);
        }

        // Apply employment type filter
        if ($type = $request->get('employment_type')) {
            $query->where('employee_type', $type);
        }

        // Apply status filter
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sorting
        $sortField = $request->get('sort', 'full_name');
        $sortDir = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDir);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $employees = $query->paginate($perPage);

        return [
            'data' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
            ],
        ];
    }

    /**
     * Create a new employee with user account
     */
    public function createEmployee(array $validated): Employee
    {
        return DB::transaction(function () use ($validated) {
            // Create user first
            $password = $validated['password'] ?? 'password123';
            $role = $validated['role'] ?? 'pegawai';

            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => Hash::make($password),
                'force_password_change' => false,
            ]);

            // Assign role
            $user->assignRole($role);

            // Prepare metadata
            $metadata = [];
            if (isset($validated['department'])) {
                $metadata['department'] = $validated['department'];
            }
            if (isset($validated['position'])) {
                $metadata['position'] = $validated['position'];
            }

            // Determine legacy employee_type
            $legacyEmployeeType = 'staff';
            if ($role === 'guru') {
                $legacyEmployeeType = 'honorary';
            } elseif (in_array($role, ['admin', 'kepala-sekolah'])) {
                $legacyEmployeeType = 'permanent';
            }

            $employeeData = [
                'user_id' => $user->id,
                'employee_id' => $validated['employee_code'] ?? 'EMP' . str_pad(Employee::count() + 1, 4, '0', STR_PAD_LEFT),
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'] ?? null,
                'employee_type' => $legacyEmployeeType,
                'employee_type_id' => $validated['employee_type_id'],
                'salary_type' => $validated['salary_type'] ?? 'monthly',
                'salary_amount' => $validated['base_salary'] ?? 0,
                'hire_date' => $validated['hire_date'] ?? now(),
                'is_active' => $validated['is_active'] ?? true,
                'location_id' => $validated['location_id'] ?? null,
                'subject_id' => $validated['subject_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'position_id' => $validated['position_id'] ?? null,
                'metadata' => $metadata,
            ];

            return Employee::create($employeeData);
        });
    }

    /**
     * Update employee data
     */
    public function updateEmployee(Employee $employee, array $validated): Employee
    {
        return DB::transaction(function () use ($employee, $validated) {
            // Prepare metadata
            $metadata = $employee->metadata ?? [];

            // Update basic fields
            $updates = [];

            $directFields = [
                'full_name', 'phone', 'employee_type_id', 'salary_type',
                'hire_date', 'is_active', 'location_id', 'subject_id',
                'department_id', 'position_id'
            ];

            foreach ($directFields as $field) {
                if (isset($validated[$field])) {
                    if ($field === 'base_salary') {
                        $updates['salary_amount'] = $validated[$field];
                    } else {
                        $updates[$field] = $validated[$field];
                    }
                }
            }

            // Update metadata fields
            $metadataFields = [
                'department', 'position', 'birth_date', 'birth_place', 'gender',
                'nik', 'npwp', 'marital_status', 'religion', 'address',
                'emergency_contact', 'education', 'bank_info'
            ];

            foreach ($metadataFields as $field) {
                if (isset($validated[$field])) {
                    $metadata[$field] = $validated[$field];
                }
            }

            if (!empty($metadata)) {
                $updates['metadata'] = $metadata;
            }

            // Update employee
            $employee->update($updates);

            // Update user if needed
            if (isset($validated['email'])) {
                $employee->user->update(['email' => $validated['email']]);
            }

            return $employee->fresh(['user', 'location', 'subject', 'departmentRelation', 'positionRelation']);
        });
    }

    /**
     * Delete employee
     */
    public function deleteEmployee(Employee $employee): bool
    {
        return DB::transaction(function () use ($employee) {
            // Soft delete or hard delete based on business logic
            $employee->delete();

            // Optionally delete user account
            if ($employee->user) {
                $employee->user->delete();
            }

            return true;
        });
    }

    /**
     * Search employees
     */
    public function searchEmployees(string $query, int $limit = 20): array
    {
        if (empty($query)) {
            return [];
        }

        return Employee::query()
            ->select(['id', 'employee_id', 'full_name', 'is_active'])
            ->where('full_name', 'like', "%{$query}%")
            ->orWhere('employee_id', 'like', "%{$query}%")
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get employee by ID
     */
    public function getEmployeeById(string $id): ?Employee
    {
        return Employee::with(['user', 'location', 'subject', 'departmentRelation', 'positionRelation'])->find($id);
    }

    /**
     * Get employees with face data
     */
    public function getEmployeesWithFaceData(): array
    {
        return Employee::query()
            ->whereHas('faceData')
            ->with(['faceData' => function ($query) {
                $query->select('id', 'employee_id', 'is_verified', 'embedding_path', 'created_at');
            }])
            ->select(['id', 'employee_id', 'full_name', 'is_active'])
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get()
            ->toArray();
    }
}
