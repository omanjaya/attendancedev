---
description: Sistem jadwal dinamis terintegrasi dengan absensi - Import Excel, semester support
---

# Dynamic Schedule System (Terintegrasi Absensi)

Sistem ini memungkinkan import jadwal mengajar dari Excel ke database yang terintegrasi dengan sistem absensi.

## Arsitektur

```
┌─────────────────────────────────────────────────────────────────┐
│                    FRONTEND                                      │
├─────────────────────────────────────────────────────────────────┤
│  Excel Upload → Parse → Preview → Pilih Semester → Import API   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    BACKEND API                                   │
├─────────────────────────────────────────────────────────────────┤
│  POST /api/v1/schedules/teaching/match-teachers                 │
│  POST /api/v1/schedules/teaching/bulk-import                    │
│  GET  /api/v1/schedules/teaching                                │
│  DELETE /api/v1/schedules/teaching/clear                        │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    DATABASE                                      │
├─────────────────────────────────────────────────────────────────┤
│  teaching_schedules → employees, subjects                       │
│  (effective_from, effective_until untuk semester)               │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    ATTENDANCE INTEGRATION                        │
├─────────────────────────────────────────────────────────────────┤
│  Employee.getEffectiveScheduleForDate()                         │
│  → Guru Tetap: Jadwal bulanan                                   │
│  → Guru Honor: Override dari TeachingSchedule                   │
│    - Check-in: Jam mengajar PERTAMA                             │
│    - Check-out: Jam mengajar TERAKHIR                           │
└─────────────────────────────────────────────────────────────────┘
```

## Logika Absensi

### Pegawai Tetap

- Jadwal mengikuti jadwal bulanan (misal: 07:30-15:30)
- Telat = check-in setelah jam masuk jadwal bulanan

### Pegawai Honor/Kontrak

- Di-assign jadwal bulanan (07:30-15:30)
- **OVERRIDE** oleh jadwal mengajar dari TeachingSchedule
- Contoh:
  - Jadwal bulanan: 07:30-15:30
  - Jadwal mengajar:
    - Sesi 1: 08:00-09:30 (Kelas 7A)
    - Sesi 2: 11:00-12:30 (Kelas 8B)
    - Sesi 3: 14:00-15:30 (Kelas 9C)
  - **Hasil:**
    - Check-in jam 07:45 → ✅ TIDAK TELAT (sesi pertama 08:00)
    - Check-in jam 08:15 → ❌ TELAT (sudah lewat 08:00)
    - Check-out ≥ 15:30 → ✅ OK (sesi terakhir 15:30)

## Format Excel yang Didukung

### Sheet "KODE GURU"

| No | Nama Guru | Jabatan/Mapel |
|----|-----------|---------------|
| 1  | Ahmad, S.Pd | Matematika |
| 2  | Budi Santoso | IPA |
| ... | ... | ... |

### Sheet "Kelas 7" / "Kelas 8" / "Kelas 9"

Grid jadwal dengan format sel: `{KODE}-{MAPEL}`

Contoh: `6-IPA`, `27-Seni Rupa`

## Cara Penggunaan

1. Buka `/admin/schedules`
2. Klik tab **"Susun Guru"**
3. Klik tombol **"Import Excel"**
4. Upload file Excel dengan format yang sesuai
5. Pilih **Periode Semester** (preset atau custom)
6. Review preview matching guru
7. Klik **"Import ke Database"**

## API Endpoints

### Match Teachers

```
POST /api/v1/schedules/teaching/match-teachers
Body: {
  teachers: [{ code: "1", name: "Ahmad, S.Pd", subject: "Matematika" }]
}
Response: [{ code: "1", matched: true, employee_id: "uuid", employee_name: "Ahmad" }]
```

### Bulk Import

```
POST /api/v1/schedules/teaching/bulk-import
Body: {
  teachers: [...],
  schedules: [...],
  effective_from: "2024-07-15",
  effective_until: "2024-12-20",
  semester: 1,
  academic_year: "2024/2025"
}
Response: {
  matched_teachers: [...],
  unmatched_teachers: [...],
  created_schedules: 150,
  skipped_schedules: 10,
  created_subjects: ["Seni Rupa"],
  errors: []
}
```

## File Terkait

### Backend

- `backend/app/Http/Controllers/Api/ScheduleApiController.php` - Bulk import controller
- `backend/app/Models/TeachingSchedule.php` - Teaching schedule model
- `backend/app/Models/Employee.php` - getEffectiveScheduleForDate()

### Frontend

- `frontend/src/pages/admin/schedules/tabs/TeacherScheduleGridContent.tsx` - Main UI
- `frontend/src/pages/admin/schedules/tabs/ExcelScheduleImporterIntegrated.tsx` - Import component
- `frontend/src/lib/api/schedules.ts` - API functions

## Periode Semester (Preset)

| Semester | Periode |
|----------|---------|
| Semester 1 | 15 Juli - 20 Desember |
| Semester 2 | 5 Januari - 20 Juni |

## Notes

- Guru yang tidak ter-match dengan database akan dilewati
- Periode waktu mengajar default:
  - Jam 1: 07:30-08:10
  - Jam 2: 08:10-08:50
  - ...dst
- Subject baru akan dibuat otomatis jika belum ada
