<?php

namespace App\Services\Schedule;

use App\Models\WeeklySchedule;
use App\Models\TeachingSchedule;
use App\Models\TimeSlot;
use App\Models\Subject;
use App\Models\AcademicClass;
use App\Models\Employee;

class ScheduleHelperService
{
    public function checkConflicts(array $scheduleData): array
    {
        $conflicts = [];

        // Check teacher conflict
        $teacherConflict = WeeklySchedule::where('employee_id', $scheduleData['employee_id'])
            ->where('day_of_week', $scheduleData['day_of_week'])
            ->where('time_slot_id', $scheduleData['time_slot_id'])
            ->when(isset($scheduleData['id']), fn($q) => $q->where('id', '!=', $scheduleData['id']))
            ->first();

        if ($teacherConflict) {
            $conflicts[] = [
                'type' => 'teacher',
                'message' => 'Teacher already has a class at this time',
                'schedule' => $teacherConflict
            ];
        }

        // Check classroom conflict
        $classConflict = WeeklySchedule::where('academic_class_id', $scheduleData['academic_class_id'])
            ->where('day_of_week', $scheduleData['day_of_week'])
            ->where('time_slot_id', $scheduleData['time_slot_id'])
            ->when(isset($scheduleData['id']), fn($q) => $q->where('id', '!=', $scheduleData['id']))
            ->first();

        if ($classConflict) {
            $conflicts[] = [
                'type' => 'classroom',
                'message' => 'Class already has a lesson at this time',
                'schedule' => $classConflict
            ];
        }

        return $conflicts;
    }

    public function getStatistics(): array
    {
        return [
            'total_schedules' => WeeklySchedule::count(),
            'active_schedules' => WeeklySchedule::where('is_active', true)->count(),
            'locked_schedules' => WeeklySchedule::where('is_locked', true)->count(),
            'teachers_assigned' => WeeklySchedule::distinct('employee_id')->count('employee_id'),
            'classes_scheduled' => WeeklySchedule::distinct('academic_class_id')->count('academic_class_id'),
        ];
    }

    public function getTimeSlots()
    {
        return TimeSlot::orderBy('start_time')->get();
    }

    public function getSubjects()
    {
        return Subject::where('is_active', true)->orderBy('name')->get();
    }

    public function getClasses()
    {
        return AcademicClass::where('is_active', true)->orderBy('name')->get();
    }

    public function getAvailableTeachers(string $dayOfWeek, string $timeSlotId): array
    {
        $busyTeachers = WeeklySchedule::where('day_of_week', $dayOfWeek)
            ->where('time_slot_id', $timeSlotId)
            ->pluck('employee_id')
            ->toArray();

        return Employee::where('is_active', true)
            ->whereNotIn('id', $busyTeachers)
            ->orderBy('full_name')
            ->get()
            ->toArray();
    }
}
