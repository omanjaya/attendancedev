<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TestSchedulePermissions extends Command
{
    protected $signature = 'test:schedule-permissions';

    protected $description = 'Test and fix schedule permissions';

    public function handle()
    {
        $this->info('Testing and fixing schedule permissions...');

        // Create missing permissions if they don't exist
        $permissions = [
            'view_schedules',
            'manage_schedules',
            'assign_schedules',
            'create_schedules',
            'edit_schedules',
            'delete_schedules',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
            $this->info("✓ Permission '{$permission}' ensured");
        }

        // Assign permissions to admin roles
        $adminRoles = ['admin', 'Admin', 'superadmin'];

        foreach ($adminRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
                $this->info("✓ Permissions assigned to role '{$roleName}'");
            } else {
                $this->warn("Role '{$roleName}' not found");
            }
        }

        // Show current user info if available
        $user = User::where('email', 'admin@example.com')->orWhere('email', 'admin@admin.com')->first();
        if ($user) {
            $this->info("Admin user found: {$user->name} ({$user->email})");
            $this->info('Roles: '.$user->getRoleNames()->implode(', '));
            $this->info('Permissions: '.$user->getPermissionNames()->implode(', '));
        }

        $this->info('Schedule permissions test completed!');
    }
}
