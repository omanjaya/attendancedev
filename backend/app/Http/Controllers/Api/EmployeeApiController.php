<?php

namespace App\Http\Controllers\Api;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeApiController extends BaseApiController
{
    /**
     * Get list of employees with pagination and filtering
     */
    public function index(Request $request)
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
            $query->where('department', $department);
        }

        // Apply position filter
        if ($position = $request->get('position')) {
            $query->where('position', $position);
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

        return $this->paginatedResponse($employees, 'Employees retrieved successfully');
    }

    /**
     * Create a new employee
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'employee_code' => 'nullable|string|unique:employees,employee_id',
            'department' => 'sometimes|string|max:100',
            'position' => 'sometimes|string|max:100',
            'employment_type' => 'sometimes|in:permanent,contract,part_time,honorary',
            'salary_type' => 'sometimes|in:monthly,hourly',
            'base_salary' => 'sometimes|numeric|min:0',
            'hire_date' => 'sometimes|date',
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:8', // Accept password from frontend
            'role' => 'nullable|string|in:pegawai,guru,admin,kepala-sekolah', // Accept role
        ]);

        try {
            $employee = DB::transaction(function () use ($validated) {
                // Create user first with provided or default password
                $password = $validated['password'] ?? 'password123';
                $role = $validated['role'] ?? 'pegawai';

                $user = \App\Models\User::create([
                    'name' => $validated['full_name'],
                    'email' => $validated['email'],
                    'password' => bcrypt($password),
                    'role' => $role,
                    'force_password_change' => true, // User must change password on first login
                ]);

                // Create employee
                return Employee::create([
                    'user_id' => $user->id,
                    'employee_id' => $validated['employee_code'] ?? 'EMP' . str_pad(Employee::count() + 1, 4, '0', STR_PAD_LEFT),
                    'full_name' => $validated['full_name'],
                    'phone' => $validated['phone'] ?? null,
                    'employee_type' => $validated['employment_type'] ?? 'staff',
                    'salary_type' => $validated['salary_type'] ?? 'monthly',
                    'salary_amount' => $validated['base_salary'] ?? 0,
                    'hire_date' => $validated['hire_date'] ?? now(),
                    'is_active' => $validated['is_active'] ?? true,
                ]);
            });

            return $this->apiResponse(
                $employee->load(['user', 'location']),
                'Employee created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Search employees
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return $this->apiResponse([], 'Search query required');
        }

        $employees = Employee::query()
            ->select(['id', 'employee_id', 'full_name', 'is_active'])
            ->where('full_name', 'like', "%{$query}%")
            ->orWhere('employee_id', 'like', "%{$query}%")
            ->limit(20)
            ->get();

        return $this->apiResponse($employees, 'Search results');
    }

    /**
     * Get employee statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => Employee::count(),
            'active' => Employee::where('is_active', true)->count(),
            'inactive' => Employee::where('is_active', false)->count(),
            'by_type' => Employee::select('employee_type', DB::raw('count(*) as count'))
                ->groupBy('employee_type')
                ->pluck('count', 'employee_type'),
        ];

        return $this->apiResponse($stats, 'Statistics retrieved successfully');
    }

    /**
     * Get single employee
     */
    public function show($id)
    {
        try {
            $employee = Employee::with(['user', 'location'])->find($id);

            if (!$employee) {
                return $this->errorResponse('Employee not found', 404);
            }

            return $this->apiResponse($employee, 'Employee retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update employee
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        $validated = $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'department' => 'sometimes|string|max:100',
            'position' => 'sometimes|string|max:100',
            'employment_type' => 'sometimes|in:permanent,contract,part_time,honorary',
            'salary_type' => 'sometimes|in:monthly,hourly',
            'base_salary' => 'sometimes|numeric|min:0',
            'hire_date' => 'sometimes|date',
            'is_active' => 'boolean',
        ]);

        try {
            $employee->update([
                'full_name' => $validated['full_name'] ?? $employee->full_name,
                'phone' => $validated['phone'] ?? $employee->phone,
                'employee_type' => $validated['employment_type'] ?? $employee->employee_type,
                'salary_type' => $validated['salary_type'] ?? $employee->salary_type,
                'salary_amount' => $validated['base_salary'] ?? $employee->salary_amount,
                'hire_date' => $validated['hire_date'] ?? $employee->hire_date,
                'is_active' => $validated['is_active'] ?? $employee->is_active,
            ]);

            return $this->apiResponse(
                $employee->fresh()->load(['user', 'location']),
                'Employee updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete employee
     */
    public function destroy($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        try {
            $employee->delete();

            return $this->apiResponse(null, 'Employee deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete employee: ' . $e->getMessage(), 500);
        }
    }
    /**
     * Get employees with registered face data
     */
    public function withFaceData()
    {
        // Filter employees who have face descriptor in metadata
        $employees = Employee::where('is_active', true)
            ->get()
            ->filter(function ($employee) {
                return isset($employee->metadata['face_recognition']['descriptor']);
            })
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'employee_id' => $employee->employee_id,
                    'photo_url' => $employee->photo_url,
                    'face_descriptor' => $employee->metadata['face_recognition']['descriptor'] ?? null
                ];
            })
            ->values();

        return $this->apiResponse($employees, 'Employees with face data retrieved');
    }
}
