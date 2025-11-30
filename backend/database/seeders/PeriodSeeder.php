<?php

namespace Database\Seeders;

use App\Models\Period;
use Illuminate\Database\Seeder;

class PeriodSeeder extends Seeder
{
    public function run(): void
    {
        $periods = [
            // Monday
            [
                'name' => 'Period 1 - Math',
                'day_of_week' => 1,
                'start_time' => '08:00',
                'end_time' => '08:45',
            ],
            [
                'name' => 'Period 2 - English',
                'day_of_week' => 1,
                'start_time' => '08:50',
                'end_time' => '09:35',
            ],
            [
                'name' => 'Period 3 - Science',
                'day_of_week' => 1,
                'start_time' => '09:40',
                'end_time' => '10:25',
            ],
            ['name' => 'Break', 'day_of_week' => 1, 'start_time' => '10:25', 'end_time' => '10:40'],
            [
                'name' => 'Period 4 - History',
                'day_of_week' => 1,
                'start_time' => '10:40',
                'end_time' => '11:25',
            ],
            [
                'name' => 'Period 5 - PE',
                'day_of_week' => 1,
                'start_time' => '11:30',
                'end_time' => '12:15',
            ],

            // Tuesday
            [
                'name' => 'Period 1 - Science',
                'day_of_week' => 2,
                'start_time' => '08:00',
                'end_time' => '08:45',
            ],
            [
                'name' => 'Period 2 - Math',
                'day_of_week' => 2,
                'start_time' => '08:50',
                'end_time' => '09:35',
            ],
            [
                'name' => 'Period 3 - English',
                'day_of_week' => 2,
                'start_time' => '09:40',
                'end_time' => '10:25',
            ],
            ['name' => 'Break', 'day_of_week' => 2, 'start_time' => '10:25', 'end_time' => '10:40'],
            [
                'name' => 'Period 4 - Art',
                'day_of_week' => 2,
                'start_time' => '10:40',
                'end_time' => '11:25',
            ],
            [
                'name' => 'Period 5 - Music',
                'day_of_week' => 2,
                'start_time' => '11:30',
                'end_time' => '12:15',
            ],

            // Wednesday
            [
                'name' => 'Period 1 - English',
                'day_of_week' => 3,
                'start_time' => '08:00',
                'end_time' => '08:45',
            ],
            [
                'name' => 'Period 2 - History',
                'day_of_week' => 3,
                'start_time' => '08:50',
                'end_time' => '09:35',
            ],
            [
                'name' => 'Period 3 - Math',
                'day_of_week' => 3,
                'start_time' => '09:40',
                'end_time' => '10:25',
            ],
            ['name' => 'Break', 'day_of_week' => 3, 'start_time' => '10:25', 'end_time' => '10:40'],
            [
                'name' => 'Period 4 - Science',
                'day_of_week' => 3,
                'start_time' => '10:40',
                'end_time' => '11:25',
            ],
            [
                'name' => 'Period 5 - Computer',
                'day_of_week' => 3,
                'start_time' => '11:30',
                'end_time' => '12:15',
            ],

            // Thursday
            [
                'name' => 'Period 1 - History',
                'day_of_week' => 4,
                'start_time' => '08:00',
                'end_time' => '08:45',
            ],
            [
                'name' => 'Period 2 - Science',
                'day_of_week' => 4,
                'start_time' => '08:50',
                'end_time' => '09:35',
            ],
            [
                'name' => 'Period 3 - PE',
                'day_of_week' => 4,
                'start_time' => '09:40',
                'end_time' => '10:25',
            ],
            ['name' => 'Break', 'day_of_week' => 4, 'start_time' => '10:25', 'end_time' => '10:40'],
            [
                'name' => 'Period 4 - Math',
                'day_of_week' => 4,
                'start_time' => '10:40',
                'end_time' => '11:25',
            ],
            [
                'name' => 'Period 5 - English',
                'day_of_week' => 4,
                'start_time' => '11:30',
                'end_time' => '12:15',
            ],

            // Friday
            [
                'name' => 'Period 1 - Art',
                'day_of_week' => 5,
                'start_time' => '08:00',
                'end_time' => '08:45',
            ],
            [
                'name' => 'Period 2 - Music',
                'day_of_week' => 5,
                'start_time' => '08:50',
                'end_time' => '09:35',
            ],
            [
                'name' => 'Period 3 - Computer',
                'day_of_week' => 5,
                'start_time' => '09:40',
                'end_time' => '10:25',
            ],
            ['name' => 'Break', 'day_of_week' => 5, 'start_time' => '10:25', 'end_time' => '10:40'],
            [
                'name' => 'Period 4 - PE',
                'day_of_week' => 5,
                'start_time' => '10:40',
                'end_time' => '11:25',
            ],
            [
                'name' => 'Period 5 - Study Hall',
                'day_of_week' => 5,
                'start_time' => '11:30',
                'end_time' => '12:15',
            ],
        ];

        foreach ($periods as $periodData) {
            Period::create($periodData);
        }
    }
}
