<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeachingSchedule>
 */
class TeachingScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dayOfWeek = fake()->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']);
        $startHour = fake()->numberBetween(7, 14); // 7 AM to 2 PM
        $teachingStartTime = sprintf('%02d:00:00', $startHour);
        $teachingEndTime = sprintf('%02d:00:00', $startHour + fake()->numberBetween(1, 3)); // 1-3 hours duration

        return [
            'teacher_id' => \App\Models\Employee::factory(),
            'subject_id' => \App\Models\Subject::factory(),
            'class_id' => null,
            'day_of_week' => $dayOfWeek,
            'teaching_start_time' => $teachingStartTime,
            'teaching_end_time' => $teachingEndTime,
            'effective_from' => now()->subMonths(2),
            'effective_until' => now()->addMonths(6),
            'class_name' => fake()->randomElement(['Kelas 5A', 'Kelas 6B', 'Kelas 10 IPA', 'Kelas 11 IPS', 'Kelas 12 Bahasa']),
            'room' => fake()->randomElement(['Ruang 101', 'Ruang 201', 'Lab Komputer', 'Lab Fisika', 'Aula']),
            'student_count' => fake()->numberBetween(20, 35),
            'is_active' => true,
            'status' => 'scheduled',
            'override_attendance' => true,
            'strict_timing' => true,
            'late_threshold_minutes' => 15,
            'monthly_schedule_id' => null,
            'metadata' => [
                'curriculum' => fake()->randomElement(['K13', 'Merdeka']),
                'semester' => fake()->numberBetween(1, 2),
                'academic_year' => '2024/2025',
                'teaching_method' => fake()->randomElement(['offline', 'online', 'hybrid']),
            ],
            'substitute_teacher_id' => null,
            'substitution_start_date' => null,
            'substitution_end_date' => null,
            'substitution_reason' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
