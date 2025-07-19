# Permission Standardization Guide

## Naming Convention

### Standard Format: `{action}_{resource}_{scope?}`

- **action**: `view`, `create`, `edit`, `delete`, `manage`, `approve`, `reject`, `export`, `import`
- **resource**: `attendance`, `employees`, `leave`, `payroll`, `schedules`, `reports`, `users`, `system`
- **scope**: `own`, `all`, `department` (optional)

### Examples:
- `view_attendance_own` - View own attendance
- `view_attendance_all` - View all attendance  
- `manage_employees` - Full employee management
- `approve_leave` - Approve leave requests
- `export_payroll_reports` - Export payroll reports

## Permission Categories

### 1. **Attendance Management**
- `view_attendance_own` - View own attendance records
- `view_attendance_all` - View all attendance records
- `manage_attendance_own` - Check in/out for self
- `manage_attendance_all` - Manage any employee attendance
- `view_attendance_reports` - View attendance analytics
- `export_attendance_data` - Export attendance data

### 2. **Employee Management**
- `view_employees` - View employee list and details
- `create_employees` - Add new employees
- `edit_employees` - Modify employee information
- `delete_employees` - Remove employees
- `manage_employees` - Full employee management
- `export_employees_data` - Export employee data

### 3. **Leave Management**
- `view_leave_own` - View own leave records
- `view_leave_all` - View all leave records
- `create_leave_requests` - Submit leave requests
- `approve_leave` - Approve leave requests
- `reject_leave` - Reject leave requests
- `manage_leave_balances` - Adjust leave balances
- `view_leave_analytics` - View leave statistics

### 4. **Payroll Management**
- `view_payroll_own` - View own payroll
- `view_payroll_all` - View all payroll
- `create_payroll` - Generate payroll
- `edit_payroll` - Modify payroll records
- `delete_payroll` - Remove payroll records
- `approve_payroll` - Approve payroll
- `process_payroll` - Process payroll payments
- `export_payroll_reports` - Export payroll data

### 5. **Schedule Management**
- `view_schedules` - View schedules
- `create_schedules` - Create new schedules
- `edit_schedules` - Modify schedules
- `delete_schedules` - Remove schedules
- `assign_schedules` - Assign employees to schedules
- `lock_schedules` - Lock/unlock schedules
- `resolve_schedule_conflicts` - Resolve scheduling conflicts

### 6. **System Administration**
- `access_admin_panel` - Access admin features
- `manage_users` - User account management
- `manage_permissions` - Role and permission management
- `manage_system_settings` - System configuration
- `manage_locations` - Location management
- `manage_backups` - Backup management
- `view_audit_logs` - View system logs
- `view_security_logs` - View security events

### 7. **Reports & Analytics**
- `view_reports` - View basic reports
- `create_reports` - Generate custom reports
- `view_advanced_analytics` - Advanced analytics
- `export_analytics_data` - Export analytics

### 8. **Security & Privacy**
- `manage_user_security` - Manage user security settings
- `manage_security_settings` - System security configuration
- `view_security_dashboard` - Access security monitoring
- `impersonate_users` - Login as other users (super admin only)

## Role Assignments

### **Super Admin**
- ALL permissions

### **Admin**
- All management permissions except `impersonate_users`
- All view permissions
- System administration (limited)

### **Kepala Sekolah (Principal)**
- `view_attendance_all`
- `view_employees`
- `approve_leave`
- `view_payroll_all`
- `view_reports`
- `view_advanced_analytics`

### **Guru (Teacher)**
- `view_attendance_own`
- `manage_attendance_own`
- `view_leave_own`
- `create_leave_requests`
- `view_payroll_own`
- `view_schedules`

### **Pegawai (Staff)**
- `view_attendance_own`
- `manage_attendance_own`
- `view_leave_own`
- `create_leave_requests`
- `view_payroll_own`

## Implementation Rules

1. **Always use snake_case** - no spaces, camelCase, or kebab-case
2. **Consistent verb usage** - stick to standard action verbs
3. **Scope specification** - use `_own` vs `_all` vs `_department`
4. **Logical grouping** - related permissions should follow same pattern
5. **No custom permissions** - use only standardized permissions

## Migration Strategy

1. **Phase 1**: Update RolesAndPermissionsSeeder with all standardized permissions
2. **Phase 2**: Update all route middleware to use new permission names
3. **Phase 3**: Update all Blade templates @can directives
4. **Phase 4**: Update controller authorization calls
5. **Phase 5**: Create Policy classes for complex authorization
6. **Phase 6**: Test all permission-protected features
7. **Phase 7**: Remove deprecated permissions and clean up

## Validation Rules

- All permissions must be defined in RolesAndPermissionsSeeder
- All permissions must follow naming convention
- All permissions must be assigned to at least one role
- All permission checks must use standardized names
- No hardcoded role checks (use permissions instead)