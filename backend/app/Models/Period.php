<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Period extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['name', 'start_time', 'end_time', 'day_of_week', 'is_active', 'metadata'];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'day_of_week' => 'integer',
    ];

    /**
     * Get the employees assigned to this period.
     */
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_schedules')
            ->withPivot(['effective_date', 'end_date', 'is_active', 'metadata'])
            ->withTimestamps();
    }

    /**
     * Get the schedules for this period.
     */
    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    /**
     * Get the day name for display.
     */
    public function getDayNameAttribute()
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return $days[$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Get formatted time range.
     */
    public function getTimeRangeAttribute()
    {
        return $this->start_time->format('H:i').' - '.$this->end_time->format('H:i');
    }

    /**
     * Scope to get periods for a specific day.
     */
    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope to get active periods.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
