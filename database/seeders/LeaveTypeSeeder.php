<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding leave types...');

        $leaveTypes = [
            [
                'name' => 'Cuti Tahunan',
                'code' => 'ANNUAL',
                'description' => 'Cuti tahunan yang diberikan kepada karyawan',
                'default_days_per_year' => 12,
                'is_paid' => true,
                'requires_approval' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'max_consecutive_days' => 7,
                    'advance_notice_days' => 3,
                    'color' => '#22c55e'
                ]),
            ],
            [
                'name' => 'Cuti Sakit',
                'code' => 'SICK',
                'description' => 'Cuti karena sakit dengan surat dokter',
                'default_days_per_year' => 12,
                'is_paid' => true,
                'requires_approval' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'max_consecutive_days' => 14,
                    'advance_notice_days' => 0,
                    'color' => '#ef4444',
                    'requires_medical_certificate' => true
                ]),
            ],
            [
                'name' => 'Cuti Melahirkan',
                'code' => 'MATERNITY',
                'description' => 'Cuti melahirkan untuk karyawan wanita',
                'default_days_per_year' => 90,
                'is_paid' => true,
                'requires_approval' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'max_consecutive_days' => 90,
                    'advance_notice_days' => 30,
                    'color' => '#ec4899',
                    'gender_specific' => 'female'
                ]),
            ],
            [
                'name' => 'Cuti Menikah',
                'code' => 'MARRIAGE',
                'description' => 'Cuti untuk menikah',
                'default_days_per_year' => 3,
                'is_paid' => true,
                'requires_approval' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'max_consecutive_days' => 3,
                    'advance_notice_days' => 14,
                    'color' => '#8b5cf6',
                    'requires_certificate' => true
                ]),
            ],
            [
                'name' => 'Cuti Duka',
                'code' => 'BEREAVEMENT',
                'description' => 'Cuti karena kematian keluarga',
                'default_days_per_year' => 3,
                'is_paid' => true,
                'requires_approval' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'max_consecutive_days' => 3,
                    'advance_notice_days' => 0,
                    'color' => '#64748b',
                    'requires_certificate' => true
                ]),
            ],
            [
                'name' => 'Cuti Ibadah Haji',
                'code' => 'HAJJ',
                'description' => 'Cuti untuk menjalankan ibadah haji',
                'default_days_per_year' => 40,
                'is_paid' => true,
                'requires_approval' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'max_consecutive_days' => 40,
                    'advance_notice_days' => 60,
                    'color' => '#06b6d4',
                    'religious_leave' => true
                ]),
            ],
            [
                'name' => 'Izin Tidak Masuk',
                'code' => 'PERMISSION',
                'description' => 'Izin tidak masuk untuk keperluan mendadak',
                'default_days_per_year' => 6,
                'is_paid' => false,
                'requires_approval' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'max_consecutive_days' => 2,
                    'advance_notice_days' => 1,
                    'color' => '#f59e0b'
                ]),
            ],
            [
                'name' => 'Cuti Bersama',
                'code' => 'COLLECTIVE',
                'description' => 'Cuti bersama yang ditetapkan pemerintah',
                'default_days_per_year' => 0,
                'is_paid' => true,
                'requires_approval' => false,
                'is_active' => true,
                'metadata' => json_encode([
                    'max_consecutive_days' => null,
                    'advance_notice_days' => 0,
                    'color' => '#3b82f6',
                    'government_mandated' => true
                ]),
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::updateOrCreate(
                ['name' => $leaveType['name']],
                $leaveType
            );
        }

        $this->command->info('✅ Leave types seeded successfully!');
        $this->command->info('📋 Created ' . count($leaveTypes) . ' leave type categories');
    }
}