<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ScheduleLock extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'schedule_id',
        'lock_type',
        'reason',
        'locked_at',
        'locked_until',
        'locked_by',
        'unlocked_at',
        'unlocked_by',
        'unlock_reason',
        'is_active'
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'locked_until' => 'datetime',
        'unlocked_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    // Constants
    const LOCK_TYPES = [
        'manual' => 'Manual',
        'automatic' => 'Otomatis',
        'system' => 'Sistem'
    ];

    // Relationships
    public function schedule()
    {
        return $this->belongsTo(WeeklySchedule::class, 'schedule_id');
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function unlockedBy()
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('locked_until', '<', now())
                    ->whereNotNull('locked_until');
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('locked_until')
              ->orWhere('locked_until', '>=', now());
        });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('lock_type', $type);
    }

    // Accessors
    public function getLockTypeNameAttribute()
    {
        return self::LOCK_TYPES[$this->lock_type] ?? $this->lock_type;
    }

    public function getIsExpiredAttribute()
    {
        if (!$this->locked_until) {
            return false; // Permanent lock
        }

        return $this->locked_until < now();
    }

    public function getStatusAttribute()
    {
        if (!$this->is_active) {
            return 'unlocked';
        }

        if ($this->is_expired) {
            return 'expired';
        }

        return 'locked';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'locked' => 'red',
            'expired' => 'orange',
            'unlocked' => 'green'
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getDurationAttribute()
    {
        if (!$this->locked_until) {
            return 'Permanent';
        }

        $now = now();
        if ($this->locked_until < $now) {
            return 'Expired';
        }

        return $this->locked_until->diffForHumans($now);
    }

    // Methods
    public function isCurrentlyLocked()
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->locked_until && $this->locked_until < now()) {
            return false;
        }

        return true;
    }

    public function unlock($userId, $reason = null)
    {
        $this->update([
            'unlocked_at' => now(),
            'unlocked_by' => $userId,
            'unlock_reason' => $reason,
            'is_active' => false
        ]);

        // Also unlock the schedule
        $this->schedule->update(['is_locked' => false]);

        return $this;
    }

    public function extend($newUntil, $reason = null)
    {
        $this->update([
            'locked_until' => $newUntil,
            'reason' => $this->reason . ($reason ? " | Extended: {$reason}" : '')
        ]);

        return $this;
    }

    public function getLockSummary()
    {
        return [
            'id' => $this->id,
            'type' => $this->lock_type_name,
            'reason' => $this->reason,
            'locked_by' => $this->lockedBy->name ?? 'System',
            'locked_at' => $this->locked_at->format('d/m/Y H:i'),
            'locked_until' => $this->locked_until?->format('d/m/Y H:i'),
            'duration' => $this->duration,
            'status' => $this->status,
            'is_expired' => $this->is_expired,
            'unlocked_by' => $this->unlockedBy->name ?? null,
            'unlocked_at' => $this->unlocked_at?->format('d/m/Y H:i'),
            'unlock_reason' => $this->unlock_reason
        ];
    }

    // Static methods
    public static function cleanupExpiredLocks()
    {
        $expiredLocks = self::active()
                           ->expired()
                           ->get();

        foreach ($expiredLocks as $lock) {
            $lock->update(['is_active' => false]);
            $lock->schedule->update(['is_locked' => false]);
        }

        return $expiredLocks->count();
    }
}