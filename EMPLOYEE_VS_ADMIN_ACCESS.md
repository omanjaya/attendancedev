# 👤 Employee vs 👨‍💼 Admin Access Control

## ✅ Implementation Summary

### Changes Made

1. ✅ Export restricted to Admin only
2. ✅ Employee summary endpoint created (read-only)
3. ✅ Permission checks added
4. ✅ Routes separated by role

---

## 🔐 Access Matrix

| Feature | Employee | Admin |
|---------|----------|-------|
| View Own Summary | ✅ YES | ✅ YES |
| View All Reports | ❌ NO | ✅ YES |
| Export PDF/Excel | ❌ NO | ✅ YES |
| Analytics Dashboard | ❌ NO | ✅ YES |

---

## 📡 API Endpoints

### For EMPLOYEES (Any authenticated user)

#### Get My Attendance Summary

```http
GET /api/v1/reports/my-attendance-summary
Authorization: Bearer {employee_token}
```

**Query Parameters:**

- `start_date` (optional): Default = current month start
- `end_date` (optional): Default = today

**Response Example:**

```json
{
  "status": "success",
  "data": {
    "period": {
      "start": "2024-12-01",
      "end": "2024-12-04",
      "work_days": 3
    },
    "employee": {
      "id": 5,
      "name": "Mita Employee",
      "employee_code": "EMP005"
    },
    "statistics": {
      "total_records": 3,
      "present": 2,
      "late": 1,
      "absent": 0,
      "attendance_rate": 100.0,
      "avg_work_hours": 8.5
    },
    "recent_attendance": [
      {
        "date": "2024-12-04",
        "check_in": "08:00",
        "check_out": "17:00",
        "status": "present",
        "work_hours": 8.5,
        "late_duration": 0
      },
      {
        "date": "2024-12-03",
        "check_in": "08:15",
        "check_out": "17:00",
        "status": "late",
        "work_hours": 8.25,
        "late_duration": 15
      }
    ],
    "monthly_calendar": [
      {
        "date": "2024-12-01",
        "day": "Sun",
        "status": "present",
        "check_in": "08:00",
        "check_out": "17:00"
      }
    ]
  }
}
```

---

### For ADMIN Only (Requires `view_attendance_reports` permission)

#### Export Reports

```http
POST /api/v1/reports/generate
Authorization: Bearer {admin_token}
```

**Request Body:**

```json
{
  "type": "attendance",
  "format": "pdf",
  "start_date": "2024-11-01",
  "end_date": "2024-11-30",
  "filters": {
    "columns": ["employee_name", "date", "check_in", "check_out", "status"]
  }
}
```

**Response (Success):**

```json
{
  "status": "success",
  "data": {
    "download_url": "http://localhost:8000/storage/exports/attendance_20241204.pdf",
    "generated_sync": true
  }
}
```

**Response (Unauthorized - Employee trying to export):**

```json
{
  "status": "error",
  "message": "Unauthorized. Only administrators can export reports.",
  "code": 403
}
```

---

## 🧪 Testing

### Test as Employee

```bash
# Login as employee (mita@gmail.com)
curl -X GET http://localhost:8000/api/v1/reports/my-attendance-summary \
  -H "Authorization: Bearer {employee_token}"

# Expected: ✅ Success (200) - Returns personal summary
```

```bash
# Try to export (should fail)
curl -X POST http://localhost:8000/api/v1/reports/generate \
  -H "Authorization: Bearer {employee_token}" \
  -H "Content-Type: application/json" \
  -d '{"type":"attendance","format":"pdf","start_date":"2024-12-01","end_date":"2024-12-04"}'

# Expected: ❌ Forbidden (403) - "Only administrators can export reports"
```

### Test as Admin

```bash
# Login as admin (admin@attendance.com)
curl -X GET http://localhost:8000/api/v1/reports/my-attendance-summary \
  -H "Authorization: Bearer {admin_token}"

# Expected: ✅ Success (200) - Returns admin's personal summary
```

```bash
# Export report
curl -X POST http://localhost:8000/api/v1/reports/generate \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{"type":"attendance","format":"pdf","start_date":"2024-12-01","end_date":"2024-12-04"}'

# Expected: ✅ Success (201) - Returns download URL
```

---

## 🎨 Frontend Integration

### Employee Summary Page (Example)

```typescript
// frontend/src/pages/employee/my-attendance.tsx

import { useQuery } from '@tanstack/react-query';
import apiClient from '@/lib/api/client';

export function MyAttendancePage() {
  const { data, isLoading } = useQuery({
    queryKey: ['my-attendance-summary'],
    queryFn: async () => {
      const response = await apiClient.get('/reports/my-attendance-summary');
      return response.data.data;
    },
  });

  if (isLoading) return <div>Loading...</div>;

  return (
    <div>
      <h1>My Attendance Summary</h1>
      
      <div className="stats">
        <div>Present: {data.statistics.present}</div>
        <div>Late: {data.statistics.late}</div>
        <div>Rate: {data.statistics.attendance_rate}%</div>
      </div>

      <div className="calendar">
        {data.monthly_calendar.map(day => (
          <div key={day.date} className={`day ${day.status}`}>
            <span>{day.day}</span>
            <span>{day.check_in} - {day.check_out}</span>
          </div>
        ))}
      </div>

      <div className="recent">
        <h2>Recent Attendance (Last 7 Days)</h2>
        {data.recent_attendance.map(att => (
          <div key={att.date}>
            {att.date}: {att.check_in} - {att.check_out} ({att.status})
          </div>
        ))}
      </div>
    </div>
  );
}
```

### Admin Export Page

```typescript
// frontend/src/pages/admin/reports/index.tsx

// Only show export buttons if user has permission
const canExport = user?.permissions?.includes('view_attendance_reports');

return (
  <div>
    {canExport ? (
      <>
        <Button onClick={handleExportPDF}>Export PDF</Button>
        <Button onClick={handleExportExcel}>Export Excel</Button>
      </>
    ) : (
      <p>You don't have permission to export reports</p>
    )}
  </div>
);
```

---

## 🔒 Security Flow

```
Employee Request Export
        ↓
Backend Check: user.can('view_attendance_reports')
        ↓
    FALSE (Employee)
        ↓
Return 403 Forbidden
"Only administrators can export reports"


Admin Request Export
        ↓
Backend Check: user.can('view_attendance_reports')
        ↓
    TRUE (Admin/HR)
        ↓
Rate Limit Check (3/min)
        ↓
Generate Report
        ↓
Return Download URL
```

---

## 📊 Data Visibility

### Employee View

- ✅ Own attendance records only
- ✅ Personal statistics
- ✅ Recent 7 days
- ✅ Monthly calendar view
- ❌ Cannot see other employees
- ❌ Cannot export data

### Admin View

- ✅ All employees
- ✅ All reports
- ✅ Analytics
- ✅ Export PDF/Excel
- ✅ Rate limiting: 3 exports/min
- ✅ Cached for 5 minutes

---

## 📝 Notes

### Permission Required for Export

- Permission name: `view_attendance_reports`
- Assigned to: Admin, HR roles
- NOT assigned to: Employee role

### Employee Summary Features

- Read-only view
- No export button
- No sensitive data from other employees
- Optimized queries (WHERE employee_id = current_user)

### Route Protection

- `/api/v1/reports/my-attendance-summary` - All users ✅
- `/api/v1/reports/generate` - Admin only ✅
- `/api/v1/reports/generated` - Admin only ✅
- `/api/v1/reports/attendance/monthly` - Admin only ✅

---

## ✅ Checklist

- [x] Export restricted to admin
- [x] Permission check in controller
- [x] Employee summary endpoint created
- [x] Routes separated by comments
- [x] API tested
- [ ] Frontend implementation (TODO)
- [ ] Employee UI page (TODO)

---

**Implementation Date**: December 4, 2025
**Status**: ✅ Backend Ready
**Next**: Create employee summary UI page
