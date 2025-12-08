<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\TeachingSchedule;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CleanAndImportExcelSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Starting Clean and Import Process...');

        // 1. Clean Data (Preserve Super Admin, Admin, Principal)
        $preservedEmails = [
            'superadmin@school.edu',
            'admin@school.edu',
            'kepala@school.edu',
            'omanjaya@school.edu' // Just in case user uses this
        ];

        // Toggle constraints
        try {
             DB::statement("SET session_replication_role = 'replica';");
        } catch (\Exception $e) {
             DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }
        
        // Delete Schedules Data
        TeachingSchedule::truncate();
        Subject::truncate(); 
        $this->command->info('Table teaching_schedules and subjects truncated.');

        // Delete Users & Employees (except preserved)
        // Note: Using delete() instead of truncate ensures model events might fire if needed, 
        // but here we want speed and bulk.
        
        $usersToDelete = User::whereNotIn('email', $preservedEmails)->get();
        $userIds = $usersToDelete->pluck('id')->toArray();

        if (!empty($userIds)) {
            Employee::whereIn('user_id', $userIds)->delete();
            User::whereIn('id', $userIds)->delete();
            
            $this->command->info('Deleted ' . count($userIds) . ' users and their employee records (except SuperAdmin/Admin).');
        }

        try {
             DB::statement("SET session_replication_role = 'origin';");
        } catch (\Exception $e) {
             DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        // 2. Read Excel
        $filePath = database_path('data/jadwal_mengajar.xlsx');
        if (!file_exists($filePath)) {
            $this->command->error("File Not Found: $filePath");
            return;
        }

        $spreadsheet = IOFactory::load($filePath);
        
        // --- Process Teachers (Sheet 1) ---
        $this->processTeachers($spreadsheet->getSheet(0));

        // --- Process Schedules (Sheet 2) ---
        $this->processSchedules($spreadsheet->getSheet(1));
        
        $this->command->info('Clean and Import Process Completed Successfully!');
    }

    private function processTeachers($sheet)
    {
        $this->command->info('Processing Teachers from Sheet 1...');
        
        $data = $sheet->toArray(null, true, true, true);
        $count = 0;
        
        foreach ($data as $rowIndex => $row) {
            if ($rowIndex < 3) continue; // Skip headers
            if (empty($row['B'])) continue; // Skip empty names

            $id = $row['A']; 
            $name = $row['B'];
            $position = $row['C'];
            $empId = 'GURU'.str_pad($id, 3, '0', STR_PAD_LEFT);

            // Periksa jika ID berupa angka valid
            if (!is_numeric($id)) continue;

            $email = 'guru' . $id . '@school.edu';
            
            // Create User
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            // Assign Role
            if (!$user->hasRole('guru')) {
                $user->assignRole('guru');
            }

            // Create Employee
            // Handle if employee exists (e.g. orphan record not deleted properly)
            Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'id' => (string) Str::uuid(),
                    'employee_id' => $empId,
                    'employee_type' => 'permanent',
                    'full_name' => $name,
                    'phone' => '-',
                    'hire_date' => now(),
                    'salary_type' => 'monthly',
                    'salary_amount' => 5000000,
                    'is_active' => true,
                    'metadata' => [
                        'position' => $position,
                        'email' => $email,
                    ],
                ]
            );

            $count++;
        }
        $this->command->info("Imported $count teachers.");
    }

    private function processSchedules($sheet)
    {
        $this->command->info('Processing Schedules from Sheet 2...');

        $data = $sheet->toArray(null, true, true, true);
        
        // Column Mapping for Classes
        $classColumns = [
            'D' => '7A', 'E' => '7B', 'F' => '7C', 'G' => '7D',
            'K' => '8A', 'L' => '8B', 'M' => '8C', 'N' => '8D',
            'R' => '9A', 'S' => '9B', 'T' => '9C', 'U' => '9D', 'V' => '9E'
        ];

        $currentDay = null;
        $scheduleCount = 0;
        $effectiveDate = Carbon::create(2024, 7, 15); // Start of Academic Year

        // Cache subjects to avoid repeated DB calls
        $subjectCache = [];

        foreach ($data as $rowIndex => $row) {
            
            $firstCol = trim($row['A'] ?? '');
            $thirdCol = trim($row['C'] ?? ''); // Sometimes day is here
            
            // Detect Day
            $dayCheck = strtoupper($firstCol ?: $thirdCol);
            if (in_array($dayCheck, ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'])) {
                $currentDay = $this->mapDayToEnglish($dayCheck);
                continue;
            }

            if (!$currentDay) continue;

            $period = $row['B'];
            $timeRange = $row['C'];

            // Must have period and time
            if (!is_numeric($period) || empty($timeRange)) continue;

            // Parse Time
            $times = explode('-', $timeRange);
            if (count($times) != 2) continue;
            
            $startTime = trim(str_replace('.', ':', $times[0])); // 07:30
            $endTime = trim(str_replace('.', ':', $times[1]));   // 08:10

            if (strlen($startTime) == 5) $startTime .= ':00';
            if (strlen($endTime) == 5) $endTime .= ':00';
            
            foreach ($classColumns as $col => $className) {
                $cellContent = $row[$col] ?? null;
                
                if (empty($cellContent) || str_contains(strtolower($cellContent), 'istirahat')) continue;

                // Example: "6-IPA" or "27-Seni Rupa"
                $parts = explode('-', $cellContent, 2);
                if (count($parts) < 2) continue; // Skip invalid format

                $teacherCode = trim($parts[0]);
                $subjectName = trim($parts[1]);

                if (!is_numeric($teacherCode)) continue; 

                // Find Teacher
                $teacherEmail = 'guru' . $teacherCode . '@school.edu';
                $teacherUser = User::where('email', $teacherEmail)->first();

                if (!$teacherUser || !$teacherUser->employee) {
                    // Log::warning("Missing teacher: $teacherCode");
                    continue;
                }

                // Find/Create Subject
                if (!isset($subjectCache[$subjectName])) {
                    $subject = Subject::firstOrCreate(
                        ['name' => $subjectName],
                        [
                            'code' => strtoupper(Str::slug($subjectName)),
                            'is_active' => true,
                            'category' => 'General'
                        ]
                    );
                    $subjectCache[$subjectName] = $subject->id;
                }
                $subjectId = $subjectCache[$subjectName];

                // Create Teaching Schedule
                TeachingSchedule::create([
                    'id' => (string) Str::uuid(),
                    'teacher_id' => $teacherUser->employee->id,
                    'subject_id' => $subjectId,
                    'class_name' => $className, // Using class_name field which is string
                    // 'class_id' => null, // Leave null if strictly using class_name string
                    'day_of_week' => $currentDay,
                    'teaching_start_time' => $startTime,
                    'teaching_end_time' => $endTime,
                    'effective_from' => $effectiveDate,
                    'effective_until' => $effectiveDate->copy()->addYear(),
                    'is_active' => true,
                    'status' => 'scheduled',
                    'override_attendance' => true,
                    'strict_timing' => true,
                ]);

                $scheduleCount++;
            }
        }
        
        $this->command->info("Imported $scheduleCount schedule entries.");
    }

    private function mapDayToEnglish($dayName)
    {
        $dayName = strtoupper(trim($dayName));
        $map = [
            'SENIN' => 'monday',
            'SELASA' => 'tuesday',
            'RABU' => 'wednesday',
            'KAMIS' => 'thursday',
            'JUMAT' => 'friday',
            'SABTU' => 'saturday',
            'MINGGU' => 'sunday',
        ];
        return $map[$dayName] ?? 'monday';
    }
}
