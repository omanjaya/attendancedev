<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Jenssegers\Agent\Agent;

class DeviceService
{
    private Agent $agent;

    public function __construct()
    {
        $this->agent = new Agent;
    }

    /**
     * Generate a comprehensive device fingerprint.
     */
    public function generateFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent(),
            $request->header('Accept'),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
            $request->header('Accept-Charset'),
            $request->header('DNT'),
            $request->header('Connection'),
            $request->header('Upgrade-Insecure-Requests'),
            $this->getScreenResolutionFromHeaders($request),
            $this->getTimezoneFromHeaders($request),
        ];

        // Remove null values and join
        $fingerprintString = implode('|', array_filter($components));

        return hash('sha256', $fingerprintString);
    }

    /**
     * Collect detailed device information.
     */
    public function collectDeviceInfo(Request $request): array
    {
        $this->agent->setUserAgent($request->userAgent());

        return [
            'device_type' => $this->getDeviceType(),
            'browser_name' => $this->agent->browser(),
            'browser_version' => $this->agent->version($this->agent->browser()),
            'os_name' => $this->agent->platform(),
            'os_version' => $this->agent->version($this->agent->platform()),
            'is_robot' => $this->agent->isRobot(),
            'robot_name' => $this->agent->robot(),
            'device_name' => $this->agent->device(),
            'languages' => $this->agent->languages(),
            'user_agent' => $request->userAgent(),
            'headers' => [
                'accept' => $request->header('Accept'),
                'accept_language' => $request->header('Accept-Language'),
                'accept_encoding' => $request->header('Accept-Encoding'),
                'accept_charset' => $request->header('Accept-Charset'),
                'dnt' => $request->header('DNT'),
            ],
        ];
    }

    /**
     * Track device for user.
     */
    public function trackDevice(User $user, Request $request): UserDevice
    {
        $fingerprint = $this->generateFingerprint($request);
        $deviceInfo = $this->collectDeviceInfo($request);

        $device = UserDevice::firstOrNew([
            'user_id' => $user->id,
            'device_fingerprint' => $fingerprint,
        ]);

        if ($device->exists) {
            // Update existing device
            $device->updateLastSeen($request->ip());
        } else {
            // Create new device record
            $device->fill([
                'device_type' => $this->getDeviceType(),
                'browser_name' => $deviceInfo['browser_name'],
                'browser_version' => $deviceInfo['browser_version'],
                'os_name' => $deviceInfo['os_name'],
                'os_version' => $deviceInfo['os_version'],
                'last_seen_at' => now(),
                'last_ip_address' => $request->ip(),
                'fingerprint_data' => $deviceInfo,
                'metadata' => [
                    'languages' => $deviceInfo['languages'],
                    'device_name' => $deviceInfo['device_name'],
                ],
            ]);
            $device->save();

            // Clean up old cache entries
            $this->cleanupOldDeviceCache($user);
        }

        return $device;
    }

    /**
     * Check if device is new for user.
     */
    public function isNewDevice(User $user, Request $request): bool
    {
        $fingerprint = $this->generateFingerprint($request);

        return ! UserDevice::where('user_id', $user->id)
            ->where('device_fingerprint', $fingerprint)
            ->exists();
    }

    /**
     * Check if device is trusted.
     */
    public function isTrustedDevice(User $user, Request $request): bool
    {
        $fingerprint = $this->generateFingerprint($request);

        $device = UserDevice::where('user_id', $user->id)
            ->where('device_fingerprint', $fingerprint)
            ->first();

        return $device && $device->is_trusted;
    }

    /**
     * Get user's devices.
     */
    public function getUserDevices(User $user, bool $onlyActive = false)
    {
        $query = $user->devices()->orderBy('last_seen_at', 'desc');

        if ($onlyActive) {
            $query->recentlyActive();
        }

        return $query->get();
    }

    /**
     * Trust a device.
     */
    public function trustDevice(UserDevice $device, User $user): bool
    {
        if ($device->user_id !== $user->id) {
            return false;
        }

        $device->markAsTrusted();

        // Log the action
        if (function_exists('activity')) {
            activity()->performedOn($device)->causedBy($user)->log('Device marked as trusted');
        }

        return true;
    }

    /**
     * Revoke device trust.
     */
    public function revokeTrust(UserDevice $device, User $user): bool
    {
        if ($device->user_id !== $user->id) {
            return false;
        }

        $device->revokeTrust();

        // Log the action
        if (function_exists('activity')) {
            activity()->performedOn($device)->causedBy($user)->log('Device trust revoked');
        }

        return true;
    }

    /**
     * Remove a device.
     */
    public function removeDevice(UserDevice $device, User $user): bool
    {
        if ($device->user_id !== $user->id) {
            return false;
        }

        $device->delete();

        // Log the action
        if (function_exists('activity')) {
            activity()->performedOn($user)->causedBy($user)->log('Device removed');
        }

        return true;
    }

    /**
     * Get device type.
     */
    private function getDeviceType(): string
    {
        if ($this->agent->isMobile()) {
            return 'mobile';
        } elseif ($this->agent->isTablet()) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }

    /**
     * Extract screen resolution from headers if available.
     */
    private function getScreenResolutionFromHeaders(Request $request): ?string
    {
        // Some clients send screen info in custom headers
        return $request->header('X-Screen-Resolution');
    }

    /**
     * Extract timezone from headers if available.
     */
    private function getTimezoneFromHeaders(Request $request): ?string
    {
        // Some clients send timezone in custom headers
        return $request->header('X-Timezone');
    }

    /**
     * Clean up old device cache entries.
     */
    private function cleanupOldDeviceCache(User $user): void
    {
        // Remove old cache-based device tracking
        Cache::forget("user_devices_{$user->id}");
    }

    /**
     * Generate a secure device token for trusted devices.
     */
    public function generateDeviceToken(UserDevice $device): string
    {
        $payload = [
            'device_id' => $device->id,
            'user_id' => $device->user_id,
            'fingerprint' => $device->device_fingerprint,
            'expires_at' => now()->addDays(30)->timestamp,
        ];

        return encrypt($payload);
    }

    /**
     * Verify device token.
     */
    public function verifyDeviceToken(string $token): ?array
    {
        try {
            $payload = decrypt($token);

            if ($payload['expires_at'] < now()->timestamp) {
                return null;
            }

            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }
}
