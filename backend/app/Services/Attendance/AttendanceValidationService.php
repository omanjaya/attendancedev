<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\EmployeeMonthlySchedule;
use App\Models\MonthlySchedule;
use App\Models\User;
use Carbon\Carbon;

class AttendanceValidationService
{
    /**
     * Check if user can bypass schedule validation
     */
    public function canBypassValidation(?User $user, Employee $employee, string $reason = 'general'): bool
    {
        // Check maintenance mode
        if (config('attendance.maintenance_mode', false)) {
            if (config('attendance.log_bypass', true)) {
                \Log::warning('Attendance validation bypassed: MAINTENANCE MODE', [
                    'employee_id' => $employee->id,
                    'reason' => 'maintenance_mode',
                ]);
            }
            return true;
        }

        // Check if strict mode is disabled globally
        if (!config('attendance.strict_mode', true)) {
            if (config('attendance.log_bypass', true)) {
                \Log::info('Attendance validation bypassed: STRICT MODE DISABLED', [
                    'employee_id' => $employee->id,
                    'reason' => 'strict_mode_disabled',
                ]);
            }
            return true;
        }

        // Check user role bypass
        if ($user) {
            $bypassRoles = config('attendance.bypass_roles', ['super_admin']);
            if ($user->hasAnyRole($bypassRoles)) {
                if (config('attendance.log_bypass', true)) {
                    \Log::info('Attendance validation bypassed: ROLE PRIVILEGE', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'user_roles' => $user->roles->pluck('name')->toArray(),
                        'employee_id' => $employee->id,
                        'reason' => $reason,
                        'timestamp' => now()->toISOString(),
                    ]);
                }
                return true;
            }
        }

        // Check employee metadata bypass flag
        if ($employee->metadata && isset($employee->metadata['bypass_schedule_validation'])) {
            if ($employee->metadata['bypass_schedule_validation'] === true) {
                if (config('attendance.log_bypass', true)) {
                    \Log::info('Attendance validation bypassed: EMPLOYEE FLAG', [
                        'employee_id' => $employee->id,
                        'reason' => 'employee_metadata_flag',
                    ]);
                }
                return true;
            }
        }

        return false;
    }

    /**
     * Get employee's active monthly schedule for a specific date
     */
    public function getEmployeeScheduleForDate(Employee $employee, ?Carbon $date = null): ?MonthlySchedule
    {
        $date = $date ?? now('Asia/Makassar');
        $month = $date->month;
        $year = $date->year;

        // Get the assigned schedule for this employee in specified month/year
        $employeeSchedule = EmployeeMonthlySchedule::where('employee_id', $employee->id)
            ->whereHas('monthlySchedule', function ($query) use ($month, $year) {
                $query->where('month', $month)
                      ->where('year', $year)
                      ->where('is_active', true);
            })
            ->with('monthlySchedule')
            ->first();

        return $employeeSchedule?->monthlySchedule;
    }

    /**
     * Validate if current date is a working day according to employee's schedule
     *
     * @return array ['valid' => bool, 'message' => string|null, 'schedule' => MonthlySchedule|null]
     */
    public function validateWorkingDay(Employee $employee, ?Carbon $date = null): array
    {
        $date = $date ?? now('Asia/Makassar');
        $dateStr = $date->toDateString(); // Format: "2025-02-01"

        // ===== CHECK BYPASS CONDITIONS =====
        $user = auth()->user();
        if ($this->canBypassValidation($user, $employee, 'working_day_validation')) {
            return [
                'valid' => true,
                'message' => null,
                'schedule' => null,
                'bypass' => true,
            ];
        }

        // ===== WORKING DAY VALIDATION (if not bypassed) =====

        // Check if working day validation is enabled
        if (!config('attendance.validate_working_days', true)) {
            return [
                'valid' => true,
                'message' => null,
                'schedule' => null,
            ];
        }

        // Get employee's schedule
        $schedule = $this->getEmployeeScheduleForDate($employee, $date);

        // If no schedule assigned, REJECT attendance (strict mode for regular employees)
        if (!$schedule) {
            return [
                'valid' => false,
                'message' => 'Anda belum memiliki jadwal kerja untuk bulan ini. Silakan hubungi admin untuk pengaturan jadwal.',
                'schedule' => null,
            ];
        }

        // Check if today is in the working_days array
        $workingDays = $schedule->working_days ?? [];
        $isWorkingDay = in_array($dateStr, $workingDays);

        if (!$isWorkingDay) {
            return [
                'valid' => false,
                'message' => 'Hari ini bukan hari kerja menurut jadwal Anda. Silakan hubungi admin jika terjadi kesalahan.',
                'schedule' => $schedule,
            ];
        }

        return [
            'valid' => true,
            'message' => null,
            'schedule' => $schedule,
        ];
    }

    /**
     * Validate if current time is within check-in window
     *
     * @return array ['valid' => bool, 'message' => string|null, 'is_late' => bool]
     */
    public function validateCheckInWindow(?MonthlySchedule $schedule, ?Carbon $time = null): array
    {
        // Check if time window validation is enabled
        if (!config('attendance.validate_time_windows', true)) {
            return [
                'valid' => true,
                'message' => null,
                'is_late' => false,
            ];
        }

        // ===== BYPASS FOR ADMIN ROLES (No schedule = admin bypass) =====
        if (!$schedule) {
            return [
                'valid' => true,
                'message' => null,
                'is_late' => false,
                'bypass' => true,
            ];
        }

        $time = $time ?? now('Asia/Makassar');
        $currentTime = $time->format('H:i:s');

        // Parse schedule times
        $checkinStart = Carbon::createFromFormat('H:i', $schedule->checkin_start_time)->format('H:i:s');
        $checkinEnd = Carbon::createFromFormat('H:i', $schedule->checkin_end_time)->format('H:i:s');
        $workStart = Carbon::createFromFormat('H:i', $schedule->default_start_time)->format('H:i:s');

        // Check if within allowed window
        if ($currentTime < $checkinStart) {
            return [
                'valid' => false,
                'message' => "Check-in hanya diperbolehkan mulai pukul {$schedule->checkin_start_time}. Saat ini terlalu awal.",
                'is_late' => false,
            ];
        }

        if ($currentTime > $checkinEnd) {
            return [
                'valid' => false,
                'message' => "Window check-in telah berakhir pada pukul {$schedule->checkin_end_time}. Silakan hubungi admin.",
                'is_late' => true,
            ];
        }

        // Check if late (after work start time)
        $isLate = $currentTime > $workStart;

        return [
            'valid' => true,
            'message' => $isLate ? "Anda terlambat. Jam kerja dimulai pada {$schedule->default_start_time}." : null,
            'is_late' => $isLate,
        ];
    }

    /**
     * Validate if current time is within check-out window
     *
     * @return array ['valid' => bool, 'message' => string|null, 'is_early' => bool]
     */
    public function validateCheckOutWindow(?MonthlySchedule $schedule, ?Carbon $time = null): array
    {
        // Check if time window validation is enabled
        if (!config('attendance.validate_time_windows', true)) {
            return [
                'valid' => true,
                'message' => null,
                'is_early' => false,
            ];
        }

        // ===== BYPASS FOR ADMIN ROLES (No schedule = admin bypass) =====
        if (!$schedule) {
            return [
                'valid' => true,
                'message' => null,
                'is_early' => false,
                'bypass' => true,
            ];
        }

        $time = $time ?? now('Asia/Makassar');
        $currentTime = $time->format('H:i:s');

        // Parse schedule times
        $checkoutStart = Carbon::createFromFormat('H:i', $schedule->checkout_start_time)->format('H:i:s');
        $checkoutEnd = Carbon::createFromFormat('H:i', $schedule->checkout_end_time)->format('H:i:s');
        $workEnd = Carbon::createFromFormat('H:i', $schedule->default_end_time)->format('H:i:s');

        // Check if within allowed window
        if ($currentTime < $checkoutStart) {
            return [
                'valid' => false,
                'message' => "Check-out hanya diperbolehkan mulai pukul {$schedule->checkout_start_time}. Saat ini terlalu awal.",
                'is_early' => true,
            ];
        }

        if ($currentTime > $checkoutEnd) {
            return [
                'valid' => false,
                'message' => "Window check-out telah berakhir pada pukul {$schedule->checkout_end_time}. Silakan hubungi admin.",
                'is_early' => false,
            ];
        }

        // Check if early (before work end time)
        $isEarly = $currentTime < $workEnd;

        return [
            'valid' => true,
            'message' => $isEarly ? "Anda pulang lebih awal. Jam kerja berakhir pada {$schedule->default_end_time}." : null,
            'is_early' => $isEarly,
        ];
    }
}
