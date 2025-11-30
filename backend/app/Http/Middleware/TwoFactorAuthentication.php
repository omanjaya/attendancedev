<?php

namespace App\Http\Middleware;

use App\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuthentication
{
    private TwoFactorService $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If user is not authenticated, let them through (handled by auth middleware)
        if (! $user) {
            return $next($request);
        }

        // Skip 2FA for API routes with valid token authentication
        if ($request->is('api/*') && $user->tokenCan('*')) {
            return $next($request);
        }

        // Skip 2FA check for 2FA-related routes to prevent loops
        if ($this->isExcludedRoute($request)) {
            return $next($request);
        }

        // Check if user has 2FA enabled
        if (! $user->two_factor_enabled) {
            // Check if 2FA is required for this user's role
            if ($this->twoFactorService->isRequiredForUser($user)) {
                return $this->redirectToSetup($request);
            }

            // 2FA not required, continue
            return $next($request);
        }

        // Check if session is already 2FA verified
        if ($this->twoFactorService->isSessionVerified($user)) {
            // Check if verification is still valid (timeout check)
            if ($this->isVerificationExpired($request)) {
                $this->twoFactorService->clearSessionVerification();

                return $this->redirectToVerification($request);
            }

            return $next($request);
        }

        // User has 2FA enabled but session is not verified
        return $this->redirectToVerification($request);
    }

    /**
     * Check if the current route should be excluded from 2FA verification.
     */
    private function isExcludedRoute(Request $request): bool
    {
        $excludedRoutes = [
            // 2FA setup and verification routes
            '2fa/*',
            'two-factor/*',

            // Authentication routes
            'login',
            'logout',
            'register',
            'password/*',
            'email/*',

            // API endpoints for 2FA
            'api/*/two-factor/*',

            // Static assets and health checks
            'health',
            'up',
            '_ignition/*',

            // Profile routes (user should be able to manage 2FA)
            'profile',
            'settings',
        ];

        foreach ($excludedRoutes as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if 2FA verification has expired.
     */
    private function isVerificationExpired(Request $request): bool
    {
        $verifiedAt = session('2fa_verified_at');

        if (! $verifiedAt) {
            return true;
        }

        // Get timeout duration from config (default: 2 hours)
        $timeoutMinutes = config('auth.2fa.session_timeout', 120);
        $timeoutSeconds = $timeoutMinutes * 60;

        return time() - $verifiedAt > $timeoutSeconds;
    }

    /**
     * Redirect to 2FA setup.
     */
    private function redirectToSetup(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(
                [
                    'message' => 'Two-factor authentication setup required.',
                    'redirect' => route('2fa.setup'),
                    'required' => true,
                ],
                403,
            );
        }

        session()->flash(
            'warning',
            'Two-factor authentication is required for your account. Please complete the setup process.',
        );

        return redirect()->route('2fa.setup')->with('intended', $request->fullUrl());
    }

    /**
     * Redirect to 2FA verification.
     */
    private function redirectToVerification(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(
                [
                    'message' => 'Two-factor authentication verification required.',
                    'redirect' => route('2fa.verify'),
                    'required' => true,
                ],
                403,
            );
        }

        return redirect()->route('2fa.verify')->with('intended', $request->fullUrl());
    }

    /**
     * Store verification timestamp in session.
     */
    public static function markSessionAsVerified(): void
    {
        session([
            '2fa_verified' => Auth::id(),
            '2fa_verified_at' => time(),
            '2fa_device_fingerprint' => request()->header('X-Device-Fingerprint'),
            '2fa_ip_address' => request()->ip(),
        ]);
    }
}
