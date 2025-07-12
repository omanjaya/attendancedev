<?php

namespace App\Listeners;

use App\Events\UserLoginEvent;
use App\Events\AttendanceEvent;
use App\Events\SecurityEvent;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Audit Event Listener
 * 
 * Handles logging of all audit events to the database and log files.
 * Implements ShouldQueue for performance.
 */
class LogAuditEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle user login events.
     */
    public function handleUserLogin(UserLoginEvent $event): void
    {
        $this->logEvent($event->getAuditData(), 'info');
    }

    /**
     * Handle attendance events.
     */
    public function handleAttendance(AttendanceEvent $event): void
    {
        $auditData = $event->getAuditData();
        $riskLevel = $event->getRiskLevel();
        
        // Determine log level based on risk
        $logLevel = match($riskLevel) {
            'high' => 'warning',
            'medium' => 'notice',
            default => 'info'
        };

        $this->logEvent($auditData, $logLevel);

        // Additional logging for high-risk attendance events
        if ($riskLevel === 'high') {
            Log::warning('High-risk attendance event detected', $auditData);
        }
    }

    /**
     * Handle security events.
     */
    public function handleSecurity(SecurityEvent $event): void
    {
        $auditData = $event->getAuditData();
        
        $this->logEvent($auditData, $event->severity);

        // Log to separate security log for critical events
        if ($event->severity === 'critical') {
            Log::channel('security')->critical('Critical security event', $auditData);
        }
    }

    /**
     * Log event to audit database and log files.
     */
    private function logEvent(array $eventData, string $logLevel): void
    {
        try {
            // Store in database
            AuditLog::create([
                'user_id' => $eventData['user_id'] ?? null,
                'event_type' => $eventData['event_type'],
                'action' => $this->extractAction($eventData['event_type']),
                'model_type' => $this->extractModelType($eventData),
                'model_id' => $eventData['attendance_id'] ?? $eventData['employee_id'] ?? null,
                'old_values' => null,
                'new_values' => $eventData['metadata'] ?? null,
                'url' => request()->fullUrl(),
                'ip_address' => $eventData['ip_address'] ?? request()->ip(),
                'user_agent' => $eventData['user_agent'] ?? request()->userAgent(),
                'risk_level' => $this->calculateRiskLevel($eventData),
                'metadata' => $eventData
            ]);

            // Log to file system
            Log::channel('audit')->log($logLevel, $eventData['event_type'], $eventData);

        } catch (\Exception $e) {
            // Fallback logging if database fails
            Log::error('Failed to log audit event', [
                'error' => $e->getMessage(),
                'event_data' => $eventData
            ]);
        }
    }

    /**
     * Extract action from event type.
     */
    private function extractAction(string $eventType): string
    {
        $parts = explode('_', $eventType);
        return end($parts);
    }

    /**
     * Extract model type from event data.
     */
    private function extractModelType(array $eventData): ?string
    {
        if (isset($eventData['attendance_id'])) {
            return 'Attendance';
        } elseif (isset($eventData['employee_id'])) {
            return 'Employee';
        } elseif (isset($eventData['user_id'])) {
            return 'User';
        }

        return null;
    }

    /**
     * Calculate risk level based on event data.
     */
    private function calculateRiskLevel(array $eventData): string
    {
        // Security events already have severity
        if (str_contains($eventData['event_type'], 'security_')) {
            return $eventData['severity'] ?? 'low';
        }

        // Attendance events with verification issues
        if (str_contains($eventData['event_type'], 'attendance_')) {
            $locationVerified = $eventData['metadata']['location_verified'] ?? true;
            $faceVerified = $eventData['metadata']['face_verified'] ?? true;

            if (!$locationVerified && !$faceVerified) {
                return 'high';
            } elseif (!$locationVerified || !$faceVerified) {
                return 'medium';
            }
        }

        // Login events
        if (str_contains($eventData['event_type'], 'login')) {
            $twoFactorRequired = $eventData['two_factor_required'] ?? false;
            return $twoFactorRequired ? 'medium' : 'low';
        }

        return 'low';
    }

    /**
     * Handle failed jobs.
     */
    public function failed($event, $exception): void
    {
        Log::error('Audit logging failed', [
            'event' => get_class($event),
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}