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
            'is_active' => 'boolean',
            'employees' => 'nullable|array',
            'employees.*.employee_id' => 'required|exists:employees,id',
            'employees.*.schedule_data' => 'nullable|array',
        ]);

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
            'is_active' => 'boolean',
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
}
