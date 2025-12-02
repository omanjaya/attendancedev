<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Holiday;
use Illuminate\Support\Facades\DB;

class HolidaysIndonesia2025Seeder extends Seeder
{
    /**
     * Seed Indonesian holidays for 2025
     */
    public function run(): void
    {
        // Clear existing 2025 holidays
        DB::table('holidays')->whereYear('date', 2025)->delete();

        $holidays = [
            // January
            [
                'name' => 'Tahun Baru 2025',
                'description' => 'Tahun Baru Masehi',
                'date' => '2025-01-01',
                'type' => 'public_holiday',
                'status' => 'active',
                'is_recurring' => true,
                'color' => '#dc3545',
                'is_paid' => true,
            ],

            // March
            [
                'name' => 'Isra Mikraj Nabi Muhammad SAW',
                'description' => 'Hari Besar Islam',
                'date' => '2025-03-08',
                'type' => 'religious_holiday',
                'status' => 'active',
                'color' => '#28a745',
                'is_paid' => true,
            ],
            [
                'name' => 'Hari Suci Nyepi (Tahun Baru Saka 1947)',
                'description' => 'Hari Raya Umat Hindu',
                'date' => '2025-03-29',
                'type' => 'religious_holiday',
                'status' => 'active',
                'color' => '#28a745',
                'is_paid' => true,
            ],
            [
                'name' => 'Wafat Yesus Kristus',
                'description' => 'Hari Raya Umat Kristen',
                'date' => '2025-03-30',
                'type' => 'religious_holiday',
                'status' => 'active',
                'color' => '#28a745',
                'is_paid' => true,
            ],
            [
                'name' => 'Hari Raya Idul Fitri 1446 Hijriah',
                'description' => 'Lebaran - Hari Pertama',
                'date' => '2025-03-31',
                'end_date' => '2025-04-01',
                'type' => 'religious_holiday',
                'status' => 'active',
                'color' => '#28a745',
                'is_paid' => true,
            ],

            // April
            [
                'name' => 'Hari Raya Idul Fitri 1446 Hijriah (Hari Kedua)',
                'description' => 'Lebaran - Hari Kedua',
                'date' => '2025-04-01',
                'type' => 'religious_holiday',
                'status' => 'active',
                'color' => '#28a745',
                'is_paid' => true,
            ],
            [
                'name' => 'Cuti Bersama Idul Fitri',
                'description' => 'Cuti bersama sebelum Lebaran',
                'date' => '2025-03-28',
                'end_date' => '2025-03-29',
                'type' => 'substitute_holiday',
                'status' => 'active',
                'color' => '#ffc107',
                'is_paid' => true,
            ],
            [
                'name' => 'Cuti Bersama Idul Fitri (Sesudah)',
                'description' => 'Cuti bersama setelah Lebaran',
                'date' => '2025-04-02',
                'end_date' => '2025-04-04',
                'type' => 'substitute_holiday',
                'status' => 'active',
                'color' => '#ffc107',
                'is_paid' => true,
            ],

            // May
            [
                'name' => 'Hari Buruh Internasional',
                'description' => 'May Day',
                'date' => '2025-05-01',
                'type' => 'public_holiday',
                'status' => 'active',
                'is_recurring' => true,
                'color' => '#dc3545',
                'is_paid' => true,
            ],
            [
                'name' => 'Kenaikan Yesus Kristus',
                'description' => 'Hari Raya Umat Kristen',
                'date' => '2025-05-29',
                'type' => 'religious_holiday',
                'status' => 'active',
                'color' => '#28a745',
                'is_paid' => true,
            ],

            // June
            [
                'name' => 'Hari Raya Waisak 2569',
                'description' => 'Hari Raya Umat Buddha',
                'date' => '2025-06-05',
                'type' => 'religious_holiday',
                'status' => 'active',
                'color' => '#28a745',
                'is_paid' => true,
            ],
            [
                'name' => 'Hari Lahir Pancasila',
                'description' => 'Peringatan Lahirnya Pancasila',
                'date' => '2025-06-01',
                'type' => 'public_holiday',
                'status' => 'active',
                'is_recurring' => true,
                'color' => '#dc3545',
                'is_paid' => true,
            ],
            [
                'name' => 'Hari Raya Idul Adha 1446 Hijriah',
                'description' => 'Hari Raya Kurban',
                'date' => '2025-06-07',
                'type' => 'religious_holiday',
                'status' => 'active',
                'color' => '#28a745',
                'is_paid' => true,
            ],

            // July
            [
                'name' => 'Tahun Baru Islam 1447 Hijriah',
                'description' => '1 Muharram',
                'date' => '2025-06-27',
                'type' => 'religious_holiday',
                'status' => 'active',
                'color' => '#28a745',
                'is_paid' => true,
            ],

            // August
            [
                'name' => 'Hari Kemerdekaan Republik Indonesia',
                'description' => 'HUT RI ke-80',
                'date' => '2025-08-17',
                'type' => 'public_holiday',
                'status' => 'active',
                'is_recurring' => true,
                'color' => '#dc3545',
                'is_paid' => true,
            ],

            // September
            [
                'name' => 'Maulid Nabi Muhammad SAW',
                'description' => 'Hari Kelahiran Nabi Muhammad SAW',
                'date' => '2025-09-05',
                'type' => 'religious_holiday',
                'status' => 'active',
                'color' => '#28a745',
                'is_paid' => true,
            ],

            // December
            [
                'name' => 'Hari Raya Natal',
                'description' => 'Hari Kelahiran Yesus Kristus',
                'date' => '2025-12-25',
                'type' => 'religious_holiday',
                'status' => 'active',
                'is_recurring' => true,
                'color' => '#28a745',
                'is_paid' => true,
            ],
        ];

        foreach ($holidays as $holiday) {
            Holiday::create($holiday);
        }

        $this->command->info('✅ Indonesian holidays for 2025 seeded successfully!');
        $this->command->info('   Total: ' . count($holidays) . ' holidays added');
    }
}
