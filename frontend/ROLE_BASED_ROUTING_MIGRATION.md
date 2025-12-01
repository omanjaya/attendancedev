# Role-Based Routing Migration Plan

Migration dari mixed pages ke separate role-based routing (admin/ vs employee/).

## Overview

**Tujuan:**
- ✅ Separate admin pages dari employee pages
- ✅ Better security (code splitting, admin logic tidak terexpose)
- ✅ Clearer structure dan easier maintenance
- ✅ Different layouts untuk different roles

**Approach:** Separate Routes by Role
- `/admin/*` → Admin-only pages
- `/employee/*` → Employee-only pages (non-admin)
- `/shared/*` → Shared components (face verification, etc)

---

## Current Structure vs New Structure

### Before (Mixed):
```
pages/
├── admin/          # Partially organized
│   ├── holidays/
│   ├── locations/
│   └── users/
├── attendance/     # MIXED (admin view all vs employee view own)
├── employees/      # ADMIN (manage all employees)
├── schedules/      # ADMIN (manage all schedules)
├── payroll/        # MIXED (admin process vs employee view own)
├── leave/          # MIXED (admin approve vs employee request)
├── reports/        # ADMIN (company-wide reports)
├── profile/        # EMPLOYEE (personal profile)
└── dashboard.tsx   # MIXED (different content for admin vs employee)
```

### After (Separated):
```
pages/
├── admin/                    # Admin-only area
│   ├── dashboard.tsx         # Admin dashboard (NEW)
│   ├── employees/            # MOVE from root
│   ├── holidays/             # KEEP
│   ├── locations/            # KEEP
│   ├── users/                # KEEP
│   ├── schedules/            # MOVE from root
│   ├── payroll/              # MOVE from root
│   ├── leave/                # MOVE from root
│   ├── attendance/           # NEW (admin view)
│   ├── reports/              # MOVE from root
│   ├── face-recognition/     # MOVE from root
│   ├── settings/             # MOVE from root
│   └── security/             # MOVE from root
│
├── employee/                 # Employee-only area
│   ├── dashboard.tsx         # MOVE from root (employee version)
│   ├── attendance/           # NEW (from shared attendance)
│   ├── leave/                # NEW (employee view)
│   ├── schedule/             # NEW (view my schedule only)
│   ├── payroll/              # NEW (view my payslips only)
│   └── profile/              # MOVE from root
│
├── shared/                   # Shared by both roles
│   ├── verify-face.tsx       # MOVE from attendance/
│   └── verify-location.tsx   # MOVE from attendance/
│
├── auth/                     # Public auth pages
├── login.tsx
├── not-found.tsx
└── unauthorized.tsx          # NEW
```

---

## File Migration Map

### ✅ Already in Admin (Keep as is):
```
pages/admin/holidays/*     → NO CHANGE
pages/admin/locations/*    → NO CHANGE
pages/admin/users/*        → NO CHANGE
```

### 📦 Move to Admin:
```bash
# Employees Management
pages/employees/*          → pages/admin/employees/*

# Schedules Management
pages/schedules/*          → pages/admin/schedules/*

# Payroll Processing
pages/payroll/*            → pages/admin/payroll/*

# Leave Approvals
pages/leave/*              → pages/admin/leave/*

# Reports
pages/reports/*            → pages/admin/reports/*

# Face Recognition Settings
pages/face-recognition/*   → pages/admin/face-recognition/*

# System Settings
pages/settings/*           → pages/admin/settings/*

# Security Settings
pages/security/*           → pages/admin/security/*
```

### 👤 Move to Employee:
```bash
# Profile
pages/profile/*            → pages/employee/profile/*
```

### 🔄 Create New Employee Pages:

**Employee Attendance** (view own only):
```typescript
// pages/employee/attendance/index.tsx
// Shows only current user's attendance
// No approve/manage features
```

**Employee Leave** (request only):
```typescript
// pages/employee/leave/index.tsx
// Request leave, view own requests
// No approval features
```

**Employee Schedule** (view own only):
```typescript
// pages/employee/schedule/index.tsx
// View assigned schedules (read-only)
```

**Employee Payroll** (view own only):
```typescript
// pages/employee/payroll/index.tsx
// View own payslips (read-only)
```

### 🌐 Move to Shared:
```bash
pages/attendance/verify-face.tsx     → pages/shared/verify-face.tsx
pages/attendance/verify-location.tsx → pages/shared/verify-location.tsx
```

### 🆕 Create New Pages:

**Admin Dashboard:**
```typescript
// pages/admin/dashboard.tsx
// Company-wide stats, pending approvals, quick actions
```

**Employee Dashboard:**
```typescript
// pages/employee/dashboard.tsx
// Personal stats, my schedule, my recent attendance
```

**Admin Attendance:**
```typescript
// pages/admin/attendance/index.tsx
// View/manage all employees' attendance
// Approve, override, export
```

**Unauthorized Page:**
```typescript
// pages/unauthorized.tsx
// Shown when user tries to access forbidden route
```

---

## Migration Script

```bash
#!/bin/bash

# Role-Based Routing Migration Script
# Run from: frontend/src/pages/

echo "🚀 Starting Role-Based Routing Migration..."

# Create backup
echo "📦 Creating backup..."
cd ..
tar -czf pages_backup_$(date +%Y%m%d_%H%M%S).tar.gz pages/
cd pages

# Move to Admin
echo "📂 Moving pages to admin/..."

mv employees admin/
mv schedules admin/
mv payroll admin/
mv leave admin/
mv reports admin/
mv face-recognition admin/
mv settings admin/
mv security admin/

# Move to Employee
echo "👤 Moving pages to employee/..."

mv profile employee/

# Move to Shared
echo "🌐 Moving pages to shared/..."

mv attendance/verify-face.tsx shared/
mv attendance/verify-location.tsx shared/

# Clean up old attendance folder (if empty or only has old files)
# rm -rf attendance  # Only if safe

echo "✅ Migration complete!"
echo ""
echo "⚠️  TODO: Update imports in all moved files"
echo "⚠️  TODO: Update router configuration"
echo "⚠️  TODO: Create new employee pages"
echo "⚠️  TODO: Create new dashboards"
```

---

## Router Configuration

### Before (Mixed):
```typescript
// All routes in one place, no role separation
Route.create({ path: '/dashboard', component: Dashboard }),
Route.create({ path: '/employees', component: Employees }),
Route.create({ path: '/attendance', component: Attendance }),
```

### After (Role-Based):
```typescript
import { requireAdmin, requireEmployee, requireAuth, requireGuest } from '@/lib/auth';

// Guest routes (login, register)
const guestRoute = createRoute({
  id: 'guest',
  beforeLoad: requireGuest,
}).children([
  createRoute({ path: '/login', component: LoginPage }),
  createRoute({ path: '/register', component: RegisterPage }),
]);

// Admin routes
const adminRoute = createRoute({
  id: 'admin',
  path: '/admin',
  beforeLoad: requireAdmin,
  component: AdminLayout,
}).children([
  createRoute({ path: '/dashboard', component: AdminDashboard }),
  createRoute({ path: '/employees', component: AdminEmployees }),
  createRoute({ path: '/schedules', component: AdminSchedules }),
  createRoute({ path: '/payroll', component: AdminPayroll }),
  createRoute({ path: '/leave', component: AdminLeave }),
  createRoute({ path: '/attendance', component: AdminAttendance }),
  createRoute({ path: '/reports', component: AdminReports }),
  createRoute({ path: '/settings', component: AdminSettings }),
  // ... more admin routes
]);

// Employee routes
const employeeRoute = createRoute({
  id: 'employee',
  path: '/employee',
  beforeLoad: requireEmployee,
  component: EmployeeLayout,
}).children([
  createRoute({ path: '/dashboard', component: EmployeeDashboard }),
  createRoute({ path: '/attendance', component: EmployeeAttendance }),
  createRoute({ path: '/schedule', component: EmployeeSchedule }),
  createRoute({ path: '/leave', component: EmployeeLeave }),
  createRoute({ path: '/payroll', component: EmployeePayroll }),
  createRoute({ path: '/profile', component: EmployeeProfile }),
]);

// Shared routes (both admin and employee can access)
const sharedRoute = createRoute({
  id: 'shared',
  beforeLoad: requireAuth,
}).children([
  createRoute({ path: '/verify-face', component: VerifyFace }),
  createRoute({ path: '/verify-location', component: VerifyLocation }),
  createRoute({ path: '/change-password', component: ChangePassword }),
]);
```

---

## Login Redirect Logic

```typescript
// pages/login.tsx

const handleLogin = async (credentials) => {
  const { user, token } = await loginApi(credentials);

  // Save auth data
  setAuthState({ user, token });

  // Redirect based on role
  if (hasRole(user, 'admin')) {
    navigate({ to: '/admin/dashboard' });
  } else {
    navigate({ to: '/employee/dashboard' });
  }
};
```

---

## Navigation/Sidebar Updates

### Admin Sidebar:
```typescript
// components/layout/admin-sidebar.tsx

const adminMenuItems = [
  { icon: LayoutDashboard, label: 'Dashboard', path: '/admin/dashboard' },
  { icon: Users, label: 'Employees', path: '/admin/employees' },
  { icon: Calendar, label: 'Schedules', path: '/admin/schedules' },
  { icon: Clock, label: 'Attendance', path: '/admin/attendance' },
  { icon: Plane, label: 'Leave Requests', path: '/admin/leave' },
  { icon: DollarSign, label: 'Payroll', path: '/admin/payroll' },
  { icon: FileText, label: 'Reports', path: '/admin/reports' },
  { icon: Settings, label: 'Settings', path: '/admin/settings' },
];
```

### Employee Sidebar:
```typescript
// components/layout/employee-sidebar.tsx

const employeeMenuItems = [
  { icon: Home, label: 'Dashboard', path: '/employee/dashboard' },
  { icon: Clock, label: 'My Attendance', path: '/employee/attendance' },
  { icon: Calendar, label: 'My Schedule', path: '/employee/schedule' },
  { icon: Plane, label: 'My Leave', path: '/employee/leave' },
  { icon: DollarSign, label: 'My Payslips', path: '/employee/payroll' },
  { icon: User, label: 'My Profile', path: '/employee/profile' },
];
```

---

## Import Updates Required

After moving files, update all imports:

**Before:**
```typescript
import { EmployeeList } from '@/pages/employees';
import { Dashboard } from '@/pages/dashboard';
```

**After:**
```typescript
import { EmployeeList } from '@/pages/admin/employees';
import { AdminDashboard } from '@/pages/admin/dashboard';
import { EmployeeDashboard } from '@/pages/employee/dashboard';
```

Use find-and-replace or automated tools:
```bash
# Find all import statements that need updating
grep -r "from '@/pages/employees" frontend/src/
grep -r "from '@/pages/schedules" frontend/src/
# ... etc
```

---

## Testing Checklist

After migration, test:

- [ ] **Login as Admin**
  - [ ] Redirects to `/admin/dashboard`
  - [ ] Can access all `/admin/*` routes
  - [ ] Cannot access `/employee/*` routes (auto-redirects)
  - [ ] Sidebar shows admin menu items

- [ ] **Login as Employee**
  - [ ] Redirects to `/employee/dashboard`
  - [ ] Can access all `/employee/*` routes
  - [ ] Cannot access `/admin/*` routes (auto-redirects)
  - [ ] Sidebar shows employee menu items

- [ ] **Shared Routes**
  - [ ] Both roles can access `/verify-face`
  - [ ] Both roles can access `/verify-location`
  - [ ] Both roles can access `/change-password`

- [ ] **Guest Routes**
  - [ ] Can access `/login` when not logged in
  - [ ] Auto-redirects to dashboard if already logged in

- [ ] **404 & Unauthorized**
  - [ ] Shows 404 for unknown routes
  - [ ] Shows unauthorized page when trying to access forbidden routes

---

## Rollback Plan

If migration causes issues, rollback:

```bash
# Restore from backup
cd frontend/src
rm -rf pages
tar -xzf pages_backup_YYYYMMDD_HHMMSS.tar.gz
```

---

## Benefits After Migration

✅ **Security:**
- Admin code not exposed to employees (code splitting)
- Clear permission boundaries
- Easier to audit access control

✅ **Performance:**
- Smaller bundle sizes (only load relevant pages)
- Faster initial load for employees
- Better code splitting

✅ **Maintainability:**
- Clear separation of concerns
- Easier to find files (know which folder to check)
- Less if-else conditional logic

✅ **Scalability:**
- Easy to add new roles (manager, supervisor, HR)
- Easy to add role-specific features
- Clear path for feature development

✅ **Developer Experience:**
- Clearer project structure
- Easier onboarding for new developers
- Better IDE navigation

---

**Created:** 2025-12-01 03:45 UTC
**Status:** Ready to execute
**Next Steps:** Run migration script → Update imports → Create new pages → Update router → Test
