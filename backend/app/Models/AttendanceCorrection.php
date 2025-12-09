<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceCorrection extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'attendance_id',
        'correction_date',
        'correction_type',
        'original_check_in',
        'original_check_out',
        'requested_check_in',
        'requested_check_out',
        'reason',
        'supporting_document',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'correction_date' => 'date',
        'original_check_in' => 'datetime:H:i',
        'original_check_out' => 'datetime:H:i',
        'requested_check_in' => 'datetime:H:i',
        'requested_check_out' => 'datetime:H:i',
        'reviewed_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    // Correction type constants
    const TYPE_CHECK_IN = 'check_in';
    const TYPE_CHECK_OUT = 'check_out';
    const TYPE_BOTH = 'both';
    const TYPE_ADD_MISSING = 'add_missing';
    const TYPE_DELETE = 'delete';

    /**
     * Get the employee that owns the correction request
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the attendance record being corrected
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Get the user who reviewed the correction
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope for pending corrections
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved corrections
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for rejected corrections
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Check if correction is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if correction is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if correction is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if correction can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Approve the correction
     */
    public function approve(int $reviewerId, ?string $notes = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        // Apply the correction to attendance
        $this->applyCorrection();

        return true;
    }

    /**
     * Reject the correction
     */
    public function reject(int $reviewerId, ?string $notes = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        return true;
    }

    /**
     * Cancel the correction (by employee)
     */
    public function cancel(): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);

        return true;
    }

    /**
     * Apply correction to the attendance record
     */
    protected function applyCorrection(): void
    {
        if ($this->correction_type === self::TYPE_DELETE) {
            // Soft delete the attendance
            if ($this->attendance) {
                $this->attendance->delete();
            }
            return;
        }

        if ($this->correction_type === self::TYPE_ADD_MISSING) {
            // Create new attendance record
            Attendance::create([
                'employee_id' => $this->employee_id,
                'date' => $this->correction_date,
                'check_in_time' => $this->requested_check_in,
                'check_out_time' => $this->requested_check_out,
                'status' => 'present',
                'check_in_notes' => 'Added via correction request #' . $this->id,
                'metadata' => ['correction_id' => $this->id],
            ]);
            return;
        }

        // Update existing attendance
        if (!$this->attendance) {
            return;
        }

        $updateData = [];
        
        if (in_array($this->correction_type, [self::TYPE_CHECK_IN, self::TYPE_BOTH])) {
            if ($this->requested_check_in) {
                $updateData['check_in_time'] = $this->correction_date->format('Y-m-d') . ' ' . $this->requested_check_in->format('H:i:s');
                $updateData['check_in_notes'] = ($this->attendance->check_in_notes ?? '') . "\nCorrected via request #" . $this->id;
            }
        }

        if (in_array($this->correction_type, [self::TYPE_CHECK_OUT, self::TYPE_BOTH])) {
            if ($this->requested_check_out) {
                $updateData['check_out_time'] = $this->correction_date->format('Y-m-d') . ' ' . $this->requested_check_out->format('H:i:s');
                $updateData['check_out_notes'] = ($this->attendance->check_out_notes ?? '') . "\nCorrected via request #" . $this->id;
            }
        }

        if (!empty($updateData)) {
            $this->attendance->update($updateData);
            
            // Recalculate total hours if both check-in and check-out exist
            if ($this->attendance->check_in_time && $this->attendance->check_out_time) {
                $checkIn = \Carbon\Carbon::parse($this->attendance->check_in_time);
                $checkOut = \Carbon\Carbon::parse($this->attendance->check_out_time);
                $totalHours = $checkOut->diffInMinutes($checkIn) / 60;
                $this->attendance->update(['total_hours' => round($totalHours, 2)]);
            }
        }
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get status label in Indonesian
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => 'Unknown',
        };
    }

    /**
     * Get correction type label in Indonesian
     */
    public function getCorrectionTypeLabelAttribute(): string
    {
        return match($this->correction_type) {
            self::TYPE_CHECK_IN => 'Koreksi Check-In',
            self::TYPE_CHECK_OUT => 'Koreksi Check-Out',
            self::TYPE_BOTH => 'Koreksi Jam Masuk & Keluar',
            self::TYPE_ADD_MISSING => 'Tambah Absensi',
            self::TYPE_DELETE => 'Hapus Absensi',
            default => 'Unknown',
        };
    }
}
