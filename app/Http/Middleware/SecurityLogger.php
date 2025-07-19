<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecurityLogger
{
    private SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Handle an incoming request and log security events.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $user = Auth::user();

        // Pre-request security check
        $this->logRequestStart($request, $user);

        $response = $next($request);

        // Post-request security logging
        $this->logRequestEnd($request, $response, $user, $startTime);

        return $response;
    }

    /**
     * Log security-relevant request information at start.
     */
    private function logRequestStart(Request $request, $user): void
    {
        $securityContext = $this->buildSecurityContext($request, $user);

        // Log high-risk actions
        if ($this->isHighRiskAction($request)) {
            Log::channel('security')->info('High-Risk Action Initiated', $securityContext);
        }

        // Detect and log suspicious patterns
        if ($user && $this->detectSuspiciousRequest($request, $user)) {
            Log::channel('security')->warning(
                'Suspicious Request Pattern Detected',
                array_merge($securityContext, [
                    'suspicious_indicators' => $this->getSuspiciousIndicators($request, $user),
                ]),
            );
        }
    }

    /**
     * Log security information after request completion.
     */
    private function logRequestEnd(
        Request $request,
        Response $response,
        $user,
        float $startTime,
    ): void {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $securityContext = $this->buildSecurityContext($request, $user, $response, $duration);

        // Log based on response status and action type
        $this->logByResponseStatus($request, $response, $securityContext);

        // Create audit log entry for tracked actions
        if ($this->shouldCreateAuditLog($request, $response)) {
            $this->createAuditLogEntry($request, $response, $user, $securityContext);
        }

        // Log performance anomalies
        if ($duration > 5000) {
            // 5 seconds
            Log::channel('security')->warning(
                'Slow Request Performance',
                array_merge($securityContext, [
                    'performance_issue' => true,
                    'threshold_exceeded' => '5s',
                ]),
            );
        }
    }

    /**
     * Build comprehensive security context for logging.
     */
    private function buildSecurityContext(
        Request $request,
        $user = null,
        ?Response $response = null,
        ?float $duration = null,
    ): array {
        $context = [
            'timestamp' => now()->toISOString(),
            'request_id' => $request->header('X-Request-ID') ?? uniqid(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route_name' => $request->route()?->getName(),
            'session_id' => session()->getId(),
        ];

        // Add user information if authenticated
        if ($user) {
            $context['user_id'] = $user->id;
            $context['user_email'] = $user->email;
            $context['user_roles'] = $user->getRoleNames()->toArray();
        }

        // Add response information if available
        if ($response) {
            $context['response_status'] = $response->getStatusCode();
            $context['response_size'] = strlen($response->getContent());
        }

        // Add timing information
        if ($duration !== null) {
            $context['duration_ms'] = $duration;
        }

        // Add security-specific context
        $context['device_fingerprint'] = $this->generateDeviceFingerprint($request);
        $context['is_mobile'] = $this->isMobileDevice($request);
        $context['country_code'] = $this->getCountryFromIP($request->ip());
        $context['risk_level'] = $this->calculateRequestRiskLevel($request, $user);

        return $context;
    }

    /**
     * Determine if this is a high-risk security action.
     */
    private function isHighRiskAction(Request $request): bool
    {
        $highRiskPatterns = [
            '/2fa/',
            '/password/',
            '/admin/',
            '/api/auth/',
            '/emergency/',
            '/security/',
            '/backup/',
            '/logs/',
        ];

        $path = $request->getPathInfo();

        foreach ($highRiskPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect suspicious request patterns.
     */
    private function detectSuspiciousRequest(Request $request, $user): bool
    {
        // Check for rapid successive requests
        if ($this->hasRapidRequests($user)) {
            return true;
        }

        // Check for unusual user agent patterns
        if ($this->hasUnusualUserAgent($request)) {
            return true;
        }

        // Check for privilege escalation attempts
        if ($this->hasPrivilegeEscalationAttempt($request, $user)) {
            return true;
        }

        // Check for unusual access patterns
        if ($this->hasUnusualAccessPattern($request, $user)) {
            return true;
        }

        return false;
    }

    /**
     * Get specific suspicious indicators.
     */
    private function getSuspiciousIndicators(Request $request, $user): array
    {
        $indicators = [];

        if ($this->hasRapidRequests($user)) {
            $indicators[] = 'rapid_requests';
        }

        if ($this->hasUnusualUserAgent($request)) {
            $indicators[] = 'unusual_user_agent';
        }

        if ($this->hasPrivilegeEscalationAttempt($request, $user)) {
            $indicators[] = 'privilege_escalation_attempt';
        }

        if ($this->hasUnusualAccessPattern($request, $user)) {
            $indicators[] = 'unusual_access_pattern';
        }

        return $indicators;
    }

    /**
     * Log based on HTTP response status.
     */
    private function logByResponseStatus(Request $request, Response $response, array $context): void
    {
        $status = $response->getStatusCode();

        switch (true) {
            case $status >= 500:
                Log::channel('security')->error('Server Error Response', $context);
                break;

            case $status === 423: // Locked
                Log::channel('security')->warning('Account/Resource Locked', $context);
                break;

            case $status === 429: // Too Many Requests
                Log::channel('security')->warning('Rate Limit Exceeded', $context);
                break;

            case $status === 403: // Forbidden
                Log::channel('security')->warning('Access Forbidden', $context);
                break;

            case $status === 401: // Unauthorized
                Log::channel('security')->info('Authentication Required', $context);
                break;

            case $status >= 400:
                Log::channel('security')->info('Client Error Response', $context);
                break;

            case $this->isSecuritySuccessAction($request):
                Log::channel('security')->info('Security Action Successful', $context);
                break;
        }
    }

    /**
     * Determine if an audit log should be created.
     */
    private function shouldCreateAuditLog(Request $request, Response $response): bool
    {
        // Always log 2FA-related actions
        if (str_contains($request->getPathInfo(), '2fa')) {
            return true;
        }

        // Log authentication actions
        if (
            str_contains($request->getPathInfo(), '/login') ||
            str_contains($request->getPathInfo(), '/logout')
        ) {
            return true;
        }

        // Log password changes
        if (str_contains($request->getPathInfo(), '/password')) {
            return true;
        }

        // Log admin actions
        if (str_contains($request->getPathInfo(), '/admin')) {
            return true;
        }

        // Log failed requests
        if ($response->getStatusCode() >= 400) {
            return true;
        }

        return false;
    }

    /**
     * Create comprehensive audit log entry.
     */
    private function createAuditLogEntry(
        Request $request,
        Response $response,
        $user,
        array $context,
    ): void {
        $action = $this->determineAuditAction($request, $response);
        $riskLevel = $this->determineRiskLevel($request, $response, $user);

        AuditLog::create([
            'user_id' => $user?->id,
            'event_type' => $this->determineEventType($request, $response),
            'action' => $action,
            'auditable_type' => $this->getAuditableType($request),
            'auditable_id' => $this->getAuditableId($request, $user),
            'old_values' => $this->getOldValues($request),
            'new_values' => $this->getNewValues($request, $response),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'tags' => $this->generateTags($request, $response, $riskLevel),
        ]);
    }

    /**
     * Determine audit action from request context.
     */
    private function determineAuditAction(Request $request, Response $response): string
    {
        $path = $request->getPathInfo();
        $method = $request->method();
        $status = $response->getStatusCode();

        // 2FA-specific actions
        if (str_contains($path, '2fa/verify')) {
            return $status === 200 ? '2fa_verification_success' : '2fa_verification_failed';
        }

        if (str_contains($path, '2fa/enable')) {
            return $status === 200 ? '2fa_enabled' : '2fa_enable_failed';
        }

        if (str_contains($path, '2fa/disable')) {
            return $status === 200 ? '2fa_disabled' : '2fa_disable_failed';
        }

        if (str_contains($path, '2fa/recovery')) {
            return $status === 200 ? '2fa_recovery_success' : '2fa_recovery_failed';
        }

        if (str_contains($path, '2fa/emergency')) {
            return '2fa_emergency_recovery_requested';
        }

        // Authentication actions
        if (str_contains($path, '/login')) {
            return $status === 200 ? 'login_success' : 'login_failed';
        }

        if (str_contains($path, '/logout')) {
            return 'logout';
        }

        // Password actions
        if (str_contains($path, '/password')) {
            return $status === 200 ? 'password_changed' : 'password_change_failed';
        }

        // Generic actions based on HTTP method and status
        if ($status >= 400) {
            return "request_failed_{$method}";
        }

        return match ($method) {
            'POST' => 'resource_created',
            'PUT', 'PATCH' => 'resource_updated',
            'DELETE' => 'resource_deleted',
            default => 'resource_accessed',
        };
    }

    /**
     * Determine risk level for audit entry.
     */
    private function determineRiskLevel(Request $request, Response $response, $user): string
    {
        $status = $response->getStatusCode();

        // Critical risk levels
        if ($status === 423 || $status === 429) {
            return 'critical';
        }

        if (str_contains($request->getPathInfo(), 'emergency')) {
            return 'critical';
        }

        // High risk levels
        if ($status >= 500 || $status === 403) {
            return 'high';
        }

        if (str_contains($request->getPathInfo(), '2fa') && $status >= 400) {
            return 'high';
        }

        // Medium risk levels
        if ($status >= 400) {
            return 'medium';
        }

        if ($this->isHighRiskAction($request)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Helper methods for audit log creation.
     */
    private function getAuditableType(Request $request): ?string
    {
        if (str_contains($request->getPathInfo(), '2fa')) {
            return 'App\\Models\\User';
        }

        return null;
    }

    private function getAuditableId(Request $request, $user): ?int
    {
        return $user?->id;
    }

    private function getOldValues(Request $request): array
    {
        // Return relevant old values for update operations
        return [];
    }

    private function getNewValues(Request $request, Response $response): array
    {
        $newValues = [];

        // Include relevant request data (excluding sensitive information)
        $input = $request->except(['password', 'password_confirmation', 'token', 'code']);

        if (! empty($input)) {
            $newValues['request_data'] = $input;
        }

        $newValues['response_status'] = $response->getStatusCode();

        return $newValues;
    }

    /**
     * Security detection helper methods.
     */
    private function hasRapidRequests($user): bool
    {
        if (! $user) {
            return false;
        }

        $recentRequests = cache()->get("user_requests_{$user->id}", []);
        $now = time();

        // Clean old requests (older than 1 minute)
        $recentRequests = array_filter($recentRequests, fn ($timestamp) => $now - $timestamp < 60);

        return count($recentRequests) > 30; // More than 30 requests per minute
    }

    private function hasUnusualUserAgent(Request $request): bool
    {
        $userAgent = $request->userAgent();

        // Check for common bot/scanner patterns
        $suspiciousPatterns = [
            'curl',
            'wget',
            'python',
            'bot',
            'crawler',
            'scanner',
            'sqlmap',
            'nikto',
            'nmap',
            'masscan',
            'zap',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (str_contains(strtolower($userAgent), $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function hasPrivilegeEscalationAttempt(Request $request, $user): bool
    {
        if (! $user) {
            return false;
        }

        // Check if user is attempting to access admin routes without admin role
        if (
            str_contains($request->getPathInfo(), '/admin') &&
            ! $user->hasRole(['admin', 'superadmin'])
        ) {
            return true;
        }

        return false;
    }

    private function hasUnusualAccessPattern(Request $request, $user): bool
    {
        if (! $user) {
            return false;
        }

        // Check if accessing from unusual IP
        $userIPs = cache()->get("user_ips_{$user->id}", []);
        $currentIP = $request->ip();

        if (! empty($userIPs) && ! in_array($currentIP, $userIPs)) {
            return true;
        }

        return false;
    }

    private function isSecuritySuccessAction(Request $request): bool
    {
        $securityPaths = ['2fa', 'login', 'password'];

        foreach ($securityPaths as $path) {
            if (str_contains($request->getPathInfo(), $path)) {
                return true;
            }
        }

        return false;
    }

    private function generateDeviceFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent(),
            $request->header('Accept'),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
            $request->header('Accept-Charset'),
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    private function isMobileDevice(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent());
        $mobileKeywords = ['mobile', 'android', 'iphone', 'ipad', 'ipod', 'blackberry', 'tablet'];

        foreach ($mobileKeywords as $keyword) {
            if (str_contains($userAgent, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function getCountryFromIP(string $ip): ?string
    {
        // Placeholder for IP geolocation service
        // You could integrate with MaxMind GeoIP or similar service
        return null;
    }

    private function calculateRequestRiskLevel(Request $request, $user): string
    {
        $riskScore = 0;

        // Risk factors
        if ($this->isHighRiskAction($request)) {
            $riskScore += 3;
        }
        if ($this->hasUnusualUserAgent($request)) {
            $riskScore += 2;
        }
        if ($user && $this->hasPrivilegeEscalationAttempt($request, $user)) {
            $riskScore += 4;
        }
        if ($user && $this->hasRapidRequests($user)) {
            $riskScore += 2;
        }
        if ($user && $this->hasUnusualAccessPattern($request, $user)) {
            $riskScore += 2;
        }

        return match (true) {
            $riskScore >= 6 => 'critical',
            $riskScore >= 4 => 'high',
            $riskScore >= 2 => 'medium',
            default => 'low',
        };
    }

    /**
     * Determine event type for audit log.
     */
    private function determineEventType(Request $request, Response $response): string
    {
        $method = $request->method();
        $path = $request->getPathInfo();
        $status = $response->getStatusCode();

        // Authentication events
        if (str_contains($path, 'login')) {
            return $status < 400 ? 'auth.login.success' : 'auth.login.failed';
        }

        if (str_contains($path, 'logout')) {
            return 'auth.logout';
        }

        if (str_contains($path, '2fa')) {
            return $status < 400 ? 'auth.2fa.success' : 'auth.2fa.failed';
        }

        // Password events
        if (str_contains($path, 'password')) {
            return $status < 400 ? 'auth.password.changed' : 'auth.password.failed';
        }

        // API events
        if (str_contains($path, '/api/')) {
            return match ($method) {
                'GET' => 'api.read',
                'POST' => 'api.create',
                'PUT', 'PATCH' => 'api.update',
                'DELETE' => 'api.delete',
                default => 'api.request',
            };
        }

        // Device management
        if (str_contains($path, 'device')) {
            return match ($method) {
                'POST' => 'device.trust',
                'DELETE' => 'device.remove',
                'PATCH' => 'device.update',
                default => 'device.access',
            };
        }

        // Security events
        if (str_contains($path, 'security')) {
            return 'security.access';
        }

        // Default web request
        return match ($method) {
            'GET' => 'web.view',
            'POST' => 'web.create',
            'PUT', 'PATCH' => 'web.update',
            'DELETE' => 'web.delete',
            default => 'web.request',
        };
    }

    /**
     * Generate tags for categorizing audit events.
     */
    private function generateTags(Request $request, Response $response, string $riskLevel): array
    {
        $tags = [];

        // Add risk level tag
        $tags[] = "risk:$riskLevel";

        // Add method tag
        $tags[] = 'method:'.strtolower($request->method());

        // Add status code category
        $status = $response->getStatusCode();
        $statusCategory = match (true) {
            $status < 300 => 'success',
            $status < 400 => 'redirect',
            $status < 500 => 'client_error',
            default => 'server_error',
        };
        $tags[] = "status:$statusCategory";

        // Add path-based tags
        $path = $request->getPathInfo();
        if (str_contains($path, '/api/')) {
            $tags[] = 'api';
        }
        if (str_contains($path, 'admin')) {
            $tags[] = 'admin';
        }
        if (str_contains($path, '2fa')) {
            $tags[] = 'security';
            $tags[] = '2fa';
        }
        if (str_contains($path, 'device')) {
            $tags[] = 'device';
        }
        if (str_contains($path, 'notification')) {
            $tags[] = 'notification';
        }

        // Add authentication status
        if (auth()->check()) {
            $tags[] = 'authenticated';
        } else {
            $tags[] = 'guest';
        }

        // Add time-based tags
        $hour = now()->hour;
        $tags[] = match (true) {
            $hour >= 6 && $hour < 12 => 'morning',
            $hour >= 12 && $hour < 18 => 'afternoon',
            $hour >= 18 && $hour < 22 => 'evening',
            default => 'night',
        };

        return $tags;
    }
}
