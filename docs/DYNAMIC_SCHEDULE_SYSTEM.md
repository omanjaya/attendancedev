# Sistem Jadwal Dinamis - Dokumentasi Teknis

## Gambaran Umum

Sistem absensi sekarang mendukung **mode jadwal dinamis** yang menentukan bagaimana keterlambatan dan pulang cepat dihitung berdasarkan jenis pegawai.

## Mode Jadwal

### 1. Mode `fixed` (Tetap)

- **Penggunaan:** Pegawai Tetap, Guru Tetap, Staff Tetap
- **Jam Masuk:** Sesuai jadwal bulanan (Monthly Schedule)
- **Jam Pulang:** Sesuai jadwal bulanan
- **Contoh:**
  - Jadwal: 07:30 - 15:30
  - Absen masuk 08:00 → Terlambat 30 menit
  - Absen pulang 15:00 → Pulang cepat 30 menit

### 2. Mode `flexible` (Fleksibel)

- **Penggunaan:** Guru Honor, Pegawai Honor, Instruktur Eksternal
- **Jam Masuk:** Berdasarkan jadwal mengajar PERTAMA hari itu
- **Jam Pulang:** Berdasarkan jadwal mengajar TERAKHIR hari itu
- **Jika tidak ada jadwal mengajar:** Tidak perlu absen (system tolak)
- **Contoh:**
  - Jadwal mengajar: 09:00-10:30, 11:00-12:30, 14:00-15:00
  - Absen masuk 08:50 → Tidak terlambat (sebelum 09:00)
  - Absen pulang 14:45 → Pulang cepat 15 menit (sebelum 15:00)

## Arsitektur

```
┌─────────────────────────────────────────────────────────────┐
│                    AttendanceController                      │
│  - processCheckIn()                                          │
│  - processCheckOut()                                         │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│               AttendanceScheduleService                      │
│  + getEffectiveWorkingHours(employee, date)                 │
│  + calculateCheckInLateness(employee, checkInTime)          │
│  + calculateCheckOutEarliness(employee, checkOutTime)       │
│  + canAttendToday(employee, date)                           │
└─────────────────────────┬───────────────────────────────────┘
                          │
           ┌──────────────┼──────────────┐
           ▼              ▼              ▼
┌─────────────────┐ ┌──────────────┐ ┌──────────────────┐
│  MonthlySchedule │ │TeachingSchedule│ │  EmployeeType   │
│  (Jadwal Bulanan)│ │(Jadwal Mengajar)│ │  (Mode Jadwal)  │
└─────────────────┘ └──────────────┘ └──────────────────┘
```

## File yang Termodifikasi

### Backend

1. `app/Services/AttendanceScheduleService.php` - **BARU**
   - Service untuk menghitung jam kerja efektif berdasarkan mode jadwal

2. `app/Http/Controllers/AttendanceController.php` - **MODIFIED**
   - Inject AttendanceScheduleService
   - Update processCheckIn() dan processCheckOut() untuk menggunakan service

3. `app/Http/Controllers/Api/EmployeeTypeApiController.php` - **MODIFIED**
   - Validasi schedule_mode pada store() dan update()

4. `app/Models/Employee.php` - **MODIFIED**
   - Tambah accessor employeeTypeModel untuk akses relasi

### Frontend

1. `pages/admin/settings/employee-types/index.tsx` - **MODIFIED**
   - Tambah dropdown "Mode Jadwal Absensi" di modal create/edit
   - Tambah kolom "Mode Jadwal" di tabel list

## Penggunaan API

### Endpoint Absensi

Tidak ada perubahan endpoint, tetapi response metadata berisi informasi tambahan:

```json
{
  "metadata": {
    "schedule_mode": "fixed|flexible",
    "schedule_source": "monthly_schedule|teaching_schedule|no_schedule",
    "is_late": true,
    "late_minutes": 30,
    "expected_start_time": "07:30",
    "is_early": false,
    "early_minutes": 0,
    "expected_end_time": "15:30"
  }
}
```

## Pengaturan Jenis Pegawai

### Membuat Jenis Pegawai Baru

1. Buka `/admin/settings/employee-types`
2. Klik "Tambah Jenis"
3. Isi:
   - **Nama:** Guru Honor
   - **Kode:** guru_honor
   - **Mode Jadwal Absensi:**
     - Tetap (Fixed) - untuk pegawai tetap
     - Fleksibel (Jadwal Mengajar) - untuk honor
4. Simpan

### Assign Jenis ke Pegawai

1. Edit data pegawai
2. Pilih "Jenis Pegawai" yang sudah dibuat
3. Simpan

## Testing

### Test via Tinker

```php
use App\Services\AttendanceScheduleService;
use App\Models\Employee;
use Carbon\Carbon;

$service = new AttendanceScheduleService();
$employee = Employee::first();
$now = Carbon::now('Asia/Makassar');

// Cek jam kerja efektif
$workingHours = $service->getEffectiveWorkingHours($employee, $now);

// Cek keterlambatan
$lateness = $service->calculateCheckInLateness($employee, $now);

// Cek pulang cepat
$earliness = $service->calculateCheckOutEarliness($employee, $now);
```

## Catatan Penting

1. **Timezone:** Semua waktu menggunakan `Asia/Makassar` (WITA)
2. **Tidak ada toleransi:** Keterlambatan dihitung langsung, tanpa grace period
3. **Boleh absen kapan saja:** Tidak ada blok waktu, hanya pencatatan terlambat/pulang cepat
4. **Flexible tanpa jadwal:** Jika pegawai honor tidak punya jadwal mengajar hari itu, sistem menolak absen

## TODO / Next Steps

1. [ ] Update halaman laporan untuk menampilkan info schedule_mode
2. [ ] Update dashboard pegawai honor untuk menampilkan jadwal mengajar hari ini
3. [ ] Integrasi dengan Schedule Builder untuk sinkronisasi jadwal mengajar
