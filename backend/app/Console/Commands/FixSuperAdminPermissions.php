<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FixSuperAdminPermissions extends Command
{
    protected $signature = 'permissions:fix-superadmin {email?}';

    protected $description = 'Fix super admin permissions and ensure they have access to everything';

    public function handle()
    {
        $email = $this->argument('email');

        if (! $email) {
            $email = $this->ask('Enter super admin email address');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email {$email} not found!");

            return 1;
        }

        // Ensure superadmin role exists with all permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin']);
        $allPermissions = Permission::all();
        $superAdminRole->syncPermissions($allPermissions);

        // Also ensure "Super Admin" role exists (for compatibility)
        $superAdminSpaceRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdminSpaceRole->syncPermissions($allPermissions);

        // Assign superadmin role to user
        $user->assignRole('superadmin');

        // Clear permission cache
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('✅ Super admin permissions fixed successfully!');
        $this->info("✅ User {$email} now has superadmin role with {$allPermissions->count()} permissions");
        $this->info('✅ Permission cache cleared');

        return 0;
    }
}
