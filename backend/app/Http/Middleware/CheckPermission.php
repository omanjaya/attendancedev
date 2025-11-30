<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, $permission): Response
    {
        if (! auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        $user = auth()->user();

        // Force fresh load of roles and permissions to ensure consistency
        $user->load('roles.permissions');

        // SUPER ADMIN BYPASS: Super admin has access to everything
        if ($user->hasRole('superadmin') || $user->hasRole('Super Admin')) {
            return $next($request);
        }

        // Simple permission check - no complex OR logic
        $hasPermission = $user->can($permission);

        if (! $hasPermission) {
            // Log permission denied for security audit
            \Log::warning('Permission Denied', [
                'user_id' => $user->id,
                'permission' => $permission,
                'user_roles' => $user->roles->pluck('name')->toArray(),
                'url' => $request->url(),
                'ip' => $request->ip(),
                'expects_json' => $request->expectsJson(),
                'wants_json' => $request->wantsJson(),
                'xhr' => $request->header('X-Requested-With') === 'XMLHttpRequest',
                'accept_header' => $request->header('Accept'),
            ]);

            // Check multiple ways to detect AJAX/JSON request
            if ($request->expectsJson() || 
                $request->wantsJson() || 
                $request->header('X-Requested-With') === 'XMLHttpRequest' ||
                str_contains($request->header('Accept', ''), 'application/json')) {
                
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You do not have the required permission.',
                    'required_permission' => $permission
                ], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
