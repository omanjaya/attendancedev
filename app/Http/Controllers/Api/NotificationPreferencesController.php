<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotificationPreferences;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class NotificationPreferencesController extends Controller
{
    /**
     * Get user's notification preferences.
     */
    public function index(Request $request): JsonResponse
    {
        $preferences = $request->user()->notificationPreferences ?? $this->createDefaultPreferences($request->user());
        
        return response()->json([
            'preferences' => [
                'id' => $preferences->id,
                'security_notifications' => $this->getSecurityPreferences($preferences),
                'system_notifications' => $this->getSystemPreferences($preferences),
                'quiet_hours' => $preferences->quiet_hours,
                'digest_frequency' => $preferences->digest_frequency,
            ],
            'available_types' => [
                'security' => UserNotificationPreferences::getSecurityNotificationTypes(),
                'system' => UserNotificationPreferences::getSystemNotificationTypes(),
            ],
        ]);
    }

    /**
     * Update user's notification preferences.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $preferences = $user->notificationPreferences ?? $this->createDefaultPreferences($user);

        $validated = $request->validate($this->getValidationRules());

        $preferences->update($validated);

        return response()->json([
            'message' => 'Notification preferences updated successfully',
            'preferences' => [
                'security_notifications' => $this->getSecurityPreferences($preferences),
                'system_notifications' => $this->getSystemPreferences($preferences),
                'quiet_hours' => $preferences->quiet_hours,
                'digest_frequency' => $preferences->digest_frequency,
            ],
        ]);
    }

    /**
     * Update quiet hours settings.
     */
    public function updateQuietHours(Request $request): JsonResponse
    {
        $user = $request->user();
        $preferences = $user->notificationPreferences ?? $this->createDefaultPreferences($user);

        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'start' => 'required_if:enabled,true|nullable|date_format:H:i',
            'end' => 'required_if:enabled,true|nullable|date_format:H:i',
            'timezone' => 'required_if:enabled,true|nullable|string|timezone',
        ]);

        $quietHours = $validated['enabled'] ? [
            'start' => $validated['start'],
            'end' => $validated['end'],
            'timezone' => $validated['timezone'] ?? config('app.timezone'),
        ] : null;

        $preferences->update(['quiet_hours' => $quietHours]);

        return response()->json([
            'message' => 'Quiet hours updated successfully',
            'quiet_hours' => $preferences->quiet_hours,
        ]);
    }

    /**
     * Update digest frequency settings.
     */
    public function updateDigestFrequency(Request $request): JsonResponse
    {
        $user = $request->user();
        $preferences = $user->notificationPreferences ?? $this->createDefaultPreferences($user);

        $validated = $request->validate([
            'security' => ['required', Rule::in(['immediate', 'daily', 'weekly'])],
            'system' => ['required', Rule::in(['immediate', 'daily', 'weekly'])],
            'attendance' => ['required', Rule::in(['immediate', 'daily', 'weekly'])],
        ]);

        $preferences->update(['digest_frequency' => $validated]);

        return response()->json([
            'message' => 'Digest frequency updated successfully',
            'digest_frequency' => $preferences->digest_frequency,
        ]);
    }

    /**
     * Test notification sending (send a test notification).
     */
    public function testNotification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['email', 'browser'])],
            'category' => ['required', Rule::in(['security', 'system'])],
        ]);

        $user = $request->user();
        
        // Create a test notification
        $testData = [
            'message' => 'This is a test notification to verify your settings are working correctly.',
            'timestamp' => now(),
            'test' => true,
        ];

        try {
            if ($validated['type'] === 'email') {
                $user->notify(new \App\Notifications\TestEmailNotification($testData));
            } else {
                $user->notify(new \App\Notifications\TestBrowserNotification($testData));
            }

            return response()->json([
                'message' => 'Test notification sent successfully',
                'type' => $validated['type'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send test notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's notification history.
     */
    public function history(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark notifications as read.
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notification_ids' => 'array',
            'notification_ids.*' => 'string|exists:notifications,id',
            'mark_all' => 'boolean',
        ]);

        $user = $request->user();

        if ($validated['mark_all'] ?? false) {
            $user->unreadNotifications()->update(['read_at' => now()]);
            $message = 'All notifications marked as read';
        } else {
            $user->notifications()
                ->whereIn('id', $validated['notification_ids'] ?? [])
                ->update(['read_at' => now()]);
            $message = 'Selected notifications marked as read';
        }

        return response()->json([
            'message' => $message,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Get security notification preferences.
     */
    private function getSecurityPreferences(UserNotificationPreferences $preferences): array
    {
        $securityTypes = UserNotificationPreferences::getSecurityNotificationTypes();
        $result = [];

        foreach ($securityTypes as $type) {
            $result[$type] = [
                'email' => $preferences->wantsEmailNotification($type),
                'browser' => $preferences->wantsBrowserNotification($type),
            ];
        }

        return $result;
    }

    /**
     * Get system notification preferences.
     */
    private function getSystemPreferences(UserNotificationPreferences $preferences): array
    {
        $systemTypes = UserNotificationPreferences::getSystemNotificationTypes();
        $result = [];

        foreach ($systemTypes as $type) {
            $result[$type] = [
                'email' => $preferences->wantsEmailNotification($type),
                'browser' => $preferences->wantsBrowserNotification($type),
            ];
        }

        return $result;
    }

    /**
     * Get validation rules for updating preferences.
     */
    private function getValidationRules(): array
    {
        $rules = [];

        // Security notification rules
        foreach (UserNotificationPreferences::getSecurityNotificationTypes() as $type) {
            $rules["{$type}_email"] = 'boolean';
            $rules["{$type}_browser"] = 'boolean';
        }

        // System notification rules
        foreach (UserNotificationPreferences::getSystemNotificationTypes() as $type) {
            $rules["{$type}_email"] = 'boolean';
            $rules["{$type}_browser"] = 'boolean';
        }

        return $rules;
    }

    /**
     * Create default notification preferences for user.
     */
    private function createDefaultPreferences($user): UserNotificationPreferences
    {
        return UserNotificationPreferences::create(
            array_merge(['user_id' => $user->id], UserNotificationPreferences::getDefaults())
        );
    }
}