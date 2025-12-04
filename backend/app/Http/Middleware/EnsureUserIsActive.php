<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // Check if user is active
            if (! $user->is_active) {
                // Optional: Revoke tokens if inactive
                // $user->tokens()->delete();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Akun ini telah dinonaktifkan. Silakan hubungi administrator.'
                ], 403);
            }

            // Check if account is locked
            if (method_exists($user, 'isLocked') && $user->isLocked()) {
                $lockTime = $user->locked_until ? $user->locked_until->diffForHumans() : 'indefinitely';
                
                return response()->json([
                    'success' => false,
                    'message' => "Akun terkunci. Silakan coba lagi {$lockTime}."
                ], 403);
            }
        }

        return $next($request);
    }
}
