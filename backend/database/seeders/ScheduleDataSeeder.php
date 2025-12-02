<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TimeSlot;
use App\Models\Subject;
use App\Models\AcademicClass;
use App\Models\WeeklySchedule;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class ScheduleDataSeeder extends Seeder
{
    /**
     * Seed schedule-related data (TimeSlots, Subjects, AcademicClasses, WeeklySchedules)
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('weekly_schedules')->delete();
        DB::table('time_slots')->delete();
        DB::table('subjects')->delete();
        DB::table('academic_classes')->delete();

        // Seed Time Slots
        $timeSlots = [
            ['name' => 'Jam 1', 'start_time' => '07:00', 'end_time' => '07:45', 'order' => 1, 'metadata' => null],
            ['name' => 'Jam 2', 'start_time' => '07:45', 'end_time' => '08:30', 'order' => 2, 'metadata' => null],
            ['name' => 'Jam 3', 'start_time' => '08:30', 'end_time' => '09:15', 'order' => 3, 'metadata' => null],
            ['name' => 'Istirahat 1', 'start_time' => '09:15', 'end_time' => '09:30', 'order' => 4, 'metadata' => json_encode(['is_break' => true])],
            ['name' => 'Jam 4', 'start_time' => '09:30', 'end_time' => '10:15', 'order' => 5, 'metadata' => null],
            ['name' => 'Jam 5', 'start_time' => '10:15', 'end_time' => '11:00', 'order' => 6, 'metadata' => null],
            ['name' => 'Jam 6', 'start_time' => '11:00', 'end_time' => '11:45', 'order' => 7, 'metadata' => null],
            ['name' => 'Istirahat 2', 'start_time' => '11:45', 'end_time' => '12:00', 'order' => 8, 'metadata' => json_encode(['is_break' => true])],
            ['name' => 'Jam 7', 'start_time' => '12:00', 'end_time' => '12:45', 'order' => 9, 'metadata' => null],
            ['name' => 'Jam 8', 'start_time' => '12:45', 'end_time' => '13:30', 'order' => 10, 'metadata' => null],
        ];

        foreach ($timeSlots as $slot) {
            TimeSlot::create($slot);
        }

        $this->command->info('✓ Time slots created');

        // Seed Subjects
        $subjects = [
            ['code' => 'MTK', 'name' => 'Matematika', 'category' => 'Exact', 'weekly_hours' => 4, 'is_active' => true],
            ['code' => 'BHS-IND', 'name' => 'Bahasa Indonesia', 'category' => 'Language', 'weekly_hours' => 4, 'is_active' => true],
            ['code' => 'BHS-ING', 'name' => 'Bahasa Inggris', 'category' => 'Language', 'weekly_hours' => 2, 'is_active' => true],
            ['code' => 'IPA', 'name' => 'IPA Terpadu', 'category' => 'Exact', 'weekly_hours' => 4, 'requires_lab' => true, 'is_active' => true],
            ['code' => 'IPS', 'name' => 'IPS Terpadu', 'category' => 'Social', 'weekly_hours' => 3, 'is_active' => true],
            ['code' => 'PKN', 'name' => 'Pendidikan Kewarganegaraan', 'category' => 'Social', 'weekly_hours' => 2, 'is_active' => true],
            ['code' => 'PAI', 'name' => 'Pendidikan Agama Islam', 'category' => 'Religion', 'weekly_hours' => 2, 'is_active' => true],
            ['code' => 'PJOK', 'name' => 'Pendidikan Jasmani Olahraga dan Kesehatan', 'category' => 'Physical', 'weekly_hours' => 2, 'is_active' => true],
            ['code' => 'SBK', 'name' => 'Seni Budaya', 'category' => 'Arts', 'weekly_hours' => 2, 'is_active' => true],
            ['code' => 'TIK', 'name' => 'Teknologi Informasi dan Komunikasi', 'category' => 'Exact', 'weekly_hours' => 2, 'is_active' => true],
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }

        $this->command->info('✓ Subjects created');

        // Seed Academic Classes
        $classes = [
            ['name' => '7A', 'grade_level' => '7', 'major' => null, 'class_number' => 'A', 'capacity' => 30, 'room' => 'Ruang 7A', 'is_active' => true],
            ['name' => '7B', 'grade_level' => '7', 'major' => null, 'class_number' => 'B', 'capacity' => 30, 'room' => 'Ruang 7B', 'is_active' => true],
            ['name' => '8A', 'grade_level' => '8', 'major' => null, 'class_number' => 'A', 'capacity' => 28, 'room' => 'Ruang 8A', 'is_active' => true],
            ['name' => '8B', 'grade_level' => '8', 'major' => null, 'class_number' => 'B', 'capacity' => 28, 'room' => 'Ruang 8B', 'is_active' => true],
            ['name' => '9A', 'grade_level' => '9', 'major' => null, 'class_number' => 'A', 'capacity' => 25, 'room' => 'Ruang 9A', 'is_active' => true],
            ['name' => '9B', 'grade_level' => '9', 'major' => null, 'class_number' => 'B', 'capacity' => 25, 'room' => 'Ruang 9B', 'is_active' => true],
        ];

        foreach ($classes as $class) {
            AcademicClass::create($class);
        }

        $this->command->info('✓ Academic classes created');

        // Get data for schedules
        // Filter out break time slots (those with is_break in metadata)
        $allTimeSlots = TimeSlot::orderBy('order')->get()->filter(function ($slot) {
            $metadata = json_decode($slot->metadata, true);
            return !isset($metadata['is_break']) || !$metadata['is_break'];
        });
        $allSubjects = Subject::all();
        $allClasses = AcademicClass::all();
        $teachers = Employee::whereHas('user.roles', function ($q) {
            $q->where('name', 'guru');
        })->where('is_active', true)->get();

        if ($teachers->isEmpty()) {
            $this->command->warn('⚠ No teachers found. Creating sample schedules with first available employee...');
            $teachers = Employee::where('is_active', true)->limit(5)->get();
        }

        if ($teachers->isEmpty()) {
            $this->command->error('✗ No employees found. Please run UserSeeder first.');
            return;
        }

        // Create sample schedules for class 7A
        $class7A = $allClasses->where('name', '7A')->first();

        if ($class7A) {
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            $scheduleCount = 0;

            foreach ($days as $dayIndex => $day) {
                // Take first 4-5 time slots per day (excluding breaks)
                $dayTimeSlots = $allTimeSlots->take(5);

                foreach ($dayTimeSlots as $index => $timeSlot) {
                    // Assign different subjects
                    $subject = $allSubjects->random();
                    $teacher = $teachers->random();

                    WeeklySchedule::create([
                        'academic_class_id' => $class7A->id,
                        'employee_id' => $teacher->id,
                        'subject_id' => $subject->id,
                        'time_slot_id' => $timeSlot->id,
                        'day_of_week' => $day,
                        'room' => 'Ruang ' . chr(65 + $dayIndex) . ($index + 1), // A1, A2, B1, B2, etc
                        'is_active' => true,
                        'is_locked' => false,
                        'effective_from' => now(),
                    ]);

                    $scheduleCount++;
                }
            }

            $this->command->info("✓ Created {$scheduleCount} sample schedules for class 7A");
        }

        // Create some schedules for 8A as well
        $class8A = $allClasses->where('name', '8A')->first();

        if ($class8A) {
            $scheduleCount = 0;

            // Monday and Tuesday only
            foreach (['monday', 'tuesday'] as $dayIndex => $day) {
                $dayTimeSlots = $allTimeSlots->take(4);

                foreach ($dayTimeSlots as $index => $timeSlot) {
                    $subject = $allSubjects->random();
                    $teacher = $teachers->random();

                    WeeklySchedule::create([
                        'academic_class_id' => $class8A->id,
                        'employee_id' => $teacher->id,
                        'subject_id' => $subject->id,
                        'time_slot_id' => $timeSlot->id,
                        'day_of_week' => $day,
                        'room' => 'Ruang ' . chr(66 + $dayIndex) . ($index + 1),
                        'is_active' => true,
                        'is_locked' => false,
                        'effective_from' => now(),
                    ]);

                    $scheduleCount++;
                }
            }

            $this->command->info("✓ Created {$scheduleCount} sample schedules for class 8A");
        }

        $this->command->info('✅ Schedule data seeding completed successfully!');
    }
}
