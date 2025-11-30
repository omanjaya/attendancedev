<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create impersonation permission
        $permission = Permission::firstOrCreate([
            'name' => 'impersonate_users',
            'guard_name' => 'web',
        ]);

        // Assign to Super Admin and Admin roles
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        $adminRole = Role::where('name', 'Admin')->first();

        if ($superAdminRole && ! $superAdminRole->hasPermissionTo($permission)) {
            $superAdminRole->givePermissionTo($permission);
        }

        if ($adminRole && ! $adminRole->hasPermissionTo($permission)) {
            $adminRole->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove impersonation permission
        Permission::where('name', 'impersonate_users')->delete();
    }
};
