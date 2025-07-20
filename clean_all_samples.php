<?php
/**
 * Script to clean ALL sample users except superadmin
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Employee;

echo "🔍 Searching for ALL sample users (excluding superadmin)...\n\n";

// Find superadmin first (to protect it)
$superadmin = User::role('Super Admin')->first();
$superadminId = $superadmin ? $superadmin->id : null;

echo "🛡️  Protected SuperAdmin: " . ($superadmin ? "{$superadmin->name} ({$superadmin->email})" : "Not found") . "\n\n";

// Find all sample users by various patterns
$samplePatterns = [
    '%@slub.ac.id',
    '%sample%',
    '%test%',
    '%demo%',
    '%contoh%'
];

$sampleUsers = collect();

foreach ($samplePatterns as $pattern) {
    $users = User::where('email', 'like', $pattern)
                 ->when($superadminId, fn($q) => $q->where('id', '!=', $superadminId))
                 ->get();
    $sampleUsers = $sampleUsers->merge($users);
}

// Also find users with sample names
$sampleNameUsers = User::where(function($q) {
        $q->where('name', 'like', '%Pande%')
          ->orWhere('name', 'like', '%Putu%')
          ->orWhere('name', 'like', '%Made%')
          ->orWhere('name', 'like', '%Ketut%')
          ->orWhere('name', 'like', '%Wayan%')
          ->orWhere('name', 'like', '%Gede%')
          ->orWhere('name', 'like', '%Kadek%')
          ->orWhere('name', 'like', '%Komang%')
          ->orWhere('name', 'like', '%Nyoman%');
    })
    ->when($superadminId, fn($q) => $q->where('id', '!=', $superadminId))
    ->get();

$sampleUsers = $sampleUsers->merge($sampleNameUsers);

// Remove duplicates
$sampleUsers = $sampleUsers->unique('id');

echo "Found " . $sampleUsers->count() . " sample users to delete:\n\n";

foreach ($sampleUsers as $user) {
    $employee = Employee::where('user_id', $user->id)->first();
    $roles = $user->getRoleNames()->implode(', ');
    echo "  - {$user->name} ({$user->email}) [Roles: {$roles}]";
    if ($employee) {
        echo " | Employee ID: {$employee->employee_id}";
    }
    echo "\n";
}

if ($sampleUsers->count() === 0) {
    echo "✅ No sample users found to delete.\n";
    exit(0);
}

echo "\n⚠️  WARNING: This will permanently delete these users and their associated records!\n";
echo "🛡️  SuperAdmin will be protected and NOT deleted.\n";
echo "Type 'CLEAN_ALL' to confirm (case-sensitive): ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if ($confirmation !== 'CLEAN_ALL') {
    echo "❌ Operation cancelled.\n";
    exit(0);
}

echo "\n🗑️  Deleting sample users and employees...\n";

$deletedUsers = 0;
$deletedEmployees = 0;

foreach ($sampleUsers as $user) {
    try {
        // Skip superadmin (double check)
        if ($user->hasRole('Super Admin')) {
            echo "  🛡️  Skipped SuperAdmin: {$user->name}\n";
            continue;
        }
        
        // Delete associated employee record first
        $employee = Employee::where('user_id', $user->id)->first();
        if ($employee) {
            $employeeId = $employee->employee_id;
            $employeeName = $employee->full_name;
            $employee->forceDelete(); // Hard delete
            $deletedEmployees++;
            echo "  ✓ Deleted employee: {$employeeName} (ID: {$employeeId})\n";
        }
        
        // Delete user account
        $user->forceDelete(); // Hard delete
        $deletedUsers++;
        echo "  ✓ Deleted user: {$user->name} ({$user->email})\n";
        
    } catch (Exception $e) {
        echo "  ❌ Error deleting {$user->email}: " . $e->getMessage() . "\n";
    }
}

echo "\n🎉 Comprehensive cleanup completed!\n";
echo "📊 Summary:\n";
echo "  - Users deleted: {$deletedUsers}\n";
echo "  - Employee records deleted: {$deletedEmployees}\n";
echo "  - SuperAdmin preserved: " . ($superadmin ? "✅ {$superadmin->name}" : "❌ Not found") . "\n";
echo "  - Database ready for fresh import\n";

// Show remaining users
echo "\n📋 Remaining users in database:\n";
$remainingUsers = User::with('roles')->get();
foreach ($remainingUsers as $user) {
    $roles = $user->getRoleNames()->implode(', ');
    echo "  - {$user->name} ({$user->email}) [Roles: {$roles}]\n";
}