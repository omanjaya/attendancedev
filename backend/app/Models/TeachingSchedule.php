<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Carbon\Carbon;

class TeachingSchedule extends Model
{
    use HasFactory, HasUuids, SoftDeletes, Auditable;

    protected $fillable = [
        'teacher_id',
        'subject_id',
        'class_id',
        'day_of_week',
        'teaching_start_time',
        'teaching_end_time',
        'effective_from',
        'effective_until',
        'class_name',
        'room',
        'student_count',
        'is_active',
        'status',
        'override_attendance',
        'strict_timing',
        'late_threshold_minutes',
        'monthly_schedule_id',
        'metadata',
        'substitute_teacher_id',
        'substitution_start_date',
        'substitution_end_date',
        'substitution_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'teaching_start_time' => 'datetime:H:i',
        'teaching_end_time' => 'datetime:H:i',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'substitution_start_date' => 'date',
        'substitution_end_date' => 'date',
        'student_count' => 'integer',
        'late_threshold_minutes' => 'integer',
        'is_active' => 'boolean',
        'override_attendance' => 'boolean',
        'strict_timing' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
        'status' => 'scheduled',
        'override_attendance' => true,
        'strict_timing' => true,
        'late_threshold_minutes' => 15,
        'metadata' => '{}',
    ];

    /**
     * Relationships
     */
    
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function monthlySchedule(): BelongsTo
    {
        return $this->belongsTo(MonthlySchedule::class);
    }

    public function substituteTeacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'substitute_teacher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'teaching_schedule_id');
    }

    public function employeeSchedules(): HasMany
    {
        return $this->hasMany(EmployeeMonthlySchedule::class, 'teaching_schedule_id');
    }

    /**
     * Scopes
     */
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTeacher($query, string $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForSubject($query, string $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForDay($query, string $dayOfWeek)
    {
        return $query->where('day_of_week', strtolower($dayOfWeek));
    }

    public function scopeForDate($query, Carbon $date)
    {
        return $query->where('day_of_week', strtolower($date->format('l')))
                    ->where('effective_from', '<=', $date)
                    ->where(function($q) use ($date) {
                        $q->whereNull('effective_until')
                          ->orWhere('effective_until', '>=', $date);
                    });
    }

    public function scopeOverrideAttendance($query)
    {
        return $query->where('override_attendance', true);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeRescheduled($query)
    {
        return $query->where('status', 'rescheduled');
    }

    public function scopeSubstituted($query)
    {
        return $query->where('status', 'substituted');
    }

    public function scopeWithSubstitute($query)
    {
        return $query->whereNotNull('substitute_teacher_id');
    }

    public function scopeForTimeRange($query, string $startTime, string $endTime)
    {
        return $query->where(function($q) use ($startTime, $endTime) {
            $q->whereBetween('teaching_start_time', [$startTime, $endTime])
              ->orWhereBetween('teaching_end_time', [$startTime, $endTime])
              ->orWhere(function($q2) use ($startTime, $endTime) {
                  $q2->where('teaching_start_time', '<=', $startTime)
                     ->where('teaching_end_time', '>=', $endTime);
              });
        });
    }

    /**
     * Accessors & Mutators
     */
    
    public function getTeachingDurationMinutesAttribute(): int
    {
        $start = Carbon::createFromFormat('H:i', $this->teaching_start_time->format('H:i'));
        $end = Carbon::createFromFormat('H:i', $this->teaching_end_time->format('H:i'));
        
        return $start->diffInMinutes($end);
    }

    public function getTeachingDurationHoursAttribute(): float
    {
        return $this->teaching_duration_minutes / 60;
    }

    public function getFormattedTimeAttribute(): string
    {
        return $this->teaching_start_time->format('H:i') . ' - ' . $this->teaching_end_time->format('H:i');
    }

    public function getDayLabelAttribute(): string
    {
        $days = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa', 
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu'
        ];
        
        return $days[$this->day_of_week] ?? ucfirst($this->day_of_week);
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'scheduled' => 'Scheduled',
            'cancelled' => 'Cancelled',
            'rescheduled' => 'Rescheduled',
            'substituted' => 'Substituted'
        ];
        
        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getFullClassNameAttribute(): string
    {
        return $this->class_name ?? 'Class ' . ($this->class_id ?? 'Unknown');
    }

    public function getIsCurrentlyActiveAttribute(): bool
    {
        $today = Carbon::today();
        return $this->effective_from <= $today && 
               ($this->effective_until === null || $this->effective_until >= $today);
    }

    public function getHasSubstituteAttribute(): bool
    {
        return $this->substitute_teacher_id !== null && 
               $this->substitution_start_date && 
               $this->substitution_end_date &&
               Carbon::today()->between($this->substitution_start_date, $this->substitution_end_date);
    }

    public function getEffectiveTeacherAttribute(): Employee
    {
        if ($this->has_substitute) {
            return $this->substituteTeacher;
        }
        
        return $this->teacher;
    }

    /**
     * Business Logic Methods
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

    public function isEffectiveForDate(Carbon $date): bool
    {
        return $this->effective_from <= $date && 
               ($this->effective_until === null || $this->effective_until >= $date);
    }

    public function getConflictingSchedules(string $teacherId = null): array
    {
        $teacherId = $teacherId ?? $this->teacher_id;
        
        $conflicts = static::where('teacher_id', $teacherId)
            ->where('day_of_week', $this->day_of_week)
            ->where('id', '!=', $this->id)
            ->active()
            ->where(function($query) {
                $query->where('effective_from', '<=', $this->effective_until ?? Carbon::parse('2030-12-31'))
                      ->where(function($q) {
                          $q->whereNull('effective_until')
                            ->orWhere('effective_until', '>=', $this->effective_from);
                      });
            })
            ->forTimeRange(
                $this->teaching_start_time->format('H:i'),
                $this->teaching_end_time->format('H:i')
            )
            ->with(['subject', 'teacher'])
            ->get();
        
        return $conflicts->map(function($schedule) {
            return [
                'id' => $schedule->id,
                'subject' => $schedule->subject->name,
                'class_name' => $schedule->full_class_name,
                'time_range' => $schedule->formatted_time,
                'room' => $schedule->room,
                'effective_period' => $schedule->effective_from->format('d/m/Y') . ' - ' . 
                                     ($schedule->effective_until ? $schedule->effective_until->format('d/m/Y') : 'Ongoing')
            ];
        })->toArray();
    }

    public function calculateTeacherWorkload(): array
    {
        $weeklySchedules = static::forTeacher($this->teacher_id)
            ->active()
            ->scheduled()
            ->where('effective_from', '<=', Carbon::today())
            ->where(function($query) {
                $query->whereNull('effective_until')
                      ->orWhere('effective_until', '>=', Carbon::today());
            })
            ->get();
        
        $totalMinutesPerWeek = $weeklySchedules->sum('teaching_duration_minutes');
        $totalHoursPerWeek = $totalMinutesPerWeek / 60;
        
        $subjectBreakdown = $weeklySchedules->groupBy('subject_id')->map(function($schedules, $subjectId) {
            $subject = Subject::find($subjectId);
            return [
                'subject_name' => $subject->name ?? 'Unknown',
                'hours_per_week' => $schedules->sum('teaching_duration_minutes') / 60,
                'classes_count' => $schedules->count(),
                'classes' => $schedules->pluck('full_class_name')->unique()->values()
            ];
        });
        
        return [
            'total_hours_per_week' => $totalHoursPerWeek,
            'total_classes' => $weeklySchedules->count(),
            'subject_breakdown' => $subjectBreakdown,
            'workload_percentage' => min(100, ($totalHoursPerWeek / 40) * 100), // Assuming 40 hours max
            'is_overloaded' => $totalHoursPerWeek > 40
        ];
    }

    public function assignSubstitute(Employee $substitute, Carbon $startDate, Carbon $endDate, string $reason): bool
    {
        // Validate substitute is qualified
        if (!$substitute->can_teach || !$substitute->can_substitute) {
            return false;
        }
        
        // Check for conflicts in substitute's schedule
        $conflicts = static::forTeacher($substitute->id)
            ->where('day_of_week', $this->day_of_week)
            ->active()
            ->where(function($query) use ($startDate, $endDate) {
                $query->where('effective_from', '<=', $endDate)
                      ->where(function($q) use ($startDate) {
                          $q->whereNull('effective_until')
                            ->orWhere('effective_until', '>=', $startDate);
                      });
            })
            ->forTimeRange(
                $this->teaching_start_time->format('H:i'),
                $this->teaching_end_time->format('H:i')
            )
            ->exists();
        
        if ($conflicts) {
            return false;
        }
        
        $this->update([
            'substitute_teacher_id' => $substitute->id,
            'substitution_start_date' => $startDate,
            'substitution_end_date' => $endDate,
            'substitution_reason' => $reason,
            'status' => 'substituted'
        ]);
        
        return true;
    }

    public function removeSubstitute(): bool
    {
        $this->update([
            'substitute_teacher_id' => null,
            'substitution_start_date' => null,
            'substitution_end_date' => null,
            'substitution_reason' => null,
            'status' => 'scheduled'
        ]);
        
        return true;
    }

    public function applyToEmployeeSchedules(): int
    {
        if (!$this->override_attendance) {
            return 0;
        }
        
        $applied = 0;
        $startDate = $this->effective_from;
        $endDate = $this->effective_until ?? Carbon::today()->addMonths(6); // Default 6 months if no end date
        
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            if (strtolower($current->format('l')) === $this->day_of_week) {
                $employeeSchedule = EmployeeMonthlySchedule::forEmployee($this->teacher_id)
                    ->forDate($current)
                    ->first();
                
                if ($employeeSchedule && $employeeSchedule->applyTeachingScheduleOverride()) {
                    $applied++;
                }
            }
            
            $current->addDay();
        }
        
        return $applied;
    }

    /**
     * Validation Rules
     */
    
    public static function validationRules(): array
    {
        return [
            'teacher_id' => 'required|uuid|exists:employees,id',
            'subject_id' => 'required|uuid|exists:subjects,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'teaching_start_time' => 'required|date_format:H:i',
            'teaching_end_time' => 'required|date_format:H:i|after:teaching_start_time',
            'effective_from' => 'required|date',
            'effective_until' => 'nullable|date|after_or_equal:effective_from',
            'class_name' => 'nullable|string|max:100',
            'room' => 'nullable|string|max:100',
            'student_count' => 'nullable|integer|min:1|max:100',
            'late_threshold_minutes' => 'required|integer|min:1|max:120',
            'monthly_schedule_id' => 'nullable|uuid|exists:monthly_schedules,id',
            'substitute_teacher_id' => 'nullable|uuid|exists:employees,id',
            'substitution_start_date' => 'required_with:substitute_teacher_id|nullable|date',
            'substitution_end_date' => 'required_with:substitute_teacher_id|nullable|date|after_or_equal:substitution_start_date',
            'substitution_reason' => 'required_with:substitute_teacher_id|nullable|string|max:500',
        ];
    }

    /**
     * Model Events
     */
    
    protected static function boot()
    {
        parent::boot();
        
        static::created(function ($model) {
            // Auto-apply to employee schedules if override is enabled
            if ($model->override_attendance) {
                $model->applyToEmployeeSchedules();
            }
        });
        
        static::updated(function ($model) {
            // Re-apply if override settings changed
            if ($model->wasChanged(['override_attendance', 'teaching_start_time', 'teaching_end_time'])) {
                $model->applyToEmployeeSchedules();
            }
        });
    }
}