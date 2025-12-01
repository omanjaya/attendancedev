<?php

namespace App\Http\Controllers\Api;

use App\Models\WeeklySchedule;
use App\Models\MonthlySchedule;
use App\Models\TimeSlot;
use App\Models\Subject;
use App\Models\AcademicClass;
use App\Models\Employee;
use Illuminate\Http\Request;

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

        $schedule = WeeklySchedule::create(array_merge($validated, [
            'is_active' => true,
            'effective_from' => now(),
        ]));

        return $this->apiResponse($schedule->load(['employee', 'subject', 'timeSlot']), 'Schedule created', 201);
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

        if (isset($validated['status'])) {
            $validated['is_active'] = $validated['status'] === 'active';
            unset($validated['status']);
        }

        $schedule->update($validated);

        return $this->apiResponse($schedule->fresh()->load(['employee', 'subject', 'timeSlot']), 'Schedule updated');
    }

    public function destroy($id)
    {
        $schedule = WeeklySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Schedule not found', 404);
        }

        $schedule->delete();

        return $this->apiResponse(null, 'Schedule deleted');
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
            ->selectRaw('count(*) as count')
            ->groupBy('day_of_week', 'time_slot_id')
            ->having('count', '>', 1)
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
}
