# 📊 Missing Features Dashboard

> Quick reference untuk fitur yang perlu diimplementasi

---

## 🎯 Priority Overview

```
HIGH PRIORITY (Week 1-2)
┌─────────────────────────────────────────────────────────────┐
│  📥 Excel Import           │  📧 Email Notifications       │
│  ├── Employees (P0)        │  ├── Leave Approved/Rejected  │
│  ├── Subjects              │  ├── Welcome + Password       │
│  ├── Positions             │  └── Attendance Reminder      │
│  ├── Departments           │                                │
│  └── Classrooms            │                                │
└─────────────────────────────────────────────────────────────┘

MEDIUM PRIORITY (Week 3-4)  
┌─────────────────────────────────────────────────────────────┐
│  📱 PWA + Offline Mode     │  ✏️ Attendance Correction      │
│  ├── manifest.json         │  ├── Request form             │
│  ├── Service Worker        │  ├── Approval workflow        │
│  ├── Install Prompt        │  └── Apply correction         │
│  └── Offline Queue         │                                │
└─────────────────────────────────────────────────────────────┘

LOWER PRIORITY (Week 5-6)
┌─────────────────────────────────────────────────────────────┐
│  ⏰ Overtime Management    │  📢 Announcement System        │
│  ├── Request overtime      │  ├── Create announcement      │
│  ├── Approval workflow     │  ├── Target audience          │
│  ├── Payroll integration   │  └── Acknowledgment           │
│  └── Reports               │                                │
└─────────────────────────────────────────────────────────────┘
```

---

## 📁 File Structure

```
NEW FILES TO CREATE:
════════════════════

Backend (Laravel)
─────────────────
app/
├── Imports/
│   ├── EmployeesImport.php      ← Excel processing
│   ├── SubjectsImport.php
│   ├── PositionsImport.php
│   ├── DepartmentsImport.php
│   └── ClassroomsImport.php
├── Http/Controllers/Api/
│   ├── ImportController.php     ← Import endpoints
│   ├── CorrectionApiController.php
│   ├── OvertimeApiController.php
│   └── AnnouncementApiController.php
├── Mail/
│   ├── LeaveApprovedMail.php    ← Email classes
│   ├── LeaveRejectedMail.php
│   ├── WelcomeEmployeeMail.php
│   └── AttendanceReminderMail.php
└── Models/
    ├── AttendanceCorrection.php
    ├── OvertimeRequest.php
    └── Announcement.php

database/migrations/
├── xxxx_create_attendance_corrections_table.php
├── xxxx_create_overtime_requests_table.php
└── xxxx_create_announcements_table.php

resources/views/emails/
├── layouts/base.blade.php
├── leave/approved.blade.php
├── leave/rejected.blade.php
├── auth/welcome.blade.php
└── attendance/reminder.blade.php

Frontend (React)
────────────────
src/
├── components/
│   ├── shared/
│   │   └── ExcelImportDialog.tsx    ← Reusable import
│   ├── pwa/
│   │   ├── InstallPrompt.tsx        ← PWA install
│   │   ├── UpdatePrompt.tsx
│   │   └── OfflineIndicator.tsx
│   ├── offline/
│   │   └── SyncStatus.tsx
│   └── announcements/
│       ├── AnnouncementBanner.tsx
│       └── AnnouncementCard.tsx
├── lib/
│   ├── api/
│   │   ├── imports.ts
│   │   ├── corrections.ts
│   │   ├── overtime.ts
│   │   └── announcements.ts
│   └── offline/
│       ├── db.ts                    ← IndexedDB
│       └── queue.ts                 ← Sync queue
├── hooks/
│   ├── useExcelImport.ts
│   ├── useServiceWorker.ts
│   └── useOfflineAttendance.ts
└── pages/
    ├── employee/attendance/
    │   └── request-correction.tsx
    └── admin/
        ├── attendance/corrections.tsx
        ├── overtime/index.tsx
        └── announcements/index.tsx

public/
├── manifest.json                    ← PWA manifest
├── offline.html                     ← Offline fallback
└── icons/
    ├── icon-192x192.png
    └── icon-512x512.png
```

---

## 📅 Sprint Timeline

```
WEEK 1 (Dec 9-13)
════════════════
Mon │ ExcelImportDialog component
Tue │ Backend employee import API
Wed │ Employee import integration
Thu │ Master data import (Subjects, Positions)
Fri │ Master data import (Depts, Classrooms)

WEEK 2 (Dec 16-20)
════════════════
Mon │ Email templates setup
Tue │ Leave approval emails
Wed │ Welcome + Password reset emails
Thu │ Email testing + queue setup
Fri │ Buffer / bug fixes

WEEK 3 (Dec 23-27)
════════════════
Mon │ PWA manifest + vite config
Tue │ Install prompt + offline indicator
Wed │ IndexedDB setup
Thu │ Offline attendance queue
Fri │ Sync endpoint + testing

WEEK 4 (Dec 30 - Jan 3)
════════════════
Mon │ Offline sync refinement
Tue │ Attendance correction backend
Wed │ Attendance correction frontend
Thu │ Integration testing
Fri │ Buffer / bug fixes

WEEK 5 (Jan 6-10)
════════════════
Mon │ Overtime backend
Tue │ Overtime frontend
Wed │ Overtime payroll integration
Thu │ Overtime testing
Fri │ Buffer

WEEK 6 (Jan 13-17)
════════════════
Mon │ Announcement backend
Tue │ Announcement frontend
Wed │ Dashboard integration
Thu │ Final testing + polish
Fri │ Deployment
```

---

## 🔗 Quick Links

| Document | Path |
|----------|------|
| Full Implementation Plan | `.gemini/artifacts/IMPLEMENTATION_PLAN_MISSING_FEATURES.md` |
| Workflow Commands | `.agent/workflows/implement-missing-features.md` |
| Architecture Doc | `docs/ARCHITECTURE.md` |
| API Routes | `backend/routes/api.php` |

---

## ✅ Checklist

### Phase 1: Import & Email

- [ ] Install `maatwebsite/excel`
- [ ] Create ExcelImportDialog component
- [ ] Create EmployeesImport class
- [ ] Add import button to employees page
- [ ] Create email templates
- [ ] Integrate email in NotificationService

### Phase 2: PWA & Offline

- [ ] Install vite-plugin-pwa
- [ ] Create manifest.json
- [ ] Create InstallPrompt component
- [ ] Setup IndexedDB
- [ ] Implement offline attendance queue
- [ ] Create sync endpoint

### Phase 3: Correction & Overtime

- [ ] Create attendance_corrections migration
- [ ] Create CorrectionApiController
- [ ] Create overtime_requests migration
- [ ] Create OvertimeApiController
- [ ] Integrate with payroll

### Phase 4: Announcements

- [ ] Create announcements migration
- [ ] Create AnnouncementApiController
- [ ] Create dashboard banner component
- [ ] Create admin management page

---

## 🚀 Ready to Start?

Run workflow:

```
/implement-missing-features
```

Or start with specific phase:

```bash
# Phase 1: Excel Import
cd /home/omanjaya/Project/attendancedev/backend
docker compose exec backend composer require maatwebsite/excel
```
