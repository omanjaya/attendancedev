<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// Import Events
use App\Events\UserLoginEvent;
use App\Events\AttendanceEvent;
use App\Events\SecurityEvent;

// Import Listeners
use App\Listeners\LogAuditEventListener;
use App\Listeners\SecurityAlertListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // User Authentication Events
        UserLoginEvent::class => [
            LogAuditEventListener::class . '@handleUserLogin',
        ],

        // Attendance Events
        AttendanceEvent::class => [
            LogAuditEventListener::class . '@handleAttendance',
            SecurityAlertListener::class . '@handleHighRiskAttendance',
        ],

        // Security Events
        SecurityEvent::class => [
            LogAuditEventListener::class . '@handleSecurity',
            SecurityAlertListener::class . '@handleSecurityEvent',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Register Laravel's built-in authentication events
        Event::listen('auth.login', function ($event, $payload) {
            $user = $payload[0] ?? auth()->user();
            
            if ($user) {
                UserLoginEvent::dispatch(
                    user: $user,
                    ipAddress: request()->ip() ?? '',
                    userAgent: request()->userAgent() ?? '',
                    deviceFingerprint: $this->generateDeviceFingerprint(),
                    isTwoFactorRequired: $user->requires2FA()
                );
            }
        });

        // Register failed login events
        Event::listen('auth.failed', function ($event, $payload) {
            $credentials = $payload[0] ?? [];
            $email = $credentials['email'] ?? '';
            
            if ($email) {
                $user = \App\Models\User::where('email', $email)->first();
                
                SecurityEvent::dispatch(
                    eventType: 'failed_login',
                    user: $user,
                    severity: 'medium',
                    ipAddress: request()->ip() ?? '',
                    userAgent: request()->userAgent() ?? '',
                    metadata: [
                        'attempted_email' => $email,
                        'credentials_provided' => array_keys($credentials)
                    ]
                );
            }
        });

        // Register account lockout events
        Event::listen('auth.lockout', function ($request) {
            $email = $request->input('email', '');
            $user = \App\Models\User::where('email', $email)->first();

            SecurityEvent::dispatch(
                eventType: 'account_locked',
                user: $user,
                severity: 'high',
                ipAddress: $request->ip() ?? '',
                userAgent: $request->userAgent() ?? '',
                metadata: [
                    'lockout_reason' => 'too_many_failed_attempts',
                    'attempted_email' => $email
                ]
            );
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    /**
     * Generate device fingerprint for tracking.
     */
    private function generateDeviceFingerprint(): string
    {
        $components = [
            request()->userAgent() ?? '',
            request()->header('Accept-Language', ''),
            request()->header('Accept-Encoding', ''),
            request()->ip() ?? ''
        ];

        return md5(implode('|', $components));
    }
}
