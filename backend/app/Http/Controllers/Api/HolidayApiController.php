<?php

namespace App\Http\Controllers\Api;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HolidayApiController extends BaseApiController
{
    /**
     * Get all holidays
     */
    public function index(Request $request)
    {
        $query = Holiday::query();

        // Filter by year
        if ($year = $request->get('year')) {
            $query->whereYear('date', $year);
        }

        // Filter by type
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by date range
        if ($startDate = $request->get('start_date')) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate = $request->get('end_date')) {
            $query->where('date', '<=', $endDate);
        }

        // Sort
        $query->orderBy('date', 'asc');

        $perPage = $request->get('per_page', 50);
        $holidays = $query->paginate($perPage);

        return $this->paginatedResponse($holidays, 'Holidays retrieved');
    }

    /**
     * Get holidays for a specific month
     */
    public function getByMonth(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2024|max:2030',
        ]);

        $startDate = Carbon::createFromDate($validated['year'], $validated['month'], 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])
            ->where('status', 'active')
            ->orderBy('date')
            ->get();

        return $this->apiResponse([
            'holidays' => $holidays,
            'count' => $holidays->count(),
            'month' => $validated['month'],
            'year' => $validated['year'],
        ], 'Holidays retrieved for ' . $startDate->format('F Y'));
    }

    /**
     * Create new holiday (with upsert - updates if same name+date+type exists)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:date',
            'type' => 'required|in:public_holiday,religious_holiday,school_holiday,substitute_holiday',
            'status' => 'sometimes|in:active,cancelled,moved',
            'is_recurring' => 'sometimes|boolean',
            'recurring_pattern' => 'nullable|array',
            'affected_roles' => 'nullable|array',
            'source' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'is_paid' => 'sometimes|boolean',
            'metadata' => 'nullable|array',
        ]);

        $validated['status'] = $validated['status'] ?? 'active';
        $validated['is_recurring'] = $validated['is_recurring'] ?? false;
        $validated['is_paid'] = $validated['is_paid'] ?? true;
        $validated['color'] = $validated['color'] ?? '#dc3545';

        // Check for existing holiday with same name, date, and type (upsert logic)
        $existing = Holiday::where('name', $validated['name'])
            ->where('date', $validated['date'])
            ->where('type', $validated['type'])
            ->first();

        if ($existing) {
            // Update existing holiday instead of creating duplicate
            $existing->update($validated);
            return $this->apiResponse($existing->fresh(), 'Holiday updated (duplicate prevented)', 200);
        }

        $holiday = Holiday::create($validated);

        return $this->apiResponse($holiday, 'Holiday created successfully', 201);
    }

    /**
     * Get specific holiday
     */
    public function show($id)
    {
        $holiday = Holiday::find($id);

        if (!$holiday) {
            return $this->errorResponse('Holiday not found', 404);
        }

        return $this->apiResponse($holiday, 'Holiday retrieved');
    }

    /**
     * Update holiday
     */
    public function update(Request $request, $id)
    {
        $holiday = Holiday::find($id);

        if (!$holiday) {
            return $this->errorResponse('Holiday not found', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'date' => 'sometimes|date',
            'end_date' => 'nullable|date|after_or_equal:date',
            'type' => 'sometimes|in:public_holiday,religious_holiday,school_holiday,substitute_holiday',
            'status' => 'sometimes|in:active,cancelled,moved',
            'is_recurring' => 'sometimes|boolean',
            'recurring_pattern' => 'nullable|array',
            'affected_roles' => 'nullable|array',
            'source' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'is_paid' => 'sometimes|boolean',
            'metadata' => 'nullable|array',
        ]);

        $holiday->update($validated);

        return $this->apiResponse($holiday->fresh(), 'Holiday updated successfully');
    }

    /**
     * Delete holiday
     */
    public function destroy($id)
    {
        $holiday = Holiday::find($id);

        if (!$holiday) {
            return $this->errorResponse('Holiday not found', 404);
        }

        $holiday->delete();

        return $this->apiResponse(null, 'Holiday deleted successfully');
    }

    /**
     * Get holiday statistics by year
     */
    public function statistics(Request $request)
    {
        $year = $request->get('year', now()->year);
        $now = now();

        // Base query for the requested year
        $holidaysThisYear = Holiday::whereYear('date', $year)->get();
        
        // Active holidays this year
        $activeHolidays = $holidaysThisYear->where('status', 'active');

        // Holidays this month (current calendar month)
        $holidaysThisMonth = Holiday::whereYear('date', $now->year)
            ->whereMonth('date', $now->month)
            ->where('status', 'active')
            ->count();

        // Upcoming holiday
        $upcomingHoliday = Holiday::where('date', '>=', $now->toDateString())
            ->where('status', 'active')
            ->orderBy('date')
            ->first();

        $stats = [
            'total_holidays' => Holiday::count(),
            'active_holidays' => Holiday::where('status', 'active')->count(),
            'holidays_this_month' => $holidaysThisMonth,
            'holidays_this_year' => $activeHolidays->count(),
            'paid_holidays' => $activeHolidays->where('is_paid', true)->count(),
            'unpaid_holidays' => $activeHolidays->where('is_paid', false)->count(),
            'recurring_holidays' => $activeHolidays->where('is_recurring', true)->count(),
            'upcoming_holiday' => $upcomingHoliday,
        ];

        return $this->apiResponse($stats, 'Holiday statistics retrieved');
    }

    /**
     * Bulk import holidays
     */
    public function bulkImport(Request $request)
    {
        $validated = $request->validate([
            'holidays' => 'required|array',
            'holidays.*.name' => 'required|string|max:255',
            'holidays.*.date' => 'required|date',
            'holidays.*.type' => 'required|in:public_holiday,religious_holiday,school_holiday,substitute_holiday',
            'holidays.*.description' => 'nullable|string',
            'holidays.*.end_date' => 'nullable|date',
        ]);

        $imported = 0;
        $errors = [];

        foreach ($validated['holidays'] as $holidayData) {
            try {
                $holidayData['status'] = 'active';
                $holidayData['is_paid'] = true;
                $holidayData['color'] = '#dc3545';

                Holiday::create($holidayData);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Failed to import {$holidayData['name']}: " . $e->getMessage();
            }
        }

        return $this->apiResponse([
            'imported' => $imported,
            'total' => count($validated['holidays']),
            'errors' => $errors,
        ], "Successfully imported {$imported} holidays");
    }
}
