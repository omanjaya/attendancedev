<?php

namespace App\Services\Employee;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeBulkService
{
    /**
     * Perform bulk actions on employees
     */
    public function performBulkAction(string $action, array $employeeIds): array
    {
        return DB::transaction(function () use ($action, $employeeIds) {
            $employees = Employee::with('user')->whereIn('id', $employeeIds)->get();
            $results = [];

            foreach ($employees as $employee) {
                try {
                    switch ($action) {
                        case 'delete':
                            $results[] = $this->deleteEmployee($employee);
                            break;
                        case 'reset_password':
                            $results[] = $this->resetPassword($employee);
                            break;
                        case 'activate':
                            $results[] = $this->activateEmployee($employee);
                            break;
                        case 'deactivate':
                            $results[] = $this->deactivateEmployee($employee);
                            break;
                    }
                } catch (\Exception $e) {
                    $results[] = [
                        'employee_id' => $employee->id,
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return $results;
        });
    }

    /**
     * Delete employee
     */
    private function deleteEmployee(Employee $employee): array
    {
        $employeeId = $employee->id;
        $userId = $employee->user_id;

        $employee->delete();

        // Optionally delete user account
        if ($userId && $employee->user) {
            $employee->user->delete();
        }

        return [
            'employee_id' => $employeeId,
            'success' => true,
            'action' => 'deleted',
        ];
    }

    /**
     * Reset password for employee
     */
    private function resetPassword(Employee $employee): array
    {
        if (!$employee->user) {
            throw new \Exception('User account not found for employee ' . $employee->id);
        }

        $newPassword = \Illuminate\Support\Str::random(12);

        $employee->user->update([
            'password' => Hash::make($newPassword),
            'force_password_change' => true,
        ]);

        return [
            'employee_id' => $employee->id,
            'success' => true,
            'action' => 'password_reset',
            'temporary_password' => $newPassword,
            'email' => $employee->user->email,
        ];
    }

    /**
     * Activate employee
     */
    private function activateEmployee(Employee $employee): array
    {
        $employee->update(['is_active' => true]);

        return [
            'employee_id' => $employee->id,
            'success' => true,
            'action' => 'activated',
        ];
    }

    /**
     * Deactivate employee
     */
    private function deactivateEmployee(Employee $employee): array
    {
        $employee->update(['is_active' => false]);

        return [
            'employee_id' => $employee->id,
            'success' => true,
            'action' => 'deactivated',
        ];
    }
}
