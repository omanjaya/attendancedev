# Phase 4: Leave Management & Attendance Corrections Flow

**Comprehensive Documentation for Leave Request and Attendance Correction System**

---

## Table of Contents

1. [Leave Types](#1-leave-types)
2. [Leave Request Flow (Employee)](#2-leave-request-flow-employee)
3. [Leave Approval Flow (Admin/Kepala Sekolah)](#3-leave-approval-flow-adminkepala-sekolah)
4. [Leave Balance Management](#4-leave-balance-management)
5. [Attendance Correction Request (Employee)](#5-attendance-correction-request-employee)
6. [Attendance Correction Approval (Admin)](#6-attendance-correction-approval-admin)
7. [Leave Calendar](#7-leave-calendar)
8. [Database Schema](#8-database-schema)
9. [API Reference](#9-api-reference)

---

## 1. Leave Types

### Overview

The system supports multiple leave types with configurable policies and balances. Each leave type has specific rules regarding approval requirements, default allocations, and metadata.

### Leave Type Categories

| Leave Type | Code | Default Days/Year | Paid | Requires Approval | Description |
|------------|------|-------------------|------|-------------------|-------------|
| Cuti Tahunan | `ANNUAL` | 12 | Yes | Yes | Annual leave for all employees |
| Cuti Sakit | `SICK` | 12 | Yes | Yes | Sick leave (requires medical certificate) |
| Cuti Melahirkan | `MATERNITY` | 90 | Yes | Yes | Maternity leave (female only) |
| Cuti Menikah | `MARRIAGE` | 3 | Yes | Yes | Marriage leave (requires certificate) |
| Cuti Duka | `BEREAVEMENT` | 3 | Yes | Yes | Bereavement leave (requires certificate) |
| Cuti Ibadah Haji | `HAJJ` | 40 | Yes | Yes | Hajj pilgrimage leave |
| Izin Tidak Masuk | `PERMISSION` | 6 | No | Yes | Permission to be absent |
| Cuti Bersama | `COLLECTIVE` | 0 | Yes | No | Government-mandated collective leave |

### Leave Type Metadata

Each leave type includes metadata for advanced rules:

```json
{
  "max_consecutive_days": 7,
  "advance_notice_days": 3,
  "color": "#22c55e",
  "requires_medical_certificate": true,
  "gender_specific": "female",
  "religious_leave": true,
  "government_mandated": true
}
```

### Database: `leave_types`

**File**: `/opt/attendancedev/backend/database/migrations/2025_07_03_140552_create_leave_types_table.php`

**Columns**:
- `id` (UUID) - Primary key
- `name` (string) - Leave type name (e.g., "Cuti Tahunan")
- `code` (string, unique) - Leave type code (e.g., "ANNUAL")
- `description` (text, nullable) - Description of leave type
- `default_days_per_year` (integer) - Default allocation per year
- `requires_approval` (boolean) - Whether this leave needs approval
- `is_paid` (boolean) - Whether this is paid leave
- `is_active` (boolean) - Whether this leave type is active
- `metadata` (json, nullable) - Additional configuration
- `timestamps` - created_at, updated_at
- `softDeletes` - deleted_at

**Model**: `/opt/attendancedev/backend/app/Models/LeaveType.php`

**Key Methods**:
- `scopeActive()` - Get only active leave types
- `scopeRequiresApproval()` - Get leave types requiring approval
- `getDisplayNameAttribute()` - Returns "Name (CODE)"

**Seeder**: `/opt/attendancedev/backend/database/seeders/LeaveTypeSeeder.php`

Automatically creates all 8 default leave types with their configurations.

---

## 2. Leave Request Flow (Employee)

### Overview

Employees can submit leave requests through the system. The workflow includes validation, balance checking, conflict detection, and automatic working day calculation.

### Frontend Components

#### Desktop View
**File**: `/opt/attendancedev/frontend/src/pages/employee/leave/desktop.tsx`

**Features**:
- Leave balance display (annual, sick, special)
- Leave request form modal
- Request history table
- Status badges and filtering

#### Mobile View
**File**: `/opt/attendancedev/frontend/src/pages/employee/leave/mobile.tsx`

**Features**:
- Mobile-optimized leave balance cards
- Touch-friendly form inputs
- Swipeable request cards

### Request Submission Process

#### Step 1: Employee Opens Form

**UI Components**:
- Leave type dropdown (annual, sick, maternity, special, etc.)
- Date range picker (start date, end date)
- Duration type selector (full_day, half_day_am, half_day_pm)
- Reason textarea (required)
- Emergency contact fields (optional)
- Attachment upload (optional)

#### Step 2: Working Days Preview

**API Endpoint**: `GET /api/v1/leave/preview-working-days`

**Request**:
```json
{
  "start_date": "2025-12-20",
  "end_date": "2025-12-27"
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "working_days": 5,
    "working_dates": ["2025-12-20", "2025-12-23", "2025-12-24", "2025-12-26", "2025-12-27"],
    "skipped_weekends": [
      { "date": "2025-12-21", "day": "Saturday" },
      { "date": "2025-12-22", "day": "Sunday" }
    ],
    "skipped_holidays": [
      { "date": "2025-12-25", "holiday_name": "Christmas Day" }
    ],
    "total_calendar_days": 8
  }
}
```

This preview helps employees understand how many working days will be deducted from their balance, excluding weekends and holidays.

#### Step 3: Submit Request

**API Endpoint**: `POST /api/v1/leave-requests`

**Request** (multipart/form-data):
```json
{
  "type": "ANNUAL",
  "start_date": "2025-12-20",
  "end_date": "2025-12-27",
  "duration_type": "full_day",
  "reason": "Family vacation",
  "emergency_contact": "Jane Doe",
  "emergency_phone": "081234567890",
  "attachment": <file>
}
```

**Response**:
```json
{
  "success": true,
  "message": "Leave request created",
  "data": {
    "id": "uuid",
    "employee_id": "uuid",
    "leave_type_id": "uuid",
    "start_date": "2025-12-20",
    "end_date": "2025-12-27",
    "days_requested": 5,
    "reason": "Family vacation",
    "status": "pending",
    "metadata": {
      "duration_type": "full_day",
      "emergency_contact": "Jane Doe",
      "emergency_phone": "081234567890"
    },
    "created_at": "2025-12-15T10:30:00Z"
  }
}
```

### Backend Processing

**Controller**: `/opt/attendancedev/backend/app/Http/Controllers/Api/LeaveApiController.php`

**Service**: `/opt/attendancedev/backend/app/Services/LeaveService.php`

#### Validation Steps

1. **Leave Type Resolution**
   - Accepts UUID, name, or code
   - Verifies leave type is active

2. **Working Days Calculation**
   - Uses `Leave::calculateWorkingDays()` method
   - Excludes weekends (Saturday, Sunday)
   - Excludes public holidays from `holidays` table
   - Must contain at least 1 working day

3. **Balance Check**
   ```php
   $balance = LeaveBalance::where('employee_id', $employee->id)
       ->where('leave_type_id', $leaveTypeId)
       ->where('year', $year)
       ->first();

   if (!$balance->canTakeDays($daysRequested)) {
       throw new Exception("Insufficient leave balance");
   }
   ```

4. **Conflict Detection**
   - Checks for overlapping leave requests
   - Only considers approved or pending leaves
   - Prevents duplicate date ranges

5. **Create Leave Record**
   ```php
   Leave::create([
       'employee_id' => $employee->id,
       'leave_type_id' => $leaveTypeId,
       'start_date' => $data['start_date'],
       'end_date' => $data['end_date'],
       'days_requested' => $daysRequested,
       'reason' => $data['reason'],
       'status' => 'pending',
       'is_emergency' => $data['is_emergency'] ?? false,
       'metadata' => [...],
   ]);
   ```

6. **Notification to Approvers**
   - Sends notification to users with `approve_leave` permission
   - Roles: super-admin, admin, kepala-sekolah

### View Request Status

**API Endpoint**: `GET /api/v1/leave-requests`

**Filters**:
- `status`: pending, approved, rejected, cancelled
- `type`: leave_type_id (UUID)
- `employee_id`: UUID (admin only)
- `start_date`: Filter from date
- `end_date`: Filter to date
- `page`: Pagination
- `per_page`: Items per page (default: 15)

**Response**:
```json
{
  "success": true,
  "message": "Leave requests retrieved",
  "data": {
    "data": [...],
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 45
  }
}
```

### Cancel Request

**API Endpoint**: `POST /api/v1/leave-requests/{id}/cancel`

**Rules**:
- Can cancel if status is `pending`
- Can cancel if status is `approved` AND start_date is in the future
- IDOR protection: Can only cancel own requests

**Process**:
1. Check if cancellation is allowed
2. If approved, restore leave balance
3. Delete auto-created attendance records
4. Restore affected teaching schedules (for teachers)
5. Update status to `cancelled`

**Teaching Schedule Restoration** (for teachers):
```php
// Finds schedules marked with leave_affected metadata
$schedules = TeachingSchedule::where('teacher_id', $leave->employee_id)
    ->whereJsonContains('metadata->leave_affected->leave_id', $leave->id)
    ->get();

// Restores original status
foreach ($schedules as $schedule) {
    $schedule->update([
        'status' => $originalStatus,
        'substitute_teacher_id' => null,
        'substitution_start_date' => null,
        'substitution_end_date' => null,
    ]);
}
```

---

## 3. Leave Approval Flow (Admin/Kepala Sekolah)

### Overview

Administrators and school principals can approve or reject leave requests. The system automatically handles balance deductions, attendance record creation, and teaching schedule updates for approved leaves.

### Frontend Components

#### Approval Dashboard
**File**: `/opt/attendancedev/frontend/src/pages/admin/leave/approvals.tsx`

**Features**:
- Pending requests counter
- Filter by employee, date range, leave type
- Quick approve/reject actions
- Affected schedules preview (for teachers)

#### Detail View
**File**: `/opt/attendancedev/frontend/src/pages/admin/leave/show.tsx`

**Features**:
- Full request details
- Employee information
- Leave balance status
- Approval/rejection form
- Affected teaching schedules (if teacher)

### Approval Process

#### Step 1: View Pending Requests

**API Endpoint**: `GET /api/v1/leave-requests/pending`

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "employee": {
        "id": "uuid",
        "employee_id": "NIP001",
        "full_name": "John Doe"
      },
      "leaveType": {
        "id": "uuid",
        "name": "Cuti Tahunan",
        "code": "ANNUAL"
      },
      "start_date": "2025-12-20",
      "end_date": "2025-12-27",
      "days_requested": 5,
      "reason": "Family vacation",
      "status": "pending",
      "created_at": "2025-12-15T10:30:00Z"
    }
  ]
}
```

#### Step 2: Preview Affected Schedules (Teachers Only)

**API Endpoint**: `GET /api/v1/leave-requests/{id}/affected-schedules`

**Response**:
```json
{
  "success": true,
  "data": {
    "leave_id": "uuid",
    "employee_name": "John Doe",
    "is_teacher": true,
    "affected_count": 8,
    "affected_schedules": [
      {
        "schedule_id": "uuid",
        "date": "2025-12-20",
        "day": "Senin",
        "subject": "Matematika",
        "class": "X IPA 1",
        "time": "07:30 - 09:00",
        "room": "Lab Komputer 1"
      }
    ]
  }
}
```

#### Step 3: Approve Request

**API Endpoint**: `POST /api/v1/leave-requests/{id}/approve`

**Request**:
```json
{
  "notes": "Approved. Have a good vacation."
}
```

**Response**:
```json
{
  "success": true,
  "message": "Leave request approved",
  "data": {
    "id": "uuid",
    "status": "approved",
    "approved_by": "uuid",
    "approved_at": "2025-12-15T14:30:00Z",
    "approval_notes": "Approved. Have a good vacation."
  }
}
```

### Backend Approval Logic

**Service Method**: `LeaveService::approveRequest()`

**File**: `/opt/attendancedev/backend/app/Services/LeaveService.php`

#### Transaction Steps

```php
DB::transaction(function () use ($leave, $approverId, $notes) {
    // 1. Deduct Balance (with row locking)
    $balance = LeaveBalance::where('employee_id', $leave->employee_id)
        ->where('leave_type_id', $leave->leave_type_id)
        ->where('year', $year)
        ->lockForUpdate()
        ->first();

    $balance->deductDays($leave->days_requested);

    // 2. Update Leave Status
    $leave->update([
        'status' => 'approved',
        'approved_by' => $approverId,
        'approved_at' => now(),
        'approval_notes' => $notes,
    ]);

    // 3. Create Attendance Records
    foreach ($period as $date) {
        if (!$date->isWeekend() && !Holiday::isHoliday($date)) {
            Attendance::updateOrCreate(
                [
                    'employee_id' => $leave->employee_id,
                    'date' => $date->format('Y-m-d'),
                ],
                [
                    'status' => 'leave',
                    'check_in_time' => null,
                    'check_out_time' => null,
                    'metadata' => [
                        'leave_id' => $leave->id,
                        'leave_type' => $leave->leaveType->name,
                        'auto_created' => true,
                    ]
                ]
            );
        }
    }

    // 4. Handle Teaching Schedule Integration
    $this->handleTeachingScheduleOnLeave($leave);

    // 5. Send Notification to Employee
    $this->notifyEmployee($leave, 'approved', $notes);
});
```

#### Teaching Schedule Handling

For teachers (`employee_type` = 'guru' or 'guru_honorer'):

```php
protected function handleTeachingScheduleOnLeave(Leave $leave): array
{
    $affectedSchedules = [];
    $period = CarbonPeriod::create($leave->start_date, $leave->end_date);

    foreach ($period as $date) {
        if ($date->isWeekend() || Holiday::isHoliday($date)) continue;

        $dayOfWeek = strtolower($date->format('l'));

        $schedules = TeachingSchedule::where('teacher_id', $employee->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $date);
            })
            ->get();

        foreach ($schedules as $schedule) {
            // Store original status for restoration
            $metadata = $schedule->metadata ?? [];
            $metadata['leave_affected'] = [
                'leave_id' => $leave->id,
                'leave_type' => $leave->leaveType->name,
                'original_status' => $schedule->status,
                'affected_date' => $date->format('Y-m-d'),
            ];

            $schedule->update([
                'substitution_start_date' => $leave->start_date,
                'substitution_end_date' => $leave->end_date,
                'substitution_reason' => 'Cuti: ' . $leave->reason,
                'metadata' => $metadata,
            ]);
        }
    }

    return $affectedSchedules;
}
```

### Rejection Process

**API Endpoint**: `POST /api/v1/leave-requests/{id}/reject`

**Request**:
```json
{
  "rejection_reason": "Conflicting with important event. Please reschedule."
}
```

**Response**:
```json
{
  "success": true,
  "message": "Leave request rejected",
  "data": {
    "id": "uuid",
    "status": "rejected",
    "approved_by": "uuid",
    "approved_at": "2025-12-15T14:30:00Z",
    "rejection_reason": "Conflicting with important event. Please reschedule."
  }
}
```

**Backend Logic**:
```php
public function rejectRequest(Leave $leave, $rejectorId, $reason = null)
{
    if (!$leave->isPending()) {
        throw new Exception('Only pending leave requests can be rejected.');
    }

    $leave->update([
        'status' => 'rejected',
        'approved_by' => $rejectorId,
        'approved_at' => now(),
        'rejection_reason' => $reason,
    ]);

    // Send notification to employee
    $this->notifyEmployee($leave, 'rejected', $reason);

    return $leave->fresh();
}
```

**Note**: Rejection does NOT deduct from leave balance. Balance remains untouched.

---

## 4. Leave Balance Management

### Overview

Leave balances track allocated, used, and remaining leave days for each employee per leave type per year. The system supports carry-forward rules and automatic balance calculations.

### Database: `leave_balances`

**File**: `/opt/attendancedev/backend/database/migrations/2025_07_03_140602_create_leave_balances_table.php`

**Columns**:
- `id` (UUID) - Primary key
- `employee_id` (UUID) - Foreign key to employees
- `leave_type_id` (UUID) - Foreign key to leave_types
- `year` (year) - Balance year
- `allocated_days` (decimal 5,2) - Total allocated days
- `used_days` (decimal 5,2) - Days used
- `remaining_days` (decimal 5,2) - Days remaining
- `carried_forward` (decimal 5,2) - Days carried from previous year
- `is_active` (boolean) - Whether balance is active
- `metadata` (json, nullable) - Additional data
- `timestamps` - created_at, updated_at

**Unique Index**: `(employee_id, leave_type_id, year)`

### Model: LeaveBalance

**File**: `/opt/attendancedev/backend/app/Models/LeaveBalance.php`

**Key Methods**:

```php
// Update remaining days
public function updateRemainingDays()
{
    $this->remaining_days = $this->allocated_days - $this->used_days;
    $this->save();
}

// Check if employee can take days
public function canTakeDays($days): bool
{
    return $this->remaining_days >= $days;
}

// Deduct days from balance
public function deductDays($days)
{
    $this->used_days += $days;
    $this->updateRemainingDays();
}

// Add days back (for cancelled/rejected leaves)
public function addDays($days)
{
    $this->used_days -= $days;
    if ($this->used_days < 0) {
        $this->used_days = 0;
    }
    $this->updateRemainingDays();
}
```

### Initial Balance Setup

**Manually via Admin Interface** (recommended):
1. Admin navigates to Leave Balance Management
2. Selects employee
3. Sets allocated days for each leave type
4. System creates balance records

**Programmatically**:
```php
LeaveBalance::create([
    'employee_id' => $employee->id,
    'leave_type_id' => $leaveType->id,
    'year' => now()->year,
    'allocated_days' => $leaveType->default_days_per_year,
    'used_days' => 0,
    'remaining_days' => $leaveType->default_days_per_year,
    'carried_forward' => 0,
    'is_active' => true,
]);
```

### Balance Calculation

**Automatic Deduction on Approval**:
```php
// In LeaveService::approveRequest()
$balance->deductDays($leave->days_requested);

// Result:
// used_days = used_days + days_requested
// remaining_days = allocated_days - used_days
```

**Automatic Restoration on Cancellation**:
```php
// In LeaveService::cancelRequest()
if ($leave->isApproved()) {
    $balance->addDays($leave->days_requested);
}

// Result:
// used_days = used_days - days_requested
// remaining_days = allocated_days - used_days
```

### Carry Over Rules

**Year-End Carry Forward Process** (manual or automated job):

```php
// Example: Carry forward max 5 days of annual leave
$currentYearBalance = LeaveBalance::where('employee_id', $employee->id)
    ->where('leave_type_id', $annualLeaveTypeId)
    ->where('year', 2025)
    ->first();

$carryForwardDays = min($currentYearBalance->remaining_days, 5);

LeaveBalance::create([
    'employee_id' => $employee->id,
    'leave_type_id' => $annualLeaveTypeId,
    'year' => 2026,
    'allocated_days' => $leaveType->default_days_per_year,
    'used_days' => 0,
    'remaining_days' => $leaveType->default_days_per_year + $carryForwardDays,
    'carried_forward' => $carryForwardDays,
    'is_active' => true,
    'metadata' => [
        'carried_from_year' => 2025,
        'carry_forward_expiry' => '2026-03-31', // Optional expiry
    ],
]);
```

### Balance History

All balance changes are tracked through:
1. **Leave Requests**: Links to `leaves` table
2. **Timestamps**: `created_at`, `updated_at` on balance records
3. **Metadata**: Can store adjustment history in JSON field

### Get Balance (API)

**Current User Balance**:

**Endpoint**: `GET /api/v1/leave/balance`

**Response**:
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "employee_id": "uuid",
    "employee_name": "John Doe",
    "year": 2025,
    "annual_total": 12,
    "annual_used": 5,
    "annual_remaining": 7,
    "sick_total": 12,
    "sick_used": 2,
    "sick_remaining": 10,
    "special_total": 6,
    "special_used": 0,
    "special_remaining": 6,
    "carry_forward": 3,
    "updated_at": "2025-12-15T10:00:00Z"
  }
}
```

**Specific Employee Balance** (Admin):

**Endpoint**: `GET /api/v1/leave/balance/{employeeId}`

**IDOR Protection**: Non-admin users can only view their own balance.

### Aggregated Balance Logic

**Service Method**: `LeaveService::getAggregatedBalance()`

Aggregates multiple leave type balances into a single summary:

```php
public function getAggregatedBalance(Employee $employee)
{
    $balances = LeaveBalance::with('leaveType')
        ->where('employee_id', $employee->id)
        ->where('year', now()->year)
        ->get();

    $summary = [
        'annual_total' => 0,
        'annual_used' => 0,
        'annual_remaining' => 0,
        'sick_total' => 0,
        'sick_used' => 0,
        'sick_remaining' => 0,
        // ... etc
    ];

    foreach ($balances as $balance) {
        $code = strtolower($balance->leaveType->code);

        if (str_contains($code, 'annual')) {
            $summary['annual_total'] += $balance->allocated_days;
            $summary['annual_used'] += $balance->used_days;
            $summary['annual_remaining'] += $balance->remaining_days;
        }
        // ... map other types
    }

    return $summary;
}
```

---

## 5. Attendance Correction Request (Employee)

### Overview

Employees can request corrections to their attendance records when mistakes occur (wrong time, missed check-in/out, etc.). Admins review and approve these correction requests.

### Database: `attendance_corrections`

**File**: `/opt/attendancedev/backend/database/migrations/2025_12_09_105857_create_attendance_corrections_table.php`

**Columns**:
- `id` (UUID) - Primary key
- `employee_id` (UUID) - Foreign key to employees
- `attendance_id` (UUID, nullable) - Foreign key to attendances (null for add_missing)
- `correction_date` (date) - Date for which correction is requested
- `correction_type` (enum) - Type of correction:
  - `check_in` - Correct check-in time
  - `check_out` - Correct check-out time
  - `both` - Correct both times
  - `add_missing` - Add missing attendance
  - `delete` - Delete attendance record
- `original_check_in` (time, nullable) - Original check-in time
- `original_check_out` (time, nullable) - Original check-out time
- `requested_check_in` (time, nullable) - Requested check-in time
- `requested_check_out` (time, nullable) - Requested check-out time
- `reason` (text) - Reason for correction (required)
- `supporting_document` (string, nullable) - Path to uploaded document
- `status` (enum) - Correction status:
  - `pending` - Waiting for approval
  - `approved` - Approved by admin
  - `rejected` - Rejected by admin
  - `cancelled` - Cancelled by employee
- `reviewed_by` (foreignId, nullable) - User who reviewed
- `reviewed_at` (timestamp, nullable) - Review timestamp
- `review_notes` (text, nullable) - Admin review notes
- `timestamps` - created_at, updated_at
- `softDeletes` - deleted_at

**Indexes**:
- `(employee_id, correction_date)`
- `(status, created_at)`

### Frontend Components

#### Desktop View
**File**: `/opt/attendancedev/frontend/src/pages/employee/corrections/desktop.tsx`

**Features**:
- Correction request form dialog
- Request history table with filters
- Status badges (pending, approved, rejected)
- Document upload
- Cancel pending requests

#### Mobile View
**File**: `/opt/attendancedev/frontend/src/pages/employee/corrections/mobile.tsx`

**Features**:
- Touch-optimized form
- Card-based request list
- Swipe actions for cancellation

### Correction Request Types

1. **Correct Check-In Time** (`check_in`)
   - Original attendance exists
   - Only check-in time is wrong
   - Provide correct check-in time

2. **Correct Check-Out Time** (`check_out`)
   - Original attendance exists
   - Only check-out time is wrong
   - Provide correct check-out time

3. **Correct Both Times** (`both`)
   - Original attendance exists
   - Both times are wrong
   - Provide both correct times

4. **Add Missing Attendance** (`add_missing`)
   - No attendance record exists for the date
   - Employee forgot to check in/out
   - Provide at least check-in time

5. **Delete Attendance** (`delete`)
   - Attendance was recorded by mistake
   - Request to remove the record
   - Provide reason for deletion

### Request Submission Process

#### Step 1: Open Correction Form

**UI Components**:
- Date picker (correction_date) - Must be <= today
- Correction type dropdown
- Time inputs (conditional based on type)
- Reason textarea (min 10 characters, max 1000)
- Document upload (optional, max 5MB, PDF/JPG/PNG)

#### Step 2: Submit Correction

**API Endpoint**: `POST /api/v1/attendance-corrections`

**Request** (multipart/form-data):
```json
{
  "attendance_id": "uuid",
  "correction_date": "2025-12-15",
  "correction_type": "both",
  "requested_check_in": "08:00",
  "requested_check_out": "17:00",
  "reason": "I forgot to check in this morning due to urgent meeting. Here is the meeting proof.",
  "supporting_document": <file>
}
```

**Response**:
```json
{
  "success": true,
  "message": "Correction request submitted successfully",
  "data": {
    "id": "uuid",
    "employee_id": "uuid",
    "attendance_id": "uuid",
    "correction_date": "2025-12-15",
    "correction_type": "both",
    "original_check_in": "08:15:23",
    "original_check_out": "16:45:10",
    "requested_check_in": "08:00:00",
    "requested_check_out": "17:00:00",
    "reason": "I forgot to check in this morning...",
    "supporting_document": "correction-documents/employee-uuid/filename.pdf",
    "status": "pending",
    "created_at": "2025-12-15T18:00:00Z"
  }
}
```

### Backend Validation

**Controller**: `/opt/attendancedev/backend/app/Http/Controllers/Api/AttendanceCorrectionController.php`

**Validation Rules**:
```php
$validated = $request->validate([
    'attendance_id' => 'nullable|uuid|exists:attendances,id',
    'correction_date' => 'required|date|before_or_equal:today',
    'correction_type' => 'required|in:check_in,check_out,both,add_missing,delete',
    'requested_check_in' => 'nullable|date_format:H:i',
    'requested_check_out' => 'nullable|date_format:H:i',
    'reason' => 'required|string|min:10|max:1000',
    'supporting_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
]);
```

**Business Logic Validation**:

1. **Check for Duplicate Pending Correction**:
   ```php
   $existing = AttendanceCorrection::where('employee_id', $employee->id)
       ->where('correction_date', $validated['correction_date'])
       ->where('status', 'pending')
       ->first();

   if ($existing) {
       return error('You already have a pending correction request for this date', 422);
   }
   ```

2. **Validate Add Missing Type**:
   ```php
   if ($validated['correction_type'] === 'add_missing') {
       if ($attendance) {
           return error('Attendance record already exists for this date', 422);
       }
       if (empty($validated['requested_check_in'])) {
           return error('Check-in time is required for adding missing attendance', 422);
       }
   }
   ```

3. **Validate Other Types**:
   ```php
   if ($validated['correction_type'] !== 'add_missing') {
       if (!$attendance) {
           return error('No attendance record found for this date', 422);
       }
   }
   ```

4. **Handle File Upload**:
   ```php
   if ($request->hasFile('supporting_document')) {
       $documentPath = $request->file('supporting_document')->store(
           'correction-documents/' . $employee->id,
           'public'
       );
   }
   ```

### View Correction Requests

**API Endpoint**: `GET /api/v1/attendance-corrections`

**Filters**:
- `status`: pending, approved, rejected, cancelled, all
- `date_from`: Filter from date
- `date_to`: Filter to date
- `sort`: created_at, correction_date, status
- `direction`: asc, desc
- `per_page`: Items per page (default: 15)

**Access Control**:
- Employees see only their own corrections
- Admins see all corrections

**Response**:
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": "uuid",
        "employee": {
          "id": "uuid",
          "full_name": "John Doe",
          "employee_id": "NIP001"
        },
        "attendance": {
          "id": "uuid",
          "date": "2025-12-15",
          "check_in_time": "08:15:23",
          "check_out_time": "16:45:10"
        },
        "correction_date": "2025-12-15",
        "correction_type": "both",
        "requested_check_in": "08:00:00",
        "requested_check_out": "17:00:00",
        "reason": "I forgot to check in...",
        "status": "pending",
        "created_at": "2025-12-15T18:00:00Z"
      }
    ],
    "current_page": 1,
    "last_page": 2,
    "per_page": 15,
    "total": 25
  }
}
```

### Cancel Correction Request

**API Endpoint**: `POST /api/v1/attendance-corrections/{id}/cancel`

**Rules**:
- Can only cancel own requests
- Can only cancel if status is `pending`

**Response**:
```json
{
  "success": true,
  "message": "Correction request cancelled",
  "data": {
    "id": "uuid",
    "status": "cancelled"
  }
}
```

### Download Supporting Document

**API Endpoint**: `GET /api/v1/attendance-corrections/{id}/document`

**Access Control**:
- Employees can download their own documents
- Admins can download all documents

**Response**: File download (PDF/JPG/PNG)

---

## 6. Attendance Correction Approval (Admin)

### Overview

Administrators review correction requests, verify supporting documents, and approve or reject them. Approved corrections are automatically applied to attendance records.

### Frontend Components

#### Admin Desktop View
**File**: `/opt/attendancedev/frontend/src/pages/admin/corrections/desktop.tsx`

**Features**:
- Pending corrections counter
- Employee filter and search
- Date range filter
- Approve/reject modal with notes
- Document preview/download

#### Admin Mobile View
**File**: `/opt/attendancedev/frontend/src/pages/admin/corrections/mobile.tsx`

**Features**:
- Mobile-optimized approval cards
- Quick approve/reject buttons
- Touch-friendly document viewer

### Correction Statistics (Admin)

**API Endpoint**: `GET /api/v1/attendance-corrections/statistics`

**Response**:
```json
{
  "success": true,
  "data": {
    "pending": 12,
    "approved": 45,
    "rejected": 8,
    "total": 65,
    "this_month": 15
  }
}
```

### Approval Process

#### Step 1: View Correction Details

**API Endpoint**: `GET /api/v1/attendance-corrections/{id}`

**Response**:
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "employee": {
      "id": "uuid",
      "full_name": "John Doe",
      "employee_id": "NIP001",
      "department": "IT Department"
    },
    "attendance": {
      "id": "uuid",
      "date": "2025-12-15",
      "check_in_time": "08:15:23",
      "check_out_time": "16:45:10",
      "status": "late"
    },
    "correction_date": "2025-12-15",
    "correction_type": "both",
    "original_check_in": "08:15:23",
    "original_check_out": "16:45:10",
    "requested_check_in": "08:00:00",
    "requested_check_out": "17:00:00",
    "reason": "I had an urgent morning meeting with client. Attached is the meeting invitation.",
    "supporting_document": "correction-documents/employee-uuid/meeting-invite.pdf",
    "status": "pending",
    "created_at": "2025-12-15T18:00:00Z",
    "updated_at": "2025-12-15T18:00:00Z"
  }
}
```

#### Step 2: Review Evidence

**Admin Actions**:
1. Review the reason provided
2. Download and verify supporting document
3. Check original vs. requested times
4. Verify with employee's schedule/calendar

#### Step 3: Approve Correction

**API Endpoint**: `POST /api/v1/attendance-corrections/{id}/approve`

**Request**:
```json
{
  "notes": "Meeting invitation verified. Correction approved."
}
```

**Response**:
```json
{
  "success": true,
  "message": "Correction request approved",
  "data": {
    "id": "uuid",
    "status": "approved",
    "reviewed_by": 1,
    "reviewed_at": "2025-12-16T09:00:00Z",
    "review_notes": "Meeting invitation verified. Correction approved.",
    "reviewer": {
      "id": 1,
      "name": "Admin User"
    }
  }
}
```

### Backend Approval Logic

**Model Method**: `AttendanceCorrection::approve()`

**File**: `/opt/attendancedev/backend/app/Models/AttendanceCorrection.php`

```php
public function approve(int $reviewerId, ?string $notes = null): bool
{
    if (!$this->isPending()) {
        return false;
    }

    $this->update([
        'status' => self::STATUS_APPROVED,
        'reviewed_by' => $reviewerId,
        'reviewed_at' => now(),
        'review_notes' => $notes,
    ]);

    // Apply the correction to attendance
    $this->applyCorrection();

    return true;
}
```

### Apply Correction Logic

**Protected Method**: `AttendanceCorrection::applyCorrection()`

Handles different correction types:

#### 1. Delete Attendance

```php
if ($this->correction_type === self::TYPE_DELETE) {
    if ($this->attendance) {
        $this->attendance->delete(); // Soft delete
    }
    return;
}
```

#### 2. Add Missing Attendance

```php
if ($this->correction_type === self::TYPE_ADD_MISSING) {
    Attendance::create([
        'employee_id' => $this->employee_id,
        'date' => $this->correction_date,
        'check_in_time' => $this->requested_check_in,
        'check_out_time' => $this->requested_check_out,
        'status' => 'present',
        'check_in_notes' => 'Added via correction request #' . $this->id,
        'metadata' => ['correction_id' => $this->id],
    ]);
    return;
}
```

#### 3. Update Existing Attendance (check_in, check_out, both)

```php
if (!$this->attendance) {
    return;
}

$updateData = [];

if (in_array($this->correction_type, [self::TYPE_CHECK_IN, self::TYPE_BOTH])) {
    if ($this->requested_check_in) {
        $updateData['check_in_time'] = $this->correction_date->format('Y-m-d') . ' ' .
                                       $this->requested_check_in->format('H:i:s');
        $updateData['check_in_notes'] = ($this->attendance->check_in_notes ?? '') .
                                       "\nCorrected via request #" . $this->id;
    }
}

if (in_array($this->correction_type, [self::TYPE_CHECK_OUT, self::TYPE_BOTH])) {
    if ($this->requested_check_out) {
        $updateData['check_out_time'] = $this->correction_date->format('Y-m-d') . ' ' .
                                        $this->requested_check_out->format('H:i:s');
        $updateData['check_out_notes'] = ($this->attendance->check_out_notes ?? '') .
                                        "\nCorrected via request #" . $this->id;
    }
}

if (!empty($updateData)) {
    $this->attendance->update($updateData);

    // Recalculate total hours
    if ($this->attendance->check_in_time && $this->attendance->check_out_time) {
        $checkIn = Carbon::parse($this->attendance->check_in_time);
        $checkOut = Carbon::parse($this->attendance->check_out_time);
        $totalHours = $checkOut->diffInMinutes($checkIn) / 60;
        $this->attendance->update(['total_hours' => round($totalHours, 2)]);
    }
}
```

### Rejection Process

**API Endpoint**: `POST /api/v1/attendance-corrections/{id}/reject`

**Request**:
```json
{
  "notes": "The supporting document does not match the claimed time. Please provide proper evidence."
}
```

**Response**:
```json
{
  "success": true,
  "message": "Correction request rejected",
  "data": {
    "id": "uuid",
    "status": "rejected",
    "reviewed_by": 1,
    "reviewed_at": "2025-12-16T09:00:00Z",
    "review_notes": "The supporting document does not match...",
    "reviewer": {
      "id": 1,
      "name": "Admin User"
    }
  }
}
```

**Backend Logic**:
```php
public function reject(int $reviewerId, ?string $notes = null): bool
{
    if (!$this->isPending()) {
        return false;
    }

    $this->update([
        'status' => self::STATUS_REJECTED,
        'reviewed_by' => $reviewerId,
        'reviewed_at' => now(),
        'review_notes' => $notes,
    ]);

    return true;
}
```

**Note**: Rejection does NOT apply any changes to attendance records.

### Model Attributes

**Status Labels** (Indonesian):
```php
public function getStatusLabelAttribute(): string
{
    return match($this->status) {
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_APPROVED => 'Disetujui',
        self::STATUS_REJECTED => 'Ditolak',
        self::STATUS_CANCELLED => 'Dibatalkan',
        default => 'Unknown',
    };
}
```

**Correction Type Labels** (Indonesian):
```php
public function getCorrectionTypeLabelAttribute(): string
{
    return match($this->correction_type) {
        self::TYPE_CHECK_IN => 'Koreksi Check-In',
        self::TYPE_CHECK_OUT => 'Koreksi Check-Out',
        self::TYPE_BOTH => 'Koreksi Jam Masuk & Keluar',
        self::TYPE_ADD_MISSING => 'Tambah Absensi',
        self::TYPE_DELETE => 'Hapus Absensi',
        default => 'Unknown',
    };
}
```

---

## 7. Leave Calendar

### Overview

The leave calendar provides a visual overview of all approved leaves across the organization. It helps managers see team availability, identify conflicts, and plan resources.

### Frontend Component

**File**: `/opt/attendancedev/frontend/src/pages/admin/leave/calendar.tsx`

**Features**:
- Monthly calendar view
- Color-coded leave types
- Employee filter
- Department filter
- Leave type filter
- Tooltip with leave details
- Conflict detection (multiple people on same date)

**Leave Calendar Component**:

**File**: `/opt/attendancedev/frontend/src/components/leave/LeaveCalendar.tsx`

Uses calendar libraries (e.g., FullCalendar, react-big-calendar) with customization.

### API Endpoint

**Endpoint**: `GET /api/v1/leave/calendar`

**Parameters**:
- `month`: Month number (1-12)
- `year`: Year (e.g., 2025)

**Example Request**:
```
GET /api/v1/leave/calendar?month=12&year=2025
```

**Response**:
```json
{
  "success": true,
  "message": "Calendar leaves retrieved",
  "data": {
    "2025-12-20": [
      {
        "id": "uuid",
        "employee": {
          "id": "uuid",
          "full_name": "John Doe",
          "department": "IT"
        },
        "leave_type": {
          "name": "Cuti Tahunan",
          "code": "ANNUAL"
        },
        "start_date": "2025-12-20",
        "end_date": "2025-12-27",
        "days_requested": 5,
        "status": "approved"
      },
      {
        "id": "uuid",
        "employee": {
          "id": "uuid",
          "full_name": "Jane Smith",
          "department": "HR"
        },
        "leave_type": {
          "name": "Cuti Sakit",
          "code": "SICK"
        },
        "start_date": "2025-12-20",
        "end_date": "2025-12-20",
        "days_requested": 1,
        "status": "approved"
      }
    ],
    "2025-12-23": [...]
  }
}
```

**Data Structure**: Leaves grouped by date for easy rendering.

### Backend Logic

**Controller Method**: `LeaveApiController::calendar()`

```php
public function calendar(Request $request)
{
    $month = $request->get('month', now()->month);
    $year = $request->get('year', now()->year);

    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
    $endDate = Carbon::create($year, $month, 1)->endOfMonth();

    $leaves = Leave::where('status', 'approved')
        ->where(function($q) use ($startDate, $endDate) {
            $q->whereDate('start_date', '>=', $startDate)
              ->orWhereDate('end_date', '>=', $startDate);
            $q->whereDate('start_date', '<=', $endDate)
              ->orWhereDate('end_date', '<=', $endDate);
        })
        ->with('employee')
        ->get()
        ->groupBy(fn($leave) => $leave->start_date->format('Y-m-d'));

    return $this->apiResponse($leaves, 'Calendar leaves retrieved');
}
```

### Team Availability

**Use Case**: Check how many people are available on a specific date

**Calculation**:
```php
$date = '2025-12-20';
$totalEmployees = Employee::active()->count();

$employeesOnLeave = Leave::where('status', 'approved')
    ->whereDate('start_date', '<=', $date)
    ->whereDate('end_date', '>=', $date)
    ->distinct('employee_id')
    ->count('employee_id');

$availableEmployees = $totalEmployees - $employeesOnLeave;

// Availability percentage
$availabilityRate = ($availableEmployees / $totalEmployees) * 100;
```

### Conflict Detection

**Scenario**: Too many people requesting leave on same date

**Detection Logic**:
```php
$maxConcurrentLeaves = 3; // Business rule

$leavesOnDate = Leave::where('status', 'approved')
    ->whereDate('start_date', '<=', $date)
    ->whereDate('end_date', '>=', $date)
    ->count();

if ($leavesOnDate >= $maxConcurrentLeaves) {
    // Warning: Maximum concurrent leaves reached
    return ['conflict' => true, 'message' => 'Too many employees on leave'];
}
```

**Integration with Request Submission**:
When employee submits leave request, system can warn if date has conflicts:
```php
$conflictDates = [];
foreach ($requestedDates as $date) {
    if ($this->hasLeaveConflict($date)) {
        $conflictDates[] = $date;
    }
}

if (!empty($conflictDates)) {
    return [
        'warning' => true,
        'message' => 'Some dates have high leave volume',
        'conflict_dates' => $conflictDates,
    ];
}
```

---

## 8. Database Schema

### Overview

Complete database structure for leave management and attendance corrections.

### Entity Relationship Diagram (ERD)

```
┌─────────────────┐
│  leave_types    │
│─────────────────│
│ id (UUID) PK    │
│ name            │
│ code            │
│ description     │
│ default_days    │
│ requires_appr   │
│ is_paid         │
│ is_active       │
│ metadata        │
└─────────────────┘
         │
         │ 1:N
         ▼
┌─────────────────┐
│  leave_balances │
│─────────────────│
│ id (UUID) PK    │
│ employee_id FK  │──────┐
│ leave_type_id FK│      │
│ year            │      │
│ allocated_days  │      │
│ used_days       │      │
│ remaining_days  │      │
│ carried_forward │      │
│ is_active       │      │
│ metadata        │      │
└─────────────────┘      │
                         │
         ┌───────────────┘
         │
         │ N:1
         ▼
┌─────────────────┐
│   employees     │
│─────────────────│
│ id (UUID) PK    │
│ employee_id     │
│ full_name       │
│ employee_type   │
│ department      │
│ ...             │
└─────────────────┘
         │
         │ 1:N
         ▼
┌─────────────────┐
│     leaves      │
│─────────────────│
│ id (UUID) PK    │
│ employee_id FK  │
│ leave_type_id FK│
│ start_date      │
│ end_date        │
│ days_requested  │
│ reason          │
│ status          │
│ approved_by FK  │
│ approved_at     │
│ approval_notes  │
│ rejection_reason│
│ is_emergency    │
│ attachments     │
│ metadata        │
└─────────────────┘
         │
         │ 1:N (when approved)
         ▼
┌─────────────────┐
│   attendances   │
│─────────────────│
│ id (UUID) PK    │
│ employee_id FK  │
│ date            │
│ check_in_time   │
│ check_out_time  │
│ status (leave)  │
│ metadata {      │
│   leave_id      │
│   leave_type    │
│ }               │
└─────────────────┘
         │
         │ 1:N
         ▼
┌──────────────────────┐
│ attendance_corrections│
│──────────────────────│
│ id (UUID) PK         │
│ employee_id FK       │
│ attendance_id FK     │
│ correction_date      │
│ correction_type      │
│ original_check_in    │
│ original_check_out   │
│ requested_check_in   │
│ requested_check_out  │
│ reason               │
│ supporting_document  │
│ status               │
│ reviewed_by FK       │
│ reviewed_at          │
│ review_notes         │
└──────────────────────┘
```

### Table: `leave_types`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PRIMARY KEY | Leave type identifier |
| name | VARCHAR | NOT NULL | Leave type name |
| code | VARCHAR(20) | UNIQUE, NOT NULL | Leave type code (ANNUAL, SICK, etc.) |
| description | TEXT | NULLABLE | Description |
| default_days_per_year | INTEGER | DEFAULT 0 | Default allocation |
| requires_approval | BOOLEAN | DEFAULT true | Whether approval needed |
| is_paid | BOOLEAN | DEFAULT true | Whether paid leave |
| is_active | BOOLEAN | DEFAULT true | Whether active |
| metadata | JSON | NULLABLE | Additional configuration |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Update timestamp |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Indexes**:
- PRIMARY: `id`
- UNIQUE: `code`
- INDEX: `is_active`

### Table: `leaves`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PRIMARY KEY | Leave request identifier |
| employee_id | UUID | FOREIGN KEY, NOT NULL | Reference to employees |
| leave_type_id | UUID | FOREIGN KEY, NOT NULL | Reference to leave_types |
| start_date | DATE | NOT NULL | Leave start date |
| end_date | DATE | NOT NULL | Leave end date |
| days_requested | DECIMAL(5,2) | NOT NULL | Working days requested |
| reason | TEXT | NULLABLE | Reason for leave |
| status | ENUM | DEFAULT 'pending' | pending, approved, rejected, cancelled |
| applied_at | TIMESTAMP | NULLABLE | When request was submitted |
| approved_by | UUID | FOREIGN KEY, NULLABLE | Reference to employees (approver) |
| approved_at | TIMESTAMP | NULLABLE | When approved/rejected |
| approval_notes | TEXT | NULLABLE | Approver notes |
| rejection_reason | TEXT | NULLABLE | Rejection reason |
| is_emergency | BOOLEAN | DEFAULT false | Emergency leave flag |
| attachments | JSON | NULLABLE | Attachment paths |
| employee_notes | TEXT | NULLABLE | Additional employee notes |
| metadata | JSON | NULLABLE | Additional data (duration_type, emergency contact, etc.) |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Update timestamp |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Foreign Keys**:
- `employee_id` → `employees.id` ON DELETE CASCADE
- `leave_type_id` → `leave_types.id` ON DELETE CASCADE
- `approved_by` → `employees.id` ON DELETE SET NULL

**Indexes**:
- PRIMARY: `id`
- INDEX: `status`
- INDEX: `start_date`
- INDEX: `end_date`
- COMPOSITE INDEX: `(employee_id, status)`

### Table: `leave_balances`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PRIMARY KEY | Balance record identifier |
| employee_id | UUID | FOREIGN KEY, NOT NULL | Reference to employees |
| leave_type_id | UUID | FOREIGN KEY, NOT NULL | Reference to leave_types |
| year | YEAR | NOT NULL | Balance year |
| allocated_days | DECIMAL(5,2) | DEFAULT 0 | Total allocated days |
| used_days | DECIMAL(5,2) | DEFAULT 0 | Days used |
| remaining_days | DECIMAL(5,2) | DEFAULT 0 | Days remaining |
| carried_forward | DECIMAL(5,2) | DEFAULT 0 | Days from previous year |
| is_active | BOOLEAN | DEFAULT true | Whether balance is active |
| metadata | JSON | NULLABLE | Additional data (carry forward expiry, etc.) |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Update timestamp |

**Foreign Keys**:
- `employee_id` → `employees.id` ON DELETE CASCADE
- `leave_type_id` → `leave_types.id` ON DELETE CASCADE

**Unique Constraint**:
- UNIQUE: `(employee_id, leave_type_id, year)`

**Indexes**:
- PRIMARY: `id`
- INDEX: `year`

### Table: `attendance_corrections`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PRIMARY KEY | Correction request identifier |
| employee_id | UUID | FOREIGN KEY, NOT NULL | Reference to employees |
| attendance_id | UUID | FOREIGN KEY, NULLABLE | Reference to attendances (null for add_missing) |
| correction_date | DATE | NOT NULL | Date for which correction requested |
| correction_type | ENUM | NOT NULL | check_in, check_out, both, add_missing, delete |
| original_check_in | TIME | NULLABLE | Original check-in time |
| original_check_out | TIME | NULLABLE | Original check-out time |
| requested_check_in | TIME | NULLABLE | Requested check-in time |
| requested_check_out | TIME | NULLABLE | Requested check-out time |
| reason | TEXT | NOT NULL | Reason for correction |
| supporting_document | VARCHAR | NULLABLE | Document file path |
| status | ENUM | DEFAULT 'pending' | pending, approved, rejected, cancelled |
| reviewed_by | BIGINT | FOREIGN KEY, NULLABLE | Reference to users (reviewer) |
| reviewed_at | TIMESTAMP | NULLABLE | Review timestamp |
| review_notes | TEXT | NULLABLE | Reviewer notes |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Update timestamp |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Foreign Keys**:
- `employee_id` → `employees.id` ON DELETE CASCADE
- `attendance_id` → `attendances.id` ON DELETE CASCADE
- `reviewed_by` → `users.id` ON DELETE SET NULL

**Indexes**:
- PRIMARY: `id`
- COMPOSITE INDEX: `(employee_id, correction_date)`
- COMPOSITE INDEX: `(status, created_at)`

### Migration: Add Leave Status to Attendances

**File**: `/opt/attendancedev/backend/database/migrations/2025_12_11_add_leave_status_to_attendances.php`

**Change**:
Updates the attendance status constraint to include `'leave'` and `'holiday'`:

```sql
ALTER TABLE attendances
ADD CONSTRAINT attendances_status_check
CHECK (status IN ('present', 'absent', 'late', 'early_departure', 'incomplete', 'leave', 'holiday'))
```

This allows attendance records to be marked as `'leave'` when leave is approved.

---

## 9. API Reference

### Complete API Endpoint List

#### Leave Requests

| Method | Endpoint | Description | Auth | Permissions |
|--------|----------|-------------|------|-------------|
| GET | `/api/v1/leave-requests` | List leave requests | Yes | view_leave_own, view_leave_all |
| POST | `/api/v1/leave-requests` | Create leave request | Yes | create_leave_requests |
| GET | `/api/v1/leave-requests/pending` | Get pending approvals | Yes | approve_leave |
| GET | `/api/v1/leave-requests/{id}` | Get single request | Yes | view_leave_own, view_leave_all |
| GET | `/api/v1/leave-requests/{id}/affected-schedules` | Get affected schedules | Yes | approve_leave |
| POST | `/api/v1/leave-requests/{id}/approve` | Approve request | Yes | approve_leave |
| POST | `/api/v1/leave-requests/{id}/reject` | Reject request | Yes | approve_leave |
| POST | `/api/v1/leave-requests/{id}/cancel` | Cancel request | Yes | Own request only |

#### Leave Balance

| Method | Endpoint | Description | Auth | Permissions |
|--------|----------|-------------|------|-------------|
| GET | `/api/v1/leave/balance` | Get current user balance | Yes | Authenticated |
| GET | `/api/v1/leave/balance/{employeeId}` | Get employee balance | Yes | Admin or own |

#### Leave Utilities

| Method | Endpoint | Description | Auth | Permissions |
|--------|----------|-------------|------|-------------|
| GET | `/api/v1/leave/preview-working-days` | Preview working days calculation | Yes | Authenticated |
| GET | `/api/v1/leave/statistics` | Get leave statistics | Yes | Admin |
| GET | `/api/v1/leave/calendar` | Get leave calendar | Yes | Authenticated |

#### Attendance Corrections

| Method | Endpoint | Description | Auth | Permissions |
|--------|----------|-------------|------|-------------|
| GET | `/api/v1/attendance-corrections` | List corrections | Yes | Own or all (admin) |
| POST | `/api/v1/attendance-corrections` | Create correction | Yes | Authenticated |
| GET | `/api/v1/attendance-corrections/statistics` | Get statistics | Yes | Admin |
| GET | `/api/v1/attendance-corrections/{id}` | Get single correction | Yes | Own or admin |
| POST | `/api/v1/attendance-corrections/{id}/cancel` | Cancel correction | Yes | Own request only |
| POST | `/api/v1/attendance-corrections/{id}/approve` | Approve correction | Yes | Admin |
| POST | `/api/v1/attendance-corrections/{id}/reject` | Reject correction | Yes | Admin |
| GET | `/api/v1/attendance-corrections/{id}/document` | Download document | Yes | Own or admin |

### API Client (Frontend)

#### Leave API Client

**File**: `/opt/attendancedev/frontend/src/lib/api/leave.ts`

**Key Functions**:
```typescript
// Get leave requests with filters
getLeaveRequests(filters?: LeaveFilters): Promise<PaginatedResponse<LeaveRequest>>

// Get single leave request
getLeaveRequest(id: string): Promise<LeaveRequest>

// Create leave request
createLeaveRequest(data: LeaveRequestFormData): Promise<LeaveRequest>

// Cancel leave request
cancelLeaveRequest(id: string): Promise<LeaveRequest>

// Approve leave request (admin)
approveLeaveRequest(id: string, notes?: string): Promise<LeaveRequest>

// Reject leave request (admin)
rejectLeaveRequest(id: string, reason: string): Promise<LeaveRequest>

// Get leave balance
getLeaveBalance(): Promise<LeaveBalance>

// Get leave balance for employee (admin)
getLeaveBalanceByEmployee(employeeId: string): Promise<LeaveBalance>

// Get leave statistics (admin)
getLeaveStatistics(): Promise<LeaveStatistics>

// Get pending approvals (admin)
getPendingApprovals(): Promise<LeaveRequest[]>

// Get affected teaching schedules
getAffectedSchedules(id: string): Promise<AffectedSchedulesResponse>

// Preview working days
previewWorkingDays(startDate: string, endDate: string): Promise<WorkingDaysPreview>
```

#### Attendance Correction API Client

**File**: `/opt/attendancedev/frontend/src/lib/api/attendance-corrections.ts`

**Key Functions**:
```typescript
// Get corrections with filters
getAttendanceCorrections(filters?: CorrectionFilters): Promise<PaginatedResponse<AttendanceCorrection>>

// Get statistics (admin)
getCorrectionStatistics(): Promise<CorrectionStatistics>

// Get single correction
getAttendanceCorrection(id: string): Promise<AttendanceCorrection>

// Create correction
createAttendanceCorrection(data: CreateCorrectionData): Promise<AttendanceCorrection>

// Cancel correction
cancelAttendanceCorrection(id: string): Promise<AttendanceCorrection>

// Approve correction (admin)
approveAttendanceCorrection(id: string, notes?: string): Promise<AttendanceCorrection>

// Reject correction (admin)
rejectAttendanceCorrection(id: string, notes: string): Promise<AttendanceCorrection>

// Get document download URL
getCorrectionDocumentUrl(id: string): string
```

### Type Definitions

#### Leave Types

**File**: `/opt/attendancedev/frontend/src/types/leave.ts`

```typescript
export type LeaveType = 'annual' | 'sick' | 'maternity' | 'paternity' |
                        'marriage' | 'bereavement' | 'unpaid' | 'special' | 'other';

export type LeaveStatus = 'pending' | 'approved' | 'rejected' | 'cancelled';

export type LeaveDurationType = 'full_day' | 'half_day_am' | 'half_day_pm';

export interface LeaveRequest {
  id: string;
  employee_id: string;
  leave_type_id: string;
  status: LeaveStatus;
  start_date: string;
  end_date: string;
  days_requested: number;
  reason: string;
  // ... more fields
}

export interface LeaveBalance {
  id: string;
  employee_id: string;
  year: number;
  annual_total: number;
  annual_used: number;
  annual_remaining: number;
  sick_total: number;
  sick_used: number;
  sick_remaining: number;
  // ... more fields
}
```

#### Correction Types

**File**: `/opt/attendancedev/frontend/src/lib/api/attendance-corrections.ts`

```typescript
export interface AttendanceCorrection {
  id: string;
  employee_id: string;
  attendance_id: string | null;
  correction_date: string;
  correction_type: 'check_in' | 'check_out' | 'both' | 'add_missing' | 'delete';
  original_check_in: string | null;
  original_check_out: string | null;
  requested_check_in: string | null;
  requested_check_out: string | null;
  reason: string;
  supporting_document: string | null;
  status: 'pending' | 'approved' | 'rejected' | 'cancelled';
  reviewed_by: number | null;
  reviewed_at: string | null;
  review_notes: string | null;
  // Relations
  employee?: {...};
  attendance?: {...};
  reviewer?: {...};
}

export interface CreateCorrectionData {
  attendance_id?: string;
  correction_date: string;
  correction_type: 'check_in' | 'check_out' | 'both' | 'add_missing' | 'delete';
  requested_check_in?: string;
  requested_check_out?: string;
  reason: string;
  supporting_document?: File;
}
```

---

## Summary

This documentation covers the complete **Phase 4: Leave Management & Attendance Corrections Flow** for the attendance system:

1. **Leave Types**: 8 configurable leave types with different policies
2. **Leave Request Flow**: Employee submission with validation, balance checking, and conflict detection
3. **Leave Approval Flow**: Admin/Kepala Sekolah approval with automatic balance deduction and attendance creation
4. **Leave Balance Management**: Allocation, usage tracking, carry-forward rules
5. **Attendance Correction Request**: 5 correction types with document support
6. **Attendance Correction Approval**: Admin review and automatic correction application
7. **Leave Calendar**: Visual team availability and conflict detection

### Key Features

- **Automatic Working Day Calculation**: Excludes weekends and holidays
- **Teaching Schedule Integration**: Automatically marks affected teaching schedules for teachers on leave
- **Balance Management**: Automatic deduction on approval, restoration on cancellation
- **IDOR Protection**: Users can only access their own requests (unless admin)
- **Document Support**: File upload for correction requests
- **Notification System**: Alerts for approvers and employees
- **Audit Trail**: Complete history with timestamps and reviewer information

### Files Reference

**Backend**:
- Migrations: `/opt/attendancedev/backend/database/migrations/2025_07_03_*` and `2025_12_09_*`
- Models: `/opt/attendancedev/backend/app/Models/Leave*.php`, `AttendanceCorrection.php`
- Controllers: `/opt/attendancedev/backend/app/Http/Controllers/Api/LeaveApiController.php`, `AttendanceCorrectionController.php`
- Services: `/opt/attendancedev/backend/app/Services/LeaveService.php`
- Seeders: `/opt/attendancedev/backend/database/seeders/LeaveTypeSeeder.php`
- Routes: `/opt/attendancedev/backend/routes/leave.php`, `/opt/attendancedev/backend/routes/api.php`

**Frontend**:
- API Clients: `/opt/attendancedev/frontend/src/lib/api/leave.ts`, `attendance-corrections.ts`
- Types: `/opt/attendancedev/frontend/src/types/leave.ts`
- Pages: `/opt/attendancedev/frontend/src/pages/employee/leave/*`, `corrections/*`, `/admin/leave/*`, `corrections/*`
- Components: `/opt/attendancedev/frontend/src/components/leave/*`

---

**Document Version**: 1.0
**Last Updated**: 2025-12-20
**Author**: AI Documentation Generator
**Project**: Attendance Management System
