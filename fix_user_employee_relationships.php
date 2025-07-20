<?php
/**
 * Fix User-Employee relationships to ensure DataTable shows data
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Employee;

echo "🔧 Fixing User-Employee Relationships...\n\n";

// Step 1: Check current state
$totalUsers = User::count();
$totalEmployees = Employee::count();
$employeesWithUsers = Employee::whereHas('user')->count();
$usersWithEmployees = User::whereHas('employee')->count();

echo "Current State:\n";
echo "- Total Users: {$totalUsers}\n";
echo "- Total Employees: {$totalEmployees}\n";
echo "- Employees with Users: {$employeesWithUsers}\n";
echo "- Users with Employees: {$usersWithEmployees}\n\n";

// Step 2: Find employees without user accounts
$employeesWithoutUsers = Employee::whereDoesntHave('user')->get();

if ($employeesWithoutUsers->count() > 0) {
    echo "⚠️  Found {$employeesWithoutUsers->count()} employees without user accounts:\n";
    foreach ($employeesWithoutUsers as $emp) {
        echo "  - Employee ID: {$emp->id} | Name: {$emp->full_name} | User ID: {$emp->user_id}\n";
    }
    echo "\n";
    
    echo "🔨 Creating missing user accounts...\n";
    
    foreach ($employeesWithoutUsers as $employee) {
        try {
            // Create user account for employee
            $userData = [
                'name' => $employee->full_name,
                'email' => $employee->phone ? $employee->phone . '@generated.local' : 'user' . $employee->id . '@generated.local',
                'password' => bcrypt('password123'),
                'email_verified_at' => now()
            ];
            
            $user = User::create($userData);
            
            // Assign default role
            $user->assignRole('pegawai');
            
            // Update employee with user_id
            $employee->update(['user_id' => $user->id]);
            
            echo "  ✅ Created user for: {$employee->full_name} (Email: {$user->email})\n";
            
        } catch (Exception $e) {
            echo "  ❌ Failed to create user for {$employee->full_name}: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "✅ All employees have user accounts\n";
}

// Step 3: Find users without employee records
$usersWithoutEmployees = User::whereDoesntHave('employee')->get();

if ($usersWithoutEmployees->count() > 0) {
    echo "\n⚠️  Found {$usersWithoutEmployees->count()} users without employee records:\n";
    foreach ($usersWithoutEmployees as $user) {
        echo "  - User ID: {$user->id} | Name: {$user->name} | Email: {$user->email}\n";
    }
    
    echo "\n🔨 Creating missing employee records...\n";
    
    foreach ($usersWithoutEmployees as $user) {
        try {
            // Skip superadmin
            if ($user->hasRole('Super Admin')) {
                echo "  🛡️  Skipping SuperAdmin: {$user->name}\n";
                continue;
            }
            
            // Generate employee ID based on existing pattern
            $existingCount = Employee::count();
            $nextNum = $existingCount + 1;
            
            // Check existing employee_id patterns to determine format
            $sampleEmployee = Employee::whereNotNull('employee_id')->first();
            if ($sampleEmployee && preg_match('/([A-Z]+)(\d+)/', $sampleEmployee->employee_id, $matches)) {
                $prefix = $matches[1];
                $employeeId = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
            } else {
                $employeeId = 'EMP' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
            }
            
            // Create employee record
            $employeeData = [
                'employee_id' => $employeeId,
                'user_id' => $user->id,
                'full_name' => $user->name,
                'phone' => null,
                'employee_type' => 'staff',
                'hire_date' => now(),
                'salary_type' => 'monthly',
                'base_salary' => 3000000,
                'hourly_rate' => 0,
                'is_active' => true,
                'metadata' => []
            ];
            
            $employee = Employee::create($employeeData);
            
            echo "  ✅ Created employee for: {$user->name} (ID: {$employeeId})\n";
            
        } catch (Exception $e) {
            echo "  ❌ Failed to create employee for {$user->name}: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "✅ All users have employee records\n";
}

// Step 4: Fix broken relationships
echo "\n🔧 Checking for broken relationships...\n";

$brokenEmployees = Employee::whereNotNull('user_id')
    ->whereDoesntHave('user')
    ->get();

if ($brokenEmployees->count() > 0) {
    echo "⚠️  Found {$brokenEmployees->count()} employees with broken user relationships\n";
    
    foreach ($brokenEmployees as $employee) {
        echo "  - Fixing Employee ID: {$employee->id} | Name: {$employee->full_name}\n";
        
        // Try to find user by email or create new one
        $email = $employee->phone ? $employee->phone . '@generated.local' : 'emp' . $employee->id . '@generated.local';
        
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $employee->full_name,
                'password' => bcrypt('password123'),
                'email_verified_at' => now()
            ]
        );
        
        $user->assignRole('pegawai');
        $employee->update(['user_id' => $user->id]);
        
        echo "    ✅ Fixed relationship with user: {$user->email}\n";
    }
} else {
    echo "✅ No broken relationships found\n";
}

// Step 5: Final verification
echo "\n📊 Final Status:\n";
$newEmployeesWithUsers = Employee::whereHas('user')->count();
$newUsersWithEmployees = User::whereHas('employee')->count();

echo "- Employees with Users: {$newEmployeesWithUsers}\n";
echo "- Users with Employees: {$newUsersWithEmployees}\n";

// Test DataTable query
echo "\n🧪 Testing DataTable Query:\n";
try {
    $employees = Employee::with(['user', 'location'])
        ->whereHas('user')
        ->get();
    
    echo "✅ DataTable query successful! Found {$employees->count()} records\n";
    
} catch (Exception $e) {
    echo "❌ DataTable query failed: " . $e->getMessage() . "\n";
}

echo "\n🎉 Relationship fix completed!\n";
echo "You should now see all employees in the DataTable.\n";