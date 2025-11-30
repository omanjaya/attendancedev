<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * User Credential Management Service
 * 
 * Handles user creation, password generation, and credential export for imported employees
 */
class UserCredentialService
{
    /**
     * Generate random secure password
     */
    public function generateSecurePassword(int $length = 12): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        // Ensure at least one character from each category
        $password .= chr(rand(97, 122)); // lowercase
        $password .= chr(rand(65, 90));  // uppercase
        $password .= chr(rand(48, 57));  // number
        $password .= '!@#$%^&*'[rand(0, 7)]; // special character
        
        // Fill the rest randomly
        for ($i = 4; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Shuffle the password
        return str_shuffle($password);
    }

    /**
     * Create user account for employee
     */
    public function createUserForEmployee(Employee $employee, ?string $customPassword = null): array
    {
        return DB::transaction(function () use ($employee, $customPassword) {
            // Check if user already exists
            if ($employee->user) {
                return [
                    'success' => false,
                    'message' => 'User already exists for this employee',
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                ];
            }

            // Generate password
            $password = $customPassword ?: $this->generateSecurePassword();

            // Create user
            $user = User::create([
                'name' => $employee->full_name,
                'email' => $employee->email,
                'password' => Hash::make($password),
                'email_verified_at' => now(), // Auto-verify for imported users
                'created_by' => auth()->id(),
            ]);

            // Link employee to user
            $employee->update(['user_id' => $user->id]);

            // Assign role based on employee type
            $roleName = $this->mapEmployeeTypeToRole($employee->employee_type);
            $role = Role::findByName($roleName);
            $user->assignRole($role);

            // Log the creation
            activity()
                ->performedOn($employee)
                ->causedBy(auth()->user())
                ->withProperties([
                    'user_id' => $user->id,
                    'role' => $roleName,
                    'auto_generated' => !$customPassword,
                ])
                ->log('User account created for employee');

            return [
                'success' => true,
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'employee_email' => $employee->email,
                'user_id' => $user->id,
                'username' => $user->email,
                'password' => $password, // Only returned once for security
                'role' => $roleName,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * Reset password for existing user
     */
    public function resetPasswordForEmployee(Employee $employee, ?string $customPassword = null): array
    {
        return DB::transaction(function () use ($employee, $customPassword) {
            // Debug: Log the employee data
            \Log::info('Resetting password for employee', [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'has_user' => $employee->user ? 'yes' : 'no',
                'user_id' => $employee->user_id ?? 'null'
            ]);
            
            if (!$employee->user) {
                return [
                    'success' => false,
                    'message' => 'No user account found for this employee',
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                ];
            }

            // Generate new password
            $newPassword = $customPassword ?: $this->generateSecurePassword();
            
            // Update user password
            $employee->user->update([
                'password' => Hash::make($newPassword),
                'updated_at' => now(),
            ]);

            // Log the reset
            activity()
                ->performedOn($employee)
                ->causedBy(auth()->user())
                ->withProperties([
                    'user_id' => $employee->user->id,
                    'reset_by' => auth()->user()->name,
                    'auto_generated' => !$customPassword,
                ])
                ->log('Password reset for employee user account');

            return [
                'success' => true,
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'employee_email' => $employee->email,
                'user_id' => $employee->user->id,
                'username' => $employee->user->email,
                'new_password' => $newPassword, // Only returned once for security
                'role' => $employee->user->roles->first()->name ?? 'guru',
                'reset_at' => now()->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * Bulk create users for multiple employees
     */
    public function bulkCreateUsers(array $employeeIds): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'skipped' => [],
        ];

        $employees = Employee::whereIn('id', $employeeIds)->get();

        foreach ($employees as $employee) {
            if ($employee->user) {
                $results['skipped'][] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'reason' => 'User already exists',
                ];
                continue;
            }

            try {
                $result = $this->createUserForEmployee($employee);
                if ($result['success']) {
                    $results['success'][] = $result;
                } else {
                    $results['failed'][] = $result;
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Bulk reset passwords for multiple employees
     */
    public function bulkResetPasswords(array $employeeIds): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        $employees = Employee::whereIn('id', $employeeIds)
            ->whereNotNull('user_id')
            ->with('user')
            ->get();

        foreach ($employees as $employee) {
            try {
                $result = $this->resetPasswordForEmployee($employee);
                if ($result['success']) {
                    $results['success'][] = $result;
                } else {
                    $results['failed'][] = $result;
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Map employee type to user role
     */
    private function mapEmployeeTypeToRole(string $employeeType): string
    {
        return match (strtolower($employeeType)) {
            'guru_tetap', 'guru tetap', 'permanent teacher' => 'guru',
            'guru_honorer', 'guru honorer', 'honorary teacher' => 'guru',
            'admin', 'administrator' => 'admin',
            'kepala_sekolah', 'kepala sekolah', 'principal' => 'kepala_sekolah',
            'staff', 'pegawai' => 'guru', // Default to guru role for staff
            default => 'guru',
        };
    }

    /**
     * Export user credentials to Excel
     */
    public function exportUserCredentials(array $credentialData, string $type = 'new_users'): string
    {
        $excelService = app(ExcelTemplateService::class);
        
        $headers = [
            'No',
            'Nama Lengkap',
            'Email/Username',
            'Password',
            'Role',
            'Status',
            'Tanggal Dibuat/Reset',
        ];

        $data = [];
        foreach ($credentialData as $index => $credential) {
            $data[] = [
                $index + 1,
                $credential['employee_name'],
                $credential['username'],
                $credential['password'] ?? $credential['new_password'] ?? '',
                ucfirst($credential['role']),
                $credential['success'] ? 'Berhasil' : 'Gagal',
                $credential['created_at'] ?? $credential['reset_at'] ?? '',
            ];
        }

        $filename = $type === 'new_users' 
            ? 'user_credentials_new_' . date('Y-m-d_H-i-s') . '.xlsx'
            : 'password_reset_' . date('Y-m-d_H-i-s') . '.xlsx';

        $title = $type === 'new_users' 
            ? 'Daftar User dan Password Baru'
            : 'Daftar Password Reset';

        return $excelService->generateCredentialExport($headers, $data, $title, $filename);
    }

    /**
     * Get employees without user accounts
     */
    public function getEmployeesWithoutUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return Employee::whereNull('user_id')
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Get employees with user accounts (for password reset)
     */
    public function getEmployeesWithUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return Employee::whereNotNull('user_id')
            ->with('user.roles')
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();
    }
}