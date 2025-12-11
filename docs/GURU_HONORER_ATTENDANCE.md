# Dokumentasi Sistem Absensi Guru Honorer

## Overview

Dokumen ini menjelaskan logic khusus untuk absensi **Guru Honorer** yang berbeda dengan karyawan lain. Guru Honorer menggunakan **TeachingSchedule** sebagai patokan waktu absensi, bukan MonthlySchedule.

---

## Perbedaan dengan Karyawan Lain

| Aspek | Karyawan Biasa | Guru Honorer |
|-------|----------------|--------------|
| **Sumber Jadwal** | MonthlySchedule | TeachingSchedule |
| **Patokan Check-in** | `checkin_start_time` dari MonthlySchedule | `teaching_start_time` sesi pertama |
| **Patokan Telat** | `checkin_end_time` (batas terlambat) | `teaching_start_time` (langsung telat jika lewat) |
| **Patokan Check-out** | `checkout_start_time` & `checkout_end_time` | `teaching_end_time` sesi terakhir |
| **Pulang Cepat** | `check_out < default_end_time` | `check_out < teaching_end_time` sesi terakhir |
| **Jam Kerja** | `check_out - check_in` (range) | SUM `teaching_duration` (actual hours) |

---

## Setup oleh Admin

### 1. Buat TeachingSchedule (1x per Semester)

Admin membuat jadwal mengajar untuk semua guru di menu **"Susun Guru"**.

```
Contoh: Pak Budi (Guru Honorer Matematika)

Semester Ganjil 2024/2025:
├── effective_from: 2024-07-15
├── effective_until: 2024-12-20
│
├── Senin:
│   ├── Sesi 1: 08:00-09:00 (Matematika, Kelas 7A)
│   └── Sesi 2: 14:00-15:00 (Matematika, Kelas 8B)
│
├── Rabu:
│   └── Sesi 1: 10:00-11:00 (Matematika, Kelas 7B)
│
└── Jumat:
    └── Sesi 1: 08:00-09:00 (Matematika, Kelas 9A)
```

### 2. Tidak Perlu Setup Tambahan

| Yang TIDAK perlu dilakukan | Keterangan |
|---------------------------|------------|
| Buat MonthlySchedule per bulan | TeachingSchedule sudah cukup |
| Set `checkin_start_time`, `checkin_end_time` | Otomatis dari TeachingSchedule |
| Set `late_threshold` per guru | Langsung dari `teaching_start_time` |

---

## Logic Absensi Guru Honorer

### Check Condition

```
IF employee.employee_type === 'guru_honorer'
   THEN gunakan TeachingSchedule logic
   ELSE gunakan MonthlySchedule logic (existing)
```

### 1. Validasi Check-in

```
Kondisi bisa check-in:
├── Ada TeachingSchedule untuk hari ini
├── Waktu sekarang >= teaching_start_time - 30 menit (buffer)
│   └── Buffer 30 menit agar guru bisa absen sebelum mulai ngajar
└── Tidak ada batas akhir check-in (selalu bisa, tapi akan dihitung telat)

Contoh (Senin, sesi pertama 08:00):
├── 07:29 → TIDAK BISA check-in (terlalu pagi)
├── 07:30 → BISA check-in (30 menit sebelum sesi)
├── 08:00 → BISA check-in (tepat waktu)
├── 08:01 → BISA check-in, status: LATE
└── 10:00 → BISA check-in, status: LATE (skip sesi 1, hadir sesi 2)
```

### 2. Penentuan Status Telat

```
Rule: Telat jika check_in_time > teaching_start_time sesi pertama

TIDAK ADA TOLERANCE - langsung dari jam sesi

Contoh (sesi pertama 08:00):
├── Check-in 07:59 → status: present
├── Check-in 08:00 → status: present
├── Check-in 08:01 → status: late (telat 1 menit)
└── Check-in 08:30 → status: late (telat 30 menit)
```

### 3. Validasi Check-out

```
Kondisi bisa check-out:
├── Sudah check-in hari ini
├── Waktu sekarang >= teaching_end_time sesi terakhir - 1 menit (tolerance)
└── Tidak ada batas akhir check-out

Contoh (sesi terakhir selesai 15:00):
├── 14:58 → TIDAK BISA check-out
├── 14:59 → BISA check-out, status: early_leave
├── 15:00 → BISA check-out, status: present
└── 15:30 → BISA check-out, status: present
```

### 4. Penentuan Status Pulang Cepat

```
Rule: Pulang cepat jika check_out_time < teaching_end_time sesi terakhir

Contoh (sesi terakhir selesai 15:00):
├── Check-out 14:59 → status: early_leave
├── Check-out 15:00 → status: present (atau tetap late jika check-in telat)
└── Check-out 15:30 → status: present
```

### 5. Perhitungan Jam Kerja

```
Jam Kerja = SUM(teaching_duration) dari semua sesi yang dijadwalkan hari itu

BUKAN: check_out_time - check_in_time

Contoh Pak Budi (Senin):
├── Sesi 1: 08:00-09:00 = 1 jam
├── Sesi 2: 14:00-15:00 = 1 jam
├── Gap: 09:00-14:00 = 5 jam (TIDAK DIHITUNG)
│
├── Total Jam Kerja: 2 jam
└── BUKAN: 15:00 - 08:00 = 7 jam
```

---

## Skenario Khusus

### Skenario 1: Guru Skip Sesi Pertama

```
Jadwal Senin:
├── Sesi 1: 08:00-09:00
└── Sesi 2: 14:00-15:00

Guru check-in jam 13:30 (hanya hadir sesi 2):
├── Status: LATE (karena > 08:00)
├── Jam Kerja Terhitung: 1 jam (hanya sesi 2)
└── Sesi 1 dianggap tidak hadir
```

### Skenario 2: Guru Tidak Ada Jadwal Hari Ini

```
Guru Honorer tidak punya TeachingSchedule untuk hari Selasa:
├── can_attend: false
├── Message: "Tidak ada jadwal mengajar hari ini"
└── Tidak bisa check-in
```

### Skenario 3: Multiple Sesi dengan Gap Panjang

```
Jadwal:
├── Sesi 1: 07:00-08:00
├── Sesi 2: 12:00-13:00
└── Sesi 3: 16:00-17:00

Check-in 06:30, Check-out 17:00:
├── Total di sistem: 17:00 - 06:30 = 10.5 jam
├── Total jam kerja actual: 3 jam (1+1+1)
└── Gap time: tidak di-track (kebijakan sekolah)
```

---

## Database Schema

### TeachingSchedule (Existing)

```sql
CREATE TABLE teaching_schedules (
    id UUID PRIMARY KEY,
    teacher_id UUID REFERENCES employees(id),        -- Guru
    subject_id UUID REFERENCES subjects(id),         -- Mata Pelajaran
    day_of_week VARCHAR(10),                         -- monday, tuesday, etc
    teaching_start_time TIME,                        -- Jam mulai ngajar
    teaching_end_time TIME,                          -- Jam selesai ngajar
    effective_from DATE,                             -- Mulai berlaku
    effective_until DATE,                            -- Sampai kapan (NULL = ongoing)
    class_name VARCHAR(100),                         -- Nama kelas
    room VARCHAR(100),                               -- Ruangan
    is_active BOOLEAN DEFAULT true,
    override_attendance BOOLEAN DEFAULT true,        -- Flag untuk override
    late_threshold_minutes INT DEFAULT 15,           -- (tidak dipakai untuk guru honorer)
    ...
);
```

### Employee Check

```sql
-- Cek apakah employee adalah guru honorer
SELECT employee_type FROM employees WHERE id = ?;

-- Jika employee_type = 'guru_honorer', gunakan TeachingSchedule
```

---

## API Changes

### 1. POST /api/v1/attendance/validate-time

**Request:**
```json
{
    "type": "check_in" | "check_out"
}
```

**Response untuk Guru Honorer:**
```json
{
    "allowed": true,
    "message": "Silakan lanjutkan absensi",
    "server_time": "07:45:00",
    "schedule_type": "teaching_based",
    "teaching_sessions": [
        {
            "start_time": "08:00",
            "end_time": "09:00",
            "subject": "Matematika",
            "class_name": "7A"
        },
        {
            "start_time": "14:00",
            "end_time": "15:00",
            "subject": "Matematika",
            "class_name": "8B"
        }
    ]
}
```

**Response jika tidak ada jadwal:**
```json
{
    "allowed": false,
    "message": "Tidak ada jadwal mengajar hari ini",
    "server_time": "07:45:00",
    "schedule_type": "no_teaching"
}
```

### 2. POST /api/v1/attendance-face/check-in

**Logic tambahan untuk Guru Honorer:**
- Validasi berdasarkan TeachingSchedule
- Status `late` jika `check_in > teaching_start_time` sesi pertama
- Simpan `teaching_schedule_id` di attendance record (optional)

### 3. POST /api/v1/attendance-face/check-out

**Logic tambahan untuk Guru Honorer:**
- Validasi berdasarkan TeachingSchedule sesi terakhir
- Status `early_leave` jika `check_out < teaching_end_time` sesi terakhir
- Hitung jam kerja = SUM teaching hours

---

## Code Changes Required

### 1. AttendanceApiController.php - validateTime()

```php
// Tambah logic untuk guru honorer
if ($employee->employee_type === 'guru_honorer') {
    return $this->validateTimeForGuruHonorer($employee, $type, $now);
}
```

### 2. AttendanceService.php - determineStatus()

```php
// Tambah logic untuk guru honorer
if ($employee->employee_type === 'guru_honorer') {
    return $this->determineStatusForGuruHonorer($time, $type, $employee);
}
```

### 3. AttendanceService.php - calculateWorkingHours()

```php
// Untuk guru honorer, hitung dari teaching schedule
if ($employee->employee_type === 'guru_honorer') {
    return $this->calculateTeachingHours($employee, $attendance->date);
}
```

### 4. Employee.php - New Helper Methods

```php
// Get first teaching session for date
public function getFirstTeachingSessionForDate($date): ?TeachingSchedule

// Get last teaching session for date
public function getLastTeachingSessionForDate($date): ?TeachingSchedule

// Get total teaching hours for date
public function getTotalTeachingHoursForDate($date): float
```

---

## Testing Checklist

### Unit Tests

- [ ] Guru honorer dengan 1 sesi - check-in tepat waktu
- [ ] Guru honorer dengan 1 sesi - check-in telat
- [ ] Guru honorer dengan multiple sesi - check-in tepat waktu
- [ ] Guru honorer dengan multiple sesi - check-in telat
- [ ] Guru honorer - check-out tepat waktu
- [ ] Guru honorer - check-out pulang cepat
- [ ] Guru honorer - tidak ada jadwal hari ini
- [ ] Guru honorer - perhitungan jam kerja (1 sesi)
- [ ] Guru honorer - perhitungan jam kerja (multiple sesi)
- [ ] Karyawan biasa - tetap pakai MonthlySchedule (no regression)

### Integration Tests

- [ ] Full flow check-in guru honorer via API
- [ ] Full flow check-out guru honorer via API
- [ ] Validate-time endpoint untuk guru honorer
- [ ] Report jam kerja guru honorer

---

## Rollback Plan

Jika ada masalah, rollback dengan:

1. Revert code changes
2. Guru honorer akan fallback ke logic MonthlySchedule (existing behavior)
3. Tidak ada perubahan database schema yang breaking

---

## Future Enhancements (Out of Scope)

| Enhancement | Description | Priority |
|-------------|-------------|----------|
| Per-sesi tracking | Track kehadiran per sesi, bukan per hari | Low |
| Gap time tracking | Track waktu di antara sesi | Low |
| Teaching hours report | Report khusus jam mengajar per guru | Medium |
| Auto-generate MonthlySchedule | Generate dari TeachingSchedule | Low |

---

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2024-12-11 | 1.0 | Initial documentation |
