<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = Carbon::now()->year;
        $nextYear = $currentYear + 1;

        // Indonesian National and Religious Holidays for current year
        $holidays = [
            // National Holidays (recurring yearly)
            [
                'name' => 'Tahun Baru',
                'description' => 'Perayaan tahun baru Masehi',
                'date' => "{$currentYear}-01-01",
                'type' => Holiday::TYPE_PUBLIC,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => true,
                'recurring_pattern' => ['type' => 'yearly'],
                'color' => '#dc3545',
                'is_paid' => true,
                'source' => 'government',
            ],
            [
                'name' => 'Hari Kemerdekaan RI',
                'description' => 'Hari kemerdekaan Republik Indonesia',
                'date' => "{$currentYear}-08-17",
                'type' => Holiday::TYPE_PUBLIC,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => true,
                'recurring_pattern' => ['type' => 'yearly'],
                'color' => '#dc3545',
                'is_paid' => true,
                'source' => 'government',
            ],
            [
                'name' => 'Hari Raya Natal',
                'description' => 'Perayaan kelahiran Yesus Kristus',
                'date' => "{$currentYear}-12-25",
                'type' => Holiday::TYPE_RELIGIOUS,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => true,
                'recurring_pattern' => ['type' => 'yearly'],
                'color' => '#198754',
                'is_paid' => true,
                'source' => 'government',
            ],

            // Religious Holidays (dates vary yearly - approximate for 2025)
            [
                'name' => 'Maulid Nabi Muhammad SAW',
                'description' => 'Peringatan kelahiran Nabi Muhammad SAW',
                'date' => "{$currentYear}-09-05",
                'type' => Holiday::TYPE_RELIGIOUS,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => false, // Islamic calendar dates vary
                'color' => '#198754',
                'is_paid' => true,
                'source' => 'government',
            ],
            [
                'name' => 'Hari Raya Idul Fitri',
                'description' => 'Perayaan akhir bulan Ramadhan',
                'date' => "{$currentYear}-03-30",
                'end_date' => "{$currentYear}-03-31",
                'type' => Holiday::TYPE_RELIGIOUS,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => false,
                'color' => '#198754',
                'is_paid' => true,
                'source' => 'government',
            ],
            [
                'name' => 'Hari Raya Idul Adha',
                'description' => 'Perayaan hari raya kurban',
                'date' => "{$currentYear}-06-07",
                'type' => Holiday::TYPE_RELIGIOUS,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => false,
                'color' => '#198754',
                'is_paid' => true,
                'source' => 'government',
            ],
            [
                'name' => 'Tahun Baru Hijriah',
                'description' => 'Perayaan tahun baru Islam',
                'date' => "{$currentYear}-07-07",
                'type' => Holiday::TYPE_RELIGIOUS,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => false,
                'color' => '#198754',
                'is_paid' => true,
                'source' => 'government',
            ],
            [
                'name' => 'Isra Miraj',
                'description' => 'Peringatan perjalanan malam Nabi Muhammad SAW',
                'date' => "{$currentYear}-01-27",
                'type' => Holiday::TYPE_RELIGIOUS,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => false,
                'color' => '#198754',
                'is_paid' => true,
                'source' => 'government',
            ],

            // Hindu/Buddhist Holidays
            [
                'name' => 'Hari Raya Nyepi',
                'description' => 'Tahun baru Saka (Hindu)',
                'date' => "{$currentYear}-03-29",
                'type' => Holiday::TYPE_RELIGIOUS,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => false,
                'color' => '#fd7e14',
                'is_paid' => true,
                'source' => 'government',
            ],
            [
                'name' => 'Waisak',
                'description' => 'Perayaan kelahiran, pencerahan, dan wafat Buddha',
                'date' => "{$currentYear}-05-12",
                'type' => Holiday::TYPE_RELIGIOUS,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => false,
                'color' => '#fd7e14',
                'is_paid' => true,
                'source' => 'government',
            ],

            // Substitute Holidays (Cuti Bersama)
            [
                'name' => 'Cuti Bersama Idul Fitri',
                'description' => 'Cuti bersama setelah Idul Fitri',
                'date' => "{$currentYear}-04-01",
                'end_date' => "{$currentYear}-04-04",
                'type' => Holiday::TYPE_SUBSTITUTE,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => false,
                'color' => '#6f42c1',
                'is_paid' => true,
                'source' => 'government',
            ],
            [
                'name' => 'Cuti Bersama Natal dan Tahun Baru',
                'description' => 'Cuti bersama antara Natal dan Tahun Baru',
                'date' => "{$currentYear}-12-26",
                'end_date' => "{$currentYear}-12-31",
                'type' => Holiday::TYPE_SUBSTITUTE,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => false,
                'color' => '#6f42c1',
                'is_paid' => true,
                'source' => 'government',
            ],

            // School Holidays
            [
                'name' => 'Liburan Semester Ganjil',
                'description' => 'Libur akhir semester ganjil',
                'date' => "{$currentYear}-12-16",
                'end_date' => "{$currentYear}-12-31",
                'type' => Holiday::TYPE_SCHOOL,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => false,
                'affected_roles' => ['guru', 'pegawai'],
                'color' => '#20c997',
                'is_paid' => true,
                'source' => 'school_admin',
            ],
            [
                'name' => 'Liburan Semester Genap',
                'description' => 'Libur akhir semester genap/kenaikan kelas',
                'date' => "{$currentYear}-06-15",
                'end_date' => "{$currentYear}-07-15",
                'type' => Holiday::TYPE_SCHOOL,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => true,
                'recurring_pattern' => [
                    'type' => 'relative',
                    'month' => 6,
                    'week' => 3,
                    'day' => 1, // Third Monday of June
                ],
                'affected_roles' => ['guru', 'pegawai'],
                'color' => '#20c997',
                'is_paid' => true,
                'source' => 'school_admin',
            ],

            // Next year's recurring holidays
            [
                'name' => 'Tahun Baru',
                'description' => 'Perayaan tahun baru Masehi',
                'date' => "{$nextYear}-01-01",
                'type' => Holiday::TYPE_PUBLIC,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => true,
                'recurring_pattern' => ['type' => 'yearly'],
                'color' => '#dc3545',
                'is_paid' => true,
                'source' => 'auto_generated',
            ],
            [
                'name' => 'Hari Kemerdekaan RI',
                'description' => 'Hari kemerdekaan Republik Indonesia',
                'date' => "{$nextYear}-08-17",
                'type' => Holiday::TYPE_PUBLIC,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => true,
                'recurring_pattern' => ['type' => 'yearly'],
                'color' => '#dc3545',
                'is_paid' => true,
                'source' => 'auto_generated',
            ],
            [
                'name' => 'Hari Raya Natal',
                'description' => 'Perayaan kelahiran Yesus Kristus',
                'date' => "{$nextYear}-12-25",
                'type' => Holiday::TYPE_RELIGIOUS,
                'status' => Holiday::STATUS_ACTIVE,
                'is_recurring' => true,
                'recurring_pattern' => ['type' => 'yearly'],
                'color' => '#198754',
                'is_paid' => true,
                'source' => 'auto_generated',
            ],
        ];

        foreach ($holidays as $holidayData) {
            Holiday::create($holidayData);
        }

        $this->command->info('Holiday seeder completed!');
        $this->command->info('Created '.count($holidays).' holidays for '.$currentYear.' and '.$nextYear);
        $this->command->info('Includes: National holidays, Religious holidays, School holidays, and Substitute holidays');
    }
}
