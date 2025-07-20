<?php
/**
 * Debug DataTable issue - check what's happening with employees
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Employee;

echo "🔍 Debugging DataTable Issue...\n\n";

// Check total users
$totalUsers = User::count();
echo "Total Users in database: {$totalUsers}\n";

// Check total employees
$totalEmployees = Employee::count();
echo "Total Employees in database: {$totalEmployees}\n\n";

// Check if employees have users
$employeesWithUsers = Employee::whereHas('user')->count();
echo "Employees with User accounts: {$employeesWithUsers}\n";

$employeesWithoutUsers = Employee::whereDoesntHave('user')->count();
echo "Employees WITHOUT User accounts: {$employeesWithoutUsers}\n\n";

// Check if users have employees
$usersWithEmployees = User::whereHas('employee')->count();
echo "Users with Employee records: {$usersWithEmployees}\n";

$usersWithoutEmployees = User::whereDoesntHave('employee')->count();
echo "Users WITHOUT Employee records: {$usersWithoutEmployees}\n\n";

// Show sample data
echo "📋 Sample Employee Data (first 5):\n";
$sampleEmployees = Employee::with('user')->limit(5)->get();

foreach ($sampleEmployees as $emp) {
    $userId = $emp->user_id ?? 'NULL';
    $userName = $emp->user ? $emp->user->name : 'NO USER';
    $userEmail = $emp->user ? $emp->user->email : 'NO EMAIL';
    
    echo "Employee ID: {$emp->id} | User ID: {$userId} | Name: {$emp->full_name} | User: {$userName} | Email: {$userEmail}\n";
}

echo "\n📋 Sample User Data (first 5):\n";
$sampleUsers = User::with('employee')->limit(5)->get();

foreach ($sampleUsers as $user) {
    $empId = $user->employee ? $user->employee->id : 'NO EMPLOYEE';
    $empName = $user->employee ? $user->employee->full_name : 'NO EMPLOYEE RECORD';
    
    echo "User ID: {$user->id} | Name: {$user->name} | Email: {$user->email} | Employee ID: {$empId} | Employee: {$empName}\n";
}

echo "\n🔗 Relationship Issues:\n";

// Check for broken relationships
$brokenEmployees = Employee::whereNotNull('user_id')
    ->whereDoesntHave('user')
    ->get();

if ($brokenEmployees->count() > 0) {
    echo "⚠️  Found {$brokenEmployees->count()} employees with broken user relationships:\n";
    foreach ($brokenEmployees as $emp) {
        echo "  - Employee ID: {$emp->id} | Name: {$emp->full_name} | Broken User ID: {$emp->user_id}\n";
    }
} else {
    echo "✅ No broken employee->user relationships found\n";
}

// Check for orphaned users
$orphanedUsers = User::whereDoesntHave('employee')->get();
if ($orphanedUsers->count() > 0) {
    echo "⚠️  Found {$orphanedUsers->count()} users without employee records:\n";
    foreach ($orphanedUsers as $user) {
        echo "  - User ID: {$user->id} | Name: {$user->name} | Email: {$user->email}\n";
    }
} else {
    echo "✅ No orphaned users found\n";
}

echo "\n📊 Status Summary:\n";
$activeEmployees = Employee::where('is_active', true)->count();
$inactiveEmployees = Employee::where('is_active', false)->count();

echo "Active Employees: {$activeEmployees}\n";
echo "Inactive Employees: {$inactiveEmployees}\n";

echo "\n🔧 DataTable Query Test:\n";
try {
    // Simulate the same query that DataTable uses
    $employees = Employee::with(['user', 'location'])
        ->whereHas('user') // Only employees with users
        ->get();
    
    echo "✅ DataTable query successful\n";
    echo "Records found: {$employees->count()}\n";
    
    if ($employees->count() > 0) {
        echo "Sample record:\n";
        $first = $employees->first();
        echo "  - ID: {$first->id}\n";
        echo "  - Name: {$first->full_name}\n";
        echo "  - Email: " . ($first->user ? $first->user->email : 'NO EMAIL') . "\n";
        echo "  - Department: " . ($first->location ? $first->location->name : 'N/A') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ DataTable query failed: " . $e->getMessage() . "\n";
}

echo "\nDone! 🎉\n";