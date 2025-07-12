<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Security Event
 * 
 * Fired when security-related actions occur in the system.
 */
class SecurityEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $eventType, // 'failed_login', 'account_locked', '2fa_enabled', 'password_changed', etc.
        public ?User $user = null,
        public string $severity = 'info', // 'low', 'medium', 'high', 'critical'
        public string $ipAddress = '',
        public string $userAgent = '',
        public array $metadata = []
    ) {}

    /**
     * Get event data for audit logging.
     */
    public function getAuditData(): array
    {
        return [
            'user_id' => $this->user?->id,
            'event_type' => 'security_' . $this->eventType,
            'severity' => $this->severity,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'metadata' => array_merge($this->metadata, [
                'user_email' => $this->user?->email,
                'user_roles' => $this->user?->roles->pluck('name')->toArray(),
                'timestamp' => now()->toISOString(),
                'source' => 'web_application'
            ])
        ];
    }

    /**
     * Determine if this event should trigger immediate alerts.
     */
    public function shouldAlert(): bool
    {
        $criticalEvents = [
            'account_locked',
            'multiple_failed_logins',
            'suspicious_login_pattern',
            'privilege_escalation',
            'data_breach_attempt'
        ];

        return in_array($this->eventType, $criticalEvents) || $this->severity === 'critical';
    }

    /**
     * Get notification recipients for this security event.
     */
    public function getNotificationRecipients(): array
    {
        $recipients = [];

        switch ($this->severity) {
            case 'critical':
                $recipients = ['security_team', 'administrators', 'system_managers'];
                break;
            case 'high':
                $recipients = ['security_team', 'system_managers'];
                break;
            case 'medium':
                $recipients = ['system_managers'];
                break;
            default:
                $recipients = [];
        }

        return $recipients;
    }
}