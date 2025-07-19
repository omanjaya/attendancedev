<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function __construct()
    {
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
            $query->whereYear('date', $year);
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
        $years = Holiday::selectRaw('DISTINCT YEAR(date) as year')
            ->orderBy('year', 'desc')
            ->pluck('year');

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
                    $importedCount = $this->importFromGovernmentAPI($request->year);
                    break;

                case 'file_upload':
                    $importedCount = $this->importFromFile($request->file('file'));
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
                $query->whereYear('date', $request->year);
            }

            if ($request->filled('type')) {
                $query->byType($request->type);
            }

            $holidays = $query->orderBy('date')->get();

            $filename = 'holidays_'.($request->year ?? 'all').'_'.now()->format('Y-m-d');

            switch ($request->format) {
                case 'csv':
                    return $this->exportToCsv($holidays, $filename);
                case 'xlsx':
                    return $this->exportToExcel($holidays, $filename);
                case 'json':
                    return $this->exportToJson($holidays, $filename);
                case 'pdf':
                    return $this->exportToPdf($holidays, $filename);
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

    /**
     * Import holidays from Indonesian government API and common holidays
     */
    private function importFromGovernmentAPI(int $year): int
    {
        $imported = 0;

        // Try to fetch from multiple sources
        try {
            // Method 1: Try Indonesian Holiday API
            $apiHolidays = $this->fetchFromHolidayAPI($year);
            if (! empty($apiHolidays)) {
                $imported += $this->processHolidayData($apiHolidays, 'holiday_api');

                return $imported;
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch from Holiday API: '.$e->getMessage());
        }

        try {
            // Method 2: Try Public Holiday API
            $publicHolidays = $this->fetchFromPublicHolidayAPI($year);
            if (! empty($publicHolidays)) {
                $imported += $this->processHolidayData($publicHolidays, 'public_holiday_api');

                return $imported;
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch from Public Holiday API: '.$e->getMessage());
        }

        // Fallback: Use predefined Indonesian holidays
        $commonHolidays = $this->getCommonIndonesianHolidays($year);
        $imported += $this->processHolidayData($commonHolidays, 'predefined');

        return $imported;
    }

    /**
     * Fetch holidays from Indonesian Holiday API
     */
    private function fetchFromHolidayAPI(int $year): array
    {
        $url = "https://api-harilibur.vercel.app/api?year={$year}";

        $response = Http::timeout(10)->get($url);

        if (! $response->successful()) {
            throw new \Exception('API request failed with status: '.$response->status());
        }

        $data = $response->json();
        $holidays = [];

        foreach ($data as $holiday) {
            if (isset($holiday['holiday_date']) && isset($holiday['holiday_name'])) {
                $holidays[] = [
                    'name' => $holiday['holiday_name'],
                    'date' => $holiday['holiday_date'],
                    'type' => $this->mapHolidayType($holiday['holiday_name']),
                    'description' => $holiday['holiday_name'],
                    'is_recurring' => $this->isRecurringHoliday($holiday['holiday_name']),
                    'recurring_pattern' => $this->isRecurringHoliday($holiday['holiday_name'])
                        ? ['type' => 'yearly'] : null,
                ];
            }
        }

        return $holidays;
    }

    /**
     * Fetch holidays from Public Holiday API
     */
    private function fetchFromPublicHolidayAPI(int $year): array
    {
        $url = "https://date.nager.at/api/v3/PublicHolidays/{$year}/ID";

        $response = Http::timeout(10)->get($url);

        if (! $response->successful()) {
            throw new \Exception('API request failed with status: '.$response->status());
        }

        $data = $response->json();
        $holidays = [];

        foreach ($data as $holiday) {
            if (isset($holiday['date']) && isset($holiday['localName'])) {
                $holidays[] = [
                    'name' => $holiday['localName'],
                    'date' => $holiday['date'],
                    'type' => $this->mapHolidayType($holiday['localName']),
                    'description' => $holiday['name'] ?? $holiday['localName'],
                    'is_recurring' => true, // Most public holidays are recurring
                    'recurring_pattern' => ['type' => 'yearly'],
                ];
            }
        }

        return $holidays;
    }

    /**
     * Get common Indonesian holidays as fallback
     */
    private function getCommonIndonesianHolidays(int $year): array
    {
        return [
            [
                'name' => 'Tahun Baru',
                'date' => "{$year}-01-01",
                'type' => Holiday::TYPE_PUBLIC,
                'description' => 'Tahun Baru Masehi',
                'is_recurring' => true,
                'recurring_pattern' => ['type' => 'yearly'],
            ],
            [
                'name' => 'Hari Kemerdekaan RI',
                'date' => "{$year}-08-17",
                'type' => Holiday::TYPE_PUBLIC,
                'description' => 'Hari Kemerdekaan Republik Indonesia',
                'is_recurring' => true,
                'recurring_pattern' => ['type' => 'yearly'],
            ],
            [
                'name' => 'Hari Raya Natal',
                'date' => "{$year}-12-25",
                'type' => Holiday::TYPE_RELIGIOUS,
                'description' => 'Hari Raya Natal',
                'is_recurring' => true,
                'recurring_pattern' => ['type' => 'yearly'],
            ],
            [
                'name' => 'Hari Buruh',
                'date' => "{$year}-05-01",
                'type' => Holiday::TYPE_PUBLIC,
                'description' => 'Hari Buruh Internasional',
                'is_recurring' => true,
                'recurring_pattern' => ['type' => 'yearly'],
            ],
            [
                'name' => 'Hari Pendidikan Nasional',
                'date' => "{$year}-05-02",
                'type' => Holiday::TYPE_SCHOOL,
                'description' => 'Hari Pendidikan Nasional',
                'is_recurring' => true,
                'recurring_pattern' => ['type' => 'yearly'],
            ],
        ];
    }

    /**
     * Process and save holiday data
     */
    private function processHolidayData(array $holidays, string $source): int
    {
        $imported = 0;

        foreach ($holidays as $holidayData) {
            try {
                $exists = Holiday::where('name', $holidayData['name'])
                    ->where('date', $holidayData['date'])
                    ->exists();

                if (! $exists) {
                    Holiday::create(array_merge($holidayData, [
                        'source' => $source,
                        'status' => Holiday::STATUS_ACTIVE,
                        'is_paid' => $this->isDayOff($holidayData['type']),
                    ]));
                    $imported++;
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to import holiday: '.$e->getMessage(), $holidayData);
            }
        }

        return $imported;
    }

    /**
     * Map holiday names to types
     */
    private function mapHolidayType(string $name): string
    {
        $name = strtolower($name);

        if (str_contains($name, 'natal') || str_contains($name, 'idul') ||
            str_contains($name, 'waisak') || str_contains($name, 'nyepi')) {
            return Holiday::TYPE_RELIGIOUS;
        }

        if (str_contains($name, 'pendidikan') || str_contains($name, 'sekolah')) {
            return Holiday::TYPE_SCHOOL;
        }

        return Holiday::TYPE_PUBLIC;
    }

    /**
     * Check if holiday is recurring
     */
    private function isRecurringHoliday(string $name): bool
    {
        $nonRecurring = ['pilkada', 'pemilu', 'khusus'];

        foreach ($nonRecurring as $keyword) {
            if (str_contains(strtolower($name), $keyword)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if holiday type should be paid day off
     */
    private function isDayOff(string $type): bool
    {
        return in_array($type, [Holiday::TYPE_PUBLIC, Holiday::TYPE_RELIGIOUS]);
    }

    /**
     * Import holidays from uploaded file (CSV, JSON, Excel)
     */
    private function importFromFile($file): int
    {
        $extension = $file->getClientOriginalExtension();
        $content = file_get_contents($file->getPathname());

        try {
            switch (strtolower($extension)) {
                case 'csv':
                    return $this->importFromCsv($content);
                case 'json':
                    return $this->importFromJson($content);
                case 'xlsx':
                case 'xls':
                    return $this->importFromExcel($file);
                default:
                    throw new \Exception("Unsupported file format: {$extension}");
            }
        } catch (\Exception $e) {
            \Log::error('File import failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Import holidays from CSV content
     */
    private function importFromCsv(string $content): int
    {
        $lines = explode("\n", $content);
        $header = str_getcsv(array_shift($lines));

        // Expected headers: name, date, type, description, is_paid
        $requiredHeaders = ['name', 'date'];
        $missingHeaders = array_diff($requiredHeaders, array_map('strtolower', $header));

        if (! empty($missingHeaders)) {
            throw new \Exception('Missing required headers: '.implode(', ', $missingHeaders));
        }

        $imported = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $data = str_getcsv($line);
            if (count($data) < count($requiredHeaders)) {
                continue;
            }

            $row = array_combine(array_map('strtolower', $header), $data);

            try {
                $holidayData = [
                    'name' => $row['name'],
                    'date' => Carbon::parse($row['date'])->format('Y-m-d'),
                    'type' => isset($row['type']) ? $row['type'] : Holiday::TYPE_PUBLIC,
                    'description' => isset($row['description']) ? $row['description'] : $row['name'],
                    'is_paid' => isset($row['is_paid']) ? filter_var($row['is_paid'], FILTER_VALIDATE_BOOLEAN) : true,
                    'source' => 'file_import',
                    'status' => Holiday::STATUS_ACTIVE,
                    'is_recurring' => false,
                ];

                $exists = Holiday::where('name', $holidayData['name'])
                    ->where('date', $holidayData['date'])
                    ->exists();

                if (! $exists) {
                    Holiday::create($holidayData);
                    $imported++;
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to import CSV row: '.$e->getMessage(), $row);
            }
        }

        return $imported;
    }

    /**
     * Import holidays from JSON content
     */
    private function importFromJson(string $content): int
    {
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON format: '.json_last_error_msg());
        }

        // Handle both array of holidays and nested structure
        $holidays = isset($data['holidays']) ? $data['holidays'] : $data;

        if (! is_array($holidays)) {
            throw new \Exception('JSON must contain an array of holidays');
        }

        $imported = 0;
        foreach ($holidays as $holiday) {
            try {
                if (! isset($holiday['name']) || ! isset($holiday['date'])) {
                    continue;
                }

                $holidayData = [
                    'name' => $holiday['name'],
                    'date' => Carbon::parse($holiday['date'])->format('Y-m-d'),
                    'type' => $holiday['type'] ?? Holiday::TYPE_PUBLIC,
                    'description' => $holiday['description'] ?? $holiday['name'],
                    'is_paid' => $holiday['is_paid'] ?? true,
                    'source' => 'file_import',
                    'status' => $holiday['status'] ?? Holiday::STATUS_ACTIVE,
                    'is_recurring' => $holiday['is_recurring'] ?? false,
                ];

                if (isset($holiday['recurring_pattern'])) {
                    $holidayData['recurring_pattern'] = $holiday['recurring_pattern'];
                }

                $exists = Holiday::where('name', $holidayData['name'])
                    ->where('date', $holidayData['date'])
                    ->exists();

                if (! $exists) {
                    Holiday::create($holidayData);
                    $imported++;
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to import JSON holiday: '.$e->getMessage(), $holiday);
            }
        }

        return $imported;
    }

    /**
     * Import holidays from Excel file (requires PhpSpreadsheet)
     */
    private function importFromExcel($file): int
    {
        // Check if PhpSpreadsheet is available
        if (! class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            throw new \Exception('PhpSpreadsheet not installed. Please install it to import Excel files.');
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                throw new \Exception('Excel file is empty');
            }

            $header = array_map('strtolower', array_map('trim', $rows[0]));
            $requiredHeaders = ['name', 'date'];
            $missingHeaders = array_diff($requiredHeaders, $header);

            if (! empty($missingHeaders)) {
                throw new \Exception('Missing required headers: '.implode(', ', $missingHeaders));
            }

            $imported = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $row = array_combine($header, $rows[$i]);

                try {
                    if (empty($row['name']) || empty($row['date'])) {
                        continue;
                    }

                    $holidayData = [
                        'name' => $row['name'],
                        'date' => Carbon::parse($row['date'])->format('Y-m-d'),
                        'type' => $row['type'] ?? Holiday::TYPE_PUBLIC,
                        'description' => $row['description'] ?? $row['name'],
                        'is_paid' => isset($row['is_paid']) ? filter_var($row['is_paid'], FILTER_VALIDATE_BOOLEAN) : true,
                        'source' => 'file_import',
                        'status' => Holiday::STATUS_ACTIVE,
                        'is_recurring' => false,
                    ];

                    $exists = Holiday::where('name', $holidayData['name'])
                        ->where('date', $holidayData['date'])
                        ->exists();

                    if (! $exists) {
                        Holiday::create($holidayData);
                        $imported++;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to import Excel row: '.$e->getMessage(), $row);
                }
            }

            return $imported;
        } catch (\Exception $e) {
            throw new \Exception('Excel import failed: '.$e->getMessage());
        }
    }

    /**
     * Export to CSV (placeholder)
     */
    private function exportToCsv($holidays, $filename): JsonResponse
    {
        // This would generate CSV file
        return response()->json([
            'success' => true,
            'download_url' => url("/holidays/download/{$filename}.csv"),
        ]);
    }

    /**
     * Export to Excel (placeholder)
     */
    private function exportToExcel($holidays, $filename): JsonResponse
    {
        // This would generate Excel file
        return response()->json([
            'success' => true,
            'download_url' => url("/holidays/download/{$filename}.xlsx"),
        ]);
    }

    /**
     * Export to JSON (placeholder)
     */
    private function exportToJson($holidays, $filename): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $holidays->toArray(),
        ]);
    }

    /**
     * Export to PDF (placeholder)
     */
    private function exportToPdf($holidays, $filename): JsonResponse
    {
        // This would generate PDF file
        return response()->json([
            'success' => true,
            'download_url' => url("/holidays/download/{$filename}.pdf"),
        ]);
    }
}
