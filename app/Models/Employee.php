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
        'photo_path', 'employee_type', 'hire_date', 'salary_type',
        'salary_amount', 'hourly_rate', 'location_id', 'metadata', 'is_active',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'salary_amount' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected $with = ['user']; // Always eager load user

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

    public function getPhotoUrlAttribute(): string
    {
        return $this->photo_path
            ? asset("storage/{$this->photo_path}")
            : 'https://ui-avatars.com/api/?name='.urlencode($this->full_name);
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
        return $this->monthlySchedules()
            ->where('effective_date', $date instanceof \Carbon\Carbon ? $date->toDateString() : $date)
            ->where('status', '!=', 'suspended')
            ->first();
    }

    public function getTeachingScheduleForDate($date): ?TeachingSchedule
    {
        if ($this->employee_type !== 'guru_honorer') {
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

    public function getEffectiveScheduleForDate($date): array
    {
        $baseSchedule = $this->getScheduleForDate($date);
        
        if (!$baseSchedule) {
            return [
                'schedule_type' => 'none',
                'start_time' => null,
                'end_time' => null,
                'location_id' => null,
                'working_hours' => 0
            ];
        }

        // Check for holiday
        if ($baseSchedule->is_holiday || $baseSchedule->status === 'holiday') {
            return [
                'schedule_type' => 'holiday',
                'start_time' => null,
                'end_time' => null,
                'location_id' => $baseSchedule->location_id,
                'working_hours' => 0,
                'holiday_name' => $baseSchedule->override_metadata['holiday_name'] ?? 'Holiday'
            ];
        }

        // For Guru Honorer, check teaching schedule override
        if ($this->employee_type === 'guru_honorer') {
            $teachingSchedule = $this->getTeachingScheduleForDate($date);
            
            if ($teachingSchedule) {
                return [
                    'schedule_type' => 'teaching_override',
                    'start_time' => $teachingSchedule->teaching_start_time,
                    'end_time' => $teachingSchedule->teaching_end_time,
                    'location_id' => $baseSchedule->location_id,
                    'working_hours' => $teachingSchedule->teaching_duration_hours,
                    'subject' => $teachingSchedule->subject->name ?? 'Unknown',
                    'class_name' => $teachingSchedule->class_name,
                    'room' => $teachingSchedule->room
                ];
            }
        }

        // Default to base schedule
        return [
            'schedule_type' => 'base_schedule',
            'start_time' => $baseSchedule->start_time,
            'end_time' => $baseSchedule->end_time,
            'location_id' => $baseSchedule->location_id,
            'working_hours' => $baseSchedule->working_hours
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
}
