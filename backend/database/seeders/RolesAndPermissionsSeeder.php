<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all standardized permissions
        $permissions = [
            // Attendance Management
            'view_attendance_own',           // View own attendance records
            'view_attendance_all',           // View all attendance records
            'manage_attendance_own',         // Check in/out for self
            'manage_attendance_all',         // Manage any employee attendance
            'view_attendance_reports',       // View attendance analytics
            'export_attendance_data',        // Export attendance data

            // Employee Management
            'view_employees',                // View employee list and details
            'create_employees',              // Add new employees
            'edit_employees',                // Modify employee information
            'delete_employees',              // Remove employees
            'manage_employees',              // Full employee management
            'export_employees_data',         // Export employee data
            'import_employees_data',         // Import employee data from Excel/CSV

            // Leave Management
            'view_leave_own',                // View own leave records
            'view_leave_all',                // View all leave records
            'create_leave_requests',         // Submit leave requests
            'approve_leave',                 // Approve leave requests
            'reject_leave',                  // Reject leave requests
            'manage_leave_balances',         // Adjust leave balances
            'view_leave_analytics',          // View leave statistics

            // Payroll Management
            'view_payroll_own',              // View own payroll
            'view_payroll_all',              // View all payroll
            'create_payroll',                // Generate payroll
            'edit_payroll',                  // Modify payroll records
            'delete_payroll',                // Remove payroll records
            'approve_payroll',               // Approve payroll
            'process_payroll',               // Process payroll payments
            'export_payroll_reports',        // Export payroll data

            // Schedule Management
            'view_schedules',                // View schedules
            'create_schedules',              // Create new schedules
            'edit_schedules',                // Modify schedules
            'delete_schedules',              // Remove schedules
            'assign_schedules',              // Assign employees to schedules
            'lock_schedules',                // Lock/unlock schedules
            'resolve_schedule_conflicts',    // Resolve scheduling conflicts

            // System Administration
            'access_admin_panel',            // Access admin features
            'manage_users',                  // User account management
            'manage_permissions',            // Role and permission management
            'manage_system_settings',        // System configuration
            'manage_locations',              // Location management
            'manage_backups',                // Backup management
            'view_audit_logs',               // View system logs
            'view_security_logs',            // View security events

            // Reports & Analytics
            'view_reports',                  // View basic reports
            'create_reports',                // Generate custom reports
            'view_analytics',                // View analytics dashboard
            'view_advanced_analytics',       // Advanced analytics
            'export_analytics_data',         // Export analytics

            // Security & Privacy
            'manage_user_security',          // Manage user security settings
            'manage_security_settings',      // System security configuration
            'view_security_dashboard',       // Access security monitoring
            'impersonate_users',             // Login as other users (super admin only)

            // Holiday Management
            'view_holidays',                 // View holiday calendar and list
            'create_holidays',               // Create new holidays
            'edit_holidays',                 // Edit existing holidays
            'delete_holidays',               // Delete holidays
            'manage_holidays',               // Full holiday management (import/export)

            // Legacy/Compatibility Permissions (will be deprecated)
            'view_attendance',               // @deprecated - use view_attendance_own or view_attendance_all
            'manage_own_attendance',         // @deprecated - use manage_attendance_own
            'view_leave',                    // @deprecated - use view_leave_own or view_leave_all
            'submit_leave',                  // @deprecated - use create_leave_requests
            'request_leave',                 // @deprecated - use create_leave_requests
            'view_payroll',                  // @supported - alias for view_payroll_own
            'manage_payroll',                // @deprecated - use specific payroll permissions
            'manage_schedules',              // @supported - alias for schedule management
            'manage_system',                 // @supported - alias for system management
            'manage_settings',               // @deprecated - use manage_system_settings
            'admin_access',                  // @deprecated - use access_admin_panel
            'view_all_attendance',           // @deprecated - use view_attendance_all
            'view_all_leaves',               // @deprecated - use view_leave_all
            'view_all_leave',                // @deprecated - use view_leave_all
            'manage_all_attendance',         // @deprecated - use manage_attendance_all
            'view_users',                    // @deprecated - covered by manage_users
            'checkin_checkout',              // @deprecated - use manage_attendance_own
            'record_own_attendance',         // @deprecated - use manage_attendance_own
            'export_payroll',                // @deprecated - use export_payroll_reports
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - has all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions(Permission::all());

        // Admin - comprehensive management permissions
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->syncPermissions([
            // Attendance
            'view_attendance_all',
            'manage_attendance_all',
            'view_attendance_reports',
            'export_attendance_data',

            // Employees
            'view_employees',
            'create_employees',
            'edit_employees',
            'delete_employees',
            'manage_employees',
            'export_employees_data',
            'import_employees_data',

            // Leave
            'view_leave_all',
            'approve_leave',
            'reject_leave',
            'manage_leave_balances',
            'view_leave_analytics',

            // Payroll
            'view_payroll_all',
            'create_payroll',
            'edit_payroll',
            'approve_payroll',
            'process_payroll',
            'export_payroll_reports',

            // Schedules
            'view_schedules',
            'create_schedules',
            'edit_schedules',
            'delete_schedules',
            'assign_schedules',
            'lock_schedules',
            'resolve_schedule_conflicts',

            // Reports
            'view_reports',
            'create_reports',
            'view_advanced_analytics',
            'export_analytics_data',

            // System (limited)
            'manage_locations',
            'view_audit_logs',
            'view_security_logs',
            'view_security_dashboard',

            // Holiday Management
            'view_holidays',
            'create_holidays',
            'edit_holidays',
            'manage_holidays',

            // Legacy permissions for backward compatibility
            'view_attendance',
            'manage_all_attendance',
            'view_all_leave',
            'view_payroll',
            'manage_payroll',
            'manage_schedules',
            'view_users',
        ]);

        // Kepala Sekolah (Principal) - oversight and approval permissions
        $principal = Role::firstOrCreate(['name' => 'kepala_sekolah']);
        $principal->syncPermissions([
            // Attendance
            'view_attendance_own',
            'view_attendance_all',
            'manage_attendance_own',
            'view_attendance_reports',

            // Employees
            'view_employees',
            'import_employees_data',

            // Leave
            'view_leave_own',
            'view_leave_all',
            'create_leave_requests',
            'approve_leave',
            'reject_leave',
            'view_leave_analytics',

            // Payroll
            'view_payroll_own',
            'view_payroll_all',
            'approve_payroll',

            // Schedules
            'view_schedules',

            // Reports
            'view_reports',
            'view_advanced_analytics',

            // Holiday Management (read-only for Principal)
            'view_holidays',

            // Legacy permissions
            'view_attendance',
            'manage_own_attendance',
            'view_leave',
            'submit_leave',
            'view_payroll',
        ]);

        // Guru (Teacher) - basic operational permissions
        $teacher = Role::firstOrCreate(['name' => 'guru']);
        $teacher->syncPermissions([
            // Attendance
            'view_attendance_own',
            'manage_attendance_own',

            // Leave
            'view_leave_own',
            'create_leave_requests',

            // Payroll
            'view_payroll_own',

            // Schedules
            'view_schedules',

            // Legacy permissions
            'view_attendance',
            'manage_own_attendance',
            'view_leave',
            'submit_leave',
            'view_payroll',
            'checkin_checkout',
            'record_own_attendance',
            'request_leave',
        ]);

        // Pegawai (Staff) - minimal operational permissions
        $staff = Role::firstOrCreate(['name' => 'pegawai']);
        $staff->syncPermissions([
            // Attendance
            'view_attendance_own',
            'manage_attendance_own',

            // Leave
            'view_leave_own',
            'create_leave_requests',

            // Payroll
            'view_payroll_own',

            // Legacy permissions
            'view_attendance',
            'manage_own_attendance',
            'view_leave',
            'submit_leave',
            'view_payroll',
            'checkin_checkout',
            'record_own_attendance',
            'request_leave',
        ]);

        $this->command->info('Roles and permissions seeded successfully with standardized naming!');
        $this->command->info('Total permissions created: '.Permission::count());
        $this->command->info('Total roles created: '.Role::count());
    }
}
