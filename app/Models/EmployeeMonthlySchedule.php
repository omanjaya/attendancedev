<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Carbon\Carbon;

class EmployeeMonthlySchedule extends Model
{
    use HasFactory, HasUuids, SoftDeletes, Auditable;

    protected $fillable = [
        'monthly_schedule_id',
        'employee_id',
        'effective_date',
        'start_time',
        'end_time',
        'location_id',
        'status',
        'override_metadata',
        'scheduled_hours',
        'is_weekend',
        'is_holiday',
        'attendance_id',
        'assigned_by',
        'modified_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'override_metadata' => 'array',
        'scheduled_hours' => 'decimal:2',
        'is_weekend' => 'boolean',
        'is_holiday' => 'boolean',
    ];

    protected $attributes = [
        'status' => 'active',
        'scheduled_hours' => 8.00,
        'is_weekend' => false,
        'is_holiday' => false,
        'override_metadata' => '{}',
    ];

    /**
     * Relationships
     */
    
    public function monthlySchedule(): BelongsTo
    {
        return $this->belongsTo(MonthlySchedule::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function modifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    /**
     * Scopes
     */
    
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForDate($query, Carbon $date)
    {
        return $query->where('effective_date', $date->toDateString());
    }

    public function scopeForDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('effective_date', [$startDate->toDateString(), $endDate->toDateString()]);
    }

    public function scopeWorkingDays($query)
    {
        return $query->where('is_weekend', false)->where('is_holiday', false)->where('status', 'active');
    }

    public function scopeHolidays($query)
    {
        return $query->where('is_holiday', true)->orWhere('status', 'holiday');
    }

    public function scopeOverridden($query)
    {
        return $query->where('status', 'overridden');
    }

    public function scopeForLocation($query, string $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Accessors & Mutators
     */
    
    public function getDayNameAttribute(): string
    {
        return $this->effective_date->format('l');
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->effective_date->format('d M Y');
    }

    public function getWorkingHoursAttribute(): float
    {
        if ($this->is_holiday || $this->status === 'holiday') {
            return 0.0;
        }
        
        $start = Carbon::createFromFormat('H:i', $this->start_time->format('H:i'));
        $end = Carbon::createFromFormat('H:i', $this->end_time->format('H:i'));
        
        return $start->diffInHours($end, true);
    }

    public function getIsWorkingDayAttribute(): bool
    {
        return !$this->is_weekend && !$this->is_holiday && $this->status === 'active';
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'active' => 'Active',
            'overridden' => 'Modified',
            'holiday' => 'Holiday',
            'leave' => 'On Leave',
            'suspended' => 'Suspended'
        ];
        
        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getOverrideReasonAttribute(): ?string
    {
        return $this->override_metadata['override_reason'] ?? null;
    }

    /**
     * Business Logic Methods
     */
    
    public function applyTeachingScheduleOverride(): bool
    {
        // Only apply to Guru Honorer
        if ($this->employee->employee_type !== 'guru_honorer') {
            return false;
        }
        
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
        
        $this->update([
            'start_time' => $teachingSchedule->teaching_start_time,
            'end_time' => $teachingSchedule->teaching_end_time,
            'status' => 'overridden',
            'scheduled_hours' => $teachingSchedule->teaching_duration_minutes / 60,
            'override_metadata' => array_merge($this->override_metadata ?? [], [
                'override_type' => 'teaching',
                'teaching_schedule_id' => $teachingSchedule->id,
                'original_start_time' => $this->getOriginal('start_time'),
                'original_end_time' => $this->getOriginal('end_time'),
                'override_reason' => 'Teaching schedule override for Guru Honorer',
                'override_at' => now(),
                'override_by' => auth()->id()
            ])
        ]);
        
        return true;
    }

    public function applyHolidayOverride(NationalHoliday $holiday): bool
    {
        $this->update([
            'status' => 'holiday',
            'is_holiday' => true,
            'override_metadata' => array_merge($this->override_metadata ?? [], [
                'override_type' => 'holiday',
                'holiday_id' => $holiday->id,
                'holiday_name' => $holiday->name,
                'holiday_type' => $holiday->type,
                'override_reason' => "Holiday: {$holiday->name}",
                'override_at' => now(),
                'override_by' => 'system'
            ])
        ]);
        
        return true;
    }

    public function revertOverride(): bool
    {
        if ($this->status === 'active') {
            return false; // Nothing to revert
        }
        
        $originalMetadata = $this->override_metadata;
        $originalStartTime = $originalMetadata['original_start_time'] ?? $this->monthlySchedule->default_start_time;
        $originalEndTime = $originalMetadata['original_end_time'] ?? $this->monthlySchedule->default_end_time;
        
        $this->update([
            'start_time' => $originalStartTime,
            'end_time' => $originalEndTime,
            'status' => 'active',
            'is_holiday' => false,
            'scheduled_hours' => $this->monthlySchedule->working_hours,
            'override_metadata' => [
                'reverted_at' => now(),
                'reverted_by' => auth()->id(),
                'previous_override' => $originalMetadata
            ]
        ]);
        
        return true;
    }

    public function getEffectiveSchedule(): array
    {
        // This method returns the final schedule considering all overrides
        $schedule = [
            'employee_id' => $this->employee_id,
            'date' => $this->effective_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'location_id' => $this->location_id,
            'status' => $this->status,
            'working_hours' => $this->working_hours,
            'is_working_day' => $this->is_working_day,
            'schedule_source' => 'monthly_schedule'
        ];
        
        // Check for teaching schedule override
        if ($this->employee->employee_type === 'guru_honorer') {
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
            
            if ($teachingSchedule) {
                $schedule['start_time'] = $teachingSchedule->teaching_start_time;
                $schedule['end_time'] = $teachingSchedule->teaching_end_time;
                $schedule['working_hours'] = $teachingSchedule->teaching_duration_minutes / 60;
                $schedule['schedule_source'] = 'teaching_schedule';
                $schedule['teaching_schedule_id'] = $teachingSchedule->id;
                $schedule['subject'] = $teachingSchedule->subject->name ?? 'Unknown';
                $schedule['class_name'] = $teachingSchedule->class_name;
            }
        }
        
        return $schedule;
    }

    public function createAttendanceRecord(): ?Attendance
    {
        if ($this->attendance_id || !$this->is_working_day) {
            return null;
        }
        
        $effectiveSchedule = $this->getEffectiveSchedule();
        
        $attendance = Attendance::create([
            'employee_id' => $this->employee_id,
            'date' => $this->effective_date,
            'location_id' => $this->location_id,
            'employee_monthly_schedule_id' => $this->id,
            'teaching_schedule_id' => $effectiveSchedule['teaching_schedule_id'] ?? null,
            'schedule_source' => $effectiveSchedule['schedule_source'],
            'schedule_metadata' => json_encode([
                'expected_start' => $effectiveSchedule['start_time'],
                'expected_end' => $effectiveSchedule['end_time'],
                'expected_hours' => $effectiveSchedule['working_hours'],
                'schedule_type' => $effectiveSchedule['schedule_source'],
                'calculated_at' => now()
            ])
        ]);
        
        $this->update(['attendance_id' => $attendance->id]);
        
        return $attendance;
    }

    /**
     * Validation Rules
     */
    
    public static function validationRules(): array
    {
        return [
            'monthly_schedule_id' => 'required|uuid|exists:monthly_schedules,id',
            'employee_id' => 'required|uuid|exists:employees,id',
            'effective_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location_id' => 'required|uuid|exists:locations,id',
            'status' => 'required|in:active,overridden,holiday,leave,suspended',
            'scheduled_hours' => 'required|numeric|min:0|max:24',
            'override_metadata' => 'nullable|array',
        ];
    }

    /**
     * Model Events
     */
    
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($model) {
            // Auto-calculate working hours if not set
            if (!$model->scheduled_hours || $model->scheduled_hours == 0) {
                $model->scheduled_hours = $model->working_hours;
            }
            
            // Auto-detect weekend
            $model->is_weekend = $model->effective_date->isWeekend();
        });
        
        static::created(function ($model) {
            // Auto-apply teaching schedule override for Guru Honorer
            if ($model->employee->employee_type === 'guru_honorer') {
                $model->applyTeachingScheduleOverride();
            }
        });
    }
}