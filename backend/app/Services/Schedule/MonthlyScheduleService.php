<?php

namespace App\Services\Schedule;

use App\Models\MonthlySchedule;
use App\Models\EmployeeMonthlySchedule;
use Illuminate\Support\Facades\DB;

class MonthlyScheduleService
{
    public function getMonthlySchedules(array $filters = [])
    {
        $query = MonthlySchedule::query()->with(['creator']);

        if (isset($filters['year'])) {
            $query->where('year', $filters['year']);
        }
        if (isset($filters['month'])) {
            $query->where('month', $filters['month']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('year', 'desc')->orderBy('month', 'desc')->get();
    }

    public function createMonthlySchedule(array $data): MonthlySchedule
    {
        return DB::transaction(function () use ($data) {
            $schedule = MonthlySchedule::create([
                'name' => $data['name'],
                'year' => $data['year'],
                'month' => $data['month'],
                'is_active' => $data['is_active'] ?? false,
                'created_by' => $data['created_by'],
            ]);

            if (isset($data['employees']) && is_array($data['employees'])) {
                foreach ($data['employees'] as $empData) {
                    EmployeeMonthlySchedule::create([
                        'monthly_schedule_id' => $schedule->id,
                        'employee_id' => $empData['employee_id'],
                        'schedule_data' => $empData['schedule_data'] ?? [],
                    ]);
                }
            }

            return $schedule;
        });
    }

    public function updateMonthlySchedule(MonthlySchedule $schedule, array $data): MonthlySchedule
    {
        DB::transaction(function () use ($schedule, $data) {
            $schedule->update(array_filter([
                'name' => $data['name'] ?? null,
                'year' => $data['year'] ?? null,
                'month' => $data['month'] ?? null,
                'is_active' => $data['is_active'] ?? null,
            ]));

            if (isset($data['employees'])) {
                EmployeeMonthlySchedule::where('monthly_schedule_id', $schedule->id)->delete();
                
                foreach ($data['employees'] as $empData) {
                    EmployeeMonthlySchedule::create([
                        'monthly_schedule_id' => $schedule->id,
                        'employee_id' => $empData['employee_id'],
                        'schedule_data' => $empData['schedule_data'] ?? [],
                    ]);
                }
            }
        });

        return $schedule->fresh();
    }

    public function publishMonthlySchedule(MonthlySchedule $schedule): MonthlySchedule
    {
        DB::transaction(function () use ($schedule) {
            MonthlySchedule::where('year', $schedule->year)
                ->where('month', $schedule->month)
                ->where('id', '!=', $schedule->id)
                ->update(['is_active' => false]);

            $schedule->update(['is_active' => true]);
        });

        return $schedule->fresh();
    }

    public function deleteMonthlySchedule(MonthlySchedule $schedule): bool
    {
        return DB::transaction(function () use ($schedule) {
            EmployeeMonthlySchedule::where('monthly_schedule_id', $schedule->id)->delete();
            return $schedule->delete();
        });
    }

    /**
     * Generate working days for a month based on day pattern
     *
     * @param int $year
     * @param int $month
     * @param array $workingDayPattern Array of day names (e.g., ['monday', 'tuesday', ...])
     * @return array
     */
    public function generateWorkingDays(int $year, int $month, array $workingDayPattern): array
    {
        // Map day names to numbers (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
        $dayMap = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];

        // Convert pattern to numbers
        $workingDayNumbers = array_map(function($day) use ($dayMap) {
            return $dayMap[strtolower($day)] ?? null;
        }, $workingDayPattern);

        $workingDayNumbers = array_filter($workingDayNumbers, fn($v) => $v !== null);

        // Get holidays for this month (optional - can be implemented later)
        $holidays = []; // TODO: Fetch from holidays table if exists

        // Generate all days in the month
        $firstDay = \Carbon\Carbon::createFromDate($year, $month, 1);
        $lastDay = $firstDay->copy()->endOfMonth();

        $workingDays = [];
        $totalDays = $lastDay->day;
        $holidayDates = [];

        for ($day = 1; $day <= $totalDays; $day++) {
            $date = \Carbon\Carbon::createFromDate($year, $month, $day);
            $dayOfWeek = $date->dayOfWeek; // 0 = Sunday, 1 = Monday, ...

            $dateString = $date->format('Y-m-d');

            // Check if this day matches the working day pattern
            if (in_array($dayOfWeek, $workingDayNumbers)) {
                // Check if it's a holiday
                if (!in_array($dateString, $holidays)) {
                    $workingDays[] = $dateString;
                } else {
                    $holidayDates[] = $dateString;
                }
            }
        }

        return [
            'working_days' => $workingDays,
            'total_working_days' => count($workingDays),
            'total_holidays' => count($holidayDates),
            'total_days' => $totalDays,
            'year' => $year,
            'month' => $month,
        ];
    }
}
