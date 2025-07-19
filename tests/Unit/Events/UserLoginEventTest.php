<?php

namespace Tests\Unit\Events;

use App\Events\UserLoginEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLoginEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_login_event_can_be_created(): void
    {
        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';
        $userAgent = 'Mozilla/5.0 (Test Browser)';
        $deviceFingerprint = 'device_123';
        $isTwoFactorRequired = true;
        $metadata = ['test' => 'data'];

        $event = new UserLoginEvent(
            user: $user,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            deviceFingerprint: $deviceFingerprint,
            isTwoFactorRequired: $isTwoFactorRequired,
            metadata: $metadata,
        );

        $this->assertEquals($user->id, $event->user->id);
        $this->assertEquals($ipAddress, $event->ipAddress);
        $this->assertEquals($userAgent, $event->userAgent);
        $this->assertEquals($deviceFingerprint, $event->deviceFingerprint);
        $this->assertTrue($event->isTwoFactorRequired);
        $this->assertEquals($metadata, $event->metadata);
    }

    public function test_user_login_event_with_default_values(): void
    {
        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';
        $userAgent = 'Mozilla/5.0 (Test Browser)';

        $event = new UserLoginEvent(user: $user, ipAddress: $ipAddress, userAgent: $userAgent);

        $this->assertEquals($user->id, $event->user->id);
        $this->assertEquals($ipAddress, $event->ipAddress);
        $this->assertEquals($userAgent, $event->userAgent);
        $this->assertNull($event->deviceFingerprint);
        $this->assertFalse($event->isTwoFactorRequired);
        $this->assertEquals([], $event->metadata);
    }

    public function test_user_login_event_get_audit_data(): void
    {
        $user = User::factory()->create();
        $ipAddress = '192.168.1.1';
        $userAgent = 'Mozilla/5.0 (Test Browser)';
        $deviceFingerprint = 'device_123';
        $metadata = ['login_method' => 'password'];

        $event = new UserLoginEvent(
            user: $user,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            deviceFingerprint: $deviceFingerprint,
            isTwoFactorRequired: true,
            metadata: $metadata,
        );

        $auditData = $event->getAuditData();

        $this->assertIsArray($auditData);
        $this->assertEquals('user_login', $auditData['event_type']);
        $this->assertEquals($user->id, $auditData['user_id']);
        $this->assertEquals($ipAddress, $auditData['ip_address']);
        $this->assertEquals($userAgent, $auditData['user_agent']);
        $this->assertEquals($deviceFingerprint, $auditData['device_fingerprint']);
        $this->assertTrue($auditData['two_factor_required']);
        $this->assertArrayHasKey('timestamp', $auditData);

        // Check metadata merge
        $this->assertEquals('password', $auditData['metadata']['login_method']);
        $this->assertEquals($deviceFingerprint, $auditData['metadata']['device_fingerprint']);
    }

    public function test_user_login_event_should_alert(): void
    {
        $user = User::factory()->create();

        // Normal login should not alert
        $normalEvent = new UserLoginEvent(
            user: $user,
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0 (Test Browser)',
        );

        $this->assertFalse($normalEvent->shouldAlert());

        // Login requiring 2FA should alert
        $twoFactorEvent = new UserLoginEvent(
            user: $user,
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0 (Test Browser)',
            isTwoFactorRequired: true,
        );

        $this->assertTrue($twoFactorEvent->shouldAlert());

        // Login from suspicious device should alert
        $suspiciousEvent = new UserLoginEvent(
            user: $user,
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0 (Test Browser)',
            metadata: ['suspicious_device' => true],
        );

        $this->assertTrue($suspiciousEvent->shouldAlert());
    }

    public function test_user_login_event_get_notification_recipients(): void
    {
        $user = User::factory()->create();

        $event = new UserLoginEvent(
            user: $user,
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0 (Test Browser)',
            isTwoFactorRequired: true,
        );

        $recipients = $event->getNotificationRecipients();

        $this->assertIsArray($recipients);
        $this->assertContains('security_team', $recipients);
    }

    public function test_user_login_event_get_severity(): void
    {
        $user = User::factory()->create();

        // Normal login should have low severity
        $normalEvent = new UserLoginEvent(
            user: $user,
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0 (Test Browser)',
        );

        $this->assertEquals('low', $normalEvent->getSeverity());

        // Login requiring 2FA should have medium severity
        $twoFactorEvent = new UserLoginEvent(
            user: $user,
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0 (Test Browser)',
            isTwoFactorRequired: true,
        );

        $this->assertEquals('medium', $twoFactorEvent->getSeverity());

        // Login from new device should have medium severity
        $newDeviceEvent = new UserLoginEvent(
            user: $user,
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0 (Test Browser)',
            metadata: ['new_device' => true],
        );

        $this->assertEquals('medium', $newDeviceEvent->getSeverity());

        // Suspicious login should have high severity
        $suspiciousEvent = new UserLoginEvent(
            user: $user,
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0 (Test Browser)',
            metadata: ['suspicious_activity' => true],
        );

        $this->assertEquals('high', $suspiciousEvent->getSeverity());
    }

    public function test_user_login_event_serialization(): void
    {
        $user = User::factory()->create();
        $event = new UserLoginEvent(
            user: $user,
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0 (Test Browser)',
            deviceFingerprint: 'device_123',
            isTwoFactorRequired: true,
            metadata: ['test' => 'data'],
        );

        // Test that event can be serialized (important for queued listeners)
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertEquals($event->user->id, $unserialized->user->id);
        $this->assertEquals($event->ipAddress, $unserialized->ipAddress);
        $this->assertEquals($event->userAgent, $unserialized->userAgent);
        $this->assertEquals($event->deviceFingerprint, $unserialized->deviceFingerprint);
        $this->assertEquals($event->isTwoFactorRequired, $unserialized->isTwoFactorRequired);
        $this->assertEquals($event->metadata, $unserialized->metadata);
    }
}
