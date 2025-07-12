<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TimeSlot;
use App\Models\AcademicClass;
use App\Models\Subject;
use App\Models\TeacherSubject;
use App\Models\Employee;
use Carbon\Carbon;

class ScheduleManagementSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Seeding schedule management data...');

        // Create Time Slots
        $this->createTimeSlots();
        
        // Create Academic Classes
        $this->createAcademicClasses();
        
        // Create Subjects
        $this->createSubjects();
        
        // Assign Teachers to Subjects
        $this->assignTeachersToSubjects();

        $this->command->info('Schedule management data seeded successfully!');
    }

    private function createTimeSlots()
    {
        $timeSlots = [
            ['name' => 'Jam 1', 'start_time' => '07:00', 'end_time' => '07:45', 'order' => 1],
            ['name' => 'Jam 2', 'start_time' => '07:45', 'end_time' => '08:30', 'order' => 2],
            ['name' => 'Istirahat 1', 'start_time' => '08:30', 'end_time' => '08:45', 'order' => 3],
            ['name' => 'Jam 3', 'start_time' => '08:45', 'end_time' => '09:30', 'order' => 4],
            ['name' => 'Jam 4', 'start_time' => '09:30', 'end_time' => '10:15', 'order' => 5],
            ['name' => 'Jam 5', 'start_time' => '10:15', 'end_time' => '11:00', 'order' => 6],
            ['name' => 'Istirahat 2', 'start_time' => '11:00', 'end_time' => '11:30', 'order' => 7],
            ['name' => 'Jam 6', 'start_time' => '11:30', 'end_time' => '12:15', 'order' => 8],
            ['name' => 'Jam 7', 'start_time' => '12:15', 'end_time' => '13:00', 'order' => 9],
            ['name' => 'Jam 8', 'start_time' => '13:00', 'end_time' => '13:45', 'order' => 10],
        ];

        foreach ($timeSlots as $slot) {
            TimeSlot::create([
                'name' => $slot['name'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'order' => $slot['order'],
                'is_active' => !str_contains($slot['name'], 'Istirahat'), // Non-break times are active
                'metadata' => str_contains($slot['name'], 'Istirahat') ? ['type' => 'break'] : ['type' => 'lesson']
            ]);
        }

        $this->command->info('Created ' . count($timeSlots) . ' time slots');
    }

    private function createAcademicClasses()
    {
        $classes = [
            // Kelas X
            ['grade_level' => 'X', 'major' => 'IPA', 'class_number' => '1', 'capacity' => 32],
            ['grade_level' => 'X', 'major' => 'IPA', 'class_number' => '2', 'capacity' => 30],
            ['grade_level' => 'X', 'major' => 'IPS', 'class_number' => '1', 'capacity' => 28],
            ['grade_level' => 'X', 'major' => 'IPS', 'class_number' => '2', 'capacity' => 30],
            
            // Kelas XI
            ['grade_level' => 'XI', 'major' => 'IPA', 'class_number' => '1', 'capacity' => 30],
            ['grade_level' => 'XI', 'major' => 'IPA', 'class_number' => '2', 'capacity' => 32],
            ['grade_level' => 'XI', 'major' => 'IPS', 'class_number' => '1', 'capacity' => 28],
            ['grade_level' => 'XI', 'major' => 'IPS', 'class_number' => '2', 'capacity' => 29],
            
            // Kelas XII
            ['grade_level' => 'XII', 'major' => 'IPA', 'class_number' => '1', 'capacity' => 31],
            ['grade_level' => 'XII', 'major' => 'IPA', 'class_number' => '2', 'capacity' => 33],
            ['grade_level' => 'XII', 'major' => 'IPS', 'class_number' => '1', 'capacity' => 27],
            ['grade_level' => 'XII', 'major' => 'IPS', 'class_number' => '2', 'capacity' => 29],
        ];

        foreach ($classes as $class) {
            AcademicClass::create([
                'name' => "{$class['grade_level']}-{$class['major']}-{$class['class_number']}",
                'grade_level' => $class['grade_level'],
                'major' => $class['major'],
                'class_number' => $class['class_number'],
                'capacity' => $class['capacity'],
                'room' => "Ruang {$class['grade_level']}-{$class['class_number']}",
                'is_active' => true,
                'metadata' => [
                    'wali_kelas' => null,
                    'academic_year' => '2024/2025'
                ]
            ]);
        }

        $this->command->info('Created ' . count($classes) . ' academic classes');
    }

    private function createSubjects()
    {
        $subjects = [
            // Mata Pelajaran Umum
            ['code' => 'MTK', 'name' => 'Matematika', 'category' => 'Exact', 'weekly_hours' => 4, 'max_meetings' => 3, 'color' => '#3B82F6'],
            ['code' => 'BIN', 'name' => 'Bahasa Indonesia', 'category' => 'Language', 'weekly_hours' => 4, 'max_meetings' => 3, 'color' => '#EF4444'],
            ['code' => 'BING', 'name' => 'Bahasa Inggris', 'category' => 'Language', 'weekly_hours' => 3, 'max_meetings' => 2, 'color' => '#10B981'],
            ['code' => 'PKN', 'name' => 'Pendidikan Kewarganegaraan', 'category' => 'Social', 'weekly_hours' => 2, 'max_meetings' => 2, 'color' => '#F59E0B'],
            ['code' => 'PAI', 'name' => 'Pendidikan Agama Islam', 'category' => 'Religion', 'weekly_hours' => 2, 'max_meetings' => 2, 'color' => '#8B5CF6'],
            ['code' => 'PENJAS', 'name' => 'Pendidikan Jasmani', 'category' => 'Physical', 'weekly_hours' => 2, 'max_meetings' => 2, 'color' => '#06B6D4'],
            
            // IPA
            ['code' => 'FIS', 'name' => 'Fisika', 'category' => 'Exact', 'weekly_hours' => 3, 'max_meetings' => 2, 'requires_lab' => true, 'color' => '#DC2626'],
            ['code' => 'KIM', 'name' => 'Kimia', 'category' => 'Exact', 'weekly_hours' => 3, 'max_meetings' => 2, 'requires_lab' => true, 'color' => '#059669'],
            ['code' => 'BIO', 'name' => 'Biologi', 'category' => 'Exact', 'weekly_hours' => 3, 'max_meetings' => 2, 'requires_lab' => true, 'color' => '#7C3AED'],
            
            // IPS
            ['code' => 'SEJ', 'name' => 'Sejarah', 'category' => 'Social', 'weekly_hours' => 3, 'max_meetings' => 2, 'color' => '#B45309'],
            ['code' => 'GEO', 'name' => 'Geografi', 'category' => 'Social', 'weekly_hours' => 3, 'max_meetings' => 2, 'color' => '#065F46'],
            ['code' => 'EKO', 'name' => 'Ekonomi', 'category' => 'Social', 'weekly_hours' => 3, 'max_meetings' => 2, 'color' => '#7C2D12'],
            ['code' => 'SOS', 'name' => 'Sosiologi', 'category' => 'Social', 'weekly_hours' => 2, 'max_meetings' => 2, 'color' => '#BE185D'],
            
            // Seni dan Keterampilan
            ['code' => 'SBD', 'name' => 'Seni Budaya', 'category' => 'Arts', 'weekly_hours' => 2, 'max_meetings' => 2, 'color' => '#DB2777'],
            ['code' => 'TIK', 'name' => 'Teknologi Informasi', 'category' => 'Technology', 'weekly_hours' => 2, 'max_meetings' => 2, 'requires_lab' => true, 'color' => '#2563EB'],
        ];

        foreach ($subjects as $subject) {
            Subject::create([
                'code' => $subject['code'],
                'name' => $subject['name'],
                'category' => $subject['category'],
                'weekly_hours' => $subject['weekly_hours'],
                'max_meetings_per_week' => $subject['max_meetings'],
                'requires_lab' => $subject['requires_lab'] ?? false,
                'color' => $subject['color'],
                'is_active' => true,
                'metadata' => [
                    'curriculum' => 'K13',
                    'type' => in_array($subject['category'], ['Exact', 'Social']) ? 'mandatory' : 'elective'
                ]
            ]);
        }

        $this->command->info('Created ' . count($subjects) . ' subjects');
    }

    private function assignTeachersToSubjects()
    {
        // Get all active teachers (permanent and honorary)
        $teachers = Employee::where('is_active', true)
                           ->whereIn('employee_type', ['permanent', 'honorary'])
                           ->whereIn('department', ['Exact Sciences', 'Language', 'Social Sciences', 'Arts', 'Physical'])
                           ->get();

        if ($teachers->isEmpty()) {
            $this->command->warn('No teachers found. Run UserSeeder first to create teacher data.');
            return;
        }

        $subjects = Subject::all();
        $assignmentCount = 0;

        // Assign teachers to subjects (random assignment for demo)
        foreach ($subjects as $subject) {
            // Assign 1-3 teachers per subject
            $teacherCount = rand(1, min(3, $teachers->count()));
            $assignedTeachers = $teachers->random($teacherCount);

            foreach ($assignedTeachers as $index => $teacher) {
                TeacherSubject::create([
                    'employee_id' => $teacher->id,
                    'subject_id' => $subject->id,
                    'is_primary' => $index === 0, // First teacher is primary
                    'max_hours_per_week' => $teacher->employee_type === 'permanent' ? 24 : 18,
                    'competencies' => [
                        'certification' => rand(0, 1) ? 'S1 ' . $subject->category : null,
                        'experience_years' => rand(1, 15),
                        'specialization' => $subject->name
                    ],
                    'is_active' => true
                ]);

                $assignmentCount++;
            }
        }

        $this->command->info("Created {$assignmentCount} teacher-subject assignments");
    }
}