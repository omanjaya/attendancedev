<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Models\Period;
use App\Models\ScheduleConflict;
use App\Models\Subject;
use App\Models\WeeklySchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Weekly Schedule Repository
 *
 * Handles all weekly schedule-related database operations
 */
class WeeklyScheduleRepository extends BaseRepository
{
    public function __construct(WeeklySchedule $weeklySchedule)
    {
        parent::__construct($weeklySchedule);
    }

    /**
     * Get schedule for a specific week
     */
    public function getWeeklySchedule(string $weekStart, ?string $employeeId = null, ?string $locationId = null): Collection
    {
        $cacheKey = $this->getCacheKey('weekly_schedule', [$weekStart, $employeeId, $locationId]);

        return cache()->remember($cacheKey, 1800, function () use ($weekStart, $employeeId, $locationId) {
            $query = $this->model
                ->where('week_start', $weekStart)
                ->with(['employee.user', 'employee.location', 'subject', 'period']);

            if ($employeeId) {
                $query->where('employee_id', $employeeId);
            }

            if ($locationId) {
                $query->whereHas('employee', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            }

            return $query->orderBy('day_of_week')
                ->orderBy('period_id')
                ->get();
        });
    }

    /**
     * Get employee schedule for a week
     */
    public function getEmployeeWeeklySchedule(string $employeeId, string $weekStart): Collection
    {
        $cacheKey = $this->getCacheKey('employee_weekly_schedule', [$employeeId, $weekStart]);

        return cache()->remember($cacheKey, 1800, function () use ($employeeId, $weekStart) {
            return $this->model
                ->where('employee_id', $employeeId)
                ->where('week_start', $weekStart)
                ->with(['subject', 'period'])
                ->orderBy('day_of_week')
                ->orderBy('period_id')
                ->get();
        });
    }

    /**
     * Get schedule conflicts for a week
     */
    public function getWeeklyScheduleConflicts(string $weekStart): Collection
    {
        $cacheKey = $this->getCacheKey('weekly_schedule_conflicts', [$weekStart]);

        return cache()->remember($cacheKey, 1800, function () use ($weekStart) {
            return ScheduleConflict::where('week_start', $weekStart)
                ->with(['employee', 'schedules'])
                ->orderBy('day_of_week')
                ->orderBy('period_id')
                ->get();
        });
    }

    /**
     * Create weekly schedule from template
     */
    public function createFromTemplate(string $weekStart, ?string $templateWeek = null): int
    {
        return DB::transaction(function () use ($weekStart, $templateWeek) {
            $templateWeek = $templateWeek ?? Carbon::parse($weekStart)->subWeek()->format('Y-m-d');

            $templateSchedules = $this->model
                ->where('week_start', $templateWeek)
                ->where('is_active', true)
                ->get();

            $created = 0;

            foreach ($templateSchedules as $template) {
                // Check if schedule already exists
                $exists = $this->model
                    ->where('week_start', $weekStart)
                    ->where('employee_id', $template->employee_id)
                    ->where('day_of_week', $template->day_of_week)
                    ->where('period_id', $template->period_id)
                    ->exists();

                if (! $exists) {
                    $this->model->create([
                        'week_start' => $weekStart,
                        'employee_id' => $template->employee_id,
                        'subject_id' => $template->subject_id,
                        'period_id' => $template->period_id,
                        'day_of_week' => $template->day_of_week,
                        'class_id' => $template->class_id,
                        'room' => $template->room,
                        'is_active' => true,
                        'created_by' => auth()->id(),
                    ]);

                    $created++;
                }
            }

            $this->clearCache();

            return $created;
        });
    }

    /**
     * Detect schedule conflicts
     */
    public function detectConflicts(string $weekStart): array
    {
        $cacheKey = $this->getCacheKey('detected_conflicts', [$weekStart]);

        return cache()->remember($cacheKey, 600, function () use ($weekStart) {
            $conflicts = [];

            // Employee double-booking conflicts
            $employeeConflicts = $this->model
                ->where('week_start', $weekStart)
                ->where('is_active', true)
                ->select('employee_id', 'day_of_week', 'period_id', DB::raw('COUNT(*) as conflict_count'))
                ->groupBy('employee_id', 'day_of_week', 'period_id')
                ->having('conflict_count', '>', 1)
                ->get();

            foreach ($employeeConflicts as $conflict) {
                $schedules = $this->model
                    ->where('week_start', $weekStart)
                    ->where('employee_id', $conflict->employee_id)
                    ->where('day_of_week', $conflict->day_of_week)
                    ->where('period_id', $conflict->period_id)
                    ->where('is_active', true)
                    ->with(['employee', 'subject', 'period'])
                    ->get();

                $conflicts[] = [
                    'type' => 'employee_double_booking',
                    'employee_id' => $conflict->employee_id,
                    'day_of_week' => $conflict->day_of_week,
                    'period_id' => $conflict->period_id,
                    'conflict_count' => $conflict->conflict_count,
                    'schedules' => $schedules,
                ];
            }

            // Room conflicts
            $roomConflicts = $this->model
                ->where('week_start', $weekStart)
                ->where('is_active', true)
                ->whereNotNull('room')
                ->select('room', 'day_of_week', 'period_id', DB::raw('COUNT(*) as conflict_count'))
                ->groupBy('room', 'day_of_week', 'period_id')
                ->having('conflict_count', '>', 1)
                ->get();

            foreach ($roomConflicts as $conflict) {
                $schedules = $this->model
                    ->where('week_start', $weekStart)
                    ->where('room', $conflict->room)
                    ->where('day_of_week', $conflict->day_of_week)
                    ->where('period_id', $conflict->period_id)
                    ->where('is_active', true)
                    ->with(['employee', 'subject', 'period'])
                    ->get();

                $conflicts[] = [
                    'type' => 'room_conflict',
                    'room' => $conflict->room,
                    'day_of_week' => $conflict->day_of_week,
                    'period_id' => $conflict->period_id,
                    'conflict_count' => $conflict->conflict_count,
                    'schedules' => $schedules,
                ];
            }

            return $conflicts;
        });
    }

    /**
     * Get schedule statistics for a week
     */
    public function getWeeklyScheduleStatistics(string $weekStart): array
    {
        $cacheKey = $this->getCacheKey('weekly_schedule_statistics', [$weekStart]);

        return cache()->remember($cacheKey, 1800, function () use ($weekStart) {
            $schedules = $this->model
                ->where('week_start', $weekStart)
                ->where('is_active', true)
                ->with(['employee', 'subject', 'period'])
                ->get();

            $totalSchedules = $schedules->count();
            $uniqueEmployees = $schedules->pluck('employee_id')->unique()->count();
            $uniqueSubjects = $schedules->pluck('subject_id')->unique()->count();

            // Hours per day
            $hoursByDay = [];
            for ($day = 1; $day <= 7; $day++) {
                $daySchedules = $schedules->where('day_of_week', $day);
                $hoursByDay[$day] = $daySchedules->count();
            }

            // Hours per employee
            $hoursByEmployee = $schedules->groupBy('employee_id')
                ->map(function ($schedules, $employeeId) {
                    return [
                        'employee_id' => $employeeId,
                        'employee_name' => $schedules->first()->employee->full_name,
                        'total_hours' => $schedules->count(),
                        'subjects' => $schedules->pluck('subject.name')->unique()->values()->toArray(),
                    ];
                })
                ->values()
                ->toArray();

            // Hours per subject
            $hoursBySubject = $schedules->groupBy('subject_id')
                ->map(function ($schedules, $subjectId) {
                    return [
                        'subject_id' => $subjectId,
                        'subject_name' => $schedules->first()->subject->name,
                        'total_hours' => $schedules->count(),
                        'teachers' => $schedules->pluck('employee.full_name')->unique()->values()->toArray(),
                    ];
                })
                ->values()
                ->toArray();

            // Conflicts
            $conflicts = $this->detectConflicts($weekStart);

            return [
                'week_start' => $weekStart,
                'total_schedules' => $totalSchedules,
                'unique_employees' => $uniqueEmployees,
                'unique_subjects' => $uniqueSubjects,
                'total_conflicts' => count($conflicts),
                'hours_by_day' => $hoursByDay,
                'hours_by_employee' => $hoursByEmployee,
                'hours_by_subject' => $hoursBySubject,
                'conflicts' => $conflicts,
                'average_hours_per_employee' => $uniqueEmployees > 0 ? round($totalSchedules / $uniqueEmployees, 1) : 0,
                'average_hours_per_subject' => $uniqueSubjects > 0 ? round($totalSchedules / $uniqueSubjects, 1) : 0,
            ];
        });
    }

    /**
     * Get schedule grid for display
     */
    public function getScheduleGrid(string $weekStart, ?string $locationId = null): array
    {
        $cacheKey = $this->getCacheKey('schedule_grid', [$weekStart, $locationId]);

        return cache()->remember($cacheKey, 1800, function () use ($weekStart, $locationId) {
            $query = $this->model
                ->where('week_start', $weekStart)
                ->where('is_active', true)
                ->with(['employee.user', 'subject', 'period']);

            if ($locationId) {
                $query->whereHas('employee', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                });
            }

            $schedules = $query->get();

            // Get all periods for the grid
            $periods = Period::where('is_active', true)
                ->orderBy('start_time')
                ->get();

            // Build grid structure
            $grid = [];

            foreach ($periods as $period) {
                $grid[$period->id] = [
                    'period' => $period,
                    'days' => [],
                ];

                for ($day = 1; $day <= 7; $day++) {
                    $daySchedules = $schedules->where('day_of_week', $day)
                        ->where('period_id', $period->id);

                    $grid[$period->id]['days'][$day] = $daySchedules->map(function ($schedule) {
                        return [
                            'id' => $schedule->id,
                            'employee_name' => $schedule->employee->full_name,
                            'employee_id' => $schedule->employee_id,
                            'subject_name' => $schedule->subject->name,
                            'subject_color' => $schedule->subject->color ?? '#3B82F6',
                            'class_id' => $schedule->class_id,
                            'room' => $schedule->room,
                        ];
                    })->toArray();
                }
            }

            return $grid;
        });
    }

    /**
     * Get available teachers for a time slot
     */
    public function getAvailableTeachers(string $weekStart, int $dayOfWeek, string $periodId): Collection
    {
        $cacheKey = $this->getCacheKey('available_teachers', [$weekStart, $dayOfWeek, $periodId]);

        return cache()->remember($cacheKey, 1800, function () use ($weekStart, $dayOfWeek, $periodId) {
            $busyEmployeeIds = $this->model
                ->where('week_start', $weekStart)
                ->where('day_of_week', $dayOfWeek)
                ->where('period_id', $periodId)
                ->where('is_active', true)
                ->pluck('employee_id');

            return Employee::where('is_active', true)
                ->where('employee_type', 'teacher')
                ->whereNotIn('id', $busyEmployeeIds)
                ->with(['user'])
                ->orderBy('full_name')
                ->get();
        });
    }

    /**
     * Assign schedule
     */
    public function assignSchedule(array $data): WeeklySchedule
    {
        return DB::transaction(function () use ($data) {
            // Check for conflicts
            $existingSchedule = $this->model
                ->where('week_start', $data['week_start'])
                ->where('employee_id', $data['employee_id'])
                ->where('day_of_week', $data['day_of_week'])
                ->where('period_id', $data['period_id'])
                ->where('is_active', true)
                ->first();

            if ($existingSchedule) {
                throw new \Exception('Employee already has a schedule for this time slot');
            }

            // Check room conflict if room is specified
            if (! empty($data['room'])) {
                $roomConflict = $this->model
                    ->where('week_start', $data['week_start'])
                    ->where('room', $data['room'])
                    ->where('day_of_week', $data['day_of_week'])
                    ->where('period_id', $data['period_id'])
                    ->where('is_active', true)
                    ->first();

                if ($roomConflict) {
                    throw new \Exception('Room is already occupied for this time slot');
                }
            }

            $schedule = $this->create(array_merge($data, [
                'is_active' => true,
                'created_by' => auth()->id(),
            ]));

            $this->clearCache();

            return $schedule;
        });
    }

    /**
     * Update schedule
     */
    public function updateSchedule(string $scheduleId, array $data): WeeklySchedule
    {
        return DB::transaction(function () use ($scheduleId, $data) {
            $schedule = $this->findOrFail($scheduleId);

            // Check for conflicts if key fields are being updated
            if (isset($data['employee_id']) || isset($data['day_of_week']) || isset($data['period_id'])) {
                $employeeId = $data['employee_id'] ?? $schedule->employee_id;
                $dayOfWeek = $data['day_of_week'] ?? $schedule->day_of_week;
                $periodId = $data['period_id'] ?? $schedule->period_id;

                $existingSchedule = $this->model
                    ->where('week_start', $schedule->week_start)
                    ->where('employee_id', $employeeId)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('period_id', $periodId)
                    ->where('is_active', true)
                    ->where('id', '!=', $scheduleId)
                    ->first();

                if ($existingSchedule) {
                    throw new \Exception('Employee already has a schedule for this time slot');
                }
            }

            // Check room conflict if room is being updated
            if (isset($data['room']) && ! empty($data['room'])) {
                $roomConflict = $this->model
                    ->where('week_start', $schedule->week_start)
                    ->where('room', $data['room'])
                    ->where('day_of_week', $data['day_of_week'] ?? $schedule->day_of_week)
                    ->where('period_id', $data['period_id'] ?? $schedule->period_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $scheduleId)
                    ->first();

                if ($roomConflict) {
                    throw new \Exception('Room is already occupied for this time slot');
                }
            }

            $schedule->update(array_merge($data, [
                'updated_by' => auth()->id(),
            ]));

            $this->clearCache();

            return $schedule->fresh();
        });
    }

    /**
     * Remove schedule
     */
    public function removeSchedule(string $scheduleId): bool
    {
        return DB::transaction(function () use ($scheduleId) {
            $schedule = $this->findOrFail($scheduleId);

            $result = $schedule->update([
                'is_active' => false,
                'updated_by' => auth()->id(),
            ]);

            $this->clearCache();

            return $result;
        });
    }

    /**
     * Copy schedule to another week
     */
    public function copyScheduleToWeek(string $sourceWeek, string $targetWeek, ?array $employeeIds = null): int
    {
        return DB::transaction(function () use ($sourceWeek, $targetWeek, $employeeIds) {
            $query = $this->model
                ->where('week_start', $sourceWeek)
                ->where('is_active', true);

            if ($employeeIds) {
                $query->whereIn('employee_id', $employeeIds);
            }

            $sourceSchedules = $query->get();
            $copied = 0;

            foreach ($sourceSchedules as $sourceSchedule) {
                // Check if target schedule already exists
                $exists = $this->model
                    ->where('week_start', $targetWeek)
                    ->where('employee_id', $sourceSchedule->employee_id)
                    ->where('day_of_week', $sourceSchedule->day_of_week)
                    ->where('period_id', $sourceSchedule->period_id)
                    ->where('is_active', true)
                    ->exists();

                if (! $exists) {
                    $this->model->create([
                        'week_start' => $targetWeek,
                        'employee_id' => $sourceSchedule->employee_id,
                        'subject_id' => $sourceSchedule->subject_id,
                        'period_id' => $sourceSchedule->period_id,
                        'day_of_week' => $sourceSchedule->day_of_week,
                        'class_id' => $sourceSchedule->class_id,
                        'room' => $sourceSchedule->room,
                        'is_active' => true,
                        'created_by' => auth()->id(),
                    ]);

                    $copied++;
                }
            }

            $this->clearCache();

            return $copied;
        });
    }

    /**
     * Get schedule coverage report
     */
    public function getScheduleCoverageReport(string $weekStart): array
    {
        $cacheKey = $this->getCacheKey('schedule_coverage_report', [$weekStart]);

        return cache()->remember($cacheKey, 1800, function () use ($weekStart) {
            $schedules = $this->model
                ->where('week_start', $weekStart)
                ->where('is_active', true)
                ->with(['employee', 'subject', 'period'])
                ->get();

            $totalPeriods = Period::where('is_active', true)->count();
            $totalDays = 7;
            $totalSlots = $totalPeriods * $totalDays;

            $activeEmployees = Employee::where('is_active', true)
                ->where('employee_type', 'teacher')
                ->count();

            $scheduledSlots = $schedules->count();
            $coveragePercentage = $totalSlots > 0 ? round(($scheduledSlots / $totalSlots) * 100, 1) : 0;

            // Employee utilization
            $employeeUtilization = $schedules->groupBy('employee_id')
                ->map(function ($schedules, $employeeId) use ($totalSlots) {
                    $employee = $schedules->first()->employee;
                    $hoursScheduled = $schedules->count();

                    return [
                        'employee_id' => $employeeId,
                        'employee_name' => $employee->full_name,
                        'hours_scheduled' => $hoursScheduled,
                        'utilization_percentage' => round(($hoursScheduled / $totalSlots) * 100, 1),
                    ];
                })
                ->values()
                ->toArray();

            // Subject coverage
            $subjectCoverage = $schedules->groupBy('subject_id')
                ->map(function ($schedules, $subjectId) {
                    $subject = $schedules->first()->subject;

                    return [
                        'subject_id' => $subjectId,
                        'subject_name' => $subject->name,
                        'hours_scheduled' => $schedules->count(),
                        'teachers' => $schedules->pluck('employee.full_name')->unique()->values()->toArray(),
                    ];
                })
                ->values()
                ->toArray();

            return [
                'week_start' => $weekStart,
                'total_slots' => $totalSlots,
                'scheduled_slots' => $scheduledSlots,
                'coverage_percentage' => $coveragePercentage,
                'active_employees' => $activeEmployees,
                'employees_with_schedules' => count($employeeUtilization),
                'employee_utilization' => $employeeUtilization,
                'subject_coverage' => $subjectCoverage,
                'average_hours_per_employee' => count($employeeUtilization) > 0 ? round($scheduledSlots / count($employeeUtilization), 1) : 0,
            ];
        });
    }

    /**
     * Get schedule changes history
     */
    public function getScheduleChangesHistory(string $weekStart, ?int $limit = 50): Collection
    {
        $cacheKey = $this->getCacheKey('schedule_changes_history', [$weekStart, $limit]);

        return cache()->remember($cacheKey, 1800, function () {
            // This would query a schedule_changes table in a full implementation
            // For now, return empty collection
            return collect([]);
        });
    }

    /**
     * Lock schedule for a week
     */
    public function lockSchedule(string $weekStart, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($weekStart, $reason) {
            // Check if already locked
            $existingLock = DB::table('schedule_locks')
                ->where('week_start', $weekStart)
                ->where('is_active', true)
                ->first();

            if ($existingLock) {
                throw new \Exception('Schedule is already locked for this week');
            }

            // Create lock record
            DB::table('schedule_locks')->insert([
                'week_start' => $weekStart,
                'locked_by' => auth()->id(),
                'locked_at' => now(),
                'reason' => $reason,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->clearCache();

            return true;
        });
    }

    /**
     * Unlock schedule for a week
     */
    public function unlockSchedule(string $weekStart): bool
    {
        return DB::transaction(function () use ($weekStart) {
            $updated = DB::table('schedule_locks')
                ->where('week_start', $weekStart)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'unlocked_by' => auth()->id(),
                    'unlocked_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->clearCache();

            return $updated > 0;
        });
    }

    /**
     * Check if schedule is locked
     */
    public function isScheduleLocked(string $weekStart): bool
    {
        $cacheKey = $this->getCacheKey('schedule_locked', [$weekStart]);

        return cache()->remember($cacheKey, 1800, function () use ($weekStart) {
            return DB::table('schedule_locks')
                ->where('week_start', $weekStart)
                ->where('is_active', true)
                ->exists();
        });
    }
}
