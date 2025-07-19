<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminBypass
{
    /**
     * Handle an incoming request.
     * Bypass all permission checks for superadmin users.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // If user is super admin, bypass all permission checks
        if ($user && $user->hasRole('superadmin')) {
            return $next($request);
        }

        // Continue to next middleware (permission check)
        return $next($request);
    }
}
