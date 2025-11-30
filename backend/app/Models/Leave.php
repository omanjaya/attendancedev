<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Leave extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days_requested',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejection_reason',
        'is_emergency',
        'attachments',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days_requested' => 'decimal:2',
        'approved_at' => 'datetime',
        'is_emergency' => 'boolean',
        'attachments' => 'array',
        'metadata' => 'array',
    ];

    /**
     * The statuses available for leaves.
     */
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the employee that requested the leave.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the leave type for this leave.
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the employee who approved this leave.
     */
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    /**
     * Scope to get pending leaves.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get approved leaves.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to get rejected leaves.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope to get cancelled leaves.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope to get current year leaves.
     */
    public function scopeCurrentYear($query)
    {
        $currentYear = date('Y');

        return $query->whereYear('start_date', $currentYear);
    }

    /**
     * Scope to get upcoming leaves.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', Carbon::today());
    }

    /**
     * Scope to get active leaves (currently ongoing).
     */
    public function scopeActive($query)
    {
        $today = Carbon::today();

        return $query
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('status', self::STATUS_APPROVED);
    }

    /**
     * Check if the leave is pending.
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the leave is approved.
     */
    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the leave is rejected.
     */
    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if the leave is cancelled.
     */
    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if the leave is currently active.
     */
    public function isActive()
    {
        $today = Carbon::today();

        return $this->isApproved() && $this->start_date <= $today && $this->end_date >= $today;
    }

    /**
     * Check if the leave can be cancelled.
     */
    public function canBeCancelled()
    {
        return $this->isPending() || ($this->isApproved() && $this->start_date > Carbon::today());
    }

    /**
     * Calculate the number of working days between start and end dates.
     */
    public static function calculateWorkingDays($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $workingDays = 0;
        $current = $start->copy();

        while ($current <= $end) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if (! $current->isWeekend()) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Get the duration in human readable format.
     */
    public function getDurationAttribute()
    {
        if ($this->days_requested == 0.5) {
            return 'Half day';
        } elseif ($this->days_requested == 1) {
            return '1 day';
        } else {
            return $this->days_requested.' days';
        }
    }

    /**
     * Get the status badge color.
     */
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get the formatted date range.
     */
    public function getDateRangeAttribute()
    {
        if ($this->start_date->eq($this->end_date)) {
            return $this->start_date->format('M j, Y');
        }

        return $this->start_date->format('M j').' - '.$this->end_date->format('M j, Y');
    }
}
