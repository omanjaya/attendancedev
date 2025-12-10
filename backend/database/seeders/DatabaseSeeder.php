<?php

namespace Database\Seeders;

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
            // 1. Basic system setup (REQUIRED)
            RolesAndPermissionsSeeder::class,
            SuperAdminSeeder::class,
            
            // 2. Reference data
            HolidaySeeder::class,
            HolidaysIndonesia2025Seeder::class,
            LocationSeeder::class,
            LeaveTypeSeeder::class,
            PeriodSeeder::class,
            EmployeeTypeSeeder::class,
            
            // 3. Schedule management data
            ScheduleManagementSeeder::class,
        ]);

        $this->command->info('🎉 All seeders completed successfully!');
        $this->command->info('✅ System is ready for production use');
        $this->command->info('👤 Super Admin: Check SuperAdminSeeder for credentials');
    }
}
