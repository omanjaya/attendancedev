<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class PasswordPolicyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if user is not authenticated
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Skip password change routes to prevent infinite redirects
        if ($this->isPasswordChangeRoute($request)) {
            return $next($request);
        }

        // Skip API routes that shouldn't enforce password policy
        if ($this->isApiRoute($request)) {
            return $next($request);
        }

        // Check if user needs to change password
        if ($user->needsPasswordChange()) {
            // For AJAX requests, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password change required',
                    'redirect' => route('password.change'),
                    'force_password_change' => true
                ], 403);
            }

            // For regular requests, redirect to password change page
            return Redirect::route('password.change')
                ->with('warning', 'You must change your password before continuing.');
        }

        // Check if password is expired based on policy
        if ($this->isPasswordExpired($user)) {
            // For AJAX requests, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password has expired and must be changed',
                    'redirect' => route('password.change'),
                    'password_expired' => true
                ], 403);
            }

            // For regular requests, redirect to password change page
            return Redirect::route('password.change')
                ->with('error', 'Your password has expired. Please change it to continue.');
        }

        // Check if account is locked
        if ($user->isLocked()) {
            Auth::logout();
            
            $message = 'Your account has been locked due to security reasons. Please contact an administrator.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'account_locked' => true
                ], 403);
            }

            return Redirect::route('login')
                ->with('error', $message);
        }

        return $next($request);
    }

    /**
     * Check if the current route is a password change route.
     */
    private function isPasswordChangeRoute(Request $request): bool
    {
        $passwordChangeRoutes = [
            'password.change',
            'password.update',
            'password.request',
            'password.email',
            'password.reset',
            'password.confirm',
            'logout'
        ];

        $currentRouteName = $request->route()?->getName();
        
        return in_array($currentRouteName, $passwordChangeRoutes) || 
               str_contains($request->path(), 'password');
    }

    /**
     * Check if the current route is an API route that should be excluded.
     */
    private function isApiRoute(Request $request): bool
    {
        $excludedApiRoutes = [
            'api/v1/auth/logout',
            'api/v1/auth/user',
            'api/v1/password/change'
        ];

        $path = $request->path();
        
        // Allow specific API routes
        foreach ($excludedApiRoutes as $route) {
            if (str_contains($path, $route)) {
                return true;
            }
        }

        // Generally exclude all API routes from password policy enforcement
        // API clients should handle authentication differently
        return str_starts_with($path, 'api/');
    }

    /**
     * Check if user's password has expired based on policy.
     */
    private function isPasswordExpired($user): bool
    {
        $expiryDays = config('security.password.expiry_days', 0);
        
        // If expiry is disabled (0), password never expires
        if ($expiryDays <= 0) {
            return false;
        }

        // If user has never changed password, use creation date
        $passwordDate = $user->password_changed_at ?? $user->created_at;
        
        if (!$passwordDate) {
            return false;
        }

        return $passwordDate->addDays($expiryDays)->isPast();
    }
}