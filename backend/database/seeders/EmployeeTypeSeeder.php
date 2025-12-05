<?php

namespace Database\Seeders;

use App\Models\EmployeeType;
use Illuminate\Database\Seeder;

class EmployeeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Pegawai Tetap',
                'code' => 'tetap',
                'description' => 'Pegawai dengan jadwal kerja tetap setiap hari sesuai jam yang ditentukan',
                'schedule_mode' => 'fixed',
                'default_start_time' => '07:30',
                'default_end_time' => '15:30',
                'late_tolerance_minutes' => 15,
                'require_schedule_for_attendance' => true,
                'can_override_by_teaching' => false,
                'features' => ['can_request_leave', 'can_view_payroll', 'can_overtime'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Guru Honor',
                'code' => 'honor',
                'description' => 'Guru dengan jadwal mengajar fleksibel berdasarkan jadwal pelajaran',
                'schedule_mode' => 'flexible',
                'default_start_time' => null,
                'default_end_time' => null,
                'late_tolerance_minutes' => 15,
                'require_schedule_for_attendance' => true,
                'can_override_by_teaching' => true,
                'features' => ['can_request_leave', 'can_substitute'],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Tenaga Kontrak',
                'code' => 'kontrak',
                'description' => 'Pegawai kontrak dengan periode kerja terbatas',
                'schedule_mode' => 'fixed',
                'default_start_time' => '08:00',
                'default_end_time' => '16:00',
                'late_tolerance_minutes' => 10,
                'require_schedule_for_attendance' => true,
                'can_override_by_teaching' => false,
                'features' => ['can_request_leave'],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Magang',
                'code' => 'magang',
                'description' => 'Pegawai magang dengan periode terbatas untuk pelatihan',
                'schedule_mode' => 'fixed',
                'default_start_time' => '08:00',
                'default_end_time' => '15:00',
                'late_tolerance_minutes' => 10,
                'require_schedule_for_attendance' => true,
                'can_override_by_teaching' => false,
                'features' => [],
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Paruh Waktu',
                'code' => 'paruh_waktu',
                'description' => 'Pegawai dengan jam kerja paruh waktu (setengah hari)',
                'schedule_mode' => 'fixed',
                'default_start_time' => '08:00',
                'default_end_time' => '12:00',
                'late_tolerance_minutes' => 15,
                'require_schedule_for_attendance' => true,
                'can_override_by_teaching' => false,
                'features' => ['can_request_leave'],
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($types as $type) {
            EmployeeType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }

        $this->command->info('Employee types seeded successfully!');
        $this->command->table(
            ['Code', 'Name', 'Schedule Mode'],
            collect($types)->map(fn($t) => [$t['code'], $t['name'], $t['schedule_mode']])->toArray()
        );
    }
}
