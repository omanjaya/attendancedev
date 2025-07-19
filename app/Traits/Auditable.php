<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    /**
     * Boot the auditable trait
     */
    protected static function bootAuditable()
    {
        static::created(function (Model $model) {
            $model->auditCreated();
        });

        static::updated(function (Model $model) {
            $model->auditUpdated();
        });

        static::deleted(function (Model $model) {
            $model->auditDeleted();
        });
    }

    /**
     * Audit model creation
     */
    protected function auditCreated()
    {
        if ($this->shouldAudit('created')) {
            AuditLog::createLog(
                'created',
                $this,
                [],
                $this->getAuditableAttributes(),
                null,
                $this->getAuditTags(),
            );
        }
    }

    /**
     * Audit model updates
     */
    protected function auditUpdated()
    {
        if ($this->shouldAudit('updated')) {
            $changes = $this->getChanges();
            $original = $this->getOriginal();

            if (! empty($changes)) {
                // Filter out timestamps if not specifically tracking them
                if (! $this->shouldAuditTimestamps()) {
                    unset($changes['updated_at'], $original['updated_at']);
                }

                // Only log if there are actual changes
                if (! empty($changes)) {
                    AuditLog::createLog(
                        'updated',
                        $this,
                        array_intersect_key($original, $changes),
                        $changes,
                        null,
                        $this->getAuditTags(),
                    );
                }
            }
        }
    }

    /**
     * Audit model deletion
     */
    protected function auditDeleted()
    {
        if ($this->shouldAudit('deleted')) {
            AuditLog::createLog(
                'deleted',
                $this,
                $this->getAuditableAttributes(),
                [],
                null,
                array_merge($this->getAuditTags(), ['deletion']),
            );
        }
    }

    /**
     * Determine if the model should be audited for the given event
     */
    protected function shouldAudit(string $event): bool
    {
        // Check if auditing is enabled
        if (! config('audit.enabled', true)) {
            return false;
        }

        // Check if this model should be audited
        if (property_exists($this, 'auditEnabled') && ! $this->auditEnabled) {
            return false;
        }

        // Check if this specific event should be audited
        if (property_exists($this, 'auditEvents')) {
            return in_array($event, $this->auditEvents);
        }

        // Default: audit all events
        return true;
    }

    /**
     * Get attributes that should be audited
     */
    protected function getAuditableAttributes(): array
    {
        $attributes = $this->getAttributes();

        // Remove excluded attributes
        if (property_exists($this, 'auditExclude')) {
            $attributes = array_diff_key($attributes, array_flip($this->auditExclude));
        }

        // Only include specified attributes if defined
        if (property_exists($this, 'auditInclude')) {
            $attributes = array_intersect_key($attributes, array_flip($this->auditInclude));
        }

        return $attributes;
    }

    /**
     * Get audit tags for this model
     */
    protected function getAuditTags(): array
    {
        $tags = [class_basename(static::class)];

        if (property_exists($this, 'auditTags')) {
            $tags = array_merge($tags, $this->auditTags);
        }

        return $tags;
    }

    /**
     * Determine if timestamps should be audited
     */
    protected function shouldAuditTimestamps(): bool
    {
        return property_exists($this, 'auditTimestamps') ? $this->auditTimestamps : false;
    }

    /**
     * Create a custom audit log entry
     */
    public function auditCustomEvent(string $eventType, array $context = [], array $tags = [])
    {
        AuditLog::createLog(
            $eventType,
            $this,
            [],
            $context,
            null,
            array_merge($this->getAuditTags(), $tags),
        );
    }

    /**
     * Get audit logs for this model
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * Get recent audit logs
     */
    public function recentAuditLogs(int $limit = 10)
    {
        return $this->auditLogs()->with('user')->latest()->limit($limit)->get();
    }
}
