<?php

namespace App\Services\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TwoFactorSecurityService
{
    /**
     * Log failed verification attempt.
     */
    public function logFailedVerification(Request $request, $user, string $type): void
    {
        $attemptData = [
            'user_id' => $user->id,
            'attempt_type' => $type,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString(),
        ];

        // Log to security channel
        logger()->channel('security')->warning('Failed 2FA Verification', $attemptData);

        // Increment failed attempts counter
        $failureKey = "2fa_failures_{$user->id}";
        $failures = cache()->get($failureKey, 0);
        $failures++;

        cache()->put($failureKey, $failures, now()->addMinutes(30));

        // Lock account after too many failures
        if ($failures >= 5) {
            $user->lockAccount(now()->addMinutes(30));

            logger()->channel('security')->critical('Account Locked Due to 2FA Failures', $attemptData);

            // Send security alert
            $this->sendSecurityAlert($user, 'Account locked due to repeated 2FA failures');
        }
    }

    /**
     * Notify admins of emergency recovery request.
     */
    public function notifyAdminsOfEmergencyRecovery($user, array $recoveryData): void
    {
        // Get all admin users
        $admins = \App\Models\User::role(['admin', 'superadmin'])->get();

        foreach ($admins as $admin) {
            // Send email notification (implement with your mail system)
            Mail::send(
                'emails.emergency-recovery-request',
                [
                    'user' => $user,
                    'admin' => $admin,
                    'recovery_data' => $recoveryData,
                ],
                function ($message) use ($admin) {
                    $message->to($admin->email)->subject('Emergency 2FA Recovery Request');
                },
            );
        }
    }

    /**
     * Send security alert notification.
     */
    public function sendSecurityAlert($user, string $message): void
    {
        // Send email to user
        Mail::send(
            'emails.security-alert',
            [
                'user' => $user,
                'message' => $message,
                'timestamp' => now(),
                'ip_address' => request()->ip(),
            ],
            function ($mail) use ($user) {
                $mail->to($user->email)->subject('Security Alert - '.config('app.name'));
            },
        );
    }
}
