<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklySchedule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'academic_class_id',
        'subject_id',
        'employee_id',
        'time_slot_id',
        'day_of_week',
        'room',
        'effective_from',
        'effective_until',
        'is_locked',
        'is_active',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_until' => 'date',
        'is_locked' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    // Constants
    const DAYS_OF_WEEK = [
        'monday' => 'Senin',
        'tuesday' => 'Selasa',
        'wednesday' => 'Rabu',
        'thursday' => 'Kamis',
        'friday' => 'Jumat',
        'saturday' => 'Sabtu',
    ];

    // Relationships
    public function academicClass()
    {
        return $this->belongsTo(AcademicClass::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function changeLogs()
    {
        return $this->hasMany(ScheduleChangeLog::class, 'schedule_id');
    }

    public function conflicts()
    {
        return $this->hasMany(ScheduleConflict::class, 'schedule_id_1')->orWhere(
            'schedule_id_2',
            $this->id,
        );
    }

    public function locks()
    {
        return $this->hasMany(ScheduleLock::class, 'schedule_id');
    }

    public function activeLock()
    {
        return $this->hasOne(ScheduleLock::class, 'schedule_id')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('locked_until')->orWhere('locked_until', '>', now());
            });
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotLocked($query)
    {
        return $query->where('is_locked', false);
    }

    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function scopeForTimeSlot($query, $timeSlotId)
    {
        return $query->where('time_slot_id', $timeSlotId);
    }

    public function scopeForClass($query, $academicClassId)
    {
        return $query->where('academic_class_id', $academicClassId);
    }

    public function scopeForTeacher($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeEffective($query, $date = null)
    {
        $date = $date ?: today();

        return $query->where('effective_from', '<=', $date)->where(function ($q) use ($date) {
            $q->whereNull('effective_until')->orWhere('effective_until', '>=', $date);
        });
    }

    // Accessors
    public function getDayNameAttribute()
    {
        return self::DAYS_OF_WEEK[$this->day_of_week] ?? $this->day_of_week;
    }

    public function getDisplayInfoAttribute()
    {
        return [
            'subject_code' => $this->subject->code,
            'subject_name' => $this->subject->name,
            'teacher_name' => $this->employee->full_name,
            'room' => $this->room,
            'time' => $this->timeSlot->formatted_time,
            'is_locked' => $this->is_locked,
            'color' => $this->subject->color,
        ];
    }

    public function getStatusAttribute()
    {
        if ($this->is_locked) {
            return 'locked';
        }
        if ($this->hasActiveConflicts()) {
            return 'conflict';
        }

        return 'normal';
    }

    // Methods
    public function hasActiveConflicts()
    {
        return $this->conflicts()->where('is_resolved', false)->exists();
    }

    public function getActiveConflicts()
    {
        return $this->conflicts()
            ->where('is_resolved', false)
            ->with(['schedule1', 'schedule2'])
            ->get();
    }

    public function isEffectiveOn($date = null)
    {
        $date = $date ?: today();

        if ($this->effective_from > $date) {
            return false;
        }
        if ($this->effective_until && $this->effective_until < $date) {
            return false;
        }

        return true;
    }

    public function canBeModified()
    {
        return ! $this->is_locked && $this->is_active;
    }

    public function lock($reason, $userId, $until = null)
    {
        $this->update(['is_locked' => true]);

        return $this->locks()->create([
            'lock_type' => 'manual',
            'reason' => $reason,
            'locked_at' => now(),
            'locked_until' => $until,
            'locked_by' => $userId,
            'is_active' => true,
        ]);
    }

    public function unlock($reason, $userId)
    {
        $this->update(['is_locked' => false]);

        $activeLock = $this->activeLock;
        if ($activeLock) {
            $activeLock->update([
                'unlocked_at' => now(),
                'unlocked_by' => $userId,
                'unlock_reason' => $reason,
                'is_active' => false,
            ]);
        }

        return true;
    }

    public function logChange($action, $oldData, $newData, $userId, $reason = null)
    {
        return ScheduleChangeLog::create([
            'schedule_id' => $this->id,
            'action' => $action,
            'old_data' => $oldData,
            'new_data' => $newData,
            'reason' => $reason,
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'action_timestamp' => now(),
            'metadata' => [
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
            ],
        ]);
    }

    public function detectConflicts()
    {
        $conflicts = [];

        // Teacher double booking
        $teacherConflicts = self::where('employee_id', $this->employee_id)
            ->where('day_of_week', $this->day_of_week)
            ->where('time_slot_id', $this->time_slot_id)
            ->where('is_active', true)
            ->where('id', '!=', $this->id)
            ->effective()
            ->get();

        foreach ($teacherConflicts as $conflict) {
            $conflicts[] = [
                'type' => 'teacher_double_booking',
                'severity' => 'critical',
                'conflicting_schedule' => $conflict,
                'description' => "Guru {$this->employee->full_name} sudah mengajar di kelas {$conflict->academicClass->full_name} pada waktu yang sama",
            ];
        }

        // Class double booking
        $classConflicts = self::where('academic_class_id', $this->academic_class_id)
            ->where('day_of_week', $this->day_of_week)
            ->where('time_slot_id', $this->time_slot_id)
            ->where('is_active', true)
            ->where('id', '!=', $this->id)
            ->effective()
            ->get();

        foreach ($classConflicts as $conflict) {
            $conflicts[] = [
                'type' => 'class_double_booking',
                'severity' => 'critical',
                'conflicting_schedule' => $conflict,
                'description' => "Kelas {$this->academicClass->full_name} sudah ada jadwal {$conflict->subject->name} pada waktu yang sama",
            ];
        }

        // Subject frequency check
        $subjectCount = self::where('academic_class_id', $this->academic_class_id)
            ->where('subject_id', $this->subject_id)
            ->where('is_active', true)
            ->effective()
            ->count();

        if ($subjectCount > $this->subject->max_meetings_per_week) {
            $conflicts[] = [
                'type' => 'subject_frequency_exceeded',
                'severity' => 'medium',
                'conflicting_schedule' => null,
                'description' => "Mata pelajaran {$this->subject->name} melebihi batas maksimal {$this->subject->max_meetings_per_week} pertemuan per minggu",
            ];
        }

        // Same day check for same subject
        $sameDaySubject = self::where('academic_class_id', $this->academic_class_id)
            ->where('subject_id', $this->subject_id)
            ->where('day_of_week', $this->day_of_week)
            ->where('is_active', true)
            ->where('id', '!=', $this->id)
            ->effective()
            ->exists();

        if ($sameDaySubject) {
            $conflicts[] = [
                'type' => 'subject_same_day',
                'severity' => 'medium',
                'conflicting_schedule' => null,
                'description' => "Mata pelajaran {$this->subject->name} sudah ada di hari yang sama untuk kelas {$this->academicClass->full_name}",
            ];
        }

        return $conflicts;
    }

    public static function getGridData($academicClassId, $date = null)
    {
        $schedules = self::forClass($academicClassId)
            ->active()
            ->effective($date)
            ->with(['subject', 'employee', 'timeSlot'])
            ->get();

        $timeSlots = TimeSlot::active()->ordered()->get();
        $grid = [];

        foreach (self::DAYS_OF_WEEK as $dayKey => $dayName) {
            $grid[$dayKey] = [
                'day_name' => $dayName,
                'slots' => [],
            ];

            foreach ($timeSlots as $slot) {
                $schedule = $schedules
                    ->where('day_of_week', $dayKey)
                    ->where('time_slot_id', $slot->id)
                    ->first();

                $grid[$dayKey]['slots'][$slot->id] = [
                    'time_slot' => $slot,
                    'schedule' => $schedule,
                    'display' => $schedule ? $schedule->display_info : null,
                    'status' => $schedule ? $schedule->status : 'empty',
                ];
            }
        }

        return $grid;
    }
}
