<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Content Security Policy
        $csp = $this->buildContentSecurityPolicy();
        $response->headers->set('Content-Security-Policy', $csp);

        // Security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', $this->buildPermissionsPolicy());

        // HSTS (only for HTTPS)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Remove server information
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');

        // Cache control for sensitive pages
        if ($this->isSensitivePage($request)) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }

    /**
     * Build Content Security Policy header.
     */
    private function buildContentSecurityPolicy(): string
    {
        $policies = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "img-src 'self' data: https: blob:",
            "font-src 'self' https://cdn.jsdelivr.net",
            "connect-src 'self' https:",
            "media-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "upgrade-insecure-requests"
        ];

        // Add development exceptions
        if (app()->environment('local', 'development')) {
            $policies[] = "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:* ws://localhost:* https://cdn.jsdelivr.net";
            $policies[] = "connect-src 'self' ws://localhost:* http://localhost:* https:";
        }

        return implode('; ', $policies);
    }

    /**
     * Build Permissions Policy header.
     */
    private function buildPermissionsPolicy(): string
    {
        $policies = [
            'camera=self',           // Required for face detection
            'microphone=()',         // Disable microphone
            'geolocation=self',      // Required for GPS verification
            'accelerometer=()',      // Disable accelerometer
            'autoplay=()',          // Disable autoplay
            'encrypted-media=()',    // Disable encrypted media
            'fullscreen=()',        // Disable fullscreen
            'payment=()',           // Disable payment API
            'picture-in-picture=()', // Disable picture-in-picture
            'usb=()',               // Disable USB
        ];

        return implode(', ', $policies);
    }

    /**
     * Check if the current page contains sensitive information.
     */
    private function isSensitivePage(Request $request): bool
    {
        $sensitiveRoutes = [
            'attendance.*',
            'employees.*',
            'payroll.*',
            'admin.*',
            'audit.*',
            'performance.*',
        ];

        foreach ($sensitiveRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }
}