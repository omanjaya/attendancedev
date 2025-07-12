<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AcademicClass extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'grade_level',
        'major',
        'class_number',
        'capacity',
        'room',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'capacity' => 'integer'
    ];

    // Relationships
    public function schedules()
    {
        return $this->hasMany(WeeklySchedule::class);
    }

    public function students()
    {
        return $this->hasMany(Employee::class, 'academic_class_id')
                    ->where('employee_type', 'student');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByGrade($query, $grade)
    {
        return $query->where('grade_level', $grade);
    }

    public function scopeByMajor($query, $major)
    {
        return $query->where('major', $major);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        $parts = [$this->grade_level];
        
        if ($this->major) {
            $parts[] = $this->major;
        }
        
        $parts[] = $this->class_number;
        
        return implode('-', $parts);
    }

    public function getCurrentStudentCountAttribute()
    {
        return $this->students()->count();
    }

    public function getCapacityUtilizationAttribute()
    {
        if ($this->capacity == 0) return 0;
        return round(($this->current_student_count / $this->capacity) * 100, 1);
    }

    // Methods
    public function hasScheduleAt($dayOfWeek, $timeSlotId)
    {
        return $this->schedules()
                    ->where('day_of_week', $dayOfWeek)
                    ->where('time_slot_id', $timeSlotId)
                    ->where('is_active', true)
                    ->exists();
    }

    public function getScheduleAt($dayOfWeek, $timeSlotId)
    {
        return $this->schedules()
                    ->where('day_of_week', $dayOfWeek)
                    ->where('time_slot_id', $timeSlotId)
                    ->where('is_active', true)
                    ->with(['subject', 'employee', 'timeSlot'])
                    ->first();
    }

    public function getWeeklySchedule()
    {
        return $this->schedules()
                    ->where('is_active', true)
                    ->with(['subject', 'employee', 'timeSlot'])
                    ->get()
                    ->groupBy('day_of_week');
    }
}