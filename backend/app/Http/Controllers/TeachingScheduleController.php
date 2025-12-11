<?php

namespace App\Http\Controllers;

use App\Models\TeachingSchedule;
use App\Models\Employee;
use App\Models\Subject;
use App\Models\AcademicClass;
use App\Services\ScheduleManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TeachingScheduleController extends Controller
{
    protected ScheduleManagementService $scheduleService;

    public function __construct(ScheduleManagementService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
        $this->middleware('auth');
        $this->middleware('permission:manage_teaching_schedules')->except(['index', 'show']);
        $this->middleware('permission:view_teaching_schedules')->only(['index', 'show']);
    }

    /**
     * Display a listing of teaching schedules
     */
    public function index(Request $request)
    {
        // For web requests, return the Blade view
        if (!$request->expectsJson() && !$request->is('api/*')) {
            $teachers = Employee::whereIn('employee_type', ['guru_tetap', 'guru_honorer'])
                ->with('user')
                ->active()
                ->get();
            $subjects = Subject::active()->get();
            $classes = AcademicClass::active()->get();
            $locations = \App\Models\Location::active()->get();
            
            return view('pages.schedules.teaching.index', compact(
                'teachers', 'subjects', 'classes', 'locations'
            ));
        }

        // For API requests, return JSON data
        $query = TeachingSchedule::with(['teacher', 'subject', 'substituteTeacher'])
            ->active();

        // Filter by teacher
        if ($request->has('teacher_id')) {
            $query->forTeacher($request->teacher_id);
        }

        // Filter by subject
        if ($request->has('subject_id')) {
            $query->forSubject($request->subject_id);
        }

        // Filter by day of week
        if ($request->has('day_of_week')) {
            $query->forDay($request->day_of_week);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->has('date_to')) {
            $dateFrom = Carbon::parse($request->date_from);
            $dateTo = Carbon::parse($request->date_to);
            
            $query->where('effective_from', '<=', $dateTo)
                  ->where(function($q) use ($dateFrom) {
                      $q->whereNull('effective_until')
                        ->orWhere('effective_until', '>=', $dateFrom);
                  });
        }

        // Search by class name
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('class_name', 'like', '%' . $request->search . '%')
                  ->orWhere('room', 'like', '%' . $request->search . '%');
            });
        }

        $schedules = $query->orderBy('day_of_week')
            ->orderBy('teaching_start_time')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $schedules->map(function($schedule) {
                return [
                    'id' => $schedule->id,
                    'teacher' => [
                        'id' => $schedule->teacher->id,
                        'name' => $schedule->teacher->full_name,
                        'employee_type' => $schedule->teacher->employee_type
                    ],
                    'subject' => [
                        'id' => $schedule->subject->id,
                        'name' => $schedule->subject->name
                    ],
                    'day_of_week' => $schedule->day_of_week,
                    'day_label' => $schedule->day_label,
                    'teaching_start_time' => $schedule->teaching_start_time->format('H:i'),
                    'teaching_end_time' => $schedule->teaching_end_time->format('H:i'),
                    'formatted_time' => $schedule->formatted_time,
                    'teaching_duration_hours' => $schedule->teaching_duration_hours,
                    'class_name' => $schedule->full_class_name,
                    'room' => $schedule->room,
                    'student_count' => $schedule->student_count,
                    'effective_from' => $schedule->effective_from->format('Y-m-d'),
                    'effective_until' => $schedule->effective_until?->format('Y-m-d'),
                    'status' => $schedule->status,
                    'status_label' => $schedule->status_label,
                    'override_attendance' => $schedule->override_attendance,
                    'is_currently_active' => $schedule->is_currently_active,
                    'has_substitute' => $schedule->has_substitute,
                    'substitute_teacher' => $schedule->substituteTeacher ? [
                        'id' => $schedule->substituteTeacher->id,
                        'name' => $schedule->substituteTeacher->full_name
                    ] : null,
                    'created_at' => $schedule->created_at->format('Y-m-d H:i:s')
                ];
            }),
            'meta' => [
                'current_page' => $schedules->currentPage(),
                'last_page' => $schedules->lastPage(),
                'per_page' => $schedules->perPage(),
                'total' => $schedules->total()
            ]
        ]);
    }

    /**
     * Show the form for creating a new teaching schedule
     */
    public function create(): JsonResponse
    {
        $teachers = Employee::where('can_teach', true)
            ->active()
            ->select('id', 'full_name', 'employee_type')
            ->get();

        $subjects = Subject::active()
            ->select('id', 'name')
            ->get();

        $daysOfWeek = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu'
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'teachers' => $teachers,
                'subjects' => $subjects,
                'days_of_week' => collect($daysOfWeek)->map(function($label, $value) {
                    return ['value' => $value, 'label' => $label];
                })->values(),
                'status_options' => [
                    ['value' => 'scheduled', 'label' => 'Scheduled'],
                    ['value' => 'cancelled', 'label' => 'Cancelled'],
                    ['value' => 'rescheduled', 'label' => 'Rescheduled'],
                    ['value' => 'substituted', 'label' => 'Substituted']
                ]
            ]
        ]);
    }

    /**
     * Store a newly created teaching schedule
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), TeachingSchedule::validationRules());
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $schedule = $this->scheduleService->createTeachingSchedule($validator->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Teaching schedule created successfully',
                'data' => [
                    'id' => $schedule->id,
                    'teacher_name' => $schedule->teacher->full_name,
                    'subject_name' => $schedule->subject->name,
                    'day_label' => $schedule->day_label,
                    'formatted_time' => $schedule->formatted_time,
                    'class_name' => $schedule->full_class_name
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create teaching schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified teaching schedule
     */
    public function show(TeachingSchedule $teachingSchedule): JsonResponse
    {
        $teachingSchedule->load(['teacher', 'subject', 'substituteTeacher', 'monthlySchedule']);
        
        $conflicts = $teachingSchedule->getConflictingSchedules();
        $workload = $teachingSchedule->teacher->getTeachingWorkload();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $teachingSchedule->id,
                'teacher' => [
                    'id' => $teachingSchedule->teacher->id,
                    'name' => $teachingSchedule->teacher->full_name,
                    'employee_type' => $teachingSchedule->teacher->employee_type,
                    'can_substitute' => $teachingSchedule->teacher->can_substitute
                ],
                'subject' => [
                    'id' => $teachingSchedule->subject->id,
                    'name' => $teachingSchedule->subject->name
                ],
                'schedule_details' => [
                    'day_of_week' => $teachingSchedule->day_of_week,
                    'day_label' => $teachingSchedule->day_label,
                    'teaching_start_time' => $teachingSchedule->teaching_start_time->format('H:i'),
                    'teaching_end_time' => $teachingSchedule->teaching_end_time->format('H:i'),
                    'formatted_time' => $teachingSchedule->formatted_time,
                    'teaching_duration_minutes' => $teachingSchedule->teaching_duration_minutes,
                    'teaching_duration_hours' => $teachingSchedule->teaching_duration_hours
                ],
                'class_details' => [
                    'class_name' => $teachingSchedule->full_class_name,
                    'room' => $teachingSchedule->room,
                    'student_count' => $teachingSchedule->student_count
                ],
                'effective_period' => [
                    'effective_from' => $teachingSchedule->effective_from->format('Y-m-d'),
                    'effective_until' => $teachingSchedule->effective_until?->format('Y-m-d'),
                    'is_currently_active' => $teachingSchedule->is_currently_active
                ],
                'settings' => [
                    'status' => $teachingSchedule->status,
                    'status_label' => $teachingSchedule->status_label,
                    'override_attendance' => $teachingSchedule->override_attendance,
                    'strict_timing' => $teachingSchedule->strict_timing,
                    'late_threshold_minutes' => $teachingSchedule->late_threshold_minutes
                ],
                'substitute_info' => $teachingSchedule->substituteTeacher ? [
                    'substitute_teacher' => [
                        'id' => $teachingSchedule->substituteTeacher->id,
                        'name' => $teachingSchedule->substituteTeacher->full_name
                    ],
                    'substitution_start_date' => $teachingSchedule->substitution_start_date?->format('Y-m-d'),
                    'substitution_end_date' => $teachingSchedule->substitution_end_date?->format('Y-m-d'),
                    'substitution_reason' => $teachingSchedule->substitution_reason,
                    'has_substitute' => $teachingSchedule->has_substitute
                ] : null,
                'conflicts' => $conflicts,
                'teacher_workload' => $workload,
                'metadata' => $teachingSchedule->metadata,
                'created_at' => $teachingSchedule->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $teachingSchedule->updated_at->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Update the specified teaching schedule
     */
    public function update(Request $request, TeachingSchedule $teachingSchedule): JsonResponse
    {
        $rules = TeachingSchedule::validationRules();
        $rules['id'] = 'sometimes'; // For conflict checking
        
        $validator = Validator::make(
            array_merge($request->all(), ['id' => $teachingSchedule->id]), 
            $rules
        );
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['updated_by'] = auth()->id();
            unset($data['id']); // Remove id from update data
            
            $teachingSchedule->update($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Teaching schedule updated successfully',
                'data' => [
                    'id' => $teachingSchedule->id,
                    'teacher_name' => $teachingSchedule->teacher->full_name,
                    'subject_name' => $teachingSchedule->subject->name,
                    'formatted_time' => $teachingSchedule->formatted_time
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update teaching schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified teaching schedule
     */
    public function destroy(TeachingSchedule $teachingSchedule): JsonResponse
    {
        try {
            $teachingSchedule->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Teaching schedule deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete teaching schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign substitute teacher
     */
    public function assignSubstitute(Request $request, TeachingSchedule $teachingSchedule): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'substitute_teacher_id' => 'required|uuid|exists:employees,id',
            'substitution_start_date' => 'required|date|after_or_equal:today',
            'substitution_end_date' => 'required|date|after_or_equal:substitution_start_date',
            'substitution_reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $substitute = Employee::findOrFail($request->substitute_teacher_id);
            
            $success = $teachingSchedule->assignSubstitute(
                $substitute,
                Carbon::parse($request->substitution_start_date),
                Carbon::parse($request->substitution_end_date),
                $request->substitution_reason
            );

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot assign substitute. Teacher may not be qualified or has scheduling conflicts.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Substitute teacher assigned successfully',
                'data' => [
                    'substitute_teacher' => $substitute->full_name,
                    'substitution_period' => $request->substitution_start_date . ' to ' . $request->substitution_end_date
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign substitute teacher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove substitute teacher
     */
    public function removeSubstitute(TeachingSchedule $teachingSchedule): JsonResponse
    {
        try {
            if (!$teachingSchedule->substitute_teacher_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No substitute teacher assigned to this schedule'
                ], 400);
            }

            $teachingSchedule->removeSubstitute();

            return response()->json([
                'success' => true,
                'message' => 'Substitute teacher removed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove substitute teacher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get teacher workload summary
     */
    public function teacherWorkload(Request $request): JsonResponse
    {
        $locationId = $request->get('location_id');

        try {
            $workloadSummary = $this->scheduleService->getTeacherWorkloadSummary($locationId);

            return response()->json([
                'success' => true,
                'data' => [
                    'teachers' => $workloadSummary,
                    'statistics' => [
                        'total_teachers' => $workloadSummary->count(),
                        'overloaded_teachers' => $workloadSummary->where('is_overloaded', true)->count(),
                        'available_substitutes' => $workloadSummary->where('can_substitute', true)->count(),
                        'average_workload' => $workloadSummary->avg('workload_percentage')
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get teacher workload summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available substitute teachers
     */
    public function availableSubstitutes(TeachingSchedule $teachingSchedule): JsonResponse
    {
        $substitutes = Employee::where('can_substitute', true)
            ->where('can_teach', true)
            ->where('id', '!=', $teachingSchedule->teacher_id)
            ->active()
            ->get()
            ->filter(function($teacher) use ($teachingSchedule) {
                // Check for conflicts
                $conflicts = TeachingSchedule::forTeacher($teacher->id)
                    ->where('day_of_week', $teachingSchedule->day_of_week)
                    ->active()
                    ->forTimeRange(
                        $teachingSchedule->teaching_start_time->format('H:i'),
                        $teachingSchedule->teaching_end_time->format('H:i')
                    )
                    ->exists();
                
                return !$conflicts;
            })
            ->map(function($teacher) {
                $workload = $teacher->getTeachingWorkload();
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->full_name,
                    'employee_type' => $teacher->employee_type,
                    'current_workload' => $workload['percentage'],
                    'is_overloaded' => $workload['is_overloaded'],
                    'subjects' => $workload['subjects']
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'available_substitutes' => $substitutes->values(),
                'total_available' => $substitutes->count()
            ]
        ]);
    }
}