---
description: Implementasi fitur yang kurang - Excel Import, Email, PWA, Overtime
---

# Missing Features Implementation Workflow

## Overview

Workflow ini mengimplementasikan fitur-fitur yang kurang pada sistem absensi berdasarkan analisis di `IMPLEMENTATION_PLAN_MISSING_FEATURES.md`.

## Prerequisites

- Docker sudah running
- Database sudah tersedia
- `.env` sudah dikonfigurasi

---

## Phase 1: Excel Import (Week 1-2)

### Step 1: Setup Laravel Excel Package

```bash
cd /home/omanjaya/Project/attendancedev/backend
docker compose exec backend composer require maatwebsite/excel
docker compose exec backend php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider"
```

### Step 2: Install Frontend Dependencies

```bash
cd /home/omanjaya/Project/attendancedev/frontend
npm install xlsx
```

### Step 3: Create Reusable Import Component

Create file: `frontend/src/components/shared/ExcelImportDialog.tsx`

### Step 4: Create Backend Import Classes

- Create: `backend/app/Imports/EmployeesImport.php`
- Create: `backend/app/Imports/SubjectsImport.php`
- Create: `backend/app/Http/Controllers/Api/ImportController.php`

### Step 5: Add API Routes

Add to `backend/routes/api.php`:

```php
Route::prefix('import')->group(function () {
    Route::post('/employees', [ImportController::class, 'employees']);
    Route::get('/employees/template', [ImportController::class, 'employeesTemplate']);
    Route::post('/subjects', [ImportController::class, 'subjects']);
    Route::post('/positions', [ImportController::class, 'positions']);
    Route::post('/departments', [ImportController::class, 'departments']);
    Route::post('/classrooms', [ImportController::class, 'classrooms']);
});
```

### Step 6: Integrate Frontend

Add import button to:

- `/admin/employees` page
- `/admin/settings/employee-types` page (tabs: Subjects, Positions, Departments)
- `/admin/master-data` page (tab: Classrooms)

### Step 7: Test Import

// turbo

```bash
curl -X POST http://localhost/api/v1/import/employees \
  -H "Authorization: Bearer $TOKEN" \
  -F "file=@test_employees.xlsx"
```

---

## Phase 2: Email Notification (Week 2)

### Step 1: Configure Email

Add to `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@school.edu
MAIL_FROM_NAME="${APP_NAME}"
```

### Step 2: Create Mail Classes

// turbo

```bash
cd /home/omanjaya/Project/attendancedev/backend
docker compose exec backend php artisan make:mail LeaveApprovedMail --markdown=emails.leave.approved
docker compose exec backend php artisan make:mail LeaveRejectedMail --markdown=emails.leave.rejected
docker compose exec backend php artisan make:mail WelcomeEmployeeMail --markdown=emails.auth.welcome
docker compose exec backend php artisan make:mail AttendanceReminderMail --markdown=emails.attendance.reminder
```

### Step 3: Create Email Base Layout

Create: `backend/resources/views/emails/layouts/base.blade.php`

### Step 4: Implement Email Sending

Update `NotificationService.php` to implement email channel (line 503-505):

```php
case 'email':
    Mail::to($user->email)->queue(new AttendanceNotification($data));
    break;
```

### Step 5: Test Email

// turbo

```bash
docker compose exec backend php artisan tinker
>>> Mail::raw('Test email', fn($m) => $m->to('test@example.com')->subject('Test'));
```

---

## Phase 3: PWA & Offline (Week 3-4)

### Step 1: Install PWA Plugin

```bash
cd /home/omanjaya/Project/attendancedev/frontend
npm install vite-plugin-pwa workbox-window
```

### Step 2: Configure Vite PWA

Update `vite.config.ts`:

```typescript
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
    VitePWA({
      registerType: 'autoUpdate',
      manifest: {
        name: 'Attendance Management System',
        short_name: 'Attendance',
        theme_color: '#0F172A',
        icons: [...]
      }
    })
  ]
});
```

### Step 3: Create Manifest

Create: `frontend/public/manifest.json`

### Step 4: Create Offline Components

- Create: `frontend/src/components/pwa/InstallPrompt.tsx`
- Create: `frontend/src/components/pwa/OfflineIndicator.tsx`
- Create: `frontend/src/hooks/useServiceWorker.ts`

### Step 5: Setup IndexedDB

Install IDB:

```bash
npm install idb
```

Create: `frontend/src/lib/offline/db.ts`

### Step 6: Implement Offline Attendance Queue

- Create: `frontend/src/lib/offline/queue.ts`
- Create: `frontend/src/hooks/useOfflineAttendance.ts`

### Step 7: Create Sync Endpoint

Add to backend: `POST /api/v1/attendance/sync`

---

## Phase 4: Attendance Correction (Week 4)

### Step 1: Create Migration

// turbo

```bash
docker compose exec backend php artisan make:migration create_attendance_corrections_table
```

### Step 2: Run Migration

// turbo

```bash
docker compose exec backend php artisan migrate
```

### Step 3: Create Model

// turbo

```bash
docker compose exec backend php artisan make:model AttendanceCorrection
```

### Step 4: Create Controller

// turbo

```bash
docker compose exec backend php artisan make:controller Api/CorrectionApiController --api
```

### Step 5: Create Frontend Pages

- Create: `frontend/src/pages/employee/attendance/request-correction.tsx`
- Create: `frontend/src/pages/admin/attendance/corrections.tsx`

---

## Phase 5: Overtime Management (Week 5)

### Step 1: Create Migration

// turbo

```bash
docker compose exec backend php artisan make:migration create_overtime_requests_table
```

### Step 2: Create Model & Controller

// turbo

```bash
docker compose exec backend php artisan make:model OvertimeRequest
docker compose exec backend php artisan make:controller Api/OvertimeApiController --api
```

### Step 3: Create Frontend

- Create: `frontend/src/pages/admin/overtime/index.tsx`
- Create: `frontend/src/lib/api/overtime.ts`

---

## Phase 6: Announcements (Week 6)

### Step 1: Create Migrations

// turbo

```bash
docker compose exec backend php artisan make:migration create_announcements_table
docker compose exec backend php artisan make:migration create_announcement_reads_table
```

### Step 2: Create Models

// turbo

```bash
docker compose exec backend php artisan make:model Announcement
docker compose exec backend php artisan make:model AnnouncementRead
docker compose exec backend php artisan make:controller Api/AnnouncementApiController --api
```

### Step 3: Create Frontend

- Create: `frontend/src/components/announcements/AnnouncementBanner.tsx`
- Create: `frontend/src/pages/admin/announcements/index.tsx`

---

## Testing Checklist

### Import Testing

- [ ] Import 10 employees successfully
- [ ] Import with duplicate email (should update)
- [ ] Import with invalid data (should show errors)
- [ ] Import 100+ employees (performance)

### Email Testing

- [ ] Leave approved email sent
- [ ] Leave rejected email sent
- [ ] Welcome email with password sent
- [ ] Emails received in queue

### PWA Testing

- [ ] Install prompt appears on mobile
- [ ] Offline page shows when disconnected
- [ ] Attendance saved offline
- [ ] Sync works when back online

### Feature Testing

- [ ] Correction request flow
- [ ] Correction approval flow
- [ ] Overtime request flow
- [ ] Announcement visible on dashboard

---

## Quick Commands

### Build & Deploy

// turbo-all

```bash
cd /home/omanjaya/Project/attendancedev
docker compose up -d --build
docker compose exec backend php artisan migrate
docker compose exec backend php artisan cache:clear
```

### Check Logs

```bash
docker compose logs -f backend
docker compose logs -f frontend
```

### Run Tests

// turbo

```bash
cd /home/omanjaya/Project/attendancedev/frontend
npm run test:e2e
```
