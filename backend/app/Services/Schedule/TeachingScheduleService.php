<?php

namespace App\Services\Schedule;

use App\Models\TeachingSchedule;
use Illuminate\Support\Facades\DB;

class TeachingScheduleService
{
    public function getTeachingSchedules(array $filters = [])
    {
        $query = TeachingSchedule::query()
            ->with(['employee', 'subject', 'academicClass']);

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (isset($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }
        if (isset($filters['class_id'])) {
            $query->where('academic_class_id', $filters['class_id']);
        }
        if (isset($filters['date'])) {
            $query->whereDate('date', $filters['date']);
        }
        if (isset($filters['day_of_week'])) {
            $query->where('day_of_week', $filters['day_of_week']);
        }

        return $query->orderBy('date')->orderBy('start_time')->get();
    }

    public function createTeachingSchedule(array $data): TeachingSchedule
    {
        return DB::transaction(function () use ($data) {
            return TeachingSchedule::create($data);
        });
    }

    public function updateTeachingSchedule(TeachingSchedule $schedule, array $data): TeachingSchedule
    {
        $schedule->update($data);
        return $schedule->fresh();
    }

    public function deleteTeachingSchedule(TeachingSchedule $schedule): bool
    {
        return $schedule->delete();
    }

    public function bulkImportTeachingSchedules(array $schedules): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        DB::transaction(function () use ($schedules, &$results) {
            foreach ($schedules as $index => $scheduleData) {
                try {
                    TeachingSchedule::create($scheduleData);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Row {$index}: " . $e->getMessage();
                }
            }
        });

        return $results;
    }
}
