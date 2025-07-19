<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationStreamController extends Controller
{
    /**
     * Stream real-time notifications using Server-Sent Events.
     */
    public function stream(Request $request): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        return response()->stream(
            function () use ($user) {
                // Set headers for SSE
                echo 'data: '.
                  json_encode([
                      'type' => 'connected',
                      'message' => 'Real-time notification stream connected',
                      'user_id' => $user->id,
                      'timestamp' => now()->toISOString(),
                  ]).
                  "\n\n";

                if (ob_get_level()) {
                    ob_end_flush();
                }
                flush();

                $lastNotificationId = 0;
                $heartbeatInterval = 30; // seconds
                $lastHeartbeat = time();

                while (true) {
                    // Check for new notifications
                    $newNotifications = $user
                        ->notifications()
                        ->where('id', '>', $lastNotificationId)
                        ->orderBy('created_at', 'desc')
                        ->limit(10)
                        ->get();

                    foreach ($newNotifications as $notification) {
                        $data = [
                            'type' => 'notification',
                            'id' => $notification->id,
                            'notification_type' => $notification->type,
                            'data' => $notification->data,
                            'read_at' => $notification->read_at,
                            'created_at' => $notification->created_at->toISOString(),
                        ];

                        echo 'data: '.json_encode($data)."\n\n";

                        if (ob_get_level()) {
                            ob_end_flush();
                        }
                        flush();

                        $lastNotificationId = max($lastNotificationId, $notification->id);
                    }

                    // Send heartbeat to keep connection alive
                    if (time() - $lastHeartbeat >= $heartbeatInterval) {
                        $heartbeatData = [
                            'type' => 'heartbeat',
                            'timestamp' => now()->toISOString(),
                            'unread_count' => $user->unreadNotifications()->count(),
                        ];

                        echo 'data: '.json_encode($heartbeatData)."\n\n";

                        if (ob_get_level()) {
                            ob_end_flush();
                        }
                        flush();

                        $lastHeartbeat = time();
                    }

                    // Check if client disconnected
                    if (connection_aborted()) {
                        break;
                    }

                    // Sleep for 2 seconds before checking again
                    sleep(2);
                }
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no', // Disable nginx buffering
            ],
        );
    }

    /**
     * Get current notification status.
     */
    public function status(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'recent_notifications' => $user
                ->notifications()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'data' => $notification->data,
                        'read_at' => $notification->read_at,
                        'created_at' => $notification->created_at,
                    ];
                }),
        ]);
    }

    /**
     * Send a test real-time notification.
     */
    public function sendTestNotification(Request $request)
    {
        $user = $request->user();

        $user->notify(
            new \App\Notifications\TestNotification([
                'message' => 'This is a test real-time notification',
                'timestamp' => now(),
                'test' => true,
            ]),
        );

        return response()->json([
            'message' => 'Test notification sent',
            'timestamp' => now(),
        ]);
    }
}
