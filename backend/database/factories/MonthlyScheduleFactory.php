<?php

namespace Database\Factories;

use App\Models\MonthlySchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonthlyScheduleFactory extends Factory
{
    protected $model = MonthlySchedule::class;

    public function definition()
    {
        return [
            'name' => $this->faker->monthName . ' ' . $this->faker->year,
            'month' => $this->faker->numberBetween(1, 12),
            'year' => (int) $this->faker->year,
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
            'working_days' => json_encode(['Mon', 'Tue', 'Wed', 'Thu', 'Fri']),
            'working_hours' => 8,
            'late_tolerance_minutes' => 15,
            'is_active' => true,
        ];
    }
}
