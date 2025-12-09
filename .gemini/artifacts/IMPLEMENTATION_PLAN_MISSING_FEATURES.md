# 🚀 Implementation Plan: Missing Features - Attendance System

> **Created:** 2025-12-09  
> **Status:** 🟡 In Progress - Phase 1  
> **Estimated Total Duration:** 4-6 minggu  
> **Last Updated:** 2025-12-09 10:45 WIB

---

## 📋 Executive Summary

Plan ini mencakup implementasi fitur-fitur yang teridentifikasi kurang pada sistem absensi, berdasarkan analisis mendalam terhadap codebase. Implementasi dibagi dalam 4 fase berdasarkan prioritas dan dependency antar fitur.

---

## ✅ Completed

### Phase 1.1: Reusable Excel Import Component ✅

- [x] Created `ExcelImportDialog.tsx` component
- [x] Implemented drag & drop file upload  
- [x] Added file type validation (xlsx, xls, csv)
- [x] Created preview table for imported data
- [x] Added progress indicator
- [x] Handle error display per row
- [x] Created download template functionality
- [x] Added onFileImport for file-based API calls
- [x] Added onSuccess callback

### Phase 1.2: Employee Excel Import ✅

- [x] Installed `maatwebsite/excel` package
- [x] Created `EmployeesImport.php` class with flexible column matching
- [x] Created validation rules for employee data
- [x] Added duplicate detection (by email, employee_id)
- [x] Implemented upsert logic (update existing or create new)
- [x] Created `ImportController.php` with template generator
- [x] Added API endpoints for employees, subjects, positions, departments, classrooms
- [x] Added "Import Excel" button to `/admin/employees` page
- [x] Integrated ExcelImportDialog component with backend API
- [x] Frontend build successful

### API Endpoints Created

| Method | Endpoint | Status |
|--------|----------|--------|
| POST | `/api/v1/import/employees` | ✅ Ready |
| GET | `/api/v1/import/employees/template` | ✅ Ready |
| POST | `/api/v1/import/subjects` | ✅ Ready |
| POST | `/api/v1/import/positions` | ✅ Ready |
| POST | `/api/v1/import/departments` | ✅ Ready |
| POST | `/api/v1/import/classrooms` | ✅ Ready |

##### Tasks

- [ ] Create `ExcelImportDialog.tsx` component
- [ ] Implement drag & drop file upload
- [ ] Add file type validation (xlsx, xls, csv)
- [ ] Create preview table for imported data
- [ ] Add progress indicator
- [ ] Handle error display per row
- [ ] Create download template functionality

##### Files to Create

```
frontend/src/components/shared/
├── ExcelImportDialog.tsx    # Main dialog component
├── ExcelPreviewTable.tsx    # Preview of parsed data
└── hooks/
    └── useExcelImport.ts    # Custom hook for import logic
```

##### Component API

```typescript
interface ExcelImportDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description?: string;
  templateUrl?: string;           // URL to download template
  expectedColumns: ExcelColumn[]; // Column definitions
  onImport: (data: any[]) => Promise<ImportResult>;
  maxRows?: number;
}

interface ExcelColumn {
  key: string;
  label: string;
  required: boolean;
  type: 'string' | 'number' | 'date' | 'email' | 'boolean';
  validation?: (value: any) => string | null;
}

interface ImportResult {
  success: boolean;
  total: number;
  imported: number;
  updated: number;
  skipped: number;
  errors: ImportError[];
}
```

---

#### 1.2 Employee Excel Import

**Duration:** 2-3 hari  
**Owner:** Full-stack Developer

##### Backend Tasks

- [ ] Install/configure `maatwebsite/excel` package
- [ ] Create `EmployeesImport.php` class
- [ ] Create validation rules for employee data
- [ ] Add duplicate detection (by email, employee_id)
- [ ] Implement upsert logic (update existing or create new)
- [ ] Create Excel template generator
- [ ] Add API endpoint `POST /api/v1/employees/import`
- [ ] Add API endpoint `GET /api/v1/employees/import-template`

##### Backend Files

```
backend/
├── app/Imports/
│   └── EmployeesImport.php
├── app/Http/Controllers/Api/
│   └── ImportController.php
├── app/Http/Requests/
│   └── ImportEmployeesRequest.php
└── storage/app/templates/
    └── employee_import_template.xlsx
```

##### Employee Import Columns

| Column | Type | Required | Validation |
|--------|------|----------|------------|
| full_name | string | ✅ | max:255 |
| email | email | ✅ | unique:users,email |
| phone | string | ❌ | phone format |
| employee_id | string | ❌ | unique:employees |
| employee_type | string | ✅ | exists:employee_types,code |
| department | string | ❌ | exists:departments,code |
| position | string | ❌ | exists:positions,code |
| hire_date | date | ✅ | date format |
| birth_date | date | ❌ | date format |
| address | string | ❌ | max:500 |
| salary_type | string | ❌ | in:monthly,daily,hourly |

##### Frontend Tasks

- [ ] Add "Import Excel" button to `/admin/employees` page
- [ ] Integrate ExcelImportDialog component
- [ ] Connect to backend API
- [ ] Show import results
- [ ] Refresh employee list after import

##### Test Cases

- [ ] Import 10 employees successfully
- [ ] Import with duplicate email (should update)
- [ ] Import with invalid employee_type (should error)
- [ ] Import with missing required fields (should error)
- [ ] Import 100+ employees (performance test)

---

#### 1.3 Master Data Excel Import

**Duration:** 2 hari  
**Owner:** Full-stack Developer

##### Data Types to Support

1. **Subjects (Mata Pelajaran)**
2. **Positions (Jabatan)**
3. **Departments (Unit Kerja)**
4. **Classrooms (Kelas)**

##### Backend Tasks

- [ ] Create `SubjectsImport.php`
- [ ] Create `PositionsImport.php`
- [ ] Create `DepartmentsImport.php`
- [ ] Create `ClassroomsImport.php`
- [ ] Add API endpoints for each type
- [ ] Create templates for each type

##### Backend Files

```
backend/app/Imports/
├── SubjectsImport.php
├── PositionsImport.php
├── DepartmentsImport.php
└── ClassroomsImport.php
```

##### Frontend Tasks

- [ ] Add import button to `/admin/settings/employee-types` (Subjects, Positions, Depts)
- [ ] Add import button to `/admin/master-data` (Classrooms)
- [ ] Integrate ExcelImportDialog for each tab

##### Integration Points

| Page | Tab | API Endpoint |
|------|-----|--------------|
| `/admin/settings/employee-types` | Mata Pelajaran | `POST /api/v1/subjects/import` |
| `/admin/settings/employee-types` | Jabatan | `POST /api/v1/positions/import` |
| `/admin/settings/employee-types` | Unit Kerja | `POST /api/v1/departments/import` |
| `/admin/master-data` | Kelas | `POST /api/v1/classrooms/import` |

---

#### 1.4 Email Notification System

**Duration:** 3-4 hari  
**Owner:** Backend Developer

##### Backend Tasks

- [ ] Create Mail configuration in `.env`
- [ ] Create base Mailable classes
- [ ] Create email templates (Blade views)
- [ ] Implement email channel in `NotificationService.php`
- [ ] Create notification queue jobs
- [ ] Add email verification for new users

##### Mail Classes to Create

```
backend/app/Mail/
├── LeaveApprovedMail.php
├── LeaveRejectedMail.php
├── PasswordResetMail.php
├── WelcomeEmployeeMail.php
├── AttendanceReminderMail.php
├── PayslipReadyMail.php
└── SecurityAlertMail.php
```

##### Email Templates

```
backend/resources/views/emails/
├── layouts/
│   └── base.blade.php          # Base email template
├── leave/
│   ├── approved.blade.php
│   └── rejected.blade.php
├── auth/
│   ├── welcome.blade.php
│   └── password-reset.blade.php
├── attendance/
│   └── reminder.blade.php
└── payroll/
    └── payslip-ready.blade.php
```

##### Email Template Design

- Company logo header
- Responsive design (mobile-friendly)
- Dark mode support
- Indonesian language
- Unsubscribe link
- Social media links in footer

##### Integration Points

| Event | Email | Recipient |
|-------|-------|-----------|
| Leave Approved | Leave Approved | Employee |
| Leave Rejected | Leave Rejected | Employee |
| New Employee Created | Welcome + Temp Password | Employee |
| Password Reset Requested | Reset Link | Employee |
| Payroll Processed | Payslip Ready | Employee |
| Check-in Reminder (30min late) | Attendance Reminder | Employee |
| Failed Login (3x) | Security Alert | Employee + Admin |

##### Configuration Required

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@school.edu
MAIL_FROM_NAME="${APP_NAME}"
```

---

### Phase 2: Core Features - PWA & Offline (Week 3-4)

#### 2.1 PWA Foundation

**Duration:** 2-3 hari  
**Owner:** Frontend Developer

##### Tasks

- [ ] Install `vite-plugin-pwa`
- [ ] Create `manifest.json` for React frontend
- [ ] Configure PWA plugin in `vite.config.ts`
- [ ] Create app icons (192x192, 512x512)
- [ ] Add meta tags for iOS support
- [ ] Create install prompt component
- [ ] Test on mobile devices

##### Files to Create/Modify

```
frontend/
├── public/
│   ├── manifest.json           # PWA manifest
│   ├── icons/
│   │   ├── icon-192x192.png
│   │   ├── icon-512x512.png
│   │   └── maskable-icon.png
│   └── offline.html            # Offline fallback page
├── src/
│   ├── components/
│   │   └── pwa/
│   │       ├── InstallPrompt.tsx
│   │       ├── UpdatePrompt.tsx
│   │       └── OfflineIndicator.tsx
│   └── hooks/
│       ├── useServiceWorker.ts
│       └── usePWAInstall.ts
└── vite.config.ts              # PWA plugin config
```

##### PWA Manifest

```json
{
  "name": "Attendance Management System",
  "short_name": "Attendance",
  "description": "Sistem Absensi dengan Face Recognition",
  "theme_color": "#0F172A",
  "background_color": "#0F172A",
  "display": "standalone",
  "orientation": "portrait",
  "scope": "/",
  "start_url": "/",
  "icons": [
    {
      "src": "/icons/icon-192x192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/icons/icon-512x512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "any maskable"
    }
  ]
}
```

---

#### 2.2 Offline Attendance Queue

**Duration:** 3-4 hari  
**Owner:** Full-stack Developer

##### Concept

Ketika user offline, attendance data disimpan di IndexedDB dan akan di-sync saat kembali online.

##### Frontend Tasks

- [ ] Create IndexedDB wrapper (`offlineDB.ts`)
- [ ] Create `useOfflineAttendance` hook
- [ ] Modify attendance check-in/out flow
- [ ] Add offline indicator to UI
- [ ] Create sync queue manager
- [ ] Add conflict resolution logic

##### Backend Tasks

- [ ] Create sync endpoint `POST /api/v1/attendance/sync`
- [ ] Handle duplicate/conflict resolution
- [ ] Add sync status response
- [ ] Create batch processing for multiple entries

##### Files to Create

```
frontend/src/
├── lib/
│   └── offline/
│       ├── db.ts               # IndexedDB wrapper
│       ├── queue.ts            # Offline queue manager
│       └── sync.ts             # Sync logic
├── hooks/
│   ├── useOfflineMode.ts
│   └── useOfflineAttendance.ts
└── components/
    └── offline/
        ├── OfflineIndicator.tsx
        └── SyncStatus.tsx
```

##### Offline Queue Schema (IndexedDB)

```typescript
interface OfflineAttendance {
  id: string;                    // Generated UUID
  type: 'check-in' | 'check-out';
  timestamp: string;
  latitude?: number;
  longitude?: number;
  accuracy?: number;
  face_verified: boolean;
  face_image?: string;           // Base64 image
  status: 'pending' | 'synced' | 'failed';
  sync_attempts: number;
  created_at: string;
}
```

##### Sync Flow

```
1. User checks in (offline)
   ↓
2. Save to IndexedDB with status='pending'
   ↓
3. Show "Saved offline" toast
   ↓
4. Network comes back online
   ↓
5. Background sync triggered
   ↓
6. POST /api/v1/attendance/sync with all pending entries
   ↓
7. Server processes and returns results
   ↓
8. Update IndexedDB status to 'synced' or 'failed'
   ↓
9. Notify user of sync result
```

---

### Phase 3: Enhancement Features (Week 5-6)

#### 3.1 Attendance Correction Request

**Duration:** 3-4 hari  
**Owner:** Full-stack Developer

##### Database Migration

```sql
CREATE TABLE attendance_corrections (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  employee_id UUID NOT NULL REFERENCES employees(id),
  attendance_id UUID REFERENCES attendances(id),
  date DATE NOT NULL,
  correction_type VARCHAR(50) NOT NULL, -- 'check_in', 'check_out', 'both', 'add_missing'
  original_check_in TIME,
  original_check_out TIME,
  requested_check_in TIME,
  requested_check_out TIME,
  reason TEXT NOT NULL,
  supporting_document VARCHAR(255),     -- File path
  status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'approved', 'rejected'
  reviewed_by UUID REFERENCES users(id),
  reviewed_at TIMESTAMP,
  review_notes TEXT,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);
```

##### Backend Tasks

- [ ] Create AttendanceCorrection model
- [ ] Create CorrectionApiController
- [ ] Add validation rules
- [ ] Create approval workflow
- [ ] Apply correction to attendance record on approval
- [ ] Send notification on status change

##### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/corrections` | List corrections (admin) |
| GET | `/api/v1/corrections/my` | My correction requests |
| POST | `/api/v1/corrections` | Create correction request |
| PUT | `/api/v1/corrections/{id}/approve` | Approve correction |
| PUT | `/api/v1/corrections/{id}/reject` | Reject correction |

##### Frontend Tasks

- [ ] Create correction request form (employee side)
- [ ] Add to attendance history page
- [ ] Create admin correction review page
- [ ] Add correction status badges
- [ ] Implement file upload for supporting document

##### Frontend Pages

```
frontend/src/pages/
├── employee/
│   └── attendance/
│       └── request-correction.tsx
└── admin/
    └── attendance/
        └── corrections.tsx
```

---

#### 3.2 Overtime (Lembur) Management

**Duration:** 5-6 hari  
**Owner:** Full-stack Developer

##### Database Migration

```sql
CREATE TABLE overtime_requests (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  employee_id UUID NOT NULL REFERENCES employees(id),
  date DATE NOT NULL,
  planned_start_time TIME NOT NULL,
  planned_end_time TIME NOT NULL,
  planned_hours DECIMAL(4,2),
  actual_start_time TIME,
  actual_end_time TIME,
  actual_hours DECIMAL(4,2),
  reason TEXT NOT NULL,
  project_name VARCHAR(255),
  status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'approved', 'rejected', 'completed', 'cancelled'
  approved_by UUID REFERENCES users(id),
  approved_at TIMESTAMP,
  multiplier DECIMAL(3,2) DEFAULT 1.5,  -- From config
  is_paid BOOLEAN DEFAULT TRUE,
  notes TEXT,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);
```

##### Backend Tasks

- [ ] Create OvertimeRequest model
- [ ] Create OvertimeApiController
- [ ] Auto-calculate overtime from attendance
- [ ] Integrate with payroll calculation
- [ ] Create overtime reports

##### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/overtime` | List overtime (admin) |
| GET | `/api/v1/overtime/my` | My overtime requests |
| POST | `/api/v1/overtime` | Request overtime |
| PUT | `/api/v1/overtime/{id}/approve` | Approve overtime |
| PUT | `/api/v1/overtime/{id}/reject` | Reject overtime |
| PUT | `/api/v1/overtime/{id}/complete` | Mark as complete with actual hours |
| GET | `/api/v1/overtime/report` | Overtime report |

##### Overtime Calculation Rules

```php
// config/payroll.php sudah ada:
'overtime_multiplier' => 1.5,
'maximum_overtime_hours' => 60,
'weekend_overtime_premium' => false,
'holiday_overtime_premium' => false,

// Tambahan rules:
'overtime_rules' => [
    'weekday' => [
        'first_hour' => 1.5,
        'next_hours' => 2.0,
    ],
    'weekend' => [
        'all_hours' => 2.0,
    ],
    'holiday' => [
        'all_hours' => 3.0,
    ],
],
```

##### Frontend Tasks

- [ ] Create overtime request form
- [ ] Create my overtime list page
- [ ] Create admin overtime management page
- [ ] Add overtime to payroll preview
- [ ] Create overtime report page

---

#### 3.3 Announcement/Broadcast System

**Duration:** 3-4 hari  
**Owner:** Full-stack Developer

##### Database Migration

```sql
CREATE TABLE announcements (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  type VARCHAR(20) DEFAULT 'info',  -- 'info', 'warning', 'critical', 'celebration'
  priority INTEGER DEFAULT 0,
  target_audience VARCHAR(50) DEFAULT 'all', -- 'all', 'role', 'department', 'employee_type'
  target_ids TEXT[],                -- Array of role/dept/type IDs if not 'all'
  starts_at TIMESTAMP DEFAULT NOW(),
  expires_at TIMESTAMP,
  require_acknowledgment BOOLEAN DEFAULT FALSE,
  is_pinned BOOLEAN DEFAULT FALSE,
  attachment_path VARCHAR(255),
  created_by UUID REFERENCES users(id),
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE announcement_reads (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  announcement_id UUID REFERENCES announcements(id) ON DELETE CASCADE,
  user_id UUID REFERENCES users(id),
  read_at TIMESTAMP DEFAULT NOW(),
  acknowledged_at TIMESTAMP,
  UNIQUE(announcement_id, user_id)
);
```

##### Backend Tasks

- [ ] Create Announcement model
- [ ] Create AnnouncementRead model
- [ ] Create AnnouncementApiController
- [ ] Add filtering by target audience
- [ ] Track read status
- [ ] Create acknowledgment system

##### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/announcements` | List all (admin) |
| GET | `/api/v1/announcements/active` | Active for current user |
| GET | `/api/v1/announcements/unread` | Unread count |
| POST | `/api/v1/announcements` | Create announcement |
| PUT | `/api/v1/announcements/{id}` | Update announcement |
| DELETE | `/api/v1/announcements/{id}` | Delete announcement |
| POST | `/api/v1/announcements/{id}/read` | Mark as read |
| POST | `/api/v1/announcements/{id}/acknowledge` | Acknowledge |

##### Frontend Tasks

- [ ] Create announcement banner on dashboard
- [ ] Create announcement list component
- [ ] Create admin announcement management page
- [ ] Add notification bell integration
- [ ] Create acknowledgment modal

##### UI Components

```
frontend/src/components/
└── announcements/
    ├── AnnouncementBanner.tsx  # Top banner for critical
    ├── AnnouncementCard.tsx    # Card in dashboard
    ├── AnnouncementList.tsx    # Full list
    └── AcknowledgeModal.tsx    # Confirmation modal
```

---

### Phase 4: Analytics & Polish (Future)

#### 4.1 Advanced Analytics Dashboard

**Duration:** 5-7 hari

##### Features

- Year-over-year attendance comparison
- Department attendance ranking
- Trend prediction using ML
- Heatmap visualization
- Custom report builder

##### Tech Stack

- Recharts (sudah installed)
- react-grid-layout for customizable widgets
- date-fns for date manipulation

---

## 📁 File Structure Summary

### Backend New Files

```
backend/
├── app/
│   ├── Imports/
│   │   ├── EmployeesImport.php
│   │   ├── SubjectsImport.php
│   │   ├── PositionsImport.php
│   │   ├── DepartmentsImport.php
│   │   └── ClassroomsImport.php
│   ├── Http/Controllers/Api/
│   │   ├── ImportController.php
│   │   ├── CorrectionApiController.php
│   │   ├── OvertimeApiController.php
│   │   └── AnnouncementApiController.php
│   ├── Mail/
│   │   ├── LeaveApprovedMail.php
│   │   ├── LeaveRejectedMail.php
│   │   ├── WelcomeEmployeeMail.php
│   │   └── AttendanceReminderMail.php
│   └── Models/
│       ├── AttendanceCorrection.php
│       ├── OvertimeRequest.php
│       ├── Announcement.php
│       └── AnnouncementRead.php
├── database/migrations/
│   ├── xxxx_create_attendance_corrections_table.php
│   ├── xxxx_create_overtime_requests_table.php
│   └── xxxx_create_announcements_table.php
├── resources/views/emails/
│   └── (email templates)
└── storage/app/templates/
    └── (Excel templates)
```

### Frontend New Files

```
frontend/src/
├── components/
│   ├── shared/
│   │   ├── ExcelImportDialog.tsx
│   │   └── ExcelPreviewTable.tsx
│   ├── pwa/
│   │   ├── InstallPrompt.tsx
│   │   ├── UpdatePrompt.tsx
│   │   └── OfflineIndicator.tsx
│   ├── offline/
│   │   └── SyncStatus.tsx
│   └── announcements/
│       ├── AnnouncementBanner.tsx
│       ├── AnnouncementCard.tsx
│       └── AnnouncementList.tsx
├── lib/
│   ├── api/
│   │   ├── imports.ts
│   │   ├── corrections.ts
│   │   ├── overtime.ts
│   │   └── announcements.ts
│   └── offline/
│       ├── db.ts
│       ├── queue.ts
│       └── sync.ts
├── hooks/
│   ├── useExcelImport.ts
│   ├── useServiceWorker.ts
│   ├── usePWAInstall.ts
│   ├── useOfflineMode.ts
│   └── useOfflineAttendance.ts
├── pages/
│   ├── employee/
│   │   └── attendance/
│   │       └── request-correction.tsx
│   └── admin/
│       ├── attendance/
│       │   └── corrections.tsx
│       ├── overtime/
│       │   └── index.tsx
│       └── announcements/
│           └── index.tsx
└── public/
    ├── manifest.json
    ├── offline.html
    └── icons/
```

---

## ✅ Definition of Done

Setiap fitur dianggap selesai jika:

1. **Code Complete**
   - [ ] Backend API functional
   - [ ] Frontend UI complete
   - [ ] Integration tested

2. **Quality**
   - [ ] No TypeScript errors
   - [ ] No console errors
   - [ ] Responsive design (mobile + desktop)

3. **Testing**
   - [ ] Unit tests for services
   - [ ] E2E test for critical paths
   - [ ] Manual testing on staging

4. **Documentation**
   - [ ] API documented
   - [ ] User guide if needed

---

## 🔧 Technical Notes

### Dependencies to Install

**Backend:**

```bash
composer require maatwebsite/excel
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider"
```

**Frontend:**

```bash
npm install vite-plugin-pwa workbox-window xlsx idb
```

### Environment Variables Needed

```env
# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=

# PWA
VITE_PWA_ENABLED=true
```

---

## 📅 Weekly Sprint Plan

### Week 1

| Day | Task | Owner |
|-----|------|-------|
| Mon | Setup ExcelImportDialog component | FE |
| Tue | Backend employee import API | BE |
| Wed | Employee import integration + testing | Full |
| Thu | Master data import (Subjects, Positions) | Full |
| Fri | Master data import (Depts, Classrooms) | Full |

### Week 2

| Day | Task | Owner |
|-----|------|-------|
| Mon | Email templates setup | BE |
| Tue | Leave approval emails | BE |
| Wed | Welcome + Password reset emails | BE |
| Thu | Email testing + queue setup | BE |
| Fri | Buffer / bug fixes | All |

### Week 3

| Day | Task | Owner |
|-----|------|-------|
| Mon | PWA manifest + vite config | FE |
| Tue | Install prompt + offline indicator | FE |
| Wed | IndexedDB setup | FE |
| Thu | Offline attendance queue | Full |
| Fri | Sync endpoint + testing | Full |

### Week 4

| Day | Task | Owner |
|-----|------|-------|
| Mon | Offline sync refinement | Full |
| Tue | Attendance correction backend | BE |
| Wed | Attendance correction frontend | FE |
| Thu | Integration testing | All |
| Fri | Buffer / bug fixes | All |

### Week 5

| Day | Task | Owner |
|-----|------|-------|
| Mon | Overtime backend | BE |
| Tue | Overtime frontend | FE |
| Wed | Overtime payroll integration | Full |
| Thu | Overtime testing | All |
| Fri | Buffer | All |

### Week 6

| Day | Task | Owner |
|-----|------|-------|
| Mon | Announcement backend | BE |
| Tue | Announcement frontend | FE |
| Wed | Dashboard integration | FE |
| Thu | Final testing + polish | All |
| Fri | Deployment | All |

---

## 🚦 Risk Management

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Email delivery issues | Medium | High | Use queue, implement retry |
| Offline sync conflicts | Medium | Medium | Implement conflict resolution UI |
| PWA browser compatibility | Low | Medium | Test on major browsers |
| Large Excel import timeout | Medium | Medium | Implement chunked processing |

---

## 📞 Communication

- **Daily Standup:** 09:00 WIB
- **Weekly Review:** Friday 16:00 WIB
- **Slack Channel:** #attendance-dev
- **Issue Tracker:** GitHub Issues

---

## 🎉 Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Import 100 employees | < 30 seconds | Performance test |
| Email delivery rate | > 95% | Mail provider stats |
| Offline sync success | > 99% | Analytics |
| PWA install rate | > 20% employees | Analytics |
| Correction request time | < 2 minutes | User feedback |

---

> **Next Steps:**
>
> 1. Review plan dengan tim
> 2. Mulai Sprint 1 dengan Excel Import
> 3. Update progress di dokumen ini
