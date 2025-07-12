<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ScheduleChangeLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'schedule_id',
        'action',
        'old_data',
        'new_data',
        'reason',
        'user_id',
        'ip_address',
        'action_timestamp',
        'metadata'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'metadata' => 'array',
        'action_timestamp' => 'datetime'
    ];

    // Constants
    const ACTIONS = [
        'create' => 'Dibuat',
        'update' => 'Diubah',
        'delete' => 'Dihapus',
        'lock' => 'Dikunci',
        'unlock' => 'Dibuka',
        'bulk_update' => 'Update Massal'
    ];

    // Relationships
    public function schedule()
    {
        return $this->belongsTo(WeeklySchedule::class, 'schedule_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForSchedule($query, $scheduleId)
    {
        return $query->where('schedule_id', $scheduleId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('action_timestamp', '>=', now()->subDays($days));
    }

    // Accessors
    public function getActionNameAttribute()
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    public function getFormattedTimestampAttribute()
    {
        return $this->action_timestamp->format('d/m/Y H:i:s');
    }

    // Methods
    public function getChangedFields()
    {
        if (!$this->old_data || !$this->new_data) {
            return [];
        }

        $changes = [];
        $oldData = $this->old_data;
        $newData = $this->new_data;

        $fieldsToCheck = [
            'academic_class_id' => 'Kelas',
            'subject_id' => 'Mata Pelajaran',
            'employee_id' => 'Guru',
            'time_slot_id' => 'Jam Pelajaran',
            'day_of_week' => 'Hari',
            'room' => 'Ruangan',
            'is_locked' => 'Status Kunci',
            'is_active' => 'Status Aktif'
        ];

        foreach ($fieldsToCheck as $field => $label) {
            if (isset($oldData[$field]) && isset($newData[$field])) {
                if ($oldData[$field] !== $newData[$field]) {
                    $changes[$field] = [
                        'label' => $label,
                        'old_value' => $oldData[$field],
                        'new_value' => $newData[$field]
                    ];
                }
            }
        }

        return $changes;
    }

    public function getChangeDescription()
    {
        $changes = $this->getChangedFields();
        
        if (empty($changes)) {
            return $this->reason ?: $this->action_name;
        }

        $descriptions = [];
        foreach ($changes as $change) {
            $descriptions[] = "{$change['label']}: {$change['old_value']} → {$change['new_value']}";
        }

        return implode(', ', $descriptions);
    }

    public function getAuditSummary()
    {
        return [
            'id' => $this->id,
            'action' => $this->action_name,
            'user' => $this->user->name ?? 'System',
            'timestamp' => $this->formatted_timestamp,
            'description' => $this->getChangeDescription(),
            'reason' => $this->reason,
            'ip_address' => $this->ip_address,
            'metadata' => $this->metadata
        ];
    }
}