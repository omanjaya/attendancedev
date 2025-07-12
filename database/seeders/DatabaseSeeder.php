<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            PeriodSeeder::class,
            EmployeeSeeder::class,
            ScheduleSeeder::class,
            AttendanceSeeder::class,
            LeaveSeeder::class,
        ]);

        // Create additional test user if needed
        if (!User::where('email', 'test@example.com')->exists()) {
            $testUser = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
            $testUser->assignRole('teacher');
        }
    }
}
