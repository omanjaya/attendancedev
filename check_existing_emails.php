<?php
/**
 * Script to check existing emails in database
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Employee;

echo "🔍 Checking existing emails in database...\n\n";

// Find all users with @slub.ac.id emails
$slubUsers = User::where('email', 'like', '%@slub.ac.id')->get();

echo "Found " . $slubUsers->count() . " users with @slub.ac.id domain:\n\n";

foreach ($slubUsers as $user) {
    $employee = Employee::where('user_id', $user->id)->first();
    echo "ID: {$user->id} | Name: {$user->name} | Email: {$user->email}";
    if ($employee) {
        echo " | Employee: {$employee->full_name} | Type: {$employee->employee_type}";
    } else {
        echo " | No Employee Record";
    }
    echo "\n";
}

echo "\n📧 Specific email patterns that might conflict:\n";

$conflictEmails = [
    'sekarariwidiantari@slub.ac.id',
    'rahayukasumawati@slub.ac.id', 
    'ngurahwijaya@slub.ac.id',
    'sekarariwidiantari1@slub.ac.id',
    'rahayukasumawati1@slub.ac.id'
];

foreach ($conflictEmails as $email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        echo "⚠️  CONFLICT: {$email} already exists (User ID: {$user->id}, Name: {$user->name})\n";
    } else {
        echo "✅ Available: {$email}\n";
    }
}

echo "\n📊 Summary:\n";
echo "Total @slub.ac.id users: " . $slubUsers->count() . "\n";
echo "Total employees: " . Employee::count() . "\n";
echo "Users without employee records: " . User::whereDoesntHave('employee')->where('email', 'like', '%@slub.ac.id')->count() . "\n";