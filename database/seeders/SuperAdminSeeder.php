<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@school.edu'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Assign superadmin role
        if (! $superAdmin->hasRole('Super Admin')) {
            $superAdmin->assignRole('Super Admin');
        }

        // Ensure superadmin has ALL permissions
        $superadminRole = \Spatie\Permission\Models\Role::where('name', 'Super Admin')->first();
        $allPermissions = \Spatie\Permission\Models\Permission::all();
        $superadminRole->syncPermissions($allPermissions);

        // Create employee record for superadmin
        $employee = Employee::firstOrCreate(
            ['user_id' => $superAdmin->id],
            [
                'id' => (string) Str::uuid(),
                'employee_id' => 'ADMIN001',
                'employee_type' => 'permanent',
                'full_name' => $superAdmin->name,
                'phone' => '+1-555-000-0001',
                'hire_date' => Carbon::now()->subYears(5),
                'salary_type' => 'monthly',
                'salary_amount' => 100000,
                'is_active' => true,
                'metadata' => json_encode([
                    'email' => $superAdmin->email,
                    'address' => '123 Admin Street',
                    'city' => 'Administrative City',
                    'state' => 'CA',
                    'zip_code' => '90210',
                    'emergency_contact_name' => 'System Administrator',
                    'emergency_contact_phone' => '+1-555-000-0002',
                    'department' => 'Administration',
                    'position' => 'System Administrator',
                    'date_of_birth' => Carbon::now()->subYears(35)->format('Y-m-d'),
                    'subjects' => [],
                    'qualifications' => [
                        'degree' => 'Master\'s in Computer Science',
                        'certifications' => ['System Administration', 'Security Management'],
                    ],
                    'face_recognition' => null,
                ]),
            ]);

        // Create Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@school.edu'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Assign admin role
        if (! $admin->hasRole('Admin')) {
            $admin->assignRole('Admin');
        }

        // Create employee record for admin
        $adminEmployee = Employee::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'id' => (string) Str::uuid(),
                'employee_id' => 'ADMIN002',
                'employee_type' => 'permanent',
                'full_name' => $admin->name,
                'phone' => '+1-555-000-0003',
                'hire_date' => Carbon::now()->subYears(3),
                'salary_type' => 'monthly',
                'salary_amount' => 80000,
                'is_active' => true,
                'metadata' => json_encode([
                    'email' => $admin->email,
                    'address' => '456 Admin Avenue',
                    'city' => 'Administrative City',
                    'state' => 'CA',
                    'zip_code' => '90210',
                    'emergency_contact_name' => 'Admin Support',
                    'emergency_contact_phone' => '+1-555-000-0004',
                    'department' => 'Administration',
                    'position' => 'Administrator',
                    'date_of_birth' => Carbon::now()->subYears(32)->format('Y-m-d'),
                    'subjects' => [],
                    'qualifications' => [
                        'degree' => 'Bachelor\'s in Business Administration',
                        'certifications' => ['Management', 'HR Management'],
                    ],
                    'face_recognition' => null,
                ]),
            ]);

        // Create Kepala Sekolah (Principal) user
        $principal = User::firstOrCreate(
            ['email' => 'kepala@school.edu'],
            [
                'name' => 'Kepala Sekolah',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Assign admin role to principal (as they have administrative privileges)
        if (! $principal->hasRole('Admin')) {
            $principal->assignRole('Admin');
        }

        // Create employee record for principal
        $principalEmployee = Employee::firstOrCreate(
            ['user_id' => $principal->id],
            [
                'id' => (string) Str::uuid(),
                'employee_id' => 'ADMIN003',
                'employee_type' => 'permanent',
                'full_name' => $principal->name,
                'phone' => '+1-555-000-0005',
                'hire_date' => Carbon::now()->subYears(10),
                'salary_type' => 'monthly',
                'salary_amount' => 120000,
                'is_active' => true,
                'metadata' => json_encode([
                    'email' => $principal->email,
                    'address' => '789 Principal Plaza',
                    'city' => 'Educational City',
                    'state' => 'CA',
                    'zip_code' => '90211',
                    'emergency_contact_name' => 'Principal Support',
                    'emergency_contact_phone' => '+1-555-000-0006',
                    'department' => 'Leadership',
                    'position' => 'Principal',
                    'date_of_birth' => Carbon::now()->subYears(45)->format('Y-m-d'),
                    'subjects' => [],
                    'qualifications' => [
                        'degree' => 'Master\'s in Educational Leadership',
                        'certifications' => ['Educational Leadership', 'School Management'],
                    ],
                    'face_recognition' => null,
                ]),
            ]);

        $this->command->info('Super Admin created: superadmin@school.edu (password: password)');
        $this->command->info('Admin created: admin@school.edu (password: password)');
        $this->command->info('Principal created: kepala@school.edu (password: password)');
    }
}
