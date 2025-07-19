<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Log successful authentication
        if (Auth::check() && $request->is('login') && $request->isMethod('POST')) {
            AuditLog::createAuthLog('login_success', Auth::user(), [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'login_time' => now()->toISOString(),
            ]);
        }

        // Log logout
        if ($request->is('logout') && $request->isMethod('POST')) {
            $user = Auth::user();
            if ($user) {
                AuditLog::createAuthLog('logout', $user, [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'logout_time' => now()->toISOString(),
                ]);
            }
        }

        return $response;
    }
}
