<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\MonthlySchedule;
use App\Models\EmployeeMonthlySchedule;
use App\Models\TeachingSchedule;
use App\Models\NationalHoliday;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ScheduleManagementService
{
    /**
     * Create a monthly schedule template
     */
    public function createMonthlySchedule(array $data): MonthlySchedule
    {
        DB::beginTransaction();
        
        try {
            // Calculate default times from working hours per day for backward compatibility
            $defaultTimes = $this->calculateDefaultTimes($data['working_hours_per_day'] ?? []);
            
            // Check if new columns exist before using them
            $scheduleData = [
                'name' => $data['name'],
                'month' => $data['month'],
                'year' => $data['year'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'default_start_time' => $defaultTimes['start_time'],
                'default_end_time' => $defaultTimes['end_time'],
                'location_id' => $data['location_id'],
                'description' => $data['description'] ?? null,
                'created_by' => auth()->id(),
            ];
            
            // Add metadata with working hours info
            $metadata = $data['metadata'] ?? [];
            $metadata['working_hours_per_day'] = $data['working_hours_per_day'] ?? null;
            $metadata['working_hours_template'] = $data['working_hours_template'] ?? null;
            $scheduleData['metadata'] = $metadata;
            
            // Only add new columns if they exist in the table
            if (Schema::hasColumn('monthly_schedules', 'working_hours_per_day')) {
                $scheduleData['working_hours_per_day'] = $data['working_hours_per_day'] ?? null;
            }
            if (Schema::hasColumn('monthly_schedules', 'working_hours_template')) {
                $scheduleData['working_hours_template'] = $data['working_hours_template'] ?? null;
            }
            
            $schedule = MonthlySchedule::create($scheduleData);
            
            // Generate daily schedule entries for validation
            $schedule->generateDailySchedules();
            
            DB::commit();
            
            // Clear schedule cache
            $this->clearScheduleCache($schedule->location_id, $schedule->month, $schedule->year);
            
            return $schedule;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate default start and end times from working hours per day
     */
    private function calculateDefaultTimes(array $workingHoursPerDay): array
    {
        $earliestStart = '08:00';
        $latestEnd = '16:00';
        
        foreach ($workingHoursPerDay as $day => $hours) {
            if ($hours && is_array($hours)) {
                if (isset($hours['start']) && $hours['start'] < $earliestStart) {
                    $earliestStart = $hours['start'];
                }
                if (isset($hours['end']) && $hours['end'] > $latestEnd) {
                    $latestEnd = $hours['end'];
                }
            }
        }
        
        return [
            'start_time' => $earliestStart,
            'end_time' => $latestEnd
        ];
    }

    /**
     * Bulk assign employees to a monthly schedule
     */
    public function bulkAssignEmployees(MonthlySchedule $schedule, array $employeeIds): array
    {
        DB::beginTransaction();
        
        try {
            $results = $schedule->bulkAssignEmployees($employeeIds);
            
            // Apply holiday overrides to newly created schedules
            $schedule->applyHolidayOverrides();
            
            // Apply teaching schedule overrides for Guru Honorer
            $this->applyTeachingScheduleOverrides($schedule, $employeeIds);
            
            DB::commit();
            
            // Clear cache
            $this->clearScheduleCache($schedule->location_id, $schedule->month, $schedule->year);
            
            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Apply teaching schedule overrides for assigned employees
     */
    private function applyTeachingScheduleOverrides(MonthlySchedule $schedule, array $employeeIds): void
    {
        $guruHonorer = Employee::whereIn('id', $employeeIds)
            ->where('employee_type', 'guru_honorer')
            ->get();
        
        foreach ($guruHonorer as $employee) {
            $teachingSchedules = $employee->teachingSchedules()
                ->active()
                ->where('override_attendance', true)
                ->get();
            
            foreach ($teachingSchedules as $teachingSchedule) {
                // Apply to all matching days in the schedule period
                $current = $schedule->start_date->copy();
                
                while ($current->lte($schedule->end_date)) {
                    if (strtolower($current->format('l')) === $teachingSchedule->day_of_week) {
                        $employeeSchedule = EmployeeMonthlySchedule::where('employee_id', $employee->id)
                            ->where('effective_date', $current)
                            ->where('monthly_schedule_id', $schedule->id)
                            ->first();
                        
                        if ($employeeSchedule) {
                            $employeeSchedule->applyTeachingScheduleOverride();
                        }
                    }
                    
                    $current->addDay();
                }
            }
        }
    }

    /**
     * Create or update a teaching schedule
     */
    public function createTeachingSchedule(array $data): TeachingSchedule
    {
        DB::beginTransaction();
        
        try {
            // Check for conflicts
            $conflicts = $this->checkTeachingScheduleConflicts($data);
            
            if (!empty($conflicts)) {
                throw new \Exception('Teaching schedule conflicts detected: ' . implode(', ', $conflicts));
            }
            
            $teachingSchedule = TeachingSchedule::create([
                'teacher_id' => $data['teacher_id'],
                'subject_id' => $data['subject_id'],
                'day_of_week' => $data['day_of_week'],
                'teaching_start_time' => $data['teaching_start_time'],
                'teaching_end_time' => $data['teaching_end_time'],
                'effective_from' => $data['effective_from'],
                'effective_until' => $data['effective_until'] ?? null,
                'class_name' => $data['class_name'] ?? null,
                'room' => $data['room'] ?? null,
                'student_count' => $data['student_count'] ?? null,
                'override_attendance' => $data['override_attendance'] ?? true,
                'strict_timing' => $data['strict_timing'] ?? true,
                'late_threshold_minutes' => $data['late_threshold_minutes'] ?? 15,
                'metadata' => $data['metadata'] ?? [],
                'created_by' => auth()->id(),
            ]);
            
            DB::commit();
            
            // Clear teacher schedule cache
            $this->clearTeacherScheduleCache($data['teacher_id']);
            
            return $teachingSchedule;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check for teaching schedule conflicts
     */
    private function checkTeachingScheduleConflicts(array $data): array
    {
        $conflicts = [];
        
        // Check for time conflicts with same teacher
        $existingSchedules = TeachingSchedule::where('teacher_id', $data['teacher_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->active()
            ->where(function($query) use ($data) {
                $query->where('effective_from', '<=', $data['effective_until'] ?? '2030-12-31')
                      ->where(function($q) use ($data) {
                          $q->whereNull('effective_until')
                            ->orWhere('effective_until', '>=', $data['effective_from']);
                      });
            })
            ->where(function($query) use ($data) {
                $query->whereBetween('teaching_start_time', [$data['teaching_start_time'], $data['teaching_end_time']])
                      ->orWhereBetween('teaching_end_time', [$data['teaching_start_time'], $data['teaching_end_time']])
                      ->orWhere(function($q) use ($data) {
                          $q->where('teaching_start_time', '<=', $data['teaching_start_time'])
                            ->where('teaching_end_time', '>=', $data['teaching_end_time']);
                      });
            });
        
        if (isset($data['id'])) {
            $existingSchedules->where('id', '!=', $data['id']);
        }
        
        if ($existingSchedules->exists()) {
            $conflicts[] = 'Time conflict with existing teaching schedule';
        }
        
        return $conflicts;
    }

    /**
     * Create or update a national holiday
     */
    public function createNationalHoliday(array $data): NationalHoliday
    {
        DB::beginTransaction();
        
        try {
            $holiday = NationalHoliday::create([
                'name' => $data['name'],
                'holiday_date' => $data['holiday_date'],
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'is_recurring' => $data['is_recurring'] ?? false,
                'recurrence_config' => $data['recurrence_config'] ?? [],
                'force_override' => $data['force_override'] ?? true,
                'paid_leave' => $data['paid_leave'] ?? true,
                'metadata' => $data['metadata'] ?? [],
                'created_by' => auth()->id(),
            ]);
            
            // Generate recurring holidays if specified
            if ($holiday->is_recurring && !empty($holiday->recurrence_config)) {
                $holiday->generateRecurringHolidays(5); // Generate for next 5 years
            }
            
            DB::commit();
            
            // Clear holiday cache
            $this->clearHolidayCache($holiday->holiday_date->year);
            
            return $holiday;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get effective schedule for an employee on a specific date
     */
    public function getEmployeeEffectiveSchedule(string $employeeId, Carbon $date): array
    {
        $cacheKey = "employee_schedule:{$employeeId}:{$date->toDateString()}";
        
        return Cache::remember($cacheKey, 900, function () use ($employeeId, $date) { // 15 minutes cache
            $employee = Employee::findOrFail($employeeId);
            return $employee->getEffectiveScheduleForDate($date);
        });
    }

    /**
     * Calculate attendance status based on schedule
     */
    public function calculateAttendanceStatus(string $employeeId, Carbon $date, ?Carbon $checkInTime = null): array
    {
        $schedule = $this->getEmployeeEffectiveSchedule($employeeId, $date);
        
        if ($schedule['schedule_type'] === 'holiday' || $schedule['schedule_type'] === 'none') {
            return [
                'status' => $schedule['schedule_type'],
                'expected_start' => null,
                'expected_end' => null,
                'is_late' => false,
                'late_minutes' => 0,
                'working_hours' => 0
            ];
        }
        
        $expectedStart = Carbon::createFromFormat('H:i', $schedule['start_time']->format('H:i'))->setDateFrom($date);
        $expectedEnd = Carbon::createFromFormat('H:i', $schedule['end_time']->format('H:i'))->setDateFrom($date);
        
        $result = [
            'status' => 'scheduled',
            'expected_start' => $expectedStart,
            'expected_end' => $expectedEnd,
            'working_hours' => $schedule['working_hours'],
            'schedule_type' => $schedule['schedule_type']
        ];
        
        if ($checkInTime) {
            $isLate = $checkInTime->gt($expectedStart);
            $lateMinutes = $isLate ? $expectedStart->diffInMinutes($checkInTime) : 0;
            
            $result['status'] = $isLate ? 'late' : 'present';
            $result['is_late'] = $isLate;
            $result['late_minutes'] = $lateMinutes;
            $result['check_in_time'] = $checkInTime;
        }
        
        return $result;
    }

    /**
     * Get monthly schedule overview
     */
    public function getMonthlyScheduleOverview(int $month, int $year, ?string $locationId = null): array
    {
        $cacheKey = "monthly_overview:{$month}:{$year}:" . ($locationId ?? 'all');
        
        return Cache::remember($cacheKey, 1800, function () use ($month, $year, $locationId) { // 30 minutes cache
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            
            // Get monthly schedules
            $monthlySchedules = MonthlySchedule::active()
                ->forMonth($month, $year)
                ->when($locationId, fn($q) => $q->where('location_id', $locationId))
                ->with(['location', 'employeeSchedules.employee'])
                ->get();
            
            // Get holidays for the month
            $holidays = NationalHoliday::forDateRange($startDate, $endDate)
                ->forLocation($locationId)
                ->active()
                ->get();
            
            // Get employee assignment statistics
            $totalEmployees = Employee::active()->count();
            $assignedEmployees = EmployeeMonthlySchedule::whereBetween('effective_date', [$startDate, $endDate])
                ->when($locationId, fn($q) => $q->where('location_id', $locationId))
                ->distinct('employee_id')
                ->count();
            
            return [
                'month' => $month,
                'year' => $year,
                'month_name' => $startDate->format('F'),
                'schedules' => $monthlySchedules->map(function($schedule) {
                    return [
                        'id' => $schedule->id,
                        'name' => $schedule->name,
                        'full_name' => $schedule->full_name,
                        'location' => $schedule->location->name,
                        'assigned_employees' => $schedule->getAssignedEmployeesCount(),
                        'working_hours' => $schedule->working_hours,
                        'duration_days' => $schedule->duration_days
                    ];
                }),
                'holidays' => $holidays->map(function($holiday) {
                    return [
                        'id' => $holiday->id,
                        'name' => $holiday->name,
                        'date' => $holiday->holiday_date,
                        'formatted_date' => $holiday->formatted_date,
                        'type' => $holiday->type_label,
                        'scope' => $holiday->scope
                    ];
                }),
                'statistics' => [
                    'total_employees' => $totalEmployees,
                    'assigned_employees' => $assignedEmployees,
                    'assignment_percentage' => $totalEmployees > 0 ? round(($assignedEmployees / $totalEmployees) * 100, 1) : 0,
                    'total_holidays' => $holidays->count(),
                    'working_days' => $this->calculateWorkingDays($startDate, $endDate, $holidays)
                ]
            ];
        });
    }

    /**
     * Calculate working days in a period excluding holidays and weekends
     */
    private function calculateWorkingDays(Carbon $startDate, Carbon $endDate, Collection $holidays): int
    {
        $workingDays = 0;
        $current = $startDate->copy();
        $holidayDates = $holidays->pluck('holiday_date')->map(fn($date) => $date->toDateString())->toArray();
        
        while ($current->lte($endDate)) {
            if (!$current->isWeekend() && !in_array($current->toDateString(), $holidayDates)) {
                $workingDays++;
            }
            $current->addDay();
        }
        
        return $workingDays;
    }

    /**
     * Get teacher workload summary
     */
    public function getTeacherWorkloadSummary(?string $locationId = null): array
    {
        $cacheKey = "teacher_workload:" . ($locationId ?? 'all');
        
        return Cache::remember($cacheKey, 1800, function () use ($locationId) { // 30 minutes cache
            $teachers = Employee::where('employee_type', 'like', 'guru_%')
                ->where('can_teach', true)
                ->active()
                ->when($locationId, fn($q) => $q->where('default_location_id', $locationId))
                ->with(['teachingSchedules' => function($query) {
                    $query->active()
                          ->where('effective_from', '<=', now())
                          ->where(function($q) {
                              $q->whereNull('effective_until')
                                ->orWhere('effective_until', '>=', now());
                          });
                }])
                ->get();
            
            return $teachers->map(function($teacher) {
                $workload = $teacher->getTeachingWorkload();
                
                return [
                    'teacher_id' => $teacher->id,
                    'name' => $teacher->full_name,
                    'employee_type' => $teacher->employee_type,
                    'total_hours' => $workload['total_hours'],
                    'workload_percentage' => $workload['percentage'],
                    'is_overloaded' => $workload['is_overloaded'],
                    'subjects' => $workload['subjects'],
                    'can_substitute' => $teacher->can_substitute
                ];
            })->sortByDesc('workload_percentage')->values();
        });
    }

    /**
     * Clear various schedule caches
     */
    private function clearScheduleCache(?string $locationId, int $month, int $year): void
    {
        $patterns = [
            "monthly_overview:{$month}:{$year}:" . ($locationId ?? 'all'),
            "teacher_workload:" . ($locationId ?? 'all'),
        ];
        
        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    private function clearTeacherScheduleCache(string $teacherId): void
    {
        // In a real implementation, you might want to use cache tags
        // For now, we'll clear the teacher workload cache
        Cache::forget("teacher_workload:all");
    }

    private function clearHolidayCache(int $year): void
    {
        // Clear holiday-related caches
        for ($month = 1; $month <= 12; $month++) {
            Cache::forget("monthly_overview:{$month}:{$year}:all");
        }
    }
}