<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use App\Services\DeviceService;
use App\Services\SecurityNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeviceController extends Controller
{
    private DeviceService $deviceService;

    private SecurityNotificationService $notificationService;

    public function __construct(
        DeviceService $deviceService,
        SecurityNotificationService $notificationService,
    ) {
        $this->deviceService = $deviceService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get user's devices.
     */
    public function index(Request $request): JsonResponse
    {
        $devices = $this->deviceService->getUserDevices($request->user());

        return response()->json([
            'devices' => $devices->map(function ($device) {
                return [
                    'id' => $device->id,
                    'display_name' => $device->display_name,
                    'device_type' => $device->device_type,
                    'browser_name' => $device->browser_name,
                    'os_name' => $device->os_name,
                    'is_trusted' => $device->is_trusted,
                    'is_current' => $device->device_fingerprint === $this->deviceService->generateFingerprint(request()),
                    'last_seen_at' => $device->last_seen_at,
                    'last_ip_address' => $device->last_ip_address,
                    'last_location' => $device->last_location,
                    'login_count' => $device->login_count,
                ];
            }),
        ]);
    }

    /**
     * Get current device info.
     */
    public function current(Request $request): JsonResponse
    {
        $fingerprint = $this->deviceService->generateFingerprint($request);
        $device = UserDevice::where('user_id', $request->user()->id)
            ->where('device_fingerprint', $fingerprint)
            ->first();

        if (! $device) {
            return response()->json(
                [
                    'message' => 'Device not found',
                    'is_new' => true,
                ],
                404,
            );
        }

        return response()->json([
            'device' => [
                'id' => $device->id,
                'display_name' => $device->display_name,
                'device_type' => $device->device_type,
                'is_trusted' => $device->is_trusted,
                'last_seen_at' => $device->last_seen_at,
            ],
        ]);
    }

    /**
     * Update device name.
     */
    public function updateName(Request $request, UserDevice $device): JsonResponse
    {
        // Verify ownership
        if ($device->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $device->update(['device_name' => $request->name]);

        return response()->json([
            'message' => 'Device name updated successfully',
            'device' => [
                'id' => $device->id,
                'display_name' => $device->display_name,
            ],
        ]);
    }

    /**
     * Trust a device.
     */
    public function trust(Request $request, UserDevice $device): JsonResponse
    {
        // Verify ownership
        if ($device->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Require 2FA verification for trusting devices
        if ($request->user()->hasTwoFactorEnabled()) {
            $request->validate([
                'code' => 'required|string',
            ]);

            $google2fa = app('pragmarx.google2fa');
            $valid = $google2fa->verifyKey($request->user()->getTwoFactorSecret(), $request->code);

            if (! $valid) {
                throw ValidationException::withMessages([
                    'code' => ['Invalid 2FA code'],
                ]);
            }
        }

        $this->deviceService->trustDevice($device, $request->user());

        // Send device trusted notification
        $this->notificationService->notifyDeviceTrusted($request->user(), $device, $request);

        return response()->json([
            'message' => 'Device trusted successfully',
            'device' => [
                'id' => $device->id,
                'is_trusted' => true,
                'trusted_at' => $device->fresh()->trusted_at,
            ],
        ]);
    }

    /**
     * Revoke device trust.
     */
    public function revokeTrust(Request $request, UserDevice $device): JsonResponse
    {
        // Verify ownership
        if ($device->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->deviceService->revokeTrust($device, $request->user());

        return response()->json([
            'message' => 'Device trust revoked successfully',
        ]);
    }

    /**
     * Remove a device.
     */
    public function destroy(Request $request, UserDevice $device): JsonResponse
    {
        // Verify ownership
        if ($device->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Don't allow removing current device
        $currentFingerprint = $this->deviceService->generateFingerprint($request);
        if ($device->device_fingerprint === $currentFingerprint) {
            return response()->json(
                [
                    'message' => 'Cannot remove current device',
                ],
                400,
            );
        }

        $this->deviceService->removeDevice($device, $request->user());

        return response()->json([
            'message' => 'Device removed successfully',
        ]);
    }

    /**
     * Remove all devices except current.
     */
    public function removeAll(Request $request): JsonResponse
    {
        // Require 2FA verification
        if ($request->user()->hasTwoFactorEnabled()) {
            $request->validate([
                'code' => 'required|string',
            ]);

            $google2fa = app('pragmarx.google2fa');
            $valid = $google2fa->verifyKey($request->user()->getTwoFactorSecret(), $request->code);

            if (! $valid) {
                throw ValidationException::withMessages([
                    'code' => ['Invalid 2FA code'],
                ]);
            }
        }

        $currentFingerprint = $this->deviceService->generateFingerprint($request);
        $removedCount = 0;

        $devices = $request
            ->user()
            ->devices()
            ->where('device_fingerprint', '!=', $currentFingerprint)
            ->get();

        foreach ($devices as $device) {
            $this->deviceService->removeDevice($device, $request->user());
            $removedCount++;
        }

        return response()->json([
            'message' => "Removed {$removedCount} devices successfully",
            'removed_count' => $removedCount,
        ]);
    }
}
