<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ScheduleConflict extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'schedule_id_1',
        'schedule_id_2',
        'conflict_type',
        'severity',
        'description',
        'is_resolved',
        'detected_at',
        'resolved_at',
        'resolved_by',
        'resolution_notes'
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];

    // Constants
    const CONFLICT_TYPES = [
        'teacher_double_booking' => 'Guru Double Booking',
        'class_double_booking' => 'Kelas Double Booking',
        'room_double_booking' => 'Ruangan Double Booking',
        'subject_frequency_exceeded' => 'Frekuensi Mata Pelajaran Berlebih',
        'teacher_max_hours_exceeded' => 'Jam Mengajar Guru Berlebih'
    ];

    const SEVERITIES = [
        'low' => 'Rendah',
        'medium' => 'Sedang',
        'high' => 'Tinggi',
        'critical' => 'Kritis'
    ];

    // Relationships
    public function schedule1()
    {
        return $this->belongsTo(WeeklySchedule::class, 'schedule_id_1');
    }

    public function schedule2()
    {
        return $this->belongsTo(WeeklySchedule::class, 'schedule_id_2');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    // Accessors
    public function getConflictTypeNameAttribute()
    {
        return self::CONFLICT_TYPES[$this->conflict_type] ?? $this->conflict_type;
    }

    public function getSeverityNameAttribute()
    {
        return self::SEVERITIES[$this->severity] ?? $this->severity;
    }

    public function getSeverityColorAttribute()
    {
        $colors = [
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'critical' => 'red'
        ];

        return $colors[$this->severity] ?? 'gray';
    }

    // Methods
    public function resolve($userId, $notes = null)
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $userId,
            'resolution_notes' => $notes
        ]);

        return $this;
    }

    public function getAffectedSchedules()
    {
        $schedules = collect();
        
        if ($this->schedule1) {
            $schedules->push($this->schedule1);
        }
        
        if ($this->schedule2) {
            $schedules->push($this->schedule2);
        }

        return $schedules;
    }

    public function getConflictSummary()
    {
        return [
            'type' => $this->conflict_type_name,
            'severity' => $this->severity_name,
            'description' => $this->description,
            'detected_at' => $this->detected_at,
            'is_resolved' => $this->is_resolved,
            'schedules' => $this->getAffectedSchedules()->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'class' => $schedule->academicClass->full_name,
                    'subject' => $schedule->subject->display_name,
                    'teacher' => $schedule->employee->full_name,
                    'time' => $schedule->day_name . ', ' . $schedule->timeSlot->formatted_time,
                    'room' => $schedule->room
                ];
            })
        ];
    }
}