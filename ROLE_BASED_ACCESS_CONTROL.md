# Role-Based Access Control (RBAC) Documentation

**Tanggal**: 1 Desember 2025
**Status**: Aktif
**Sistem**: Attendance Management System

---

## Daftar Isi
- [Overview](#overview)
- [Roles & Permissions](#roles--permissions)
- [Admin Roles](#admin-roles)
- [Employee Roles](#employee-roles)
- [Shared Routes](#shared-routes)
- [Test Credentials](#test-credentials)
- [Route Guards](#route-guards)

---

## Overview

Sistem ini menggunakan **Role-Based Routing** dengan 2 hierarki utama:
- **Admin Routes**: `/admin/*` - Untuk Super Admin, Admin, dan Kepala Sekolah
- **Employee Routes**: `/employee/*` - Untuk Pegawai dan Guru

### Prinsip Akses
- Admin **TIDAK BISA** akses `/employee/*` routes (akan redirect ke `/admin/dashboard`)
- Employee **TIDAK BISA** akses `/admin/*` routes (akan redirect ke `/employee/dashboard`)
- Shared routes dapat diakses kedua role dengan logic berbeda

---

## Roles & Permissions

### Admin Roles
Roles yang masuk kategori admin:
- `super-admin` - Akses penuh ke semua fitur
- `admin` - Akses penuh manajemen
- `kepala-sekolah` - Akses penuh manajemen (Principal)

### Employee Roles
Roles yang masuk kategori employee:
- `pegawai` - Staff/Karyawan regular
- `guru` - Guru/Teacher
- `employee` - Default employee role

---

## Admin Roles

### 🔐 Super Admin / Admin / Kepala Sekolah

**Login Redirect**: `/admin/dashboard`

#### ✅ Pages yang Bisa Diakses

| Route | Halaman | Deskripsi | Sub-Pages |
|-------|---------|-----------|-----------|
| `/admin/dashboard` | Dashboard Admin | Ringkasan & statistik perusahaan | - |
| `/admin/attendance` | Manajemen Absensi | Kelola absensi semua karyawan | `/admin/attendance/history` |
| `/admin/employees` | Manajemen Karyawan | Data karyawan & kredensial | `/create`, `/edit/:id`, `/show/:id`, `/credentials/:id` |
| `/admin/schedules` | Manajemen Jadwal | Jadwal kerja & penugasan | `/create`, `/edit/:id`, `/show/:id`, `/builder`, `/assign`, `/monthly` |
| `/admin/leave` | Manajemen Cuti | Pengajuan & persetujuan cuti | `/approvals`, `/calendar`, `/show/:id` |
| `/admin/payroll` | Manajemen Penggajian | Slip gaji & kalkulasi | `/show/:id`, `/edit/:id` |
| `/admin/reports` | Laporan | Laporan & report builder | `/builder` |
| `/admin/settings` | Pengaturan Sistem | Konfigurasi sistem | `/locations`, `/holidays`, `/users`, `/security` |

#### ❌ Pages yang TIDAK Bisa Diakses

Semua route `/employee/*` akan redirect ke `/admin/dashboard`:
- `/employee/dashboard`
- `/employee/attendance`
- `/employee/schedule`
- `/employee/leave`
- `/employee/payroll`
- `/employee/profile`

#### 📋 Detail Sub-Pages Admin

**Karyawan (Employees)**
- `/admin/employees` - List semua karyawan
- `/admin/employees/create` - Tambah karyawan baru
- `/admin/employees/edit/:id` - Edit data karyawan
- `/admin/employees/show/:id` - Detail karyawan
- `/admin/employees/credentials/:id` - Manajemen kredensial face recognition

**Jadwal (Schedules)**
- `/admin/schedules` - List jadwal (tabs: List, Builder, Assign, Monthly)
- `/admin/schedules/create` - Buat jadwal baru
- `/admin/schedules/edit/:id` - Edit jadwal
- `/admin/schedules/show/:id` - Detail jadwal
- `/admin/schedules/builder` - Visual schedule builder
- `/admin/schedules/assign` - Assign jadwal ke karyawan
- `/admin/schedules/monthly` - Jadwal bulanan
- `/admin/schedules/monthly/create` - Buat jadwal bulanan

**Cuti (Leave)**
- `/admin/leave` - List pengajuan cuti semua karyawan
- `/admin/leave/approvals` - Persetujuan cuti
- `/admin/leave/calendar` - Kalender cuti
- `/admin/leave/show/:id` - Detail pengajuan cuti

**Penggajian (Payroll)**
- `/admin/payroll` - List slip gaji
- `/admin/payroll/show/:id` - Detail slip gaji
- `/admin/payroll/edit/:id` - Edit slip gaji

**Pengaturan (Settings)**
- `/admin/settings/locations` - Manajemen lokasi
- `/admin/settings/locations/create` - Tambah lokasi
- `/admin/settings/locations/edit/:id` - Edit lokasi
- `/admin/settings/holidays` - Manajemen hari libur
- `/admin/settings/holidays/create` - Tambah hari libur
- `/admin/settings/holidays/calendar` - Kalender libur
- `/admin/settings/users` - Manajemen users
- `/admin/settings/users/create` - Tambah user

---

## Employee Roles

### 👤 Pegawai / Guru / Employee

**Login Redirect**: `/employee/dashboard`

#### ✅ Pages yang Bisa Diakses

| Route | Halaman | Deskripsi | Sub-Pages |
|-------|---------|-----------|-----------|
| `/employee/dashboard` | Dashboard Pribadi | Dashboard karyawan | - |
| `/employee/attendance` | Riwayat Absensi | Kehadiran pribadi | `/employee/attendance/history` |
| `/employee/schedule` | Jadwal Saya | Jadwal kerja pribadi | - |
| `/employee/leave` | Cuti Saya | Pengajuan cuti pribadi | `/employee/leave/create`, `/employee/leave/show/:id`, `/employee/leave/calendar` |
| `/employee/payroll` | Slip Gaji Saya | Riwayat gaji pribadi | `/employee/payroll/show/:id` |
| `/employee/profile` | Profil Saya | Data profil pribadi | `/employee/profile/edit` |

#### ❌ Pages yang TIDAK Bisa Diakses

Semua route `/admin/*` akan redirect ke `/employee/dashboard`:
- `/admin/dashboard`
- `/admin/attendance`
- `/admin/employees`
- `/admin/schedules`
- `/admin/leave`
- `/admin/payroll`
- `/admin/reports`
- `/admin/settings`

#### 📋 Detail Sub-Pages Employee

**Absensi (Attendance)**
- `/employee/attendance` - Halaman absensi (check-in/out)
- `/employee/attendance/history` - Riwayat absensi pribadi

**Cuti (Leave)**
- `/employee/leave` - List pengajuan cuti pribadi
- `/employee/leave/create` - Ajukan cuti baru
- `/employee/leave/show/:id` - Detail pengajuan cuti
- `/employee/leave/calendar` - Kalender cuti pribadi

**Slip Gaji (Payroll)**
- `/employee/payroll` - List slip gaji pribadi
- `/employee/payroll/show/:id` - Detail slip gaji

**Profil (Profile)**
- `/employee/profile` - Lihat profil
- `/employee/profile/edit` - Edit profil

---

## Shared Routes

Routes yang dapat diakses oleh **semua role** (Admin & Employee):

### 🔓 Public/Auth Routes

| Route | Halaman | Akses | Redirect Behavior |
|-------|---------|-------|-------------------|
| `/login` | Login Page | Semua (guest only) | Admin → `/admin/dashboard`<br>Employee → `/employee/dashboard` |
| `/register` | Register Page | Semua (guest only) | Sama dengan login |
| `/forgot-password` | Lupa Password | Semua (guest only) | - |
| `/reset-password` | Reset Password | Semua (guest only) | - |
| `/verify-email` | Verifikasi Email | Authenticated | - |

### 🔄 Shared Features

| Route | Halaman | Akses | Redirect Logic |
|-------|---------|-------|----------------|
| `/attendance/verify-location` | Verifikasi GPS | Authenticated | Success/Cancel → role-based attendance page |
| `/attendance/verify-face` | Verifikasi Wajah | Authenticated | Success/Cancel → role-based attendance page |
| `/face-recognition` | Face Recognition | Authenticated | - |
| `/face-recognition/settings` | Settings Face | Authenticated | - |

**Contoh Redirect Logic**:
- Admin complete face verification → redirect ke `/admin/attendance`
- Employee complete face verification → redirect ke `/employee/attendance`

### 🚫 Error Routes

| Route | Halaman | Akses |
|-------|---------|-------|
| `/unauthorized` | Unauthorized 403 | Semua |
| `/not-found` | Not Found 404 | Semua |

**Tombol "Kembali ke Dashboard"** akan redirect sesuai role:
- Admin → `/admin/dashboard`
- Employee → `/employee/dashboard`

---

## Test Credentials

### Admin Users

| Role | Email | Password | Dashboard |
|------|-------|----------|-----------|
| **Super Admin** | `superadmin@school.edu` | `password` | `/admin/dashboard` |
| **Admin** | `admin@attendance.com` | `password123` | `/admin/dashboard` |
| **Admin** | `admin@school.edu` | `password` | `/admin/dashboard` |
| **Kepala Sekolah** | `kepala@school.edu` | `password` | `/admin/dashboard` |
| **Manager** | `siti.nurhaliza@attendance.com` | `password123` | `/admin/dashboard` |

### Employee Users

| Role | Email | Password | Dashboard |
|------|-------|----------|-----------|
| **Employee** | `ahmad.wijaya@attendance.com` | `password123` | `/employee/dashboard` |
| **Employee** | `dewi.lestari@attendance.com` | `password123` | `/employee/dashboard` |
| **Employee** | `andi.pratama@attendance.com` | `password123` | `/employee/dashboard` |
| **Employee** | `sri.mulyani@attendance.com` | `password123` | `/employee/dashboard` |

---

## Route Guards

### Guard Functions

| Guard | Fungsi | Redirect Jika Gagal |
|-------|--------|---------------------|
| `requireAuth()` | Harus login | `/login` |
| `requireAdmin()` | Harus admin role | `/employee/dashboard` (jika employee)<br>`/login` (jika guest) |
| `requireEmployee()` | Harus employee role | `/admin/dashboard` (jika admin)<br>`/login` (jika guest) |
| `requireGuest()` | Harus belum login | Role-based dashboard |

### Implementation di Router

```typescript
// Admin Routes - Protected by requireAdmin
{
  path: '/admin',
  beforeLoad: requireAdmin,
  children: [
    { path: '/dashboard', ... },
    { path: '/employees', ... },
    // ...
  ]
}

// Employee Routes - Protected by requireEmployee
{
  path: '/employee',
  beforeLoad: requireEmployee,
  children: [
    { path: '/dashboard', ... },
    { path: '/profile', ... },
    // ...
  ]
}

// Shared Routes - Protected by requireAuth only
{
  path: '/attendance/verify-face',
  beforeLoad: requireAuth,
  // Redirects based on role after completion
}
```

---

## Navigation Menu

### Admin Navigation

**Menu Utama**
- Dashboard (`/admin/dashboard`)
- Absensi (`/admin/attendance`)

**Manajemen**
- Karyawan (`/admin/employees`)
- Jadwal (`/admin/schedules`)
- Cuti (`/admin/leave`)
- Penggajian (`/admin/payroll`)
- Laporan (`/admin/reports`)

**Sistem**
- Pengaturan (`/admin/settings`)

### Employee Navigation

**Menu Utama**
- Dashboard (`/employee/dashboard`)
- Absensi (`/employee/attendance`)

**Pribadi**
- Jadwal Saya (`/employee/schedule`)
- Cuti Saya (`/employee/leave`)
- Slip Gaji (`/employee/payroll`)
- Profil (`/employee/profile`)

---

## Quick Reference Matrix

| Feature | Super Admin | Admin | Kepala Sekolah | Pegawai | Guru |
|---------|-------------|-------|----------------|---------|------|
| View All Attendance | ✅ | ✅ | ✅ | ❌ | ❌ |
| Manage Employees | ✅ | ✅ | ✅ | ❌ | ❌ |
| Manage Schedules | ✅ | ✅ | ✅ | ❌ | ❌ |
| Approve Leave | ✅ | ✅ | ✅ | ❌ | ❌ |
| Process Payroll | ✅ | ✅ | ✅ | ❌ | ❌ |
| Generate Reports | ✅ | ✅ | ✅ | ❌ | ❌ |
| System Settings | ✅ | ✅ | ✅ | ❌ | ❌ |
| Own Attendance | ✅ | ✅ | ✅ | ✅ | ✅ |
| Request Leave | ✅ | ✅ | ✅ | ✅ | ✅ |
| View Own Schedule | ✅ | ✅ | ✅ | ✅ | ✅ |
| View Own Payslip | ✅ | ✅ | ✅ | ✅ | ✅ |
| Edit Own Profile | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## Testing Checklist

### ✅ Basic Access Control
- [x] Login sebagai Admin → Redirect ke `/admin/dashboard`
- [x] Login sebagai Employee → Redirect ke `/employee/dashboard`
- [x] Admin akses `/employee/*` → Redirect ke `/admin/dashboard`
- [x] Employee akses `/admin/*` → Redirect ke `/employee/dashboard`

### ✅ Navigation
- [x] Admin melihat admin navigation menu
- [x] Employee melihat employee navigation menu
- [x] Bottom nav (mobile) menampilkan links sesuai role
- [x] Sidebar menampilkan links sesuai role

### ✅ Shared Routes
- [x] Admin complete face verification → Redirect ke `/admin/attendance`
- [x] Employee complete face verification → Redirect ke `/employee/attendance`
- [x] Admin cancel face verification → Redirect ke `/admin/attendance`
- [x] Employee cancel face verification → Redirect ke `/employee/attendance`

### ✅ Error Pages
- [x] 404 page "Kembali ke Dashboard" → Role-based redirect
- [x] 403 unauthorized page → Role-based redirect

### ✅ Guest Routes
- [x] Guest akses `/admin/*` → Redirect ke `/login`
- [x] Guest akses `/employee/*` → Redirect ke `/login`
- [x] Authenticated user akses `/login` → Redirect to dashboard

---

## Related Files

### Configuration
- `/home/omanjaya/Project/attendancedev/frontend/src/config/navigation.ts` - Navigation menu config
- `/home/omanjaya/Project/attendancedev/frontend/src/lib/auth/guards.ts` - Route guard functions

### Router
- `/home/omanjaya/Project/attendancedev/frontend/src/app/router.tsx` - TanStack Router config

### Documentation
- `ROLE_BASED_ROUTING_COMPLETE.md` - Implementation details
- `NAVIGATION_PATH_UPDATES_COMPLETE.md` - Path migration details

---

## Support & Issues

Jika menemukan masalah akses:
1. Periksa role user di database (tabel `users` dan `model_has_roles`)
2. Pastikan guard function di route config sudah benar
3. Check browser console untuk error redirect
4. Verifikasi auth token masih valid

---

**Last Updated**: 1 Desember 2025
**Version**: 1.0.0
**Author**: Claude Code
