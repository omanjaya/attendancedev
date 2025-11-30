<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'name' => fake()->randomElement([
                'Jakarta Office',
                'Bandung Branch',
                'Surabaya Division',
                'Medan Center',
                'Yogyakarta Hub',
                'IT Department',
                'HR Department',
                'Finance Department',
                'Operations Team',
                'Customer Service',
            ]),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(-8, -5), // Indonesia latitude range
            'longitude' => fake()->longitude(95, 141), // Indonesia longitude range
            'radius_meters' => fake()->numberBetween(50, 500),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the location is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a headquarters location.
     */
    public function headquarters(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Headquarters',
            'address' => 'Jl. Sudirman No. 1, Jakarta Pusat',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'radius_meters' => 200,
        ]);
    }

    /**
     * Create a branch office location.
     */
    public function branch(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->city().' Branch',
            'radius_meters' => fake()->numberBetween(100, 300),
        ]);
    }

    /**
     * Create a department location.
     */
    public function department(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->randomElement([
                'IT Department',
                'HR Department',
                'Finance Department',
                'Operations Department',
                'Marketing Department',
                'Sales Department',
            ]).' - '.fake()->city(),
            'radius_meters' => fake()->numberBetween(50, 150),
        ]);
    }
}
