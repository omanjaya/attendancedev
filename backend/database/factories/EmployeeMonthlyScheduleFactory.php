<?php

namespace Database\Factories;

use App\Models\EmployeeMonthlySchedule;
use App\Models\Employee;
use App\Models\MonthlySchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeMonthlyScheduleFactory extends Factory
{
    protected $model = EmployeeMonthlySchedule::class;

    public function definition()
    {
        return [
            'employee_id' => Employee::factory(),
            'monthly_schedule_id' => MonthlySchedule::factory(),
            'effective_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'is_active' => true,
        ];
    }
}
