# FLOW PHASE 5: Scheduling & Teaching Management

> **Complete Flow Documentation**: Work Schedules, Teaching Schedules, Grade Schedule Builder, Schedule Overrides, dan Integrasi dengan Attendance System

**Last Updated**: 2025-12-20
**Version**: 1.0
**Status**: Production Ready

---

## Table of Contents

1. [Overview](#1-overview)
2. [Work Schedule Management](#2-work-schedule-management)
3. [Teaching Schedule (Jadwal Mengajar)](#3-teaching-schedule-jadwal-mengajar)
4. [Grade Schedule Builder](#4-grade-schedule-builder)
5. [Teaching Schedule for Teachers](#5-teaching-schedule-for-teachers)
6. [Schedule Override System](#6-schedule-override-system)
7. [Attendance Integration](#7-attendance-integration)
8. [Database Schema](#8-database-schema)
9. [API Endpoints Reference](#9-api-endpoints-reference)
10. [Frontend Components](#10-frontend-components)
11. [Backend Services](#11-backend-services)
12. [Business Rules](#12-business-rules)
13. [Common Workflows](#13-common-workflows)

---

## 1. Overview

Sistem scheduling di aplikasi attendance management ini memiliki 3 layer utama:

### Layer Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    ATTENDANCE LAYER                          │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Attendances (Face Recognition, GPS, Check In/Out)    │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                           ▲
                           │ Validates against
                           │
┌─────────────────────────────────────────────────────────────┐
│              SCHEDULE OVERRIDE LAYER (Layer 3)               │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  TeachingSchedule (Guru Honorer Override)             │ │
│  │  - Override attendance time for teachers              │ │
│  │  - Teaching sessions per class                        │ │
│  │  - Subject assignments                                │ │
│  └────────────────────────────────────────────────────────┘ │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  NationalHolidays (Holiday Override)                  │ │
│  │  - National, Regional, School holidays                │ │
│  │  - Auto-override schedules                            │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                           ▲
                           │ Overrides
                           │
┌─────────────────────────────────────────────────────────────┐
│           DAILY SCHEDULE LAYER (Layer 2)                     │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  EmployeeMonthlySchedule (Daily Assignments)          │ │
│  │  - One record per employee per day                    │ │
│  │  - Effective schedule after all overrides             │ │
│  │  - Linked to attendance records                       │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                           ▲
                           │ Generated from
                           │
┌─────────────────────────────────────────────────────────────┐
│           BASE SCHEDULE LAYER (Layer 1)                      │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  MonthlySchedule (Base Template)                      │ │
│  │  - Default working hours                              │ │
│  │  - Working days pattern                               │ │
│  │  - Check in/out windows                               │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Key Features

- **Multi-layered schedule system** dengan inheritance dan override
- **Monthly schedule templates** untuk base schedule
- **Teaching schedules** untuk guru dengan override attendance
- **Grade Schedule Builder** - Excel-like interface untuk susun jadwal per kelas
- **Auto-save functionality** dengan debounce 2 detik
- **Holiday management** dengan auto-override
- **Schedule conflict detection** untuk mencegah double booking
- **Real-time validation** untuk teaching schedules
- **Excel import/export** untuk bulk operations

---

## 2. Work Schedule Management

### 2.1 Monthly Schedule (Base Template)

**Tabel**: `monthly_schedules`

Monthly schedule adalah template dasar yang mendefinisikan:
- Jam kerja default untuk satu bulan
- Working days (hari kerja) dalam bulan tersebut
- Check-in/check-out windows
- Location assignment

#### Database Fields

```sql
monthly_schedules:
  - id (UUID)
  - name (string) - "Jadwal Januari 2025"
  - month (int 1-12)
  - year (int)
  - start_date (date) - Awal bulan
  - end_date (date) - Akhir bulan
  - default_start_time (time) - Default jam masuk (08:00)
  - default_end_time (time) - Default jam pulang (16:00)
  - checkin_start_time (time) - Window check-in mulai (06:00)
  - checkin_end_time (time) - Window check-in berakhir (10:00)
  - checkout_start_time (time) - Window check-out mulai (14:00)
  - checkout_end_time (time) - Window check-out berakhir (20:00)
  - working_days (JSON array) - ["2025-01-02", "2025-01-03", ...]
  - total_working_days (int)
  - location_id (UUID) - Default location
  - is_active (boolean)
  - description (text)
  - metadata (JSON)
```

#### Working Days Template Options

System menyediakan template untuk generate working days:

1. **Standard 5 Days** (Senin-Jumat)
2. **Uniform 5 Days** (Jam kerja sama setiap hari)
3. **Half Day Saturday** (Sabtu setengah hari)
4. **Custom** (Pilih hari manual)

#### Backend Logic

```php
// app/Models/MonthlySchedule.php

public function assignEmployee(Employee $employee): bool
{
    // Generate EmployeeMonthlySchedule for each working day
    foreach ($this->working_days as $dateString) {
        EmployeeMonthlySchedule::create([
            'employee_id' => $employee->id,
            'monthly_schedule_id' => $this->id,
            'effective_date' => $dateString,
            'start_time' => $this->default_start_time,
            'end_time' => $this->default_end_time,
            'location_id' => $this->location_id,
            'scheduled_hours' => $this->working_hours,
            'status' => 'active',
        ]);
    }

    return true;
}

public function applyHolidayOverrides(): int
{
    // Auto-override schedules with holidays
    $holidays = NationalHoliday::whereBetween(
        'holiday_date',
        [$this->start_date, $this->end_date]
    )->get();

    foreach ($holidays as $holiday) {
        $this->employeeSchedules()
            ->where('effective_date', $holiday->holiday_date)
            ->update([
                'status' => 'holiday',
                'is_holiday' => true,
            ]);
    }
}
```

### 2.2 Employee Monthly Schedule (Daily Assignments)

**Tabel**: `employee_monthly_schedules`

Record harian per employee yang di-generate dari monthly schedule template.

#### Key Features

- **One record per employee per day**
- **Effective schedule** setelah semua override
- **Status tracking**: active, overridden, holiday, leave, suspended
- **Override metadata** untuk audit trail
- **Linked to attendance** untuk validasi

#### Database Fields

```sql
employee_monthly_schedules:
  - id (UUID)
  - monthly_schedule_id (UUID) - Reference ke template
  - employee_id (UUID)
  - effective_date (date) - Tanggal spesifik
  - start_time (time) - Jam masuk (bisa di-override)
  - end_time (time) - Jam pulang (bisa di-override)
  - location_id (UUID)
  - status (enum) - active, overridden, holiday, leave, suspended
  - override_metadata (JSON) - Tracking perubahan
  - scheduled_hours (decimal)
  - is_weekend (boolean)
  - is_holiday (boolean)
  - attendance_id (UUID) - Link ke attendance record
  - assigned_by (user_id)
  - modified_by (user_id)
```

#### Override Metadata Structure

```json
{
  "override_type": "teaching|holiday|manual",
  "override_reason": "Teaching schedule for Guru Honorer",
  "original_start_time": "08:00:00",
  "original_end_time": "16:00:00",
  "override_by": "user-uuid",
  "override_at": "2025-01-15 10:30:00",
  "teaching_schedule_id": "uuid",
  "holiday_id": "uuid"
}
```

---

## 3. Teaching Schedule (Jadwal Mengajar)

### 3.1 Overview

Teaching Schedule adalah jadwal mengajar untuk guru yang dapat **override attendance schedule** untuk Guru Honorer (guru tidak tetap).

**Use Case**:
- Guru Honorer hanya perlu absen saat ada jadwal mengajar
- Attendance time mengikuti jam mengajar (bukan jam kerja kantor)
- Validasi terlambat berdasarkan waktu mulai mengajar

### 3.2 Database Schema

**Tabel**: `teaching_schedules`

```sql
teaching_schedules:
  - id (UUID)
  - teacher_id (UUID) - Reference ke employees
  - subject_id (UUID) - Mata pelajaran
  - class_id (UUID, nullable) - Academic class
  - day_of_week (enum) - monday, tuesday, ..., sunday
  - teaching_start_time (time) - Jam mulai mengajar
  - teaching_end_time (time) - Jam selesai mengajar
  - effective_from (date) - Berlaku dari tanggal
  - effective_until (date, nullable) - Berlaku sampai (null = indefinite)
  - class_name (string) - Nama kelas (e.g., "7A", "8B")
  - room (string) - Ruang kelas
  - student_count (int)
  - is_active (boolean)
  - status (enum) - scheduled, cancelled, rescheduled, substituted

  # Override Settings
  - override_attendance (boolean) - Enable override untuk Guru Honorer
  - strict_timing (boolean) - Enforce strict timing
  - late_threshold_minutes (int) - Batas keterlambatan (default 15)

  # Links
  - monthly_schedule_id (UUID, nullable)

  # Substitute Teacher
  - substitute_teacher_id (UUID, nullable)
  - substitution_start_date (date, nullable)
  - substitution_end_date (date, nullable)
  - substitution_reason (text)

  # Metadata
  - metadata (JSON)

  # Audit
  - created_by, updated_by
  - timestamps, soft_deletes
```

### 3.3 Teaching Schedule Metadata

```json
{
  "curriculum": "K13",
  "semester": 1,
  "academic_year": "2024/2025",
  "grade": "7",
  "period": 3,
  "lesson_plan_id": "uuid",
  "teaching_method": "online|offline|hybrid",
  "special_requirements": ["projector", "lab"],
  "teacher_code": "G001",
  "is_locked": false
}
```

### 3.4 Backend Logic

```php
// app/Models/TeachingSchedule.php

/**
 * Check if this teaching schedule can override attendance for employee
 */
public function canOverrideAttendance(Employee $employee, Carbon $date): bool
{
    // Only applies to Guru Honorer
    if ($employee->employee_type !== 'guru_honorer') {
        return false;
    }

    // Must be active and override enabled
    if (!$this->is_active || !$this->override_attendance) {
        return false;
    }

    // Must be effective for the date
    if (!$this->isEffectiveForDate($date)) {
        return false;
    }

    // Must be the correct day of week
    return strtolower($date->format('l')) === $this->day_of_week;
}

/**
 * Apply to employee schedules (auto-called on create/update)
 */
public function applyToEmployeeSchedules(): int
{
    if (!$this->override_attendance) {
        return 0;
    }

    $applied = 0;
    $current = $this->effective_from->copy();

    while ($current->lte($this->effective_until ?? Carbon::today()->addMonths(6))) {
        if (strtolower($current->format('l')) === $this->day_of_week) {
            $employeeSchedule = EmployeeMonthlySchedule::forEmployee($this->teacher_id)
                ->forDate($current)
                ->first();

            if ($employeeSchedule) {
                $employeeSchedule->update([
                    'start_time' => $this->teaching_start_time,
                    'end_time' => $this->teaching_end_time,
                    'status' => 'overridden',
                    'scheduled_hours' => $this->teaching_duration_hours,
                    'override_metadata' => [
                        'override_type' => 'teaching',
                        'teaching_schedule_id' => $this->id,
                        // ... other metadata
                    ]
                ]);
                $applied++;
            }
        }

        $current->addDay();
    }

    return $applied;
}

/**
 * Get conflicting schedules (untuk prevent double booking)
 */
public function getConflictingSchedules(): array
{
    return static::where('teacher_id', $this->teacher_id)
        ->where('day_of_week', $this->day_of_week)
        ->where('id', '!=', $this->id)
        ->active()
        ->forTimeRange(
            $this->teaching_start_time,
            $this->teaching_end_time
        )
        ->get()
        ->toArray();
}

/**
 * Calculate teacher workload
 */
public function calculateTeacherWorkload(): array
{
    $weeklySchedules = static::forTeacher($this->teacher_id)
        ->active()
        ->scheduled()
        ->effective()
        ->get();

    $totalHoursPerWeek = $weeklySchedules->sum('teaching_duration_hours');

    return [
        'total_hours_per_week' => $totalHoursPerWeek,
        'total_classes' => $weeklySchedules->count(),
        'subject_breakdown' => $weeklySchedules->groupBy('subject_id'),
        'is_overloaded' => $totalHoursPerWeek > 40
    ];
}
```

---

## 4. Grade Schedule Builder

### 4.1 Overview

**Grade Schedule Builder** adalah UI Excel-like untuk menyusun jadwal mengajar per grade (kelas 7, 8, 9).

**Fitur Utama**:
- ✅ Excel-like grid interface
- ✅ Drag & drop teachers ke time slots
- ✅ Auto-save dengan debounce 2 detik
- ✅ Excel import/export
- ✅ JSON export/import untuk backup
- ✅ Real-time conflict detection
- ✅ Lock/unlock cells
- ✅ Swap functionality
- ✅ Keyboard shortcuts (Ctrl+S, Esc, Delete, 1-9)
- ✅ Status bar dengan last saved time
- ✅ Multi-grade tabs (Grade 7, 8, 9)

### 4.2 Frontend Architecture

**Path**: `/opt/attendancedev/frontend/src/pages/admin/schedules/grade-builder/`

```
grade-builder/
├── index.tsx                    # Main component
├── GradeScheduleGrid.tsx        # Grid display
├── TeacherSidebar.tsx           # Teacher list
├── ExcelImportDialog.tsx        # Excel import
├── StatusBar.tsx                # Auto-save status
├── constants.ts                 # Time slots, days, etc.
└── [other components]
```

#### State Management (Zustand)

```typescript
// stores/grade-schedule-store.ts

interface GradeScheduleStore {
  // State
  grids: Record<string, Record<string, Record<string, CellData>>>; // grade -> className -> rowKey -> cell
  teachers: Map<string, Teacher>;
  activeGrade: string;
  activeTeacher: Teacher | null;
  selectedCell: { grade: string; className: string; rowKey: string } | null;
  swapSource: { grade: string; className: string; rowKey: string } | null;
  hasUnsavedChanges: boolean;
  dirtyGrades: Set<string>;
  metadata: {
    academicYear: string;
    semester: 1 | 2;
    effectiveFrom: string;
    effectiveUntil?: string;
  };

  // Actions
  setCell(grade: string, className: string, rowKey: string, data: CellData): void;
  clearCell(grade: string, className: string, rowKey: string): void;
  swapCells(source: CellLocation, target: CellLocation): void;
  toggleLock(grade: string, className: string, rowKey: string): void;

  addTeacher(teacher: Teacher): void;
  setActiveTeacher(teacher: Teacher | null): void;

  setMetadata(metadata: Partial<Metadata>): void;
  markSaved(): void;
  getDirtyGrades(): string[];

  resetGrade(grade: string): void;
  resetAll(): void;
}

interface CellData {
  teacherCode: string;
  subject?: string;
  isLocked?: boolean;
  teachingScheduleId?: string; // Link ke backend record
}
```

### 4.3 Grid Structure

Grid menggunakan row key format: `{day}-{period}`

```typescript
const DAYS = ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];
const PERIODS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]; // Periods (jam pelajaran)

// Example row keys:
// "senin-1"  -> Senin, Jam 1
// "selasa-3" -> Selasa, Jam 3
// "jumat-8"  -> Jumat, Jam 8
```

**Time Slots Definition**:

```typescript
const TIME_SLOTS = [
  { period: 1, start: '07:00', end: '07:45', label: 'Jam 1' },
  { period: 2, start: '07:45', end: '08:30', label: 'Jam 2' },
  { period: 3, start: '08:30', end: '09:15', label: 'Jam 3' },
  { period: 4, start: '09:15', end: '10:00', label: 'Jam 4', isBreak: true }, // Istirahat 1
  { period: 5, start: '10:00', end: '10:45', label: 'Jam 5' },
  { period: 6, start: '10:45', end: '11:30', label: 'Jam 6' },
  { period: 7, start: '11:30', end: '12:15', label: 'Jam 7' },
  { period: 8, start: '12:15', end: '13:00', label: 'Jam 8', isBreak: true }, // Istirahat 2
  { period: 9, start: '13:00', end: '13:45', label: 'Jam 9' },
  { period: 10, start: '13:45', end: '14:30', label: 'Jam 10' },
  { period: 11, start: '14:30', end: '15:15', label: 'Jam 11' },
  { period: 12, start: '15:15', end: '16:00', label: 'Jam 12' },
];
```

### 4.4 Auto-Save Mechanism

```typescript
// hooks/use-auto-save.ts

interface AutoSaveOptions {
  debounceMs: number;        // Default 2000ms
  maxRetries: number;        // Default 3
  retryDelayMs: number;      // Default 1000ms
  onSave: () => Promise<void>;
  onError?: (error: Error) => void;
}

const useAutoSave = (enabled: boolean, options: AutoSaveOptions) => {
  const [status, setStatus] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
  const [lastSavedAt, setLastSavedAt] = useState<Date | null>(null);
  const [retryCount, setRetryCount] = useState(0);
  const [error, setError] = useState<Error | null>(null);

  // Debounced save
  const debouncedSave = useMemo(
    () => debounce(async () => {
      if (!enabled) return;

      setStatus('saving');
      try {
        await options.onSave();
        setStatus('saved');
        setLastSavedAt(new Date());
        setRetryCount(0);
      } catch (err) {
        if (retryCount < options.maxRetries) {
          setRetryCount(r => r + 1);
          setTimeout(() => debouncedSave(), options.retryDelayMs);
        } else {
          setStatus('error');
          setError(err);
          options.onError?.(err);
        }
      }
    }, options.debounceMs),
    [enabled, retryCount]
  );

  // Trigger on changes
  useEffect(() => {
    if (enabled) {
      debouncedSave();
    }
  }, [enabled]);

  return {
    status,
    lastSavedAt,
    retryCount,
    error,
    saveNow: () => debouncedSave.flush(),
  };
};
```

### 4.5 API Integration

#### Save Grade Schedule

**Endpoint**: `POST /api/v1/schedules/teaching/save-grade`

```typescript
interface SaveGradeScheduleRequest {
  grade: string; // "7", "8", "9"
  academic_year: string; // "2024/2025"
  semester: 1 | 2;
  effective_from: string; // "2025-01-15"
  effective_until?: string; // "2025-06-30" (optional)
  schedules: Array<{
    class_name: string; // "7A", "7B", etc.
    day: string; // "monday", "tuesday", ...
    period: number; // 1-12
    time_start: string; // "07:00"
    time_end: string; // "07:45"
    teacher_id?: string; // UUID
    teacher_code?: string; // "G001"
    subject?: string; // "Matematika"
    is_locked?: boolean;
  }>;
}

interface SaveGradeScheduleResponse {
  grade: string;
  created: number;
  updated: number;
  deleted: number;
  errors: Array<{
    entry: ScheduleEntry;
    error: string;
  }>;
}
```

**Backend Processing**:

```php
// app/Http/Controllers/Api/ScheduleApiController.php

public function saveGradeSchedule(Request $request)
{
    $validated = $request->validate([
        'grade' => 'required|string|in:7,8,9',
        'academic_year' => 'required|string',
        'semester' => 'required|integer|in:1,2',
        'effective_from' => 'required|date',
        'effective_until' => 'nullable|date|after_or_equal:effective_from',
        'schedules' => 'required|array',
        // ... validation rules
    ]);

    DB::beginTransaction();
    try {
        // 1. Delete existing schedules for this grade
        $classNames = collect($validated['schedules'])
            ->pluck('class_name')
            ->unique();

        TeachingSchedule::withTrashed()
            ->whereIn('class_name', $classNames)
            ->where('effective_from', $validated['effective_from'])
            ->forceDelete();

        // 2. Create new teaching schedules
        foreach ($validated['schedules'] as $entry) {
            // Skip empty cells
            if (!$entry['teacher_id'] && !$entry['teacher_code']) continue;

            // Find/create subject
            $subject = Subject::firstOrCreate(
                ['name' => $entry['subject']],
                ['code' => strtoupper(substr($entry['subject'], 0, 5))]
            );

            // Create teaching schedule
            TeachingSchedule::create([
                'teacher_id' => $teacherId,
                'subject_id' => $subject->id,
                'day_of_week' => $entry['day'],
                'teaching_start_time' => $entry['time_start'],
                'teaching_end_time' => $entry['time_end'],
                'class_name' => $entry['class_name'],
                'effective_from' => $validated['effective_from'],
                'effective_until' => $validated['effective_until'],
                'is_active' => true,
                'status' => 'scheduled',
                'override_attendance' => true, // Enable override
                'metadata' => [
                    'grade' => $validated['grade'],
                    'academic_year' => $validated['academic_year'],
                    'semester' => $validated['semester'],
                    'period' => $entry['period'],
                ],
            ]);

            $created++;
        }

        DB::commit();

        return $this->apiResponse([
            'created' => $created,
            // ...
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return $this->errorResponse($e->getMessage(), 500);
    }
}
```

#### Load Grade Schedule

**Endpoint**: `GET /api/v1/schedules/teaching/load-grade/{grade}`

```typescript
interface LoadGradeScheduleResponse {
  grade: string;
  grid: Record<string, Record<string, {
    teacherCode: string;
    teacherId: string;
    teacherName: string;
    subject: string | null;
    isLocked: boolean;
    scheduleId: string;
  }>>;
  teachers: Array<{
    id: string;
    code: string;
    name: string;
    position: string;
  }>;
  metadata: {
    academic_year: string | null;
    semester: number | null;
    effective_from: string | null;
    effective_until: string | null;
  };
  total_schedules: number;
}
```

### 4.6 Excel Import/Export

#### Excel Format

Excel file harus memiliki format:

| Hari | Jam | 7A | 7B | 7C | ... |
|------|-----|----|----|----|----|
| Senin | 1 | G001 | G002 | G003 | ... |
| Senin | 2 | G004 | G001 | G005 | ... |
| ... | ... | ... | ... | ... | ... |

- **Kolom A**: Hari (Senin, Selasa, dst)
- **Kolom B**: Jam (1, 2, 3, dst)
- **Kolom C+**: Kode guru per kelas

#### Import Process

1. User upload Excel file
2. Frontend parse dengan `xlsx` library
3. Extract teacher codes
4. Match teachers dengan existing employees
5. Build grid structure
6. User review & confirm
7. Save to store
8. Auto-save triggers API call

```typescript
// ExcelImportDialog.tsx

const handleExcelImport = async (file: File) => {
  const workbook = XLSX.read(await file.arrayBuffer());
  const sheet = workbook.Sheets[workbook.SheetNames[0]];
  const data = XLSX.utils.sheet_to_json(sheet);

  // Extract unique teacher codes
  const teacherCodes = new Set<string>();
  const gridData: GridData = {};

  for (const row of data) {
    const day = row['Hari'];
    const period = row['Jam'];

    // For each class column
    for (const className in row) {
      if (className === 'Hari' || className === 'Jam') continue;

      const teacherCode = row[className];
      if (teacherCode) {
        teacherCodes.add(teacherCode);

        const rowKey = `${day.toLowerCase()}-${period}`;
        if (!gridData[className]) gridData[className] = {};
        gridData[className][rowKey] = { teacherCode };
      }
    }
  }

  // Match teachers with backend
  const matchedTeachers = await matchTeachersFromExcel(
    Array.from(teacherCodes).map(code => ({ code, name: '' }))
  );

  // Add to store
  for (const teacher of matchedTeachers) {
    if (teacher.matched) {
      addTeacher({
        code: teacher.code,
        name: teacher.employee_name,
        employeeId: teacher.employee_id,
        // ...
      });
    }
  }

  // Update grid
  for (const className in gridData) {
    for (const rowKey in gridData[className]) {
      setCell(activeGrade, className, rowKey, gridData[className][rowKey]);
    }
  }
};
```

---

## 5. Teaching Schedule for Teachers

### 5.1 My Teaching Schedule (Jadwal Mengajar Saya)

**Path**: `/employee/teaching-schedule`

Interface untuk guru melihat jadwal mengajar mereka sendiri.

**Features**:
- Weekly calendar view
- Today's schedule highlight
- Subject badges dengan warna
- Room information
- Class names
- Statistics (total hours, classes, subjects)

### 5.2 API Endpoint

**Endpoint**: `GET /api/v1/schedules/teaching/my-schedule`

```typescript
interface MyTeachingScheduleResponse {
  schedules: Record<DayOfWeek, TeachingSession[]>; // Grouped by day
  today: {
    day_of_week: string;
    schedules: TeachingSession[];
    total_hours: number;
  };
  statistics: {
    total_sessions_per_week: number;
    total_hours_per_week: number;
    subjects_count: number;
    classes_count: number;
  };
  employee: {
    id: string;
    name: string;
    type: string; // guru_tetap, guru_honorer
    is_guru_honorer: boolean;
  };
}

interface TeachingSession {
  id: string;
  subject: {
    id: string;
    name: string;
    code: string;
  };
  day_of_week: string;
  teaching_start_time: string; // "07:00"
  teaching_end_time: string; // "08:30"
  teaching_duration_hours: number; // 1.5
  class_name: string; // "7A"
  room: string; // "Lab Komputer"
  effective_from: string;
  effective_until: string | null;
}
```

### 5.3 Backend Implementation

```php
// app/Http/Controllers/Api/ScheduleApiController.php

public function myTeachingSchedules(Request $request)
{
    $user = $request->user();
    $employee = $user->employee;

    if (!$employee) {
        return $this->errorResponse('Employee not found', 404);
    }

    // Get active teaching schedules
    $schedules = TeachingSchedule::with(['subject'])
        ->where('teacher_id', $employee->id)
        ->where('is_active', true)
        ->where('effective_from', '<=', now())
        ->where(function ($q) {
            $q->whereNull('effective_until')
              ->orWhere('effective_until', '>=', now());
        })
        ->orderBy('day_of_week')
        ->orderBy('teaching_start_time')
        ->get();

    // Group by day of week
    $grouped = $schedules->groupBy('day_of_week');

    // Calculate statistics
    $totalHoursPerWeek = $schedules->sum('teaching_duration_hours');
    $totalSessions = $schedules->count();

    // Get today's schedule
    $todayDayOfWeek = strtolower(now()->format('l'));
    $todaySchedules = $schedules->where('day_of_week', $todayDayOfWeek);

    return $this->apiResponse([
        'schedules' => $grouped,
        'today' => [
            'day_of_week' => $todayDayOfWeek,
            'schedules' => $todaySchedules,
            'total_hours' => $todaySchedules->sum('teaching_duration_hours'),
        ],
        'statistics' => [
            'total_sessions_per_week' => $totalSessions,
            'total_hours_per_week' => round($totalHoursPerWeek, 2),
            'subjects_count' => $schedules->pluck('subject_id')->unique()->count(),
            'classes_count' => $schedules->pluck('class_name')->unique()->count(),
        ],
        'employee' => [
            'id' => $employee->id,
            'name' => $employee->full_name,
            'type' => $employee->employee_type,
            'is_guru_honorer' => $employee->isGuruHonorer(),
        ],
    ]);
}
```

### 5.4 Frontend Implementation

```typescript
// pages/employee/teaching-schedule/desktop.tsx

export function DesktopTeachingSchedulePage() {
  const { data, isLoading } = useQuery({
    queryKey: ['my-teaching-schedule'],
    queryFn: getMyTeachingSchedule,
  });

  const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
  const dayLabels = {
    monday: 'Senin',
    tuesday: 'Selasa',
    wednesday: 'Rabu',
    thursday: 'Kamis',
    friday: 'Jumat',
  };

  return (
    <div className="space-y-6">
      {/* Statistics Cards */}
      <div className="grid grid-cols-4 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm">Total Jam/Minggu</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {data?.statistics.total_hours_per_week} JP
            </div>
          </CardContent>
        </Card>
        {/* More stats cards... */}
      </div>

      {/* Today's Schedule */}
      <Card>
        <CardHeader>
          <CardTitle>Jadwal Hari Ini</CardTitle>
        </CardHeader>
        <CardContent>
          {data?.today.schedules.map(session => (
            <div key={session.id} className="flex items-center gap-4 p-3 border rounded">
              <div className="text-sm font-medium">
                {session.teaching_start_time} - {session.teaching_end_time}
              </div>
              <Badge>{session.subject.name}</Badge>
              <div className="text-sm text-muted-foreground">
                Kelas {session.class_name}
              </div>
              <div className="text-sm text-muted-foreground">
                {session.room || 'No room'}
              </div>
            </div>
          ))}
        </CardContent>
      </Card>

      {/* Weekly Schedule */}
      <Card>
        <CardHeader>
          <CardTitle>Jadwal Mingguan</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-5 gap-4">
            {days.map(day => (
              <div key={day} className="space-y-2">
                <h3 className="font-semibold">{dayLabels[day]}</h3>
                <div className="space-y-2">
                  {data?.schedules[day]?.map(session => (
                    <div
                      key={session.id}
                      className="p-2 border rounded text-sm"
                    >
                      <div className="font-medium">
                        {session.teaching_start_time}
                      </div>
                      <div className="text-xs text-muted-foreground">
                        {session.subject.name}
                      </div>
                      <div className="text-xs">Kelas {session.class_name}</div>
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
```

---

## 6. Schedule Override System

### 6.1 Override Hierarchy

Schedule override system memiliki **priority hierarchy**:

```
Priority 1 (Highest): Holiday Override
  - National holidays
  - Regional holidays
  - School holidays
  ↓
Priority 2: Teaching Schedule Override
  - Only for Guru Honorer
  - Override attendance time to match teaching time
  ↓
Priority 3: Manual Override
  - Admin manual override
  - Temporary schedule changes
  ↓
Priority 4 (Lowest): Base Schedule
  - MonthlySchedule default
```

### 6.2 Holiday Override

**Tabel**: `national_holidays`

```sql
national_holidays:
  - id (UUID)
  - name (string) - "Hari Kemerdekaan Indonesia"
  - holiday_date (date)
  - type (enum) - national, regional, religious, school, custom
  - description (text)
  - is_recurring (boolean) - Annual holiday
  - is_active (boolean)
  - location_id (UUID, nullable) - For regional holidays
  - recurrence_config (JSON)
  - force_override (boolean) - Auto-override schedules
  - paid_leave (boolean) - Employees get paid
  - source (string) - manual, import, system, api
  - holiday_year (int) - For indexing
```

#### Recurrence Config

```json
{
  "frequency": "yearly",
  "day_of_month": 17,
  "month": 8,
  "day_of_week": null,
  "week_of_month": null,
  "end_date": null,
  "exceptions": ["2025-08-17"]
}
```

#### Auto-Override Process

```php
// app/Models/MonthlySchedule.php

public function applyHolidayOverrides(): int
{
    $overridden = 0;

    // Get holidays in this month
    $holidays = NationalHoliday::whereBetween(
        'holiday_date',
        [$this->start_date, $this->end_date]
    )
    ->where(function ($query) {
        $query->whereNull('location_id')
            ->orWhere('location_id', $this->location_id);
    })
    ->where('is_active', true)
    ->where('force_override', true)
    ->get();

    foreach ($holidays as $holiday) {
        // Update all employee schedules on this date
        $affected = $this->employeeSchedules()
            ->where('effective_date', $holiday->holiday_date)
            ->where('status', 'active')
            ->update([
                'status' => 'holiday',
                'is_holiday' => true,
                'override_metadata' => [
                    'override_type' => 'holiday',
                    'holiday_name' => $holiday->name,
                    'holiday_type' => $holiday->type,
                    'override_at' => now(),
                    'override_by' => 'system',
                ]
            ]);

        $overridden += $affected;
    }

    return $overridden;
}
```

### 6.3 Teaching Schedule Override

Only applies to **Guru Honorer** (non-permanent teachers).

#### Business Rules

1. **Guru Honorer** attendance time = teaching time
2. Late calculation berdasarkan `teaching_start_time` + `late_threshold_minutes`
3. Auto-applied when `override_attendance = true`
4. Overrides `EmployeeMonthlySchedule` for matching day of week

#### Implementation

```php
// app/Models/EmployeeMonthlySchedule.php

public function applyTeachingScheduleOverride(): bool
{
    // Only apply to Guru Honorer
    if ($this->employee->employee_type !== 'guru_honorer') {
        return false;
    }

    // Find matching teaching schedule
    $teachingSchedule = TeachingSchedule::where('teacher_id', $this->employee_id)
        ->where('day_of_week', strtolower($this->day_name))
        ->where('effective_from', '<=', $this->effective_date)
        ->where(function($query) {
            $query->whereNull('effective_until')
                ->orWhere('effective_until', '>=', $this->effective_date);
        })
        ->where('is_active', true)
        ->where('override_attendance', true)
        ->first();

    if (!$teachingSchedule) {
        return false;
    }

    // Apply override
    $this->update([
        'start_time' => $teachingSchedule->teaching_start_time,
        'end_time' => $teachingSchedule->teaching_end_time,
        'status' => 'overridden',
        'scheduled_hours' => $teachingSchedule->teaching_duration_hours,
        'override_metadata' => [
            'override_type' => 'teaching',
            'teaching_schedule_id' => $teachingSchedule->id,
            'original_start_time' => $this->getOriginal('start_time'),
            'original_end_time' => $this->getOriginal('end_time'),
            'override_reason' => 'Teaching schedule override for Guru Honorer',
            'class_name' => $teachingSchedule->class_name,
            'subject' => $teachingSchedule->subject->name,
            'override_at' => now(),
        ]
    ]);

    return true;
}

/**
 * Get effective schedule considering all overrides
 */
public function getEffectiveSchedule(): array
{
    $schedule = [
        'employee_id' => $this->employee_id,
        'date' => $this->effective_date,
        'start_time' => $this->start_time,
        'end_time' => $this->end_time,
        'working_hours' => $this->working_hours,
        'schedule_source' => 'monthly_schedule',
    ];

    // Check for teaching schedule override (highest priority)
    if ($this->employee->isGuruHonorer()) {
        $teachingSchedule = TeachingSchedule::where('teacher_id', $this->employee_id)
            ->where('day_of_week', strtolower($this->day_name))
            ->forDate($this->effective_date)
            ->where('override_attendance', true)
            ->first();

        if ($teachingSchedule) {
            $schedule['start_time'] = $teachingSchedule->teaching_start_time;
            $schedule['end_time'] = $teachingSchedule->teaching_end_time;
            $schedule['working_hours'] = $teachingSchedule->teaching_duration_hours;
            $schedule['schedule_source'] = 'teaching_schedule';
            $schedule['teaching_schedule_id'] = $teachingSchedule->id;
            $schedule['subject'] = $teachingSchedule->subject->name;
            $schedule['class_name'] = $teachingSchedule->class_name;
        }
    }

    return $schedule;
}
```

---

## 7. Attendance Integration

### 7.1 Attendance Validation Flow

When employee submits attendance (check-in/check-out):

```
1. Get EmployeeMonthlySchedule for today
   ↓
2. Call getEffectiveSchedule() to get final schedule
   ↓
3. Validate attendance time against effective schedule
   ↓
4. Calculate late/early based on:
   - Teaching schedule (if Guru Honorer)
   - Monthly schedule (if regular employee)
   ↓
5. Create Attendance record with schedule metadata
```

### 7.2 Attendance Record Structure

```sql
attendances:
  - id (UUID)
  - employee_id (UUID)
  - date (date)
  - employee_monthly_schedule_id (UUID) - Link ke schedule
  - teaching_schedule_id (UUID, nullable) - If override by teaching
  - check_in_time (timestamp)
  - check_out_time (timestamp)
  - schedule_source (enum) - monthly_schedule, teaching_schedule
  - schedule_metadata (JSON) - Expected times
  - is_late (boolean)
  - late_minutes (int)
  - is_early_departure (boolean)
  - early_departure_minutes (int)
```

#### Schedule Metadata

```json
{
  "expected_start": "07:00:00",
  "expected_end": "14:30:00",
  "expected_hours": 7.5,
  "schedule_type": "teaching_schedule",
  "teaching_schedule_id": "uuid",
  "class_name": "7A",
  "subject": "Matematika",
  "late_threshold_minutes": 15,
  "calculated_at": "2025-01-15 06:30:00"
}
```

### 7.3 Late Calculation Logic

```php
// app/Services/AttendanceService.php

public function calculateLateStatus(Attendance $attendance): void
{
    // Get effective schedule
    $schedule = $attendance->employeeMonthlySchedule->getEffectiveSchedule();

    $expectedStart = Carbon::parse($schedule['start_time']);
    $checkInTime = Carbon::parse($attendance->check_in_time);

    // Determine late threshold
    $lateThreshold = 15; // Default 15 minutes

    if ($schedule['schedule_source'] === 'teaching_schedule') {
        // Use teaching schedule threshold
        $teachingSchedule = TeachingSchedule::find($schedule['teaching_schedule_id']);
        $lateThreshold = $teachingSchedule->late_threshold_minutes ?? 15;
    }

    // Calculate late minutes
    $lateMinutes = $checkInTime->diffInMinutes($expectedStart, false);

    if ($lateMinutes > $lateThreshold) {
        $attendance->update([
            'is_late' => true,
            'late_minutes' => $lateMinutes,
        ]);
    }
}

public function validateAttendanceWindow(Employee $employee, Carbon $time): bool
{
    // Get today's schedule
    $schedule = EmployeeMonthlySchedule::forEmployee($employee->id)
        ->forDate($time)
        ->first();

    if (!$schedule || !$schedule->is_working_day) {
        return false; // Not a working day
    }

    // Get effective schedule (after overrides)
    $effectiveSchedule = $schedule->getEffectiveSchedule();

    // Check if within attendance window
    $monthlySchedule = $schedule->monthlySchedule;
    $checkinStart = Carbon::parse($monthlySchedule->checkin_start_time);
    $checkinEnd = Carbon::parse($monthlySchedule->checkin_end_time);

    return $time->between($checkinStart, $checkinEnd);
}
```

### 7.4 Create Attendance from Schedule

```php
// app/Models/EmployeeMonthlySchedule.php

public function createAttendanceRecord(): ?Attendance
{
    // Don't create if already has attendance or not working day
    if ($this->attendance_id || !$this->is_working_day) {
        return null;
    }

    $effectiveSchedule = $this->getEffectiveSchedule();

    // Create attendance record
    $attendance = Attendance::create([
        'employee_id' => $this->employee_id,
        'date' => $this->effective_date,
        'location_id' => $this->location_id,
        'employee_monthly_schedule_id' => $this->id,
        'teaching_schedule_id' => $effectiveSchedule['teaching_schedule_id'] ?? null,
        'schedule_source' => $effectiveSchedule['schedule_source'],
        'schedule_metadata' => [
            'expected_start' => $effectiveSchedule['start_time'],
            'expected_end' => $effectiveSchedule['end_time'],
            'expected_hours' => $effectiveSchedule['working_hours'],
            'schedule_type' => $effectiveSchedule['schedule_source'],
            'calculated_at' => now(),
        ]
    ]);

    // Link attendance to schedule
    $this->update(['attendance_id' => $attendance->id]);

    return $attendance;
}
```

---

## 8. Database Schema

### 8.1 Core Tables

#### monthly_schedules

Base template untuk jadwal bulanan.

```sql
CREATE TABLE monthly_schedules (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    month INT NOT NULL CHECK (month BETWEEN 1 AND 12),
    year INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    default_start_time TIME NOT NULL,
    default_end_time TIME NOT NULL,
    checkin_start_time TIME,
    checkin_end_time TIME,
    checkout_start_time TIME,
    checkout_end_time TIME,
    working_days JSONB, -- Array of date strings
    total_working_days INT,
    location_id UUID REFERENCES locations(id),
    is_active BOOLEAN DEFAULT TRUE,
    description TEXT,
    metadata JSONB,
    created_by BIGINT REFERENCES users(id),
    updated_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,

    CONSTRAINT unique_monthly_schedule UNIQUE (name, month, year)
);

CREATE INDEX idx_monthly_schedules_active ON monthly_schedules(month, year, is_active);
CREATE INDEX idx_monthly_schedules_location ON monthly_schedules(location_id, is_active);
CREATE INDEX idx_monthly_schedules_dates ON monthly_schedules(start_date, end_date);
```

#### employee_monthly_schedules

Daily schedule per employee (generated from monthly_schedules).

```sql
CREATE TABLE employee_monthly_schedules (
    id UUID PRIMARY KEY,
    monthly_schedule_id UUID NOT NULL REFERENCES monthly_schedules(id) ON DELETE CASCADE,
    employee_id UUID NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    effective_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location_id UUID REFERENCES locations(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'overridden', 'holiday', 'leave', 'suspended')),
    override_metadata JSONB,
    scheduled_hours DECIMAL(4,2) DEFAULT 8.00,
    is_weekend BOOLEAN DEFAULT FALSE,
    is_holiday BOOLEAN DEFAULT FALSE,
    attendance_id UUID REFERENCES attendances(id) ON DELETE SET NULL,
    assigned_by BIGINT REFERENCES users(id),
    modified_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,

    CONSTRAINT unique_employee_daily_schedule UNIQUE (employee_id, effective_date)
);

CREATE INDEX idx_ems_employee_date ON employee_monthly_schedules(employee_id, effective_date);
CREATE INDEX idx_ems_schedule_date ON employee_monthly_schedules(monthly_schedule_id, effective_date);
CREATE INDEX idx_ems_date_status ON employee_monthly_schedules(effective_date, status);
CREATE INDEX idx_ems_employee_date_status ON employee_monthly_schedules(employee_id, effective_date, status);
```

#### teaching_schedules

Teaching schedule untuk guru (dapat override attendance untuk Guru Honorer).

```sql
CREATE TABLE teaching_schedules (
    id UUID PRIMARY KEY,
    teacher_id UUID NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    subject_id UUID REFERENCES subjects(id) ON DELETE CASCADE,
    class_id UUID REFERENCES academic_classes(id),
    day_of_week VARCHAR(10) NOT NULL CHECK (day_of_week IN ('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')),
    teaching_start_time TIME NOT NULL,
    teaching_end_time TIME NOT NULL,
    effective_from DATE NOT NULL,
    effective_until DATE,
    class_name VARCHAR(100),
    room VARCHAR(100),
    student_count INT,
    is_active BOOLEAN DEFAULT TRUE,
    status VARCHAR(20) DEFAULT 'scheduled' CHECK (status IN ('scheduled', 'cancelled', 'rescheduled', 'substituted')),
    override_attendance BOOLEAN DEFAULT TRUE,
    strict_timing BOOLEAN DEFAULT TRUE,
    late_threshold_minutes INT DEFAULT 15,
    monthly_schedule_id UUID REFERENCES monthly_schedules(id),
    metadata JSONB,
    substitute_teacher_id UUID REFERENCES employees(id),
    substitution_start_date DATE,
    substitution_end_date DATE,
    substitution_reason TEXT,
    created_by BIGINT REFERENCES users(id),
    updated_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,

    CONSTRAINT unique_teacher_time_slot UNIQUE (teacher_id, day_of_week, teaching_start_time, effective_from)
);

CREATE INDEX idx_ts_teacher_day ON teaching_schedules(teacher_id, day_of_week, is_active);
CREATE INDEX idx_ts_effective_dates ON teaching_schedules(effective_from, effective_until, is_active);
CREATE INDEX idx_ts_day_time ON teaching_schedules(day_of_week, teaching_start_time);
CREATE INDEX idx_ts_subject_class ON teaching_schedules(subject_id, class_name);
CREATE INDEX idx_ts_teacher_schedule ON teaching_schedules(teacher_id, day_of_week, effective_from, effective_until);
CREATE INDEX idx_ts_override ON teaching_schedules(override_attendance, is_active, teacher_id);
```

#### national_holidays

Holiday definitions dengan auto-override capability.

```sql
CREATE TABLE national_holidays (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    holiday_date DATE NOT NULL,
    type VARCHAR(20) DEFAULT 'national' CHECK (type IN ('national', 'regional', 'religious', 'school', 'custom')),
    description TEXT,
    is_recurring BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    location_id UUID REFERENCES locations(id) ON DELETE CASCADE,
    recurrence_config JSONB,
    force_override BOOLEAN DEFAULT TRUE,
    paid_leave BOOLEAN DEFAULT TRUE,
    source VARCHAR(50) DEFAULT 'manual',
    reference_code VARCHAR(100),
    metadata JSONB,
    holiday_year INT,
    created_by BIGINT REFERENCES users(id),
    updated_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,

    CONSTRAINT unique_holiday_date_scope UNIQUE (holiday_date, type, location_id)
);

CREATE INDEX idx_holidays_date ON national_holidays(holiday_date, is_active);
CREATE INDEX idx_holidays_type ON national_holidays(type, is_active);
CREATE INDEX idx_holidays_location ON national_holidays(location_id, holiday_date);
CREATE INDEX idx_holidays_year ON national_holidays(holiday_year, type);
```

### 8.2 Academic Tables (for Weekly Schedules)

#### time_slots

Jam pelajaran untuk academic schedules.

```sql
CREATE TABLE time_slots (
    id UUID PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    "order" INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    metadata JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX idx_time_slots_active_order ON time_slots(is_active, "order");
```

#### subjects

Mata pelajaran.

```sql
CREATE TABLE subjects (
    id UUID PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    weekly_hours INT DEFAULT 2,
    max_meetings_per_week INT DEFAULT 3,
    requires_lab BOOLEAN DEFAULT FALSE,
    color VARCHAR(7) DEFAULT '#3B82F6',
    is_active BOOLEAN DEFAULT TRUE,
    metadata JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX idx_subjects_category ON subjects(category, is_active);
```

#### academic_classes

Kelas akademik.

```sql
CREATE TABLE academic_classes (
    id UUID PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    grade_level VARCHAR(10) NOT NULL,
    major VARCHAR(50),
    class_number VARCHAR(10) NOT NULL,
    capacity INT DEFAULT 30,
    room VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    metadata JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    CONSTRAINT unique_class UNIQUE (grade_level, major, class_number)
);

CREATE INDEX idx_classes_grade ON academic_classes(grade_level, major, is_active);
```

#### weekly_schedules

Academic weekly schedule (alternative system, not used in teaching_schedules).

```sql
CREATE TABLE weekly_schedules (
    id UUID PRIMARY KEY,
    academic_class_id UUID NOT NULL REFERENCES academic_classes(id) ON DELETE CASCADE,
    subject_id UUID NOT NULL REFERENCES subjects(id) ON DELETE CASCADE,
    employee_id UUID NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    time_slot_id UUID NOT NULL REFERENCES time_slots(id) ON DELETE CASCADE,
    day_of_week VARCHAR(10) NOT NULL CHECK (day_of_week IN ('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday')),
    room VARCHAR(50),
    effective_from DATE NOT NULL,
    effective_until DATE,
    is_locked BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    metadata JSONB,
    created_by BIGINT REFERENCES users(id),
    updated_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    CONSTRAINT unique_class_schedule UNIQUE (academic_class_id, time_slot_id, day_of_week, effective_from)
);

CREATE INDEX idx_ws_class_day ON weekly_schedules(academic_class_id, day_of_week);
CREATE INDEX idx_ws_teacher ON weekly_schedules(employee_id, day_of_week, time_slot_id);
CREATE INDEX idx_ws_effective ON weekly_schedules(effective_from, effective_until, is_active);
```

### 8.3 Entity Relationships

```
┌─────────────────┐         ┌──────────────────────┐
│ MonthlySchedule │1──────N│EmployeeMonthlySchedule│
└─────────────────┘         └──────────────────────┘
                                      │1
                                      │
                                      │N
                            ┌──────────────┐
                            │  Attendance  │
                            └──────────────┘
                                      │N
                                      │
                                      │1
                            ┌─────────────────┐
                            │TeachingSchedule │
                            └─────────────────┘
                                      │N
                                      │
                                      │1
                            ┌─────────────┐
                            │  Employee   │
                            └─────────────┘
```

---

## 9. API Endpoints Reference

### 9.1 Monthly Schedule Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/monthly-schedules` | List monthly schedules |
| GET | `/api/v1/monthly-schedules/{id}` | Get single schedule |
| POST | `/api/v1/monthly-schedules` | Create schedule |
| PUT | `/api/v1/monthly-schedules/{id}` | Update schedule |
| DELETE | `/api/v1/monthly-schedules/{id}` | Delete schedule |
| POST | `/api/v1/monthly-schedules/{id}/assign` | Assign employees |
| POST | `/api/v1/monthly-schedules/{id}/unassign` | Unassign employee |
| POST | `/api/v1/monthly-schedules/{id}/sync` | Sync employees |
| GET | `/api/v1/monthly-schedules/{id}/employees` | Get assigned employees |
| POST | `/api/v1/monthly-schedules/generate-working-days` | Generate working days |
| GET | `/api/v1/monthly-schedules/my-schedule` | Get employee's own schedule |

### 9.2 Teaching Schedule Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/schedules/teaching` | List teaching schedules |
| GET | `/api/v1/schedules/teaching/my-schedule` | Get teacher's own schedule |
| POST | `/api/v1/schedules/teaching/save-grade` | Save grade schedule (from builder) |
| GET | `/api/v1/schedules/teaching/load-grade/{grade}` | Load grade schedule |
| POST | `/api/v1/schedules/teaching/bulk-import` | Bulk import from Excel |
| POST | `/api/v1/schedules/teaching/match-teachers` | Match teachers from Excel |
| DELETE | `/api/v1/schedules/teaching/clear` | Clear teaching schedules |

### 9.3 Academic Schedule Endpoints (WeeklySchedule)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/schedules` | List schedules |
| GET | `/api/v1/schedules/{id}` | Get schedule |
| POST | `/api/v1/schedules` | Create schedule |
| PUT | `/api/v1/schedules/{id}` | Update schedule |
| DELETE | `/api/v1/schedules/{id}` | Delete schedule |
| POST | `/api/v1/schedules/{id}/lock` | Lock schedule |
| POST | `/api/v1/schedules/{id}/unlock` | Unlock schedule |
| GET | `/api/v1/schedules/class/{classId}` | Get schedules by class |
| GET | `/api/v1/schedules/statistics` | Get statistics |
| GET | `/api/v1/schedules/conflicts` | Check conflicts |
| GET | `/api/v1/schedules/time-slots` | Get time slots |
| GET | `/api/v1/schedules/subjects` | Get subjects |
| GET | `/api/v1/schedules/classes` | Get classes |

### 9.4 Request/Response Examples

#### Create Monthly Schedule

**Request**: `POST /api/v1/monthly-schedules`

```json
{
  "name": "Jadwal Februari 2025",
  "month": 2,
  "year": 2025,
  "default_start_time": "08:00",
  "default_end_time": "16:00",
  "checkin_start_time": "06:00",
  "checkin_end_time": "10:00",
  "checkout_start_time": "14:00",
  "checkout_end_time": "20:00",
  "working_days": [
    "2025-02-03",
    "2025-02-04",
    "2025-02-05",
    "..."
  ],
  "location_id": "uuid-location",
  "is_active": true,
  "description": "Jadwal kerja bulan Februari 2025"
}
```

**Response**: `201 Created`

```json
{
  "success": true,
  "message": "Monthly schedule created",
  "data": {
    "id": "uuid-schedule",
    "name": "Jadwal Februari 2025",
    "month": 2,
    "year": 2025,
    "start_date": "2025-02-01",
    "end_date": "2025-02-28",
    "total_working_days": 20,
    "assigned_employees_count": 0,
    "created_at": "2025-01-15T10:00:00Z"
  }
}
```

#### Save Grade Schedule

**Request**: `POST /api/v1/schedules/teaching/save-grade`

```json
{
  "grade": "7",
  "academic_year": "2024/2025",
  "semester": 1,
  "effective_from": "2025-01-15",
  "effective_until": "2025-06-30",
  "schedules": [
    {
      "class_name": "7A",
      "day": "monday",
      "period": 1,
      "time_start": "07:00",
      "time_end": "07:45",
      "teacher_id": "uuid-teacher",
      "teacher_code": "G001",
      "subject": "Matematika",
      "is_locked": false
    },
    {
      "class_name": "7A",
      "day": "monday",
      "period": 2,
      "time_start": "07:45",
      "time_end": "08:30",
      "teacher_id": "uuid-teacher",
      "teacher_code": "G002",
      "subject": "Bahasa Indonesia",
      "is_locked": false
    }
  ]
}
```

**Response**: `200 OK`

```json
{
  "success": true,
  "message": "Grade 7 schedule saved successfully. Created: 45, Updated: 0, Replaced: 40",
  "data": {
    "grade": "7",
    "created": 45,
    "updated": 0,
    "deleted": 40,
    "errors": [],
    "effective_from": "2025-01-15",
    "effective_until": "2025-06-30"
  }
}
```

#### Get My Teaching Schedule

**Request**: `GET /api/v1/schedules/teaching/my-schedule`

**Response**: `200 OK`

```json
{
  "success": true,
  "message": "My teaching schedules retrieved",
  "data": {
    "schedules": {
      "monday": [
        {
          "id": "uuid",
          "subject": {
            "id": "uuid",
            "name": "Matematika",
            "code": "MTK"
          },
          "day_of_week": "monday",
          "teaching_start_time": "07:00",
          "teaching_end_time": "08:30",
          "teaching_duration_hours": 1.5,
          "class_name": "7A",
          "room": "Ruang 101",
          "effective_from": "2025-01-15",
          "effective_until": null
        }
      ],
      "tuesday": [...],
      "..."
    },
    "today": {
      "day_of_week": "monday",
      "schedules": [...],
      "total_hours": 4.5
    },
    "statistics": {
      "total_sessions_per_week": 12,
      "total_hours_per_week": 18.0,
      "subjects_count": 2,
      "classes_count": 3
    },
    "employee": {
      "id": "uuid",
      "name": "Ahmad Guru",
      "type": "guru_honorer",
      "is_guru_honorer": true
    }
  }
}
```

---

## 10. Frontend Components

### 10.1 Component Structure

```
frontend/src/
├── pages/
│   ├── admin/
│   │   └── schedules/
│   │       ├── index.tsx                  # Schedule management index
│   │       ├── calendar.tsx               # Calendar view
│   │       ├── builder.tsx                # Academic schedule builder
│   │       ├── desktop.tsx                # Desktop layout
│   │       ├── mobile.tsx                 # Mobile layout
│   │       ├── grade-builder/
│   │       │   ├── index.tsx              # Grade schedule builder main
│   │       │   ├── GradeScheduleGrid.tsx  # Excel-like grid
│   │       │   ├── TeacherSidebar.tsx     # Teacher list sidebar
│   │       │   ├── ExcelImportDialog.tsx  # Excel import
│   │       │   ├── StatusBar.tsx          # Auto-save status
│   │       │   └── constants.ts           # Time slots, days
│   │       ├── monthly/
│   │       │   ├── index.tsx              # Monthly schedule list
│   │       │   ├── create.tsx             # Create monthly schedule
│   │       │   └── edit.tsx               # Edit monthly schedule
│   │       └── tabs/
│   │           ├── ScheduleListContent.tsx
│   │           ├── ScheduleAssignContent.tsx
│   │           ├── TeacherScheduleContent.tsx
│   │           ├── MonthlyScheduleContent.tsx
│   │           └── ExcelScheduleImporter.tsx
│   └── employee/
│       ├── schedule/
│       │   ├── index.tsx                  # Employee schedule (auto-detect mobile/desktop)
│       │   ├── desktop.tsx                # Desktop version
│       │   └── mobile.tsx                 # Mobile version
│       └── teaching-schedule/
│           ├── index.tsx                  # Teaching schedule (auto-detect)
│           ├── desktop.tsx                # Desktop version
│           └── mobile.tsx                 # Mobile version
├── lib/
│   └── api/
│       └── schedules.ts                   # API client functions
├── stores/
│   └── grade-schedule-store.ts            # Zustand store for grade builder
└── hooks/
    └── use-auto-save.ts                   # Auto-save hook
```

### 10.2 Key Components

#### Grade Schedule Builder

**Path**: `/admin/schedules/grade-builder`

Features:
- Excel-like grid dengan editable cells
- Teacher drag & drop
- Auto-save (2 second debounce)
- Keyboard shortcuts
- Swap cells
- Lock/unlock
- Excel import/export
- JSON backup/restore
- Multi-grade tabs (7, 8, 9)

**Usage**:
```tsx
import GradeScheduleBuilder from '@/pages/admin/schedules/grade-builder';

<GradeScheduleBuilder />
```

#### Monthly Schedule Management

**Path**: `/admin/schedules/monthly`

Features:
- Create/edit monthly schedules
- Generate working days automatically
- Assign employees to schedules
- Apply holiday overrides
- View assigned employees
- Statistics dashboard

#### My Teaching Schedule

**Path**: `/employee/teaching-schedule`

Features:
- Weekly calendar view
- Today's schedule highlight
- Subject badges
- Room information
- Statistics (hours, classes, subjects)
- Responsive (mobile/desktop)

### 10.3 API Integration

```typescript
// lib/api/schedules.ts

// Monthly Schedules
export async function getMonthlyAttendanceSchedules(filters?: {
  month?: number;
  year?: number;
  location_id?: string;
  is_active?: boolean;
}): Promise<PaginatedResponse<MonthlyAttendanceSchedule>>;

export async function createMonthlyAttendanceSchedule(
  data: MonthlyAttendanceScheduleFormData
): Promise<MonthlyAttendanceSchedule>;

export async function assignEmployeesToSchedule(
  id: string,
  params: { employee_ids: string[] }
): Promise<AssignScheduleResponse>;

// Teaching Schedules
export async function saveGradeSchedule(
  data: SaveGradeScheduleRequest
): Promise<SaveGradeScheduleResponse>;

export async function loadGradeSchedule(
  grade: string,
  effectiveFrom?: string
): Promise<LoadGradeScheduleResponse>;

export async function getMyTeachingSchedule(): Promise<MyTeachingScheduleResponse>;

// Subjects & Classes
export async function getSubjects(): Promise<Subject[]>;
export async function getClasses(): Promise<AcademicClass[]>;
export async function getTimeSlots(): Promise<TimeSlot[]>;
```

---

## 11. Backend Services

### 11.1 Service Layer

```
app/Services/
├── ScheduleService.php              # Base schedule operations
├── ScheduleManagementService.php    # Overall schedule management
├── AttendanceScheduleService.php    # Attendance schedule integration
└── Schedule/
    ├── ScheduleService.php          # Weekly schedule service
    ├── MonthlyScheduleService.php   # Monthly schedule service
    ├── TeachingScheduleService.php  # Teaching schedule service
    └── ScheduleHelperService.php    # Helper functions
```

### 11.2 Key Service Methods

#### MonthlyScheduleService

```php
class MonthlyScheduleService
{
    /**
     * Create monthly schedule with working days
     */
    public function createMonthlySchedule(array $data): MonthlySchedule
    {
        $schedule = MonthlySchedule::create($data);

        // Auto-apply holiday overrides
        if ($schedule->is_active) {
            $schedule->applyHolidayOverrides();
        }

        return $schedule;
    }

    /**
     * Assign employees to schedule (generates daily records)
     */
    public function assignEmployees(
        MonthlySchedule $schedule,
        array $employeeIds
    ): array {
        $results = [];

        foreach ($employeeIds as $employeeId) {
            $employee = Employee::findOrFail($employeeId);
            $success = $schedule->assignEmployee($employee);

            $results[] = [
                'employee_id' => $employeeId,
                'success' => $success,
            ];
        }

        return $results;
    }

    /**
     * Sync employees (assign selected, unassign others)
     */
    public function syncEmployees(
        MonthlySchedule $schedule,
        array $employeeIds
    ): array {
        $assigned = 0;
        $unassigned = 0;

        // Unassign employees not in list
        $existingAssignments = $schedule->employeeMonthlySchedules()
            ->whereNotIn('employee_id', $employeeIds)
            ->delete();

        $unassigned = $existingAssignments;

        // Assign new employees
        foreach ($employeeIds as $employeeId) {
            $employee = Employee::find($employeeId);
            if ($employee && $schedule->assignEmployee($employee)) {
                $assigned++;
            }
        }

        return compact('assigned', 'unassigned');
    }
}
```

#### TeachingScheduleService

```php
class TeachingScheduleService
{
    /**
     * Save grade schedule from Grade Builder
     */
    public function saveGradeSchedule(array $data): array
    {
        $grade = $data['grade'];
        $created = 0;
        $updated = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            // Delete existing schedules for this grade
            $this->clearGradeSchedules($grade, $data['effective_from']);

            // Create new schedules
            foreach ($data['schedules'] as $entry) {
                try {
                    $schedule = $this->createTeachingSchedule($entry, $data);
                    $created++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'entry' => $entry,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            return compact('created', 'updated', 'errors');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get teacher workload summary
     */
    public function getTeacherWorkload(string $teacherId): array
    {
        $schedules = TeachingSchedule::forTeacher($teacherId)
            ->active()
            ->effective()
            ->get();

        $totalHours = $schedules->sum('teaching_duration_hours');
        $subjectBreakdown = $schedules->groupBy('subject_id')
            ->map(fn($s) => $s->sum('teaching_duration_hours'));

        return [
            'total_hours_per_week' => $totalHours,
            'total_classes' => $schedules->count(),
            'subject_breakdown' => $subjectBreakdown,
            'is_overloaded' => $totalHours > 40,
        ];
    }

    /**
     * Detect schedule conflicts
     */
    public function detectConflicts(TeachingSchedule $schedule): array
    {
        $conflicts = [];

        // Teacher double booking
        $teacherConflicts = TeachingSchedule::where('teacher_id', $schedule->teacher_id)
            ->where('day_of_week', $schedule->day_of_week)
            ->where('id', '!=', $schedule->id)
            ->active()
            ->forTimeRange(
                $schedule->teaching_start_time,
                $schedule->teaching_end_time
            )
            ->get();

        foreach ($teacherConflicts as $conflict) {
            $conflicts[] = [
                'type' => 'teacher_double_booking',
                'severity' => 'critical',
                'conflicting_schedule' => $conflict,
                'description' => "Teacher already has class at this time",
            ];
        }

        return $conflicts;
    }
}
```

#### AttendanceScheduleService

```php
class AttendanceScheduleService
{
    /**
     * Get effective schedule for attendance validation
     */
    public function getEffectiveScheduleForAttendance(
        Employee $employee,
        Carbon $date
    ): ?array {
        // Get base schedule from EmployeeMonthlySchedule
        $schedule = EmployeeMonthlySchedule::forEmployee($employee->id)
            ->forDate($date)
            ->first();

        if (!$schedule) {
            return null;
        }

        // Return effective schedule (after all overrides)
        return $schedule->getEffectiveSchedule();
    }

    /**
     * Validate attendance against schedule
     */
    public function validateAttendance(
        Attendance $attendance
    ): array {
        $schedule = $this->getEffectiveScheduleForAttendance(
            $attendance->employee,
            $attendance->date
        );

        if (!$schedule) {
            return [
                'valid' => false,
                'reason' => 'No schedule found for this date',
            ];
        }

        // Calculate late/early
        $expectedStart = Carbon::parse($schedule['start_time']);
        $checkInTime = Carbon::parse($attendance->check_in_time);
        $lateMinutes = $checkInTime->diffInMinutes($expectedStart, false);

        $lateThreshold = 15;
        if ($schedule['schedule_source'] === 'teaching_schedule') {
            $teachingSchedule = TeachingSchedule::find($schedule['teaching_schedule_id']);
            $lateThreshold = $teachingSchedule->late_threshold_minutes ?? 15;
        }

        return [
            'valid' => true,
            'is_late' => $lateMinutes > $lateThreshold,
            'late_minutes' => max(0, $lateMinutes),
            'schedule_source' => $schedule['schedule_source'],
        ];
    }
}
```

---

## 12. Business Rules

### 12.1 Schedule Hierarchy & Override Rules

1. **Holiday Override** (Highest Priority)
   - National holidays override all schedules
   - `force_override = true` applies automatically
   - Status changes to `holiday`, `is_holiday = true`

2. **Teaching Schedule Override**
   - Only applies to `Guru Honorer` (`employee_type = 'guru_honorer'`)
   - `override_attendance = true` required
   - Overrides attendance time to match teaching time
   - Must match `day_of_week` and `effective_date`

3. **Manual Override**
   - Admin can manually override schedules
   - Status changes to `overridden`
   - `override_metadata` tracks changes

4. **Base Schedule** (Lowest Priority)
   - From `MonthlySchedule` template
   - Default working hours

### 12.2 Teaching Schedule Rules

1. **Unique Constraint**: No teacher can have 2+ classes at same time
   - Constraint: `(teacher_id, day_of_week, teaching_start_time, effective_from)`

2. **Effective Dates**:
   - `effective_from` <= attendance_date <= `effective_until` (or null)

3. **Override Attendance**:
   - Only enabled for `Guru Honorer`
   - Auto-applies to `EmployeeMonthlySchedule` on create/update

4. **Late Threshold**:
   - Default: 15 minutes
   - Can be customized per teaching schedule
   - `strict_timing = true` enforces exact timing

5. **Substitute Teachers**:
   - Can assign substitute for specific period
   - Status changes to `substituted`
   - Attendance goes to substitute teacher

### 12.3 Monthly Schedule Rules

1. **Working Days**:
   - Must be array of date strings in `YYYY-MM-DD` format
   - Generated from template or custom selection
   - Excludes holidays automatically

2. **Employee Assignment**:
   - Creates `EmployeeMonthlySchedule` for each working day
   - One record per employee per day
   - Can assign multiple employees to same schedule

3. **Holiday Auto-Override**:
   - Runs when schedule is created/updated
   - Checks `NationalHoliday` for matching dates
   - Updates status to `holiday` for matching dates

4. **Check-In/Check-Out Windows**:
   - Check-in window: `checkin_start_time` to `checkin_end_time`
   - Check-out window: `checkout_start_time` to `checkout_end_time`
   - Attendance outside window may be flagged

### 12.4 Conflict Detection

1. **Teacher Double Booking**:
   - Same teacher, same day, overlapping time
   - Severity: **Critical**

2. **Class Double Booking** (for WeeklySchedule):
   - Same class, same day, same time slot
   - Severity: **Critical**

3. **Subject Frequency Exceeded**:
   - Subject scheduled more than `max_meetings_per_week`
   - Severity: **Medium**

4. **Same Day Subject** (for WeeklySchedule):
   - Same subject appears twice on same day for same class
   - Severity: **Low** (warning)

### 12.5 Validation Rules

#### Monthly Schedule

```php
[
    'name' => 'required|string|max:255',
    'month' => 'required|integer|min:1|max:12',
    'year' => 'required|integer|min:2024|max:2030',
    'start_date' => 'required|date',
    'end_date' => 'required|date|after_or_equal:start_date',
    'default_start_time' => 'required|date_format:H:i',
    'default_end_time' => 'required|date_format:H:i|after:default_start_time',
    'working_days' => 'required|array',
    'location_id' => 'required|uuid|exists:locations,id',
]
```

#### Teaching Schedule

```php
[
    'teacher_id' => 'required|uuid|exists:employees,id',
    'subject_id' => 'required|uuid|exists:subjects,id',
    'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
    'teaching_start_time' => 'required|date_format:H:i',
    'teaching_end_time' => 'required|date_format:H:i|after:teaching_start_time',
    'effective_from' => 'required|date',
    'effective_until' => 'nullable|date|after_or_equal:effective_from',
    'class_name' => 'required|string|max:100',
    'late_threshold_minutes' => 'required|integer|min:1|max:120',
]
```

---

## 13. Common Workflows

### 13.1 Create Monthly Schedule & Assign Employees

**Step 1**: Create Monthly Schedule

```bash
POST /api/v1/monthly-schedules
{
  "name": "Jadwal Februari 2025",
  "month": 2,
  "year": 2025,
  "default_start_time": "08:00",
  "default_end_time": "16:00",
  "working_days": ["2025-02-03", "2025-02-04", ...],
  "location_id": "uuid"
}
```

**Step 2**: Generate Working Days (if needed)

```bash
POST /api/v1/monthly-schedules/generate-working-days
{
  "month": 2,
  "year": 2025,
  "working_day_pattern": ["monday", "tuesday", "wednesday", "thursday", "friday"]
}
# Returns: { "working_days": [...], "total_working_days": 20 }
```

**Step 3**: Assign Employees

```bash
POST /api/v1/monthly-schedules/{id}/assign
{
  "employee_ids": ["uuid1", "uuid2", "uuid3"]
}
# Creates EmployeeMonthlySchedule for each employee for each working day
```

**Step 4**: Apply Holiday Overrides (optional)

```bash
POST /api/v1/monthly-schedules/{id}/apply-holiday-overrides
# Auto-marks holidays based on NationalHoliday table
```

### 13.2 Build Grade Schedule with Excel-like Interface

**Step 1**: Open Grade Schedule Builder

Navigate to `/admin/schedules/grade-builder`

**Step 2**: Add Teachers to Sidebar

Click "Tambah Guru" button, fill form:
- Kode Guru: G001
- Nama: Ahmad
- Mata Pelajaran: Matematika
- Max JP: 24 (optional)

**Step 3**: Drag Teacher to Grid or Click Cell

- Drag teacher from sidebar to grid cell
- OR click cell, select teacher from active teacher
- Cell shows: `G001 - Matematika`

**Step 4**: Lock Important Cells (optional)

Right-click cell → Lock
- Locked cells have lock icon
- Cannot be edited/swapped

**Step 5**: Swap Cells (if needed)

Right-click cell → Swap
- Click another cell to swap
- Press Esc to cancel

**Step 6**: Set Metadata (Required Before Save)

Click "Pengaturan" button:
- Tahun Ajaran: 2024/2025
- Semester: 1 (Ganjil)
- Berlaku Dari: 2025-01-15
- Berlaku Sampai: 2025-06-30 (optional)

**Step 7**: Auto-Save

- Changes auto-save every 2 seconds
- Status bar shows "Tersimpan" when done
- Red indicator if unsaved changes

**Step 8**: Manual Save (Force)

Press `Ctrl+S` or click "Simpan Sekarang"

**Step 9**: Export for Backup (optional)

Click "Export" → JSON file downloads
- Can be re-imported later
- Useful for backup or sharing

### 13.3 Import Teaching Schedule from Excel

**Step 1**: Prepare Excel File

Format:
| Hari | Jam | 7A | 7B | 7C |
|------|-----|----|----|-----|
| Senin | 1 | G001 | G002 | G003 |
| Senin | 2 | G004 | G001 | G005 |

**Step 2**: Open Excel Import Dialog

Grade Schedule Builder → Click "Excel" button

**Step 3**: Upload Excel File

Drag & drop or click to select .xlsx file

**Step 4**: Review Matched Teachers

- Green: Teacher found in system
- Red: Teacher not found
- Fix unmatched teachers (create in system first)

**Step 5**: Confirm Import

Click "Import" button
- Grid updates with data from Excel
- Teachers added to sidebar
- Auto-save triggers

### 13.4 View My Teaching Schedule (Teacher/Guru)

**Employee Login** → Navigate to `/employee/teaching-schedule`

Interface shows:
- **Today's Schedule**: Highlighted classes for today
- **Weekly Calendar**: All classes for the week
- **Statistics**:
  - Total hours per week
  - Number of classes
  - Number of subjects
- **Subject Badges**: Color-coded by subject
- **Room Information**: Where to teach

### 13.5 Check Attendance with Teaching Schedule Override

**Scenario**: Guru Honorer has teaching schedule on Monday at 07:00-08:30

**Step 1**: System checks for schedule

```php
$schedule = EmployeeMonthlySchedule::forEmployee($employee->id)
    ->forDate($today)
    ->first();

$effectiveSchedule = $schedule->getEffectiveSchedule();
// Returns:
// {
//   "start_time": "07:00",
//   "end_time": "08:30",
//   "schedule_source": "teaching_schedule",
//   "teaching_schedule_id": "uuid"
// }
```

**Step 2**: Guru checks in at 07:10

```php
$attendance = Attendance::create([
    'employee_id' => $employee->id,
    'check_in_time' => '07:10',
    'employee_monthly_schedule_id' => $schedule->id,
    'teaching_schedule_id' => $effectiveSchedule['teaching_schedule_id'],
    'schedule_source' => 'teaching_schedule',
]);
```

**Step 3**: Calculate late status

```php
$lateMinutes = Carbon::parse('07:10')->diffInMinutes(Carbon::parse('07:00'));
// $lateMinutes = 10

$teachingSchedule = TeachingSchedule::find($effectiveSchedule['teaching_schedule_id']);
$lateThreshold = $teachingSchedule->late_threshold_minutes; // 15

if ($lateMinutes > $lateThreshold) {
    $attendance->update(['is_late' => true, 'late_minutes' => $lateMinutes]);
} else {
    // Not late (10 < 15)
    $attendance->update(['is_late' => false]);
}
```

**Result**: Guru not marked as late (10 minutes < 15 minute threshold)

### 13.6 Handle Holiday Override

**Step 1**: Admin adds national holiday

```bash
POST /api/v1/holidays
{
  "name": "Hari Kemerdekaan Indonesia",
  "holiday_date": "2025-08-17",
  "type": "national",
  "force_override": true,
  "is_active": true
}
```

**Step 2**: Apply to monthly schedules

```php
// Auto-triggered on monthly schedule create/update
$schedule->applyHolidayOverrides();

// Updates all EmployeeMonthlySchedule for 2025-08-17:
// status = 'holiday'
// is_holiday = true
```

**Step 3**: Employees see holiday in schedule

Navigate to `/employee/schedule`
- Date "2025-08-17" marked as holiday
- No attendance required
- Shows holiday name in calendar

---

## Conclusion

Phase 5 Scheduling & Teaching Management system adalah fondasi untuk:
- ✅ Flexible work schedule management
- ✅ Teaching schedule dengan attendance override
- ✅ Excel-like grade schedule builder dengan auto-save
- ✅ Multi-layered schedule dengan holiday & teaching overrides
- ✅ Real-time conflict detection
- ✅ Teacher workload tracking
- ✅ Attendance validation berdasarkan effective schedule

**Next Steps**:
1. Implement recurring holidays generator
2. Add schedule analytics dashboard
3. Build schedule optimization algorithm
4. Create mobile app for schedule viewing
5. Implement schedule notifications (push/email)

---

**Documentation Version**: 1.0
**Last Updated**: 2025-12-20
**Maintained By**: Development Team
