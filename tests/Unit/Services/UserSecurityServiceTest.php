<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\UserSecurityService;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class UserSecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserSecurityService $userSecurityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userSecurityService = new UserSecurityService();
    }

    public function test_has_two_factor_enabled_returns_true_when_enabled(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'some_secret',
        ]);

        $result = $this->userSecurityService->hasTwoFactorEnabled($user);

        $this->assertTrue($result);
    }

    public function test_has_two_factor_enabled_returns_false_when_disabled(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => null,
        ]);

        $result = $this->userSecurityService->hasTwoFactorEnabled($user);

        $this->assertFalse($result);
    }

    public function test_lock_account_sets_lock_until_time(): void
    {
        $user = User::factory()->create([
            'is_locked' => false,
            'locked_until' => null,
        ]);

        $lockUntil = Carbon::now()->addHours(2);
        $this->userSecurityService->lockAccount($user, $lockUntil, 'Test lock');

        $user->refresh();
        $this->assertTrue($user->is_locked);
        $this->assertEquals($lockUntil->toDateTimeString(), $user->locked_until->toDateTimeString());
        $this->assertEquals('Test lock', $user->lock_reason);
    }

    public function test_lock_account_uses_default_lock_time_when_not_specified(): void
    {
        $user = User::factory()->create([
            'is_locked' => false,
        ]);

        $this->userSecurityService->lockAccount($user);

        $user->refresh();
        $this->assertTrue($user->is_locked);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());
    }

    public function test_unlock_account_removes_lock(): void
    {
        $user = User::factory()->create([
            'is_locked' => true,
            'locked_until' => Carbon::now()->addHours(1),
            'lock_reason' => 'Test lock',
        ]);

        $this->userSecurityService->unlockAccount($user);

        $user->refresh();
        $this->assertFalse($user->is_locked);
        $this->assertNull($user->locked_until);
        $this->assertNull($user->lock_reason);
    }

    public function test_is_account_locked_returns_true_when_locked(): void
    {
        $user = User::factory()->create([
            'is_locked' => true,
            'locked_until' => Carbon::now()->addHours(1),
        ]);

        $result = $this->userSecurityService->isAccountLocked($user);

        $this->assertTrue($result);
    }

    public function test_is_account_locked_returns_false_when_not_locked(): void
    {
        $user = User::factory()->create([
            'is_locked' => false,
        ]);

        $result = $this->userSecurityService->isAccountLocked($user);

        $this->assertFalse($result);
    }

    public function test_is_account_locked_returns_false_when_lock_expired(): void
    {
        $user = User::factory()->create([
            'is_locked' => true,
            'locked_until' => Carbon::now()->subHours(1), // Past time
        ]);

        $result = $this->userSecurityService->isAccountLocked($user);

        $this->assertFalse($result);
    }

    public function test_increment_failed_login_attempts(): void
    {
        $user = User::factory()->create([
            'failed_login_attempts' => 2,
        ]);

        $this->userSecurityService->incrementFailedLoginAttempts($user);

        $user->refresh();
        $this->assertEquals(3, $user->failed_login_attempts);
        $this->assertNotNull($user->last_failed_login_at);
    }

    public function test_reset_failed_login_attempts(): void
    {
        $user = User::factory()->create([
            'failed_login_attempts' => 5,
            'last_failed_login_at' => Carbon::now(),
        ]);

        $this->userSecurityService->resetFailedLoginAttempts($user);

        $user->refresh();
        $this->assertEquals(0, $user->failed_login_attempts);
        $this->assertNull($user->last_failed_login_at);
    }

    public function test_should_lock_account_returns_true_when_max_attempts_reached(): void
    {
        $user = User::factory()->create([
            'failed_login_attempts' => 5, // Assuming max is 5
        ]);

        $result = $this->userSecurityService->shouldLockAccount($user);

        $this->assertTrue($result);
    }

    public function test_should_lock_account_returns_false_when_below_max_attempts(): void
    {
        $user = User::factory()->create([
            'failed_login_attempts' => 3,
        ]);

        $result = $this->userSecurityService->shouldLockAccount($user);

        $this->assertFalse($result);
    }

    public function test_get_security_score_returns_high_score_for_secure_user(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'secret',
            'email_verified_at' => Carbon::now(),
            'failed_login_attempts' => 0,
            'is_locked' => false,
            'password_changed_at' => Carbon::now()->subDays(10),
        ]);

        $score = $this->userSecurityService->getSecurityScore($user);

        $this->assertGreaterThan(80, $score);
    }

    public function test_get_security_score_returns_low_score_for_insecure_user(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => null,
            'email_verified_at' => null,
            'failed_login_attempts' => 3,
            'is_locked' => false,
            'password_changed_at' => Carbon::now()->subDays(200), // Old password
        ]);

        $score = $this->userSecurityService->getSecurityScore($user);

        $this->assertLessThan(50, $score);
    }

    public function test_is_password_expired_returns_true_for_old_password(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => Carbon::now()->subDays(100), // Assuming 90 days is max
        ]);

        $result = $this->userSecurityService->isPasswordExpired($user);

        $this->assertTrue($result);
    }

    public function test_is_password_expired_returns_false_for_recent_password(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => Carbon::now()->subDays(30),
        ]);

        $result = $this->userSecurityService->isPasswordExpired($user);

        $this->assertFalse($result);
    }

    public function test_generate_backup_codes_creates_array_of_codes(): void
    {
        $codes = $this->userSecurityService->generateBackupCodes();

        $this->assertIsArray($codes);
        $this->assertCount(10, $codes); // Default backup codes count
        
        foreach ($codes as $code) {
            $this->assertIsString($code);
            $this->assertGreaterThan(5, strlen($code));
        }
    }

    public function test_verify_backup_code_returns_true_for_valid_code(): void
    {
        $user = User::factory()->create([
            'two_factor_backup_codes' => Hash::make('123456'),
        ]);

        $result = $this->userSecurityService->verifyBackupCode($user, '123456');

        $this->assertTrue($result);
    }

    public function test_verify_backup_code_returns_false_for_invalid_code(): void
    {
        $user = User::factory()->create([
            'two_factor_backup_codes' => Hash::make('123456'),
        ]);

        $result = $this->userSecurityService->verifyBackupCode($user, 'invalid');

        $this->assertFalse($result);
    }

    public function test_log_security_event_stores_event_data(): void
    {
        $user = User::factory()->create();

        $this->userSecurityService->logSecurityEvent($user, 'login_attempt', [
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => 'login_attempt',
        ]);
    }

    public function test_get_recent_security_events_returns_recent_events(): void
    {
        $user = User::factory()->create();

        // Create some security events
        $this->userSecurityService->logSecurityEvent($user, 'login_success', []);
        $this->userSecurityService->logSecurityEvent($user, 'password_change', []);

        $events = $this->userSecurityService->getRecentSecurityEvents($user, 5);

        $this->assertCount(2, $events);
    }

    public function test_is_suspicious_activity_returns_true_for_suspicious_patterns(): void
    {
        $user = User::factory()->create();

        // Mock suspicious activity - multiple failed logins from different IPs
        Cache::put("failed_logins_{$user->id}", [
            ['ip' => '192.168.1.1', 'time' => Carbon::now()->subMinutes(5)],
            ['ip' => '192.168.1.2', 'time' => Carbon::now()->subMinutes(3)],
            ['ip' => '192.168.1.3', 'time' => Carbon::now()->subMinutes(1)],
        ]);

        $result = $this->userSecurityService->isSuspiciousActivity($user, '192.168.1.4');

        $this->assertTrue($result);
    }

    public function test_get_trusted_devices_returns_user_devices(): void
    {
        $user = User::factory()->create([
            'trusted_devices' => [
                [
                    'device_id' => 'device_123',
                    'device_name' => 'iPhone 12',
                    'last_used_at' => Carbon::now()->toISOString(),
                ]
            ]
        ]);

        $devices = $this->userSecurityService->getTrustedDevices($user);

        $this->assertCount(1, $devices);
        $this->assertEquals('device_123', $devices[0]['device_id']);
        $this->assertEquals('iPhone 12', $devices[0]['device_name']);
    }

    public function test_add_trusted_device_adds_device_to_user(): void
    {
        $user = User::factory()->create([
            'trusted_devices' => []
        ]);

        $this->userSecurityService->addTrustedDevice($user, 'device_456', 'Android Phone');

        $user->refresh();
        $devices = $user->trusted_devices;

        $this->assertCount(1, $devices);
        $this->assertEquals('device_456', $devices[0]['device_id']);
        $this->assertEquals('Android Phone', $devices[0]['device_name']);
    }

    public function test_remove_trusted_device_removes_device_from_user(): void
    {
        $user = User::factory()->create([
            'trusted_devices' => [
                [
                    'device_id' => 'device_123',
                    'device_name' => 'iPhone 12',
                    'last_used_at' => Carbon::now()->toISOString(),
                ]
            ]
        ]);

        $this->userSecurityService->removeTrustedDevice($user, 'device_123');

        $user->refresh();
        $this->assertEmpty($user->trusted_devices);
    }

    public function test_is_device_trusted_returns_true_for_trusted_device(): void
    {
        $user = User::factory()->create([
            'trusted_devices' => [
                [
                    'device_id' => 'device_123',
                    'device_name' => 'iPhone 12',
                    'last_used_at' => Carbon::now()->toISOString(),
                ]
            ]
        ]);

        $result = $this->userSecurityService->isDeviceTrusted($user, 'device_123');

        $this->assertTrue($result);
    }

    public function test_is_device_trusted_returns_false_for_untrusted_device(): void
    {
        $user = User::factory()->create([
            'trusted_devices' => []
        ]);

        $result = $this->userSecurityService->isDeviceTrusted($user, 'unknown_device');

        $this->assertFalse($result);
    }
}