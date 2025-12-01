# 🔐 Security Implementation Guide

## Triple Protection System

Sistem keamanan aplikasi ini menggunakan **3 layer protection** untuk memastikan setiap page hanya dapat diakses oleh role yang sesuai:

1. **Sidebar Filter** - Hide menu yang tidak sesuai role
2. **Route Guards** - Protect URL access di router level
3. **Page Guards** - Additional protection di component level

---

## 📊 Permission Matrix

### Role Hierarchy
```
super-admin > admin > kepala-sekolah > guru > pegawai
```

### Access Control

| Feature | super-admin | admin | kepala-sekolah | guru | pegawai |
|---------|-------------|-------|----------------|------|---------|
| **Dashboard** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Attendance** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Employees** | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Schedules** | ✅ | ✅ | ✅ | ✅ (view) | ✅ (view) |
| **Leave** | ✅ | ✅ | ✅ (approve) | ✅ (request) | ✅ (request) |
| **Payroll** | ✅ | ✅ | ✅ (view) | ✅ (own) | ✅ (own) |
| **Reports** | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Settings** | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Admin Pages** | ✅ | ✅ | ❌ | ❌ | ❌ |

---

## 🛡️ Layer 1: Sidebar Filter

**File:** `/frontend/src/config/navigation.ts`

Navigation menu otomatis ter-filter berdasarkan role dan permission user.

```typescript
// Super admin sees all menus
if (userRole === 'super-admin') return true;

// Check role restriction
if (item.roles && !item.roles.includes(userRole)) return false;

// Check permission
if (item.permission && !userPermissions.includes(item.permission)) return false;
```

**Contoh Konfigurasi Menu:**
```typescript
{
  title: 'Karyawan',
  href: '/employees',
  icon: Users,
  permission: 'employees.view',
  roles: ['super-admin', 'admin', 'kepala-sekolah'],
}
```

---

## 🔒 Layer 2: Route Guards

**File:** `/frontend/src/lib/guards/permission-guard.ts`

Setiap route dilindungi dengan `requirePermission()` guard yang mengecek permission dan role sebelum user bisa akses.

### Cara Pakai:

```typescript
import { requirePermission } from '@/lib/guards/permission-guard';

const employeesRoute = createRoute({
  path: '/employees',
  beforeLoad: requirePermission('employees.view', ['super-admin', 'admin', 'kepala-sekolah']),
  component: EmployeesPage,
});
```

### Route Protection Map:

#### **Employees Routes**
```typescript
/employees                  → employees.view       → super-admin, admin, kepala-sekolah
/employees/create           → employees.create     → super-admin, admin
/employees/:id              → employees.view       → super-admin, admin, kepala-sekolah
/employees/:id/edit         → employees.edit       → super-admin, admin
/employees/credentials      → employees.view       → super-admin, admin, kepala-sekolah
```

#### **Schedule Routes**
```typescript
/schedules                  → schedules.view       → ALL ROLES
/schedules/create           → schedules.create     → super-admin, admin, kepala-sekolah
/schedules/:id/edit         → schedules.edit       → super-admin, admin, kepala-sekolah
```

#### **Leave Routes**
```typescript
/leave                      → leave.view           → ALL ROLES
/leave/approvals            → leave.approve        → super-admin, admin, kepala-sekolah
```

#### **Reports Routes**
```typescript
/reports                    → reports.view         → super-admin, admin, kepala-sekolah
/reports/builder            → reports.view         → super-admin, admin, kepala-sekolah
```

#### **Settings & Admin Routes**
```typescript
/settings                   → NONE                 → super-admin, admin
/admin/users/*              → NONE                 → super-admin, admin
/admin/locations/*          → NONE                 → super-admin, admin
/admin/holidays/*           → NONE                 → super-admin, admin
```

### Behavior on Access Denied:
- User akan di-redirect ke `/dashboard`
- URL query parameter `?error=unauthorized` akan ditambahkan
- Console warning akan muncul (development mode)

---

## 🛡️ Layer 3: Page Guards (OPTIONAL)

**File:** `/frontend/src/components/guards/ProtectedPage.tsx`

Layer tambahan untuk double-check di component level.

### Cara Pakai:

```typescript
import { ProtectedPage } from '@/components/guards';

export default function EmployeesPage() {
  return (
    <ProtectedPage
      permission="employees.view"
      roles={['super-admin', 'admin', 'kepala-sekolah']}
    >
      {/* Page content here */}
      <div>Employee List</div>
    </ProtectedPage>
  );
}
```

### Props:
- `permission` (optional): Required permission
- `roles` (optional): Allowed roles array
- `fallback` (optional): Custom unauthorized UI

### Default Behavior:
- Shows "Access Denied" screen jika unauthorized
- Auto-redirect ke dashboard via useEffect
- Shows notification error

---

## 🔧 Helper Functions

**File:** `/frontend/src/lib/guards/permission-guard.ts`

### hasPermission()
Check apakah user punya specific permission:
```typescript
import { hasPermission } from '@/lib/guards/permission-guard';

if (hasPermission('employees.create')) {
  // Show create button
}
```

### hasRole()
Check apakah user punya specific role:
```typescript
import { hasRole } from '@/lib/guards/permission-guard';

if (hasRole(['admin', 'super-admin'])) {
  // Show admin features
}
```

### hasAnyPermission()
Check apakah user punya salah satu dari beberapa permission:
```typescript
import { hasAnyPermission } from '@/lib/guards/permission-guard';

if (hasAnyPermission(['employees.edit', 'employees.delete'])) {
  // Show action menu
}
```

### hasAllPermissions()
Check apakah user punya semua permission yang dibutuhkan:
```typescript
import { hasAllPermissions } from '@/lib/guards/permission-guard';

if (hasAllPermissions(['reports.view', 'reports.export'])) {
  // Enable export feature
}
```

---

## 🧪 Testing Security

### Test Manual per Role:

1. **Login sebagai Super Admin**
   - Harus bisa akses SEMUA menu & page
   - Role switcher harus muncul (dev mode)

2. **Login sebagai Admin**
   - Harus bisa akses: Dashboard, Attendance, Employees, Schedules, Leave, Payroll, Reports, Settings, Admin pages
   - Tidak bisa akses: (none, admin sees everything except super-admin features)
   - Role switcher TIDAK muncul

3. **Login sebagai Kepala Sekolah**
   - Harus bisa akses: Dashboard, Attendance, Employees (view only), Schedules, Leave (with approve), Payroll (view), Reports
   - Tidak bisa akses: Settings, Admin pages, Employee create/edit
   - Tidak bisa akses URL langsung ke `/settings` atau `/admin/*`

4. **Login sebagai Guru**
   - Harus bisa akses: Dashboard, Attendance, Schedules (view), Leave (request only), Payroll (own)
   - Tidak bisa akses: Employees, Reports, Settings, Admin pages
   - Redirect ke dashboard jika coba akses `/employees`

5. **Login sebagai Pegawai**
   - Harus bisa akses: Dashboard, Attendance, Schedules (view), Leave (request only), Payroll (own)
   - Tidak bisa akses: Employees, Reports, Settings, Admin pages
   - Same as Guru restrictions

### Test Cases Kritis:

```bash
# Test unauthorized URL access
1. Login as "pegawai"
2. Manually navigate to: http://localhost:5173/employees
3. Expected: Redirect to /dashboard with error notification

# Test permission on page actions
1. Login as "kepala-sekolah"
2. Go to /employees
3. Expected: Can view list, but NO "Create Employee" button

# Test super admin bypass
1. Login as "super-admin"
2. Access ANY page
3. Expected: Full access, no restrictions
```

---

## ⚠️ Security Best Practices

### DO ✅
- Always use route guards untuk protected routes
- Check permission sebelum show action buttons (create, edit, delete)
- Use helper functions (hasPermission, hasRole) untuk conditional rendering
- Log unauthorized access attempts (already implemented in guards)

### DON'T ❌
- Jangan hanya rely on sidebar filter - user bisa akses URL langsung!
- Jangan hardcode role checks dengan `user.role === 'admin'` - use helper functions
- Jangan lupa protect API endpoints di backend juga!
- Jangan expose sensitive data di frontend untuk roles yang tidak berhak

---

## 🔄 Future Improvements

- [ ] Add rate limiting untuk unauthorized access attempts
- [ ] Log security events ke database
- [ ] Add 2FA untuk admin & super-admin
- [ ] Implement IP whitelisting untuk admin pages
- [ ] Add session timeout untuk inactive users
- [ ] Implement audit log untuk sensitive actions

---

## 📝 Changelog

### v1.0.0 - Triple Protection Implementation
- ✅ Added permission guard utilities
- ✅ Created ProtectedPage wrapper component
- ✅ Updated all routes with permission guards
- ✅ Documented security system

---

**Maintained by:** Development Team
**Last Updated:** 2025-01-30
