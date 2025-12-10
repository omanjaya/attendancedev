<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\EmployeeResource;
use App\Http\Traits\PreventsIdor;
use App\Services\Employee\EmployeeService;
use App\Services\Employee\EmployeeStatisticsService;
use App\Services\Employee\EmployeeAvatarService;
use App\Services\Employee\EmployeeBulkService;
use Illuminate\Http\Request;

class EmployeeApiController extends BaseApiController
{
    use PreventsIdor;

    public function __construct(
        private EmployeeService $employeeService,
        private EmployeeStatisticsService $statisticsService,
        private EmployeeAvatarService $avatarService,
        private EmployeeBulkService $bulkService
    ) {}

    /**
     * Get list of employees with pagination and filtering
     */
    public function index(Request $request)
    {
        $result = $this->employeeService->getEmployees($request);

        return response()->json([
            'success' => true,
            'message' => 'Employees retrieved successfully',
            'data' => EmployeeResource::collection($result['data']),
            'meta' => $result['meta'],
            'timestamp' => now()->toISOString(),
        ]);
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
            'employee_type_id' => 'required|exists:employee_types,id',
            'salary_type' => 'sometimes|in:monthly,hourly',
            'base_salary' => 'sometimes|numeric|min:0',
            'hire_date' => 'sometimes|date',
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:8',
            'role' => 'nullable|string|in:pegawai,guru,admin,kepala-sekolah',
            'location_id' => 'nullable|uuid|exists:locations,id',
            'subject_id' => 'nullable|uuid|exists:subjects,id',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'position_id' => 'nullable|uuid|exists:positions,id',
        ]);

        try {
            $employee = $this->employeeService->createEmployee($validated);
            return $this->apiResponse(
                $employee->load(['user', 'location', 'subject', 'departmentRelation', 'positionRelation']),
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

        $employees = $this->employeeService->searchEmployees($query);
        return $this->apiResponse($employees, 'Search results');
    }

    /**
     * Get employee statistics
     */
    public function statistics()
    {
        $stats = $this->statisticsService->getStatistics();
        return $this->apiResponse($stats, 'Statistics retrieved successfully');
    }

    /**
     * Get single employee
     */
    public function show($id)
    {
        try {
            $employee = $this->employeeService->getEmployeeById($id);

            if (!$employee) {
                return $this->errorResponse('Employee not found', 404);
            }

            // IDOR Protection
            if (!$this->canAccessEmployee($employee)) {
                return $this->errorResponse('Unauthorized access to employee data', 403);
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
        $employee = $this->employeeService->getEmployeeById($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        // IDOR Protection
        if (!$this->canAccessEmployee($employee)) {
            return $this->errorResponse('Unauthorized access to employee data', 403);
        }

        $validated = $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'department' => 'sometimes|string|max:100',
            'position' => 'sometimes|string|max:100',
            'employee_type_id' => 'sometimes|exists:employee_types,id',
            'salary_type' => 'sometimes|in:monthly,hourly',
            'base_salary' => 'sometimes|numeric|min:0',
            'hire_date' => 'sometimes|date',
            'is_active' => 'boolean',
            'location_id' => 'nullable|uuid|exists:locations,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:100',
            'gender' => 'nullable|in:male,female',
            'nik' => 'nullable|string|max:20',
            'npwp' => 'nullable|string|max:25',
            'marital_status' => 'nullable|string|in:single,married,divorced,widowed',
            'religion' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|array',
            'education' => 'nullable|array',
            'bank_info' => 'nullable|array',
        ]);

        try {
            $employee = $this->employeeService->updateEmployee($employee, $validated);
            return $this->apiResponse($employee, 'Employee updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete employee
     */
    public function destroy($id)
    {
        $employee = $this->employeeService->getEmployeeById($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        try {
            $this->employeeService->deleteEmployee($employee);
            return $this->apiResponse(null, 'Employee deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get employees with face data
     */
    public function withFaceData()
    {
        $employees = $this->employeeService->getEmployeesWithFaceData();
        return $this->apiResponse($employees, 'Employees with face data retrieved');
    }

    /**
     * Get employee dashboard data
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->errorResponse('Employee record not found', 404);
        }

        $data = $this->statisticsService->getDashboard($employee);
        return $this->apiResponse($data, 'Employee dashboard data retrieved');
    }

    /**
     * Upload avatar for employee
     */
    public function uploadAvatar(Request $request, $id)
    {
        $employee = $this->employeeService->getEmployeeById($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        // IDOR Protection
        if (!$this->canAccessEmployee($employee)) {
            return $this->errorResponse('Unauthorized to upload avatar', 403);
        }

        $validated = $request->validate([
            'avatar' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        try {
            $result = $this->avatarService->uploadAvatar($employee, $request->file('avatar'));
            return $this->apiResponse($result, 'Avatar uploaded successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to upload avatar: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete employee avatar
     */
    public function deleteAvatar($id)
    {
        $employee = $this->employeeService->getEmployeeById($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        // IDOR Protection
        if (!$this->canAccessEmployee($employee)) {
            return $this->errorResponse('Unauthorized to delete avatar', 403);
        }

        try {
            $this->avatarService->deleteAvatar($employee);
            return $this->apiResponse(null, 'Avatar deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete avatar: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reset password for an employee's user account (Admin only)
     */
    public function resetPassword(Request $request, $id)
    {
        $employee = $this->employeeService->getEmployeeById($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        // Check admin permission
        $currentUser = $request->user();
        $userRoles = $currentUser->getRoleNames()->map(fn($role) => strtolower($role))->toArray();
        $allowedRoles = ['admin', 'super-admin', 'super admin', 'kepala-sekolah'];

        if (!array_intersect($userRoles, $allowedRoles)) {
            return $this->errorResponse('Unauthorized. Only admins can reset passwords.', 403);
        }

        $validated = $request->validate([
            'new_password' => 'sometimes|string|min:8',
            'send_email' => 'boolean',
        ]);

        try {
            $result = $this->avatarService->resetPassword($employee, $validated['new_password'] ?? null);
            $result['message'] = 'Password berhasil direset. User harus mengubah password saat login berikutnya.';
            return $this->apiResponse($result, 'Password reset successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reset password: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk actions for employees
     */
    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string|in:delete,reset_password,activate,deactivate',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|exists:employees,id',
        ]);

        // Check admin permission
        $currentUser = $request->user();
        $userRoles = $currentUser->getRoleNames()->map(fn($role) => strtolower($role))->toArray();
        $allowedRoles = ['admin', 'super-admin', 'super admin', 'kepala-sekolah'];

        if (!array_intersect($userRoles, $allowedRoles)) {
            return $this->errorResponse('Unauthorized. Only admins can perform bulk actions.', 403);
        }

        try {
            $results = $this->bulkService->performBulkAction($validated['action'], $validated['employee_ids']);

            $successful = collect($results)->where('success', true)->count();
            $failed = collect($results)->where('success', false)->count();

            return $this->apiResponse([
                'results' => $results,
                'summary' => [
                    'total' => count($results),
                    'successful' => $successful,
                    'failed' => $failed,
                ],
            ], "Bulk {$validated['action']} completed: {$successful} successful, {$failed} failed");
        } catch (\Exception $e) {
            return $this->errorResponse('Bulk action failed: ' . $e->getMessage(), 500);
        }
    }
}
