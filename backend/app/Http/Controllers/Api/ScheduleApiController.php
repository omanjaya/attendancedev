<?php

namespace App\Http\Controllers\Api;

use App\Models\WeeklySchedule;
use App\Models\MonthlySchedule;
use App\Models\TeachingSchedule;
use App\Models\TimeSlot;
use App\Models\Subject;
use App\Models\AcademicClass;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = WeeklySchedule::query()
            ->with(['employee:id,employee_id,full_name', 'academicClass:id,name', 'subject:id,name', 'timeSlot']);

        if ($classId = $request->get('class_id')) {
            $query->where('academic_class_id', $classId);
        }

        if ($employeeId = $request->get('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        if ($subjectId = $request->get('subject_id')) {
            $query->where('subject_id', $subjectId);
        }

        if ($day = $request->get('day_of_week')) {
            $query->where('day_of_week', $day);
        }

        if ($status = $request->get('status')) {
            $query->where('is_active', $status === 'active');
        }

        $perPage = $request->get('per_page', 15);
        $schedules = $query->paginate($perPage);

        return $this->paginatedResponse($schedules, 'Schedules retrieved');
    }

    public function show($id)
    {
        $schedule = WeeklySchedule::with(['employee', 'academicClass', 'subject', 'timeSlot'])->find($id);

        if (!$schedule) {
            return $this->errorResponse('Schedule not found', 404);
        }

        return $this->apiResponse($schedule, 'Schedule retrieved');
    }

    public function byClass($classId)
    {
        $schedules = WeeklySchedule::with(['employee', 'subject', 'timeSlot'])
            ->where('academic_class_id', $classId)
            ->orderBy('day_of_week')
            ->get()
            ->sortBy(function ($schedule) {
                return $schedule->timeSlot->start_time;
            })
            ->values();

        return $this->apiResponse($schedules, 'Class schedules retrieved');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_class_id' => 'required|exists:academic_classes,id',
            'employee_id' => 'required|exists:employees,id',
            'subject_id' => 'required|exists:subjects,id',
            'time_slot_id' => 'required|exists:time_slots,id',
            'day_of_week' => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'room' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            // Create the weekly schedule
            $schedule = WeeklySchedule::create(array_merge($validated, [
                'is_active' => true,
                'effective_from' => now(),
            ]));

            // Auto-sync to TeachingSchedule for the teacher
            $this->syncToTeachingSchedule($schedule);

            return $this->apiResponse($schedule->load(['employee', 'subject', 'timeSlot']), 'Schedule created', 201);
        });
    }

    /**
     * Sync WeeklySchedule to TeachingSchedule
     * This ensures teacher's working hours are automatically updated
     */
    private function syncToTeachingSchedule(WeeklySchedule $schedule): void
    {
        $employee = Employee::with('employeeTypeRelation')->find($schedule->employee_id);
        
        if (!$employee) {
            return;
        }

        // Check if employee type is flexible (Guru Honor) - they need TeachingSchedule
        $employeeType = $employee->employeeTypeRelation;
        $isFlexible = $employeeType?->isFlexible() ?? ($employee->employee_type === 'guru_honorer');
        $canOverrideByTeaching = $employeeType?->can_override_by_teaching ?? ($employee->employee_type === 'guru_honorer');

        // Get time slot for start/end times
        $timeSlot = TimeSlot::find($schedule->time_slot_id);
        $academicClass = AcademicClass::find($schedule->academic_class_id);

        if (!$timeSlot) {
            return;
        }

        // Update or create TeachingSchedule
        // Match by teacher, day, and time range (since TeachingSchedule doesn't have time_slot_id)
        TeachingSchedule::updateOrCreate(
            [
                'teacher_id' => $schedule->employee_id,
                'day_of_week' => $schedule->day_of_week,
                'teaching_start_time' => $timeSlot->start_time,
                'teaching_end_time' => $timeSlot->end_time,
            ],
            [
                'subject_id' => $schedule->subject_id,
                'class_id' => $schedule->academic_class_id,
                'class_name' => $academicClass?->name ?? 'Unknown',
                'room' => $schedule->room,
                'override_attendance' => $isFlexible && $canOverrideByTeaching, // Only override for flexible types
                'is_active' => true,
                'effective_from' => now(),
                'status' => 'scheduled',
            ]
        );
    }

    /**
     * Remove TeachingSchedule when WeeklySchedule is deleted
     */
    private function removeTeachingSchedule(WeeklySchedule $schedule): void
    {
        $timeSlot = TimeSlot::find($schedule->time_slot_id);
        
        if (!$timeSlot) {
            return;
        }

        TeachingSchedule::where('teacher_id', $schedule->employee_id)
            ->where('day_of_week', $schedule->day_of_week)
            ->where('teaching_start_time', $timeSlot->start_time)
            ->where('teaching_end_time', $timeSlot->end_time)
            ->delete();
    }

    public function update(Request $request, $id)
    {
        $schedule = WeeklySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Schedule not found', 404);
        }

        $validated = $request->validate([
            'employee_id' => 'sometimes|exists:employees,id',
            'subject_id' => 'sometimes|exists:subjects,id',
            'time_slot_id' => 'sometimes|exists:time_slots,id',
            'day_of_week' => 'sometimes|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'room' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ]);

        return DB::transaction(function () use ($schedule, $validated) {
            // Store old values for potential TeachingSchedule cleanup
            $oldEmployeeId = $schedule->employee_id;
            $oldDayOfWeek = $schedule->day_of_week;
            $oldTimeSlotId = $schedule->time_slot_id;
            $oldTimeSlot = TimeSlot::find($oldTimeSlotId);

            if (isset($validated['status'])) {
                $validated['is_active'] = $validated['status'] === 'active';
                unset($validated['status']);
            }

            $schedule->update($validated);
            $schedule = $schedule->fresh();

            // If employee, day, or time changed, remove old TeachingSchedule
            if (
                $oldEmployeeId !== $schedule->employee_id ||
                $oldDayOfWeek !== $schedule->day_of_week ||
                $oldTimeSlotId !== $schedule->time_slot_id
            ) {
                if ($oldTimeSlot) {
                    TeachingSchedule::where('teacher_id', $oldEmployeeId)
                        ->where('day_of_week', $oldDayOfWeek)
                        ->where('teaching_start_time', $oldTimeSlot->start_time)
                        ->where('teaching_end_time', $oldTimeSlot->end_time)
                        ->delete();
                }
            }

            // Re-sync the updated schedule
            $this->syncToTeachingSchedule($schedule);

            return $this->apiResponse($schedule->load(['employee', 'subject', 'timeSlot']), 'Schedule updated');
        });
    }

    public function destroy($id)
    {
        $schedule = WeeklySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Schedule not found', 404);
        }

        return DB::transaction(function () use ($schedule) {
            // Remove associated TeachingSchedule first
            $this->removeTeachingSchedule($schedule);
            
            $schedule->delete();

            return $this->apiResponse(null, 'Schedule deleted');
        });
    }

    public function lock(Request $request, $id)
    {
        $schedule = WeeklySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Schedule not found', 404);
        }

        $schedule->update([
            'is_locked' => true,
            // 'lock_reason' => $request->get('reason'), // WeeklySchedule might handle locking differently via relations
        ]);

        return $this->apiResponse($schedule->fresh(), 'Schedule locked');
    }

    public function unlock($id)
    {
        $schedule = WeeklySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Schedule not found', 404);
        }

        $schedule->update([
            'is_locked' => false,
            // 'lock_reason' => null,
        ]);

        return $this->apiResponse($schedule->fresh(), 'Schedule unlocked');
    }

    public function statistics()
    {
        $stats = [
            'total' => WeeklySchedule::count(),
            'active' => WeeklySchedule::where('is_active', true)->count(),
            'inactive' => WeeklySchedule::where('is_active', false)->count(),
            'locked' => WeeklySchedule::where('is_locked', true)->count(),
        ];

        return $this->apiResponse($stats, 'Statistics retrieved');
    }

    public function conflicts(Request $request)
    {
        $classId = $request->get('class_id');

        // Simple conflict detection - schedules with same time slot and day
        $conflicts = WeeklySchedule::query()
            ->when($classId, fn($q) => $q->where('academic_class_id', $classId))
            ->select('day_of_week', 'time_slot_id')
            ->selectRaw('count(*) as conflict_count')
            ->groupBy('day_of_week', 'time_slot_id')
            ->havingRaw('count(*) > 1')
            ->get();

        return $this->apiResponse($conflicts, 'Conflicts retrieved');
    }

    public function timeSlots()
    {
        $slots = TimeSlot::orderBy('start_time')->get();

        return $this->apiResponse($slots, 'Time slots retrieved');
    }

    public function subjects()
    {
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();

        return $this->apiResponse($subjects, 'Subjects retrieved');
    }

    public function classes()
    {
        $classes = AcademicClass::where('is_active', true)->orderBy('name')->get();

        return $this->apiResponse($classes, 'Classes retrieved');
    }

    public function availableTeachers(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'day_of_week' => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'time_slot_id' => 'required|exists:time_slots,id',
        ]);

        // Get teachers who are qualified for the subject and not scheduled at this time
        $busyTeacherIds = WeeklySchedule::where('day_of_week', $validated['day_of_week'])
            ->where('time_slot_id', $validated['time_slot_id'])
            ->pluck('employee_id');

        $teachers = Employee::whereHas('user.roles', function ($q) {
            $q->where('name', 'guru');
        })
            ->whereNotIn('id', $busyTeacherIds)
            ->where('is_active', true)
            ->get(['id', 'employee_id', 'full_name']);

        return $this->apiResponse($teachers, 'Available teachers retrieved');
    }

    // Monthly Schedules
    public function monthlySchedules(Request $request)
    {
        $query = MonthlySchedule::query();

        if ($year = $request->get('year')) {
            $query->where('year', $year);
        }

        if ($month = $request->get('month')) {
            $query->where('month', $month);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $query->orderBy('year', 'desc')->orderBy('month', 'desc');

        $perPage = $request->get('per_page', 15);
        $schedules = $query->paginate($perPage);

        return $this->paginatedResponse($schedules, 'Monthly schedules retrieved');
    }

    public function showMonthly($id)
    {
        $schedule = MonthlySchedule::with(['schedules'])->find($id);

        if (!$schedule) {
            return $this->errorResponse('Monthly schedule not found', 404);
        }

        return $this->apiResponse($schedule, 'Monthly schedule retrieved');
    }

    public function storeMonthly(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'academic_year' => 'required|string',
            'semester' => 'required|in:1,2',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
        ]);

        $schedule = MonthlySchedule::create(array_merge($validated, [
            'status' => 'draft',
        ]));

        return $this->apiResponse($schedule, 'Monthly schedule created', 201);
    }

    public function publishMonthly($id)
    {
        $schedule = MonthlySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Monthly schedule not found', 404);
        }

        $schedule->update(['status' => 'published', 'published_at' => now()]);

        return $this->apiResponse($schedule->fresh(), 'Monthly schedule published');
    }

    public function destroyMonthly($id)
    {
        $schedule = MonthlySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Monthly schedule not found', 404);
        }

        $schedule->delete();

        return $this->apiResponse(null, 'Monthly schedule deleted');
    }

    /**
     * Bulk import teaching schedules from Excel data
     * 
     * This endpoint receives parsed Excel data and creates TeachingSchedule records
     * for each teacher-class-period combination, linking to existing employees.
     */
    public function bulkImportTeachingSchedules(Request $request)
    {
        $validated = $request->validate([
            'teachers' => 'required|array',
            'teachers.*.code' => 'required|string',
            'teachers.*.name' => 'required|string',
            'teachers.*.subject' => 'nullable|string',
            'schedules' => 'required|array',
            'schedules.*.day' => 'required|string',
            'schedules.*.period' => 'required|integer|min:1|max:12',
            'schedules.*.className' => 'required|string',
            'schedules.*.teacherCode' => 'required|string',
            'schedules.*.subject' => 'nullable|string',
            'effective_from' => 'required|date',
            'effective_until' => 'nullable|date|after_or_equal:effective_from',
            'semester' => 'nullable|in:1,2',
            'academic_year' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $results = [
                'matched_teachers' => [],
                'unmatched_teachers' => [],
                'created_schedules' => 0,
                'skipped_schedules' => 0,
                'created_subjects' => [],
                'errors' => [],
            ];

            // Day mapping from Indonesian to English
            $dayMapping = [
                'Senin' => 'monday',
                'Selasa' => 'tuesday',
                'Rabu' => 'wednesday',
                'Kamis' => 'thursday',
                'Jumat' => 'friday',
                'Sabtu' => 'saturday',
                'Minggu' => 'sunday',
            ];

            // Period to time mapping (default school times, format 24-hour)
            $periodTimes = [
                1 => ['start' => '07:30', 'end' => '08:10'],
                2 => ['start' => '08:10', 'end' => '08:50'],
                3 => ['start' => '08:50', 'end' => '09:30'],
                4 => ['start' => '09:30', 'end' => '10:10'],
                5 => ['start' => '10:30', 'end' => '11:10'],
                6 => ['start' => '11:10', 'end' => '11:50'],
                7 => ['start' => '11:50', 'end' => '12:30'],
                8 => ['start' => '12:30', 'end' => '13:10'],
                9 => ['start' => '13:20', 'end' => '14:00'],
                10 => ['start' => '14:00', 'end' => '14:40'],
                11 => ['start' => '14:40', 'end' => '15:20'],
                12 => ['start' => '15:20', 'end' => '16:00'],
            ];

            // Step 1: Match teachers to employees
            $teacherMap = []; // code => employee_id
            
            foreach ($validated['teachers'] as $teacher) {
                $code = $teacher['code'];
                $name = $teacher['name'];
                
                // Try to find employee by name (fuzzy match)
                $employee = $this->findEmployeeByName($name);
                
                if ($employee) {
                    $teacherMap[$code] = $employee->id;
                    $results['matched_teachers'][] = [
                        'code' => $code,
                        'excel_name' => $name,
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->full_name,
                    ];
                    
                    // Store teacher code in employee metadata
                    $metadata = $employee->metadata ?? [];
                    $metadata['teacher_code'] = $code;
                    $metadata['subject'] = $teacher['subject'] ?? null;
                    $employee->update(['metadata' => $metadata]);
                } else {
                    $results['unmatched_teachers'][] = [
                        'code' => $code,
                        'name' => $name,
                        'subject' => $teacher['subject'] ?? null,
                    ];
                }
            }

            // Step 2: Get or create subjects
            $subjectMap = []; // name => subject_id
            
            foreach ($validated['schedules'] as $schedule) {
                $subjectName = $schedule['subject'] ?? null;
                if ($subjectName && !isset($subjectMap[$subjectName])) {
                    $subject = Subject::firstOrCreate(
                        ['name' => $subjectName],
                        [
                            'code' => strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $subjectName), 0, 5)),
                            'is_active' => true,
                        ]
                    );
                    $subjectMap[$subjectName] = $subject->id;
                    
                    if ($subject->wasRecentlyCreated) {
                        $results['created_subjects'][] = $subjectName;
                    }
                }
            }

            // Step 3: Create teaching schedules
            foreach ($validated['schedules'] as $schedule) {
                $teacherCode = $schedule['teacherCode'];
                
                // Skip if teacher not matched
                if (!isset($teacherMap[$teacherCode])) {
                    $results['skipped_schedules']++;
                    continue;
                }

                $employeeId = $teacherMap[$teacherCode];
                $dayEnglish = $dayMapping[$schedule['day']] ?? strtolower($schedule['day']);
                $period = $schedule['period'];
                $times = $periodTimes[$period] ?? ['start' => '08:00', 'end' => '08:40'];
                $subjectId = $subjectMap[$schedule['subject']] ?? null;

                try {
                    // Check if employee is flexible (honor/contract) to set override_attendance
                    $employee = Employee::with('employeeTypeRelation')->find($employeeId);
                    $isFlexible = $employee?->employeeTypeRelation?->isFlexible() ?? 
                                  ($employee?->employee_type === 'guru_honorer');

                    TeachingSchedule::updateOrCreate(
                        [
                            'teacher_id' => $employeeId,
                            'day_of_week' => $dayEnglish,
                            'teaching_start_time' => $times['start'],
                        ],
                        [
                            'teaching_end_time' => $times['end'],
                            'subject_id' => $subjectId,
                            'class_name' => $schedule['className'],
                            'effective_from' => $validated['effective_from'],
                            'effective_until' => $validated['effective_until'],
                            'is_active' => true,
                            'status' => 'scheduled',
                            'override_attendance' => $isFlexible, // Only honor teachers override
                            'strict_timing' => true,
                            'late_threshold_minutes' => 15,
                            'metadata' => [
                                'imported_from' => 'excel',
                                'import_date' => now()->toDateTimeString(),
                                'semester' => $validated['semester'] ?? null,
                                'academic_year' => $validated['academic_year'] ?? null,
                                'period_number' => $period,
                                'teacher_code' => $teacherCode,
                            ],
                        ]
                    );

                    $results['created_schedules']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'schedule' => $schedule,
                        'error' => $e->getMessage(),
                    ];
                    $results['skipped_schedules']++;
                }
            }

            return $this->apiResponse($results, 'Bulk import completed', 200);
        });
    }

    /**
     * Match teachers from Excel to existing employees by name
     */
    public function matchTeachers(Request $request)
    {
        $validated = $request->validate([
            'teachers' => 'required|array',
            'teachers.*.code' => 'required|string',
            'teachers.*.name' => 'required|string',
            'teachers.*.subject' => 'nullable|string',
        ]);

        $results = [];

        foreach ($validated['teachers'] as $teacher) {
            $employee = $this->findEmployeeByName($teacher['name']);
            
            $results[] = [
                'code' => $teacher['code'],
                'excel_name' => $teacher['name'],
                'subject' => $teacher['subject'] ?? null,
                'matched' => $employee !== null,
                'employee_id' => $employee?->id,
                'employee_name' => $employee?->full_name,
                'employee_nip' => $employee?->employee_id,
            ];
        }

        return $this->apiResponse($results, 'Teacher matching completed');
    }

    /**
     * Find employee by name using fuzzy matching
     */
    private function findEmployeeByName(string $name): ?Employee
    {
        // Clean the name
        $cleanName = $this->cleanName($name);
        
        // Try exact match first
        $employee = Employee::where('full_name', 'LIKE', "%{$cleanName}%")
            ->where('is_active', true)
            ->first();
        
        if ($employee) {
            return $employee;
        }

        // Try matching without titles
        $nameWithoutTitles = $this->removeNameTitles($name);
        $employee = Employee::where('full_name', 'LIKE', "%{$nameWithoutTitles}%")
            ->where('is_active', true)
            ->first();
        
        if ($employee) {
            return $employee;
        }

        // Try matching first two words only
        $nameParts = explode(' ', $nameWithoutTitles);
        if (count($nameParts) >= 2) {
            $shortName = $nameParts[0] . ' ' . $nameParts[1];
            $employee = Employee::where('full_name', 'LIKE', "%{$shortName}%")
                ->where('is_active', true)
                ->first();
        }

        return $employee;
    }

    /**
     * Clean name for matching
     */
    private function cleanName(string $name): string
    {
        // Remove extra whitespace
        $name = preg_replace('/\s+/', ' ', trim($name));
        
        // Remove common punctuation
        $name = str_replace([',', '.', '-', '_'], ' ', $name);
        
        return trim($name);
    }

    /**
     * Remove academic titles from name
     */
    private function removeNameTitles(string $name): string
    {
        $titles = [
            'Dr.', 'Drs.', 'Dra.', 'Prof.', 'Ir.',
            'S.Pd', 'S.Ag', 'S.Sn', 'S.Si', 'S.Sos', 'S.Th', 'S.Pdi', 'S.Ak', 'S.ST', 'S.Pd.H',
            'M.Pd', 'M.Si', 'M.Ag', 'M.A', 'M.M',
            'B.A', 'BA', 'SS', 'ST', 'SE',
            'S. Pd', 'S. Si', 'S. Ag', 'S. Sn', 'S. Sos', 'S. Th', 'S. Psi', 'S. Ak',
        ];

        $name = str_replace($titles, '', $name);
        $name = preg_replace('/\s+/', ' ', trim($name));
        
        return $name;
    }

    /**
     * Get teaching schedules for a specific period
     */
    public function getTeachingSchedules(Request $request)
    {
        $query = TeachingSchedule::query()
            ->with(['teacher:id,employee_id,full_name', 'subject:id,name']);

        if ($teacherId = $request->get('teacher_id')) {
            $query->where('teacher_id', $teacherId);
        }

        if ($day = $request->get('day_of_week')) {
            $query->where('day_of_week', strtolower($day));
        }

        if ($effectiveFrom = $request->get('effective_from')) {
            $query->where('effective_from', '>=', $effectiveFrom);
        }

        if ($effectiveUntil = $request->get('effective_until')) {
            $query->where(function($q) use ($effectiveUntil) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '<=', $effectiveUntil);
            });
        }

        if ($request->get('active_only', true)) {
            $query->where('is_active', true);
        }

        $perPage = $request->get('per_page', 50);
        $schedules = $query->orderBy('day_of_week')
            ->orderBy('teaching_start_time')
            ->paginate($perPage);

        return $this->paginatedResponse($schedules, 'Teaching schedules retrieved');
    }

    /**
     * Clear teaching schedules for a specific period
     */
    public function clearTeachingSchedules(Request $request)
    {
        $validated = $request->validate([
            'effective_from' => 'required|date',
            'effective_until' => 'nullable|date',
            'teacher_id' => 'nullable|uuid',
        ]);

        $query = TeachingSchedule::where('effective_from', '>=', $validated['effective_from']);

        if (!empty($validated['effective_until'])) {
            $query->where(function($q) use ($validated) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '<=', $validated['effective_until']);
            });
        }

        if (!empty($validated['teacher_id'])) {
            $query->where('teacher_id', $validated['teacher_id']);
        }

        $count = $query->count();
        $query->delete();

        return $this->apiResponse(['deleted_count' => $count], 'Teaching schedules cleared');
    }
}

