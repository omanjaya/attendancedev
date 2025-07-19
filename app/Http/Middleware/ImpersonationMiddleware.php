<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is being impersonated
        if (Session::has('impersonated_by')) {
            $originalUserId = Session::get('impersonated_by');
            $currentUser = Auth::user();

            // Add impersonation context to the request
            $request->merge([
                'is_impersonating' => true,
                'original_user_id' => $originalUserId,
                'impersonated_user_id' => $currentUser->id,
            ]);

            // Add header to identify impersonation in views
            view()->share('isImpersonating', true);
            view()->share('originalUserId', $originalUserId);
        }

        return $next($request);
    }
}
