<?php

namespace App\Contracts\Services;

use App\Models\User;
use Illuminate\Support\Collection;

interface NotificationServiceInterface
{
    /**
     * Send notification to a user
     */
    public function send(User $user, string $type, array $data): bool;

    /**
     * Send notification to multiple users
     */
    public function sendBulk(Collection $users, string $type, array $data): array;

    /**
     * Send notification via specific channel
     */
    public function sendVia(User $user, string $channel, string $type, array $data): bool;

    /**
     * Queue notification for later delivery
     */
    public function queue(User $user, string $type, array $data, ?Carbon $sendAt = null): bool;

    /**
     * Get user notifications
     */
    public function getUserNotifications(User $user, array $filters = []): Collection;

    /**
     * Mark notification as read
     */
    public function markAsRead(string $notificationId): bool;

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(User $user): bool;

    /**
     * Delete notification
     */
    public function delete(string $notificationId): bool;

    /**
     * Get notification preferences
     */
    public function getPreferences(User $user): array;

    /**
     * Update notification preferences
     */
    public function updatePreferences(User $user, array $preferences): bool;

    /**
     * Get unread count
     */
    public function getUnreadCount(User $user): int;
}