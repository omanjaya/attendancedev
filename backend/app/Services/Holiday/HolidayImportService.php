<?php

namespace App\Services\Holiday;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class HolidayImportService
{
    /**
     * Import holidays from Indonesian government API and common holidays
     */
    public function importFromGovernmentAPI(int $year): int
    {
        $imported = 0;

        // Try to fetch from multiple sources
        try {
            // Method 1: Try Indonesian Holiday API
            $apiHolidays = $this->fetchFromHolidayAPI($year);
            if (!empty($apiHolidays)) {
                $imported += $this->processHolidayData($apiHolidays, 'holiday_api');
                return $imported;
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch from Holiday API: ' . $e->getMessage());
        }

        try {
            // Method 2: Try Public Holiday API
            $publicHolidays = $this->fetchFromPublicHolidayAPI($year);
            if (!empty($publicHolidays)) {
                $imported += $this->processHolidayData($publicHolidays, 'public_holiday_api');
                return $imported;
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch from Public Holiday API: ' . $e->getMessage());
        }

        // Fallback: Use predefined Indonesian holidays
        $commonHolidays = $this->getCommonIndonesianHolidays($year);
        $imported += $this->processHolidayData($commonHolidays, 'predefined');

        return $imported;
    }

    /**
     * Import holidays from uploaded file (CSV, JSON, Excel)
     */
    public function importFromFile($file): int
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
            \Log::error('File import failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch holidays from Indonesian Holiday API
     */
    private function fetchFromHolidayAPI(int $year): array
    {
        $url = "https://api-harilibur.vercel.app/api?year={$year}";

        $response = Http::timeout(10)->get($url);

        if (!$response->successful()) {
            throw new \Exception('API request failed with status: ' . $response->status());
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
                        ? ['type' => 'yearly']
                        : null,
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

        if (!$response->successful()) {
            throw new \Exception('API request failed with status: ' . $response->status());
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

                if (!$exists) {
                    Holiday::create(array_merge($holidayData, [
                        'source' => $source,
                        'status' => Holiday::STATUS_ACTIVE,
                        'is_paid' => $this->isDayOff($holidayData['type']),
                    ]));
                    $imported++;
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to import holiday: ' . $e->getMessage(), $holidayData);
            }
        }

        return $imported;
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

        if (!empty($missingHeaders)) {
            throw new \Exception('Missing required headers: ' . implode(', ', $missingHeaders));
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

                if (!$exists) {
                    Holiday::create($holidayData);
                    $imported++;
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to import CSV row: ' . $e->getMessage(), $row);
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
            throw new \Exception('Invalid JSON format: ' . json_last_error_msg());
        }

        // Handle both array of holidays and nested structure
        $holidays = isset($data['holidays']) ? $data['holidays'] : $data;

        if (!is_array($holidays)) {
            throw new \Exception('JSON must contain an array of holidays');
        }

        $imported = 0;
        foreach ($holidays as $holiday) {
            try {
                if (!isset($holiday['name']) || !isset($holiday['date'])) {
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

                if (!$exists) {
                    Holiday::create($holidayData);
                    $imported++;
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to import JSON holiday: ' . $e->getMessage(), $holiday);
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
        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
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

            if (!empty($missingHeaders)) {
                throw new \Exception('Missing required headers: ' . implode(', ', $missingHeaders));
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

                    if (!$exists) {
                        Holiday::create($holidayData);
                        $imported++;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to import Excel row: ' . $e->getMessage(), $row);
                }
            }

            return $imported;
        } catch (\Exception $e) {
            throw new \Exception('Excel import failed: ' . $e->getMessage());
        }
    }

    /**
     * Map holiday names to types
     */
    private function mapHolidayType(string $name): string
    {
        $name = strtolower($name);

        if (
            str_contains($name, 'natal') || str_contains($name, 'idul') ||
            str_contains($name, 'waisak') || str_contains($name, 'nyepi')
        ) {
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
}
