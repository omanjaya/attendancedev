# Phase 6: Reports & Analytics Flow

**Attendance Management System - Comprehensive Reporting Documentation**

---

## Table of Contents

1. [Dashboard Analytics](#1-dashboard-analytics)
2. [Attendance Reports](#2-attendance-reports)
3. [Employee Reports](#3-employee-reports)
4. [Teaching Reports (Guru)](#4-teaching-reports-guru)
5. [Leave Reports](#5-leave-reports)
6. [Export Functionality](#6-export-functionality)
7. [Report Scheduling](#7-report-scheduling)
8. [Audit Logs](#8-audit-logs)

---

## 1. Dashboard Analytics

### 1.1 Admin Dashboard Widgets

**Frontend:** `/frontend/src/pages/admin/dashboard/`
- `desktop.tsx` - Desktop dashboard with comprehensive widgets
- `mobile.tsx` - Mobile-optimized dashboard
- `index.tsx` - Device-responsive dashboard wrapper

**API Endpoints:**
```
GET /api/v1/reports/dashboard
GET /dashboard/data
GET /dashboard/widget/{widget}
GET /dashboard/chart-data?period={week|month|quarter}
```

**Backend Controllers:**
- `/backend/app/Http/Controllers/DashboardController.php`
  - `index()` - Main dashboard view
  - `getData()` - Real-time dashboard data
  - `getWidgetData()` - Specific widget data
  - `getChartData()` - Chart data for different periods
  - `getAttendanceDashboard()` - Attendance dashboard for Vue components
  - `getAttendanceStats()` - Attendance statistics

**Services:**
- `/backend/app/Services/DashboardService.php`
  - `getDashboardData()` - Get comprehensive dashboard based on role
  - `getSuperAdminDashboard()` - Complete school overview
  - `getAdminDashboard()` - Daily operations focus
  - `getKepalaSekolahDashboard()` - Strategic overview
  - `getGuruDashboard()` - Personal teaching focus
  - `getRealtimeAttendanceStatus()` - Real-time attendance metrics
  - `getWeeklyAttendanceChart()` - Weekly attendance visualization
  - `getMonthlyAttendanceChart()` - Monthly attendance trends
  - `getQuarterlyAttendanceChart()` - Quarterly performance

- `/backend/app/Services/Reports/DashboardReportService.php`
  - `getDashboard()` - Dashboard statistics aggregation
  - `getSummary()` - Summary for date range
  - `getRecentActivity()` - Recent check-ins/outs and leave requests
  - `getAttendanceTrends()` - Last 7 days trends
  - `getStatsByEmployeeType()` - Guru vs Pegawai breakdown

**Dashboard Widgets:**

1. **Real-time Status Widget**
   - Total employees
   - Checked in today
   - Attendance rate (%)
   - Late arrivals
   - Early departures
   - Incomplete checkouts
   - Present/absent employees list
   - Security alerts count
   - Holidays this month

2. **School Overview Widget**
   - Total teachers
   - Total staff
   - Active employees
   - On leave today
   - New hires this month

3. **Attendance Trends Widget**
   - Daily trends (30/60/90 days)
   - Average attendance rate
   - Best attendance day
   - Worst attendance day

4. **Leave Management Widget**
   - Pending requests
   - Approved today
   - Emergency requests
   - Upcoming leaves (7 days)
   - Leave types usage chart

5. **System Health Widget**
   - Database connection status
   - Face recognition system status
   - Last backup status
   - Active sessions count
   - Face enrollment rate

6. **Teacher Status Widget**
   - Teacher attendance list
   - Teaching coverage rate
   - Substitute teacher needs

**Data Flow:**
```
User → Frontend Dashboard Page
  ↓
API Request: GET /dashboard/data
  ↓
DashboardController::getData()
  ↓
DashboardService::getDashboardData($user)
  ↓
Role-based Dashboard Method:
  - getSuperAdminDashboard()
  - getAdminDashboard()
  - getKepalaSekolahDashboard()
  - getGuruDashboard()
  ↓
Aggregate Data from:
  - Attendance Model
  - Employee Model
  - Leave Model
  - Holiday Model
  - AuditLog Model
  ↓
Return JSON Response with:
  - summary statistics
  - recent_activity
  - attendance_trends
  - system_status
  ↓
Frontend renders widgets with real-time updates
```

### 1.2 Employee Dashboard Widgets

**Frontend:** `/frontend/src/pages/employee/dashboard/`

**Widgets:**

1. **Personal Status Widget**
   - Today's check-in/out status
   - Total working hours
   - Monthly attendance rate
   - Punctuality score

2. **Teaching Schedule Widget (Guru)**
   - Classes today
   - Total periods
   - Completed periods
   - Next class
   - Schedule conflicts

3. **Teaching Summary Widget**
   - Total classes per week
   - Classes today
   - Upcoming classes
   - Completed classes this week
   - Weekly/monthly hours

4. **Leave Balance Widget**
   - Annual leave balance
   - Sick leave balance
   - Personal leave balance
   - Used days
   - Remaining days

5. **Performance Summary Widget**
   - Attendance rate
   - Punctuality score
   - Performance rating
   - Weekly attendance chart
   - Weekly punctuality chart

### 1.3 Real-time Statistics

**Refresh Mechanisms:**
- Auto-refresh every 30 seconds (configurable)
- Manual refresh button
- WebSocket support (future enhancement)

**Cached Data:**
- Dashboard data cached for 15 minutes (900 seconds)
- Widget data cached per widget type
- Cache invalidation on data updates

**Statistics Calculated:**
- Attendance rate: `(present + late) / total_employees * 100`
- Punctuality score: `on_time / total_days * 100`
- Teaching coverage: `present_teachers / total_teachers * 100`
- Face enrollment rate: `enrolled / total_employees * 100`

### 1.4 Charts & Visualizations

**Chart Libraries:**
- Frontend: Chart.js / Recharts
- Backend: Data preparation in Services

**Chart Types:**

1. **Weekly Attendance Chart**
   - Line chart: Present vs Late
   - 7 days data
   - Color-coded statuses

2. **Monthly Attendance Chart**
   - Bar chart: Present vs Absent
   - Last 4 months
   - Comparison trends

3. **Quarterly Attendance Chart**
   - Area chart: Attendance rate %
   - Q1, Q2, Q3, Q4
   - Year-over-year comparison

4. **Department Breakdown**
   - Pie/Doughnut chart
   - Attendance by department
   - Interactive filtering

5. **Leave Distribution**
   - Stacked bar chart
   - Leave types usage
   - Monthly breakdown

---

## 2. Attendance Reports

### 2.1 Daily Attendance Report

**API Endpoint:**
```
GET /api/v1/reports/monthly-recap?month={month}&year={year}&department={dept}&employee_type={guru|pegawai}
```

**Backend:**
- **Controller:** `ReportsApiController::monthlyRecap()`
- **Service:** `AttendanceReportService::getMonthlyRecap()`

**Report Fields:**
- Employee code, name, department
- **H** = Hadir (Present on time)
- **T** = Terlambat (Late)
- **A** = Alpha (Absent without notice)
- **I** = Izin (Permission)
- **S** = Sakit (Sick)
- **D** = Dinas (Official duty)
- **C** = Cuti (Leave/Vacation)
- Working days
- Attendance rate (%)

**Filters:**
- Month & Year
- Department
- Employee Type (Guru/Pegawai)

**Data Processing:**
```
AttendanceReportService::getMonthlyRecap()
  ↓
1. Calculate working days (exclude weekends & holidays)
  ↓
2. Get employees based on filters
  ↓
3. For each employee:
   - Get attendance records
   - Count: Hadir, Terlambat
   - Get approved leaves
   - Categorize leaves: I/S/D/C
   - Calculate Alpha (unaccounted days)
  ↓
4. Calculate totals and attendance rate
  ↓
5. Return structured data with legend
```

### 2.2 Monthly Attendance Summary

**Frontend:** `/frontend/src/pages/admin/reports/tabs/MonthlyRecapTab.tsx`

**Features:**
- Month/Year selector
- Department filter
- Employee type tabs (All/Guru/Pegawai)
- Export to Excel/PDF
- Printable format

**Data Display:**
- Table with employee rows
- A/I/S/D/C columns
- Color-coded statuses
- Attendance rate indicator
- Summary totals row

### 2.3 Late/Early Analysis

**Service Method:** `DashboardService::getRealtimeAttendanceStatus()`

**Metrics:**
- Late arrivals count
- Early departures count
- Average late duration
- Repeat offenders
- Department comparison

**Query:**
```sql
SELECT
  COUNT(*) as total,
  SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
  AVG(late_duration_minutes) as avg_late_duration
FROM attendances
WHERE date BETWEEN ? AND ?
GROUP BY employee_id
```

### 2.4 Absence Report

**Service:** `DashboardReportService::getStatsByEmployeeType()`

**Report Includes:**
- Absent employees list
- Absence reasons (Leave, Sick, Unknown)
- Absence patterns
- Department breakdown
- Monthly trends

### 2.5 Department/Location Breakdown

**API Endpoint:**
```
GET /api/v1/reports/departments?start_date={date}&end_date={date}
```

**Backend:**
- **Controller:** `ReportsApiController::departmentStats()`
- **Service:** `DepartmentReportService::getDepartmentStats()`

**Metrics:**
- Attendance by department
- Department-wise rates
- Location-based statistics
- Cross-department comparison

---

## 3. Employee Reports

### 3.1 Employee Attendance History

**API Endpoint:**
```
GET /api/v1/reports/my-attendance-summary?start_date={date}&end_date={date}
```

**Backend:**
- **Controller:** `ReportsApiController::myAttendanceSummary()`
- **Service:** `EmployeeReportService::getMyAttendanceSummary()`

**Report Sections:**

1. **Period Summary**
   - Start/End dates
   - Total work days
   - Present days
   - Late days
   - Absent days
   - Attendance rate

2. **Recent Attendance (Last 7 days)**
   - Date
   - Check-in time
   - Check-out time
   - Status
   - Work hours
   - Late duration

3. **Monthly Calendar View**
   - Calendar grid
   - Color-coded days
   - Hover tooltips with details

### 3.2 Individual Performance

**Service:** `DashboardService::getPersonalPerformance()`

**Metrics:**
- Attendance rate (monthly)
- Punctuality score
- Performance rating (Excellent/Good/Satisfactory/Needs Improvement/Poor)
- Weekly attendance trend
- Weekly punctuality trend
- Last evaluation date

**Rating Calculation:**
```php
$averageScore = ($attendanceRate + $punctualityScore) / 2;

if ($averageScore >= 95) return 'Excellent';
if ($averageScore >= 85) return 'Good';
if ($averageScore >= 75) return 'Satisfactory';
if ($averageScore >= 60) return 'Needs Improvement';
return 'Poor';
```

### 3.3 Leave Utilization

**Service:** `DashboardService::getLeaveBalance()`

**Report Fields:**
- Annual leave: allocated vs used
- Sick leave: allocated vs used
- Personal leave: allocated vs used
- Total used days
- Remaining days
- Leave history

### 3.4 Face Registration Status

**Service:** `DashboardService::getFaceEnrollmentRate()`

**Metrics:**
- Total active employees
- Employees with face data
- Enrollment percentage
- Pending enrollments
- Failed enrollments

**Query:**
```sql
SELECT
  COUNT(*) as total,
  SUM(CASE WHEN face_embedding IS NOT NULL THEN 1 ELSE 0 END) as enrolled
FROM employees
WHERE is_active = true
```

---

## 4. Teaching Reports (Guru)

### 4.1 Teaching Schedule Completion

**API Endpoint:**
```
GET /api/v1/reports/teaching-schedules?month={month}&year={year}&subject={subject}
```

**Backend:**
- **Controller:** `ReportsApiController::teachingScheduleReport()`
- **Service:** `TeachingScheduleReportService::getTeachingReport()`

**Report Fields:**
- Teacher name, code, department
- Sessions per week
- Hours per week
- Subjects taught (count & list)
- Classes taught (count & list)
- Scheduled sessions (in period)
- Sessions taught (actual)
- Sessions missed
- Attendance rate
- Attendance details (present/late/absent)

**Data Flow:**
```
getTeachingReport(month, year, subject)
  ↓
1. Get teachers with active teaching schedules
  ↓
2. For each teacher:
   - Get teaching schedules
   - Calculate sessions per week
   - Calculate hours per week
   - Get unique subjects & classes
   - Calculate scheduled sessions in period
   - Get attendance stats
   - Calculate taught/missed sessions
  ↓
3. Calculate summary totals
  ↓
4. Return structured report data
```

### 4.2 Class Attendance

**Service:** `TeachingScheduleReportService::getTeacherAttendanceStats()`

**Metrics:**
- Present days
- Late days
- Absent days
- Teaching coverage rate

### 4.3 Substitute Tracking

**Service:** `DashboardService::getSubstituteTeacherNeeds()`

**Report:**
- Teachers absent with scheduled classes
- Classes needing substitutes
- Period, subject, class details
- Reason (On Leave / Absent)

**Query Flow:**
```
1. Find absent teachers for today
2. Check if they have scheduled classes
3. Get class details (period, subject, class name)
4. Return list of classes needing substitutes
```

---

## 5. Leave Reports

### 5.1 Leave Balance Summary

**Service:** `DashboardService::getLeaveBalance()`

**Report:**
- Leave type allocations
- Used leave per type
- Remaining balance
- Year-to-date usage

### 5.2 Leave Usage Trends

**API Endpoint:**
```
GET /api/v1/reports/leave?year={year}
```

**Backend:**
- **Controller:** `ReportsApiController::leaveStats()`
- **Service:** `LeaveReportService::getLeaveStats()`

**Metrics:**
- Leave requests per month
- Approval rate
- Rejection rate
- Average leave duration
- Peak leave periods

**Query:**
```sql
SELECT
  leave_type_id,
  COUNT(*) as count,
  SUM(DATEDIFF(end_date, start_date) + 1) as total_days
FROM leaves
WHERE YEAR(start_date) = ? AND status = 'approved'
GROUP BY leave_type_id
```

### 5.3 Approval Statistics

**Service:** `DashboardService::getLeaveManagement()`

**Metrics:**
- Pending requests
- Approved today
- Rejected today
- Emergency requests (within 2 days)
- Average approval time

---

## 6. Export Functionality

### 6.1 Export to Excel

**Backend:**
- **Controller:** `ReportsApiController::generate()`
- **Service:** `ReportExportService::generate()`
- **Export Class:** `AttendanceReportExport` (Maatwebsite\Excel)

**Supported Reports:**
- Attendance (daily/monthly)
- Leave requests
- Payroll (if available)
- Employee data
- Teaching schedules

**Export Flow:**
```
User clicks Export button
  ↓
Frontend: POST /api/v1/reports/generate
Body: {
  type: 'attendance',
  format: 'excel',
  start_date: '2024-01-01',
  end_date: '2024-01-31',
  filters: { columns: [...] }
}
  ↓
ReportExportService::generate()
  ↓
1. Estimate row count
2. If > 1000 rows: generateAsync() (queue)
   Else: generateSync() (immediate)
  ↓
3. Fetch data based on report type
4. Generate Excel file with Maatwebsite\Excel
5. Store in storage/app/public/exports/
6. Save Report record to database
7. Cache result for 5 minutes
  ↓
Return download URL or pending status
  ↓
Frontend downloads file or shows "processing" status
```

**Smart Routing:**
- Small datasets (<1000 rows): Synchronous generation
- Large datasets (>1000 rows): Async queue job

### 6.2 Export to PDF

**Format:** Same as Excel, with `format: 'pdf'`
**Engine:** DomPDF (via Maatwebsite\Excel)

**Features:**
- Company header
- Date range
- Page numbers
- Print-optimized layout

### 6.3 Custom Date Ranges

**Filter Options:**
- Start date (required)
- End date (required, >= start_date)
- Department
- Employee type (Guru/Pegawai)
- Subject (for teaching reports)

**Validation:**
```php
$request->validate([
    'type' => 'required|in:attendance,leave,payroll,summary',
    'format' => 'required|in:pdf,excel',
    'start_date' => 'required|date',
    'end_date' => 'required|date|after_or_equal:start_date',
    'filters' => 'nullable|array',
]);
```

### 6.4 Filter Options

**Column Selection:**
```javascript
// Attendance columns
const columns = [
  'employee_name',
  'employee_code',
  'date',
  'check_in',
  'check_out',
  'status',
  'work_hours',
  'late_duration',
  'notes'
];

// Leave columns
const columns = [
  'employee_name',
  'leave_type',
  'start_date',
  'end_date',
  'duration',
  'reason',
  'status',
  'approved_by'
];
```

**Dynamic Column Headers:**
```php
$columnMap = [
    'employee_name' => 'Nama Karyawan',
    'employee_code' => 'NIK',
    'date' => 'Tanggal',
    'check_in' => 'Jam Masuk',
    // ... etc
];
```

---

## 7. Report Scheduling

**Note:** Report scheduling is partially implemented. Full automation requires cron job setup.

### 7.1 Automated Report Generation

**Planned Features:**
- Schedule report types (attendance, leave, payroll)
- Frequency (daily, weekly, monthly, quarterly)
- Recipients (email list)
- Custom filters

**Backend Methods:**
- `ReportsController::scheduleReport()` - Create schedule
- `ReportsController::getScheduledReports()` - List schedules

**Database Table:** `scheduled_reports` (to be created)

### 7.2 Email Delivery

**Planned Implementation:**
- Queue job for report generation
- Email notification with attachment
- Retry on failure
- Delivery confirmation

### 7.3 Report Templates

**API Endpoint:**
```
GET /api/v1/reports/templates
```

**Controller:** `ReportsApiController::templates()`

**Current Templates:**
1. Laporan Kehadiran Bulanan (Monthly Attendance)
2. Laporan Cuti (Leave Report)
3. Laporan Ringkasan (Summary Report)

**Template Structure:**
```php
[
    'id' => '1',
    'name' => 'Laporan Kehadiran Bulanan',
    'type' => 'attendance',
    'description' => 'Rekap kehadiran per bulan'
]
```

---

## 8. Audit Logs

### 8.1 User Activity Logs

**Database Table:** `audit_logs`

**Model:** `/backend/app/Models/AuditLog.php`

**Fields:**
- `id` (UUID)
- `user_id`
- `event_type` (created, updated, deleted, login, logout, etc.)
- `auditable_type` (model class)
- `auditable_id` (model ID)
- `old_values` (JSON)
- `new_values` (JSON)
- `url`
- `ip_address`
- `user_agent`
- `tags` (JSON array)
- `created_at`

**Event Types:**
- `login` / `logout` / `login_failed`
- `created` / `updated` / `deleted`
- `permission_changed` / `role_changed`
- `face_enrolled` / `face_verification_failed`

**Service:** `/backend/app/Services/Audit/AuditLogService.php`

**Controllers:**
- `/backend/app/Http/Controllers/AuditLogController.php`

### 8.2 Attendance Changes

**Auditable Events:**
- Check-in/Check-out
- Manual attendance corrections
- Status changes (present → late)
- Admin overrides

**Logging Method:**
```php
AuditLog::createLog(
    eventType: 'attendance_corrected',
    auditable: $attendance,
    oldValues: $attendance->getOriginal(),
    newValues: $attendance->getChanges(),
    user: auth()->user(),
    tags: ['attendance', 'correction']
);
```

**Traits:** `Auditable` trait for automatic logging

### 8.3 System Events

**Monitored Events:**
- Failed login attempts
- Security alerts
- Database connection issues
- Face recognition failures
- Backup completion
- Holiday imports
- System performance issues

**Service:** `/backend/app/Services/DashboardService.php`

**Methods:**
- `getSystemAlerts()` - Get recent alerts
- `getFailedCheckinAttempts()` - Face recognition failures
- `getExcessiveLateArrivals()` - Late arrival alerts
- `getLastBackupInfo()` - Backup status
- `getSystemPerformanceIssues()` - Performance monitoring

**Alert Types:**
- `critical` - Failed logins, high error rates
- `warning` - Excessive late arrivals, slow database
- `success` - Backup completed
- `info` - Holiday import, general notices

**Dashboard Display:**
```php
// Example alert structure
[
    'type' => 'critical',
    'icon' => 'info',
    'title' => '5 Gagal Check-in',
    'description' => 'Masalah pengenalan wajah • 10 menit lalu',
    'color' => 'red',
    'priority' => 1
]
```

**Priority Sorting:** Alerts sorted by priority (1 = highest)

**Retention:** Configurable cleanup via `SecurityCleanup` command

---

## Database Schema

### Reports-related Tables

**1. `reports` Table**
```sql
CREATE TABLE reports (
    id UUID PRIMARY KEY,
    type VARCHAR(50),
    format VARCHAR(10),
    filename VARCHAR(255),
    file_path TEXT,
    status VARCHAR(20),
    filters JSON,
    generated_by BIGINT UNSIGNED,
    expires_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**2. `audit_logs` Table**
```sql
CREATE TABLE audit_logs (
    id UUID PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    event_type VARCHAR(100),
    auditable_type VARCHAR(255),
    auditable_id VARCHAR(255),
    old_values JSON,
    new_values JSON,
    url TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    tags JSON,
    created_at TIMESTAMP
);
```

**Indexes:**
- `audit_logs_user_id_index`
- `audit_logs_event_type_index`
- `audit_logs_auditable_type_auditable_id_index`
- `audit_logs_created_at_index`

---

## API Routes Summary

### Reports Endpoints

```php
// Public dashboard
GET /dashboard

// API v1 - Reports
Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {

        // Reports
        Route::prefix('reports')->group(function () {
            // Basic reports
            GET /reports/data
            GET /reports/summary

            // Employee-only
            GET /reports/my-attendance-summary

            // Admin analytics
            GET /reports/attendance/monthly
            GET /reports/attendance/weekly
            GET /reports/departments
            GET /reports/leave

            // Monthly recap (A/I/S/D/C)
            GET /reports/monthly-recap

            // Teaching schedules
            GET /reports/teaching-schedules
            GET /reports/teaching-subjects

            // Export
            POST /reports/generate (rate-limited)
            GET /reports/templates
            GET /reports/generated
            GET /reports/generated/{id}
        });

        // Dashboard
        GET /dashboard/data
        GET /dashboard/widget/{widget}
        GET /dashboard/chart-data
        GET /dashboard/attendance-dashboard
        GET /dashboard/attendance-stats
    });
});

// Web routes
Route::middleware(['auth', 'verified', 'permission:view_reports'])->group(function () {
    Route::prefix('reports')->group(function () {
        GET /reports
        GET /reports/builder

        GET /reports/attendance
        GET /reports/leave
        GET /reports/employees
        GET /reports/payroll
        GET /reports/summary

        POST /reports/custom/generate
        POST /reports/schedule
        GET /reports/scheduled

        // Export
        GET /reports/attendance/export
        GET /reports/leave/export
        GET /reports/payroll/export
        GET /reports/employee/export
    });
});
```

---

## Frontend Component Structure

```
frontend/src/
├── pages/
│   ├── admin/
│   │   ├── dashboard/
│   │   │   ├── index.tsx (responsive wrapper)
│   │   │   ├── desktop.tsx (admin dashboard)
│   │   │   └── mobile.tsx (mobile dashboard)
│   │   └── reports/
│   │       ├── index.tsx (responsive wrapper)
│   │       ├── desktop.tsx (reports page)
│   │       ├── mobile.tsx (mobile reports)
│   │       ├── builder.tsx (report builder)
│   │       └── tabs/
│   │           ├── MonthlyRecapTab.tsx
│   │           ├── TeachingScheduleTab.tsx
│   │           └── ReportBuilderContent.tsx
│   └── employee/
│       ├── dashboard/
│       │   ├── index.tsx
│       │   ├── desktop.tsx
│       │   └── mobile.tsx
│       └── reports/
│           ├── index.tsx
│           ├── desktop.tsx
│           └── mobile.tsx
├── components/
│   ├── shared/
│   │   ├── PageLayout.tsx
│   │   ├── StatsGrid.tsx
│   │   ├── ContentCard.tsx
│   │   └── PageHeader.tsx
│   └── ui/ (shadcn/ui components)
├── lib/
│   └── api/
│       ├── dashboard.ts
│       ├── reports.ts
│       └── client.ts
└── types/
    ├── reports.ts
    └── index.ts
```

---

## Backend Service Architecture

```
backend/app/
├── Http/Controllers/
│   ├── DashboardController.php
│   ├── ReportsController.php
│   ├── PayrollReportController.php
│   ├── AuditLogController.php
│   └── Api/
│       └── ReportsApiController.php
├── Services/
│   ├── DashboardService.php
│   ├── ExportService.php
│   └── Reports/
│       ├── DashboardReportService.php
│       ├── AttendanceReportService.php
│       ├── EmployeeReportService.php
│       ├── LeaveReportService.php
│       ├── DepartmentReportService.php
│       ├── TeachingScheduleReportService.php
│       ├── ReportExportService.php
│       ├── ReportFilterService.php
│       ├── ReportDataService.php
│       ├── ReportSummaryService.php
│       ├── ReportAnalyticsService.php
│       └── ReportHelperService.php
├── Models/
│   ├── Report.php
│   └── AuditLog.php
├── Jobs/
│   └── GenerateReportJob.php
└── Exports/
    └── AttendanceReportExport.php
```

---

## Security & Performance

### Security Measures

1. **Role-based Access Control**
   - Admin-only export endpoints
   - User can only view own data
   - Permission checks: `view_reports`, `export_payroll_reports`

2. **Rate Limiting**
   ```php
   Route::post('/reports/generate')
       ->middleware('throttle:report-export');
   ```

3. **Input Validation**
   - Date range validation
   - Report type whitelisting
   - File format validation

4. **Audit Logging**
   - All report exports logged
   - User activity tracked
   - Sensitive field monitoring

### Performance Optimizations

1. **Caching**
   - Dashboard data: 15 minutes
   - Report results: 5 minutes
   - Chart data: per period

2. **Smart Export Routing**
   - < 1000 rows: Synchronous (immediate)
   - ≥ 1000 rows: Asynchronous (queue)

3. **Query Optimization**
   - Indexed columns: date, employee_id, status
   - Eager loading relationships
   - Selective column fetching

4. **Database Queries**
   - Use selectRaw for aggregations
   - Limit result sets
   - Pagination for large datasets

5. **File Management**
   - Auto-expire exported files (7 days)
   - Cleanup cron job
   - Storage quota monitoring

---

## Error Handling

### Frontend Error Handling

```typescript
try {
    const report = await generateReport(data);
    if (report.generated_async) {
        toast.info('Laporan sedang diproses...');
        // Poll for completion
        const completed = await waitForReportCompletion(report.id);
        toast.success('Laporan siap diunduh!');
    } else {
        toast.success('Laporan berhasil dibuat!');
    }
} catch (error) {
    toast.error('Gagal membuat laporan');
    console.error(error);
}
```

### Backend Error Handling

```php
try {
    $result = $this->exportService->generate($validated, ...);
    return $this->apiResponse($result, 'Report generated successfully');
} catch (\Exception $e) {
    Log::error('Report generation failed', [
        'user_id' => $request->user()->id,
        'error' => $e->getMessage()
    ]);
    return $this->errorResponse('Failed to generate report', 500);
}
```

---

## Future Enhancements

### Planned Features

1. **Advanced Analytics**
   - Predictive attendance modeling
   - Anomaly detection
   - Trend forecasting

2. **Automated Scheduling**
   - Cron-based report generation
   - Email distribution lists
   - Custom report templates

3. **Real-time Notifications**
   - WebSocket integration
   - Push notifications
   - SMS alerts

4. **Interactive Dashboards**
   - Drag-and-drop widgets
   - Custom dashboard builder
   - Widget marketplace

5. **AI-Powered Insights**
   - Attendance pattern analysis
   - Workforce optimization suggestions
   - Leave prediction

6. **Mobile App Integration**
   - Native dashboard widgets
   - Offline report viewing
   - Push report notifications

---

## Testing Recommendations

### Unit Tests

```php
// Test report generation
public function test_generates_attendance_report()
{
    $response = $this->postJson('/api/v1/reports/generate', [
        'type' => 'attendance',
        'format' => 'excel',
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-31',
    ]);

    $response->assertStatus(201)
             ->assertJsonStructure(['data' => ['download_url']]);
}

// Test dashboard data
public function test_fetches_dashboard_data()
{
    $response = $this->getJson('/dashboard/data');

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'data' => [
                     'stats',
                     'activities',
                     'schedule',
                     'system_status'
                 ]
             ]);
}
```

### Integration Tests

- Test export flow end-to-end
- Test async report generation
- Test cache invalidation
- Test permission enforcement

### Performance Tests

- Load test with 10,000+ records
- Concurrent export requests
- Dashboard refresh under load
- Database query performance

---

## Deployment Checklist

- [ ] Configure queue workers for async reports
- [ ] Set up cron job for cleanup
- [ ] Configure storage quotas
- [ ] Enable Redis cache (production)
- [ ] Set up monitoring alerts
- [ ] Configure backup retention
- [ ] Test export file permissions
- [ ] Verify rate limiting
- [ ] Check audit log retention
- [ ] Test email delivery (when implemented)

---

## Monitoring & Maintenance

### Key Metrics to Monitor

1. **Report Generation**
   - Generation time
   - Success/failure rate
   - Queue backlog

2. **Dashboard Performance**
   - Page load time
   - API response time
   - Cache hit rate

3. **Storage Usage**
   - Exported files size
   - Audit log growth
   - Database size

4. **User Activity**
   - Most generated reports
   - Peak usage times
   - Feature adoption

### Maintenance Tasks

- **Daily:** Clean expired reports
- **Weekly:** Review audit logs
- **Monthly:** Archive old data
- **Quarterly:** Performance review

---

## Conclusion

Phase 6 implements a comprehensive reporting and analytics system with:

- ✅ Role-based dashboard widgets
- ✅ Real-time attendance statistics
- ✅ Monthly recap with A/I/S/D/C breakdown
- ✅ Teaching schedule reports
- ✅ Excel/PDF export functionality
- ✅ Audit logging system
- ✅ Smart async processing
- ✅ Responsive design (desktop/mobile)

**Key Files:**
- Frontend: `/frontend/src/pages/admin/reports/`, `/frontend/src/lib/api/reports.ts`
- Backend: `/backend/app/Services/Reports/`, `/backend/app/Http/Controllers/Api/ReportsApiController.php`
- Database: `reports`, `audit_logs` tables

**Documentation Updated:** 2025-12-20
