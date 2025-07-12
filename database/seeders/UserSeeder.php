<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Creating users and employees...');

        // Create roles if they don't exist
        $this->createRoles();

        // Create admin user
        $this->createAdminUser();

        // Create sample employees/users
        $this->createSampleUsers();

        $this->command->info('Users and employees created successfully!');
    }

    private function createRoles()
    {
        $roles = [
            'Super Admin' => [
                'admin_access', 'manage_system', 'manage_users', 'manage_employees', 
                'view_employees', 'create_employees', 'edit_employees', 'delete_employees',
                'view_attendance', 'manage_all_attendance', 'view_attendance_reports',
                'view_schedules', 'manage_schedules', 'view_payroll', 'create_payroll',
                'view_payroll_reports', 'approve_leave', 'manage_leave_balances',
                'view_leave_analytics', 'manage_locations', 'view_reports'
            ],
            'Admin' => [
                'manage_employees', 'view_employees', 'create_employees', 'edit_employees',
                'view_attendance', 'manage_all_attendance', 'view_attendance_reports',
                'view_schedules', 'manage_schedules', 'view_payroll', 'approve_leave',
                'view_reports'
            ],
            'Manager' => [
                'view_employees', 'view_attendance', 'view_attendance_reports',
                'view_schedules', 'approve_leave', 'view_reports'
            ],
            'Employee' => [
                'view_own_attendance', 'manage_own_attendance', 'view_schedules'
            ]
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            
            foreach ($permissions as $permissionName) {
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                $role->givePermissionTo($permission);
            }
        }
    }

    private function createAdminUser()
    {
        // Create admin user first
        $adminUser = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@attendance.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_active' => true
        ]);

        // Create admin employee
        $adminEmployee = Employee::create([
            'user_id' => $adminUser->id,
            'employee_id' => 'ADM001',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'phone' => '+62812-3456-7890',
            'hire_date' => '2024-01-01',
            'employee_type' => 'permanent',
            'salary_type' => 'monthly',
            'salary_amount' => 15000000,
            'is_active' => true,
            'metadata' => [
                'position' => 'System Administrator',
                'department' => 'IT',
                'blood_type' => 'O',
                'religion' => 'Islam',
                'marital_status' => 'single',
                'education' => 'S1 Teknik Informatika',
                'address' => 'Jl. Admin No. 1, Jakarta',
                'emergency_contact_name' => 'Emergency Contact',
                'emergency_contact_phone' => '+62812-9876-5432',
                'date_of_birth' => '1985-01-01'
            ]
        ]);

        // Assign Super Admin role
        $adminUser->assignRole('Super Admin');

        $this->command->info('✅ Admin user created: admin@attendance.com / password123');
    }

    private function createSampleUsers()
    {
        $sampleUsers = [
            // Managers
            [
                'employee_id' => 'MGR001',
                'first_name' => 'Budi',
                'last_name' => 'Santoso',
                'email' => 'budi.santoso@attendance.com',
                'password' => 'password123',
                'position' => 'Kepala Sekolah',
                'department' => 'Management',
                'employee_type' => 'permanent',
                'salary' => 12000000,
                'role' => 'Admin'
            ],
            [
                'employee_id' => 'MGR002',
                'first_name' => 'Siti',
                'last_name' => 'Nurhaliza',
                'email' => 'siti.nurhaliza@attendance.com',
                'password' => 'password123',
                'position' => 'Wakil Kepala Sekolah',
                'department' => 'Academic',
                'employee_type' => 'permanent',
                'salary' => 10000000,
                'role' => 'Manager'
            ],

            // Teachers
            [
                'employee_id' => 'TCH001',
                'first_name' => 'Ahmad',
                'last_name' => 'Wijaya',
                'email' => 'ahmad.wijaya@attendance.com',
                'password' => 'password123',
                'position' => 'Guru Matematika',
                'department' => 'Exact Sciences',
                'employee_type' => 'permanent',
                'salary' => 8000000,
                'role' => 'Employee'
            ],
            [
                'employee_id' => 'TCH002',
                'first_name' => 'Dewi',
                'last_name' => 'Lestari',
                'email' => 'dewi.lestari@attendance.com',
                'password' => 'password123',
                'position' => 'Guru Bahasa Indonesia',
                'department' => 'Language',
                'employee_type' => 'permanent',
                'salary' => 7500000,
                'role' => 'Employee'
            ],
            [
                'employee_id' => 'TCH003',
                'first_name' => 'Andi',
                'last_name' => 'Pratama',
                'email' => 'andi.pratama@attendance.com',
                'password' => 'password123',
                'position' => 'Guru Bahasa Inggris',
                'department' => 'Language',
                'employee_type' => 'permanent',
                'salary' => 7500000,
                'role' => 'Employee'
            ],
            [
                'employee_id' => 'TCH004',
                'first_name' => 'Sri',
                'last_name' => 'Mulyani',
                'email' => 'sri.mulyani@attendance.com',
                'password' => 'password123',
                'position' => 'Guru Fisika',
                'department' => 'Exact Sciences',
                'employee_type' => 'permanent',
                'salary' => 8000000,
                'role' => 'Employee'
            ],
            [
                'employee_id' => 'TCH005',
                'first_name' => 'Bambang',
                'last_name' => 'Setiawan',
                'email' => 'bambang.setiawan@attendance.com',
                'password' => 'password123',
                'position' => 'Guru Kimia',
                'department' => 'Exact Sciences',
                'employee_type' => 'permanent',
                'salary' => 8000000,
                'role' => 'Employee'
            ],
            [
                'employee_id' => 'TCH006',
                'first_name' => 'Maya',
                'last_name' => 'Sari',
                'email' => 'maya.sari@attendance.com',
                'password' => 'password123',
                'position' => 'Guru Biologi',
                'department' => 'Exact Sciences',
                'employee_type' => 'permanent',
                'salary' => 7800000,
                'role' => 'Employee'
            ],
            [
                'employee_id' => 'TCH007',
                'first_name' => 'Dedi',
                'last_name' => 'Kurniawan',
                'email' => 'dedi.kurniawan@attendance.com',
                'password' => 'password123',
                'position' => 'Guru Sejarah',
                'department' => 'Social Sciences',
                'employee_type' => 'permanent',
                'salary' => 7200000,
                'role' => 'Employee'
            ],
            [
                'employee_id' => 'TCH008',
                'first_name' => 'Ratna',
                'last_name' => 'Dewi',
                'email' => 'ratna.dewi@attendance.com',
                'password' => 'password123',
                'position' => 'Guru Geografi',
                'department' => 'Social Sciences',
                'employee_type' => 'permanent',
                'salary' => 7200000,
                'role' => 'Employee'
            ],

            // Honorary Teachers
            [
                'employee_id' => 'HON001',
                'first_name' => 'Joko',
                'last_name' => 'Widodo',
                'email' => 'joko.widodo@attendance.com',
                'password' => 'password123',
                'position' => 'Guru Ekonomi',
                'department' => 'Social Sciences',
                'employee_type' => 'honorary',
                'salary' => 4500000,
                'role' => 'Employee'
            ],
            [
                'employee_id' => 'HON002',
                'first_name' => 'Indira',
                'last_name' => 'Kenzo',
                'email' => 'indira.kenzo@attendance.com',
                'password' => 'password123',
                'position' => 'Guru Seni Budaya',
                'department' => 'Arts',
                'employee_type' => 'honorary',
                'salary' => 4000000,
                'role' => 'Employee'
            ],

            // Staff
            [
                'employee_id' => 'STF001',
                'first_name' => 'Eko',
                'last_name' => 'Purnomo',
                'email' => 'eko.purnomo@attendance.com',
                'password' => 'password123',
                'position' => 'Staff Tata Usaha',
                'department' => 'Administration',
                'employee_type' => 'permanent',
                'salary' => 5500000,
                'role' => 'Employee'
            ],
            [
                'employee_id' => 'STF002',
                'first_name' => 'Lina',
                'last_name' => 'Marlina',
                'email' => 'lina.marlina@attendance.com',
                'password' => 'password123',
                'position' => 'Staff Perpustakaan',
                'department' => 'Library',
                'employee_type' => 'permanent',
                'salary' => 4800000,
                'role' => 'Employee'
            ],
            [
                'employee_id' => 'STF003',
                'first_name' => 'Hendra',
                'last_name' => 'Gunawan',
                'email' => 'hendra.gunawan@attendance.com',
                'password' => 'password123',
                'position' => 'Staff IT',
                'department' => 'IT',
                'employee_type' => 'permanent',
                'salary' => 6500000,
                'role' => 'Employee'
            ]
        ];

        foreach ($sampleUsers as $userData) {
            // Create user first
            $user = User::create([
                'name' => $userData['first_name'] . ' ' . $userData['last_name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'email_verified_at' => now(),
                'is_active' => true
            ]);

            // Create employee
            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_id' => $userData['employee_id'],
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'phone' => '+62812-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'hire_date' => now()->subMonths(rand(1, 60))->format('Y-m-d'),
                'employee_type' => $userData['employee_type'],
                'salary_type' => $userData['employee_type'] === 'honorary' ? 'hourly' : 'monthly',
                'salary_amount' => $userData['employee_type'] === 'honorary' ? null : $userData['salary'],
                'hourly_rate' => $userData['employee_type'] === 'honorary' ? 50000 : null,
                'is_active' => true,
                'metadata' => [
                    'position' => $userData['position'],
                    'department' => $userData['department'],
                    'date_of_birth' => now()->subYears(rand(25, 50))->format('Y-m-d'),
                    'address' => 'Jl. Sample No. ' . rand(1, 100) . ', Jakarta',
                    'emergency_contact_name' => 'Emergency Contact',
                    'emergency_contact_phone' => '+62812-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                    'blood_type' => collect(['A', 'B', 'AB', 'O'])->random(),
                    'religion' => collect(['Islam', 'Kristen', 'Hindu', 'Buddha'])->random(),
                    'marital_status' => collect(['single', 'married', 'divorced'])->random(),
                    'education' => collect(['S1', 'S2', 'S3', 'D3'])->random() . ' ' . $userData['department']
                ]
            ]);

            // Assign role
            $user->assignRole($userData['role']);

            $this->command->info("✅ Created user: {$userData['email']} / {$userData['password']} ({$userData['role']})");
        }
    }
}