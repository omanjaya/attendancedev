<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FaceRecognitionLog extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'action',
        'employee_id',
        'data',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the employee that owns the log.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Scope a query to only include logs for a specific action.
     */
    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include logs for a specific employee.
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope a query to only include logs within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include successful verifications.
     */
    public function scopeSuccessfulVerifications($query)
    {
        return $query->where('action', 'verify_success');
    }

    /**
     * Scope a query to only include failed verifications.
     */
    public function scopeFailedVerifications($query)
    {
        return $query->where('action', 'verify_failed');
    }

    /**
     * Get the confidence score from the data.
     */
    public function getConfidenceAttribute(): ?float
    {
        return $this->data['confidence'] ?? null;
    }

    /**
     * Get the similarity score from the data.
     */
    public function getSimilarityAttribute(): ?float
    {
        return $this->data['similarity'] ?? null;
    }

    /**
     * Get the quality score from the data.
     */
    public function getQualityScoreAttribute(): ?float
    {
        return $this->data['quality_score'] ?? null;
    }

    /**
     * Get the liveness score from the data.
     */
    public function getLivenessScoreAttribute(): ?float
    {
        return $this->data['liveness_score'] ?? null;
    }

    /**
     * Check if the log is for a successful action.
     */
    public function isSuccessful(): bool
    {
        return in_array($this->action, ['register', 'verify_success', 'update']);
    }

    /**
     * Check if the log is for a failed action.
     */
    public function isFailed(): bool
    {
        return in_array($this->action, ['verify_failed']);
    }

    /**
     * Get formatted action name.
     */
    public function getFormattedActionAttribute(): string
    {
        return match ($this->action) {
            'register' => 'Face Registered',
            'verify_success' => 'Verification Success',
            'verify_failed' => 'Verification Failed',
            'update' => 'Face Updated',
            'delete' => 'Face Deleted',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Get action color for UI.
     */
    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'register' => 'success',
            'verify_success' => 'success',
            'verify_failed' => 'danger',
            'update' => 'info',
            'delete' => 'warning',
            default => 'secondary',
        };
    }
}