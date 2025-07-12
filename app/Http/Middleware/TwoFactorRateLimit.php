<?php

namespace App\Http\Middleware;

use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class TwoFactorRateLimit
{
    private SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $action = 'general'): BaseResponse
    {
        $ip = $request->ip();
        $user = auth()->user();
        
        // Create identifier based on user + IP for authenticated requests
        $identifier = $user ? $user->id . '_' . $ip : $ip;

        // Check for security lockdown (for authenticated users)
        if ($user && $this->securityService->is2FALockedDown($identifier)) {
            return $this->createLockdownResponse();
        }

        // Check specific 2FA action rate limiting
        if ($this->securityService->isRateLimited($ip, "2fa_{$action}")) {
            return $this->createRateLimitResponse($action);
        }

        // Track the request attempt
        $this->securityService->trackRateLimit($ip, "2fa_{$action}");

        $response = $next($request);

        // If the response indicates success, clear related attempts
        if ($response->isSuccessful() && $user) {
            $this->securityService->clearFailedLoginAttempts($identifier);
        }

        return $response;
    }

    /**
     * Create response for rate limited requests
     */
    private function createRateLimitResponse(string $action): Response
    {
        $messages = [
            'verification' => 'Too many verification attempts. Please wait before trying again.',
            'recovery_code' => 'Too many recovery code attempts. Please wait before trying again.',
            'sms_request' => 'Too many SMS requests. Please wait before requesting another code.',
            'emergency_recovery' => 'Emergency recovery requests are limited. Please contact support.',
            'setup_attempt' => 'Too many setup attempts. Please wait before trying again.',
            'general' => 'Too many requests. Please wait before trying again.'
        ];

        $message = $messages[$action] ?? $messages['general'];

        if (request()->expectsJson()) {
            return response()->json([
                'error' => $message,
                'rate_limited' => true,
                'retry_after' => $this->getRetryAfter($action)
            ], 429);
        }

        return response()->view('errors.rate-limited', [
            'message' => $message,
            'action' => $action,
            'retry_after' => $this->getRetryAfter($action)
        ], 429);
    }

    /**
     * Create response for security lockdown
     */
    private function createLockdownResponse(): Response
    {
        $message = 'Account temporarily locked due to security concerns. Please contact support for assistance.';

        if (request()->expectsJson()) {
            return response()->json([
                'error' => $message,
                'lockdown' => true,
                'admin_required' => true
            ], 423);
        }

        return response()->view('errors.security-lockdown', [
            'message' => $message
        ], 423);
    }

    /**
     * Get retry after time for specific action
     */
    private function getRetryAfter(string $action): int
    {
        $retryTimes = [
            'verification' => 900,      // 15 minutes
            'recovery_code' => 3600,    // 1 hour
            'sms_request' => 3600,      // 1 hour
            'emergency_recovery' => 86400, // 24 hours
            'setup_attempt' => 3600,    // 1 hour
            'general' => 3600           // 1 hour
        ];

        return $retryTimes[$action] ?? $retryTimes['general'];
    }
}