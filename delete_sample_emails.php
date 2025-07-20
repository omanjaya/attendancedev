<?php
/**
 * Script to delete all sample emails from the database
 * Run this script via command line: php delete_sample_emails.php
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Employee;

echo "🔍 Searching for sample emails with @slub.ac.id domain...\n";

// Find all users with @slub.ac.id emails
$sampleUsers = User::where('email', 'like', '%@slub.ac.id')->get();

echo "Found " . $sampleUsers->count() . " sample users:\n";

foreach ($sampleUsers as $user) {
    echo "  - {$user->name} ({$user->email})\n";
}

if ($sampleUsers->count() === 0) {
    echo "✅ No sample emails found to delete.\n";
    exit(0);
}

echo "\n⚠️  WARNING: This will permanently delete these users and their associated employee records!\n";
echo "Type 'DELETE' to confirm (case-sensitive): ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if ($confirmation !== 'DELETE') {
    echo "❌ Operation cancelled.\n";
    exit(0);
}

echo "\n🗑️  Deleting sample users and employees...\n";

$deletedCount = 0;
$employeeCount = 0;

foreach ($sampleUsers as $user) {
    try {
        // Delete associated employee record first
        $employee = Employee::where('user_id', $user->id)->first();
        if ($employee) {
            $employee->forceDelete(); // Hard delete
            $employeeCount++;
            echo "  ✓ Deleted employee: {$employee->full_name}\n";
        }
        
        // Delete user account
        $user->forceDelete(); // Hard delete
        $deletedCount++;
        echo "  ✓ Deleted user: {$user->name} ({$user->email})\n";
        
    } catch (Exception $e) {
        echo "  ❌ Error deleting {$user->email}: " . $e->getMessage() . "\n";
    }
}

echo "\n🎉 Cleanup completed!\n";
echo "📊 Summary:\n";
echo "  - Users deleted: {$deletedCount}\n";
echo "  - Employee records deleted: {$employeeCount}\n";
echo "  - Sample emails removed from database\n";