# Implementation Plan: Sistem Dinamis dengan Tipe Pegawai

## Ringkasan Perubahan

Mengubah sistem dari "Department-based" menjadi "Employee Type-based" dengan:

1. Tipe Pegawai dinamis (Tetap, Honor, dll) yang bisa diatur Super Admin
2. Validasi absensi per-user berdasarkan tipe pegawai
3. Jadwal Override untuk pegawai non-tetap (Honor)
4. Fix bug jadwal yang muncul untuk karyawan tanpa assignment

---

## STATUS IMPLEMENTASI

| Fase | Status | Terakhir Update |
|------|--------|----------------|
| FASE 1: Database & Model | ✅ SELESAI | 2025-12-05 |
| FASE 2: Fix Bug Jadwal | ✅ SELESAI | 2025-12-05 |
| FASE 3: Settings Management | ✅ SELESAI | 2025-12-05 |
| FASE 4: Seed Data | ✅ SELESAI | 2025-12-05 |
| FASE 5: Update Frontend | ✅ SELESAI | 2025-12-05 |

---

## FASE 1: Database & Model Changes ✅ SELESAI

### 1.1 Perbaiki Migration `employee_types`

**File:** `backend/database/migrations/2025_12_04_221038_create_employee_types_table.php`

```php
Schema::create('employee_types', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');                    // e.g., "Pegawai Tetap", "Guru Honor"
    $table->string('code')->unique();          // e.g., "tetap", "honor"
    $table->text('description')->nullable();
    
    // Schedule behavior
    $table->enum('schedule_mode', ['fixed', 'flexible'])->default('fixed');
    // fixed = Jam kerja tetap sesuai jadwal bulanan
    // flexible = Override oleh teaching schedule
    
    // Default working hours (untuk pegawai tetap)
    $table->time('default_start_time')->nullable();  // 07:30
    $table->time('default_end_time')->nullable();    // 15:30
    
    // Attendance rules
    $table->integer('late_tolerance_minutes')->default(15);
    $table->boolean('require_schedule_for_attendance')->default(true);
    $table->boolean('can_override_by_teaching')->default(false);
    
    // Permissions/Features (JSON array of enabled features)
    $table->json('features')->nullable();
    // e.g., ["can_request_leave", "can_view_payroll", "can_substitute"]
    
    $table->boolean('is_active')->default(true);
    $table->integer('sort_order')->default(0);
    
    $table->timestamps();
    $table->softDeletes();
});
```

### 1.2 Update Migration `employees` Table

**File:** Buat migration baru `add_employee_type_id_to_employees_table.php`

```php
// Tambah foreign key ke employee_types
$table->uuid('employee_type_id')->nullable()->after('employee_type');
$table->foreign('employee_type_id')->references('id')->on('employee_types');

// Hapus kolom lama (opsional, bisa keep untuk backward compatibility)
// $table->dropColumn('employee_type');
```

### 1.3 Update Model `EmployeeType`

**File:** `backend/app/Models/EmployeeType.php`

```php
class EmployeeType extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name', 'code', 'description',
        'schedule_mode', 'default_start_time', 'default_end_time',
        'late_tolerance_minutes', 'require_schedule_for_attendance',
        'can_override_by_teaching', 'features', 'is_active', 'sort_order'
    ];

    protected $casts = [
        'default_start_time' => 'datetime:H:i',
        'default_end_time' => 'datetime:H:i',
        'features' => 'array',
        'is_active' => 'boolean',
        'require_schedule_for_attendance' => 'boolean',
        'can_override_by_teaching' => 'boolean',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    // Helper methods
    public function isFixed(): bool
    {
        return $this->schedule_mode === 'fixed';
    }

    public function isFlexible(): bool
    {
        return $this->schedule_mode === 'flexible';
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }
}
```

### 1.4 Update Model `Employee`

**File:** `backend/app/Models/Employee.php`

```php
// Tambah relationship
public function employeeType(): BelongsTo
{
    return $this->belongsTo(EmployeeType::class);
}

// Update method getEffectiveScheduleForDate
public function getEffectiveScheduleForDate($date): array
{
    $baseSchedule = $this->getScheduleForDate($date);
    
    // PENTING: Jika tidak ada jadwal yang di-assign, return none
    if (!$baseSchedule) {
        return [
            'schedule_type' => 'none',
            'can_attend' => false,  // Flag baru: tidak boleh absen
            'start_time' => null,
            'end_time' => null,
            'location_id' => null,
            'working_hours' => 0,
            'message' => 'Tidak ada jadwal yang di-assign'
        ];
    }

    // Check for holiday
    if ($baseSchedule->is_holiday || $baseSchedule->status === 'holiday') {
        return [
            'schedule_type' => 'holiday',
            'can_attend' => false,
            'start_time' => null,
            'end_time' => null,
            'location_id' => $baseSchedule->location_id,
            'working_hours' => 0,
            'holiday_name' => $baseSchedule->override_metadata['holiday_name'] ?? 'Libur'
        ];
    }

    // Untuk tipe pegawai Flexible (Honor), check teaching schedule override
    if ($this->employeeType?->isFlexible()) {
        $teachingSchedules = $this->getTeachingSchedulesForDate($date);
        
        if ($teachingSchedules->isEmpty()) {
            // Tidak ada jadwal mengajar = tidak boleh absen
            return [
                'schedule_type' => 'no_teaching',
                'can_attend' => false,
                'start_time' => null,
                'end_time' => null,
                'location_id' => $baseSchedule->location_id,
                'working_hours' => 0,
                'message' => 'Tidak ada jadwal mengajar hari ini'
            ];
        }
        
        // Ambil jam paling awal dan paling akhir dari semua jadwal mengajar
        $firstTeaching = $teachingSchedules->sortBy('teaching_start_time')->first();
        $lastTeaching = $teachingSchedules->sortByDesc('teaching_end_time')->first();
        
        return [
            'schedule_type' => 'teaching_override',
            'can_attend' => true,
            'start_time' => $firstTeaching->teaching_start_time,
            'end_time' => $lastTeaching->teaching_end_time,
            'location_id' => $baseSchedule->location_id,
            'working_hours' => $teachingSchedules->sum('teaching_duration_hours'),
            'teaching_schedules' => $teachingSchedules->toArray(),
            'late_tolerance' => $this->employeeType->late_tolerance_minutes
        ];
    }

    // Default: Pegawai Tetap - pakai jadwal bulanan
    return [
        'schedule_type' => 'base_schedule',
        'can_attend' => true,
        'start_time' => $baseSchedule->start_time,
        'end_time' => $baseSchedule->end_time,
        'location_id' => $baseSchedule->location_id,
        'working_hours' => $baseSchedule->working_hours,
        'late_tolerance' => $this->employeeType?->late_tolerance_minutes ?? 15
    ];
}

// Method baru: Ambil SEMUA teaching schedules untuk satu hari
public function getTeachingSchedulesForDate($date): Collection
{
    $dayOfWeek = Carbon::parse($date)->format('l');
    
    return $this->teachingSchedules()
        ->where('day_of_week', strtolower($dayOfWeek))
        ->where('effective_from', '<=', $date)
        ->where(function($query) use ($date) {
            $query->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', $date);
        })
        ->where('is_active', true)
        ->orderBy('teaching_start_time')
        ->get();
}
```

---

## FASE 2: Fix Bug Jadwal ✅ SELESAI

### 2.1 Root Cause Analysis

Bug: Karyawan baru tanpa jadwal muncul jadwal orang lain.

**Kemungkinan penyebab:**

1. Query `EmployeeMonthlySchedule` tidak filter by `employee_id` dengan benar
2. Frontend menampilkan data schedule tanpa validasi employee ownership
3. Ada shared schedule yang salah di-assign

### 2.2 Fix di Backend

**File:** `backend/app/Http/Controllers/Api/MonthlyScheduleApiController.php`

```php
// Pastikan filter employee_id strict
public function getEmployeeSchedule($employeeId, Request $request)
{
    $employee = Employee::findOrFail($employeeId);
    
    // PENTING: Strict query dengan employee_id
    $schedules = EmployeeMonthlySchedule::query()
        ->where('employee_id', $employee->id)  // HARUS exact match
        ->when($request->month && $request->year, function($q) use ($request) {
            $startDate = Carbon::createFromDate($request->year, $request->month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            return $q->whereBetween('effective_date', [$startDate, $endDate]);
        })
        ->orderBy('effective_date')
        ->get();
    
    // Jika tidak ada jadwal, return empty array (bukan jadwal orang lain!)
    return response()->json([
        'success' => true,
        'data' => $schedules,
        'employee_id' => $employee->id,
        'has_schedule' => $schedules->isNotEmpty()
    ]);
}
```

### 2.3 Fix di AttendanceService

**File:** `backend/app/Services/AttendanceService.php`

```php
public function checkIn(
    Employee $employee,
    array $locationData,
    ?array $faceData = null,
    ?UploadedFile $photo = null,
    bool $overwrite = false
): Attendance {
    return DB::transaction(function () use ($employee, $locationData, $faceData, $photo, $overwrite) {
        
        // === VALIDASI BARU: Cek apakah boleh absen ===
        $effectiveSchedule = $employee->getEffectiveScheduleForDate(now());
        
        if (!$effectiveSchedule['can_attend']) {
            throw new \Exception($effectiveSchedule['message'] ?? 'Tidak memiliki jadwal untuk absen hari ini');
        }
        
        // ... rest of existing logic
    });
}

private function determineStatus(Carbon $time, string $type, Employee $employee): string
{
    $effectiveSchedule = $employee->getEffectiveScheduleForDate($time);
    
    if (!$effectiveSchedule['can_attend']) {
        return 'invalid'; // Tidak seharusnya terjadi, sudah diblock di checkIn
    }

    if ($type === 'check_in') {
        $scheduledTime = Carbon::parse($effectiveSchedule['start_time']);
        $graceMinutes = $effectiveSchedule['late_tolerance'] ?? 15;

        if ($time->gt($scheduledTime->addMinutes($graceMinutes))) {
            return 'late';
        }
    }

    return 'present';
}
```

---

## FASE 3: Settings Management (Super Admin) ✅ SELESAI

### 3.1 Create EmployeeTypeApiController

**File:** `backend/app/Http/Controllers/Api/EmployeeTypeApiController.php`

```php
class EmployeeTypeApiController extends Controller
{
    public function index()
    {
        $types = EmployeeType::orderBy('sort_order')->get();
        return response()->json(['success' => true, 'data' => $types]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-settings');
        
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:employee_types,code',
            'description' => 'nullable|string',
            'schedule_mode' => 'required|in:fixed,flexible',
            'default_start_time' => 'nullable|date_format:H:i',
            'default_end_time' => 'nullable|date_format:H:i',
            'late_tolerance_minutes' => 'integer|min:0|max:120',
            'require_schedule_for_attendance' => 'boolean',
            'can_override_by_teaching' => 'boolean',
            'features' => 'nullable|array',
        ]);

        $type = EmployeeType::create($validated);
        
        return response()->json(['success' => true, 'data' => $type], 201);
    }

    public function update(Request $request, EmployeeType $employeeType)
    {
        $this->authorize('manage-settings');
        
        // ... validation & update
    }

    public function destroy(EmployeeType $employeeType)
    {
        $this->authorize('manage-settings');
        
        // Check if any employees using this type
        if ($employeeType->employees()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus tipe yang masih digunakan'
            ], 400);
        }
        
        $employeeType->delete();
        return response()->json(['success' => true]);
    }
}
```

### 3.2 Frontend: Settings Page

**File:** `frontend/src/pages/admin/settings/employee-types.tsx`

Buat halaman CRUD untuk manage Employee Types dengan:

- List tipe pegawai
- Form tambah/edit dengan fields:
  - Nama & Kode
  - Mode jadwal (Fixed/Flexible)
  - Jam kerja default
  - Toleransi keterlambatan
  - Fitur yang diaktifkan (checkbox list)

---

## FASE 4: Seed Data Default ✅ SELESAI

### 4.1 Create Seeder

**File:** `backend/database/seeders/EmployeeTypeSeeder.php`

```php
class EmployeeTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            [
                'name' => 'Pegawai Tetap',
                'code' => 'tetap',
                'description' => 'Pegawai dengan jadwal kerja tetap setiap hari',
                'schedule_mode' => 'fixed',
                'default_start_time' => '07:30',
                'default_end_time' => '15:30',
                'late_tolerance_minutes' => 15,
                'require_schedule_for_attendance' => true,
                'can_override_by_teaching' => false,
                'features' => ['can_request_leave', 'can_view_payroll'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Guru Honor',
                'code' => 'honor',
                'description' => 'Guru dengan jadwal mengajar fleksibel',
                'schedule_mode' => 'flexible',
                'default_start_time' => null,
                'default_end_time' => null,
                'late_tolerance_minutes' => 15,
                'require_schedule_for_attendance' => true,
                'can_override_by_teaching' => true,
                'features' => ['can_request_leave', 'can_substitute'],
                'sort_order' => 2,
            ],
            [
                'name' => 'Magang',
                'code' => 'magang',
                'description' => 'Pegawai magang dengan periode terbatas',
                'schedule_mode' => 'fixed',
                'default_start_time' => '08:00',
                'default_end_time' => '16:00',
                'late_tolerance_minutes' => 10,
                'require_schedule_for_attendance' => true,
                'can_override_by_teaching' => false,
                'features' => [],
                'sort_order' => 3,
            ],
        ];

        foreach ($types as $type) {
            EmployeeType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
```

---

## FASE 5: Update Frontend ✅ SELESAI

### 5.1 Update Employee Form

Ganti dropdown "Department" dengan "Tipe Pegawai" yang diambil dari API.

### 5.2 Update Schedule Display

Di halaman jadwal karyawan:

- Jika tidak ada jadwal = kosong (tanpa badge)
- Jika ada jadwal = tampilkan sesuai tipe:
  - Fixed: Jam dari jadwal bulanan
  - Flexible: Jam dari teaching schedule

### 5.3 Update Attendance Flow

Di halaman absensi:

- Cek `can_attend` dari effective schedule
- Jika `false`, tampilkan pesan dan disable tombol absen

---

## Timeline Implementasi

| Fase | Durasi Estimasi | Prioritas |
|------|-----------------|-----------|
| Fase 1: Database & Model | 2-3 jam | HIGH |
| Fase 2: Fix Bug | 1-2 jam | HIGH |
| Fase 3: Settings API | 2-3 jam | MEDIUM |
| Fase 4: Seeder | 30 menit | MEDIUM |
| Fase 5: Frontend | 3-4 jam | MEDIUM |

**Total: ~10-12 jam kerja**

---

## Langkah Pertama

1. Backup database
2. Jalankan migration untuk update `employee_types` table
3. Jalankan seeder untuk data default
4. Update model Employee dengan relationship baru
5. Fix bug jadwal di MonthlyScheduleApiController
6. Update AttendanceService dengan validasi baru

Apakah Anda ingin saya mulai dari Fase mana?
