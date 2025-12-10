<?php

namespace App\Services\Employee;

use App\Models\Employee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EmployeeAvatarService
{
    /**
     * Upload avatar for employee
     */
    public function uploadAvatar(Employee $employee, UploadedFile $avatar): array
    {
        // Delete old avatar if exists
        if ($employee->photo_path && Storage::disk('public')->exists($employee->photo_path)) {
            Storage::disk('public')->delete($employee->photo_path);
        }

        // Store new avatar
        $avatarPath = $avatar->store('avatars', 'public');

        // Update employee photo_path
        $employee->update(['photo_path' => $avatarPath]);

        return [
            'employee' => $employee->fresh(['user', 'location']),
            'avatar_url' => asset("storage/{$avatarPath}"),
        ];
    }

    /**
     * Delete employee avatar
     */
    public function deleteAvatar(Employee $employee): bool
    {
        // Delete avatar file if exists
        if ($employee->photo_path && Storage::disk('public')->exists($employee->photo_path)) {
            Storage::disk('public')->delete($employee->photo_path);
        }

        // Clear photo_path in database
        $employee->update(['photo_path' => null]);

        return true;
    }

    /**
     * Reset password for an employee's user account
     */
    public function resetPassword(Employee $employee, ?string $newPassword = null): array
    {
        if (!$employee->user) {
            throw new \Exception('User account not found for this employee');
        }

        // Generate random password if not provided
        $password = $newPassword ?? \Illuminate\Support\Str::random(12);

        $employee->user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($password),
            'force_password_change' => true,
            'password_changed_at' => null,
        ]);

        return [
            'employee_id' => $employee->id,
            'email' => $employee->user->email,
            'temporary_password' => $password,
            'force_password_change' => true,
        ];
    }
}
