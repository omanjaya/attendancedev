# Security Hardening Guide

## Overview

This document describes the security measures implemented in the Attendance System to protect against common vulnerabilities.

---

## 1. Password Change Bypass Prevention ✅

### Problem

Users who are required to change their password (first login or admin-forced reset) could bypass this requirement by directly navigating to other URLs.

### Solution Implemented

#### Frontend Guards (`frontend/src/lib/auth/guards.ts`)

- `requireAuth()` - Checks for `force_password_change` or `password_changed_at === null`
- `requireAdmin()` - Same check for admin routes
- `requireEmployee()` - Same check for employee routes

```typescript
// All guards now check:
const mustChangePassword = 
  user.force_password_change === true || 
  user.password_changed_at === null;

if (mustChangePassword && window.location.pathname !== '/auth/change-password') {
  throw redirect({ to: '/auth/change-password' });
}
```

#### Backend Middleware (`backend/app/Http/Middleware/ForcePasswordChangeMiddleware.php`)

- Registered in API middleware group
- Blocks all API requests except:
  - `/api/v1/auth/change-password`
  - `/api/v1/auth/logout`
  - `/api/v1/auth/user` (needed for frontend to know about requirement)

```php
// Returns 403 with specific error code
{
  "success": false,
  "error": "password_change_required",
  "redirect_to": "/auth/change-password"
}
```

---

## 2. IDOR (Insecure Direct Object Reference) Prevention

### Problem

Users might access other users' data by manipulating IDs in API requests.

### Solution Implemented

#### PreventsIdor Trait (`backend/app/Http/Traits/PreventsIdor.php`)

Reusable trait for all controllers that provides:

- `canAccessEmployee(Employee $employee)` - Role-based access check
- `canAccessUser(User $targetUser)` - User-to-user access check
- `authorizeEmployeeAccess(Employee $employee)` - Throws 403 if unauthorized

#### Access Rules

| Role | Access Level |
|------|--------------|
| Super Admin | All employees |
| Admin | Employees in their location |
| Kepala Sekolah | Employees in their location |
| Regular User | Only their own data |

### Usage in Controllers

```php
use App\Http\Traits\PreventsIdor;

class MyController extends Controller
{
    use PreventsIdor;
    
    public function show(Employee $employee)
    {
        $this->authorizeEmployeeAccess($employee);
        // Continue with authorized access
    }
}
```

---

## 3. Existing Security Features

### Password Policy (`config/security.php`)

- Minimum 8 characters
- Requires uppercase, lowercase, numbers, special chars
- Prevents common passwords
- Password history check (5 previous)
- 90-day expiration

### Rate Limiting

- Login: 5 attempts per 15 minutes
- API: 100 requests per minute
- Password Reset: 3 attempts per hour

### Session Security

- 30 minute idle timeout
- Max 3 concurrent sessions
- IP and User-Agent validation
- Session regeneration on login

### Face Recognition Security

- Liveness detection required
- 0.68+ confidence threshold
- Anti-spoofing enabled
- Gesture verification

### Security Headers

- HSTS enabled (1 year)
- CSP enabled
- X-Frame-Options: DENY
- X-Content-Type-Options: nosniff

---

## 4. Security Audit Checklist

### API Endpoints

- [ ] All endpoints require authentication
- [ ] Role-based access control implemented
- [ ] IDOR checks on all resource access
- [ ] Input validation with Form Requests
- [ ] Rate limiting enabled

### Frontend

- [ ] Route guards enforce authentication
- [ ] Password change requirement enforced
- [ ] No sensitive data in localStorage
- [ ] API tokens properly managed

### Database

- [ ] Sensitive data encrypted
- [ ] No plaintext passwords
- [ ] Proper indexing for queries
- [ ] Backups encrypted

---

## 5. Security Testing Commands

```bash
# Test unauthorized access (should return 401)
curl -X GET http://localhost/api/v1/employees

# Test IDOR (should return 403 for unauthorized)
curl -X GET http://localhost/api/v1/employees/999 \
  -H "Authorization: Bearer $TOKEN"

# Test password change bypass (should return 403 with redirect)
curl -X GET http://localhost/api/v1/dashboard \
  -H "Authorization: Bearer $FORCE_CHANGE_TOKEN"
```

---

## 6. Incident Response

### Detected Suspicious Activity

1. Check `storage/logs/laravel.log` for security events
2. Review failed login attempts
3. Check for unusual API access patterns
4. Verify no IDOR attempts in logs

### Emergency Actions

```bash
# Lock a user account
php artisan tinker --execute="User::find(ID)->update(['is_active' => false])"

# Force password change for all users
php artisan tinker --execute="User::query()->update(['force_password_change' => true])"

# Clear all sessions
php artisan session:flush
```

---

## Change Log

| Date | Change | Author |
|------|--------|--------|
| 2025-12-09 | Added password change bypass prevention | System |
| 2025-12-09 | Created PreventsIdor trait | System |
| 2025-12-09 | Added ForcePasswordChangeMiddleware | System |
