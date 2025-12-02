<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Check if 'role' column exists in 'users' table
$hasRoleColumn = Schema::hasColumn('users', 'role');
echo "Has 'role' column: " . ($hasRoleColumn ? 'Yes' : 'No') . "\n";

// Try creating a user
try {
    DB::beginTransaction();
    $user = User::create([
        'name' => 'Test User ' . time(),
        'email' => 'test' . time() . '@example.com',
        'password' => bcrypt('password'),
        'role' => 'pegawai', // Try passing role
    ]);
    echo "User created with ID: " . $user->id . "\n";
    DB::rollBack();
} catch (\Exception $e) {
    echo "Error creating user: " . $e->getMessage() . "\n";
    DB::rollBack();
}
