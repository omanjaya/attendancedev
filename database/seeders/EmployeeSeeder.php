<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run()
    {
        // Get existing users or create new ones
        $users = User::all();
        
        // If we don't have enough users, create more
        if ($users->count() < 10) {
            $additionalUsers = [
                ['name' => 'John Smith', 'email' => 'john.smith@school.edu', 'role' => 'teacher'],
                ['name' => 'Sarah Johnson', 'email' => 'sarah.johnson@school.edu', 'role' => 'teacher'],
                ['name' => 'Michael Brown', 'email' => 'michael.brown@school.edu', 'role' => 'teacher'],
                ['name' => 'Lisa Wilson', 'email' => 'lisa.wilson@school.edu', 'role' => 'teacher'],
                ['name' => 'David Miller', 'email' => 'david.miller@school.edu', 'role' => 'teacher'],
                ['name' => 'Emma Davis', 'email' => 'emma.davis@school.edu', 'role' => 'staff'],
                ['name' => 'James Garcia', 'email' => 'james.garcia@school.edu', 'role' => 'staff'],
                ['name' => 'Maria Rodriguez', 'email' => 'maria.rodriguez@school.edu', 'role' => 'staff'],
            ];

            foreach ($additionalUsers as $userData) {
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]);
                
                // Assign role
                $user->assignRole($userData['role']);
                $users->push($user);
            }
        }

        // Employee types and salary ranges
        $employeeTypes = [
            'permanent' => ['min' => 45000, 'max' => 85000],
            'honorary' => ['min' => 25, 'max' => 150], // Per hour
            'staff' => ['min' => 30000, 'max' => 60000],
        ];

        $subjects = ['Mathematics', 'English', 'Science', 'History', 'Geography', 'Art', 'Physical Education', 'Computer Science'];
        $departments = ['Academic', 'Administration', 'Sports', 'Arts', 'Science', 'Languages'];

        // Create employees for users that don't have employees
        foreach ($users as $user) {
            // Skip if user already has an employee
            if ($user->employee) {
                continue;
            }

            // Determine employee type based on user role
            $type = match($user->roles->first()?->name) {
                'teacher' => rand(0, 10) < 7 ? 'permanent' : 'honorary', // 70% permanent, 30% honorary
                'staff' => 'permanent',
                'admin', 'superadmin' => 'permanent',
                default => 'staff'
            };

            $salaryRange = $employeeTypes[$type];
            $baseSalary = $type === 'honorary' 
                ? rand($salaryRange['min'], $salaryRange['max']) // Hourly rate
                : rand($salaryRange['min'], $salaryRange['max']); // Annual salary

            $salaryType = $type === 'honorary' ? 'hourly' : 'monthly';
            
            $employee = Employee::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'employee_id' => 'EMP' . str_pad(Employee::count() + 1, 4, '0', STR_PAD_LEFT),
                'employee_type' => $type,
                'first_name' => explode(' ', $user->name)[0],
                'last_name' => explode(' ', $user->name)[1] ?? 'Doe',
                'phone' => '+1-555-' . rand(100, 999) . '-' . rand(1000, 9999),
                'hire_date' => Carbon::now()->subMonths(rand(1, 60)),
                'salary_type' => $salaryType,
                'salary_amount' => $salaryType === 'hourly' ? null : $baseSalary,
                'hourly_rate' => $salaryType === 'hourly' ? $baseSalary : null,
                'is_active' => true,
                'metadata' => json_encode([
                    'email' => $user->email,
                    'address' => rand(100, 9999) . ' ' . ['Main St', 'Oak Ave', 'Pine Rd', 'Elm Dr', 'Maple Way'][array_rand(['Main St', 'Oak Ave', 'Pine Rd', 'Elm Dr', 'Maple Way'])],
                    'city' => ['Springfield', 'Riverside', 'Franklin', 'Georgetown', 'Madison'][array_rand(['Springfield', 'Riverside', 'Franklin', 'Georgetown', 'Madison'])],
                    'state' => ['CA', 'NY', 'TX', 'FL', 'IL'][array_rand(['CA', 'NY', 'TX', 'FL', 'IL'])],
                    'zip_code' => rand(10000, 99999),
                    'emergency_contact_name' => ['Alice Smith', 'Bob Johnson', 'Carol Brown', 'David Wilson'][array_rand(['Alice Smith', 'Bob Johnson', 'Carol Brown', 'David Wilson'])],
                    'emergency_contact_phone' => '+1-555-' . rand(100, 999) . '-' . rand(1000, 9999),
                    'department' => $departments[array_rand($departments)],
                    'position' => $type === 'honorary' ? 'Honorary Teacher' : 
                                ($user->hasRole(['admin', 'superadmin']) ? 'Administrator' : 
                                ($type === 'staff' ? 'Support Staff' : 'Teacher')),
                    'date_of_birth' => Carbon::now()->subYears(rand(25, 60))->subDays(rand(1, 365))->format('Y-m-d'),
                    'subjects' => $type !== 'staff' ? array_slice($subjects, 0, rand(1, 3)) : [],
                    'qualifications' => [
                        'degree' => ['Bachelor\'s', 'Master\'s', 'PhD'][array_rand(['Bachelor\'s', 'Master\'s', 'PhD'])],
                        'certifications' => rand(0, 1) ? ['Teaching License', 'CPR Certified'] : [],
                    ],
                    'face_recognition' => rand(0, 10) < 8 ? [ // 80% have face recognition set up
                        'registered_at' => Carbon::now()->subDays(rand(1, 30))->toISOString(),
                        'descriptor' => base64_encode(json_encode(array_fill(0, 128, rand(-100, 100) / 100))), // Fake face descriptor
                        'confidence_threshold' => 0.85
                    ] : null,
                ])
            ]);

            // Set photo path if needed (30% chance)
            if (rand(0, 10) < 3) {
                $employee->update([
                    'photo_path' => 'photos/employees/' . $employee->id . '.jpg'
                ]);
            }

            $this->command->info("Created employee: {$employee->first_name} {$employee->last_name} ({$employee->employee_id}) - {$employee->employee_type}");
        }
    }
}