<?php

namespace App\Events;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Attendance Event
 *
 * Fired when attendance actions occur (check-in, check-out, manual adjustments).
 */
class AttendanceEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Employee $employee,
        public string $action, // 'check_in', 'check_out', 'manual_adjustment', 'correction'
        public ?Attendance $attendance = null,
        public array $locationData = [],
        public array $faceData = [],
        public array $metadata = [],
    ) {}

    /**
     * Get event data for audit logging.
     */
    public function getAuditData(): array
    {
        return [
            'user_id' => $this->employee->user_id,
            'employee_id' => $this->employee->id,
            'event_type' => 'attendance_'.$this->action,
            'attendance_id' => $this->attendance?->id,
            'metadata' => array_merge($this->metadata, [
                'employee_code' => $this->employee->employee_code,
                'employee_name' => $this->employee->full_name,
                'location_verified' => ! empty($this->locationData['verified']),
                'face_verified' => ! empty($this->faceData['verified']),
                'location_data' => $this->locationData,
                'face_data' => array_merge($this->faceData, [
                    // Remove sensitive face embedding data from logs
                    'face_embedding' => ! empty($this->faceData['face_embedding']) ? '[REDACTED]' : null,
                ]),
                'timestamp' => now()->toISOString(),
            ]),
        ];
    }

    /**
     * Determine risk level based on verification status.
     */
    public function getRiskLevel(): string
    {
        $locationVerified = ! empty($this->locationData['verified']);
        $faceVerified = ! empty($this->faceData['verified']);

        if (! $locationVerified && ! $faceVerified) {
            return 'high';
        } elseif (! $locationVerified || ! $faceVerified) {
            return 'medium';
        }

        return 'low';
    }
}
