<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\Employee;
use App\Notifications\AttendanceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new NotificationService();
        
        Notification::fake();
        Queue::fake();
    }

    /** @test */
    public function it_can_send_notification_to_user()
    {
        $user = User::factory()->create();
        
        $result = $this->service->send($user, 'attendance.checked_in', [
            'time' => '08:00',
            'location' => 'Main Office',
        ]);

        $this->assertTrue($result);
        
        Notification::assertSentTo(
            $user,
            function ($notification) {
                return $notification instanceof AttendanceNotification &&
                    $notification->data['time'] === '08:00';
            }
        );
    }

    /** @test */
    public function it_can_send_bulk_notifications()
    {
        $users = User::factory()->count(5)->create();
        
        $results = $this->service->sendBulk($users, 'system.announcement', [
            'title' => 'System Maintenance',
            'message' => 'The system will be under maintenance',
        ]);

        $this->assertCount(5, $results);
        $this->assertEquals(5, array_sum(array_column($results, 'success')));
        
        Notification::assertSentToTimes(
            User::class,
            AttendanceNotification::class,
            5
        );
    }

    /** @test */
    public function it_can_send_via_specific_channel()
    {
        $user = User::factory()->create([
            'phone' => '+628123456789',
        ]);
        
        $result = $this->service->sendVia($user, 'sms', 'attendance.late', [
            'time' => '09:30',
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_queue_notifications()
    {
        $user = User::factory()->create();
        $sendAt = Carbon::now()->addHours(2);
        
        $result = $this->service->queue($user, 'reminder.check_out', [
            'message' => 'Don\'t forget to check out',
        ], $sendAt);

        $this->assertTrue($result);
        
        Queue::assertPushed(function ($job) use ($sendAt) {
            return $job->delay->eq($sendAt);
        });
    }

    /** @test */
    public function it_can_get_user_notifications()
    {
        $user = User::factory()->create();
        
        // Create some notifications
        $user->notifications()->create([
            'id' => \Str::uuid(),
            'type' => AttendanceNotification::class,
            'data' => ['message' => 'Test 1'],
            'created_at' => Carbon::now()->subDays(1),
        ]);
        
        $user->notifications()->create([
            'id' => \Str::uuid(),
            'type' => AttendanceNotification::class,
            'data' => ['message' => 'Test 2'],
            'created_at' => Carbon::now(),
        ]);

        $notifications = $this->service->getUserNotifications($user);

        $this->assertCount(2, $notifications);
        $this->assertEquals('Test 2', $notifications->first()->data['message']);
    }

    /** @test */
    public function it_can_filter_notifications_by_type()
    {
        $user = User::factory()->create();
        
        $user->notifications()->create([
            'id' => \Str::uuid(),
            'type' => AttendanceNotification::class,
            'data' => ['type' => 'attendance'],
        ]);
        
        $user->notifications()->create([
            'id' => \Str::uuid(),
            'type' => AttendanceNotification::class,
            'data' => ['type' => 'leave'],
        ]);

        $notifications = $this->service->getUserNotifications($user, ['type' => 'attendance']);

        $this->assertCount(1, $notifications);
        $this->assertEquals('attendance', $notifications->first()->data['type']);
    }

    /** @test */
    public function it_can_mark_notification_as_read()
    {
        $user = User::factory()->create();
        
        $notification = $user->notifications()->create([
            'id' => \Str::uuid(),
            'type' => AttendanceNotification::class,
            'data' => ['message' => 'Test'],
        ]);

        $this->assertNull($notification->read_at);

        $result = $this->service->markAsRead($notification->id);

        $this->assertTrue($result);
        
        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    /** @test */
    public function it_can_mark_all_notifications_as_read()
    {
        $user = User::factory()->create();
        
        // Create unread notifications
        $user->notifications()->createMany([
            [
                'id' => \Str::uuid(),
                'type' => AttendanceNotification::class,
                'data' => ['message' => 'Test 1'],
            ],
            [
                'id' => \Str::uuid(),
                'type' => AttendanceNotification::class,
                'data' => ['message' => 'Test 2'],
            ],
        ]);

        $this->assertEquals(2, $user->unreadNotifications()->count());

        $result = $this->service->markAllAsRead($user);

        $this->assertTrue($result);
        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    /** @test */
    public function it_can_delete_notification()
    {
        $user = User::factory()->create();
        
        $notification = $user->notifications()->create([
            'id' => \Str::uuid(),
            'type' => AttendanceNotification::class,
            'data' => ['message' => 'Test'],
        ]);

        $result = $this->service->delete($notification->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    /** @test */
    public function it_can_get_unread_count()
    {
        $user = User::factory()->create();
        
        // Create mixed read/unread notifications
        $user->notifications()->create([
            'id' => \Str::uuid(),
            'type' => AttendanceNotification::class,
            'data' => ['message' => 'Test 1'],
            'read_at' => Carbon::now(),
        ]);
        
        $user->notifications()->createMany([
            [
                'id' => \Str::uuid(),
                'type' => AttendanceNotification::class,
                'data' => ['message' => 'Test 2'],
            ],
            [
                'id' => \Str::uuid(),
                'type' => AttendanceNotification::class,
                'data' => ['message' => 'Test 3'],
            ],
        ]);

        $count = $this->service->getUnreadCount($user);

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function it_can_get_and_update_preferences()
    {
        $user = User::factory()->create();
        
        // Get default preferences
        $preferences = $this->service->getPreferences($user);
        
        $this->assertIsArray($preferences);
        $this->assertArrayHasKey('email', $preferences);
        $this->assertArrayHasKey('push', $preferences);
        $this->assertArrayHasKey('sms', $preferences);

        // Update preferences
        $newPreferences = [
            'email' => false,
            'push' => true,
            'sms' => false,
        ];

        $result = $this->service->updatePreferences($user, $newPreferences);
        
        $this->assertTrue($result);

        $updated = $this->service->getPreferences($user);
        
        $this->assertFalse($updated['email']);
        $this->assertTrue($updated['push']);
        $this->assertFalse($updated['sms']);
    }

    /** @test */
    public function it_respects_user_preferences_when_sending()
    {
        $user = User::factory()->create();
        
        // Disable email notifications
        $this->service->updatePreferences($user, [
            'email' => false,
            'push' => true,
        ]);

        $result = $this->service->send($user, 'test.notification', []);

        // Should still return true but not send via disabled channels
        $this->assertTrue($result);
        
        Notification::assertNotSentTo($user, function ($notification, $channels) {
            return in_array('mail', $channels);
        });
    }
}