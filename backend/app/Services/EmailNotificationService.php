<?php

namespace App\Services;

use App\Mail\AttendanceCheckInMail;
use App\Mail\AttendanceCheckOutMail;
use App\Mail\AttendanceReminderMail;
use App\Mail\PasswordResetMail;
use App\Mail\WelcomeEmployeeMail;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    /**
     * Check if email notifications are enabled
     */
    protected function isEmailEnabled(): bool
    {
        // Check if mail is configured (not just log driver)
        $mailer = config('mail.default');
        
        // In development, we might want to log emails instead
        if (app()->environment('local') && $mailer === 'log') {
            return true; // Still send to log
        }
        
        return !empty(config('mail.from.address')) && config('mail.from.address') !== 'hello@example.com';
    }

    /**
     * Check if user wants to receive email notifications
     */
    protected function userWantsEmail(User $user, string $notificationType): bool
    {
        // Check user notification preferences if exists
        $preferences = $user->notification_preferences ?? [];
        
        // Default to true if no specific preference set
        $emailEnabled = $preferences['email_enabled'] ?? true;
        $typeEnabled = $preferences[$notificationType] ?? true;
        
        return $emailEnabled && $typeEnabled;
    }

    /**
     * Send check-in notification email
     */
    public function sendCheckInEmail(Employee $employee, Attendance $attendance): void
    {
        try {
            $user = $employee->user;
            
            if (!$user || !$user->email) {
                Log::info('Skipping check-in email: No user or email', [
                    'employee_id' => $employee->id,
                ]);
                return;
            }

            if (!$this->userWantsEmail($user, 'attendance_check_in')) {
                Log::info('Skipping check-in email: User preference disabled', [
                    'employee_id' => $employee->id,
                ]);
                return;
            }

            Mail::to($user->email)->queue(new AttendanceCheckInMail($employee, $attendance));
            
            Log::info('Check-in email queued', [
                'employee_id' => $employee->id,
                'email' => $user->email,
                'is_late' => $attendance->status === 'late',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue check-in email', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send check-out notification email
     */
    public function sendCheckOutEmail(Employee $employee, Attendance $attendance): void
    {
        try {
            $user = $employee->user;
            
            if (!$user || !$user->email) {
                return;
            }

            if (!$this->userWantsEmail($user, 'attendance_check_out')) {
                return;
            }

            Mail::to($user->email)->queue(new AttendanceCheckOutMail($employee, $attendance));
            
            Log::info('Check-out email queued', [
                'employee_id' => $employee->id,
                'email' => $user->email,
                'total_hours' => $attendance->total_hours,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue check-out email', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send attendance reminder email
     */
    public function sendReminderEmail(Employee $employee, string $reminderType = 'check_in', ?string $message = null): void
    {
        try {
            $user = $employee->user;
            
            if (!$user || !$user->email) {
                return;
            }

            if (!$this->userWantsEmail($user, 'attendance_reminder')) {
                return;
            }

            Mail::to($user->email)->queue(new AttendanceReminderMail($employee, $reminderType, $message));
            
            Log::info('Reminder email queued', [
                'employee_id' => $employee->id,
                'type' => $reminderType,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue reminder email', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(User $user, string $temporaryPassword, string $resetBy = 'Administrator'): void
    {
        try {
            if (!$user->email) {
                return;
            }

            Mail::to($user->email)->queue(new PasswordResetMail($user, $temporaryPassword, $resetBy));
            
            Log::info('Password reset email queued', [
                'user_id' => $user->id,
                'email' => $user->email,
                'reset_by' => $resetBy,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue password reset email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send welcome email to new employee
     */
    public function sendWelcomeEmail(Employee $employee, User $user, string $temporaryPassword): void
    {
        try {
            if (!$user->email) {
                return;
            }

            Mail::to($user->email)->queue(new WelcomeEmployeeMail($employee, $user, $temporaryPassword));
            
            Log::info('Welcome email queued', [
                'employee_id' => $employee->id,
                'email' => $user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue welcome email', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send bulk reminder emails for employees who haven't checked in
     */
    public function sendBulkCheckInReminders(): int
    {
        $count = 0;
        
        try {
            $absentEmployees = Employee::where('is_active', true)
                ->whereDoesntHave('attendances', function ($query) {
                    $query->whereDate('date', today());
                })
                ->with('user')
                ->get();

            foreach ($absentEmployees as $employee) {
                if ($employee->user && $employee->user->email) {
                    $this->sendReminderEmail($employee, 'check_in');
                    $count++;
                }
            }

            Log::info('Bulk check-in reminders sent', ['count' => $count]);
        } catch (\Exception $e) {
            Log::error('Failed to send bulk check-in reminders', [
                'error' => $e->getMessage(),
            ]);
        }

        return $count;
    }

    /**
     * Send bulk reminder emails for employees who haven't checked out
     */
    public function sendBulkCheckOutReminders(): int
    {
        $count = 0;
        
        try {
            $needCheckoutEmployees = Employee::where('is_active', true)
                ->whereHas('attendances', function ($query) {
                    $query->whereDate('date', today())
                        ->whereNotNull('check_in_time')
                        ->whereNull('check_out_time');
                })
                ->with('user')
                ->get();

            foreach ($needCheckoutEmployees as $employee) {
                if ($employee->user && $employee->user->email) {
                    $this->sendReminderEmail($employee, 'check_out');
                    $count++;
                }
            }

            Log::info('Bulk check-out reminders sent', ['count' => $count]);
        } catch (\Exception $e) {
            Log::error('Failed to send bulk check-out reminders', [
                'error' => $e->getMessage(),
            ]);
        }

        return $count;
    }
}
