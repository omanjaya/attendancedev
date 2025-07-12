<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class MakeAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'make:admin-user {--email=} {--password=} {--name=}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new admin user with employee record';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Creating new admin user...');

        // Get user details
        $email = $this->option('email') ?: $this->ask('Email address');
        $name = $this->option('name') ?: $this->ask('Full name');
        $password = $this->option('password') ?: $this->secret('Password (leave empty for random)');

        // Validate email
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email|unique:users,email'
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('- ' . $error);
            }
            return 1;
        }

        // Generate random password if not provided
        if (empty($password)) {
            $password = $this->generateSecurePassword();
            $this->warn("Generated password: {$password}");
            $this->warn('Please save this password securely!');
        }

        try {
            // Create user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);

            // Assign admin role
            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            $user->assignRole($adminRole);

            // Create or get main location
            $location = Location::firstOrCreate([
                'name' => 'Head Office'
            ], [
                'address' => 'Main Office Location',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'radius' => 100,
                'is_active' => true,
            ]);

            // Create employee record
            $employee = Employee::create([
                'user_id' => $user->id,
                'location_id' => $location->id,
                'employee_id' => 'ADM' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'first_name' => $this->getFirstName($name),
                'last_name' => $this->getLastName($name),
                'position' => 'System Administrator',
                'department' => 'IT',
                'salary_type' => 'monthly',
                'salary_amount' => 10000,
                'hire_date' => now(),
                'is_active' => true,
            ]);

            $this->info('✅ Admin user created successfully!');
            $this->table(
                ['Field', 'Value'],
                [
                    ['User ID', $user->id],
                    ['Employee ID', $employee->employee_id],
                    ['Name', $user->name],
                    ['Email', $user->email],
                    ['Role', 'Admin'],
                    ['Location', $location->name],
                    ['Created', $user->created_at->format('Y-m-d H:i:s')],
                ]
            );

            if (!$this->option('password')) {
                $this->warn('⚠️  Remember to save the generated password!');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to create admin user: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Generate a secure random password
     */
    private function generateSecurePassword(int $length = 12): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*';

        $password = '';
        $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $numbers[rand(0, strlen($numbers) - 1)];
        $password .= $symbols[rand(0, strlen($symbols) - 1)];

        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[rand(0, strlen($allChars) - 1)];
        }

        return str_shuffle($password);
    }

    /**
     * Extract first name from full name
     */
    private function getFirstName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? $fullName;
    }

    /**
     * Extract last name from full name
     */
    private function getLastName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        if (count($parts) > 1) {
            array_shift($parts);
            return implode(' ', $parts);
        }
        return '';
    }
}