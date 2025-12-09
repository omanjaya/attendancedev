<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to enforce password change requirement
 * 
 * Users with force_password_change=true or password_changed_at=null
 * will be blocked from most API endpoints until they change their password.
 * 
 * Exceptions:
 * - Change password endpoint
 * - Logout endpoint
 * - User info endpoint (so frontend knows about the requirement)
 */
class ForcePasswordChangeMiddleware
{
    /**
     * Routes that are allowed even when password change is required
     */
    protected array $allowedRoutes = [
        'api/v1/auth/change-password',
        'api/v1/auth/logout',
        'api/v1/auth/user',
        'api/v1/auth/me',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Check if password change is required
        $mustChangePassword = $user->force_password_change === true || 
                              $user->password_changed_at === null;

        if (!$mustChangePassword) {
            return $next($request);
        }

        // Check if current route is allowed
        $currentPath = $request->path();
        foreach ($this->allowedRoutes as $allowedRoute) {
            if (str_starts_with($currentPath, $allowedRoute)) {
                return $next($request);
            }
        }

        // Block the request with appropriate response
        return response()->json([
            'success' => false,
            'message' => 'Anda harus mengganti password terlebih dahulu',
            'error' => 'password_change_required',
            'data' => [
                'force_password_change' => true,
                'redirect_to' => '/auth/change-password',
            ]
        ], 403);
    }
}
