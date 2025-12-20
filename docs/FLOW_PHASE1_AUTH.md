# Phase 1: Authentication & User Management Flow

## Table of Contents
- [1. Authentication Flow](#1-authentication-flow)
- [2. User Roles & Permissions](#2-user-roles--permissions)
- [3. User Management (Admin)](#3-user-management-admin)
- [4. Profile Management (Self)](#4-profile-management-self)
- [5. Security Features](#5-security-features)
- [6. Database Schema](#6-database-schema)

---

## 1. Authentication Flow

### 1.1 Login Flow (Email/Password + Turnstile CAPTCHA)

#### Frontend Flow
**Page:** `/opt/attendancedev/frontend/src/pages/login.tsx`

**Components Used:**
- `Button`, `Input`, `Label`, `Checkbox`, `Alert` from `@/components/ui/*`
- `Turnstile` from `@marsidev/react-turnstile`

**Store:** `useAuthStore` from `/opt/attendancedev/frontend/src/stores/auth-store.ts`

**Process:**
1. User enters email & password
2. Cloudflare Turnstile CAPTCHA verification is required
3. Form validation via Zod schema:
   ```typescript
   {
     email: string (valid email format),
     password: string (min 6 characters),
     remember: boolean (optional)
   }
   ```
4. On submit:
   - Validates Turnstile token exists
   - Calls `authStore.login()` with credentials + turnstile_token
   - On success:
     - Checks if user must change password (`force_password_change` or `password_changed_at === null`)
     - Redirects to `/auth/change-password` if required
     - Otherwise redirects to role-based default route
   - On error:
     - Resets Turnstile token
     - Shows error notification

**API Call:** `POST /api/v1/auth/login`

---

#### Backend Flow
**Controller:** `/opt/attendancedev/backend/app/Http/Controllers/Api/AuthController.php`

**Route:** `POST /api/v1/auth/login` (Rate limited: 5 attempts per minute)

**Validation:**
```php
{
  'email' => 'required|email',
  'password' => 'required',
  'device_name' => 'nullable',
  'turnstile_token' => 'required|string'
}
```

**Process:**
1. **Turnstile Verification:**
   - Sends request to Cloudflare API
   - Validates token and IP address
   - Returns 422 if verification fails

2. **User Lookup:**
   - Finds user by email
   - Returns 422 if user not found

3. **Security Checks:**
   - Check if account is active (`is_active`)
   - Check if account is locked (`isLocked()`)
   - Returns 422 with appropriate message if checks fail

4. **Password Verification:**
   - Validates password with `Hash::check()`
   - On failure:
     - Increments failed login attempts
     - Potentially locks account after threshold
     - Logs failed attempt to audit_logs
     - Returns 422

5. **Login Success:**
   - Updates `last_login_at` and `last_login_ip`
   - Resets failed login attempts to 0
   - Creates Sanctum token
   - Logs successful login to audit_logs
   - Returns:
     ```json
     {
       "token": "plaintext_token",
       "user": {User object with employee, roles, permissions},
       "requires_password_change": boolean
     }
     ```

**Database Tables:**
- `users` - User account data
- `audit_logs` - Login/logout tracking
- `personal_access_tokens` (Sanctum) - API tokens

**Models:**
- `/opt/attendancedev/backend/app/Models/User.php`
- `/opt/attendancedev/backend/app/Models/AuditLog.php`

**Security Service:**
- `/opt/attendancedev/backend/app/Services/UserSecurityService.php`

---

### 1.2 Two-Factor Authentication (2FA) Flow

#### Frontend Flow
**Pages:**
- Setup: 2FA setup page (if exists)
- Verify: 2FA verification page (if exists)

**Process:**
1. After successful login, if user has 2FA enabled
2. User is prompted to enter 2FA code
3. Supports three types:
   - TOTP (Google Authenticator)
   - Recovery codes
   - SMS codes

#### Backend Flow
**Controller:** `/opt/attendancedev/backend/app/Http/Controllers/Auth/TwoFactorController.php`

**Routes:**
```php
POST /api/v1/two-factor/setup/initialize  // Rate limited
POST /api/v1/two-factor/setup/verify      // Rate limited
POST /api/v1/two-factor/verify            // Rate limited
DELETE /api/v1/two-factor/disable
POST /api/v1/two-factor/recovery-codes/regenerate
GET /api/v1/two-factor/status
POST /api/v1/two-factor/sms/send          // Rate limited
GET /api/v1/two-factor/qr-code
```

**Initialize 2FA Setup:**
- Generates secret key
- Creates QR code (SVG)
- Returns QR code as base64 data URL

**Enable 2FA:**
1. User scans QR code with authenticator app
2. Enters 6-digit verification code
3. System verifies code
4. Generates 8 recovery codes
5. Enables 2FA for user
6. Logs setup to audit_logs

**Verify 2FA Code:**
1. Validates code (TOTP/Recovery/SMS)
2. Tracks failed attempts per IP+UserID
3. Implements lockdown after repeated failures
4. On success:
   - Marks session as verified
   - Optionally remembers device
   - Clears rate limits
   - Returns redirect URL
5. On failure:
   - Records global failure for attack detection
   - Returns remaining attempts
   - Locks account if threshold exceeded

**Disable 2FA:**
1. Validates user password
2. Checks if 2FA is required for role (super-admin/admin cannot disable)
3. Removes 2FA secret and recovery codes
4. Logs disable action

**Database Fields (users table):**
```php
'two_factor_enabled' => boolean
'two_factor_secret' => text (encrypted)
'two_factor_recovery_codes' => text (encrypted)
'two_factor_confirmed_at' => timestamp
```

**Middleware:** `/opt/attendancedev/backend/app/Http/Middleware/TwoFactorAuthentication.php`
- Checks if user has 2FA enabled
- Verifies session is 2FA-authenticated
- Redirects to verification if needed
- Skips check for API routes and excluded routes

---

### 1.3 Token Management (Sanctum)

**Library:** Laravel Sanctum

**Token Creation:**
- Created on successful login: `$user->createToken($deviceName)`
- Token stored in `personal_access_tokens` table
- Plain text token returned to client once
- Frontend stores token in localStorage

**Token Storage (Frontend):**
```typescript
localStorage.setItem('auth_token', token);
localStorage.setItem('auth_user', JSON.stringify(user));
```

**Token Usage:**
- Included in all API requests via axios interceptor
- Header: `Authorization: Bearer {token}`
- Validated by `auth:sanctum` middleware

**Token Revocation:**
- On logout: `$user->currentAccessToken()->delete()`
- On password change: `$user->tokens()->delete()` (all tokens)

---

### 1.4 Session Handling

**Driver:** Database sessions (configured in `config/session.php`)

**Table:** `sessions`

**Fields:**
```php
'id' => string (primary)
'user_id' => foreignId (nullable)
'ip_address' => string(45)
'user_agent' => text
'payload' => longText
'last_activity' => integer (indexed)
```

**Session Data:**
- 2FA verification status
- Device fingerprint
- Intended URL (for redirect after auth)

**Session Regeneration:**
- On successful login
- On successful 2FA verification
- Prevents session fixation attacks

---

### 1.5 Logout Flow

#### Frontend Flow
**API Call:** `logout()` from `/opt/attendancedev/frontend/src/lib/api/auth.ts`

**Store Action:** `authStore.logout()`

**Process:**
1. Calls backend API to revoke token
2. Clears Sentry user context
3. Removes token and user data from localStorage
4. Resets auth state
5. Redirects to login page

#### Backend Flow
**Route:** `POST /api/v1/auth/logout` (authenticated)

**Process:**
1. Logs logout event to audit_logs
2. Deletes current access token
3. Returns success message

---

## 2. User Roles & Permissions

### 2.1 Available Roles

**Seeder:** `/opt/attendancedev/backend/database/seeders/RolesAndPermissionsSeeder.php`

**Role Hierarchy (highest to lowest):**

| Role | Value | Description |
|------|-------|-------------|
| Super Admin | `super-admin` | Full system access, all permissions |
| Admin | `admin` | Comprehensive management, most permissions |
| Kepala Sekolah | `kepala-sekolah` | School principal, oversight & approval |
| Guru | `guru` | Teacher, basic operational permissions |
| Pegawai | `pegawai` | Staff, minimal operational permissions |

### 2.2 Permission Categories

#### A. Attendance Management
```
view_attendance_own          - View own attendance records
view_attendance_all          - View all attendance records
manage_attendance_own        - Check in/out for self
manage_attendance_all        - Manage any employee attendance
view_attendance_reports      - View attendance analytics
export_attendance_data       - Export attendance data
```

#### B. Employee Management
```
view_employees               - View employee list and details
create_employees             - Add new employees
edit_employees               - Modify employee information
delete_employees             - Remove employees
manage_employees             - Full employee management
export_employees_data        - Export employee data
import_employees_data        - Import employee data from Excel/CSV
```

#### C. Leave Management
```
view_leave_own               - View own leave records
view_leave_all               - View all leave records
create_leave_requests        - Submit leave requests
approve_leave                - Approve leave requests
reject_leave                 - Reject leave requests
manage_leave_balances        - Adjust leave balances
view_leave_analytics         - View leave statistics
```

#### D. Payroll Management
```
view_payroll_own             - View own payroll
view_payroll_all             - View all payroll
create_payroll               - Generate payroll
edit_payroll                 - Modify payroll records
delete_payroll               - Remove payroll records
approve_payroll              - Approve payroll
process_payroll              - Process payroll payments
export_payroll_reports       - Export payroll data
```

#### E. Schedule Management
```
view_schedules               - View schedules
create_schedules             - Create new schedules
edit_schedules               - Modify schedules
delete_schedules             - Remove schedules
assign_schedules             - Assign employees to schedules
lock_schedules               - Lock/unlock schedules
resolve_schedule_conflicts   - Resolve scheduling conflicts
```

#### F. System Administration
```
access_admin_panel           - Access admin features
manage_users                 - User account management
manage_permissions           - Role and permission management
manage_system_settings       - System configuration
manage_locations             - Location management
manage_backups               - Backup management
view_audit_logs              - View system logs
view_security_logs           - View security events
```

#### G. Reports & Analytics
```
view_reports                 - View basic reports
create_reports               - Generate custom reports
view_analytics               - View analytics dashboard
view_advanced_analytics      - Advanced analytics
export_analytics_data        - Export analytics
```

#### H. Security & Privacy
```
manage_user_security         - Manage user security settings
manage_security_settings     - System security configuration
view_security_dashboard      - Access security monitoring
impersonate_users            - Login as other users (super admin only)
```

#### I. Holiday Management
```
view_holidays                - View holiday calendar and list
create_holidays              - Create new holidays
edit_holidays                - Edit existing holidays
delete_holidays              - Delete holidays
manage_holidays              - Full holiday management (import/export)
```

### 2.3 Role-Permission Matrix

#### Super Admin
- **All permissions** (full access)

#### Admin
**Attendance:**
- view_attendance_all
- manage_attendance_all
- view_attendance_reports
- export_attendance_data

**Employees:**
- view_employees
- create_employees
- edit_employees
- delete_employees
- manage_employees
- export_employees_data
- import_employees_data

**Leave:**
- view_leave_all
- approve_leave
- reject_leave
- manage_leave_balances
- view_leave_analytics

**Payroll:**
- view_payroll_all
- create_payroll
- edit_payroll
- approve_payroll
- process_payroll
- export_payroll_reports

**Schedules:**
- view_schedules
- create_schedules
- edit_schedules
- delete_schedules
- assign_schedules
- lock_schedules
- resolve_schedule_conflicts

**Reports:**
- view_reports
- create_reports
- view_advanced_analytics
- export_analytics_data

**System:**
- manage_locations
- view_audit_logs
- view_security_logs
- view_security_dashboard

**Holidays:**
- view_holidays
- create_holidays
- edit_holidays
- manage_holidays

#### Kepala Sekolah (Principal)
**Attendance:**
- view_attendance_own
- view_attendance_all
- manage_attendance_own
- view_attendance_reports

**Employees:**
- view_employees
- import_employees_data

**Leave:**
- view_leave_own
- view_leave_all
- create_leave_requests
- approve_leave
- reject_leave
- view_leave_analytics

**Payroll:**
- view_payroll_own
- view_payroll_all
- approve_payroll

**Schedules:**
- view_schedules

**Reports:**
- view_reports
- view_advanced_analytics

**Holidays:**
- view_holidays

#### Guru (Teacher)
**Attendance:**
- view_attendance_own
- manage_attendance_own
- view_attendance_reports

**Leave:**
- view_leave_own
- create_leave_requests

**Payroll:**
- view_payroll_own

**Schedules:**
- view_schedules

#### Pegawai (Staff)
**Attendance:**
- view_attendance_own
- manage_attendance_own
- view_attendance_reports

**Leave:**
- view_leave_own
- create_leave_requests

**Payroll:**
- view_payroll_own

### 2.4 Middleware

**Permission Check:**
- Route middleware: `permission:permission_name`
- Example: `->middleware('permission:view_employees')`
- Multiple permissions (OR): `permission:view_leave_own|view_leave_all`

**Role Check:**
- User model method: `$user->hasRole('admin')`
- Multiple roles: `$user->hasRole(['admin', 'super-admin'])`

**Frontend Permission Check:**
```typescript
// Auth store method
authStore.hasPermission('employees.view')
authStore.hasRole('super-admin')
authStore.hasRole(['admin', 'super-admin'])
```

### 2.5 Permission Mapping (Backend to Frontend)

**Backend → Frontend mapping in User model:**
```php
'view_employees' => 'employees.view'
'create_employees' => 'employees.create'
'edit_employees' => 'employees.edit'
'delete_employees' => 'employees.delete'
'view_attendance_own' => 'attendance.view'
'manage_attendance_own' => 'attendance.create'
'view_leave_own' => 'leave.view'
'create_leave_requests' => 'leave.create'
'approve_leave' => 'leave.approve'
// ... etc
```

---

## 3. User Management (Admin)

### 3.1 View Users List

#### Frontend
**Pages:**
- Desktop: `/opt/attendancedev/frontend/src/pages/admin/users/desktop.tsx`
- Mobile: `/opt/attendancedev/frontend/src/pages/admin/users/mobile.tsx`
- Index: `/opt/attendancedev/frontend/src/pages/admin/users/index.tsx`

**Route:** `/admin/users`

**Required Permission:** `manage_system_settings`

**Features:**
- DataTable with pagination
- Filter by role
- Filter by status (active/inactive)
- Search by name/email
- Sort by columns
- View user details
- Edit user
- Toggle active/inactive
- Reset password
- Delete user

#### Backend
**Controller:** `/opt/attendancedev/backend/app/Http/Controllers/UserController.php`

**Route:** `GET /api/v1/users/data`

**Middleware:** `auth:sanctum`, `permission:manage_system_settings`

**Service:** `/opt/attendancedev/backend/app/Services/User/UserDataTableService.php`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Admin User",
      "email": "admin@school.edu",
      "is_active": true,
      "last_login_at": "2025-01-15T10:30:00Z",
      "roles": ["admin"],
      "employee": {Employee object or null}
    }
  ]
}
```

---

### 3.2 Create User

#### Frontend
**Page:** `/opt/attendancedev/frontend/src/pages/admin/users/create.tsx`

**Route:** `/admin/users/create`

**Form Fields:**
- Name (required)
- Email (required, unique)
- Password (required, min 8 chars)
- Password Confirmation (required)
- Roles (required, multi-select)
- Active Status (checkbox)

#### Backend
**Route:** `POST /api/v1/users`

**Middleware:** `auth:sanctum`, `permission:manage_system_settings`

**Validation:**
```php
{
  'name' => 'required|string|max:255',
  'email' => 'required|string|email|max:255|unique:users',
  'password' => 'required|confirmed|min:8',
  'roles' => 'required|array',
  'roles.*' => 'exists:roles,id',
  'is_active' => 'boolean'
}
```

**Process:**
1. Validates input
2. Creates user with hashed password
3. Assigns selected roles
4. Returns created user data

**Service:** `/opt/attendancedev/backend/app/Services/User/UserService.php` → `createUser()`

**Repository:** `/opt/attendancedev/backend/app/Repositories/UserRepository.php` → `createWithRole()`

---

### 3.3 Update User

#### Frontend
**Page:** `/opt/attendancedev/frontend/src/pages/admin/users/edit.tsx`

**Route:** `/admin/users/:id/edit`

**Form Fields:**
- Name
- Email (unique except current)
- Password (optional)
- Password Confirmation (if password provided)
- Roles
- Active Status

#### Backend
**Route:** `PUT /api/v1/users/{user}`

**Middleware:** `auth:sanctum`, `permission:manage_system_settings`

**Validation:**
```php
{
  'name' => 'required|string|max:255',
  'email' => 'required|string|email|max:255|unique:users,email,{user_id}',
  'password' => 'nullable|confirmed|min:8',
  'roles' => 'required|array',
  'roles.*' => 'exists:roles,id',
  'is_active' => 'boolean'
}
```

**Process:**
1. Validates input
2. Updates user data
3. Updates password if provided
4. Syncs roles
5. Returns updated user data

---

### 3.4 Toggle User Status (Active/Inactive)

#### Frontend
**Action:** Toggle button in user list

#### Backend
**Route:** `POST /api/v1/users/{user}/toggle-status`

**Middleware:** `auth:sanctum`, `permission:manage_system_settings`

**Process:**
1. Checks user is not trying to deactivate themselves
2. Toggles `is_active` field
3. Returns new status

**Safety Check:**
- Cannot deactivate your own account

---

### 3.5 Reset User Password (Admin)

#### Frontend
**Action:** Reset password button/modal in user list

#### Backend
**Route:** `POST /api/v1/users/{user}/reset-password`

**Middleware:** `auth:sanctum`, `permission:manage_system_settings`

**Validation:**
```php
{
  'new_password' => 'required|confirmed|min:8'
}
```

**Process:**
1. Validates new password
2. Hashes and updates password
3. Returns success message

**Note:** User should be forced to change password on next login

---

### 3.6 Delete User

#### Frontend
**Action:** Delete button with confirmation modal

#### Backend
**Route:** `DELETE /api/v1/users/{user}`

**Middleware:** `auth:sanctum`, `permission:manage_system_settings`

**Safety Checks:**
1. Cannot delete user with associated employee record
2. Cannot delete your own account
3. Soft delete by default

**Process:**
1. Checks safety conditions
2. Soft deletes user
3. Returns success/error message

---

### 3.7 View User Details

#### Frontend
**Page:** `/opt/attendancedev/frontend/src/pages/admin/users/show.tsx`

**Route:** `/admin/users/:id`

**Displays:**
- User information
- Assigned roles
- Permissions (via roles)
- Associated employee (if exists)
- Last login info
- Account status
- Security settings

---

### 3.8 User Statistics

#### Backend
**Route:** `GET /api/v1/users/statistics`

**Service:** `/opt/attendancedev/backend/app/Services/User/UserStatisticsService.php`

**Returns:**
```json
{
  "success": true,
  "statistics": {
    "total_users": 50,
    "active_users": 45,
    "inactive_users": 5,
    "by_role": {
      "super-admin": 2,
      "admin": 5,
      "kepala-sekolah": 3,
      "guru": 25,
      "pegawai": 15
    },
    "recent_logins": 30,
    "locked_accounts": 1
  }
}
```

---

## 4. Profile Management (Self)

### 4.1 View Profile

#### Frontend
**Pages:**
- Desktop: `/opt/attendancedev/frontend/src/pages/employee/profile/desktop.tsx`
- Mobile: `/opt/attendancedev/frontend/src/pages/employee/profile/mobile.tsx`
- Index: `/opt/attendancedev/frontend/src/pages/employee/profile/index.tsx`

**Route:** `/employee/profile`

**Displays:**
- User information
- Employee information (if linked)
- Avatar
- Account security status
- 2FA status
- Last login info

#### Backend
**Route:** `GET /api/v1/auth/me`

**Middleware:** `auth:sanctum`

**Returns:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@school.edu",
    "phone": "+62123456789",
    "avatar": "/storage/avatars/xyz.jpg",
    "is_active": true,
    "role": "guru",
    "permissions": ["attendance.view", "attendance.create", ...],
    "employee": {Employee object},
    "last_login_at": "2025-01-15T10:30:00Z",
    "two_factor_enabled": false
  }
}
```

---

### 4.2 Edit Profile

#### Frontend
**Page:** `/opt/attendancedev/frontend/src/pages/employee/profile/edit.tsx`

**Route:** `/employee/profile/edit`

**Editable Fields:**
- Name
- Email
- Phone
- Avatar (upload)

#### Backend
**Controller:** `/opt/attendancedev/backend/app/Http/Controllers/ProfileController.php`

**Routes:**
- `PUT /api/v1/profile` - Update basic info
- `POST /api/v1/auth/avatar` - Upload avatar
- `DELETE /api/v1/auth/avatar` - Delete avatar

**Update Profile Validation:**
```php
{
  'name' => 'required|string|max:255',
  'email' => 'required|email|max:255|unique:users,email,{user_id}',
  'phone' => 'nullable|string|max:20'
}
```

**Upload Avatar:**
- Validation: `'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'`
- Storage: `storage/app/public/avatars/`
- Deletes old avatar if exists
- Returns new avatar URL

---

### 4.3 Change Password (Self)

#### Frontend
**Component:** Password change form in profile page

**Form Fields:**
- Current Password (required)
- New Password (required, min 8 chars)
- Confirm New Password (required)

#### Backend
**Route:** `POST /api/v1/auth/change-password`

**Middleware:** `auth:sanctum`

**Validation:**
```php
{
  'current_password' => 'required',
  'password' => 'required|min:8',
  'password_confirmation' => 'required|same:password'
}
```

**Process:**
1. Verifies current password
2. Hashes new password
3. Updates password
4. Sets `password_changed_at` to now
5. Clears `force_password_change` flag
6. Logs password change to audit_logs
7. **Revokes ALL tokens** (forces re-login on all devices)
8. Returns success message

**Security:**
- User must re-login after password change
- All active sessions/tokens invalidated

---

### 4.4 Enable/Disable 2FA

#### Enable 2FA

**Frontend Flow:**
1. User clicks "Enable 2FA" button
2. Calls `POST /api/v1/two-factor/setup/initialize`
3. Receives QR code and secret key
4. User scans QR code with authenticator app
5. User enters 6-digit verification code
6. Calls `POST /api/v1/two-factor/setup/verify` with code
7. Receives 8 recovery codes
8. User saves recovery codes securely
9. 2FA is now enabled

**Backend Routes:**
- `POST /api/v1/two-factor/setup/initialize` - Generate QR code
- `POST /api/v1/two-factor/setup/verify` - Verify and enable

**Initialize Response:**
```json
{
  "success": true,
  "data": {
    "secret_key": "BASE32_SECRET",
    "qr_code_url": "data:image/svg+xml;base64,...",
    "manual_entry_key": "BASE32_SECRET",
    "company_name": "Attendance System",
    "user_email": "user@school.edu"
  }
}
```

**Verify & Enable:**
1. Validates 6-digit code
2. Enables 2FA: `two_factor_enabled = true`
3. Stores encrypted secret
4. Generates 8 recovery codes (encrypted)
5. Logs 2FA setup to audit_logs
6. Returns recovery codes

#### Disable 2FA

**Frontend Flow:**
1. User clicks "Disable 2FA" button
2. Enters password for confirmation
3. Calls `DELETE /api/v1/two-factor/disable`

**Backend Route:** `DELETE /api/v1/two-factor/disable`

**Process:**
1. Validates password
2. Checks if 2FA is required for role (cannot disable if super-admin/admin)
3. Clears 2FA secret and recovery codes
4. Sets `two_factor_enabled = false`
5. Clears session verification
6. Logs disable action to audit_logs

---

### 4.5 Regenerate Recovery Codes

**Frontend:** Recovery codes section in profile/security settings

**Backend Route:** `POST /api/v1/two-factor/recovery-codes/regenerate`

**Validation:**
```php
{
  'password' => 'required|string'
}
```

**Process:**
1. Validates password
2. Generates 8 new recovery codes
3. Encrypts and stores codes
4. Logs regeneration to audit_logs
5. Returns new codes

**Recovery Codes:**
- 8 codes
- 8 characters each
- Uppercase alphanumeric
- Each code can be used once
- Encrypted in database

---

### 4.6 Delete Account (Self)

**Frontend:** Delete account section with password confirmation

**Backend Route:** `POST /api/v1/auth/delete-account`

**Validation:**
```php
{
  'password' => 'required'
}
```

**Process:**
1. Validates password
2. Deletes avatar file if exists
3. Deletes all tokens
4. Soft deletes user account
5. Returns success message

**Safety:**
- Requires password confirmation
- Soft delete (can be restored)

---

## 5. Security Features

### 5.1 Failed Login Attempts Tracking

**Implementation:** User model methods via `UserSecurityService`

**Process:**
1. On failed login:
   - Calls `$user->incrementFailedLogins($ipAddress)`
   - Increments `failed_login_attempts` counter
   - Logs failed attempt to audit_logs with IP and user agent

2. **Account Locking:**
   - After X failed attempts (configurable, default 5)
   - Sets `account_locked = true`
   - Sets `locked_until` timestamp (e.g., 30 minutes)
   - Logs lock event to audit_logs

3. On successful login:
   - Calls `$user->resetFailedLogins()`
   - Sets `failed_login_attempts = 0`

**User Model Methods:**
```php
incrementFailedLogins(?string $ipAddress): void
resetFailedLogins(): void
isLocked(): bool
lockAccount(?Carbon $until, string $reason): void
unlockAccount(string $reason): void
```

**Database Fields:**
```php
'failed_login_attempts' => integer (default 0)
'account_locked' => boolean (default false)
'locked_until' => timestamp (nullable)
```

---

### 5.2 Account Locking

**Automatic Lock:**
- Triggered by failed login attempts threshold
- Temporary lock (e.g., 30 minutes)

**Manual Lock (Admin):**
- Admin can manually lock any account
- Can set custom lock duration or indefinite
- Requires reason (logged to audit_logs)

**Lock Check:**
```php
// In AuthController login method
if ($user->isLocked()) {
    $lockTime = $user->locked_until ? $user->locked_until->diffForHumans() : 'indefinitely';
    throw ValidationException::withMessages([
        'email' => ["Account locked. Try again {$lockTime}."],
    ]);
}
```

**Unlock:**
- Automatic: When `locked_until` timestamp passes
- Manual: Admin calls `unlockAccount()` method

---

### 5.3 Password Policies

**Validation Rules:**
```php
Rules\Password::defaults()
```

**Default Policy:**
- Minimum 8 characters
- Can be configured to require:
  - Mixed case
  - Numbers
  - Symbols
  - Not compromised (using Have I Been Pwned API)

**Force Password Change:**
- Admin can force user to change password on next login
- Set `force_password_change = true`
- User redirected to change password page after login
- New users with `password_changed_at = null` must change password

**Password Change Tracking:**
- `password_changed_at` timestamp updated on change
- Can implement password expiry policy (e.g., 90 days)

---

### 5.4 Session Timeout

**Configuration:** `config/session.php`

**Lifetime:** Configurable (e.g., 120 minutes)

**Idle Timeout:**
- Session expires after inactivity period
- User must re-login
- Prevents unauthorized access to unattended devices

**2FA Session Verification Timeout:**
- 2FA verification expires after period
- User must verify 2FA again
- Checked in `TwoFactorAuthentication` middleware:
  ```php
  if ($this->isVerificationExpired($request)) {
      $this->twoFactorService->clearSessionVerification();
      return $this->redirectToVerification($request);
  }
  ```

---

### 5.5 Audit Logging

**Model:** `/opt/attendancedev/backend/app/Models/AuditLog.php`

**Table:** `audit_logs`

**Schema:**
```sql
id                uuid (primary)
user_id           bigint (nullable, indexed)
event_type        string(50) (indexed)
auditable_type    string (indexed)
auditable_id      string (indexed)
old_values        jsonb
new_values        jsonb
url               text
ip_address        inet
user_agent        text
tags              jsonb
created_at        timestamp (indexed)
```

**Logged Events:**

**Authentication:**
- `login` - Successful login
- `login_failed` - Failed login attempt
- `logout` - User logout
- `password_change` - Password changed
- `password_reset` - Password reset via email
- `2fa_setup` - 2FA enabled/disabled
- `2fa_verified` - 2FA verification success
- `2fa_failed` - 2FA verification failed

**User Management:**
- `user_created` - New user account created
- `user_updated` - User account modified
- `user_deleted` - User account deleted
- `user_locked` - Account locked
- `user_unlocked` - Account unlocked
- `role_changed` - User role modified

**Profile:**
- `profile_updated` - Profile information changed
- `avatar_uploaded` - Avatar image uploaded
- `avatar_deleted` - Avatar image deleted

**Static Methods:**
```php
AuditLog::createLog(
    string $eventType,
    Model $auditable,
    array $oldValues,
    array $newValues,
    ?User $user,
    array $tags
)

AuditLog::createAuthLog(
    string $eventType,
    User $user,
    array $context
)

AuditLog::createSecurityLog(
    string $eventType,
    array $context
)
```

**Example:**
```php
// Log successful login
AuditLog::createAuthLog('login', $user, [
    'device_name' => 'web',
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);

// Log failed login
AuditLog::createAuthLog('login_failed', $user, [
    'reason' => 'Invalid password',
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);
```

---

### 5.6 Security Monitoring

**Features:**
- Real-time security dashboard (for admins)
- Failed login monitoring
- 2FA failure tracking
- Suspicious activity detection
- Account lockout alerts
- Rate limiting metrics

**Middleware:** `/opt/attendancedev/backend/app/Http/Middleware/AuditAuthentication.php`

**Services:**
- `/opt/attendancedev/backend/app/Services/SecurityService.php`
- `/opt/attendancedev/backend/app/Services/SecurityLogger.php`
- `/opt/attendancedev/backend/app/Services/Auth/TwoFactorSecurityService.php`

**Rate Limiting:**
- Login attempts: 5 per minute
- Password reset: 5 per minute
- 2FA verification: Custom rates per type
- 2FA SMS: Limited per IP

---

### 5.7 Device Management

**Table:** `user_devices`

**Features:**
- Track user devices (fingerprint, name, OS, browser)
- Trust devices for 2FA bypass
- Revoke device trust
- View all active devices
- Force logout from specific device

**Routes:**
```php
GET /api/v1/devices                    // List devices
GET /api/v1/devices/current            // Current device
PATCH /api/v1/devices/{device}/name    // Update device name
POST /api/v1/devices/{device}/trust    // Trust device
DELETE /api/v1/devices/{device}/trust  // Revoke trust
DELETE /api/v1/devices/{device}        // Remove device
DELETE /api/v1/devices/all             // Remove all devices
```

**User Model Methods:**
```php
isNewDevice(string $deviceFingerprint): bool
rememberDevice(string $deviceFingerprint): void
forgetAllDevices(): void
```

---

## 6. Database Schema

### 6.1 Users Table

**Migration:** `/opt/attendancedev/backend/database/migrations/0001_01_01_000000_create_users_table.php`

**Base Schema:**
```sql
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Additional Fields (from migrations):**

**User Management Fields:**
```sql
is_active BOOLEAN DEFAULT TRUE,
last_login_at TIMESTAMP,
last_login_ip VARCHAR(45),
```

**Security Fields:**
```sql
password_changed_at TIMESTAMP,
failed_login_attempts INTEGER DEFAULT 0,
locked_until TIMESTAMP,
phone VARCHAR(20),
security_preferences JSON,
force_password_change BOOLEAN DEFAULT FALSE,
account_locked BOOLEAN DEFAULT FALSE,
```

**2FA Fields:**
```sql
two_factor_enabled BOOLEAN DEFAULT FALSE,
two_factor_secret TEXT,
two_factor_recovery_codes TEXT,
two_factor_confirmed_at TIMESTAMP,
```

**Indexes:**
```sql
INDEX idx_users_is_active (is_active)
INDEX idx_users_last_login (last_login_at)
INDEX idx_users_email (email)
```

---

### 6.2 Audit Logs Table

**Migration:** `/opt/attendancedev/backend/database/migrations/2024_01_01_000000_create_audit_logs_table.php`

**Schema:**
```sql
CREATE TABLE audit_logs (
    id UUID PRIMARY KEY,
    user_id BIGINT,
    event_type VARCHAR(50) NOT NULL,
    auditable_type VARCHAR(255),
    auditable_id VARCHAR(255),
    old_values JSONB,
    new_values JSONB,
    url TEXT,
    ip_address INET,
    user_agent TEXT,
    tags JSONB,
    created_at TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Indexes
CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id, created_at);
CREATE INDEX idx_audit_logs_event_type ON audit_logs(event_type, created_at);
CREATE INDEX idx_audit_logs_auditable ON audit_logs(auditable_type, auditable_id);
```

---

### 6.3 Sessions Table

**Schema:**
```sql
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload LONGTEXT NOT NULL,
    last_activity INTEGER NOT NULL,

    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_sessions_user_id (user_id),
    INDEX idx_sessions_last_activity (last_activity)
);
```

---

### 6.4 Password Reset Tokens Table

**Schema:**
```sql
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP
);
```

---

### 6.5 Personal Access Tokens (Sanctum)

**Schema:**
```sql
CREATE TABLE personal_access_tokens (
    id BIGSERIAL PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    abilities TEXT,
    last_used_at TIMESTAMP,
    expires_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_pat_tokenable (tokenable_type, tokenable_id)
);
```

---

### 6.6 Roles & Permissions (Spatie)

**Roles Table:**
```sql
CREATE TABLE roles (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Permissions Table:**
```sql
CREATE TABLE permissions (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Role Has Permissions:**
```sql
CREATE TABLE role_has_permissions (
    permission_id BIGINT NOT NULL,
    role_id BIGINT NOT NULL,

    PRIMARY KEY (permission_id, role_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);
```

**Model Has Roles:**
```sql
CREATE TABLE model_has_roles (
    role_id BIGINT NOT NULL,
    model_type VARCHAR(255) NOT NULL,
    model_id BIGINT NOT NULL,

    PRIMARY KEY (role_id, model_id, model_type),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    INDEX idx_model_has_roles (model_id, model_type)
);
```

---

## Summary

This documentation covers the complete Phase 1 Authentication & User Management flow for the attendance system, including:

1. **Authentication Flow**: Login, 2FA, token management, session handling, logout
2. **User Roles & Permissions**: 5 roles with comprehensive permission matrix
3. **User Management**: CRUD operations, status management, password reset
4. **Profile Management**: View/edit profile, change password, 2FA setup, account deletion
5. **Security Features**: Failed login tracking, account locking, password policies, session timeout, audit logging, device management
6. **Database Schema**: Complete table structures with indexes and relationships

All flows include both frontend and backend implementation details with file paths, API endpoints, validations, and security measures.
