<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IPWhitelist
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $clientIP = $this->getClientIP($request);

        // Check if IP restrictions are enabled
        if (! config('security.ip_whitelist.enabled', false)) {
            return $next($request);
        }

        // Get allowed IPs for the current route/guard
        $allowedIPs = $this->getAllowedIPs($guards);

        // Check if client IP is whitelisted
        if (! $this->isIPAllowed($clientIP, $allowedIPs)) {
            $this->logUnauthorizedAccess($request, $clientIP);

            return response()->json(
                [
                    'error' => 'Access denied from this IP address',
                    'ip' => $clientIP,
                ],
                403,
            );
        }

        return $next($request);
    }

    /**
     * Get the real client IP address.
     */
    private function getClientIP(Request $request): string
    {
        // Check for various headers that might contain the real IP
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP', // Nginx
            'HTTP_X_FORWARDED_FOR', // Load balancers
            'HTTP_X_FORWARDED', // Proxies
            'HTTP_X_CLUSTER_CLIENT_IP', // Cluster
            'HTTP_FORWARDED_FOR', // Standard
            'HTTP_FORWARDED', // Standard
            'REMOTE_ADDR', // Standard
        ];

        foreach ($headers as $header) {
            if ($request->server($header)) {
                $ips = explode(',', $request->server($header));
                $ip = trim($ips[0]);

                if (
                    filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                ) {
                    return $ip;
                }
            }
        }

        return $request->ip();
    }

    /**
     * Get allowed IPs based on guards/roles.
     */
    private function getAllowedIPs(array $guards): array
    {
        $allowedIPs = config('security.ip_whitelist.global', []);

        // Add guard-specific IPs
        foreach ($guards as $guard) {
            $guardIPs = config("security.ip_whitelist.guards.{$guard}", []);
            $allowedIPs = array_merge($allowedIPs, $guardIPs);
        }

        // Add role-specific IPs if user is authenticated
        if (auth()->check()) {
            $userRoles = auth()->user()->getRoleNames();

            foreach ($userRoles as $role) {
                $roleIPs = config("security.ip_whitelist.roles.{$role}", []);
                $allowedIPs = array_merge($allowedIPs, $roleIPs);
            }
        }

        return array_unique($allowedIPs);
    }

    /**
     * Check if IP is in the allowed list.
     */
    private function isIPAllowed(string $clientIP, array $allowedIPs): bool
    {
        // If no restrictions configured, allow all
        if (empty($allowedIPs)) {
            return true;
        }

        foreach ($allowedIPs as $allowedIP) {
            if ($this->matchesIPPattern($clientIP, $allowedIP)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP matches pattern (supports CIDR notation).
     */
    private function matchesIPPattern(string $clientIP, string $pattern): bool
    {
        // Exact match
        if ($clientIP === $pattern) {
            return true;
        }

        // CIDR notation
        if (str_contains($pattern, '/')) {
            return $this->ipInRange($clientIP, $pattern);
        }

        // Wildcard pattern (e.g., 192.168.1.*)
        if (str_contains($pattern, '*')) {
            $regex = str_replace('*', '\d+', preg_quote($pattern, '/'));

            return preg_match("/^{$regex}$/", $clientIP);
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range.
     */
    private function ipInRange(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);

        if ((ip2long($ip) & ~((1 << 32 - $mask) - 1)) === ip2long($subnet)) {
            return true;
        }

        return false;
    }

    /**
     * Log unauthorized access attempt.
     */
    private function logUnauthorizedAccess(Request $request, string $clientIP): void
    {
        $data = [
            'ip_address' => $clientIP,
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString(),
        ];

        // Log to Laravel log
        Log::warning('Unauthorized IP access attempt', $data);

        // Create audit log if user is authenticated
        if (auth()->check()) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'unauthorized_ip_access',
                'auditable_type' => 'App\Models\User',
                'auditable_id' => auth()->id(),
                'old_values' => [],
                'new_values' => $data,
                'ip_address' => $clientIP,
                'user_agent' => $request->userAgent(),
                'risk_level' => 'high',
            ]);
        }
    }
}
