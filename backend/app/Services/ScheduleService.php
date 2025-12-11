<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\ScheduleConflict;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\WeeklySchedule;
use App\Repositories\EmployeeRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\WeeklyScheduleRepository;
use Illuminate\Http\UploadedFile;

class ScheduleService
{
    protected $weeklyScheduleRepository;

    protected $subjectRepository;

    protected $employeeRepository;

    public function __construct(
        WeeklyScheduleRepository $weeklyScheduleRepository,
        SubjectRepository $subjectRepository,
        EmployeeRepository $employeeRepository
    ) {
        $this->weeklyScheduleRepository = $weeklyScheduleRepository;
        $this->subjectRepository = $subjectRepository;
        $this->employeeRepository = $employeeRepository;
    }

    /**
     * Validate schedule creation
     */
    public function validateScheduleCreation(array $scheduleData): array
    {
        $errors = [];
        $warnings = [];

        // Check for class double booking
        $classConflict = WeeklySchedule::where('academic_class_id', $scheduleData['academic_class_id'])
            ->where('day_of_week', $scheduleData['day_of_week'])
            ->where('time_slot_id', $scheduleData['time_slot_id'])
            ->where('is_active', true)
            ->effective($scheduleData['effective_from'])
            ->exists();

        if ($classConflict) {
            $errors[] = 'Kelas sudah memiliki jadwal pada waktu yang sama';
        }

        // Check for teacher double booking
        $teacherConflict = WeeklySchedule::where('employee_id', $scheduleData['employee_id'])
            ->where('day_of_week', $scheduleData['day_of_week'])
            ->where('time_slot_id', $scheduleData['time_slot_id'])
            ->where('is_active', true)
            ->effective($scheduleData['effective_from'])
            ->exists();

        if ($teacherConflict) {
            $errors[] = 'Guru sudah mengajar pada waktu yang sama';
        }

        // Check subject frequency using repository
        $subject = $this->subjectRepository->find($scheduleData['subject_id']);
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

        // Check teacher workload
        $teacherWeeklyHours = WeeklySchedule::where('employee_id', $scheduleData['employee_id'])
            ->where('is_active', true)
            ->effective($scheduleData['effective_from'])
            ->count();

        $employee = $this->employeeRepository->find($scheduleData['employee_id']);
        $maxHours = $employee->employee_type === 'permanent' ? 24 : 18; // Example limits

        if ($teacherWeeklyHours >= $maxHours) {
            $warnings[] = "Guru mendekati batas maksimal jam mengajar ({$teacherWeeklyHours}/{$maxHours})";
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
    public function validateScheduleUpdate(WeeklySchedule $schedule, array $newData): array
    {
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
            ->effective($checkData['effective_from'])
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
            ->effective($checkData['effective_from'])
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
     * Swap two schedules
     */
    public function swapSchedules(
        string $schedule1Id,
        string $schedule2Id,
        string $userId,
        ?string $reason = null,
    ): array {
        $schedule1 = WeeklySchedule::findOrFail($schedule1Id);
        $schedule2 = WeeklySchedule::findOrFail($schedule2Id);

        if ($schedule1->is_locked || $schedule2->is_locked) {
            return [
                'success' => false,
                'message' => 'Salah satu jadwal terkunci dan tidak dapat ditukar',
            ];
        }

        // Store original data
        $original1 = $schedule1->toArray();
        $original2 = $schedule2->toArray();

        // Swap the time slots and days
        $temp = [
            'time_slot_id' => $schedule1->time_slot_id,
            'day_of_week' => $schedule1->day_of_week,
            'room' => $schedule1->room,
        ];

        $schedule1->update([
            'time_slot_id' => $schedule2->time_slot_id,
            'day_of_week' => $schedule2->day_of_week,
            'room' => $schedule2->room,
            'updated_by' => $userId,
        ]);

        $schedule2->update([
            'time_slot_id' => $temp['time_slot_id'],
            'day_of_week' => $temp['day_of_week'],
            'room' => $temp['room'],
            'updated_by' => $userId,
        ]);

        // Log the changes
        $swapReason = $reason ?: 'Schedule swap';
        $schedule1->logChange(
            'update',
            $original1,
            $schedule1->fresh()->toArray(),
            $userId,
            $swapReason,
        );
        $schedule2->logChange(
            'update',
            $original2,
            $schedule2->fresh()->toArray(),
            $userId,
            $swapReason,
        );

        return [
            'success' => true,
            'message' => 'Jadwal berhasil ditukar',
            'schedules' => [
                'schedule_1' => $schedule1->load(['subject', 'employee', 'timeSlot', 'academicClass']),
                'schedule_2' => $schedule2->load(['subject', 'employee', 'timeSlot', 'academicClass']),
            ],
        ];
    }

    /**
     * Store conflicts for a schedule
     */
    public function storeConflicts(WeeklySchedule $schedule, array $conflicts): void
    {
        foreach ($conflicts as $conflict) {
            if (isset($conflict['conflicting_schedule']) && $conflict['conflicting_schedule']) {
                ScheduleConflict::create([
                    'schedule_id_1' => $schedule->id,
                    'schedule_id_2' => $conflict['conflicting_schedule']->id,
                    'conflict_type' => $conflict['type'],
                    'severity' => $conflict['severity'],
                    'description' => $conflict['description'],
                    'detected_at' => now(),
                ]);
            } else {
                // Single schedule conflicts (like frequency exceeded)
                ScheduleConflict::create([
                    'schedule_id_1' => $schedule->id,
                    'schedule_id_2' => null,
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
    public function clearConflicts(WeeklySchedule $schedule): void
    {
        ScheduleConflict::where('schedule_id_1', $schedule->id)
            ->orWhere('schedule_id_2', $schedule->id)
            ->delete();
    }

    /**
     * Import schedules from JSON file
     */
    public function importFromJson(
        UploadedFile $file,
        string $classId,
        bool $replaceExisting,
        string $userId,
    ): array {
        try {
            $jsonContent = file_get_contents($file->getPathname());
            $data = json_decode($jsonContent, true);

            if (! $data || ! isset($data['schedules'])) {
                return [
                    'success' => false,
                    'message' => 'Format file JSON tidak valid',
                ];
            }

            $academicClass = AcademicClass::findOrFail($classId);
            $importedCount = 0;
            $skippedCount = 0;
            $errors = [];
            $conflicts = [];

            // Clear existing schedules if replace mode
            if ($replaceExisting) {
                WeeklySchedule::where('academic_class_id', $classId)
                    ->where('is_active', true)
                    ->update(['is_active' => false, 'updated_by' => $userId]);
            }

            foreach ($data['schedules'] as $scheduleData) {
                try {
                    // Validate required fields
                    $requiredFields = ['day_of_week', 'time_slot', 'subject', 'teacher'];
                    $missingFields = [];

                    foreach ($requiredFields as $field) {
                        if (! isset($scheduleData[$field])) {
                            $missingFields[] = $field;
                        }
                    }

                    if (! empty($missingFields)) {
                        $errors[] = 'Missing fields: '.implode(', ', $missingFields);
                        $skippedCount++;

                        continue;
                    }

                    // Find related entities
                    $timeSlot = TimeSlot::where('name', $scheduleData['time_slot']['name'])->first();
                    $subject = $this->subjectRepository->getByCode($scheduleData['subject']['code']);
                    $employee = $this->employeeRepository->getByEmployeeId($scheduleData['teacher']['employee_id']);

                    if (! $timeSlot || ! $subject || ! $employee) {
                        $errors[] = 'Related entities not found for schedule';
                        $skippedCount++;

                        continue;
                    }

                    // Check for existing schedule
                    $existingSchedule = WeeklySchedule::where('academic_class_id', $classId)
                        ->where('day_of_week', $scheduleData['day_of_week'])
                        ->where('time_slot_id', $timeSlot->id)
                        ->where('is_active', true)
                        ->first();

                    if ($existingSchedule && ! $replaceExisting) {
                        $skippedCount++;

                        continue;
                    }

                    // Create new schedule
                    $newScheduleData = [
                        'academic_class_id' => $classId,
                        'subject_id' => $subject->id,
                        'employee_id' => $employee->id,
                        'time_slot_id' => $timeSlot->id,
                        'day_of_week' => $scheduleData['day_of_week'],
                        'room' => $scheduleData['room'] ?? null,
                        'effective_from' => $scheduleData['effective_from'] ?? today(),
                        'effective_until' => $scheduleData['effective_until'] ?? null,
                        'is_active' => true,
                        'created_by' => $userId,
                    ];

                    $schedule = $this->weeklyScheduleRepository->assignSchedule($newScheduleData);

                    // Log the import
                    $schedule->logChange('create', null, $newScheduleData, $userId, 'Imported from JSON');

                    // Detect conflicts
                    $scheduleConflicts = $schedule->detectConflicts();
                    if (! empty($scheduleConflicts)) {
                        $conflicts = array_merge($conflicts, $scheduleConflicts);
                        $this->storeConflicts($schedule, $scheduleConflicts);
                    }

                    $importedCount++;
                } catch (\Exception $e) {
                    $errors[] = 'Error processing schedule: '.$e->getMessage();
                    $skippedCount++;
                }
            }

            return [
                'success' => true,
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'errors' => $errors,
                'conflicts' => $conflicts,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error importing JSON: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Bulk update schedules
     */
    public function bulkUpdate(
        array $scheduleIds,
        array $updateData,
        string $userId,
        ?string $reason = null,
    ): array {
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($scheduleIds as $scheduleId) {
            try {
                $schedule = WeeklySchedule::findOrFail($scheduleId);

                if ($schedule->is_locked) {
                    $skippedCount++;
                    $errors[] = "Schedule {$scheduleId} is locked";

                    continue;
                }

                $oldData = $schedule->toArray();
                $newData = array_merge($updateData, ['updated_by' => $userId]);

                $schedule->update($newData);

                // Log the change
                $schedule->logChange(
                    'bulk_update',
                    $oldData,
                    $schedule->fresh()->toArray(),
                    $userId,
                    $reason,
                );

                $updatedCount++;
            } catch (\Exception $e) {
                $skippedCount++;
                $errors[] = "Error updating schedule {$scheduleId}: ".$e->getMessage();
            }
        }

        return [
            'success' => $updatedCount > 0,
            'updated_count' => $updatedCount,
            'skipped_count' => $skippedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Generate schedule template
     */
    public function generateTemplate(string $templateName, array $config): array
    {
        // This would generate a basic schedule template based on configuration
        // For example: standard weekly schedule for a grade level

        $timeSlots = TimeSlot::active()->ordered()->get();
        $subjects = $this->subjectRepository->getActiveSubjects();
        $days = array_keys(WeeklySchedule::DAYS_OF_WEEK);

        $template = [
            'name' => $templateName,
            'description' => 'Generated template',
            'template_data' => [
                'time_slots' => $timeSlots->toArray(),
                'subjects' => $subjects->toArray(),
                'days' => $days,
                'configuration' => $config,
            ],
            'template_type' => 'weekly',
        ];

        return $template;
    }

    /**
     * Detect system-wide conflicts
     */
    public function detectSystemWideConflicts(): array
    {
        // Use repository method to detect conflicts
        return $this->weeklyScheduleRepository->detectConflicts(now()->startOfWeek()->format('Y-m-d'));
    }

    /**
     * Get schedule statistics
     */
    public function getScheduleStatistics(): array
    {
        // Use repository method to get comprehensive statistics
        return $this->weeklyScheduleRepository->getWeeklyScheduleStatistics(
            now()->startOfWeek()->format('Y-m-d')
        );
    }
}
