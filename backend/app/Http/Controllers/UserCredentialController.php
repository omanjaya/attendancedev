<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\UserCredentialService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * User Credential Management Controller
 * 
 * Handles user creation and password management for imported employees
 */
class UserCredentialController extends Controller
{
    public function __construct(
        private UserCredentialService $userCredentialService
    ) {
        $this->middleware(['auth', 'verified']);
        $this->middleware('permission:manage_employees')->only(['index', 'create', 'reset']);
        $this->middleware('permission:export_data')->only(['exportCredentials']);
    }

    /**
     * Show user credential management page
     */
    public function index()
    {
        $employeesWithoutUsers = $this->userCredentialService->getEmployeesWithoutUsers();
        $employeesWithUsers = $this->userCredentialService->getEmployeesWithUsers();

        return view('pages.management.employees.user-credentials', [
            'employeesWithoutUsers' => $employeesWithoutUsers,
            'employeesWithUsers' => $employeesWithUsers,
            'stats' => [
                'total_employees' => Employee::active()->count(),
                'with_users' => $employeesWithUsers->count(),
                'without_users' => $employeesWithoutUsers->count(),
                'percentage_with_users' => Employee::active()->count() > 0 
                    ? round(($employeesWithUsers->count() / Employee::active()->count()) * 100, 1) 
                    : 0,
            ],
        ]);
    }

    /**
     * Create user account for single employee
     */
    public function createUser(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'custom_password' => 'nullable|string|min:8|max:50',
        ]);

        try {
            $employee = Employee::findOrFail($request->employee_id);
            $customPassword = $request->filled('custom_password') ? $request->custom_password : null;
            
            $result = $this->userCredentialService->createUserForEmployee($employee, $customPassword);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => "User account berhasil dibuat untuk {$employee->full_name}",
                    'data' => $result,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat user account: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create user accounts for multiple employees (bulk)
     */
    public function bulkCreateUsers(Request $request): JsonResponse
    {
        $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        try {
            $results = $this->userCredentialService->bulkCreateUsers($request->employee_ids);

            $successCount = count($results['success']);
            $failedCount = count($results['failed']);
            $skippedCount = count($results['skipped']);

            $message = "Bulk create users selesai: {$successCount} berhasil";
            if ($skippedCount > 0) {
                $message .= ", {$skippedCount} dilewati";
            }
            if ($failedCount > 0) {
                $message .= ", {$failedCount} gagal";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $results,
                'summary' => [
                    'success' => $successCount,
                    'failed' => $failedCount,
                    'skipped' => $skippedCount,
                    'total' => count($request->employee_ids),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat user accounts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset password for single employee
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'custom_password' => 'nullable|string|min:8|max:50',
        ]);

        try {
            $employee = Employee::findOrFail($request->employee_id);
            $customPassword = $request->filled('custom_password') ? $request->custom_password : null;
            
            \Log::info('Password reset request', [
                'employee_id' => $request->employee_id,
                'has_custom_password' => $request->filled('custom_password')
            ]);
            
            $result = $this->userCredentialService->resetPasswordForEmployee($employee, $customPassword);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => "Password berhasil direset untuk {$employee->full_name}",
                    'data' => $result,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }
        } catch (\Exception $e) {
            \Log::error('Password reset failed', [
                'employee_id' => $request->employee_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal reset password: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Reset passwords for multiple employees (bulk)
     */
    public function bulkResetPasswords(Request $request): JsonResponse
    {
        $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        try {
            $results = $this->userCredentialService->bulkResetPasswords($request->employee_ids);

            $successCount = count($results['success']);
            $failedCount = count($results['failed']);

            $message = "Bulk password reset selesai: {$successCount} berhasil";
            if ($failedCount > 0) {
                $message .= ", {$failedCount} gagal";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $results,
                'summary' => [
                    'success' => $successCount,
                    'failed' => $failedCount,
                    'total' => count($request->employee_ids),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal reset passwords: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export user credentials to Excel
     */
    public function exportCredentials(Request $request)
    {
        $request->validate([
            'type' => 'required|in:new_users,password_reset',
            'data' => 'required|array|min:1',
        ]);

        try {
            $credentialData = $request->data;
            $type = $request->type;

            $filePath = $this->userCredentialService->exportUserCredentials($credentialData, $type);

            $filename = $type === 'new_users' 
                ? 'Daftar_User_Password_Baru_' . date('Y-m-d_H-i-s') . '.xlsx'
                : 'Daftar_Password_Reset_' . date('Y-m-d_H-i-s') . '.xlsx';

            return response()->download($filePath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal export credentials: ' . $e->getMessage());
        }
    }

    /**
     * Get employees without user accounts (AJAX)
     */
    public function getEmployeesWithoutUsers(): JsonResponse
    {
        try {
            $employees = $this->userCredentialService->getEmployeesWithoutUsers();

            return response()->json([
                'success' => true,
                'data' => $employees->map(function ($employee) {
                    return [
                        'id' => $employee->id,
                        'full_name' => $employee->full_name,
                        'email' => $employee->email,
                        'employee_type' => $employee->employee_type,
                        'department' => $employee->location->name ?? 'N/A',
                        'hire_date' => $employee->hire_date?->format('Y-m-d'),
                    ];
                }),
                'count' => $employees->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get employees with user accounts (AJAX)
     */
    public function getEmployeesWithUsers(): JsonResponse
    {
        try {
            $employees = $this->userCredentialService->getEmployeesWithUsers();

            return response()->json([
                'success' => true,
                'data' => $employees->map(function ($employee) {
                    return [
                        'id' => $employee->id,
                        'full_name' => $employee->full_name,
                        'email' => $employee->email,
                        'employee_type' => $employee->employee_type,
                        'department' => $employee->location->name ?? 'N/A',
                        'user_role' => $employee->user?->roles->first()?->name ?? 'N/A',
                        'last_login' => $employee->user?->last_login_at?->format('Y-m-d H:i:s'),
                        'user_created_at' => $employee->user?->created_at?->format('Y-m-d H:i:s'),
                    ];
                }),
                'count' => $employees->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data: ' . $e->getMessage(),
            ], 500);
        }
    }
}