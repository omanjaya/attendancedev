<?php

namespace App\Http\Controllers\Api;

use App\Models\WeeklySchedule;
use App\Services\Schedule\ScheduleService;
use App\Services\Schedule\MonthlyScheduleService;
use App\Services\Schedule\TeachingScheduleService;
use App\Services\Schedule\ScheduleHelperService;
use Illuminate\Http\Request;

class ScheduleApiController extends BaseApiController
{
    public function __construct(
        private ScheduleService $scheduleService,
        private MonthlyScheduleService $monthlyService,
        private TeachingScheduleService $teachingService,
        private ScheduleHelperService $helperService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['class_id', 'employee_id', 'subject_id', 'day_of_week', 'status']);
        $schedules = $this->scheduleService->getSchedules($filters, $request->get('per_page', 15));
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
        $schedules = $this->scheduleService->getSchedulesByClass($classId);
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
            'is_active' => 'boolean',
        ]);

        // Check conflicts
        $conflicts = $this->helperService->checkConflicts($validated);
        if (!empty($conflicts)) {
            return $this->errorResponse('Schedule conflicts detected', 422, ['conflicts' => $conflicts]);
        }

        try {
            $schedule = $this->scheduleService->createSchedule($validated);
            return $this->apiResponse($schedule->load(['employee', 'academicClass', 'subject', 'timeSlot']), 'Schedule created', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create schedule: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $schedule = WeeklySchedule::find($id);
        if (!$schedule) {
            return $this->errorResponse('Schedule not found', 404);
        }

        $validated = $request->validate([
            'academic_class_id' => 'sometimes|exists:academic_classes,id',
            'employee_id' => 'sometimes|exists:employees,id',
            'subject_id' => 'sometimes|exists:subjects,id',
            'time_slot_id' => 'sometimes|exists:time_slots,id',
            'day_of_week' => 'sometimes|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'is_active' => 'boolean',
        ]);

        // Check conflicts
        $validated['id'] = $id;
        $conflicts = $this->helperService->checkConflicts($validated);
        if (!empty($conflicts)) {
            return $this->errorResponse('Schedule conflicts detected', 422, ['conflicts' => $conflicts]);
        }

        try {
            $schedule = $this->scheduleService->updateSchedule($schedule, $validated);
            return $this->apiResponse($schedule, 'Schedule updated');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update schedule: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $schedule = WeeklySchedule::find($id);
        if (!$schedule) {
            return $this->errorResponse('Schedule not found', 404);
        }

        try {
            $this->scheduleService->deleteSchedule($schedule);
            return $this->apiResponse(null, 'Schedule deleted');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete schedule: ' . $e->getMessage(), 500);
        }
    }

    public function lock(Request $request, $id)
    {
        $schedule = WeeklySchedule::find($id);
        if (!$schedule) {
            return $this->errorResponse('Schedule not found', 404);
        }

        $schedule = $this->scheduleService->lockSchedule($schedule);
        return $this->apiResponse($schedule, 'Schedule locked');
    }

    public function unlock($id)
    {
        $schedule = WeeklySchedule::find($id);
        if (!$schedule) {
            return $this->errorResponse('Schedule not found', 404);
        }

        $schedule = $this->scheduleService->unlockSchedule($schedule);
        return $this->apiResponse($schedule, 'Schedule unlocked');
    }

    public function statistics()
    {
        $stats = $this->helperService->getStatistics();
        return $this->apiResponse($stats, 'Statistics retrieved');
    }

    public function conflicts(Request $request)
    {
        $validated = $request->validate([
            'academic_class_id' => 'required|exists:academic_classes,id',
            'employee_id' => 'required|exists:employees,id',
            'time_slot_id' => 'required|exists:time_slots,id',
            'day_of_week' => 'required|string',
        ]);

        $conflicts = $this->helperService->checkConflicts($validated);
        return $this->apiResponse(['conflicts' => $conflicts], 'Conflicts checked');
    }

    public function timeSlots()
    {
        return $this->apiResponse($this->helperService->getTimeSlots(), 'Time slots retrieved');
    }

    public function subjects()
    {
        return $this->apiResponse($this->helperService->getSubjects(), 'Subjects retrieved');
    }

    public function classes()
    {
        return $this->apiResponse($this->helperService->getClasses(), 'Classes retrieved');
    }

    public function availableTeachers(Request $request)
    {
        $validated = $request->validate([
            'day_of_week' => 'required|string',
            'time_slot_id' => 'required|exists:time_slots,id',
        ]);

        $teachers = $this->helperService->getAvailableTeachers($validated['day_of_week'], $validated['time_slot_id']);
        return $this->apiResponse($teachers, 'Available teachers retrieved');
    }

    public function monthlySchedules(Request $request)
    {
        $filters = $request->only(['year', 'month', 'is_active']);
        $schedules = $this->monthlyService->getMonthlySchedules($filters);
        return $this->apiResponse($schedules, 'Monthly schedules retrieved');
    }

    public function showMonthly($id)
    {
        $schedule = \App\Models\MonthlySchedule::with('employeeSchedules.employee')->find($id);
        if (!$schedule) {
            return $this->errorResponse('Monthly schedule not found', 404);
        }
        return $this->apiResponse($schedule, 'Monthly schedule retrieved');
    }

    public function storeMonthly(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'is_active' => 'boolean',
            'employees' => 'nullable|array',
        ]);

        $validated['created_by'] = $request->user()->id;

        try {
            $schedule = $this->monthlyService->createMonthlySchedule($validated);
            return $this->apiResponse($schedule, 'Monthly schedule created', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create monthly schedule: ' . $e->getMessage(), 500);
        }
    }

    public function publishMonthly($id)
    {
        $schedule = \App\Models\MonthlySchedule::find($id);
        if (!$schedule) {
            return $this->errorResponse('Monthly schedule not found', 404);
        }

        try {
            $schedule = $this->monthlyService->publishMonthlySchedule($schedule);
            return $this->apiResponse($schedule, 'Monthly schedule published');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to publish: ' . $e->getMessage(), 500);
        }
    }

    public function destroyMonthly($id)
    {
        $schedule = \App\Models\MonthlySchedule::find($id);
        if (!$schedule) {
            return $this->errorResponse('Monthly schedule not found', 404);
        }

        try {
            $this->monthlyService->deleteMonthlySchedule($schedule);
            return $this->apiResponse(null, 'Monthly schedule deleted');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete: ' . $e->getMessage(), 500);
        }
    }

    public function bulkImportTeachingSchedules(Request $request)
    {
        $validated = $request->validate([
            'schedules' => 'required|array',
            'schedules.*.employee_id' => 'required|exists:employees,id',
            'schedules.*.subject_id' => 'required|exists:subjects,id',
            'schedules.*.academic_class_id' => 'required|exists:academic_classes,id',
            'schedules.*.date' => 'required|date',
            'schedules.*.start_time' => 'required',
            'schedules.*.end_time' => 'required',
        ]);

        try {
            $result = $this->teachingService->bulkImportTeachingSchedules($validated['schedules']);
            return $this->apiResponse($result, 'Bulk import completed');
        } catch (\Exception $e) {
            return $this->errorResponse('Bulk import failed: ' . $e->getMessage(), 500);
        }
    }

    public function calendar(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

        $schedules = \App\Models\MonthlySchedule::whereBetween('effective_date', [$startDate, $endDate])
            ->with('employee')
            ->get()
            ->groupBy(fn($s) => $s->effective_date->format('Y-m-d'));

        return $this->apiResponse($schedules, 'Calendar schedules retrieved');
    }

    /**
     * Get teaching schedules for admin/management
     */
    public function getTeachingSchedules(Request $request)
    {
        $filters = $request->only(['teacher_id', 'subject_id', 'day_of_week', 'is_active']);

        $query = \App\Models\TeachingSchedule::with(['teacher', 'subject'])
            ->where('is_active', true)
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', now());
            });

        if (!empty($filters['teacher_id'])) {
            $query->where('teacher_id', $filters['teacher_id']);
        }
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }
        if (!empty($filters['day_of_week'])) {
            $query->where('day_of_week', $filters['day_of_week']);
        }

        $schedules = $query->orderBy('day_of_week')
            ->orderBy('teaching_start_time')
            ->get();

        return $this->apiResponse($schedules, 'Teaching schedules retrieved');
    }

    /**
     * Get current user's (teacher) teaching schedules - "Jadwal Mengajar Saya"
     */
    public function myTeachingSchedules(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        $now = now();

        // Get all active teaching schedules for this employee
        $schedules = \App\Models\TeachingSchedule::with(['subject'])
            ->where('teacher_id', $employee->id)
            ->where('is_active', true)
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', $now);
            })
            ->orderBy('day_of_week')
            ->orderBy('teaching_start_time')
            ->get();

        // Group by day of week for easier display
        $grouped = $schedules->groupBy('day_of_week');

        // Calculate statistics
        $totalHoursPerWeek = $schedules->sum(function ($schedule) {
            return $schedule->teaching_duration_hours ?? 0;
        });

        $totalSessions = $schedules->count();

        // Get today's schedule
        $todayDayOfWeek = strtolower($now->format('l'));
        $todaySchedules = $schedules->where('day_of_week', $todayDayOfWeek)->values();

        // Day order for sorting
        $dayOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $sortedGrouped = collect($dayOrder)
            ->filter(fn($day) => $grouped->has($day))
            ->mapWithKeys(fn($day) => [$day => $grouped->get($day)]);

        return $this->apiResponse([
            'schedules' => $sortedGrouped,
            'today' => [
                'day_of_week' => $todayDayOfWeek,
                'schedules' => $todaySchedules,
                'total_hours' => $todaySchedules->sum(fn($s) => $s->teaching_duration_hours ?? 0),
            ],
            'statistics' => [
                'total_sessions_per_week' => $totalSessions,
                'total_hours_per_week' => round($totalHoursPerWeek, 2),
                'subjects_count' => $schedules->pluck('subject_id')->unique()->count(),
                'classes_count' => $schedules->pluck('class_name')->unique()->count(),
            ],
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'type' => $employee->employee_type,
                'is_guru_honorer' => $employee->isGuruHonorer(),
            ],
        ], 'My teaching schedules retrieved');
    }

    /**
     * Clear teaching schedules (admin only)
     */
    public function clearTeachingSchedules(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'nullable|exists:employees,id',
            'confirm' => 'required|boolean|accepted',
        ]);

        try {
            $query = \App\Models\TeachingSchedule::query();

            if (!empty($validated['teacher_id'])) {
                $query->where('teacher_id', $validated['teacher_id']);
            }

            $count = $query->count();
            $query->delete();

            return $this->apiResponse(['deleted_count' => $count], 'Teaching schedules cleared');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to clear teaching schedules: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Match teachers for bulk import (helper endpoint)
     */
    public function matchTeachers(Request $request)
    {
        $validated = $request->validate([
            'names' => 'required|array',
            'names.*' => 'required|string',
        ]);

        $results = [];
        foreach ($validated['names'] as $name) {
            $employee = \App\Models\Employee::where('full_name', 'like', "%{$name}%")
                ->orWhere('employee_id', $name)
                ->first();

            $results[] = [
                'input' => $name,
                'matched' => $employee ? [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'employee_id' => $employee->employee_id,
                ] : null,
            ];
        }

        return $this->apiResponse($results, 'Teachers matched');
    }
}
