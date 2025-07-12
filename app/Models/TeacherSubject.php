<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TeacherSubject extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'employee_id',
        'subject_id',
        'is_primary',
        'max_hours_per_week',
        'competencies',
        'is_active'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'competencies' => 'array',
        'max_hours_per_week' => 'integer'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    // Methods
    public function getCurrentWeeklyHours()
    {
        return WeeklySchedule::where('employee_id', $this->employee_id)
                            ->where('subject_id', $this->subject_id)
                            ->where('is_active', true)
                            ->count();
    }

    public function hasCapacityForMoreHours()
    {
        return $this->getCurrentWeeklyHours() < $this->max_hours_per_week;
    }
}