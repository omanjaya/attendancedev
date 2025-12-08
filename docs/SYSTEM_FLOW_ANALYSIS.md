# Analisis Flow Sistem Kehadiran (Attendance System)

## Deskripsi Sistem

Sistem ini adalah aplikasi manajemen kehadiran (attendance management system) berbasis web yang dibangun dengan:

- **Backend**: Laravel 12, PHP 8.2+, PostgreSQL, Redis
- **Frontend**: React 19, TypeScript, TanStack Router/Query, Tailwind CSS 4
- **Face Recognition Service**: Python DeepFace (ArcFace)

---

## 🔐 Flow Autentikasi

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           AUTHENTICATION FLOW                            │
└─────────────────────────────────────────────────────────────────────────┘

┌──────────┐     ┌────────────┐     ┌─────────────┐     ┌──────────────┐
│  User    │────▶│ Login Page │────▶│ API: Login  │────▶│ Validate     │
│  Akses   │     │ /login     │     │ /v1/auth/   │     │ Credentials  │
└──────────┘     └────────────┘     │   login     │     └──────┬───────┘
                                    └─────────────┘            │
                                                               ▼
                                                    ┌──────────────────┐
                                                    │  Get User Role   │
                                                    │  & Permissions   │
                                                    └────────┬─────────┘
                                                             │
                          ┌──────────────────────────────────┴───────────┐
                          │                                              │
                          ▼                                              ▼
              ┌────────────────────┐                        ┌────────────────────┐
              │ Role: Admin/       │                        │ Role: Employee/    │
              │ Super Admin/       │                        │ Guru/Staff         │
              │ Kepala Sekolah     │                        │                    │
              └─────────┬──────────┘                        └──────────┬─────────┘
                        │                                              │
                        ▼                                              ▼
              ┌────────────────────┐                        ┌────────────────────┐
              │ Redirect to:       │                        │ Redirect to:       │
              │ /admin/dashboard   │                        │ /employee/dashboard│
              └────────────────────┘                        └────────────────────┘
```

---

## 👨‍💼 Flow Admin (Super Admin / Admin / Kepala Sekolah)

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              ADMIN MAIN FLOW                                     │
└─────────────────────────────────────────────────────────────────────────────────┘

                            ┌────────────────────┐
                            │   Admin Dashboard  │
                            │   /admin/dashboard │
                            └─────────┬──────────┘
                                      │
       ┌──────────────────────────────┼──────────────────────────────────┐
       │              │               │               │                  │
       ▼              ▼               ▼               ▼                  ▼
┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐    ┌────────────┐
│ Employees  │ │ Attendance │ │ Schedules  │ │   Leave    │    │   More...  │
│ Management │ │ Management │ │ Management │ │ Management │    │            │
└─────┬──────┘ └─────┬──────┘ └─────┬──────┘ └─────┬──────┘    └─────┬──────┘
      │              │               │               │                │
      ▼              ▼               ▼               ▼                ▼
```

### 1. Employee Management Flow

```
┌────────────────────────────────────────────────────────────────────────────┐
│                        EMPLOYEE MANAGEMENT                                  │
└────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐
│ /admin/employees    │
│ (Employee List)     │
└─────────┬───────────┘
          │
          ├──────────▶ [Tambah Pegawai] ──▶ /admin/employees/create
          │                                        │
          │                                        ▼
          │                               ┌─────────────────┐
          │                               │ Form: Data      │
          │                               │ Pegawai Baru    │
          │                               │ - Nama, NIP     │
          │                               │ - Email, Phone  │
          │                               │ - Jenis Pegawai │
          │                               │ - Unit Kerja    │
          │                               │ - Jabatan       │
          │                               │ - Lokasi        │
          │                               └────────┬────────┘
          │                                        │
          │                                        ▼
          │                               ┌─────────────────┐
          │                               │ API: POST       │
          │                               │ /v1/employees   │
          │                               └─────────────────┘
          │
          ├──────────▶ [Edit] ──────────▶ /admin/employees/$id/edit
          │
          ├──────────▶ [Detail] ────────▶ /admin/employees/$id
          │
          ├──────────▶ [Reset Password] ▶ API: POST /v1/employees/$id/reset-password
          │
          ├──────────▶ [Bulk Actions] ──▶ API: POST /v1/employees/bulk
          │                               - Reset Password
          │                               - Activate/Deactivate
          │                               - Delete
          │
          └──────────▶ [Credentials] ───▶ /admin/employees/credentials
                                          (Kelola kredensial login)
```

### 2. Schedule Management Flow

```
┌────────────────────────────────────────────────────────────────────────────┐
│                        SCHEDULE MANAGEMENT                                  │
└────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐
│ /admin/schedules    │
│ (Schedule List)     │
└─────────┬───────────┘
          │
          ├──────────▶ [Schedule Builder] ─▶ /admin/schedules/builder
          │                                   (Visual schedule builder)
          │                                         │
          │                                         ▼
          │                                ┌─────────────────────┐
          │                                │ Drag & Drop Grid    │
          │                                │ - Pilih Kelas       │
          │                                │ - Pilih Hari        │
          │                                │ - Pilih Jam         │
          │                                │ - Pilih Guru        │
          │                                │ - Pilih Mapel       │
          │                                └─────────────────────┘
          │
          ├──────────▶ [Monthly Schedule] ─▶ /admin/schedules/monthly
          │                                         │
          │                                         ▼
          │                                ┌─────────────────────┐
          │                                │ Jadwal Bulanan      │
          │                                │ - Set jam kerja     │
          │                                │ - Assign pegawai    │
          │                                │ - Hari kerja        │
          │                                └─────────────────────┘
          │
          ├──────────▶ [Calendar View] ────▶ /admin/schedules/calendar
          │
          ├──────────▶ [Assign Schedule] ──▶ /admin/schedules/assign
          │
          └──────────▶ [Import/Export] ────▶ Excel Import Support
```

### 3. Attendance Management Flow

```
┌────────────────────────────────────────────────────────────────────────────┐
│                       ATTENDANCE MANAGEMENT                                 │
└────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐
│ /admin/attendance   │
│ (Attendance List)   │
└─────────┬───────────┘
          │
          ├──────────▶ [Filter by Date] ──▶ View attendance per tanggal
          │
          ├──────────▶ [Filter by Employee] ▶ View per pegawai
          │
          ├──────────▶ [Manual Entry] ────▶ Tambah absensi manual
          │                                       │
          │                                       ▼
          │                              ┌─────────────────────┐
          │                              │ Form Manual Entry   │
          │                              │ - Pilih Pegawai     │
          │                              │ - Set Date/Time     │
          │                              │ - Check-in/out      │
          │                              │ - Notes/Reason      │
          │                              └─────────────────────┘
          │
          ├──────────▶ [Edit Attendance] ──▶ Update existing record
          │
          ├──────────▶ [Manual Checkout] ──▶ API: POST /attendance/$id/manual-checkout
          │
          └──────────▶ [Export] ───────────▶ API: GET /attendance/export
                                             - CSV/Excel format
```

### 4. Leave Management Flow

```
┌────────────────────────────────────────────────────────────────────────────┐
│                         LEAVE MANAGEMENT                                    │
└────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐
│ /admin/leave        │
│ (Leave List)        │
└─────────┬───────────┘
          │
          ├──────────▶ [Approvals] ────────▶ /admin/leave/approvals
          │                                        │
          │                                        ▼
          │                               ┌─────────────────────┐
          │                               │ Pending Requests    │
          │                               │ - Approve           │
          │                               │ - Reject            │
          │                               │ - View Details      │
          │                               └─────────────────────┘
          │
          ├──────────▶ [Calendar] ─────────▶ /admin/leave/calendar
          │                                  (Visual leave calendar)
          │
          ├──────────▶ [Create] ───────────▶ /admin/leave/create
          │                                  (Buat cuti untuk pegawai)
          │
          └──────────▶ [View Detail] ──────▶ /admin/leave/$id
```

### 5. Settings & Master Data Flow

```
┌────────────────────────────────────────────────────────────────────────────┐
│                        SETTINGS & MASTER DATA                               │
└────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐
│ /admin/settings     │
└─────────┬───────────┘
          │
          ├──────────▶ /admin/settings/employee-types
          │            ├── Jenis Pegawai (Guru, Staff, TU, dll)
          │            ├── Unit Kerja (Department)
          │            └── Jabatan (Position)
          │
          ├──────────▶ /admin/master-data
          │            ├── Academic Years
          │            ├── Subjects
          │            ├── Classrooms
          │            └── Periods (Time Slots)
          │
          ├──────────▶ /admin/locations
          │            └── Lokasi absensi + Koordinat GPS
          │
          └──────────▶ /admin/holidays
                       └── Kelola hari libur nasional & khusus
```

---

## 👩‍🏫 Flow Pegawai / Guru (Employee)

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                            EMPLOYEE MAIN FLOW                                    │
└─────────────────────────────────────────────────────────────────────────────────┘

                            ┌────────────────────┐
                            │ Employee Dashboard │
                            │ /employee/dashboard│
                            └─────────┬──────────┘
                                      │
       ┌─────────────┬────────────────┼────────────────┬─────────────┐
       │             │                │                │             │
       ▼             ▼                ▼                ▼             ▼
┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐
│ Attendance │ │  Schedule  │ │   Leave    │ │  Payroll   │ │  Profile   │
│            │ │            │ │            │ │            │ │            │
└─────┬──────┘ └─────┬──────┘ └─────┬──────┘ └─────┬──────┘ └─────┬──────┘
      │              │               │               │             │
```

### 1. Employee Attendance Flow (Check-in/Check-out)

```
┌────────────────────────────────────────────────────────────────────────────┐
│                        EMPLOYEE ATTENDANCE FLOW                             │
└────────────────────────────────────────────────────────────────────────────┘

┌───────────────────────┐
│ /employee/attendance  │
│ (Riwayat Kehadiran)   │
└───────────┬───────────┘
            │
            ├──────▶ [Lihat Kalender] ──▶ View mode: Calendar
            │
            ├──────▶ [Lihat List] ──────▶ View mode: List
            │
            └──────▶ [Check-In / Check-Out Button]
                            │
                            ▼
                ┌───────────────────────────┐
                │ Verify Location           │
                │ /shared/verify-location   │
                └───────────────┬───────────┘
                                │
                                ▼
                ┌───────────────────────────┐
                │ Get GPS Coordinates       │
                │ - Request location access │
                │ - Validate against        │
                │   assigned location       │
                │ - Check radius (100m)     │
                └───────────────┬───────────┘
                                │
                     ┌──────────┴──────────┐
                     │                     │
                     ▼                     ▼
            ┌───────────────┐     ┌───────────────────┐
            │ Location OK   │     │ Location FAILED   │
            └───────┬───────┘     │ - Show error      │
                    │             │ - Cannot proceed  │
                    │             └───────────────────┘
                    ▼
        ┌───────────────────────────┐
        │ Verify Face               │
        │ /shared/verify-face       │
        └───────────────┬───────────┘
                        │
                        ▼
        ┌───────────────────────────┐
        │ Face Recognition Steps    │
        │ 1. Enable Camera          │
        │ 2. Detect Face            │
        │ 3. Liveness Detection     │
        │    - Check stable face    │
        │    - Smile detection      │
        │ 4. Capture & Extract      │
        │    embedding (ArcFace)    │
        │ 5. Compare with stored    │
        │    face data              │
        └───────────────┬───────────┘
                        │
            ┌───────────┴───────────┐
            │                       │
            ▼                       ▼
    ┌───────────────┐      ┌───────────────────┐
    │ Face Verified │      │ Face NOT Verified │
    │ Confidence    │      │ - Retry           │
    │ >= threshold  │      │ - Contact admin   │
    └───────┬───────┘      └───────────────────┘
            │
            ▼
    ┌───────────────────────────┐
    │ API: Submit Attendance    │
    │ POST /v1/attendance/      │
    │      check-in or          │
    │      check-out            │
    │                           │
    │ Payload:                  │
    │ - employee_id             │
    │ - timestamp               │
    │ - location (lat/lng)      │
    │ - face_image (base64)     │
    │ - face_confidence         │
    └───────────────┬───────────┘
                    │
                    ▼
    ┌───────────────────────────┐
    │ Backend Processing        │
    │ 1. Validate schedule      │
    │ 2. Check if working day   │
    │ 3. Determine status:      │
    │    - Present              │
    │    - Late                 │
    │    - Early departure      │
    │ 4. Save attendance record │
    │ 5. Return confirmation    │
    └───────────────────────────┘
```

### 2. Employee Schedule Flow

```
┌────────────────────────────────────────────────────────────────────────────┐
│                        EMPLOYEE SCHEDULE VIEW                               │
└────────────────────────────────────────────────────────────────────────────┘

┌───────────────────────┐
│ /employee/schedule    │
│ (Lihat Jadwal)        │
└───────────┬───────────┘
            │
            ├──────▶ [Jadwal Hari Ini] ──▶ Today's schedule & work hours
            │
            ├──────▶ [Jadwal Mingguan] ──▶ Weekly view
            │
            ├──────▶ [Jadwal Bulanan] ───▶ Monthly calendar view
            │
            └──────▶ [Teaching Schedule] ▶ (Untuk Guru)
                     - Kelas yang diajar
                     - Jam mengajar
                     - Mata pelajaran
```

### 3. Employee Leave Flow

```
┌────────────────────────────────────────────────────────────────────────────┐
│                         EMPLOYEE LEAVE FLOW                                 │
└────────────────────────────────────────────────────────────────────────────┘

┌───────────────────────┐
│ /employee/leave       │
│ (Pengajuan Cuti)      │
└───────────┬───────────┘
            │
            ├──────▶ [Lihat Saldo Cuti] ──▶ API: GET /v1/leave/balance
            │                               - Cuti tahunan
            │                               - Cuti sakit
            │                               - Cuti lainnya
            │
            ├──────▶ [Riwayat Cuti] ──────▶ API: GET /v1/leave-requests
            │                               - Status: Pending/Approved/Rejected
            │
            └──────▶ [Ajukan Cuti Baru]
                            │
                            ▼
                ┌───────────────────────────┐
                │ Form Pengajuan Cuti       │
                │ - Jenis cuti              │
                │ - Tanggal mulai           │
                │ - Tanggal selesai         │
                │ - Alasan                  │
                │ - Dokumen pendukung       │
                └───────────────┬───────────┘
                                │
                                ▼
                ┌───────────────────────────┐
                │ API: POST                 │
                │ /v1/leave-requests        │
                └───────────────┬───────────┘
                                │
                                ▼
                ┌───────────────────────────┐
                │ Status: Pending           │
                │ Menunggu approval admin   │
                └───────────────────────────┘
```

---

## 🔄 Complete Attendance Lifecycle

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│                          COMPLETE ATTENDANCE LIFECYCLE                            │
└──────────────────────────────────────────────────────────────────────────────────┘

    ADMIN SETUP                    EMPLOYEE USE                    ADMIN REVIEW
    ───────────                    ────────────                    ────────────
         │                               │                               │
         ▼                               ▼                               ▼
┌─────────────────┐            ┌─────────────────┐            ┌─────────────────┐
│ 1. Setup        │            │ 4. Check-In     │            │ 7. Review       │
│    Locations    │────────────│    Process      │────────────│    Attendance   │
│    (GPS coords) │            │                 │            │    Data         │
└─────────────────┘            └─────────────────┘            └─────────────────┘
         │                               │                               │
         ▼                               ▼                               ▼
┌─────────────────┐            ┌─────────────────┐            ┌─────────────────┐
│ 2. Register     │            │ 5. Work Period  │            │ 8. Generate     │
│    Employees    │────────────│    (Working)    │────────────│    Reports      │
│    + Face Data  │            │                 │            │    (Monthly)    │
└─────────────────┘            └─────────────────┘            └─────────────────┘
         │                               │                               │
         ▼                               ▼                               ▼
┌─────────────────┐            ┌─────────────────┐            ┌─────────────────┐
│ 3. Create       │            │ 6. Check-Out    │            │ 9. Export       │
│    Monthly      │────────────│    Process      │────────────│    (PDF/Excel)  │
│    Schedule     │            │                 │            │                 │
└─────────────────┘            └─────────────────┘            └─────────────────┘
```

---

## 🔍 Report Generation Flow

```
┌────────────────────────────────────────────────────────────────────────────┐
│                         REPORT GENERATION FLOW                              │
└────────────────────────────────────────────────────────────────────────────┘

┌───────────────────────┐
│ /admin/reports        │
└───────────┬───────────┘
            │
            ├──────▶ [Rekap Bulanan]
            │        - Pilih bulan/tahun
            │        - View attendance summary
            │        - Status: A/I/S/D/C breakdown
            │                │
            │                ▼
            │        ┌─────────────────────┐
            │        │ Export Options:     │
            │        │ - PDF               │
            │        │ - Excel (.xlsx)     │
            │        └─────────────────────┘
            │
            ├──────▶ [Riwayat]
            │        - Historical reports
            │        - Download previous exports
            │
            └──────▶ [Report Builder] ──▶ /admin/reports/builder
                     - Custom report templates
```

---

## 🛡️ Security & Two-Factor Authentication

```
┌────────────────────────────────────────────────────────────────────────────┐
│                           SECURITY FLOW                                     │
└────────────────────────────────────────────────────────────────────────────┘

┌───────────────────────┐
│ /admin/security       │
└───────────┬───────────┘
            │
            ├──────▶ [Two-Factor Auth] ──▶ /admin/security/two-factor
            │        - Setup TOTP
            │        - QR Code generation
            │        - Recovery codes
            │
            ├──────▶ [Security Events] ──▶ /admin/security/events
            │        - Login attempts
            │        - Failed authentications
            │        - Suspicious activity
            │
            └──────▶ [Device Management] ─▶ /admin/security/devices
                     - Trusted devices
                     - Revoke access
```

---

## 📱 Face Recognition Registration Flow

```
┌────────────────────────────────────────────────────────────────────────────┐
│                    FACE REGISTRATION FLOW (Admin)                           │
└────────────────────────────────────────────────────────────────────────────┘

┌───────────────────────────┐
│ /admin/face-recognition   │
└───────────────┬───────────┘
                │
                ▼
┌───────────────────────────┐
│ Employee List (tanpa wajah)│
│ - Filter: No face data    │
└───────────────┬───────────┘
                │
                ▼
┌───────────────────────────┐
│ [Register Wajah]          │
│ - Pilih pegawai           │
│ - Buka kamera             │
│ - Capture wajah           │
└───────────────┬───────────┘
                │
                ▼
┌───────────────────────────┐
│ DeepFace Service          │
│ (Python - ArcFace)        │
│ 1. Face detection         │
│ 2. Extract 512-d embedding│
│ 3. Store in database      │
└───────────────────────────┘
```

---

## 📊 Data Model Relationships

```
┌────────────────────────────────────────────────────────────────────────────┐
│                         DATA MODEL OVERVIEW                                 │
└────────────────────────────────────────────────────────────────────────────┘

    ┌─────────────┐       ┌─────────────────┐       ┌─────────────────┐
    │    User     │──1:1──│    Employee     │──1:N──│   Attendance    │
    │  (Auth)     │       │  (Data Pegawai) │       │ (Records)       │
    └─────────────┘       └────────┬────────┘       └─────────────────┘
                                   │
                    ┌──────────────┼──────────────┐
                    │              │              │
                    ▼              ▼              ▼
          ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
          │  Location   │ │EmployeeType │ │ MonthlySchdl│
          │ (Lokasi GPS)│ │ (Guru/Staff)│ │ (Jadwal)    │
          └─────────────┘ └─────────────┘ └─────────────┘
                                 │
                    ┌────────────┼────────────┐
                    │            │            │
                    ▼            ▼            ▼
          ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
          │ Department  │ │  Position   │ │TeachingSchdl│
          │ (Unit Kerja)│ │  (Jabatan)  │ │ (Untuk Guru)│
          └─────────────┘ └─────────────┘ └─────────────┘

    Other Related Models:
    - Leave / LeaveBalance / LeaveType
    - Payroll / PayrollItem / PayrollPeriod
    - Holiday / NationalHoliday
    - Subject / Classroom / Period
```

---

## 🔗 API Endpoint Summary

### Authentication

| Method | Endpoint                     | Description            |
| ------ | ---------------------------- | ---------------------- |
| POST   | `/v1/auth/login`           | Login                  |
| POST   | `/v1/auth/logout`          | Logout                 |
| GET    | `/v1/auth/me`              | Get current user       |
| POST   | `/v1/auth/forgot-password` | Request password reset |
| POST   | `/v1/auth/reset-password`  | Reset password         |

### Employees

| Method | Endpoint               | Description         |
| ------ | ---------------------- | ------------------- |
| GET    | `/v1/employees`      | List employees      |
| POST   | `/v1/employees`      | Create employee     |
| GET    | `/v1/employees/{id}` | Get employee detail |
| PUT    | `/v1/employees/{id}` | Update employee     |
| DELETE | `/v1/employees/{id}` | Delete employee     |
| POST   | `/v1/employees/bulk` | Bulk actions        |

### Attendance

| Method | Endpoint                      | Description        |
| ------ | ----------------------------- | ------------------ |
| GET    | `/v1/attendance`            | List attendance    |
| POST   | `/v1/attendance/check-in`   | Check-in           |
| POST   | `/v1/attendance/check-out`  | Check-out          |
| GET    | `/v1/attendance/status`     | Get current status |
| GET    | `/v1/attendance/statistics` | Get statistics     |

### Face Recognition

| Method | Endpoint                                | Description       |
| ------ | --------------------------------------- | ----------------- |
| POST   | `/v1/face-recognition/register`       | Register face     |
| POST   | `/v1/face-recognition/verify`         | Verify face       |
| POST   | `/v1/face/deepface/extract-embedding` | Extract embedding |
| POST   | `/v1/face/deepface/check-liveness`    | Liveness check    |

### Schedules

| Method | Endpoint                              | Description           |
| ------ | ------------------------------------- | --------------------- |
| GET    | `/v1/schedules`                     | List schedules        |
| POST   | `/v1/schedules`                     | Create schedule       |
| GET    | `/v1/monthly-schedules`             | Get monthly schedules |
| POST   | `/v1/monthly-schedules/{id}/assign` | Assign employees      |

### Leave

| Method | Endpoint                            | Description          |
| ------ | ----------------------------------- | -------------------- |
| GET    | `/v1/leave-requests`              | List leave requests  |
| POST   | `/v1/leave-requests`              | Create leave request |
| POST   | `/v1/leave-requests/{id}/approve` | Approve leave        |
| POST   | `/v1/leave-requests/{id}/reject`  | Reject leave         |

### Reports

| Method | Endpoint                      | Description     |
| ------ | ----------------------------- | --------------- |
| GET    | `/v1/reports/monthly-recap` | Monthly recap   |
| POST   | `/v1/reports/generate`      | Generate report |
| GET    | `/v1/reports/templates`     | List templates  |

---

*Document generated: December 8, 2025*
*Last updated based on codebase analysis*
