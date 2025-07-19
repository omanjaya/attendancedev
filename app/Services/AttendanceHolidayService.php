<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceHolidayService
{
    /**
     * Check if a given date is a holiday
     */
    public function isHoliday(Carbon $date): bool
    {
        return Holiday::where('date', $date->format('Y-m-d'))
            ->where('status', Holiday::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Get holiday information for a date
     */
    public function getHoliday(Carbon $date): ?Holiday
    {
        return Holiday::where('date', $date->format('Y-m-d'))
            ->where('status', Holiday::STATUS_ACTIVE)
            ->first();
    }

    /**
     * Check if a holiday is a paid day off
     */
    public function isHolidayPaid(Carbon $date): bool
    {
        $holiday = $this->getHoliday($date);

        return $holiday ? $holiday->is_paid : false;
    }

    /**
     * Get all holidays in a date range
     */
    public function getHolidaysInRange(Carbon $startDate, Carbon $endDate): Collection
    {
        return Holiday::where('status', Holiday::STATUS_ACTIVE)
            ->whereBetween('date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ])
            ->orderBy('date')
            ->get();
    }

    /**
     * Calculate working days excluding holidays
     */
    public function calculateWorkingDays(Carbon $startDate, Carbon $endDate): int
    {
        $workingDays = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            // Skip weekends (Saturday and Sunday)
            if (! $current->isWeekend() && ! $this->isHoliday($current)) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Calculate expected working hours for a period, excluding holidays
     */
    public function calculateExpectedWorkingHours(
        Employee $employee,
        Carbon $startDate,
        Carbon $endDate,
        float $dailyHours = 8.0
    ): float {
        $workingDays = $this->calculateWorkingDays($startDate, $endDate);

        return $workingDays * $dailyHours;
    }

    /**
     * Get attendance summary with holiday adjustments
     */
    public function getAttendanceSummary(Employee $employee, Carbon $month): array
    {
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        // Get all attendances for the month
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Get holidays in the month
        $holidays = $this->getHolidaysInRange($startDate, $endDate);

        // Calculate statistics
        $totalWorkingDays = $this->calculateWorkingDays($startDate, $endDate);
        $expectedHours = $this->calculateExpectedWorkingHours($employee, $startDate, $endDate);

        $actualHours = $attendances->sum('total_hours');
        $presentDays = $attendances->where('status', '!=', 'absent')->count();
        $absentDays = $totalWorkingDays - $presentDays;

        // Holidays breakdown
        $paidHolidays = $holidays->where('is_paid', true)->count();
        $unpaidHolidays = $holidays->where('is_paid', false)->count();

        return [
            'employee' => $employee,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
                'month_name' => $month->format('F Y'),
            ],
            'working_days' => [
                'total_working_days' => $totalWorkingDays,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'attendance_rate' => $totalWorkingDays > 0 ? round(($presentDays / $totalWorkingDays) * 100, 2) : 0,
            ],
            'working_hours' => [
                'expected_hours' => $expectedHours,
                'actual_hours' => $actualHours,
                'variance_hours' => $actualHours - $expectedHours,
                'efficiency_rate' => $expectedHours > 0 ? round(($actualHours / $expectedHours) * 100, 2) : 0,
            ],
            'holidays' => [
                'total_holidays' => $holidays->count(),
                'paid_holidays' => $paidHolidays,
                'unpaid_holidays' => $unpaidHolidays,
                'holiday_list' => $holidays->map(function ($holiday) {
                    return [
                        'name' => $holiday->name,
                        'date' => $holiday->date,
                        'type' => $holiday->type,
                        'is_paid' => $holiday->is_paid,
                    ];
                }),
            ],
            'status_breakdown' => [
                'present' => $attendances->where('status', 'present')->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'early_departure' => $attendances->where('status', 'early_departure')->count(),
                'incomplete' => $attendances->where('status', 'incomplete')->count(),
                'absent' => $absentDays,
            ],
        ];
    }

    /**
     * Adjust attendance status considering holidays
     */
    public function adjustAttendanceForHolidays(Attendance $attendance): string
    {
        $date = Carbon::parse($attendance->date);

        // Check if the date is a holiday
        if ($this->isHoliday($date)) {
            $holiday = $this->getHoliday($date);

            // If it's a holiday and employee still came to work
            if ($attendance->check_in_time) {
                // Check if it's a school holiday where teachers might still work
                if ($holiday->type === Holiday::TYPE_SCHOOL) {
                    return 'holiday_work'; // Custom status for working on school holidays
                } else {
                    return 'holiday_overtime'; // Working on public/religious holiday
                }
            } else {
                // Not present on holiday - this is normal
                return $holiday->is_paid ? 'holiday_paid' : 'holiday_unpaid';
            }
        }

        // If it's a weekend, handle differently
        if ($date->isWeekend()) {
            if ($attendance->check_in_time) {
                return 'weekend_work';
            } else {
                return 'weekend'; // Normal weekend off
            }
        }

        // Use normal status determination for regular working days
        return $attendance->determineStatus();
    }

    /**
     * Generate payroll-ready data with holiday adjustments
     */
    public function generatePayrollData(Employee $employee, Carbon $month): array
    {
        $summary = $this->getAttendanceSummary($employee, $month);

        // Base salary calculation
        $baseSalary = $employee->salary_amount ?? 0;
        $hourlyRate = $employee->hourly_rate ?? ($baseSalary / (22 * 8)); // Assuming 22 working days, 8 hours per day

        // Calculate holiday pay
        $paidHolidayHours = $summary['holidays']['paid_holidays'] * 8; // 8 hours per holiday
        $holidayPay = $paidHolidayHours * $hourlyRate;

        // Calculate overtime for holiday work
        $holidayWorkHours = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$summary['period']['start'], $summary['period']['end']])
            ->whereHas('holiday', function ($query) {
                $query->where('status', Holiday::STATUS_ACTIVE);
            })
            ->sum('total_hours');

        $overtimePay = $holidayWorkHours * $hourlyRate * 1.5; // 1.5x for holiday work

        // Calculate deductions for unpaid absences
        $absentHours = ($summary['working_days']['absent_days']) * 8;
        $absentDeduction = $absentHours * $hourlyRate;

        return [
            'employee' => $employee,
            'period' => $summary['period'],
            'base_salary' => $baseSalary,
            'hourly_rate' => $hourlyRate,
            'regular_hours' => $summary['working_hours']['actual_hours'],
            'holiday_hours' => $paidHolidayHours,
            'overtime_hours' => $holidayWorkHours,
            'regular_pay' => $summary['working_hours']['actual_hours'] * $hourlyRate,
            'holiday_pay' => $holidayPay,
            'overtime_pay' => $overtimePay,
            'absent_deduction' => $absentDeduction,
            'gross_pay' => ($summary['working_hours']['actual_hours'] * $hourlyRate) + $holidayPay + $overtimePay,
            'net_pay' => ($summary['working_hours']['actual_hours'] * $hourlyRate) + $holidayPay + $overtimePay - $absentDeduction,
            'summary' => $summary,
        ];
    }

    /**
     * Check if employee should be marked absent for a working day
     */
    public function shouldMarkAbsent(Employee $employee, Carbon $date): bool
    {
        // Don't mark absent on holidays or weekends
        if ($this->isHoliday($date) || $date->isWeekend()) {
            return false;
        }

        // Check if there's already an attendance record
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $date->format('Y-m-d'))
            ->first();

        // If no attendance record and it's a past working day, should be marked absent
        return ! $attendance && $date->isPast();
    }

    /**
     * Generate absence records for missed working days
     */
    public function generateAbsenceRecords(Employee $employee, Carbon $startDate, Carbon $endDate): int
    {
        $generated = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate) && $current->isPast()) {
            if ($this->shouldMarkAbsent($employee, $current)) {
                Attendance::create([
                    'employee_id' => $employee->id,
                    'date' => $current->format('Y-m-d'),
                    'status' => 'absent',
                    'total_hours' => 0,
                    'metadata' => [
                        'auto_generated' => true,
                        'reason' => 'absence_detection',
                    ],
                ]);
                $generated++;
            }
            $current->addDay();
        }

        return $generated;
    }
}
