<?php

namespace App\Services\Schedule;

use App\Models\WeeklySchedule;
use Illuminate\Support\Facades\DB;

class ScheduleService
{
    public function getSchedules(array $filters = [], int $perPage = 15)
    {
        $query = WeeklySchedule::query()
            ->with(['employee:id,employee_id,full_name', 'academicClass:id,name', 'subject:id,name', 'timeSlot']);

        if (isset($filters['class_id'])) {
            $query->where('academic_class_id', $filters['class_id']);
        }
        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (isset($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }
        if (isset($filters['day_of_week'])) {
            $query->where('day_of_week', $filters['day_of_week']);
        }
        if (isset($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->paginate($perPage);
    }

    public function getSchedulesByClass(string $classId)
    {
        return WeeklySchedule::with(['employee', 'subject', 'timeSlot'])
            ->where('academic_class_id', $classId)
            ->orderBy('day_of_week')
            ->get()
            ->sortBy(fn($s) => $s->timeSlot->start_time)
            ->values();
    }

    public function createSchedule(array $data): WeeklySchedule
    {
        return DB::transaction(function () use ($data) {
            return WeeklySchedule::create($data);
        });
    }

    public function updateSchedule(WeeklySchedule $schedule, array $data): WeeklySchedule
    {
        $schedule->update($data);
        return $schedule->fresh();
    }

    public function deleteSchedule(WeeklySchedule $schedule): bool
    {
        return $schedule->delete();
    }

    public function lockSchedule(WeeklySchedule $schedule): WeeklySchedule
    {
        $schedule->update(['is_locked' => true]);
        return $schedule->fresh();
    }

    public function unlockSchedule(WeeklySchedule $schedule): WeeklySchedule
    {
        $schedule->update(['is_locked' => false]);
        return $schedule->fresh();
    }
}
