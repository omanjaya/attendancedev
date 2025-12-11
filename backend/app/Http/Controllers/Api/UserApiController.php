<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserApiController extends Controller
{
    use ApiResponseTrait;

    /**
     * Create a new user account for an employee (Admin only).
     * This is used when admin creates an employee and needs to create their login account.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createUserAccount(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:pegawai,guru,admin,kepala-sekolah'],
        ]);

        try {
            DB::beginTransaction();

            // Get employee details
            $employee = Employee::findOrFail($validated['employee_id']);

            // Check if employee already has a user account
            if (User::where('employee_id', $employee->id)->exists()) {
                return $this->errorResponse('This employee already has a user account', 422);
            }

            // Create user account
            $user = User::create([
                'name' => $employee->name,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'employee_id' => $employee->id,
                'role' => $validated['role'],
                'is_active' => true,
            ]);

            // Assign role using Spatie if available
            if (method_exists($user, 'assignRole')) {
                $user->assignRole($validated['role']);
            }

            DB::commit();

            return $this->createdResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'employee_id' => $user->employee_id,
                ],
            ], 'User account created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to create user account: ' . $e->getMessage());
        }
    }

    /**
     * Get user account details by employee ID.
     *
     * @param int $employeeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserByEmployeeId($employeeId)
    {
        try {
            $user = User::where('employee_id', $employeeId)
                ->with(['employee', 'roles'])
                ->first();

            if (!$user) {
                return $this->notFoundResponse('User account not found for this employee');
            }

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'employee_id' => $user->employee_id,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                ],
            ]);

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to get user: ' . $e->getMessage());
        }
    }

    /**
     * Check if employee has a user account.
     *
     * @param int $employeeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkEmployeeHasUser($employeeId)
    {
        try {
            $hasUser = User::where('employee_id', $employeeId)->exists();

            return $this->successResponse([
                'has_user' => $hasUser,
                'employee_id' => $employeeId,
            ]);

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to check user status: ' . $e->getMessage());
        }
    }
}
