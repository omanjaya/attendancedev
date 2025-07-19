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
        // Order is important for foreign key relationships
        $this->call([
            // 1. Basic system setup
            RolesAndPermissionsSeeder::class,
            SuperAdminSeeder::class,
            
            // 2. Reference data
            HolidaySeeder::class,
            LocationSeeder::class,
            LeaveTypeSeeder::class,
            PeriodSeeder::class,
            
            // 3. Schedule management data
            ScheduleManagementSeeder::class,
            
            // 4. Users and employees
            UserSeeder::class,
            EmployeeSeeder::class,
            
            // 5. Operational data
            ScheduleSeeder::class,
            AttendanceSeeder::class,
            LeaveSeeder::class,
        ]);

        // Create additional test user if needed
        if (! User::where('email', 'test@example.com')->exists()) {
            $testUser = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
            $testUser->assignRole('guru');
        }

        $this->command->info('🎉 All seeders completed successfully!');
        $this->command->info('📊 Database has been populated with sample data');
        $this->command->info('👤 Default admin: admin@school.com / password');
        $this->command->info('👨‍🏫 Test teacher: test@example.com / password');
    }
}
