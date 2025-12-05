---
description: Plan untuk sistem jadwal dinamis yang terintegrasi dengan attendance
---

# 📋 Dynamic Schedule System Plan

## 🎯 Ringkasan Eksekutif

Sistem ini adalah **Attendance Management System** berbasis jadwal untuk institusi pendidikan (sekolah) dengan berbagai tipe pegawai. Core dari sistem ini adalah **Schedule Engine** yang menentukan kapan pegawai harus hadir, kapan libur, dan bagaimana validasi absensi bekerja.

---

## 📊 Analisis Struktur Saat Ini

### Model-Model Kunci

| Model | Fungsi | Status |
|-------|--------|--------|
| `EmployeeType` | Definisi tipe pegawai (mode jadwal, default jam kerja) | ✅ Lengkap |
| `MonthlySchedule` | Template jadwal bulanan | ✅ Lengkap |
| `EmployeeMonthlySchedule` | Jadwal harian per pegawai | ✅ Lengkap |
| `TeachingSchedule` | Jadwal mengajar (untuk Guru Honor) | ✅ Lengkap |
| `WeeklySchedule` | Jadwal mingguan untuk sistem akademik | ✅ Lengkap |
| `Holiday` | Hari libur nasional/lokal | ✅ Baru diintegrasikan |
| `Employee.getEffectiveScheduleForDate()` | Engine utama penentuan jadwal | ✅ Baru diperbaiki |

### Alur Kerja Saat Ini

```
┌─────────────────────────────────────────────────────────────────┐
│                    Pegawai Ingin Absen                           │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│           getEffectiveScheduleForDate($date)                     │
├─────────────────────────────────────────────────────────────────┤
│ 1. Cek EmployeeMonthlySchedule (override spesifik)               │
│ 2. Cek Holiday (libur global)                                    │
│ 3. Cek TeachingSchedule (untuk flexible employee)                │
│ 4. Fallback ke EmployeeType default                              │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│  Return: schedule_type, can_attend, start_time, end_time, etc   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🆕 Saran Peningkatan Sistem Dinamis

### 1. **Schedule Priority Engine** (Recommended)

Buat sistem prioritas yang lebih jelas:

```
Priority Level:
1. [HIGHEST] Manual Override (admin set khusus hari ini)
2. [HIGH]    Leave/Cuti (pegawai cuti)
3. [MEDIUM]  Holiday (libur nasional/lokal)
4. [LOW]     Teaching Schedule (untuk guru fleksibel)
5. [LOWEST]  EmployeeType Default (jam kerja standar)
```

**Implementasi:**

```php
// backend/app/Services/ScheduleEngine.php (NEW)

class ScheduleEngine
{
    const PRIORITY_MANUAL_OVERRIDE = 100;
    const PRIORITY_LEAVE = 90;
    const PRIORITY_HOLIDAY = 80;
    const PRIORITY_TEACHING = 70;
    const PRIORITY_DEFAULT = 10;

    public function getEffectiveSchedule(Employee $employee, Carbon $date): ScheduleResult
    {
        $scheduleSources = collect([
            $this->checkManualOverride($employee, $date),
            $this->checkLeave($employee, $date),
            $this->checkHoliday($date),
            $this->checkTeachingSchedule($employee, $date),
            $this->getDefaultSchedule($employee, $date),
        ])->filter()->sortByDesc('priority');

        return $scheduleSources->first() ?? ScheduleResult::noSchedule();
    }
}
```

### 2. **Schedule Template System** (Recommended)

Buat template yang bisa di-reuse:

```
┌─────────────────────────────────────────────────────────────────┐
│                   Schedule Templates                             │
├─────────────────────────────────────────────────────────────────┤
│ 1. Template "Standard Office"                                    │
│    - Mon-Fri: 08:00 - 16:00                                      │
│    - Sat-Sun: OFF                                                │
│                                                                  │
│ 2. Template "Shift Pagi"                                         │
│    - Mon-Sat: 06:00 - 14:00                                      │
│    - Sun: OFF                                                    │
│                                                                  │
│ 3. Template "Guru Honor"                                         │
│    - Based on TeachingSchedule                                   │
│    - Flexible per day                                            │
└─────────────────────────────────────────────────────────────────┘
```

**Database Schema:**

```sql
CREATE TABLE schedule_templates (
    id UUID PRIMARY KEY,
    name VARCHAR(255),
    type ENUM('fixed', 'shift', 'flexible'),
    config JSON, -- { work_days: [...], start_time, end_time, break_duration, etc }
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Penggunaan: employee_types.schedule_template_id
```

### 3. **Auto-Sync Holiday to Schedule** (Recommended)

Ketika libur ditambahkan, otomatis update semua jadwal terkait:

```php
// In Holiday model boot()

protected static function boot()
{
    parent::boot();

    static::created(function ($holiday) {
        // Dispatch job to update all schedules
        SyncHolidayToSchedules::dispatch($holiday);
    });
}
```

**Job `SyncHolidayToSchedules`:**

```php
class SyncHolidayToSchedules implements ShouldQueue
{
    public function handle()
    {
        // Get all EmployeeMonthlySchedule for this date
        EmployeeMonthlySchedule::whereDate('effective_date', $this->holiday->date)
            ->where('status', '!=', 'overridden') // Don't override manual overrides
            ->update([
                'status' => 'holiday',
                'is_holiday' => true,
                'override_metadata' => [
                    'holiday_id' => $this->holiday->id,
                    'holiday_name' => $this->holiday->name,
                    'synced_at' => now()
                ]
            ]);
    }
}
```

### 4. **Schedule Conflict Detection** (Important)

Deteksi konflik jadwal secara real-time:

```
Conflict Types:
1. Double Booking - Guru dijadwalkan di 2 tempat bersamaan
2. Overtime Violation - Melebihi jam kerja maksimal
3. Rest Period Violation - Kurang istirahat antar shift
4. Holiday Conflict - Dijadwalkan kerja di hari libur
```

**API Endpoint:**

```
POST /api/v1/schedules/validate
{
    "employee_id": "xxx",
    "date": "2025-12-25",
    "start_time": "08:00",
    "end_time": "16:00"
}

Response:
{
    "valid": false,
    "conflicts": [
        {
            "type": "holiday_conflict",
            "message": "Hari Raya Natal - Libur Nasional",
            "severity": "warning", // warning = bisa di-override, error = tidak bisa
            "can_override": true
        }
    ]
}
```

### 5. **Bulk Schedule Operations** (Productivity)

Operasi massal untuk efisiensi:

```
Features:
1. Generate jadwal untuk 1 bulan ke depan
2. Copy jadwal bulan lalu ke bulan ini
3. Apply template ke banyak pegawai sekaligus
4. Import jadwal dari CSV/Excel
5. Sync dengan kalender akademik
```

**API Endpoint:**

```
POST /api/v1/schedules/bulk-generate
{
    "month": 12,
    "year": 2025,
    "employee_ids": ["all"] atau ["id1", "id2"],
    "template_id": "xxx" (optional),
    "exclude_holidays": true,
    "auto_apply_leave": true
}
```

### 6. **Real-time Schedule Dashboard** (UX)

Dashboard yang menampilkan:

```
┌─────────────────────────────────────────────────────────────────┐
│  📅 Schedule Overview - Desember 2025                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────┬─────┬─────┬─────┬─────┬─────┬─────┐                     │
│  │ Sen │ Sel │ Rab │ Kam │ Jum │ Sab │ Min │                     │
│  ├─────┼─────┼─────┼─────┼─────┼─────┼─────┤                     │
│  │  1  │  2  │  3  │  4  │  5  │  6  │  7  │                     │
│  │ 50👷│ 50👷│ 50👷│ 50👷│ 50👷│ 25👷│ 🔴  │                     │
│  ├─────┼─────┼─────┼─────┼─────┼─────┼─────┤                     │
│  │ 25  │     │     │     │     │     │     │ ◀ Hari Raya Natal   │
│  │ 🔴  │     │     │     │     │     │     │                     │
│  └─────┴─────┴─────┴─────┴─────┴─────┴─────┘                     │
│                                                                  │
│  Legend: 👷 = Jumlah pegawai terjadwal, 🔴 = Libur               │
│                                                                  │
│  ⚠️ Conflicts: 3    📋 Pending Approvals: 5                      │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🛠️ Roadmap Implementasi

### Phase 1: Foundation ✅ (Sudah Selesai)

- [x] EmployeeType dengan schedule_mode
- [x] getEffectiveScheduleForDate logic
- [x] Holiday integration
- [x] Generate Holiday dari API publik
- [x] Real-time holiday check di schedule engine

### Phase 2: Automation (Recommended Next)

- [ ] Auto-sync Holiday ke EmployeeMonthlySchedule
- [ ] Schedule Template system
- [ ] Bulk schedule generation
- [ ] Schedule validation API

### Phase 3: Advanced Features

- [ ] Conflict detection engine
- [ ] Schedule suggestion/optimization
- [ ] Integration dengan Leave Management
- [ ] Notification untuk perubahan jadwal

### Phase 4: Analytics & Reporting

- [ ] Schedule compliance report
- [ ] Overtime tracking
- [ ] Attendance vs Schedule correlation
- [ ] Predictive scheduling

---

## 📝 Database Schema Updates Needed

### New Tables

```sql
-- 1. Schedule Templates
CREATE TABLE schedule_templates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE,
    type ENUM('fixed', 'shift', 'flexible', 'custom') DEFAULT 'fixed',
    work_days JSON, -- ["Mon", "Tue", ...]
    start_time TIME,
    end_time TIME,
    break_start TIME,
    break_end TIME,
    late_tolerance_minutes INT DEFAULT 15,
    early_leave_tolerance_minutes INT DEFAULT 15,
    overtime_eligible BOOLEAN DEFAULT FALSE,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP
);

-- 2. Schedule Sync Log
CREATE TABLE schedule_sync_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    sync_type ENUM('holiday', 'template', 'bulk', 'manual') NOT NULL,
    source_id UUID, -- holiday_id, template_id, etc
    affected_count INT DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT,
    metadata JSON,
    initiated_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP
);

-- 3. Schedule Conflicts
CREATE TABLE schedule_conflicts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    employee_id UUID REFERENCES employees(id) ON DELETE CASCADE,
    conflict_date DATE NOT NULL,
    conflict_type ENUM('double_booking', 'overtime', 'rest_violation', 'holiday') NOT NULL,
    severity ENUM('info', 'warning', 'error') DEFAULT 'warning',
    schedule_1_id UUID,
    schedule_2_id UUID,
    description TEXT,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_by UUID REFERENCES users(id),
    resolved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Updates to Existing Tables

```sql
-- Add to employee_types
ALTER TABLE employee_types ADD COLUMN schedule_template_id UUID REFERENCES schedule_templates(id);

-- Add to holidays
ALTER TABLE holidays ADD COLUMN auto_sync_schedules BOOLEAN DEFAULT TRUE;
ALTER TABLE holidays ADD COLUMN sync_status ENUM('pending', 'synced', 'failed') DEFAULT 'pending';
ALTER TABLE holidays ADD COLUMN synced_at TIMESTAMP;
```

---

## 🔧 Service Layer Architecture

```
app/
├── Services/
│   ├── Schedule/
│   │   ├── ScheduleEngine.php          # Core logic
│   │   ├── ScheduleValidator.php       # Conflict detection
│   │   ├── ScheduleGenerator.php       # Bulk generation
│   │   ├── ScheduleSync.php            # Sync operations
│   │   └── ScheduleTemplateManager.php # Template CRUD
│   └── Holiday/
│       └── HolidaySyncService.php      # Holiday to schedule sync
├── Jobs/
│   ├── SyncHolidayToSchedules.php
│   ├── GenerateMonthlySchedules.php
│   └── DetectScheduleConflicts.php
└── Events/
    ├── ScheduleCreated.php
    ├── ScheduleUpdated.php
    └── HolidayCreated.php
```

---

## 📌 Quick Wins (Bisa Langsung Dikerjakan)

1. **Auto-sync Holiday** - Saat libur ditambahkan, update EmployeeMonthlySchedule
2. **Schedule Preview API** - Endpoint untuk preview jadwal sebelum di-apply
3. **Conflict Warning di UI** - Tampilkan warning jika ada konflik saat input jadwal
4. **Bulk Delete Holiday** - Hapus semua libur untuk tahun tertentu
5. **Copy Schedule** - Copy jadwal dari bulan lalu

---

## ❓ Pertanyaan untuk User

1. Apakah perlu fitur **Shift Rotation** (jadwal bergantian pagi/siang/malam)?
2. Apakah ada **jam break** yang perlu ditrack (istirahat makan siang)?
3. Apakah perlu **approval workflow** untuk perubahan jadwal?
4. Berapa jumlah maksimal **jam lembur** yang diizinkan?
5. Apakah perlu integrasi dengan **Google Calendar** atau kalender lain?
