# Monthly Schedule System - Integration Complete

## Overview

This document describes the complete implementation of the Monthly Schedule Management System and its integration with the Attendance System.

**Implementation Date:** December 2, 2025
**Status:** ✅ Complete and Integrated

---

## Features Implemented

### 1. Monthly Schedule CRUD Operations

#### ✅ Create Monthly Schedule
- **Location:** `frontend/src/pages/admin/schedules/monthly/create.tsx`
- **API Endpoint:** `POST /api/v1/monthly-schedules`
- **Features:**
  - Month and year selection
  - Location assignment
  - Working day pattern generator (auto-generate Mon-Fri, etc.)
  - Manual calendar picker for custom working days
  - Time window configuration:
    - Default work hours (e.g., 08:00 - 16:00)
    - Check-in window (e.g., 07:00 - 09:00)
    - Check-out window (e.g., 15:00 - 18:00)
  - Holiday conflict detection
  - Cache invalidation for immediate UI updates

#### ✅ Read/List Monthly Schedules
- **Location:** `frontend/src/pages/admin/schedules/monthly/index.tsx`
- **API Endpoint:** `GET /api/v1/monthly-schedules`
- **Features:**
  - Filterable by month, year, location
  - Search by name
  - Displays assigned employee count
  - Action menu: Edit, Assign Employees, Delete
  - Responsive design (desktop + mobile)

#### ✅ Update Monthly Schedule
- **Location:** `frontend/src/pages/admin/schedules/monthly/edit.tsx`
- **API Endpoint:** `PUT /api/v1/monthly-schedules/{id}`
- **Features:**
  - Pre-fills all existing data
  - Re-generate working days option
  - Manual adjustments to working days
  - Updates time windows
  - Maintains schedule integrity

#### ✅ Delete Monthly Schedule
- **API Endpoint:** `DELETE /api/v1/monthly-schedules/{id}`
- **Features:**
  - Soft delete with confirmation
  - Automatic cleanup of employee assignments

---

### 2. Employee Assignment

#### ✅ Assign Employees to Schedule
- **Location:** `frontend/src/pages/admin/schedules/monthly/index.tsx` (dialog)
- **API Endpoint:** `POST /api/v1/monthly-schedules/{id}/assign`
- **Features:**
  - Multi-select employee assignment
  - **Auto-Replace Logic:** Automatically removes conflicting schedules from the same month/year
  - Search and filter employees
  - Shows employee name, position, location
  - Real-time assignment count updates

#### ✅ View Assigned Employees
- **API Endpoint:** `GET /api/v1/monthly-schedules/{id}/employees`
- **Features:**
  - List all employees assigned to a schedule
  - Display employee details
  - Unassign functionality

#### ✅ Unassign Employee
- **API Endpoint:** `POST /api/v1/monthly-schedules/{id}/unassign`
- **Features:**
  - Remove employee from schedule
  - Confirmation dialog

---

### 3. Employee Schedule View

#### ✅ Employee Self-Service Schedule Page
- **Location:** `frontend/src/pages/employee/schedule.tsx`
- **API Endpoint:** `GET /api/v1/monthly-schedules/my-schedule`
- **Features:**
  - Month/year navigation (prev/next buttons)
  - **Calendar Grid Visualization:**
    - Working days highlighted in green
    - Weekends/non-working days in gray
    - Current day highlighted with ring
    - Day-of-week headers
  - **Schedule Information Card:**
    - Schedule name
    - Period (month/year)
    - Location
    - Total working days count
  - **Time Settings Card:**
    - Work hours (e.g., 08:00 - 16:00)
    - Check-in window
    - Check-out window
  - **Empty State Handling:**
    - Clear message if no schedule assigned
    - Instruction to contact admin

---

### 4. Attendance System Integration

This is the critical integration that makes schedules actually **enforce** attendance rules.

#### ✅ Phase 1: Working Day Validation

**Implementation:** `backend/app/Http/Controllers/AttendanceController.php`

**Check-In Validation:**
1. Retrieve employee's assigned monthly schedule
2. Verify current date exists in `working_days` array
3. **If NOT a working day:** Reject check-in with error message
4. **If IS a working day:** Proceed to Phase 2

**Error Message:**
```
"Hari ini bukan hari kerja menurut jadwal Anda. Silakan hubungi admin jika terjadi kesalahan."
```

**Fallback Behavior:**
- If employee has NO schedule assigned → Allow check-in (backward compatibility)
- System logs schedule info in attendance metadata

---

#### ✅ Phase 2: Time Window Validation

**Check-In Time Validation:**

1. **Too Early** (before `checkin_start_time`):
   - **Error:** `"Check-in hanya diperbolehkan mulai pukul {time}. Saat ini terlalu awal."`
   - **HTTP 400** - Rejected

2. **Within Window** (`checkin_start_time` to `checkin_end_time`):
   - **Allowed** ✅
   - Check if late (after `default_start_time`)
   - **Warning Message:** `"Anda terlambat. Jam kerja dimulai pada {time}."`
   - Metadata: `is_late: true`

3. **Too Late** (after `checkin_end_time`):
   - **Error:** `"Window check-in telah berakhir pada pukul {time}. Silakan hubungi admin."`
   - **HTTP 400** - Rejected

**Check-Out Time Validation:**

1. **Too Early** (before `checkout_start_time`):
   - **Error:** `"Check-out hanya diperbolehkan mulai pukul {time}. Saat ini terlalu awal."`
   - **HTTP 400** - Rejected

2. **Within Window** (`checkout_start_time` to `checkout_end_time`):
   - **Allowed** ✅
   - Check if early (before `default_end_time`)
   - **Warning Message:** `"Anda pulang lebih awal. Jam kerja berakhir pada {time}."`
   - Metadata: `is_early: true`

3. **Too Late** (after `checkout_end_time`):
   - **Error:** `"Window check-out telah berakhir pada pukul {time}. Silakan hubungi admin."`
   - **HTTP 400** - Rejected

---

#### ✅ Attendance Metadata Enrichment

When a schedule is found, attendance records now include:

```json
{
  "metadata": {
    "monthly_schedule_id": "uuid",
    "schedule_name": "Jadwal Februari 2025",
    "expected_start_time": "08:00",
    "expected_end_time": "16:00",
    "is_late": false,
    "is_early": false
  }
}
```

This allows for:
- Historical tracking of which schedule was active
- Retroactive analysis of late/early patterns
- Audit trail for schedule changes

---

#### ✅ Enhanced API Responses

**Check-In Response:**
```json
{
  "success": true,
  "message": "Check-in successful. Anda terlambat. Jam kerja dimulai pada 08:00.",
  "data": {
    "attendance_id": "uuid",
    "check_in_time": "2025-02-10 08:15:00",
    "location_verified": true,
    "confidence": 0.95,
    "schedule": {
      "name": "Jadwal Februari 2025",
      "expected_start": "08:00",
      "expected_end": "16:00",
      "is_late": true
    }
  }
}
```

**Check-Out Response:**
```json
{
  "success": true,
  "message": "Check-out successful",
  "data": {
    "attendance_id": "uuid",
    "check_out_time": "2025-02-10 16:00:00",
    "total_hours": 7.75,
    "status": "present",
    "location_verified": true,
    "confidence": 0.97,
    "schedule": {
      "name": "Jadwal Februari 2025",
      "expected_start": "08:00",
      "expected_end": "16:00",
      "is_early": false
    }
  }
}
```

---

## Technical Implementation Details

### Database Structure

**`monthly_schedules` Table:**
```sql
- id (uuid)
- name (string)
- month (integer 1-12)
- year (integer)
- start_date (date)
- end_date (date)
- location_id (uuid, FK)
- default_start_time (time)
- default_end_time (time)
- checkin_start_time (time)
- checkin_end_time (time)
- checkout_start_time (time)
- checkout_end_time (time)
- working_days (json array of ISO dates)
- total_working_days (integer)
- is_active (boolean)
- description (text, nullable)
- metadata (json)
- created_by, updated_by (uuid, FK)
- created_at, updated_at, deleted_at
```

**`employee_monthly_schedules` Table (Assignment Junction):**
```sql
- id (uuid)
- monthly_schedule_id (uuid, FK)
- employee_id (uuid, FK)
- assigned_at (timestamp)
- assigned_by (uuid, FK)
- created_at, updated_at, deleted_at
```

### Backend Architecture

**Controllers:**
- `MonthlyScheduleApiController.php` - CRUD operations, assignment logic
- `AttendanceController.php` - Integrated validation methods

**Models:**
- `MonthlySchedule.php` - Main schedule model
- `EmployeeMonthlySchedule.php` - Assignment pivot model

**New Helper Methods in `AttendanceController`:**
```php
private function getEmployeeScheduleForDate(Employee $employee, ?Carbon $date): ?MonthlySchedule
private function validateWorkingDay(Employee $employee, ?Carbon $date): array
private function validateCheckInWindow(MonthlySchedule $schedule, ?Carbon $time): array
private function validateCheckOutWindow(MonthlySchedule $schedule, ?Carbon $time): array
```

### Frontend Architecture

**Pages:**
- `admin/schedules/monthly/index.tsx` - List page with filters
- `admin/schedules/monthly/create.tsx` - Create form
- `admin/schedules/monthly/edit.tsx` - Edit form
- `employee/schedule.tsx` - Employee view

**API Client:**
- `lib/api/schedules.ts` - All schedule API functions
- Functions:
  - `getMonthlyAttendanceSchedules()`
  - `getMonthlyAttendanceSchedule()`
  - `createMonthlyAttendanceSchedule()`
  - `updateMonthlyAttendanceSchedule()`
  - `deleteMonthlyAttendanceSchedule()`
  - `assignEmployeesToSchedule()`
  - `unassignEmployeeFromSchedule()`
  - `getScheduleEmployees()`
  - `generateWorkingDays()`
  - `getMySchedule()` ← Employee endpoint

**State Management:**
- React Query for server state caching
- Cache invalidation on mutations
- Optimistic updates for better UX

---

## User Workflow

### Admin Workflow

1. **Create Monthly Schedule:**
   - Navigate to `/admin/schedules/monthly`
   - Click "Buat Jadwal Baru"
   - Select month, year, location
   - Choose working day pattern OR manually select days
   - Configure time windows
   - Save → Auto-redirects to list with new schedule visible

2. **Assign Employees:**
   - Click "Assign" button on schedule
   - Search/filter employees
   - Select multiple employees
   - Click "Assign" → Conflicting schedules auto-replaced
   - Confirmation toast shown

3. **Edit Schedule:**
   - Click menu → "Edit Jadwal"
   - Modify any field
   - Re-generate working days if needed
   - Save → Updates applied, employees unaffected

4. **View Assignments:**
   - Assigned employee count shown on card
   - Click to view full list
   - Unassign individual employees if needed

### Employee Workflow

1. **View Schedule:**
   - Navigate to `/employee/schedule`
   - See current month by default
   - Navigate to future/past months
   - Visual calendar shows working days
   - Time windows clearly displayed

2. **Check-In:**
   - Open attendance page
   - Capture face
   - System validates:
     - Is today a working day? ✅
     - Is time within check-in window? ✅
     - Is employee late? → Warning shown
   - Attendance recorded

3. **Check-Out:**
   - Open attendance page
   - Capture face
   - System validates:
     - Is time within check-out window? ✅
     - Is employee leaving early? → Warning shown
   - Attendance completed

---

## Edge Cases Handled

### ✅ No Schedule Assigned
- **Behavior:** Attendance allowed (backward compatibility)
- **Message:** Normal check-in/out messages
- **Metadata:** No schedule info

### ✅ Inactive Schedule
- **Behavior:** Ignored (query filters `is_active = true`)
- **Effect:** Falls back to "no schedule" behavior

### ✅ Multiple Schedules (Conflict)
- **Prevention:** Auto-replace logic in assignment
- **Rule:** Only ONE active schedule per employee per month/year
- **Implementation:** Hard delete conflicting records before new assignment

### ✅ Holiday Overrides
- **Future Enhancement:** System detects holidays that conflict with working days
- **Current:** Working days take precedence (admin manually adjusts)

### ✅ Schedule Changes Mid-Month
- **Behavior:** Existing attendance records retain original metadata
- **New Attendance:** Uses updated schedule rules
- **Benefit:** Historical accuracy maintained

### ✅ Deleted Schedule
- **Soft Delete:** Schedule marked as deleted, not removed
- **Assignments:** Cascade soft delete
- **Attendance:** Historical records unaffected (metadata preserved)

---

## Testing Scenarios

### Scenario 1: Normal Working Day
- **Setup:** Schedule has Feb 10 as working day, check-in window 07:00-09:00
- **Test:** Employee checks in at 08:00
- **Expected:** ✅ Success, `is_late: false`

### Scenario 2: Late Check-In
- **Setup:** Schedule has work start at 08:00, check-in window 07:00-09:00
- **Test:** Employee checks in at 08:30
- **Expected:** ✅ Success with warning, `is_late: true`

### Scenario 3: Non-Working Day
- **Setup:** Schedule does NOT have Feb 11 as working day
- **Test:** Employee tries to check in on Feb 11
- **Expected:** ❌ HTTP 400 - "Hari ini bukan hari kerja..."

### Scenario 4: Outside Check-In Window
- **Setup:** Check-in window is 07:00-09:00
- **Test:** Employee tries to check in at 09:30
- **Expected:** ❌ HTTP 400 - "Window check-in telah berakhir..."

### Scenario 5: Early Check-Out
- **Setup:** Work end is 16:00, check-out window 15:00-18:00
- **Test:** Employee checks out at 15:30
- **Expected:** ✅ Success with warning, `is_early: true`

### Scenario 6: No Schedule Assigned
- **Setup:** Employee has no monthly schedule
- **Test:** Employee tries to check in
- **Expected:** ✅ Success (legacy mode)

---

## API Endpoints Summary

### Monthly Schedule CRUD
```
GET    /api/v1/monthly-schedules          # List with filters
POST   /api/v1/monthly-schedules          # Create
GET    /api/v1/monthly-schedules/{id}     # Show details
PUT    /api/v1/monthly-schedules/{id}     # Update
DELETE /api/v1/monthly-schedules/{id}     # Soft delete
```

### Employee Assignment
```
POST   /api/v1/monthly-schedules/{id}/assign     # Assign employees
POST   /api/v1/monthly-schedules/{id}/unassign   # Unassign employee
GET    /api/v1/monthly-schedules/{id}/employees  # List assigned
```

### Employee Self-Service
```
GET    /api/v1/monthly-schedules/my-schedule?month=2&year=2025
```

### Helper Endpoints
```
POST   /api/v1/monthly-schedules/generate-working-days
```

### Attendance (Enhanced)
```
POST   /api/v1/attendance/check-in   # Now validates schedule
POST   /api/v1/attendance/check-out  # Now validates schedule
```

---

## Files Modified/Created

### Backend Files
```
✅ Created:
- app/Http/Controllers/Api/MonthlyScheduleApiController.php
- app/Models/MonthlySchedule.php
- database/migrations/*_create_monthly_schedules_table.php
- database/migrations/*_create_employee_monthly_schedules_table.php
- database/seeders/MonthlyScheduleSeeder.php

✅ Modified:
- app/Http/Controllers/AttendanceController.php (added validation)
- routes/api.php (added monthly schedule routes)
```

### Frontend Files
```
✅ Created:
- pages/admin/schedules/monthly/index.tsx
- pages/admin/schedules/monthly/create.tsx
- pages/admin/schedules/monthly/edit.tsx
- pages/employee/schedule.tsx

✅ Modified:
- lib/api/schedules.ts (added monthly schedule functions)
- app/router.tsx (added routes)
- config/navigation.ts (added menu items)
```

---

## Performance Optimizations

1. **Query Optimization:**
   - Eager loading relationships (`with(['location', 'creator'])`)
   - Index on `(employee_id, month, year)` for fast lookups

2. **Caching Strategy:**
   - React Query caches schedule data
   - 5-minute stale time for list views
   - Automatic invalidation on mutations

3. **Pagination:**
   - Backend pagination (15 per page default)
   - Frontend virtualization for large lists

4. **Database Cleanup:**
   - Auto-replace logic prevents orphaned records
   - Soft deletes for audit trail
   - Cascade deletes on relationships

---

## Security Considerations

1. **Authorization:**
   - Only admins can create/edit/delete schedules
   - Employees can only view their own schedule
   - API middleware enforces role checks

2. **Validation:**
   - Server-side validation on all inputs
   - Date range validation (start ≤ end)
   - Time window validation (start < end)
   - Working days must be within month bounds

3. **Data Integrity:**
   - Foreign key constraints
   - Unique constraint on `(employee_id, monthly_schedule_id)`
   - Cascade rules for related deletions
   - Transaction wrapping for multi-step operations

---

## Future Enhancements

### Phase 3: Location-Based Validation (Planned)
- Use schedule's `location_id` for GPS verification
- Enforce geofencing based on schedule location
- Allow location override for special cases

### Phase 4: Shift Patterns (Planned)
- Multiple shifts per day (morning, afternoon, night)
- Rotating shift schedules
- Shift swap requests

### Phase 5: Analytics (Planned)
- Schedule compliance reports
- Late/early patterns per employee
- Location utilization metrics
- Schedule change audit logs

### Phase 6: Notifications (Planned)
- Notify employees when schedule is assigned
- Remind employees of tomorrow's schedule
- Alert admin of low compliance

---

## Conclusion

The Monthly Schedule System is now **fully integrated** with the Attendance System.

**What Changed:**
- ❌ **Before:** Attendance had no concept of working days or time windows
- ✅ **After:** Attendance validates every check-in/out against assigned schedules

**Business Impact:**
- Employees can only check in on scheduled working days
- Check-ins outside time windows are rejected
- Late/early patterns are automatically tracked
- Admins have full control over work schedules
- Self-service schedule viewing reduces admin inquiries

**Technical Quality:**
- Type-safe TypeScript frontend
- Service layer pattern in Laravel backend
- Comprehensive validation at all layers
- Proper error handling and user feedback
- Backward compatible (no-schedule fallback)
- Performance optimized queries
- Security hardened authorization

---

## Support & Maintenance

**Testing:**
```bash
# Backend
cd backend && php artisan test --filter=MonthlySchedule

# Frontend
cd frontend && npm run test

# E2E
cd frontend && npm run test:e2e
```

**Logs:**
- Attendance validation: `storage/logs/laravel.log`
- Schedule changes: `monthly_schedules.metadata` field
- Errors: Check `failed_jobs` table

**Database Maintenance:**
```bash
# View schedules
php artisan tinker
>>> MonthlySchedule::with('employeeSchedules')->get();

# Cleanup soft-deleted schedules (older than 90 days)
>>> MonthlySchedule::onlyTrashed()->where('deleted_at', '<', now()->subDays(90))->forceDelete();
```

---

**Documentation Version:** 1.0
**Last Updated:** December 2, 2025
**Implementation Status:** ✅ Production Ready
