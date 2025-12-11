<?php

namespace App\Models;

use App\Services\EmployeeIdGeneratorService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Employee Model
 *
 * Optimized for performance with proper caching and eager loading
 */
class Employee extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id', 'employee_id', 'full_name', 'phone',
        'photo_path', 'employee_type', 'employee_type_id', 'hire_date', 'salary_type',
        'salary_amount', 'hourly_rate', 'location_id', 'metadata', 'sensitive_data', 'is_active',
        'subject_id', 'department_id', 'position_id',
    ];

    protected $hidden = [
        'sensitive_data'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'salary_amount' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'sensitive_data' => 'encrypted:array',
    ];

    /**
     * Accessor to merge metadata and sensitive_data seamlessly.
     * Use getRawOriginal('metadata') to get raw DB value if needed.
     */
    public function getMetadataAttribute($value)
    {
        // Get public metadata (decoded)
        $publicData = isset($this->attributes['metadata']) 
            ? json_decode($this->attributes['metadata'], true) 
            : [];
            
        // Handle double-encoding edge case
        if (is_string($publicData)) {
            $publicData = json_decode($publicData, true) ?? [];
        }

        // Get sensitive data (auto-decrypted by cast)
        // Check array key first to avoid recursion or missing key issues
        $sensitiveData = $this->sensitive_data ?? [];

        if (!is_array($publicData)) $publicData = [];
        if (!is_array($sensitiveData)) $sensitiveData = [];

        return array_merge($publicData, $sensitiveData);
    }

    // Attributes to append when serializing to JSON (from accessors)
    protected $appends = [
        'name',
        'email',
        'department',
        'position',
        'status',
        'face_registered',
        'photo_url',
        'avatar',
        'join_date',
        'address',
        'role',
        'roles',
    ];

    protected $with = ['user', 'user.roles']; // Always eager load user and roles

    // ========== MODEL EVENTS ==========
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($employee) {
            // Auto-generate employee ID if not provided
            if (empty($employee->employee_id)) {
                $user = $employee->user;
                if ($user) {
                    $role = $user->roles->first()->name ?? 'pegawai';
                    $employeeType = $employee->employee_type ?? 'staff';
                    
                    $idGenerator = app(EmployeeIdGeneratorService::class);
                    $employee->employee_id = $idGenerator->generateUniqueEmployeeId($role, $employeeType);
                }
            }
        });
    }

    // ========== RELATIONSHIPS ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    // Schedule Management System Relationships
    public function monthlySchedules(): HasMany
    {
        return $this->hasMany(EmployeeMonthlySchedule::class);
    }

    public function teachingSchedules(): HasMany
    {
        return $this->hasMany(TeachingSchedule::class, 'teacher_id');
    }

    public function substituteSchedules(): HasMany
    {
        return $this->hasMany(TeachingSchedule::class, 'substitute_teacher_id');
    }

    public function defaultLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'default_location_id');
    }

    /**
     * Relationship to EmployeeType
     */
    public function employeeTypeRelation(): BelongsTo
    {
        return $this->belongsTo(EmployeeType::class, 'employee_type_id');
    }

    /**
     * Subject for teachers (Guru)
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Department for staff (Unit Kerja)
     */
    public function departmentRelation(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Position for staff (Jabatan)
     */
    public function positionRelation(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    /**
     * Get the EmployeeType model (alias for easier access)
     */
    public function getEmployeeTypeModelAttribute(): ?EmployeeType
    {
        return $this->employeeTypeRelation;
    }

    // ========== SCOPES ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithTodayAttendance($query)
    {
        return $query->with(['attendances' => fn ($q) => $q->whereDate('created_at', today())]);
    }

    public function scopeWithLatestAttendance($query)
    {
        return $query->with(['attendances' => fn ($q) => $q->latest()->limit(1)]);
    }

    // ========== ACCESSORS ==========

    public function getNameAttribute(): string
    {
        return $this->full_name ?? '';
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user?->email ?? $this->metadata['email'] ?? null;
    }

    public function getDepartmentAttribute(): ?string
    {
        // First check from departmentRelation, then fallback to metadata
        if ($this->departmentRelation) {
            return $this->departmentRelation->name;
        }
        $metadata = $this->getDecodedMetadata();
        return $metadata['department'] ?? null;
    }

    public function getPositionAttribute(): ?string
    {
        // First check from positionRelation, then fallback to metadata
        if ($this->positionRelation) {
            return $this->positionRelation->name;
        }
        $metadata = $this->getDecodedMetadata();
        return $metadata['position'] ?? null;
    }

    public function getSubjectNameAttribute(): ?string
    {
        return $this->subject?->name;
    }

    /**
     * Get decoded metadata (handles when metadata is stored as JSON string)
     */
    protected function getDecodedMetadata(): array
    {
        // Use getRawOriginal to bypass the getMetadataAttribute accessor interaction
        $metadata = $this->getRawOriginal('metadata'); // isset check removed as getRawOriginal handles it safely enough or returns null? no, returns mixed.

        
        if ($metadata === null) {
            return [];
        }
        
        // If it's already an array, return it
        if (is_array($metadata)) {
            return $metadata;
        }
        
        // If it's a string, decode it (may need to decode twice if double-encoded)
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            // Check if result is still a string (double-encoded JSON)
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            return is_array($decoded) ? $decoded : [];
        }
        
        return [];
    }

    public function getStatusAttribute(): string
    {
        return $this->is_active ? 'active' : 'inactive';
    }

    public function getJoinDateAttribute(): ?string
    {
        if ($this->hire_date === null) {
            return null;
        }
        // hire_date is cast to 'date' which returns a Carbon instance
        return $this->hire_date->toIso8601String();
    }

    public function getAddressAttribute(): ?string
    {
        $metadata = $this->getDecodedMetadata();
        return $metadata['address'] ?? null;
    }

    public function getRoleAttribute(): ?string
    {
        return $this->user?->roles?->first()?->name;
    }

    public function getRolesAttribute(): array
    {
        return $this->user?->roles?->pluck('name')->toArray() ?? [];
    }

    public function getAvatarAttribute(): ?string
    {
        return $this->photo_url;
    }

    public function getPhotoUrlAttribute(): string
    {
        return $this->photo_path
            ? asset("storage/{$this->photo_path}")
            : 'https://ui-avatars.com/api/?name='.urlencode($this->full_name ?? 'User');
    }

    public function getFaceRegisteredAttribute(): bool
    {
        return isset($this->metadata['face_recognition']['descriptor']);
    }

    // ========== OPTIMIZED QUERIES ==========

    /**
     * Get employees with minimal data for lists
     */
    public static function minimal()
    {
        return static::select(['id', 'first_name', 'last_name', 'employee_id', 'is_active'])
            ->with(['user:id,name,email'])
            ->orderBy('first_name');
    }

    /**
     * Get employees for dashboard stats
     */
    public static function forStats()
    {
        return static::select(['id', 'is_active', 'created_at'])
            ->withCount(['attendances', 'leaves']);
    }

    /**
     * Get employees for DataTables
     */
    public static function forDataTable()
    {
        return static::select([
            'id', 'employee_id', 'first_name', 'last_name',
            'employee_type', 'is_active', 'user_id', 'location_id',
        ])->with([
            'user:id,name,email',
            'location:id,name',
            'user.roles:id,name',
        ]);
    }

    // ========== BUSINESS METHODS ==========

    public function isActiveToday(): bool
    {
        return $this->attendances()
            ->whereDate('created_at', today())
            ->exists();
    }

    public function hasCheckedInToday(): bool
    {
        return $this->attendances()
            ->whereDate('created_at', today())
            ->whereNotNull('check_in_time')
            ->exists();
    }

    public function getTodayAttendance()
    {
        return $this->attendances()
            ->whereDate('created_at', today())
            ->first();
    }

    // ========== SCHEDULE MANAGEMENT METHODS ==========

    public function getScheduleForDate($date): ?EmployeeMonthlySchedule
    {
        $dateCarbon = $date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::parse($date);
        $dateString = $dateCarbon->format('Y-m-d');

        // Find employee monthly schedule where the date is in the working_days of the parent MonthlySchedule
        return $this->monthlySchedules()
            ->where('status', '!=', 'suspended')
            ->whereHas('monthlySchedule', function ($query) use ($dateCarbon, $dateString) {
                $query->where('year', $dateCarbon->year)
                      ->where('month', $dateCarbon->month)
                      ->where('is_active', true)
                      ->whereJsonContains('working_days', $dateString);
            })
            ->first();
    }

    public function getTodaySchedule()
    {
        return $this->getScheduleForDate(now());
    }

    public function getTeachingScheduleForDate($date): ?TeachingSchedule
    {
        // Check if employee type allows teaching override
        $employeeType = $this->employeeTypeRelation;
        if ($employeeType && !$employeeType->can_override_by_teaching) {
            return null;
        }

        // Fallback to legacy check if no employee_type relationship
        if (!$employeeType && $this->employee_type !== 'guru_honorer') {
            return null;
        }

        $dayOfWeek = $date instanceof \Carbon\Carbon ? strtolower($date->format('l')) : strtolower(\Carbon\Carbon::parse($date)->format('l'));
        
        return $this->teachingSchedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('effective_from', '<=', $date)
            ->where(function($query) use ($date) {
                $query->whereNull('effective_until')
                      ->orWhere('effective_until', '>=', $date);
            })
            ->where('is_active', true)
            ->where('override_attendance', true)
            ->first();
    }

    /**
     * Get ALL teaching schedules for a specific date (not just the first one)
     * This is important for flexible employees who may have multiple teaching sessions
     */
    public function getTeachingSchedulesForDate($date): \Illuminate\Support\Collection
    {
        $dateCarbon = $date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::parse($date);
        $dayOfWeek = strtolower($dateCarbon->format('l'));
        
        return $this->teachingSchedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('effective_from', '<=', $dateCarbon)
            ->where(function($query) use ($dateCarbon) {
                $query->whereNull('effective_until')
                      ->orWhere('effective_until', '>=', $dateCarbon);
            })
            ->where('is_active', true)
            ->orderBy('teaching_start_time')
            ->get();
    }

    /**
     * Get effective schedule for a specific date
     * This method handles all the logic for determining what schedule applies:
     * - For fixed employees: uses the monthly schedule
     * - For flexible employees: uses teaching schedules as override
     * - Returns can_attend flag to indicate if employee is allowed to check in
     */
    public function getEffectiveScheduleForDate($date): array
    {
        $baseSchedule = $this->getScheduleForDate($date);
        $employeeType = $this->employeeTypeRelation;
        
        // If no specific monthly schedule, try to use EmployeeType default
        // BUT only if require_schedule_for_attendance is false (schedule not strictly required)
        if (!$baseSchedule && $employeeType) {
            $requireSchedule = $employeeType->require_schedule_for_attendance ?? true;
            
            // Only create virtual schedule from defaults if schedule is NOT required
            if (!$requireSchedule) {
                $dayOfWeek = $date instanceof \Carbon\Carbon ? $date->format('D') : \Carbon\Carbon::parse($date)->format('D');
                $workDays = $employeeType->work_days ?? [];
                
                // Check if today is a work day for this employee type
                if (in_array($dayOfWeek, $workDays)) {
                    // Create a virtual schedule object based on defaults
                    $baseSchedule = (object) [
                        'start_time' => $employeeType->default_start_time,
                        'end_time' => $employeeType->default_end_time,
                        'location_id' => $this->location_id,
                        'is_holiday' => false,
                        'status' => 'scheduled',
                        'working_hours' => 8, // Approximate
                        'override_metadata' => [],
                    ];
                }
            }
        }
        
        // IMPORTANT: If still no schedule assigned, employee cannot attend
        if (!$baseSchedule) {
            // Check for Global Holiday first even if no schedule exists
            $globalHoliday = \App\Models\Holiday::active()
                ->whereDate('date', $date instanceof \Carbon\Carbon ? $date : $date)
                ->first();

            if ($globalHoliday) {
                return [
                    'schedule_type' => 'holiday',
                    'can_attend' => false,
                    'start_time' => null,
                    'end_time' => null,
                    'location_id' => $this->location_id,
                    'working_hours' => 0,
                    'holiday_name' => $globalHoliday->name,
                    'message' => 'Hari libur: ' . $globalHoliday->name,
                    'late_tolerance' => 0,
                    'color' => $globalHoliday->color ?? '#dc3545',
                ];
            }

            $requireSchedule = $employeeType?->require_schedule_for_attendance ?? true;
            
            return [
                'schedule_type' => 'none',
                'can_attend' => !$requireSchedule, // Only allow if schedule not required
                'start_time' => null,
                'end_time' => null,
                'location_id' => $this->location_id,
                'working_hours' => 0,
                'message' => 'Tidak ada jadwal yang di-assign untuk tanggal ini',
                'late_tolerance' => $employeeType?->late_tolerance_minutes ?? 15,
            ];
        }

        // Check for holiday (either from schedule override or global holiday)
        $isScheduleHoliday = $baseSchedule->is_holiday || $baseSchedule->status === 'holiday';
        
        // Check global holiday if not explicitly overridden to work
        $globalHoliday = null;
        if (!$isScheduleHoliday && !in_array($baseSchedule->status, ['overridden', 'scheduled'])) {
            $globalHoliday = \App\Models\Holiday::active()
                ->whereDate('date', $date instanceof \Carbon\Carbon ? $date : $date)
                ->first();
        }

        if ($isScheduleHoliday || $globalHoliday) {
            return [
                'schedule_type' => 'holiday',
                'can_attend' => false,
                'start_time' => null,
                'end_time' => null,
                'location_id' => $baseSchedule->location_id,
                'working_hours' => 0,
                'holiday_name' => $isScheduleHoliday 
                    ? ($baseSchedule->override_metadata['holiday_name'] ?? 'Libur')
                    : $globalHoliday->name,
                'message' => 'Hari libur' . ($globalHoliday ? ': ' . $globalHoliday->name : ''),
                'late_tolerance' => 0,
                'color' => $globalHoliday->color ?? '#dc3545',
            ];
        }

        // Check if employee type is flexible (e.g., Guru Honor)
        $isFlexible = $employeeType?->isFlexible() ?? ($this->employee_type === 'guru_honorer');
        $canOverrideByTeaching = $employeeType?->can_override_by_teaching ?? ($this->employee_type === 'guru_honorer');
        
        if ($isFlexible && $canOverrideByTeaching) {
            $teachingSchedules = $this->getTeachingSchedulesForDate($date);
            
            if ($teachingSchedules->isEmpty()) {
                // Flexible employee without teaching schedule = cannot attend
                return [
                    'schedule_type' => 'no_teaching',
                    'can_attend' => false,
                    'start_time' => null,
                    'end_time' => null,
                    'location_id' => $baseSchedule->location_id,
                    'working_hours' => 0,
                    'message' => 'Tidak ada jadwal mengajar hari ini',
                    'late_tolerance' => $employeeType?->late_tolerance_minutes ?? 15,
                ];
            }
            
            // Get earliest start time and latest end time from all teaching schedules
            $firstTeaching = $teachingSchedules->sortBy('teaching_start_time')->first();
            $lastTeaching = $teachingSchedules->sortByDesc('teaching_end_time')->first();
            
            $totalHours = $teachingSchedules->sum(function ($schedule) {
                return $schedule->teaching_duration_hours ?? 0;
            });
            
            return [
                'schedule_type' => 'teaching_override',
                'can_attend' => true,
                'start_time' => $firstTeaching->teaching_start_time,
                'end_time' => $lastTeaching->teaching_end_time,
                'location_id' => $baseSchedule->location_id,
                'working_hours' => $totalHours,
                'teaching_schedules' => $teachingSchedules->map(function ($ts) {
                    return [
                        'id' => $ts->id,
                        'subject' => $ts->subject?->name ?? 'Unknown',
                        'class_name' => $ts->class_name,
                        'room' => $ts->room,
                        'start_time' => $ts->teaching_start_time?->format('H:i'),
                        'end_time' => $ts->teaching_end_time?->format('H:i'),
                    ];
                })->toArray(),
                'message' => 'Jadwal mengajar: ' . $teachingSchedules->count() . ' sesi',
                'late_tolerance' => $employeeType?->late_tolerance_minutes ?? 15,
            ];
        }

        // Default: Fixed employee - use base schedule
        return [
            'schedule_type' => 'base_schedule',
            'can_attend' => true,
            'start_time' => $baseSchedule->start_time,
            'end_time' => $baseSchedule->end_time,
            'location_id' => $baseSchedule->location_id,
            'working_hours' => $baseSchedule->working_hours,
            'message' => 'Jadwal tetap',
            'late_tolerance' => $employeeType?->late_tolerance_minutes ?? 15,
        ];
    }

    public function hasScheduleForDate($date): bool
    {
        return $this->getScheduleForDate($date) !== null;
    }

    public function isWorkingDay($date): bool
    {
        $schedule = $this->getScheduleForDate($date);
        return $schedule && $schedule->is_working_day;
    }

    public function getWeeklyTeachingHours(): float
    {
        if (!$this->can_teach) {
            return 0.0;
        }

        return $this->teachingSchedules()
            ->active()
            ->where('effective_from', '<=', now())
            ->where(function($query) {
                $query->whereNull('effective_until')
                      ->orWhere('effective_until', '>=', now());
            })
            ->sum('teaching_duration_minutes') / 60;
    }

    public function getTeachingWorkload(): array
    {
        if (!$this->can_teach) {
            return ['total_hours' => 0, 'percentage' => 0, 'subjects' => []];
        }

        $schedules = $this->teachingSchedules()
            ->active()
            ->with('subject')
            ->where('effective_from', '<=', now())
            ->where(function($query) {
                $query->whereNull('effective_until')
                      ->orWhere('effective_until', '>=', now());
            })
            ->get();

        $totalHours = $schedules->sum('teaching_duration_minutes') / 60;
        $maxHours = 40; // Standard full-time teaching load

        $subjectBreakdown = $schedules->groupBy('subject_id')->map(function($subjectSchedules) {
            $subject = $subjectSchedules->first()->subject;
            return [
                'subject_name' => $subject->name ?? 'Unknown',
                'hours_per_week' => $subjectSchedules->sum('teaching_duration_minutes') / 60,
                'classes_count' => $subjectSchedules->count()
            ];
        });

        return [
            'total_hours' => $totalHours,
            'percentage' => min(100, ($totalHours / $maxHours) * 100),
            'subjects' => $subjectBreakdown->values(),
            'is_overloaded' => $totalHours > $maxHours
        ];
    }

    // ========== GURU HONORER HELPER METHODS ==========

    /**
     * Check if employee is Guru Honorer (uses TeachingSchedule for attendance)
     * Checks EmployeeType relationship first (can_override_by_teaching flag)
     * Only falls back to employee_type field if no EmployeeType is assigned
     */
    public function isGuruHonorer(): bool
    {
        // Check via EmployeeType relationship (preferred - uses can_override_by_teaching flag)
        $employeeType = $this->employeeTypeRelation;
        if ($employeeType) {
            // If EmployeeType is assigned, use its can_override_by_teaching flag
            return (bool) $employeeType->can_override_by_teaching;
        }

        // Fallback: Only if no EmployeeType assigned, check via employee_type field
        $honorTypes = ['guru_honorer', 'guru_honor', 'guru-honor'];
        return in_array(strtolower($this->employee_type ?? ''), $honorTypes);
    }

    /**
     * Get first teaching session for a specific date
     * Used for check-in validation (earliest session)
     */
    public function getFirstTeachingSessionForDate($date): ?TeachingSchedule
    {
        $schedules = $this->getTeachingSchedulesForDate($date);
        return $schedules->sortBy('teaching_start_time')->first();
    }

    /**
     * Get last teaching session for a specific date
     * Used for check-out validation (latest session)
     */
    public function getLastTeachingSessionForDate($date): ?TeachingSchedule
    {
        $schedules = $this->getTeachingSchedulesForDate($date);
        return $schedules->sortByDesc('teaching_end_time')->first();
    }

    /**
     * Get total teaching hours for a specific date
     * Sum of all teaching session durations (not range)
     */
    public function getTotalTeachingHoursForDate($date): float
    {
        $schedules = $this->getTeachingSchedulesForDate($date);

        return $schedules->sum(function ($schedule) {
            return $schedule->teaching_duration_hours ?? 0;
        });
    }

    /**
     * Get check-in time boundaries for Guru Honorer
     * Returns: [late_after, sessions]
     * NOTE: No blocking - guru honorer can check-in anytime, but will be marked late if after session start
     */
    public function getGuruHonorerCheckInBoundaries($date): array
    {
        $firstSession = $this->getFirstTeachingSessionForDate($date);

        if (!$firstSession) {
            return [
                'has_schedule' => false,
                'late_after' => null,
                'first_session_start' => null,
                'message' => 'Tidak ada jadwal mengajar hari ini',
            ];
        }

        $sessionStart = $firstSession->teaching_start_time;

        // Late if check-in after session start (no tolerance for guru honorer)
        $lateAfter = $sessionStart->copy();

        return [
            'has_schedule' => true,
            'late_after' => $lateAfter,
            'first_session_start' => $sessionStart,
            'first_session' => [
                'start_time' => $sessionStart->format('H:i'),
                'end_time' => $firstSession->teaching_end_time->format('H:i'),
                'subject' => $firstSession->subject?->name ?? 'Unknown',
                'class_name' => $firstSession->class_name,
            ],
            'message' => 'Sesi pertama: ' . $sessionStart->format('H:i'),
        ];
    }

    /**
     * Get check-out time boundaries for Guru Honorer
     * Returns: [early_leave_before, sessions]
     * NOTE: No blocking - guru honorer can check-out anytime, but will be marked early_leave if before session end
     */
    public function getGuruHonorerCheckOutBoundaries($date): array
    {
        $lastSession = $this->getLastTeachingSessionForDate($date);

        if (!$lastSession) {
            return [
                'has_schedule' => false,
                'early_leave_before' => null,
                'last_session_end' => null,
                'message' => 'Tidak ada jadwal mengajar hari ini',
            ];
        }

        $sessionEnd = $lastSession->teaching_end_time;

        // Early leave if check-out before session end
        $earlyLeaveBefore = $sessionEnd->copy();

        return [
            'has_schedule' => true,
            'early_leave_before' => $earlyLeaveBefore,
            'last_session_end' => $sessionEnd,
            'last_session' => [
                'start_time' => $lastSession->teaching_start_time->format('H:i'),
                'end_time' => $sessionEnd->format('H:i'),
                'subject' => $lastSession->subject?->name ?? 'Unknown',
                'class_name' => $lastSession->class_name,
            ],
            'message' => 'Sesi terakhir selesai: ' . $sessionEnd->format('H:i'),
        ];
    }

}
