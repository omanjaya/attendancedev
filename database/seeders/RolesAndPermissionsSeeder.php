<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Attendance permissions
            'view_attendance',
            'manage_own_attendance',
            'manage_all_attendance',
            'view_attendance_reports',
            
            // Employee permissions
            'view_employees',
            'create_employees',
            'edit_employees',
            'delete_employees',
            'manage_employees',
            
            // Schedule permissions
            'view_schedules',
            'manage_schedules',
            'assign_schedules',
            
            // Leave permissions
            'view_own_leave',
            'submit_leave',
            'view_all_leave',
            'approve_leave',
            'reject_leave',
            'manage_leave_balances',
            'view_leave_balance_history',
            
            // Payroll permissions
            'view_own_payroll',
            'view_all_payroll',
            'manage_payroll',
            'approve_payroll',
            'export_payroll',
            
            // Analytics permissions
            'view_analytics',
            'view_reports',
            'create_reports',
            'export_analytics',
            'view_advanced_analytics',
            
            // System permissions
            'manage_system',
            'manage_settings',
            'manage_locations',
            'manage_permissions',
            'view_audit_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Superadmin - has all permissions
        $superadmin = Role::create(['name' => 'superadmin']);
        $superadmin->givePermissionTo(Permission::all());

        // Admin - has most permissions except system management
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'view_attendance',
            'manage_all_attendance',
            'view_attendance_reports',
            'view_employees',
            'create_employees',
            'edit_employees',
            'delete_employees',
            'manage_employees',
            'view_schedules',
            'manage_schedules',
            'assign_schedules',
            'view_all_leave',
            'approve_leave',
            'reject_leave',
            'manage_leave_balances',
            'view_leave_balance_history',
            'view_all_payroll',
            'manage_payroll',
            'approve_payroll',
            'export_payroll',
            'view_analytics',
            'view_reports',
            'create_reports',
            'export_analytics',
            'view_advanced_analytics',
            'manage_locations',
            'view_audit_logs',
        ]);

        // Teacher - limited permissions
        $teacher = Role::create(['name' => 'teacher']);
        $teacher->givePermissionTo([
            'view_attendance',
            'manage_own_attendance',
            'view_own_leave',
            'submit_leave',
            'view_schedules',
            'view_own_payroll',
            'view_leave_balance_history',
            'view_analytics',
            'view_reports',
        ]);

        // Staff - basic permissions
        $staff = Role::create(['name' => 'staff']);
        $staff->givePermissionTo([
            'view_attendance',
            'manage_own_attendance',
            'view_own_leave',
            'submit_leave',
            'view_own_payroll',
            'view_leave_balance_history',
        ]);
    }
}
