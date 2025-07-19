<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        $employees = Employee::where('is_active', true)->get();

        if ($employees->isEmpty()) {
            $this->command->warn('No employees found. Run EmployeeSeeder first.');

            return;
        }

        // Generate attendance for the last 30 days
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        $periods = Period::where('is_active', true)->get();
        $locations = [
            ['latitude' => 40.7128, 'longitude' => -74.006, 'name' => 'Main Campus'],
            ['latitude' => 40.7589, 'longitude' => -73.9851, 'name' => 'Annex Building'],
            ['latitude' => 40.6892, 'longitude' => -74.0445, 'name' => 'Sports Complex'],
        ];

        $this->command->info(
            "Generating attendance records for {$employees->count()} employees over 30 days...",
        );

        foreach ($employees as $employee) {
            $this->command->info("Processing employee: {$employee->first_name} {$employee->last_name}");

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                // Skip weekends for most employees (90% chance)
                if ($date->isWeekend() && rand(0, 10) < 9) {
                    continue;
                }

                // 85% attendance rate (15% chance of being absent)
                if (rand(0, 100) < 15) {
                    continue;
                }

                // Determine work pattern based on employee type
                $workPattern = $this->getWorkPattern($employee, $date);

                if (! $workPattern['should_work']) {
                    continue;
                }

                // Create attendance record
                $checkInTime = $this->generateCheckInTime(
                    $workPattern['start_time'],
                    $employee->employee_type,
                );
                $checkOutTime = $this->generateCheckOutTime(
                    $checkInTime,
                    $workPattern['end_time'],
                    $employee->employee_type,
                );

                $status = $this->determineStatus(
                    $checkInTime,
                    $workPattern['start_time'],
                    $checkOutTime,
                    $workPattern['end_time'],
                );
                $totalHours = $checkOutTime ? $checkInTime->diffInHours($checkOutTime, true) : 0;

                $location = $locations[array_rand($locations)];

                $checkInLat = $location['latitude'] + rand(-50, 50) / 10000;
                $checkInLng = $location['longitude'] + rand(-50, 50) / 10000;
                $checkOutLat = $checkOutTime ? $location['latitude'] + rand(-50, 50) / 10000 : null;
                $checkOutLng = $checkOutTime ? $location['longitude'] + rand(-50, 50) / 10000 : null;

                Attendance::create([
                    'id' => (string) Str::uuid(),
                    'employee_id' => $employee->id,
                    'date' => $date->format('Y-m-d'),
                    'check_in_time' => $checkInTime,
                    'check_out_time' => $checkOutTime,
                    'total_hours' => round($totalHours, 2),
                    'status' => $status,
                    'check_in_confidence' => rand(85, 98) / 100,
                    'check_out_confidence' => $checkOutTime ? rand(85, 98) / 100 : null,
                    'check_in_latitude' => $checkInLat,
                    'check_in_longitude' => $checkInLng,
                    'check_out_latitude' => $checkOutLat,
                    'check_out_longitude' => $checkOutLng,
                    'location_verified' => true,
                    'check_in_notes' => rand(0, 50) === 0 ? $this->getRandomNote() : null,
                    'check_out_notes' => $checkOutTime && rand(0, 50) === 0 ? 'Regular check-out' : null,
                    'metadata' => json_encode([
                        'location_name' => $location['name'],
                        'face_gesture' => ['nod', 'smile', 'blink'][array_rand(['nod', 'smile', 'blink'])],
                        'check_in_accuracy' => rand(5, 20),
                        'check_out_accuracy' => $checkOutTime ? rand(5, 20) : null,
                    ]),
                ]);
            }
        }

        $this->command->info('Attendance seeding completed successfully!');
        $this->command->info('Total attendance records: '.Attendance::count());
    }

    private function getWorkPattern(Employee $employee, Carbon $date): array
    {
        // Different work patterns based on employee type
        return match ($employee->employee_type) {
            'permanent' => [
                'should_work' => true,
                'start_time' => '08:00',
                'end_time' => '17:00',
            ],
            'honorary' => [
                'should_work' => rand(0, 10) < 7, // Honorary teachers work 70% of days
                'start_time' => ['09:00', '10:00', '11:00', '13:00', '14:00'][
                  array_rand(['09:00', '10:00', '11:00', '13:00', '14:00'])
                ],
                'end_time' => ['12:00', '15:00', '16:00', '17:00'][
                  array_rand(['12:00', '15:00', '16:00', '17:00'])
                ],
            ],
            'staff' => [
                'should_work' => true,
                'start_time' => '08:30',
                'end_time' => '16:30',
            ],
            default => [
                'should_work' => true,
                'start_time' => '09:00',
                'end_time' => '17:00',
            ],
        };
    }

    private function generateCheckInTime(string $expectedStart, string $employeeType): Carbon
    {
        $baseTime = Carbon::createFromFormat('H:i', $expectedStart);

        // Add some realistic variance
        $variance = match ($employeeType) {
            'permanent' => rand(-10, 30), // Can be 10 min early to 30 min late
            'honorary' => rand(-5, 15), // Usually more punctual
            'staff' => rand(-15, 20), // Mixed punctuality
            default => rand(-10, 25),
        };

        return $baseTime->addMinutes($variance);
    }

    private function generateCheckOutTime(
        Carbon $checkInTime,
        string $expectedEnd,
        string $employeeType,
    ): ?Carbon {
        // 5% chance of incomplete attendance (no check-out)
        if (rand(0, 100) < 5) {
            return null;
        }

        $baseEndTime = Carbon::createFromFormat('H:i', $expectedEnd);

        // Ensure check-out is after check-in
        $minCheckOut = $checkInTime->copy()->addHours(2); // Minimum 2 hours

        if ($baseEndTime->lt($minCheckOut)) {
            $baseEndTime = $minCheckOut;
        }

        // Add some variance to check-out time
        $variance = match ($employeeType) {
            'permanent' => rand(-20, 60), // Can leave early or stay late
            'honorary' => rand(-10, 30), // Usually stick to schedule
            'staff' => rand(-15, 45), // Mixed patterns
            default => rand(-15, 30),
        };

        return $baseEndTime->addMinutes($variance);
    }

    private function determineStatus(
        Carbon $checkInTime,
        string $expectedStart,
        ?Carbon $checkOutTime,
        string $expectedEnd,
    ): string {
        $expectedStartTime = Carbon::createFromFormat('H:i', $expectedStart);
        $expectedEndTime = Carbon::createFromFormat('H:i', $expectedEnd);

        // No check-out = incomplete
        if (! $checkOutTime) {
            return 'incomplete';
        }

        // Late if more than 15 minutes after expected start
        $isLate = $checkInTime->gt($expectedStartTime->copy()->addMinutes(15));

        return $isLate ? 'late' : 'present';
    }

    private function getRandomNote(): string
    {
        $notes = [
            'Early meeting scheduled',
            'Doctor appointment',
            'Traffic delay',
            'Family emergency',
            'Training session',
            'Conference call',
            'School event preparation',
            'Parent-teacher meeting',
            'Equipment maintenance',
            'Weather-related delay',
        ];

        return $notes[array_rand($notes)];
    }
}
