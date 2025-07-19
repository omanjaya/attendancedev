<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Login Event
 *
 * Fired when a user successfully logs in to the system.
 */
class UserLoginEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public string $ipAddress,
        public string $userAgent,
        public ?string $deviceFingerprint = null,
        public bool $isTwoFactorRequired = false,
        public array $metadata = [],
    ) {}

    /**
     * Get event data for audit logging.
     */
    public function getAuditData(): array
    {
        return [
            'user_id' => $this->user->id,
            'event_type' => 'user_login',
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'device_fingerprint' => $this->deviceFingerprint,
            'two_factor_required' => $this->isTwoFactorRequired,
            'metadata' => array_merge($this->metadata, [
                'user_roles' => $this->user->roles->pluck('name')->toArray(),
                'last_login' => $this->user->last_login_at?->toISOString(),
                'login_count' => $this->user->failed_login_attempts === 0 ? 'successful' : 'after_failures',
            ]),
        ];
    }
}
