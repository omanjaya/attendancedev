<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'allocated_days',
        'used_days',
        'remaining_days',
        'carried_forward',
        'metadata',
    ];

    protected $casts = [
        'year' => 'integer',
        'allocated_days' => 'decimal:2',
        'used_days' => 'decimal:2',
        'remaining_days' => 'decimal:2',
        'carried_forward' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the employee that owns the leave balance.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the leave type for this balance.
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Scope to get current year balances.
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('year', date('Y'));
    }

    /**
     * Scope to get balances for a specific year.
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Update remaining days based on allocated and used days.
     */
    public function updateRemainingDays()
    {
        $this->remaining_days = $this->allocated_days - $this->used_days;
        $this->save();
    }

    /**
     * Check if employee can take specified days of leave.
     */
    public function canTakeDays($days)
    {
        return $this->remaining_days >= $days;
    }

    /**
     * Deduct days from the balance.
     */
    public function deductDays($days)
    {
        $this->used_days += $days;
        $this->updateRemainingDays();
    }

    /**
     * Add days back to the balance (for cancelled/rejected leaves).
     */
    public function addDays($days)
    {
        $this->used_days -= $days;
        if ($this->used_days < 0) {
            $this->used_days = 0;
        }
        $this->updateRemainingDays();
    }
}
