<?php

namespace App\Http\Controllers\Api;

use App\Models\MonthlySchedule;
use App\Services\Schedule\MonthlyScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MonthlyScheduleApiController extends BaseApiController
{
    public function __construct(
        private MonthlyScheduleService $monthlyService
    ) {}

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['year', 'month', 'is_active']);
            $schedules = $this->monthlyService->getMonthlySchedules($filters);
            return $this->apiResponse($schedules, 'Monthly schedules retrieved');
        } catch (\Exception $e) {
            Log::error('DEBUG INDEX ERROR: ' . $e->getMessage());
            return $this->errorResponse('DEBUG ERROR: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $schedule = MonthlySchedule::with(['employeeSchedules.employee', 'creator'])->find($id);
        
        if (!$schedule) {
            return $this->errorResponse('Monthly schedule not found', 404);
        }

        return $this->apiResponse($schedule, 'Monthly schedule retrieved');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'default_start_time' => 'required|date_format:H:i',
            'default_end_time' => 'required|date_format:H:i|after:default_start_time',
            'checkin_start_time' => 'nullable|date_format:H:i',
            'checkin_end_time' => 'nullable|date_format:H:i',
            'checkout_start_time' => 'nullable|date_format:H:i',
            'checkout_end_time' => 'nullable|date_format:H:i',
            'working_days' => 'nullable|array',
            'working_days.*' => 'date',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
            'employees' => 'nullable|array',
            'employees.*.employee_id' => 'required|exists:employees,id',
            'employees.*.schedule_data' => 'nullable|array',
        ]);

        // Calculate start_date and end_date from month/year if not provided
        if (empty($validated['start_date'])) {
            $validated['start_date'] = \Carbon\Carbon::createFromDate($validated['year'], $validated['month'], 1)->startOfMonth()->format('Y-m-d');
        }
        if (empty($validated['end_date'])) {
            $validated['end_date'] = \Carbon\Carbon::createFromDate($validated['year'], $validated['month'], 1)->endOfMonth()->format('Y-m-d');
        }

        $validated['created_by'] = $request->user()->id;

        try {
            $schedule = $this->monthlyService->createMonthlySchedule($validated);
            return $this->apiResponse($schedule->load('employeeSchedules.employee'), 'Monthly schedule created', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create monthly schedule: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $schedule = MonthlySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Monthly schedule not found', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'year' => 'sometimes|integer|min:2020|max:2100',
            'month' => 'sometimes|integer|min:1|max:12',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'default_start_time' => 'sometimes|date_format:H:i',
            'default_end_time' => 'sometimes|date_format:H:i',
            'checkin_start_time' => 'nullable|date_format:H:i',
            'checkin_end_time' => 'nullable|date_format:H:i',
            'checkout_start_time' => 'nullable|date_format:H:i',
            'checkout_end_time' => 'nullable|date_format:H:i',
            'working_days' => 'nullable|array',
            'working_days.*' => 'date',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
            'employees' => 'nullable|array',
            'employees.*.employee_id' => 'required|exists:employees,id',
            'employees.*.schedule_data' => 'nullable|array',
        ]);

        try {
            $schedule = $this->monthlyService->updateMonthlySchedule($schedule, $validated);
            return $this->apiResponse($schedule->load('employeeSchedules.employee'), 'Monthly schedule updated');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update monthly schedule: ' . $e->getMessage(), 500);
        }
    }

    public function publish($id)
    {
        $schedule = MonthlySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Monthly schedule not found', 404);
        }

        try {
            $schedule = $this->monthlyService->publishMonthlySchedule($schedule);
            return $this->apiResponse($schedule, 'Monthly schedule published successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to publish monthly schedule: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $schedule = MonthlySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Monthly schedule not found', 404);
        }

        try {
            $this->monthlyService->deleteMonthlySchedule($schedule);
            return $this->apiResponse(null, 'Monthly schedule deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete monthly schedule: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get authenticated employee's schedule for current or specified month
     */
    public function mySchedule(Request $request)
    {
        $user = $request->user();

        if (!$user->employee) {
            return $this->errorResponse('User tidak memiliki data karyawan', 404);
        }

        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        try {
            // Get the monthly schedule for the employee
            $employeeSchedule = \App\Models\EmployeeMonthlySchedule::with(['monthlySchedule'])
                ->where('employee_id', $user->employee->id)
                ->whereHas('monthlySchedule', function ($q) use ($year, $month) {
                    $q->where('year', $year)
                      ->where('month', $month)
                      ->where('is_active', true);
                })
                ->first();

            if (!$employeeSchedule) {
                // Return empty schedule with default info
                return $this->apiResponse([
                    'has_schedule' => false,
                    'year' => (int) $year,
                    'month' => (int) $month,
                    'employee' => [
                        'id' => $user->employee->id,
                        'name' => $user->employee->full_name,
                    ],
                    'schedule' => null,
                ], 'Tidak ada jadwal untuk bulan ini');
            }

            $monthlySchedule = $employeeSchedule->monthlySchedule;

            $workingDays = $monthlySchedule->working_days ?? [];

            // Format time fields properly (HH:mm format)
            $formatTime = fn($time) => $time ? \Carbon\Carbon::parse($time)->format('H:i') : null;

            return $this->apiResponse([
                'has_schedule' => true,
                'year' => (int) $year,
                'month' => (int) $month,
                'employee' => [
                    'id' => $user->employee->id,
                    'name' => $user->employee->full_name,
                ],
                'schedule' => [
                    'id' => $monthlySchedule->id,
                    'name' => $monthlySchedule->name,
                    'month' => $monthlySchedule->month,
                    'year' => $monthlySchedule->year,
                    'start_date' => $monthlySchedule->start_date?->format('Y-m-d'),
                    'end_date' => $monthlySchedule->end_date?->format('Y-m-d'),
                    'default_start_time' => $formatTime($monthlySchedule->getRawOriginal('default_start_time')),
                    'default_end_time' => $formatTime($monthlySchedule->getRawOriginal('default_end_time')),
                    'checkin_start_time' => $formatTime($monthlySchedule->getRawOriginal('checkin_start_time')),
                    'checkin_end_time' => $formatTime($monthlySchedule->getRawOriginal('checkin_end_time')),
                    'checkout_start_time' => $formatTime($monthlySchedule->getRawOriginal('checkout_start_time')),
                    'checkout_end_time' => $formatTime($monthlySchedule->getRawOriginal('checkout_end_time')),
                    'working_days' => $workingDays,
                    'total_working_days' => count($workingDays),
                    'is_active' => $monthlySchedule->is_active,
                    'schedule_data' => $employeeSchedule->schedule_data,
                ],
                'assigned_at' => $employeeSchedule->created_at?->toISOString(),
            ], 'Jadwal berhasil diambil');
        } catch (\Exception $e) {
            Log::error('mySchedule error: ' . $e->getMessage());
            return $this->errorResponse('Gagal mengambil jadwal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate working days for a month based on pattern
     */
    public function generateWorkingDays(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'working_day_pattern' => 'required|array',
            'working_day_pattern.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);

        try {
            $result = $this->monthlyService->generateWorkingDays(
                $validated['year'],
                $validated['month'],
                $validated['working_day_pattern']
            );

            return $this->apiResponse($result, 'Working days generated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to generate working days: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Assign employees to a monthly schedule
     */
    public function assign(Request $request, $id)
    {
        $schedule = MonthlySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Monthly schedule not found', 404);
        }

        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|exists:employees,id',
            'schedule_data' => 'nullable|array',
        ]);

        try {
            $assigned = [];
            $replaced = [];

            foreach ($validated['employee_ids'] as $employeeId) {
                // Get employee to fetch location_id
                $employee = \App\Models\Employee::find($employeeId);
                if (!$employee) {
                    continue;
                }

                // Get default location (use employee's location or first available location)
                $locationId = $employee->location_id ?? \App\Models\Location::first()?->id;

                // Check if already assigned to THIS schedule
                $existingInThisSchedule = \App\Models\EmployeeMonthlySchedule::where('monthly_schedule_id', $schedule->id)
                    ->where('employee_id', $employeeId)
                    ->first();

                if ($existingInThisSchedule) {
                    // Already assigned to this schedule, update it
                    $existingInThisSchedule->update([
                        'effective_date' => $schedule->start_date,
                        'start_time' => $schedule->default_start_time,
                        'end_time' => $schedule->default_end_time,
                        'location_id' => $locationId,
                        'modified_by' => $request->user()->id,
                    ]);
                    $replaced[] = $employeeId;
                    continue;
                }

                // Check if assigned to OTHER schedule for same month/year - remove old assignment
                $existingOther = \App\Models\EmployeeMonthlySchedule::whereHas('monthlySchedule', function ($q) use ($schedule) {
                    $q->where('year', $schedule->year)
                      ->where('month', $schedule->month)
                      ->where('id', '!=', $schedule->id);
                })->where('employee_id', $employeeId)->first();

                if ($existingOther) {
                    $existingOther->delete();
                    $replaced[] = $employeeId;
                }

                \App\Models\EmployeeMonthlySchedule::create([
                    'monthly_schedule_id' => $schedule->id,
                    'employee_id' => $employeeId,
                    'effective_date' => $schedule->start_date,
                    'start_time' => $schedule->default_start_time,
                    'end_time' => $schedule->default_end_time,
                    'location_id' => $locationId,
                    'override_metadata' => $validated['schedule_data'] ?? [],
                    'assigned_by' => $request->user()->id,
                ]);

                $assigned[] = $employeeId;
            }

            $totalAssigned = count($assigned) + count($replaced);
            return $this->apiResponse([
                'assigned' => $assigned,
                'replaced' => $replaced,
                'assigned_count' => $totalAssigned,
                'replaced_count' => count($replaced),
                'total_employees' => $totalAssigned,
                'errors' => [],
                'schedule' => $schedule->load('employeeSchedules.employee'),
            ], $totalAssigned . ' employee(s) assigned successfully');
        } catch (\Exception $e) {
            Log::error('Assign employees error: ' . $e->getMessage());
            return $this->errorResponse('Failed to assign employees: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get employees assigned to a monthly schedule
     */
    public function getEmployees($id)
    {
        $schedule = MonthlySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Monthly schedule not found', 404);
        }

        try {
            $employees = \App\Models\EmployeeMonthlySchedule::where('monthly_schedule_id', $schedule->id)
                ->with(['employee.location'])
                ->get()
                ->map(function ($es) {
                    $employee = $es->employee;
                    return [
                        'id' => $employee->id,
                        'employee_id' => $employee->employee_id,
                        'full_name' => $employee->full_name,
                        'name' => $employee->full_name, // alias
                        'position' => $employee->metadata['position'] ?? null,
                        'department' => $employee->metadata['department'] ?? null,
                        'location' => $employee->location ? [
                            'id' => $employee->location->id,
                            'name' => $employee->location->name,
                        ] : null,
                        'assigned_at' => $es->created_at?->toISOString(),
                    ];
                });

            return $this->apiResponse($employees, 'Employees retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get schedule employees error: ' . $e->getMessage());
            return $this->errorResponse('Failed to get employees: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Unassign employees from a monthly schedule
     */
    public function unassign(Request $request, $id)
    {
        $schedule = MonthlySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Monthly schedule not found', 404);
        }

        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|exists:employees,id',
        ]);

        try {
            $deleted = \App\Models\EmployeeMonthlySchedule::where('monthly_schedule_id', $schedule->id)
                ->whereIn('employee_id', $validated['employee_ids'])
                ->delete();

            return $this->apiResponse([
                'unassigned_count' => $deleted,
                'schedule' => $schedule->load('employeeSchedules.employee'),
            ], $deleted . ' employee(s) unassigned successfully');
        } catch (\Exception $e) {
            Log::error('Unassign employees error: ' . $e->getMessage());
            return $this->errorResponse('Failed to unassign employees: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Sync employees to a monthly schedule (assign selected, unassign unselected)
     */
    public function sync(Request $request, $id)
    {
        $schedule = MonthlySchedule::find($id);

        if (!$schedule) {
            return $this->errorResponse('Monthly schedule not found', 404);
        }

        $validated = $request->validate([
            'employee_ids' => 'present|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        try {
            $newEmployeeIds = $validated['employee_ids'] ?? [];

            // Get currently assigned employee IDs
            $currentAssignments = \App\Models\EmployeeMonthlySchedule::where('monthly_schedule_id', $schedule->id)
                ->pluck('employee_id')
                ->toArray();

            // Calculate who to add and who to remove
            $toAdd = array_diff($newEmployeeIds, $currentAssignments);
            $toRemove = array_diff($currentAssignments, $newEmployeeIds);

            // Remove unselected employees (force delete to avoid soft-delete issues)
            $unassignedCount = 0;
            if (!empty($toRemove)) {
                $unassignedCount = \App\Models\EmployeeMonthlySchedule::where('monthly_schedule_id', $schedule->id)
                    ->whereIn('employee_id', $toRemove)
                    ->forceDelete();
            }

            // Add newly selected employees
            $assignedCount = 0;
            foreach ($toAdd as $employeeId) {
                $employee = \App\Models\Employee::find($employeeId);
                if (!$employee) {
                    continue;
                }

                $locationId = $employee->location_id ?? \App\Models\Location::first()?->id;

                // Remove any existing schedule for this employee in the same month/year
                // (from other schedules) to avoid unique constraint violation
                // Use forceDelete to permanently remove (including soft-deleted)
                \App\Models\EmployeeMonthlySchedule::withTrashed()
                    ->whereHas('monthlySchedule', function ($q) use ($schedule) {
                        $q->where('year', $schedule->year)
                          ->where('month', $schedule->month);
                    })->where('employee_id', $employeeId)
                      ->where('monthly_schedule_id', '!=', $schedule->id)
                      ->forceDelete();

                // Also remove any existing assignment with the same effective_date (including soft-deleted)
                \App\Models\EmployeeMonthlySchedule::withTrashed()
                    ->where('employee_id', $employeeId)
                    ->where('effective_date', $schedule->start_date)
                    ->forceDelete();

                \App\Models\EmployeeMonthlySchedule::create([
                    'monthly_schedule_id' => $schedule->id,
                    'employee_id' => $employeeId,
                    'effective_date' => $schedule->start_date,
                    'start_time' => $schedule->default_start_time,
                    'end_time' => $schedule->default_end_time,
                    'location_id' => $locationId,
                    'override_metadata' => [],
                    'assigned_by' => $request->user()->id,
                ]);
                $assignedCount++;
            }

            return $this->apiResponse([
                'assigned_count' => $assignedCount,
                'unassigned_count' => $unassignedCount,
                'total_employees' => count($newEmployeeIds),
            ], "Sync berhasil: {$assignedCount} ditambah, {$unassignedCount} dihapus");
        } catch (\Exception $e) {
            Log::error('Sync employees error: ' . $e->getMessage());
            return $this->errorResponse('Failed to sync employees: ' . $e->getMessage(), 500);
        }
    }
}
