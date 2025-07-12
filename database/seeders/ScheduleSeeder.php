<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Period;
use App\Models\EmployeeSchedule;
use Carbon\Carbon;

class ScheduleSeeder extends Seeder
{
    public function run()
    {
        $employees = Employee::where('is_active', true)
                           ->where('employee_type', '!=', 'staff') // Only teachers get period schedules
                           ->get();
        
        $periods = Period::where('is_active', true)->get();

        if ($employees->isEmpty() || $periods->isEmpty()) {
            $this->command->warn('Need employees and periods. Run EmployeeSeeder and ensure periods exist.');
            return;
        }

        $this->command->info("Assigning schedules to {$employees->count()} employees across {$periods->count()} periods...");

        // Subjects for assignment
        $subjects = ['Mathematics', 'English', 'Science', 'History', 'Geography', 'Art', 'Physical Education', 'Computer Science'];
        $rooms = ['A101', 'A102', 'B201', 'B202', 'C301', 'C302', 'Lab-1', 'Lab-2', 'Gym', 'Library'];

        foreach ($employees as $employee) {
            $this->command->info("Assigning schedule for: {$employee->first_name} {$employee->last_name}");
            
            // Determine how many periods this employee should teach
            $periodsToAssign = match($employee->employee_type) {
                'permanent' => rand(8, 12), // Full-time teachers: 8-12 periods total
                'honorary' => rand(2, 5),   // Part-time teachers: 2-5 periods total
                default => rand(5, 8),
            };

            $assignedPeriods = 0;
            $maxAttempts = $periodsToAssign * 5; // Prevent infinite loop
            $attempts = 0;
            $startDate = Carbon::now()->startOfWeek(); // This week

            while ($assignedPeriods < $periodsToAssign && $attempts < $maxAttempts) {
                $attempts++;
                
                // Pick a random period
                $period = $periods->random();
                
                // Check if this employee is already assigned to this period (for this effective date)
                $existingAssignment = EmployeeSchedule::where('employee_id', $employee->id)
                                                   ->where('period_id', $period->id)
                                                   ->where('effective_date', $startDate->format('Y-m-d'))
                                                   ->exists();

                if ($existingAssignment) {
                    continue; // Skip if already assigned
                }

                // Check if this period is over-assigned (max 3 teachers per period)
                $existingCount = EmployeeSchedule::where('period_id', $period->id)
                                               ->where('effective_date', $startDate->format('Y-m-d'))
                                               ->where('is_active', true)
                                               ->count();

                if ($existingCount >= 3) {
                    continue; // Skip if period is full
                }

                // Create the assignment
                $subject = $subjects[array_rand($subjects)];
                $room = $rooms[array_rand($rooms)];

                EmployeeSchedule::create([
                    'employee_id' => $employee->id,
                    'period_id' => $period->id,
                    'effective_date' => $startDate->format('Y-m-d'),
                    'end_date' => null, // Open-ended assignment
                    'is_active' => true,
                    'metadata' => json_encode([
                        'subject' => $subject,
                        'room' => $room,
                        'class_size' => rand(15, 35),
                        'grade_level' => rand(1, 12),
                        'semester' => 'Fall 2024',
                        'notes' => rand(0, 10) < 2 ? 'Lab session' : null, // 20% chance of lab
                    ])
                ]);

                $assignedPeriods++;
                $this->command->line("  - Assigned {$subject} in {$room} at {$period->name}");
            }

            $this->command->info("  Total periods assigned: {$assignedPeriods}");
        }

        $this->command->info('Schedule seeding completed successfully!');
        $this->command->info('Total schedule assignments: ' . EmployeeSchedule::count());
        
        // Show some statistics by employee type
        $stats = Employee::selectRaw('employee_type, COUNT(*) as employee_count')
                        ->join('employee_schedules', 'employees.id', '=', 'employee_schedules.employee_id')
                        ->where('employee_schedules.is_active', true)
                        ->groupBy('employee_type')
                        ->get();
        
        $this->command->table(
            ['Employee Type', 'Scheduled Assignments'],
            $stats->map(function($stat) {
                return [ucfirst($stat->employee_type), $stat->employee_count];
            })->toArray()
        );
    }
}