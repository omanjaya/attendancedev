<?php

namespace App\Console\Commands;

use App\Http\Controllers\HolidayController;
use App\Services\AttendanceHolidayService;
use Illuminate\Console\Command;

class ImportHolidays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holidays:import 
                            {year? : The year to import holidays for (defaults to current year)}
                            {--source=government : Import source (government, file)}
                            {--file= : File path for file import}
                            {--test : Test the holiday integration with attendance}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import holidays from various sources and test attendance integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->argument('year') ?? date('Y');
        $source = $this->option('source');
        $file = $this->option('file');
        $test = $this->option('test');

        $this->info("🎯 Starting holiday import for year {$year}");

        try {
            if ($source === 'government') {
                $this->importFromGovernment($year);
            } elseif ($source === 'file' && $file) {
                $this->importFromFile($file);
            }

            if ($test) {
                $this->testHolidayIntegration();
            }

            $this->info('✅ Holiday import completed successfully!');

        } catch (\Exception $e) {
            $this->error('❌ Holiday import failed: '.$e->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     * Import holidays from government API
     */
    private function importFromGovernment(int $year)
    {
        $this->info("📡 Importing holidays from Indonesian government APIs for {$year}...");

        // Directly use the controller methods to bypass middleware
        $controller = new HolidayController;

        try {
            // Call the private method directly using reflection for testing
            $reflectionClass = new \ReflectionClass($controller);
            $method = $reflectionClass->getMethod('importFromGovernmentAPI');
            $method->setAccessible(true);

            $importedCount = $method->invoke($controller, $year);

            $this->info("✅ Successfully imported {$importedCount} holidays");

            // Display imported holidays
            $holidays = \App\Models\Holiday::whereYear('date', $year)
                ->orderBy('date')
                ->get();

            if ($holidays->count() > 0) {
                $this->table(
                    ['Date', 'Name', 'Type', 'Paid', 'Source'],
                    $holidays->map(function ($holiday) {
                        return [
                            $holiday->date->format('Y-m-d'),
                            $holiday->name,
                            $holiday->type_label,
                            $holiday->is_paid ? 'Yes' : 'No',
                            $holiday->source,
                        ];
                    })
                );
            } else {
                $this->warn("No holidays found for {$year}");
            }
        } catch (\Exception $e) {
            $this->error('❌ Import failed: '.$e->getMessage());
        }
    }

    /**
     * Import holidays from file
     */
    private function importFromFile(string $filePath)
    {
        if (! file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $this->info("📁 Importing holidays from file: {$filePath}");

        // Create a mock uploaded file
        $file = new \Illuminate\Http\UploadedFile(
            $filePath,
            basename($filePath),
            mime_content_type($filePath),
            null,
            true
        );

        $request = new \Illuminate\Http\Request;
        $request->files->set('file', $file);
        $request->merge(['source' => 'file']);

        $controller = new HolidayController;
        $response = $controller->import($request);
        $data = $response->getData();

        if ($data->success) {
            $this->info("✅ Successfully imported {$data->imported_count} holidays from file");
        } else {
            $this->error('❌ File import failed: '.($data->message ?? 'Unknown error'));
        }
    }

    /**
     * Test holiday integration with attendance system
     */
    private function testHolidayIntegration()
    {
        $this->info('🧪 Testing holiday integration with attendance system...');

        $service = new AttendanceHolidayService;

        // Test 1: Check if any holidays exist
        $currentYear = date('Y');
        $startDate = \Carbon\Carbon::create($currentYear, 1, 1);
        $endDate = \Carbon\Carbon::create($currentYear, 12, 31);

        $holidays = $service->getHolidaysInRange($startDate, $endDate);
        $this->info("📅 Found {$holidays->count()} holidays for {$currentYear}");

        // Test 2: Check working days calculation
        $workingDays = $service->calculateWorkingDays(
            \Carbon\Carbon::now()->startOfMonth(),
            \Carbon\Carbon::now()->endOfMonth()
        );
        $this->info("📊 Working days this month: {$workingDays}");

        // Test 3: Check holiday detection for today
        $today = \Carbon\Carbon::today();
        $isHoliday = $service->isHoliday($today);
        $this->info($isHoliday
            ? "🎉 Today ({$today->format('Y-m-d')}) is a holiday!"
            : "💼 Today ({$today->format('Y-m-d')}) is a working day"
        );

        if ($isHoliday) {
            $holiday = $service->getHoliday($today);
            $this->info("   Holiday: {$holiday->name} ({$holiday->type_label})");
            $this->info('   Paid: '.($holiday->is_paid ? 'Yes' : 'No'));
        }

        // Test 4: Check if we have any employees for attendance summary
        $employee = \App\Models\Employee::first();
        if ($employee) {
            $this->info("👤 Testing attendance summary for employee: {$employee->full_name}");

            $summary = $service->getAttendanceSummary($employee, \Carbon\Carbon::now());

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Working Days', $summary['working_days']['total_working_days']],
                    ['Present Days', $summary['working_days']['present_days']],
                    ['Absent Days', $summary['working_days']['absent_days']],
                    ['Attendance Rate', $summary['working_days']['attendance_rate'].'%'],
                    ['Expected Hours', $summary['working_hours']['expected_hours']],
                    ['Actual Hours', $summary['working_hours']['actual_hours']],
                    ['Efficiency Rate', $summary['working_hours']['efficiency_rate'].'%'],
                    ['Total Holidays', $summary['holidays']['total_holidays']],
                    ['Paid Holidays', $summary['holidays']['paid_holidays']],
                ]
            );
        } else {
            $this->warn('⚠️  No employees found. Create an employee to test attendance summaries.');
        }

        $this->info('✅ Holiday integration test completed!');
    }
}
