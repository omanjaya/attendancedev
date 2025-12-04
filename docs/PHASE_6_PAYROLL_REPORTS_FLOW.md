# PHASE 6: PAYROLL & REPORTS FLOW

**Status**: ⚠️ PARTIALLY INTEGRATED (Critical Issues)
**Last Updated**: 2025-12-03
**Prerequisites**:
- [Phase 1 - Authentication](PHASE_1_AUTHENTICATION_FLOW.md)
- [Phase 2 - Attendance](PHASE_2_ATTENDANCE_FLOW.md)

---

## 📋 Overview

Phase ini mencakup:
1. **Payroll Calculation** - Menghitung gaji berdasarkan attendance
2. **Reports & Analytics** - Dashboard, statistik, dan export
3. **Report Generation** - PDF/Excel export

**⚠️ WARNING**: Ada beberapa critical issues di phase ini yang perlu attention.

---

## 💰 1. PAYROLL CALCULATION FLOW

### 1.1 Overview

**Status**: ✅ FULLY INTEGRATED

Payroll system menghitung gaji berdasarkan:
- Base salary (monthly/hourly)
- Attendance data (working hours)
- Overtime hours
- Deductions (late penalties, absent penalties, tax, insurance)
- Allowances (configurable)

### 1.2 Payroll Calculation Process

```
┌──────────────────────────────────────────────────────────────┐
│ 1. ADMIN CREATES PAYROLL PERIOD                              │
│    POST /api/v1/payroll/periods                              │
│    Payload:                                                  │
│    {                                                         │
│      "period_name": "December 2025",                         │
│      "start_date": "2025-12-01",                             │
│      "end_date": "2025-12-31",                               │
│      "status": "draft"                                       │
│    }                                                         │
└────────────────────┬─────────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────────┐
│ 2. FETCH ATTENDANCE DATA FOR PERIOD                          │
│    SQL:                                                      │
│      SELECT                                                  │
│        employee_id,                                          │
│        SUM(total_hours) as total_hours,                      │
│        SUM(JSON_EXTRACT(metadata,                            │
│            '$.overtime_hours')) as overtime_hours,           │
│        COUNT(CASE WHEN status = 'present' THEN 1 END)        │
│            as present_days,                                  │
│        COUNT(CASE WHEN status = 'late' THEN 1 END)           │
│            as late_days,                                     │
│        COUNT(CASE WHEN status = 'absent' THEN 1 END)         │
│            as absent_days                                    │
│      FROM attendances                                        │
│      WHERE date BETWEEN ? AND ?                              │
│      GROUP BY employee_id                                    │
└────────────────────┬─────────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────────┐
│ 3. FOR EACH EMPLOYEE: CALCULATE PAYROLL                      │
│    Service: PayrollCalculationService                        │
└────────────────────┬─────────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────────┐
│ 4. CALCULATE BASE PAY                                        │
│    if (employee.salary_type === 'monthly') {                 │
│        basePay = employee.salary_amount;                     │
│    } else {                                                  │
│        basePay = totalHours * employee.hourly_rate;          │
│    }                                                         │
│                                                              │
│    Example:                                                  │
│    - Monthly: Rp 5,000,000                                   │
│    - Hourly: 168 hours * Rp 30,000 = Rp 5,040,000           │
└────────────────────┬─────────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────────┐
│ 5. CALCULATE DEDUCTIONS                                      │
│    From .env configuration:                                  │
│                                                              │
│    $deductions = [                                           │
│        'late_penalty' =>                                     │
│            $lateDays * env('PAYROLL_LATE_DEDUCTION', 50000), │
│        'absent_penalty' =>                                   │
│            $absentDays * env('PAYROLL_ABSENT_DEDUCTION', 200000),│
│        'tax' => $this->calculateTax($basePay),               │
│        'insurance' =>                                        │
│            $basePay * env('PAYROLL_INSURANCE_RATE', 0.02)    │
│    ];                                                        │
│                                                              │
│    Example:                                                  │
│    - Late penalty: 2 days * Rp 50,000 = Rp 100,000          │
│    - Absent penalty: 1 day * Rp 200,000 = Rp 200,000        │
│    - Tax: Rp 250,000 (progressive brackets)                  │
│    - Insurance: Rp 5,000,000 * 2% = Rp 100,000              │
│    Total deductions: Rp 650,000                              │
└────────────────────┬─────────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────────┐
│ 6. CALCULATE TAX (PROGRESSIVE BRACKETS)                      │
│    Brackets from .env:                                       │
│    [                                                         │
│        ['limit' => 5000000, 'rate' => 0.05],                 │
│        ['limit' => 10000000, 'rate' => 0.10],                │
│        ['limit' => PHP_INT_MAX, 'rate' => 0.15]              │
│    ]                                                         │
│                                                              │
│    For income Rp 5,000,000:                                  │
│    Tax = 5,000,000 * 5% = Rp 250,000                         │
│                                                              │
│    For income Rp 7,000,000:                                  │
│    Tax = (5,000,000 * 5%) + (2,000,000 * 10%)               │
│        = 250,000 + 200,000 = Rp 450,000                      │
└────────────────────┬─────────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────────┐
│ 7. CALCULATE OVERTIME PAY                                    │
│    $hourlyRate = employee.hourly_rate ??                     │
│                  (employee.salary_amount / 160);             │
│                                                              │
│    $overtimePay = $overtimeHours *                           │
│                   $hourlyRate *                              │
│                   env('PAYROLL_OVERTIME_MULTIPLIER', 1.5);   │
│                                                              │
│    Example:                                                  │
│    Overtime: 10 hours                                        │
│    Hourly rate: Rp 31,250 (5,000,000 / 160)                 │
│    Multiplier: 1.5x                                          │
│    Overtime pay: 10 * 31,250 * 1.5 = Rp 468,750             │
└────────────────────┬─────────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────────┐
│ 8. CALCULATE NET PAY                                         │
│    $netPay = $basePay + $overtimePay - array_sum($deductions);│
│                                                              │
│    Example:                                                  │
│    Base: Rp 5,000,000                                        │
│    Overtime: Rp 468,750                                      │
│    Deductions: -Rp 650,000                                   │
│    Net Pay: Rp 4,818,750                                     │
└────────────────────┬─────────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────────┐
│ 9. STORE PAYROLL RECORD                                      │
│    SQL:                                                      │
│      INSERT INTO payroll_records (                           │
│        id, employee_id, period_id, base_salary,              │
│        overtime_pay, total_deductions, net_pay,              │
│        total_hours, overtime_hours,                          │
│        present_days, late_days, absent_days,                 │
│        breakdown, created_at                                 │
│      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)     │
└────────────────────┬─────────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────────┐
│ 10. RETURN PAYROLL SUMMARY                                   │
│     {                                                        │
│       "employee": { ... },                                   │
│       "period": "December 2025",                             │
│       "base_salary": 5000000,                                │
│       "overtime_pay": 468750,                                │
│       "gross_pay": 5468750,                                  │
│       "deductions": {                                        │
│         "late_penalty": 100000,                              │
│         "absent_penalty": 200000,                            │
│         "tax": 250000,                                       │
│         "insurance": 100000                                  │
│       },                                                     │
│       "total_deductions": 650000,                            │
│       "net_pay": 4818750,                                    │
│       "attendance_summary": {                                │
│         "total_hours": 168,                                  │
│         "overtime_hours": 10,                                │
│         "present_days": 20,                                  │
│         "late_days": 2,                                      │
│         "absent_days": 1                                     │
│       }                                                      │
│     }                                                        │
└──────────────────────────────────────────────────────────────┘
```

### 1.3 Payroll Configuration (.env)

```env
# Base deductions
PAYROLL_LATE_DEDUCTION=50000
PAYROLL_ABSENT_DEDUCTION=200000
PAYROLL_OVERTIME_MULTIPLIER=1.5
PAYROLL_INSURANCE_RATE=0.02

# Tax brackets (progressive)
PAYROLL_TAX_BRACKET_1_LIMIT=5000000
PAYROLL_TAX_BRACKET_1_RATE=0.05
PAYROLL_TAX_BRACKET_2_LIMIT=10000000
PAYROLL_TAX_BRACKET_2_RATE=0.10
PAYROLL_TAX_BRACKET_3_RATE=0.15

# Allowances (optional)
PAYROLL_TRANSPORT_ALLOWANCE=500000
PAYROLL_MEAL_ALLOWANCE=300000
```

---

## 📊 2. REPORTS & ANALYTICS

### 2.1 Reports Summary (✅ Working)

**Available Reports**:

1. **Monthly Attendance Report**: `GET /api/v1/reports/attendance/monthly`
2. **Department Statistics**: `GET /api/v1/reports/departments`
3. **Leave Statistics**: `GET /api/v1/reports/leave`
4. **Attendance Summary**: `GET /api/v1/reports/summary`
5. **Weekly Trend**: `GET /api/v1/reports/weekly-trend`

All these endpoints **fully working** dengan real database queries.

### 2.2 Report Examples

#### Monthly Attendance Report

**SQL**:
```sql
SELECT
    e.full_name,
    e.employee_code,
    COUNT(DISTINCT a.date) as total_days,
    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
    SUM(a.total_hours) as total_hours
FROM employees e
LEFT JOIN attendances a ON e.id = a.employee_id
WHERE a.date BETWEEN ? AND ?
GROUP BY e.id
ORDER BY e.full_name
```

**Response**:
```json
{
  "period": {
    "start_date": "2025-12-01",
    "end_date": "2025-12-31"
  },
  "data": [
    {
      "employee_name": "John Doe",
      "employee_code": "EMP001",
      "total_days": 22,
      "present": 18,
      "late": 3,
      "absent": 1,
      "total_hours": 176.5,
      "attendance_rate": "95.5%"
    }
  ]
}
```

#### Department Statistics

**SQL**:
```sql
SELECT
    l.name as location_name,
    COUNT(DISTINCT e.id) as total_employees,
    COUNT(DISTINCT CASE
        WHEN a.date = CURDATE() AND a.status = 'present'
        THEN e.id
    END) as present_today,
    ROUND(AVG(CASE
        WHEN a.status IN ('present', 'late')
        THEN 1 ELSE 0
    END) * 100, 2) as attendance_rate
FROM locations l
LEFT JOIN employees e ON l.id = e.location_id
LEFT JOIN attendances a ON e.id = a.employee_id
WHERE a.date BETWEEN ? AND ?
GROUP BY l.id
```

---

## ⚠️ KNOWN ISSUES & GAPS

### Integration Status: ⚠️ PARTIALLY INTEGRATED (3 Critical Issues)

**Payroll Calculation**: ✅ 100% Working
**Reports Endpoints**: ✅ 100% Working
**Dashboard Aggregation**: ❌ MISSING ENDPOINT
**Report Generation**: ❌ PLACEHOLDER IMPLEMENTATION

---

### ⚠️ ISSUE 1: Dashboard Endpoint Missing (HIGH PRIORITY - CRITICAL)

**Status**: ❌ **ENDPOINT NOT FOUND**

**Location**: Frontend expects `/api/v1/reports/dashboard` or `/api/v1/dashboard`

**Problem**:
Frontend dashboard API client memanggil endpoint yang **tidak terdaftar** di backend routes. Saat API call fails, frontend fallback ke mock data.

**Frontend Code**: `frontend/src/lib/api/dashboard.ts`

```typescript
export async function getDashboardData() {
  try {
    const response = await apiClient.get('/reports/dashboard');
    return response.data;
  } catch (error) {
    console.warn('Dashboard API failed, using mock data', error);
    return getMockDashboardData(); // ← FALLBACK TO MOCK!
  }
}

function getMockDashboardData() {
  return {
    total_employees: 160,        // ← HARDCODED!
    present_today: 128,          // ← HARDCODED!
    late_today: 12,              // ← HARDCODED!
    absent_today: 20,            // ← HARDCODED!
    on_leave_today: 5,           // ← HARDCODED!
    attendance_rate: 87.5,       // ← HARDCODED!
    // ... more mock data
  };
}
```

**Backend Reality**:
```bash
# Check routes
php artisan route:list | grep dashboard
# RESULT: NO MATCHES FOUND ❌
```

**Impact**:
- ❌ **CRITICAL** - Dashboard menampilkan data palsu ke user
- ❌ Admin tidak bisa lihat real-time statistics
- ❌ Decision making based on wrong data
- ❌ User bingung kenapa angka tidak match dengan reality

**Where the Endpoint SHOULD Be**:
- File: `backend/routes/api.php` - **NOT REGISTERED**
- Controller: Should be `DashboardController` or `ReportsApiController::dashboard` - **DOESN'T EXIST**

**Solution (Recommended)**:

**Option 1**: Create new dashboard endpoint
```php
// routes/api.php
Route::middleware(['auth:sanctum', 'permission:view_attendance_reports'])
    ->get('/reports/dashboard', [ReportsApiController::class, 'dashboard']);

// ReportsApiController.php
public function dashboard(Request $request)
{
    $today = now()->format('Y-m-d');

    return $this->apiResponse([
        'total_employees' => Employee::active()->count(),
        'present_today' => Attendance::whereDate('date', $today)
            ->where('status', 'present')->count(),
        'late_today' => Attendance::whereDate('date', $today)
            ->where('status', 'late')->count(),
        'absent_today' => Employee::active()->count() -
            Attendance::whereDate('date', $today)->count(),
        'on_leave_today' => Leave::approved()
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)->count(),
        'pending_leaves' => Leave::where('status', 'pending')->count(),
        'attendance_rate' => $this->calculateAttendanceRate($today),
        'weekly_trend' => $this->getWeeklyTrend(),
    ]);
}
```

**Option 2**: Update frontend to aggregate from existing endpoints
```typescript
// Aggregate from multiple endpoints
const [employees, attendance, leaves] = await Promise.all([
  apiClient.get('/employees/count'),
  apiClient.get('/attendance/statistics'),
  apiClient.get('/leave-requests/statistics')
]);

return {
  total_employees: employees.data.total,
  present_today: attendance.data.present_today,
  // ... combine data
};
```

**Effort Estimate**:
- **Option 1**: 30-60 minutes (create endpoint)
- **Option 2**: 15-30 minutes (update frontend)
- **Recommended**: Option 1 (cleaner, better performance)

---

### ❌ ISSUE 2: Report Generation Placeholder (MEDIUM PRIORITY)

**Status**: ❌ **FAKE IMPLEMENTATION**

**Location**: `backend/app/Http/Controllers/Api/ReportsApiController.php:218-227`

**Problem**:
Method `generate()` hanya return fake report ID, **tidak generate PDF/Excel actual**.

**Code**:
```php
// Lines 218-227
public function generate(Request $request)
{
    // For now, return a placeholder - actual report generation would be implemented
    $report = [
        'id' => uniqid('report_'),        // ← FAKE ID!
        'status' => 'generating',         // ← FAKE STATUS!
        'type' => $request->report_type,
        'created_at' => now(),
        'message' => 'Report generation started'
    ];

    return $this->apiResponse($report, 'Report generation started');
}
```

**Impact**:
- ❌ User click "Generate Report" → Gets fake response
- ❌ No actual PDF/Excel file created
- ❌ Download link tidak ada
- ⚠️ User mengira report sedang di-generate, tapi sebenarnya tidak ada

**Backend APIs Available for Data**:
- ✅ `/reports/attendance/monthly` - Returns data
- ✅ `/reports/departments` - Returns data
- ✅ `/reports/leave` - Returns data

**What's Missing**: Export layer (PDF/Excel generation)

**Solution (Recommended)**:

Use **Maatwebsite/Excel** for XLSX export:

```bash
composer require maatwebsite/excel
```

```php
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceReportExport;

public function generate(Request $request)
{
    $reportType = $request->report_type; // 'attendance_monthly'
    $filters = $request->filters; // ['start_date' => '2025-12-01', ...]

    // Fetch data
    $data = $this->getReportData($reportType, $filters);

    // Generate Excel
    $filename = "{$reportType}_" . now()->format('Ymd_His') . ".xlsx";
    $path = Excel::store(
        new AttendanceReportExport($data),
        "exports/{$filename}",
        'public'
    );

    // Store report record
    $report = Report::create([
        'id' => Str::uuid(),
        'type' => $reportType,
        'filename' => $filename,
        'file_path' => "storage/exports/{$filename}",
        'status' => 'completed',
        'filters' => $filters,
        'generated_by' => Auth::id(),
        'created_at' => now()
    ]);

    return $this->apiResponse([
        'report' => $report,
        'download_url' => url("storage/exports/{$filename}"),
        'expires_at' => now()->addDays(7)
    ], 'Report generated successfully');
}
```

**Effort Estimate**:
- **Time**: 2-4 hours
- **Complexity**: Medium (perlu install package + create export classes)
- **Priority**: Medium (nice to have, bukan blocker)

---

### ❌ ISSUE 3: Generated Reports List Always Empty (LOW PRIORITY)

**Status**: ❌ **ALWAYS RETURNS EMPTY ARRAY**

**Location**: `backend/app/Http/Controllers/Api/ReportsApiController.php:244`

**Problem**:
Method `generatedReports()` return empty array karena tidak ada database storage.

**Code**:
```php
// Line 244
public function generatedReports()
{
    return $this->apiResponse([], 'Generated reports retrieved');
    // ← Always empty!
}
```

**Why**:
- Tidak ada table `reports` untuk store generated reports
- `generate()` method tidak save anything ke database
- History reports tidak tracked

**Impact**:
- ⚠️ LOW - User tidak bisa lihat history reports
- ⚠️ User harus re-generate setiap kali perlu report

**Solution**:

Create `reports` table:
```sql
CREATE TABLE reports (
    id CHAR(36) PRIMARY KEY,
    type VARCHAR(100) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('generating', 'completed', 'failed') DEFAULT 'completed',
    filters JSON NULL,
    generated_by CHAR(36) NOT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (generated_by) REFERENCES users(id),
    INDEX idx_generated_by (generated_by),
    INDEX idx_created_at (created_at)
);
```

Update `generatedReports()`:
```php
public function generatedReports()
{
    $reports = Report::where('generated_by', Auth::id())
        ->where('expires_at', '>', now())
        ->latest()
        ->take(20)
        ->get();

    return $this->apiResponse($reports, 'Generated reports retrieved');
}
```

**Effort Estimate**:
- **Time**: 1-2 hours (create migration + update methods)
- **Priority**: Low (bonus feature)

---

### ⚠️ ISSUE 4: Report Templates Hardcoded (VERY LOW PRIORITY)

**Status**: ⚠️ **HARDCODED ARRAY**

**Location**: `backend/app/Http/Controllers/Api/ReportsApiController.php:230-238`

**Problem**:
Template list adalah hardcoded array, bukan dari database.

**Code**:
```php
// Lines 230-238
public function templates()
{
    $templates = [
        ['id' => 1, 'name' => 'Monthly Attendance'],
        ['id' => 2, 'name' => 'Department Statistics'],
        ['id' => 3, 'name' => 'Leave Summary'],
        // ... hardcoded
    ];

    return $this->apiResponse($templates, 'Report templates retrieved');
}
```

**Impact**:
- ⚠️ VERY LOW - Templates jarang berubah
- Templates are configuration-like data
- Hardcoded acceptable untuk use case ini

**Recommendation**:
- **No action needed** - This is acceptable
- Templates are essentially configuration
- If need dynamic: move to config file, not database

---

### What's Working Perfectly:

✅ **Payroll Calculation**
- Base salary calculation correct
- Overtime pay formula accurate
- Progressive tax brackets working
- Deductions (late, absent, insurance) applied correctly
- Net pay calculation accurate
- Database storage complete

✅ **Report Endpoints (Data Retrieval)**
- `/reports/summary` - ✅ Working
- `/reports/attendance/monthly` - ✅ Working
- `/reports/departments` - ✅ Working
- `/reports/leave` - ✅ Working
- `/reports/weekly-trend` - ✅ Working

✅ **Payroll Configuration**
- Environment variables for flexibility
- Tax brackets configurable
- Deduction rates adjustable
- Overtime multiplier customizable

---

### Summary

| Component | Status | Priority | Notes |
|-----------|--------|----------|-------|
| Payroll Calculation | ✅ 100% | - | Fully working |
| Report Data Endpoints | ✅ 100% | - | All working |
| **Dashboard Endpoint** | ❌ 0% | **HIGH** | **CRITICAL - Must fix** |
| **Report Generation** | ❌ 0% | MEDIUM | Placeholder only |
| Generated Reports List | ❌ 0% | LOW | Always empty |
| Report Templates | ⚠️ 90% | VERY LOW | Hardcoded (acceptable) |

**Overall Phase 6 Score**: 65% Complete

**Critical Action Required**:
1. **MUST FIX**: Create dashboard endpoint (HIGH priority)
2. **SHOULD FIX**: Implement report generation (MEDIUM priority)
3. **NICE TO HAVE**: Store generated reports (LOW priority)

---

## 📚 REFERENCES

### Backend Files
- **PayrollApiController**: `backend/app/Http/Controllers/Api/PayrollApiController.php`
- **ReportsApiController**: `backend/app/Http/Controllers/Api/ReportsApiController.php`
  - ⚠️ Line 218-227: `generate()` - PLACEHOLDER
  - ⚠️ Line 244: `generatedReports()` - EMPTY
  - Lines 15-50: `summary()` - ✅ Working
  - Lines 52-80: `monthlyAttendance()` - ✅ Working
- **PayrollCalculationService**: `backend/app/Services/PayrollCalculationService.php`
- **Routes**: `backend/routes/api.php`

### Frontend Files
- **Dashboard API**: `frontend/src/lib/api/dashboard.ts` - ⚠️ FALLBACK TO MOCK
- **Dashboard Page**: `frontend/src/pages/admin/dashboard.tsx`
- **Reports Page**: `frontend/src/pages/admin/reports/`

---

**Phase 6 Complete (with Critical Issues)** ⚠️
**Next**: [Phase 7 - Real-time Features](PHASE_7_REALTIME_FEATURES_FLOW.md)
