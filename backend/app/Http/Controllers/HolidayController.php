<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Services\Holiday\HolidayImportService;
use App\Services\Holiday\HolidayExportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function __construct(
        private HolidayImportService $importService,
        private HolidayExportService $exportService
    ) {
        $this->middleware('permission:view_holidays')->only(['index', 'show', 'calendar']);
        $this->middleware('permission:create_holidays')->only(['create', 'store']);
        $this->middleware('permission:edit_holidays')->only(['edit', 'update']);
        $this->middleware('permission:delete_holidays')->only(['destroy']);
        $this->middleware('permission:manage_holidays')->only(['import', 'export', 'generateRecurring']);
    }

    /**
     * Display a listing of holidays
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = Holiday::query();

        // Apply filters
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('year')) {
            $year = $request->year;
            // SQLite compatible year filtering
            $query->whereRaw("strftime('%Y', date) = ?", [$year]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'date');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        if ($request->expectsJson()) {
            $holidays = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $holidays->items(),
                'pagination' => [
                    'current_page' => $holidays->currentPage(),
                    'last_page' => $holidays->lastPage(),
                    'per_page' => $holidays->perPage(),
                    'total' => $holidays->total(),
                ],
            ]);
        }

        $holidays = $query->paginate(15);
        $types = Holiday::getTypes();
        $statuses = Holiday::getStatuses();
        // SQLite compatible year extraction with fallback for empty table
        try {
            $years = Holiday::selectRaw("DISTINCT strftime('%Y', date) as year")
                ->orderBy('year', 'desc')
                ->pluck('year');
            
            // If no years found, provide current and next year as fallback
            if ($years->isEmpty()) {
                $currentYear = date('Y');
                $years = collect([$currentYear, $currentYear + 1]);
            }
        } catch (\Exception $e) {
            // Fallback in case of any SQL issues
            $currentYear = date('Y');
            $years = collect([$currentYear, $currentYear + 1]);
        }

        return view('pages.holidays.index', compact(
            'holidays',
            'types',
            'statuses',
            'years'
        ));
    }

    /**
     * Show the form for creating a new holiday
     */
    public function create(): View
    {
        $types = Holiday::getTypes();
        $statuses = Holiday::getStatuses();

        return view('pages.holidays.create', compact('types', 'statuses'));
    }

    /**
     * Store a newly created holiday
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:date',
            'type' => 'required|in:'.implode(',', array_keys(Holiday::getTypes())),
            'status' => 'required|in:'.implode(',', array_keys(Holiday::getStatuses())),
            'is_recurring' => 'boolean',
            'recurring_pattern' => 'nullable|array',
            'affected_roles' => 'nullable|array',
            'color' => 'nullable|string|size:7|regex:/^#[a-fA-F0-9]{6}$/',
            'is_paid' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $holiday = Holiday::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Holiday created successfully',
                'data' => $holiday,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create holiday: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified holiday
     */
    public function show(Holiday $holiday): View|JsonResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $holiday,
            ]);
        }

        return view('pages.holidays.show', compact('holiday'));
    }

    /**
     * Show the form for editing the specified holiday
     */
    public function edit(Holiday $holiday): View
    {
        $types = Holiday::getTypes();
        $statuses = Holiday::getStatuses();

        return view('pages.holidays.edit', compact('holiday', 'types', 'statuses'));
    }

    /**
     * Update the specified holiday
     */
    public function update(Request $request, Holiday $holiday): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:date',
            'type' => 'required|in:'.implode(',', array_keys(Holiday::getTypes())),
            'status' => 'required|in:'.implode(',', array_keys(Holiday::getStatuses())),
            'is_recurring' => 'boolean',
            'recurring_pattern' => 'nullable|array',
            'affected_roles' => 'nullable|array',
            'color' => 'nullable|string|size:7|regex:/^#[a-fA-F0-9]{6}$/',
            'is_paid' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $holiday->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Holiday updated successfully',
                'data' => $holiday->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update holiday: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified holiday
     */
    public function destroy(Holiday $holiday): JsonResponse
    {
        try {
            $holiday->delete();

            return response()->json([
                'success' => true,
                'message' => 'Holiday deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete holiday: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display holiday calendar view
     */
    public function calendar(Request $request): View|JsonResponse
    {
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month');

        $query = Holiday::active();

        if ($month) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
        } else {
            $startDate = Carbon::create($year, 1, 1)->startOfYear();
            $endDate = $startDate->copy()->endOfYear();
        }

        $holidays = $query->dateRange($startDate, $endDate)->get();

        if ($request->expectsJson()) {
            $calendarData = $holidays->map(function ($holiday) {
                return [
                    'id' => $holiday->id,
                    'title' => $holiday->name,
                    'start' => $holiday->date->format('Y-m-d'),
                    'end' => $holiday->end_date ? $holiday->end_date->addDay()->format('Y-m-d') : null,
                    'color' => $holiday->color,
                    'description' => $holiday->description,
                    'type' => $holiday->type_label,
                    'allDay' => true,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $calendarData,
            ]);
        }

        return view('pages.holidays.calendar', compact('holidays', 'year'));
    }

    /**
     * Import holidays from external source
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('manage_holidays');

        $validator = Validator::make($request->all(), [
            'source' => 'required|string|in:government_api,file_upload',
            'year' => 'required|integer|min:2020|max:2030',
            'file' => 'required_if:source,file_upload|file|mimes:csv,xlsx,json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $importedCount = 0;

            switch ($request->source) {
                case 'government_api':
                    $importedCount = $this->importService->importFromGovernmentAPI($request->year);
                    break;

                case 'file_upload':
                    $importedCount = $this->importService->importFromFile($request->file('file'));
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$importedCount} holidays",
                'imported_count' => $importedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export holidays
     */
    public function export(Request $request): JsonResponse
    {
        $this->authorize('manage_holidays');

        $validator = Validator::make($request->all(), [
            'format' => 'required|string|in:csv,xlsx,json,pdf',
            'year' => 'nullable|integer',
            'type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = Holiday::active();

            if ($request->filled('year')) {
                // SQLite compatible year filtering
                $query->whereRaw("strftime('%Y', date) = ?", [$request->year]);
            }

            if ($request->filled('type')) {
                $query->byType($request->type);
            }

            $holidays = $query->orderBy('date')->get();

            $filename = 'holidays_'.($request->year ?? 'all').'_'.now()->format('Y-m-d');

            switch ($request->format) {
                case 'csv':
                    return $this->exportService->exportToCsv($holidays, $filename);
                case 'xlsx':
                    return $this->exportService->exportToExcel($holidays, $filename);
                case 'json':
                    return $this->exportService->exportToJson($holidays, $filename);
                case 'pdf':
                    return $this->exportService->exportToPdf($holidays, $filename);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate recurring holidays for a specific year
     */
    public function generateRecurring(Request $request): JsonResponse
    {
        $this->authorize('manage_holidays');

        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $year = $request->year;
            $recurringHolidays = Holiday::recurring()->get();
            $generatedCount = 0;

            foreach ($recurringHolidays as $holiday) {
                $nextOccurrence = $holiday->generateNextOccurrence($year);

                if ($nextOccurrence) {
                    // Check if already exists
                    $exists = Holiday::where('name', $nextOccurrence->name)
                        ->where('date', $nextOccurrence->date)
                        ->exists();

                    if (! $exists) {
                        $nextOccurrence->save();
                        $generatedCount++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Generated {$generatedCount} recurring holidays for {$year}",
                'generated_count' => $generatedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Generation failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if a date is a holiday
     */
    public function checkDate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'role' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $date = Carbon::parse($request->date);
        $role = $request->role;

        $isHoliday = Holiday::isHoliday($date, $role);
        $holidays = Holiday::getHolidaysForDate($date);

        return response()->json([
            'success' => true,
            'is_holiday' => $isHoliday,
            'holidays' => $holidays,
            'date' => $date->format('Y-m-d'),
        ]);
    }

}
