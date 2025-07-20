<?php
// Simple debug script to check face data in database

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Employee;

echo "=== Checking Face Registration Data ===\n\n";

// Get all users with employees
$users = User::with('employee')->get();

foreach ($users as $user) {
    echo "User: {$user->name} (ID: {$user->id})\n";
    echo "  Email: {$user->email}\n";
    
    if ($user->employee) {
        $employee = $user->employee;
        echo "  Employee: {$employee->full_name} (ID: {$employee->id})\n";
        echo "  Photo Path: " . ($employee->photo_path ? $employee->photo_path : 'None') . "\n";
        
        // Check user table face descriptor
        echo "  User Face Descriptor: " . (!empty($user->face_descriptor) ? 'YES' : 'NO') . "\n";
        if (!empty($user->face_descriptor)) {
            echo "    Type: " . gettype($user->face_descriptor) . "\n";
            echo "    Size: " . (is_array($user->face_descriptor) ? count($user->face_descriptor) : strlen(json_encode($user->face_descriptor))) . "\n";
        }
        
        // Check employee metadata
        echo "  Employee Metadata Face: " . (isset($employee->metadata['face_recognition']['descriptor']) ? 'YES' : 'NO') . "\n";
        if (isset($employee->metadata['face_recognition']['descriptor'])) {
            $descriptor = $employee->metadata['face_recognition']['descriptor'];
            echo "    Type: " . gettype($descriptor) . "\n";
            echo "    Size: " . (is_array($descriptor) ? count($descriptor) : 'not_array') . "\n";
            echo "    Registered At: " . ($employee->metadata['face_recognition']['registered_at'] ?? 'Unknown') . "\n";
        }
        
        // Check accessor
        echo "  Face Registered Accessor: " . ($employee->face_registered ? 'YES' : 'NO') . "\n";
        
    } else {
        echo "  No Employee Record\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

echo "=== End Check ===\n";