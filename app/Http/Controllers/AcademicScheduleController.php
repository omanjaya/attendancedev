<?php

namespace App\Http\Controllers;

use App\Models\WeeklySchedule;
use App\Models\AcademicClass;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\Employee;
use App\Models\ScheduleConflict;
use App\Services\ScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AcademicScheduleController extends Controller
{
    protected $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
        $this->middleware(['auth']);
    }

    /**
     * Display schedule management page
     */
    public function index()
    {
        $academicClasses = AcademicClass::active()
                                      ->orderBy('grade_level')
                                      ->orderBy('major')
                                      ->orderBy('class_number')
                                      ->get();

        $subjects = Subject::active()->orderBy('name')->get();
        $timeSlots = TimeSlot::active()->ordered()->get();
        
        // Get statistics for dashboard
        $statistics = [
            'total_schedules' => WeeklySchedule::active()->count(),
            'active_classes' => AcademicClass::active()->count(),
            'total_conflicts' => ScheduleConflict::unresolved()->count(),
            'locked_schedules' => WeeklySchedule::active()->where('is_locked', true)->count(),
        ];
        
        return view('pages.academic.schedules', compact('academicClasses', 'subjects', 'timeSlots', 'statistics'));
    }

    /**
     * Get teachers with their assigned subjects for the calendar view
     */
    public function getTeachersWithSubjects()
    {
        try {
            $teachers = Employee::where('is_active', true)
                ->whereHas('teacherSubjects')
                ->with(['teacherSubjects.subject'])
                ->get()
                ->map(function ($teacher) {
                    return [
                        'id' => $teacher->id,
                        'full_name' => $teacher->full_name,
                        'employee_id' => $teacher->employee_id,
                        'teacher_code' => $teacher->metadata['teacher_code'] ?? substr($teacher->employee_id, 0, 3),
                        'employee_type' => $teacher->employee_type,
                        'metadata' => $teacher->metadata,
                        'subjects' => $teacher->teacherSubjects->map(function ($ts) {
                            return [
                                'id' => $ts->subject->id,
                                'code' => $ts->subject->code,
                                'name' => $ts->subject->name
                            ];
                        })
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $teachers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load teachers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get schedule grid data for a specific class
     */
    public function getScheduleGrid(Request $request, $classId)
    {
        $validator = Validator::make(['class_id' => $classId], [
            'class_id' => 'required|uuid|exists:academic_classes,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $academicClass = AcademicClass::findOrFail($classId);
        $date = $request->input('date', today());
        
        $gridData = WeeklySchedule::getGridData($classId, $date);
        $timeSlots = TimeSlot::active()->ordered()->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'class' => $academicClass,
                'grid' => $gridData,
                'time_slots' => $timeSlots,
                'days' => WeeklySchedule::DAYS_OF_WEEK
            ]
        ]);
    }

    /**
     * Store a new schedule
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'academic_class_id' => 'required|uuid|exists:academic_classes,id',
            'subject_id' => 'required|uuid|exists:subjects,id',
            'employee_id' => 'required|uuid|exists:employees,id',
            'time_slot_id' => 'required|uuid|exists:time_slots,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday',
            'room' => 'nullable|string|max:50',
            'effective_from' => 'nullable|date',
            'reason' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $scheduleData = $request->only([
                'academic_class_id', 'subject_id', 'employee_id', 
                'time_slot_id', 'day_of_week', 'room'
            ]);
            
            $scheduleData['effective_from'] = $request->input('effective_from', today());
            $scheduleData['created_by'] = auth()->id();
            $scheduleData['is_active'] = true;

            // Validate business rules
            $validation = $this->validateScheduleCreation($scheduleData);
            
            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors']
                ], 422);
            }

            // Create schedule
            $schedule = WeeklySchedule::create($scheduleData);

            // Detect and store conflicts
            $conflicts = $schedule->detectConflicts();
            $this->storeConflicts($schedule, $conflicts);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil dibuat',
                'data' => $schedule->load(['subject', 'employee', 'timeSlot', 'academicClass']),
                'conflicts' => $conflicts
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat jadwal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a schedule
     */
    public function update(Request $request, $id)
    {
        $schedule = WeeklySchedule::findOrFail($id);

        if ($schedule->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal terkunci dan tidak dapat diubah'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'academic_class_id' => 'sometimes|uuid|exists:academic_classes,id',
            'subject_id' => 'sometimes|uuid|exists:subjects,id',
            'employee_id' => 'sometimes|uuid|exists:employees,id',
            'time_slot_id' => 'sometimes|uuid|exists:time_slots,id',
            'day_of_week' => 'sometimes|in:monday,tuesday,wednesday,thursday,friday,saturday',
            'room' => 'nullable|string|max:50',
            'reason' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $oldData = $schedule->toArray();
            $newData = $request->only([
                'academic_class_id', 'subject_id', 'employee_id', 
                'time_slot_id', 'day_of_week', 'room'
            ]);
            $newData['updated_by'] = auth()->id();

            // Validate business rules for update
            $validation = $this->validateScheduleUpdate($schedule, $newData);
            
            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors']
                ], 422);
            }

            // Update schedule
            $schedule->update($newData);

            // Log the change
            $schedule->logChange('update', $oldData, $schedule->fresh()->toArray(), auth()->id(), $request->input('reason'));

            // Re-detect conflicts
            $this->clearConflicts($schedule);
            $conflicts = $schedule->detectConflicts();
            $this->storeConflicts($schedule, $conflicts);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil diupdate',
                'data' => $schedule->load(['subject', 'employee', 'timeSlot', 'academicClass']),
                'conflicts' => $conflicts
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate jadwal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a schedule
     */
    public function destroy(Request $request, $id)
    {
        $schedule = WeeklySchedule::findOrFail($id);

        if ($schedule->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal terkunci dan tidak dapat dihapus'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $oldData = $schedule->toArray();

            // Log the deletion
            $schedule->logChange('delete', $oldData, null, auth()->id(), $request->input('reason'));

            // Clear conflicts
            $this->clearConflicts($schedule);

            // Soft delete
            $schedule->update(['is_active' => false, 'updated_by' => auth()->id()]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus jadwal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Swap two schedules
     */
    public function swapSchedules(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schedule_1_id' => 'required|uuid|exists:weekly_schedules,id',
            'schedule_2_id' => 'required|uuid|exists:weekly_schedules,id',
            'reason' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $schedule1 = WeeklySchedule::findOrFail($request->input('schedule_1_id'));
            $schedule2 = WeeklySchedule::findOrFail($request->input('schedule_2_id'));

            if ($schedule1->is_locked || $schedule2->is_locked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Salah satu jadwal terkunci dan tidak dapat ditukar'
                ], 403);
            }

            // Store original data
            $original1 = $schedule1->toArray();
            $original2 = $schedule2->toArray();

            // Swap the schedules
            $temp = [
                'academic_class_id' => $schedule1->academic_class_id,
                'time_slot_id' => $schedule1->time_slot_id,
                'day_of_week' => $schedule1->day_of_week,
                'room' => $schedule1->room
            ];

            $schedule1->update([
                'academic_class_id' => $schedule2->academic_class_id,
                'time_slot_id' => $schedule2->time_slot_id,
                'day_of_week' => $schedule2->day_of_week,
                'room' => $schedule2->room,
                'updated_by' => auth()->id()
            ]);

            $schedule2->update([
                'academic_class_id' => $temp['academic_class_id'],
                'time_slot_id' => $temp['time_slot_id'],
                'day_of_week' => $temp['day_of_week'],
                'room' => $temp['room'],
                'updated_by' => auth()->id()
            ]);

            // Log the changes
            $reason = $request->input('reason', 'Schedule swap');
            $schedule1->logChange('update', $original1, $schedule1->fresh()->toArray(), auth()->id(), $reason);
            $schedule2->logChange('update', $original2, $schedule2->fresh()->toArray(), auth()->id(), $reason);

            // Re-detect conflicts for both
            $this->clearConflicts($schedule1);
            $this->clearConflicts($schedule2);
            
            $conflicts1 = $schedule1->detectConflicts();
            $conflicts2 = $schedule2->detectConflicts();
            
            $this->storeConflicts($schedule1, $conflicts1);
            $this->storeConflicts($schedule2, $conflicts2);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil ditukar',
                'data' => [
                    'schedule_1' => $schedule1->load(['subject', 'employee', 'timeSlot', 'academicClass']),
                    'schedule_2' => $schedule2->load(['subject', 'employee', 'timeSlot', 'academicClass'])
                ],
                'conflicts' => array_merge($conflicts1, $conflicts2)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menukar jadwal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lock/Unlock schedule
     */
    public function toggleLock(Request $request, $id)
    {
        $schedule = WeeklySchedule::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
            'locked_until' => 'nullable|date|after:now'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            if ($schedule->is_locked) {
                // Unlock
                $schedule->unlock($request->input('reason'), auth()->id());
                $message = 'Jadwal berhasil dibuka';
                $action = 'unlock';
            } else {
                // Lock
                $schedule->lock(
                    $request->input('reason'), 
                    auth()->id(), 
                    $request->input('locked_until')
                );
                $message = 'Jadwal berhasil dikunci';
                $action = 'lock';
            }

            // Log the action
            $schedule->logChange($action, [], [], auth()->id(), $request->input('reason'));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $schedule->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status kunci: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available teachers for a subject
     */
    public function getAvailableTeachers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|uuid|exists:subjects,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday',
            'time_slot_id' => 'required|uuid|exists:time_slots,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subject = Subject::findOrFail($request->input('subject_id'));
        $availableTeachers = $subject->getAvailableTeachers(
            $request->input('day_of_week'),
            $request->input('time_slot_id')
        );

        return response()->json([
            'success' => true,
            'data' => $availableTeachers->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->full_name,
                    'employee_id' => $teacher->employee_id
                ];
            })
        ]);
    }

    /**
     * Export schedule to JSON
     */
    public function exportJson($classId)
    {
        $academicClass = AcademicClass::findOrFail($classId);
        $schedules = WeeklySchedule::forClass($classId)
                                  ->active()
                                  ->with(['subject', 'employee', 'timeSlot'])
                                  ->get();

        $exportData = [
            'exported_at' => now()->toISOString(),
            'exported_by' => auth()->user()->name,
            'class' => [
                'id' => $academicClass->id,
                'name' => $academicClass->full_name,
                'grade_level' => $academicClass->grade_level,
                'major' => $academicClass->major,
                'class_number' => $academicClass->class_number
            ],
            'schedules' => $schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'day_of_week' => $schedule->day_of_week,
                    'time_slot' => [
                        'id' => $schedule->timeSlot->id,
                        'name' => $schedule->timeSlot->name,
                        'start_time' => $schedule->timeSlot->start_time->format('H:i'),
                        'end_time' => $schedule->timeSlot->end_time->format('H:i'),
                        'order' => $schedule->timeSlot->order
                    ],
                    'subject' => [
                        'id' => $schedule->subject->id,
                        'code' => $schedule->subject->code,
                        'name' => $schedule->subject->name,
                        'color' => $schedule->subject->color
                    ],
                    'teacher' => [
                        'id' => $schedule->employee->id,
                        'name' => $schedule->employee->full_name,
                        'employee_id' => $schedule->employee->employee_id
                    ],
                    'room' => $schedule->room,
                    'effective_from' => $schedule->effective_from->format('Y-m-d'),
                    'effective_until' => $schedule->effective_until?->format('Y-m-d'),
                    'is_locked' => $schedule->is_locked,
                    'metadata' => $schedule->metadata
                ];
            })
        ];

        $filename = "schedule_{$academicClass->full_name}_" . now()->format('Y-m-d_H-i-s') . ".json";

        return response()->json($exportData)
                         ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Get conflicts for a class
     */
    public function getConflicts($classId)
    {
        $conflicts = ScheduleConflict::whereHas('schedule1', function ($query) use ($classId) {
                                        $query->where('academic_class_id', $classId);
                                    })
                                    ->orWhereHas('schedule2', function ($query) use ($classId) {
                                        $query->where('academic_class_id', $classId);
                                    })
                                    ->where('is_resolved', false)
                                    ->with(['schedule1.subject', 'schedule1.employee', 'schedule1.timeSlot',
                                           'schedule2.subject', 'schedule2.employee', 'schedule2.timeSlot'])
                                    ->orderBy('severity', 'desc')
                                    ->orderBy('detected_at', 'desc')
                                    ->get();

        return response()->json([
            'success' => true,
            'data' => $conflicts
        ]);
    }

    /**
     * Validate schedule creation
     */
    private function validateScheduleCreation($scheduleData)
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
                $scheduleData['day_of_week']
            );

            if (!$validation['weekly_valid']) {
                $errors[] = "Mata pelajaran {$subject->name} melebihi batas maksimal {$subject->max_meetings_per_week} pertemuan per minggu";
            }

            if (!$validation['daily_valid']) {
                $warnings[] = "Mata pelajaran {$subject->name} sudah ada di hari yang sama";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Validate schedule update
     */
    private function validateScheduleUpdate($schedule, $newData)
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

        if (!$hasChanges) {
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
            'warnings' => $warnings
        ];
    }

    /**
     * Store conflicts
     */
    private function storeConflicts($schedule, $conflicts)
    {
        foreach ($conflicts as $conflict) {
            if ($conflict['conflicting_schedule']) {
                ScheduleConflict::create([
                    'schedule_id_1' => $schedule->id,
                    'schedule_id_2' => $conflict['conflicting_schedule']->id,
                    'conflict_type' => $conflict['type'],
                    'severity' => $conflict['severity'],
                    'description' => $conflict['description'],
                    'detected_at' => now()
                ]);
            }
        }
    }

    /**
     * Clear conflicts for a schedule
     */
    private function clearConflicts($schedule)
    {
        ScheduleConflict::where('schedule_id_1', $schedule->id)
                       ->orWhere('schedule_id_2', $schedule->id)
                       ->delete();
    }
}