<?php

namespace App\Http\Controllers\Api;

use App\Models\MonthlySchedule;
use App\Models\Employee;
use App\Models\EmployeeMonthlySchedule;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MonthlyScheduleApiController extends BaseApiController
{
    /**
     * Get all monthly schedules
     */
    public function index(Request $request)
    {
        $query = MonthlySchedule::query()
            ->with(['location:id,name', 'creator:id,name']);

        // Filters
        if ($month = $request->get('month')) {
            $query->where('month', $month);
        }

        if ($year = $request->get('year')) {
            $query->where('year', $year);
        }

        if ($locationId = $request->get('location_id')) {
            $query->where('location_id', $locationId);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sort
        $query->orderBy('year', 'desc')
            ->orderBy('month', 'desc');

        $perPage = $request->get('per_page', 15);
        $schedules = $query->paginate($perPage);

        // Add assigned employees count to each schedule
        $schedules->getCollection()->transform(function ($schedule) {
            $schedule->assigned_employees_count = $schedule->getAssignedEmployeesCount();
            return $schedule;
        });

        return $this->paginatedResponse($schedules, 'Monthly schedules retrieved');
    }

    /**
     * Create new monthly schedule
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2024|max:2030',
            'location_id' => 'required|uuid|exists:locations,id',
            'default_start_time' => 'required|date_format:H:i',
            'default_end_time' => 'required|date_format:H:i|after:default_start_time',
            'checkin_start_time' => 'required|date_format:H:i',
            'checkin_end_time' => 'required|date_format:H:i|after:checkin_start_time',
            'checkout_start_time' => 'required|date_format:H:i',
            'checkout_end_time' => 'required|date_format:H:i|after:checkout_start_time',
            'working_days' => 'required|array',
            'working_days.*' => 'date_format:Y-m-d',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        // Calculate start_date and end_date from month/year
        $startDate = Carbon::createFromDate($validated['year'], $validated['month'], 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $schedule = MonthlySchedule::create(array_merge($validated, [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_working_days' => count($validated['working_days']),
            'is_active' => true,
            'created_by' => auth()->id(),
        ]));

        return $this->apiResponse(
            $schedule->load(['location', 'creator']),
            'Monthly schedule created successfully',
            201
        );
    }

    /**
     * Get specific schedule
     */
    public function show($id)
    {
        $schedule = MonthlySchedule::with(['location', 'creator', 'employeeSchedules.employee'])
            ->find($id);

        if (!$schedule) {
            return $this->errorResponse('Schedule not found', 404);
        }

        // Add stats
        $schedule->assigned_employees_count = $schedule->getAssignedEmployeesCount();
        $schedule->holiday_conflicts = $schedule->getHolidayConflicts();

        return $this->apiResponse($schedule, 'Schedule retrieved');
    }

    /**
     * Update schedule
     */
    public function update(Request $request, $id)
    {
        $schedule = MonthlySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Schedule not found', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'default_start_time' => 'sometimes|date_format:H:i',
            'default_end_time' => 'sometimes|date_format:H:i',
            'checkin_start_time' => 'sometimes|date_format:H:i',
            'checkin_end_time' => 'sometimes|date_format:H:i',
            'checkout_start_time' => 'sometimes|date_format:H:i',
            'checkout_end_time' => 'sometimes|date_format:H:i',
            'working_days' => 'sometimes|array',
            'working_days.*' => 'date_format:Y-m-d',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'metadata' => 'nullable|array',
        ]);

        if (isset($validated['working_days'])) {
            $validated['total_working_days'] = count($validated['working_days']);
        }

        $validated['updated_by'] = auth()->id();
        $schedule->update($validated);

        return $this->apiResponse(
            $schedule->fresh(['location', 'creator']),
            'Schedule updated successfully'
        );
    }

    /**
     * Delete schedule
     */
    public function destroy($id)
    {
        $schedule = MonthlySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Schedule not found', 404);
        }

        // Hard delete employee schedules first (as per user requirement)
        EmployeeMonthlySchedule::where('monthly_schedule_id', $schedule->id)->delete();

        $schedule->delete();

        return $this->apiResponse(null, 'Schedule deleted successfully');
    }

    /**
     * Assign schedule to employees (with auto-replace logic)
     */
    public function assign(Request $request, $id)
    {
        $schedule = MonthlySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Schedule not found', 404);
        }

        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'uuid|exists:employees,id',
        ]);

        $employeeIds = $validated['employee_ids'];
        $assignedCount = 0;
        $replacedCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($employeeIds as $employeeId) {
                $employee = Employee::find($employeeId);

                if (!$employee) {
                    $errors[] = "Employee {$employeeId} not found";
                    continue;
                }

                // Check for conflicts (existing schedules in same month/year)
                $conflictingSchedules = EmployeeMonthlySchedule::where('employee_id', $employeeId)
                    ->whereHas('monthlySchedule', function ($query) use ($schedule) {
                        $query->where('month', $schedule->month)
                            ->where('year', $schedule->year);
                    })
                    ->get();

                // Auto-replace: Hard delete conflicting schedules
                if ($conflictingSchedules->isNotEmpty()) {
                    EmployeeMonthlySchedule::where('employee_id', $employeeId)
                        ->whereHas('monthlySchedule', function ($query) use ($schedule) {
                            $query->where('month', $schedule->month)
                                ->where('year', $schedule->year);
                        })
                        ->forceDelete();

                    $replacedCount++;
                }

                // Assign employee to schedule
                if ($schedule->assignEmployee($employee)) {
                    $assignedCount++;
                } else {
                    $errors[] = "Failed to assign {$employee->full_name}";
                }
            }

            DB::commit();

            return $this->apiResponse([
                'assigned_count' => $assignedCount,
                'replaced_count' => $replacedCount,
                'total_employees' => count($employeeIds),
                'errors' => $errors,
            ], "Successfully assigned {$assignedCount} employees" . ($replacedCount > 0 ? " ({$replacedCount} replaced)" : ""));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign employees to schedule', [
                'schedule_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to assign employees: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get employees assigned to schedule
     */
    public function getEmployees($id)
    {
        $schedule = MonthlySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Schedule not found', 404);
        }

        $employees = Employee::whereHas('monthlySchedules', function ($query) use ($id) {
            $query->where('monthly_schedule_id', $id);
        })->with(['user', 'location'])->get();

        return $this->apiResponse(
            \App\Http\Resources\EmployeeResource::collection($employees),
            'Assigned employees retrieved'
        );
    }

    /**
     * Unassign employee from schedule
     */
    public function unassign(Request $request, $id)
    {
        $schedule = MonthlySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Schedule not found', 404);
        }

        $validated = $request->validate([
            'employee_id' => 'required|uuid|exists:employees,id',
        ]);

        $deleted = EmployeeMonthlySchedule::where('monthly_schedule_id', $id)
            ->where('employee_id', $validated['employee_id'])
            ->delete();

        if ($deleted === 0) {
            return $this->errorResponse('Employee not assigned to this schedule', 404);
        }

        return $this->apiResponse(null, 'Employee unassigned successfully');
    }

    /**
     * Generate working days for a month (excluding holidays)
     */
    public function generateWorkingDays(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2024|max:2030',
            'working_day_pattern' => 'required|array', // e.g., ["monday", "tuesday", "wednesday", "thursday", "friday"]
            'working_day_pattern.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);

        $startDate = Carbon::createFromDate($validated['year'], $validated['month'], 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = [];

        // Get holidays for this month
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])
            ->where('status', 'active')
            ->pluck('date')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();

        // Generate working days
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dayName = strtolower($current->format('l'));

            // Check if this day is in the working pattern and not a holiday
            if (
                in_array($dayName, $validated['working_day_pattern']) &&
                !in_array($current->format('Y-m-d'), $holidays)
            ) {
                $workingDays[] = $current->format('Y-m-d');
            }

            $current->addDay();
        }

        return $this->apiResponse([
            'working_days' => $workingDays,
            'total_working_days' => count($workingDays),
            'total_holidays' => count($holidays),
            'holidays' => $holidays,
        ], 'Working days generated');
    }

    /**
     * Get current employee's assigned schedule
     * For employees to view their own schedule
     */
    public function mySchedule(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        $employee = $user->employee;

        // Get filters
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        // Get the assigned schedule for this employee in specified month/year
        $employeeSchedule = EmployeeMonthlySchedule::where('employee_id', $employee->id)
            ->whereHas('monthlySchedule', function ($query) use ($month, $year) {
                $query->where('month', $month)
                    ->where('year', $year)
                    ->where('is_active', true);
            })
            ->with(['monthlySchedule.location'])
            ->first();

        if (!$employeeSchedule) {
            return $this->apiResponse(null, 'No schedule assigned for this period', 200);
        }

        $schedule = $employeeSchedule->monthlySchedule;

        return $this->apiResponse([
            'schedule' => $schedule,
            'assigned_at' => $employeeSchedule->assigned_at,
            'employee' => [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'position' => $employee->position,
            ]
        ], 'Employee schedule retrieved');
    }
}
