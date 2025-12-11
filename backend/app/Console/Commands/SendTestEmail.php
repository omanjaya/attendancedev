<?php

namespace App\Console\Commands;

use App\Mail\AttendanceCheckInMail;
use App\Mail\AttendanceCheckOutMail;
use App\Mail\AttendanceReminderMail;
use App\Mail\PasswordResetMail;
use App\Mail\WelcomeEmployeeMail;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'email:test 
                            {type : Email type to send (check-in, check-out, reminder, password-reset, welcome)}
                            {--to= : Email address to send to (optional, uses first employee email if not specified)}
                            {--preview : Preview the email in browser instead of sending}';

    /**
     * The console command description.
     */
    protected $description = 'Send a test email notification for development/testing purposes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        $toEmail = $this->option('to');
        $preview = $this->option('preview');

        // Get sample employee for testing
        $employee = Employee::with('user')->first();
        
        if (!$employee) {
            $this->error('No employee found in database. Please seed the database first.');
            return self::FAILURE;
        }

        // Determine recipient email
        $email = $toEmail ?? $employee->user?->email ?? 'test@example.com';

        $this->info("Preparing to send {$type} email to: {$email}");

        try {
            $mailable = $this->createMailable($type, $employee);
            
            if (!$mailable) {
                $this->error("Unknown email type: {$type}");
                return self::FAILURE;
            }

            if ($preview) {
                // Render email to browser
                return $this->previewEmail($mailable);
            }

            // Send the email
            Mail::to($email)->send($mailable);
            
            $this->info("✅ Email sent successfully!");
            $this->line("Check your inbox at: {$email}");
            
            // If using log driver, show where to find it
            if (config('mail.default') === 'log') {
                $this->warn("📧 Email was logged (MAIL_MAILER=log). Check storage/logs/laravel.log");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to send email: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Create the appropriate mailable based on type
     */
    private function createMailable(string $type, Employee $employee): ?\Illuminate\Mail\Mailable
    {
        switch ($type) {
            case 'check-in':
                $attendance = $this->getOrCreateTodayAttendance($employee);
                return new AttendanceCheckInMail($employee, $attendance);

            case 'check-out':
                $attendance = $this->getOrCreateTodayAttendance($employee, true);
                return new AttendanceCheckOutMail($employee, $attendance);

            case 'reminder':
                return new AttendanceReminderMail($employee, 'check_in');

            case 'password-reset':
                if (!$employee->user) {
                    $this->error('Employee has no associated user account.');
                    return null;
                }
                return new PasswordResetMail($employee->user, 'TempPass123!', 'System Test');

            case 'welcome':
                if (!$employee->user) {
                    $this->error('Employee has no associated user account.');
                    return null;
                }
                return new WelcomeEmployeeMail($employee, $employee->user, 'Welcome123!');

            default:
                return null;
        }
    }

    /**
     * Get or create today's attendance for testing
     */
    private function getOrCreateTodayAttendance(Employee $employee, bool $withCheckOut = false): Attendance
    {
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', today())
            ->first();

        if (!$attendance) {
            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'date' => today(),
                'check_in_time' => now()->setTime(8, 30, 0),
                'check_in_latitude' => -8.6175,
                'check_in_longitude' => 115.2126,
                'check_in_confidence' => 0.95,
                'status' => 'present',
                'location_verified' => true,
            ]);
        }

        if ($withCheckOut && !$attendance->check_out_time) {
            $attendance->update([
                'check_out_time' => now()->setTime(17, 15, 0),
                'check_out_confidence' => 0.92,
                'total_hours' => 8.75,
            ]);
            $attendance->refresh();
        }

        return $attendance;
    }

    /**
     * Preview email in browser
     */
    private function previewEmail(\Illuminate\Mail\Mailable $mailable): int
    {
        $html = $mailable->render();
        
        $previewPath = storage_path('app/email-preview.html');
        file_put_contents($previewPath, $html);
        
        $this->info("📧 Email preview saved to: {$previewPath}");
        $this->line("Open this file in your browser to preview the email.");
        
        // Output rendered HTML for quick viewing
        $this->newLine();
        $this->line("=== EMAIL SUBJECT ===");
        $this->info($mailable->envelope()->subject ?? 'No subject');
        
        return self::SUCCESS;
    }
}
