---
description: Panduan Testing Step-by-Step untuk Attendance System
---

# 🧪 Testing Flow Guide - Attendance System

Panduan ini membantu Anda menguji fitur aplikasi secara bertahap dan terstruktur.

---

## 📋 Pre-requisites

Sebelum mulai testing, pastikan:

```bash
# 1. Backend running
cd backend && php artisan serve

# 2. Frontend running  
cd frontend && npm run dev

# 3. Database sudah di-seed
cd backend && php artisan migrate:fresh --seed
```

---

## 🔄 FASE 1: Setup & Authentication (Wajib Pertama)

### Step 1.1: Test Login Admin

```bash
cd frontend && npx playwright test login.spec.ts --headed
```

**Expected**: Form login tampil, bisa toggle password, login berhasil redirect ke dashboard

### Step 1.2: Test Dashboard Admin

```bash
cd frontend && npx playwright test dashboard.spec.ts --headed
```

**Expected**: Dashboard tampil dengan statistik, grafik, dan navigasi sidebar

**✅ Checkpoint**: Jika Fase 1 berhasil, lanjut ke Fase 2

---

## 🏢 FASE 2: Master Data (Admin)

### Step 2.1: Test Lokasi

```bash
cd frontend && npx playwright test e2e/admin/locations.spec.ts --headed
```

**Expected**: CRUD lokasi berfungsi, map picker tampil

### Step 2.2: Test Employee Management

```bash
cd frontend && npx playwright test e2e/admin/employees.spec.ts --headed
```

**Expected**: Bisa lihat, tambah, edit, hapus karyawan

### Step 2.3: Test Master Data Lainnya

```bash
cd frontend && npx playwright test e2e/admin/master-data.spec.ts --headed
```

**Expected**: Department, position, employee types berfungsi

**✅ Checkpoint**: Jika Fase 2 berhasil, lanjut ke Fase 3

---

## 📅 FASE 3: Jadwal & Setup

### Step 3.1: Test Setup Flow

```bash
cd frontend && npx playwright test e2e/flows/setup-flow.spec.ts --headed
```

**Expected**: Bisa setup lokasi → karyawan → credentials → jadwal → assign

**✅ Checkpoint**: Jika Fase 3 berhasil, lanjut ke Fase 4

---

## 👤 FASE 4: Employee Flow

### Step 4.1: Test Employee Basic Flow

```bash
cd frontend && npx playwright test e2e/employee/employee-flow.spec.ts --headed
```

**Expected**: Employee bisa login, lihat dashboard, navigasi menu

### Step 4.2: Test Face Recognition (jika DeepFace aktif)

```bash
cd frontend && npx playwright test e2e/employee/face-recognition.spec.ts --headed
```

**Expected**: Kamera aktif, bisa capture wajah

**✅ Checkpoint**: Jika Fase 4 berhasil, lanjut ke Fase 5

---

## 📊 FASE 5: Reports & Operations

### Step 5.1: Test Daily Operations

```bash
cd frontend && npx playwright test e2e/flows/daily-operations.spec.ts --headed
```

**Expected**: Absensi, monitoring, koreksi berfungsi

### Step 5.2: Test Reports

```bash
cd frontend && npx playwright test e2e/admin/reports-attendance.spec.ts --headed
```

**Expected**: Report tampil, filter berfungsi, export tersedia

### Step 5.3: Test Monthly Operations

```bash
cd frontend && npx playwright test e2e/flows/monthly-operations.spec.ts --headed
```

**Expected**: Rekap bulanan, cuti, payroll berfungsi

**✅ Checkpoint**: Jika Fase 5 berhasil, lanjut ke Fase 6

---

## 🔍 FASE 6: Full Regression (Optional)

### Step 6.1: Run All Admin Tests

```bash
cd frontend && npx playwright test e2e/admin/ --reporter=html
```

### Step 6.2: Run All Employee Tests  

```bash
cd frontend && npx playwright test e2e/employee/ --reporter=html
```

### Step 6.3: Run ALL Tests

```bash
cd frontend && npx playwright test --reporter=html
```

### Step 6.4: View Report

```bash
cd frontend && npx playwright show-report
```

---

## 🔧 FASE 7: Backend API Tests

### Step 7.1: Test Authentication API

```bash
cd backend && php artisan test --filter=AuthTest
```

### Step 7.2: Test Attendance API

```bash
cd backend && php artisan test --filter=AttendanceTest
```

### Step 7.3: Test Face Recognition API

```bash
cd backend && php artisan test --filter=FaceRecognitionTest
```

### Step 7.4: Run All Backend Tests

```bash
cd backend && php artisan test
```

---

## 📝 Tips Testing

### Jalankan dengan Visual (melihat browser)

Tambahkan `--headed` untuk melihat browser:

```bash
npx playwright test login.spec.ts --headed
```

### Debug Mode (step-by-step)

```bash
npx playwright test login.spec.ts --debug
```

### Jalankan Test Spesifik

```bash
# By name
npx playwright test -g "should display login form"

# By file
npx playwright test e2e/login.spec.ts
```

### Generate Report HTML

```bash
npx playwright test --reporter=html
npx playwright show-report
```

---

## ⚡ Quick Commands

| Tujuan | Command |
|--------|---------|
| Test Login | `npx playwright test login.spec.ts --headed` |
| Test Dashboard | `npx playwright test dashboard.spec.ts --headed` |
| Test Admin All | `npx playwright test e2e/admin/` |
| Test Employee All | `npx playwright test e2e/employee/` |
| Test dengan UI | `npx playwright test --ui` |
| Backend Tests | `cd backend && php artisan test` |

---

## 🚨 Troubleshooting

### Test gagal karena timeout

- Pastikan backend dan frontend running
- Tambahkan `--timeout=60000` untuk increase timeout

### Login test gagal

- Cek database sudah di-seed: `php artisan db:seed`
- Cek user credentials: `admin@school.edu` / `password`

### Camera tests gagal

- Camera tests butuh permission, mungkin skip di CI
- Jalankan dengan `--headed` untuk allow permission

---

## 📌 Testing Checklist

- [ ] Fase 1: Login & Dashboard ✅
- [ ] Fase 2: Master Data (Lokasi, Employee, dll)
- [ ] Fase 3: Setup Flow (Jadwal, Assign)
- [ ] Fase 4: Employee Flow (Login, Dashboard)
- [ ] Fase 5: Reports & Operations
- [ ] Fase 6: Full Regression
- [ ] Fase 7: Backend API Tests
