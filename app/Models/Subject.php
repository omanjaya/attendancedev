<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Subject extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'name',
        'category',
        'weekly_hours',
        'max_meetings_per_week',
        'requires_lab',
        'color',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'weekly_hours' => 'integer',
        'max_meetings_per_week' => 'integer',
        'requires_lab' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array'
    ];

    // Relationships
    public function teachers()
    {
        return $this->belongsToMany(Employee::class, 'teacher_subjects')
                    ->withPivot(['is_primary', 'max_hours_per_week', 'competencies', 'is_active'])
                    ->withTimestamps();
    }

    public function primaryTeachers()
    {
        return $this->teachers()->wherePivot('is_primary', true);
    }

    public function schedules()
    {
        return $this->hasMany(WeeklySchedule::class);
    }

    public function teacherSubjects()
    {
        return $this->hasMany(TeacherSubject::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeRequiringLab($query)
    {
        return $query->where('requires_lab', true);
    }

    // Accessors
    public function getDisplayNameAttribute()
    {
        return $this->code . ' - ' . $this->name;
    }

    public function getShortNameAttribute()
    {
        return $this->code;
    }

    // Methods
    public function getScheduleCountForClass($academicClassId, $dayOfWeek = null)
    {
        $query = $this->schedules()
                      ->where('academic_class_id', $academicClassId)
                      ->where('is_active', true);

        if ($dayOfWeek) {
            $query->where('day_of_week', $dayOfWeek);
        }

        return $query->count();
    }

    public function getWeeklyScheduleForClass($academicClassId)
    {
        return $this->schedules()
                    ->where('academic_class_id', $academicClassId)
                    ->where('is_active', true)
                    ->with(['timeSlot', 'employee'])
                    ->get()
                    ->groupBy('day_of_week');
    }

    public function hasAvailableTeacher($dayOfWeek, $timeSlotId)
    {
        foreach ($this->teachers as $teacher) {
            if (!$teacher->hasScheduleAt($dayOfWeek, $timeSlotId)) {
                return true;
            }
        }
        return false;
    }

    public function getAvailableTeachers($dayOfWeek, $timeSlotId)
    {
        return $this->teachers->filter(function ($teacher) use ($dayOfWeek, $timeSlotId) {
            return !$teacher->hasScheduleAt($dayOfWeek, $timeSlotId);
        });
    }

    public function validateScheduleFrequency($academicClassId, $dayOfWeek)
    {
        $currentCount = $this->getScheduleCountForClass($academicClassId);
        $todayCount = $this->getScheduleCountForClass($academicClassId, $dayOfWeek);

        return [
            'weekly_valid' => $currentCount < $this->max_meetings_per_week,
            'daily_valid' => $todayCount == 0, // No more than 1 per day
            'current_weekly_count' => $currentCount,
            'current_daily_count' => $todayCount,
            'max_weekly' => $this->max_meetings_per_week
        ];
    }
}