<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class EnsurePersistentAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware during authentication process to avoid conflicts
        if ($request->is('login') && $request->isMethod('POST')) {
            return $next($request);
        }

        // If user is authenticated and has remember token but session is about to expire
        if (Auth::check() && Auth::user()->remember_token) {
            // Extend session lifetime for authenticated users with remember token
            config(['session.lifetime' => 43200]); // 30 days

            // Refresh the remember cookie
            $cookieName = 'remember_'.config('app.name');
            if ($request->hasCookie($cookieName)) {
                Cookie::queue(
                    $cookieName,
                    $request->cookie($cookieName),
                    525600, // 365 days
                    config('session.path'),
                    config('session.domain'),
                    config('session.secure'),
                    true, // HttpOnly
                    false,
                    config('session.same_site')
                );
            }
        }

        return $next($request);
    }
}
