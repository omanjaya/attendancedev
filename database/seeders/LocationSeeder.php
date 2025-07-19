<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding locations...');

        $locations = [
            [
                'name' => 'SMA Negeri 1 Jakarta',
                'address' => 'Jl. Pendidikan No. 123, Jakarta Pusat',
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'radius_meters' => 100,
                'wifi_ssid' => 'SMANEGA1_WIFI',
                'is_active' => true,
                'metadata' => json_encode([
                    'description' => 'Lokasi utama sekolah',
                    'type' => 'main_building',
                    'capacity' => 2000
                ]),
            ],
            [
                'name' => 'Kantor Guru',
                'address' => 'Gedung A Lantai 2, SMA Negeri 1 Jakarta',
                'latitude' => -6.200050,
                'longitude' => 106.816700,
                'radius_meters' => 50,
                'wifi_ssid' => 'GURU_OFFICE_WIFI',
                'is_active' => true,
                'metadata' => json_encode([
                    'description' => 'Ruang kantor guru dan administrasi',
                    'type' => 'office',
                    'floor' => 2
                ]),
            ],
            [
                'name' => 'Ruang Kelas',
                'address' => 'Gedung B, SMA Negeri 1 Jakarta',
                'latitude' => -6.199950,
                'longitude' => 106.816600,
                'radius_meters' => 75,
                'wifi_ssid' => 'KELAS_WIFI',
                'is_active' => true,
                'metadata' => json_encode([
                    'description' => 'Area ruang kelas utama',
                    'type' => 'classroom',
                    'total_rooms' => 36
                ]),
            ],
            [
                'name' => 'Laboratorium',
                'address' => 'Gedung C, SMA Negeri 1 Jakarta',
                'latitude' => -6.200100,
                'longitude' => 106.816750,
                'radius_meters' => 60,
                'wifi_ssid' => 'LAB_WIFI',
                'is_active' => true,
                'metadata' => json_encode([
                    'description' => 'Kompleks laboratorium IPA',
                    'type' => 'laboratory',
                    'labs' => ['Fisika', 'Kimia', 'Biologi', 'Komputer']
                ]),
            ],
            [
                'name' => 'Perpustakaan',
                'address' => 'Gedung D Lantai 1, SMA Negeri 1 Jakarta',
                'latitude' => -6.199900,
                'longitude' => 106.816550,
                'radius_meters' => 40,
                'wifi_ssid' => 'LIBRARY_WIFI',
                'is_active' => true,
                'metadata' => json_encode([
                    'description' => 'Perpustakaan sekolah',
                    'type' => 'library',
                    'capacity' => 100
                ]),
            ],
            [
                'name' => 'Aula Sekolah',
                'address' => 'Gedung E, SMA Negeri 1 Jakarta',
                'latitude' => -6.200150,
                'longitude' => 106.816800,
                'radius_meters' => 80,
                'wifi_ssid' => 'AULA_WIFI',
                'is_active' => true,
                'metadata' => json_encode([
                    'description' => 'Aula untuk acara besar sekolah',
                    'type' => 'auditorium',
                    'capacity' => 500
                ]),
            ],
        ];

        foreach ($locations as $location) {
            Location::updateOrCreate(
                ['name' => $location['name']],
                $location
            );
        }

        $this->command->info('✅ Locations seeded successfully!');
        $this->command->info('📍 Created ' . count($locations) . ' location checkpoints');
    }
}