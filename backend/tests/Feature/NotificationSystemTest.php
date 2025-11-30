<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDevice;
use App\Notifications\TestNotification;
use App\Services\DeviceService;
use App\Services\SecurityNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected $deviceService;

    protected $securityNotificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->deviceService = app(DeviceService::class);
        $this->securityNotificationService = app(SecurityNotificationService::class);
    }

    /** @test */
    public function test_notification_stream_endpoint_requires_authentication()
    {
        $response = $this->get('/api/notifications/stream');
        $response->assertStatus(302); // Redirect to login
    }

    /** @test */
    public function test_authenticated_user_can_access_notification_stream()
    {
        $response = $this->actingAs($this->user)->get('/api/notifications/stream');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/event-stream');
    }

    /** @test */
    public function test_notification_status_endpoint_returns_correct_data()
    {
        // Create some test notifications
        $this->user->notify(new TestNotification(['message' => 'Test notification 1']));
        $this->user->notify(new TestNotification(['message' => 'Test notification 2']));

        $response = $this->actingAs($this->user)->get('/api/notifications/status');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'unread_count',
            'recent_notifications' => [
                '*' => ['id', 'type', 'data', 'read_at', 'created_at'],
            ],
        ]);

        $data = $response->json();
        $this->assertEquals(2, $data['unread_count']);
        $this->assertCount(2, $data['recent_notifications']);
    }

    /** @test */
    public function test_send_test_notification_endpoint()
    {
        Notification::fake();

        $response = $this->actingAs($this->user)->post('/api/notifications/test');

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'timestamp']);

        Notification::assertSentTo($this->user, TestNotification::class);
    }

    /** @test */
    public function test_device_api_endpoints()
    {
        // Create a test device
        $device = UserDevice::factory()->create(['user_id' => $this->user->id]);

        // Test device listing
        $response = $this->actingAs($this->user)->get('/api/devices');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'devices' => [
                '*' => [
                    'id',
                    'display_name',
                    'device_type',
                    'browser_name',
                    'os_name',
                    'is_trusted',
                    'is_current',
                    'last_seen_at',
                    'last_ip_address',
                ],
            ],
        ]);

        // Test device name update
        $response = $this->actingAs($this->user)->patch("/api/devices/{$device->id}/name", [
            'name' => 'Updated Device Name',
        ]);
        $response->assertStatus(200);

        $device->refresh();
        $this->assertEquals('Updated Device Name', $device->display_name);
    }

    /** @test */
    public function test_notification_preferences_endpoints()
    {
        $response = $this->actingAs($this->user)->get('/api/notification-preferences');
        $response->assertStatus(200);

        $response = $this->actingAs($this->user)->put('/api/notification-preferences', [
            'email_enabled' => true,
            'browser_enabled' => true,
            'security_notifications' => true,
            'device_notifications' => true,
            'login_notifications' => false,
        ]);
        $response->assertStatus(200);
    }

    /** @test */
    public function test_mark_notification_as_read()
    {
        $this->user->notify(new TestNotification(['message' => 'Test notification']));
        $notification = $this->user->notifications()->first();

        $response = $this->actingAs($this->user)->post('/api/notification-preferences/mark-read', [
            'notification_id' => $notification->id,
        ]);

        $response->assertStatus(200);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    /** @test */
    public function test_mark_all_notifications_as_read()
    {
        // Create multiple notifications
        $this->user->notify(new TestNotification(['message' => 'Test 1']));
        $this->user->notify(new TestNotification(['message' => 'Test 2']));
        $this->user->notify(new TestNotification(['message' => 'Test 3']));

        $response = $this->actingAs($this->user)->post('/api/notification-preferences/mark-read', [
            'mark_all' => true,
        ]);

        $response->assertStatus(200);

        $unreadCount = $this->user->unreadNotifications()->count();
        $this->assertEquals(0, $unreadCount);
    }

    /** @test */
    public function test_device_fingerprinting()
    {
        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.5',
                'REMOTE_ADDR' => '192.168.1.1',
            ],
        );

        $fingerprint = $this->deviceService->generateFingerprint($request);

        $this->assertIsString($fingerprint);
        $this->assertEquals(64, strlen($fingerprint)); // SHA256 hash length
    }

    /** @test */
    public function test_device_creation_and_tracking()
    {
        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
                'REMOTE_ADDR' => '192.168.1.100',
            ],
        );

        $device = $this->deviceService->getOrCreateDevice($this->user, $request);

        $this->assertInstanceOf(UserDevice::class, $device);
        $this->assertEquals($this->user->id, $device->user_id);
        $this->assertNotNull($device->device_fingerprint);
        $this->assertEquals('mobile', $device->device_type);
    }

    /** @test */
    public function test_security_notification_service()
    {
        Notification::fake();

        $device = UserDevice::factory()->create(['user_id' => $this->user->id]);
        $request = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '192.168.1.1']);

        $this->securityNotificationService->notifyNewDeviceLogin($this->user, $device, $request);

        Notification::assertSentTo($this->user, function ($notification) {
            return $notification instanceof \App\Notifications\SecurityNotification &&
              $notification->getEventType() === 'new_device_login';
        });
    }

    /** @test */
    public function test_quiet_hours_respect()
    {
        // Set quiet hours to current time
        $currentHour = now()->hour;
        $preferences = $this->user->notificationPreferences()->create([
            'quiet_hours_enabled' => true,
            'quiet_hours_start' => sprintf('%02d:00', $currentHour),
            'quiet_hours_end' => sprintf('%02d:59', $currentHour),
            'timezone' => 'UTC',
        ]);

        $shouldSend = $this->securityNotificationService->shouldSendNotification($this->user, 'low');

        $this->assertFalse($shouldSend);
    }

    /** @test */
    public function test_high_priority_notifications_bypass_quiet_hours()
    {
        // Set quiet hours to current time
        $currentHour = now()->hour;
        $preferences = $this->user->notificationPreferences()->create([
            'quiet_hours_enabled' => true,
            'quiet_hours_start' => sprintf('%02d:00', $currentHour),
            'quiet_hours_end' => sprintf('%02d:59', $currentHour),
            'timezone' => 'UTC',
        ]);

        $shouldSend = $this->securityNotificationService->shouldSendNotification($this->user, 'high');

        $this->assertTrue($shouldSend);
    }

    /** @test */
    public function test_navigation_includes_security_section()
    {
        $this->user->givePermissionTo('view_security_dashboard');

        $navigationService = app(\App\Services\NavigationService::class);
        $navigation = $navigationService->getMainNavigation(user: $this->user);

        $securitySection = collect($navigation)->firstWhere('name', 'Security');

        $this->assertNotNull($securitySection);
        $this->assertArrayHasKey('children', $securitySection);

        $deviceManagement = collect($securitySection['children'])->firstWhere(
            'name',
            'Device Management',
        );
        $this->assertNotNull($deviceManagement);
    }

    /** @test */
    public function test_real_time_notification_integration()
    {
        // Test that the stream endpoint starts correctly
        $response = $this->actingAs($this->user)->get('/api/notifications/stream');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/event-stream');
        $response->assertHeader('Cache-Control', 'no-cache');
        $response->assertHeader('Connection', 'keep-alive');
    }
}
