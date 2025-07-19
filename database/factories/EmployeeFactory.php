<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'employee_id' => 'EMP'.fake()->unique()->numberBetween(1000, 9999),
            'full_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'employee_type' => fake()->randomElement(['permanent', 'contract', 'part_time']),
            'position' => fake()->jobTitle(),
            'is_active' => true,
            'location_id' => Location::factory(),
            'date_joined' => fake()->dateTimeBetween('-2 years', 'now'),
            'salary_amount' => fake()->numberBetween(3000000, 15000000),
            'salary_type' => fake()->randomElement(['monthly', 'hourly']),
            'metadata' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the employee is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the employee has face recognition data.
     */
    public function withFaceRecognition(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => [
                'face_recognition' => [
                    'descriptor' => array_fill(0, 128, fake()->randomFloat(3, -1, 1)),
                    'confidence' => fake()->randomFloat(2, 0.7, 0.95),
                    'quality_score' => fake()->randomFloat(2, 0.5, 1.0),
                    'algorithm' => 'face-api.js',
                    'model_version' => '1.0',
                    'registered_at' => now()->toISOString(),
                ],
            ],
        ]);
    }

    /**
     * Indicate that the employee is a permanent staff.
     */
    public function permanent(): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_type' => 'permanent',
            'salary_type' => 'monthly',
            'salary_amount' => fake()->numberBetween(5000000, 15000000),
        ]);
    }

    /**
     * Indicate that the employee is a contract worker.
     */
    public function contract(): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_type' => 'contract',
            'salary_type' => 'monthly',
            'salary_amount' => fake()->numberBetween(3000000, 8000000),
        ]);
    }

    /**
     * Indicate that the employee is part-time.
     */
    public function partTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_type' => 'part_time',
            'salary_type' => 'hourly',
            'salary_amount' => fake()->numberBetween(50000, 150000),
        ]);
    }
}
