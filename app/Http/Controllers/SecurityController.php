<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserDevice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecurityController extends Controller
{
    /**
     * Display the security dashboard.
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Security metrics
        $metrics = [
            'total_devices' => UserDevice::where('user_id', $user->id)->count(),
            'trusted_devices' => UserDevice::where('user_id', $user->id)->where('is_trusted', true)->count(),
            'recent_logins' => AuditLog::where('user_id', $user->id)
                ->where('event_type', 'auth')
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count(),
            'security_events' => AuditLog::where('user_id', $user->id)
                ->where('risk_level', 'high')
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->count(),
        ];

        // Recent security events
        $recentEvents = AuditLog::where('user_id', $user->id)
            ->whereIn('event_type', ['auth', 'security', 'access'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('security.dashboard', compact('metrics', 'recentEvents'));
    }

    /**
     * Display user devices.
     */
    public function devices()
    {
        $user = Auth::user();

        $devices = UserDevice::where('user_id', $user->id)
            ->orderBy('last_seen_at', 'desc')
            ->get();

        return view('security.devices', compact('devices'));
    }

    /**
     * Display security notifications.
     */
    public function notifications()
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->whereIn('type', [
                'App\Notifications\NewDeviceLogin',
                'App\Notifications\SecurityAlert',
                'App\Notifications\SuspiciousActivity',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('security.notifications', compact('notifications'));
    }

    /**
     * Display security events.
     */
    public function events()
    {
        $user = Auth::user();

        $events = AuditLog::where('user_id', $user->id)
            ->whereIn('event_type', ['auth', 'security', 'access', 'data'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('security.events', compact('events'));
    }

    /**
     * Display two-factor authentication settings.
     */
    public function twoFactor()
    {
        $user = Auth::user();

        return view('security.two-factor', compact('user'));
    }

    /**
     * Trust a device.
     */
    public function trustDevice(UserDevice $device)
    {
        $this->authorize('update', $device);

        $device->update([
            'is_trusted' => true,
            'trusted_at' => Carbon::now(),
        ]);

        return response()->json(['message' => 'Device trusted successfully']);
    }

    /**
     * Remove a device.
     */
    public function removeDevice(UserDevice $device)
    {
        $this->authorize('delete', $device);

        $device->delete();

        return response()->json(['message' => 'Device removed successfully']);
    }

    /**
     * Mark notification as read.
     */
    public function markNotificationRead(Request $request)
    {
        $user = Auth::user();

        if ($request->has('notification_id')) {
            $user->notifications()
                ->where('id', $request->notification_id)
                ->update(['read_at' => Carbon::now()]);
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return response()->json(['message' => 'Notification marked as read']);
    }
}
