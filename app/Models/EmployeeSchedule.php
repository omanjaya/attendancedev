<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmployeeSchedule extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'employee_id',
        'period_id',
        'effective_date',
        'end_date',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array'
    ];

    /**
     * Get the employee that owns the schedule.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the period that owns the schedule.
     */
    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    /**
     * Scope to get active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get current schedules (effective today).
     */
    public function scopeCurrent($query)
    {
        $today = now()->toDateString();
        return $query->where('effective_date', '<=', $today)
                    ->where(function ($q) use ($today) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $today);
                    });
    }

    /**
     * Check if schedule is currently active.
     */
    public function isCurrentlyActive()
    {
        $today = now()->toDateString();
        return $this->is_active
            && $this->effective_date <= $today
            && (is_null($this->end_date) || $this->end_date >= $today);
    }
}
