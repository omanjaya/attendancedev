<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    const UPDATED_AT = null; // Disable updated_at timestamp since table only has created_at

    protected $fillable = [
        'id',
        'user_id',
        'event_type',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'tags',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'tags' => 'array',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the user that performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by event type
     */
    public function scopeOfEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to filter by auditable type
     */
    public function scopeOfAuditableType($query, $auditableType)
    {
        return $query->where('auditable_type', $auditableType);
    }

    /**
     * Scope to filter by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get the formatted event type
     */
    public function getFormattedEventTypeAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->event_type));
    }

    /**
     * Get the model name from auditable_type
     */
    public function getModelNameAttribute()
    {
        return class_basename($this->auditable_type);
    }

    /**
     * Get changes summary
     */
    public function getChangesSummaryAttribute()
    {
        if (empty($this->old_values) && empty($this->new_values)) {
            return 'No changes recorded';
        }

        $changes = [];

        if ($this->event_type === 'created') {
            $fields = array_keys($this->new_values ?? []);

            return 'Created with '.count($fields).' fields';
        }

        if ($this->event_type === 'updated') {
            $old = $this->old_values ?? [];
            $new = $this->new_values ?? [];

            foreach ($new as $key => $value) {
                if (isset($old[$key]) && $old[$key] !== $value) {
                    $changes[] = $key;
                }
            }

            return 'Updated '.implode(', ', $changes);
        }

        if ($this->event_type === 'deleted') {
            return 'Record deleted';
        }

        return $this->formatted_event_type;
    }

    /**
     * Get risk level based on event type and model
     */
    public function getRiskLevelAttribute()
    {
        $highRiskEvents = ['deleted', 'login_failed', 'permission_changed', 'role_changed'];
        $highRiskModels = ['User', 'Employee', 'Payroll'];

        if (in_array($this->event_type, $highRiskEvents)) {
            return 'high';
        }

        if (in_array($this->model_name, $highRiskModels)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get risk level color
     */
    public function getRiskColorAttribute()
    {
        switch ($this->risk_level) {
            case 'high':
                return 'red';
            case 'medium':
                return 'yellow';
            default:
                return 'green';
        }
    }

    /**
     * Check if audit log has significant changes
     */
    public function hasSignificantChanges()
    {
        $sensitiveFields = [
            'password',
            'email',
            'salary',
            'role',
            'permissions',
            'status',
            'deleted_at',
            'face_data',
        ];

        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        foreach ($sensitiveFields as $field) {
            if (isset($old[$field]) || isset($new[$field])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create audit log entry
     */
    public static function createLog(
        string $eventType,
        Model $auditable,
        array $oldValues = [],
        array $newValues = [],
        ?User $user = null,
        array $tags = [],
    ) {
        $user = $user ?? auth()->user();
        $request = request();

        return static::create([
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => $request?->fullUrl(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'tags' => $tags,
        ]);
    }

    /**
     * Create authentication audit log
     */
    public static function createAuthLog(string $eventType, User $user, array $context = [])
    {
        $request = request();

        return static::create([
            'user_id' => $user->id,
            'event_type' => $eventType,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => [],
            'new_values' => $context,
            'url' => $request?->fullUrl(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'tags' => ['authentication'],
        ]);
    }

    /**
     * Create security audit log
     */
    public static function createSecurityLog(string $eventType, array $context = [])
    {
        $request = request();
        $user = auth()->user();

        return static::create([
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'auditable_type' => 'Security',
            'auditable_id' => null,
            'old_values' => [],
            'new_values' => $context,
            'url' => $request?->fullUrl(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'tags' => ['security'],
        ]);
    }
}
