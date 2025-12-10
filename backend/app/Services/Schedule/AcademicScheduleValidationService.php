<?php

namespace App\Services\Schedule;

use App\Models\ScheduleConflict;
use App\Models\Subject;
use App\Models\WeeklySchedule;

class AcademicScheduleValidationService
{
    /**
     * Validate schedule creation
     */
    public function validateScheduleCreation($scheduleData)
    {
        $errors = [];
        $warnings = [];

        // Check for class double booking
        $classConflict = WeeklySchedule::where('academic_class_id', $scheduleData['academic_class_id'])
            ->where('day_of_week', $scheduleData['day_of_week'])
            ->where('time_slot_id', $scheduleData['time_slot_id'])
            ->where('is_active', true)
            ->exists();

        if ($classConflict) {
            $errors[] = 'Kelas sudah memiliki jadwal pada waktu yang sama';
        }

        // Check for teacher double booking
        $teacherConflict = WeeklySchedule::where('employee_id', $scheduleData['employee_id'])
            ->where('day_of_week', $scheduleData['day_of_week'])
            ->where('time_slot_id', $scheduleData['time_slot_id'])
            ->where('is_active', true)
            ->exists();

        if ($teacherConflict) {
            $errors[] = 'Guru sudah mengajar pada waktu yang sama';
        }

        // Check subject frequency
        $subject = Subject::find($scheduleData['subject_id']);
        if ($subject) {
            $validation = $subject->validateScheduleFrequency(
                $scheduleData['academic_class_id'],
                $scheduleData['day_of_week'],
            );

            if (! $validation['weekly_valid']) {
                $errors[] = "Mata pelajaran {$subject->name} melebihi batas maksimal {$subject->max_meetings_per_week} pertemuan per minggu";
            }

            if (! $validation['daily_valid']) {
                $warnings[] = "Mata pelajaran {$subject->name} sudah ada di hari yang sama";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate schedule update
     */
    public function validateScheduleUpdate($schedule, $newData)
    {
        // Similar validation as creation but excluding current schedule
        $errors = [];
        $warnings = [];

        // Only validate if critical fields changed
        $criticalFields = ['academic_class_id', 'employee_id', 'time_slot_id', 'day_of_week'];
        $hasChanges = false;

        foreach ($criticalFields as $field) {
            if (isset($newData[$field]) && $newData[$field] != $schedule->$field) {
                $hasChanges = true;
                break;
            }
        }

        if (! $hasChanges) {
            return ['valid' => true, 'errors' => [], 'warnings' => []];
        }

        // Use current values if not provided in update
        $checkData = array_merge($schedule->toArray(), $newData);

        // Check for class double booking
        $classConflict = WeeklySchedule::where('academic_class_id', $checkData['academic_class_id'])
            ->where('day_of_week', $checkData['day_of_week'])
            ->where('time_slot_id', $checkData['time_slot_id'])
            ->where('is_active', true)
            ->where('id', '!=', $schedule->id)
            ->exists();

        if ($classConflict) {
            $errors[] = 'Kelas sudah memiliki jadwal pada waktu yang sama';
        }

        // Check for teacher double booking
        $teacherConflict = WeeklySchedule::where('employee_id', $checkData['employee_id'])
            ->where('day_of_week', $checkData['day_of_week'])
            ->where('time_slot_id', $checkData['time_slot_id'])
            ->where('is_active', true)
            ->where('id', '!=', $schedule->id)
            ->exists();

        if ($teacherConflict) {
            $errors[] = 'Guru sudah mengajar pada waktu yang sama';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Store conflicts
     */
    public function storeConflicts($schedule, $conflicts)
    {
        foreach ($conflicts as $conflict) {
            if ($conflict['conflicting_schedule']) {
                ScheduleConflict::create([
                    'schedule_id_1' => $schedule->id,
                    'schedule_id_2' => $conflict['conflicting_schedule']->id,
                    'conflict_type' => $conflict['type'],
                    'severity' => $conflict['severity'],
                    'description' => $conflict['description'],
                    'detected_at' => now(),
                ]);
            }
        }
    }

    /**
     * Clear conflicts for a schedule
     */
    public function clearConflicts($schedule)
    {
        ScheduleConflict::where('schedule_id_1', $schedule->id)
            ->orWhere('schedule_id_2', $schedule->id)
            ->delete();
    }
}
