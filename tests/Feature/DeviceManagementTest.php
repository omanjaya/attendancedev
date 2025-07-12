<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDevice;
use App\Services\DeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DeviceManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private DeviceService $deviceService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt('test_secret'),
        ]);
        
        $this->deviceService = app(DeviceService::class);
    }

    public function test_device_fingerprint_generation(): void
    {
        $request = $this->createMockRequest();
        $fingerprint = $this->deviceService->generateFingerprint($request);
        
        $this->assertNotEmpty($fingerprint);
        $this->assertEquals(64, strlen($fingerprint)); // SHA256 hash length
    }

    public function test_device_tracking_creates_new_device(): void
    {
        $request = $this->createMockRequest();
        
        $this->assertDatabaseCount('user_devices', 0);
        
        $device = $this->deviceService->trackDevice($this->user, $request);
        
        $this->assertDatabaseCount('user_devices', 1);
        $this->assertEquals($this->user->id, $device->user_id);
        $this->assertFalse($device->is_trusted);
    }

    public function test_device_tracking_updates_existing_device(): void
    {
        $request = $this->createMockRequest();
        
        // Create initial device
        $device1 = $this->deviceService->trackDevice($this->user, $request);
        $initialLoginCount = $device1->login_count;
        
        // Track same device again
        $device2 = $this->deviceService->trackDevice($this->user, $request);
        
        $this->assertEquals($device1->id, $device2->id);
        $this->assertEquals($initialLoginCount + 1, $device2->login_count);
        $this->assertDatabaseCount('user_devices', 1);
    }

    public function test_new_device_detection(): void
    {
        $request = $this->createMockRequest();
        
        $this->assertTrue($this->deviceService->isNewDevice($this->user, $request));
        
        $this->deviceService->trackDevice($this->user, $request);
        
        $this->assertFalse($this->deviceService->isNewDevice($this->user, $request));
    }

    public function test_trusted_device_detection(): void
    {
        $request = $this->createMockRequest();
        
        $this->assertFalse($this->deviceService->isTrustedDevice($this->user, $request));
        
        $device = $this->deviceService->trackDevice($this->user, $request);
        $device->markAsTrusted();
        
        $this->assertTrue($this->deviceService->isTrustedDevice($this->user, $request));
    }

    public function test_device_api_list_requires_auth(): void
    {
        $response = $this->get('/api/devices');
        
        $response->assertStatus(302); // Redirect to login
    }

    public function test_device_api_list_returns_user_devices(): void
    {
        $this->actingAs($this->user);
        
        // Create some devices
        UserDevice::factory()->count(3)->create(['user_id' => $this->user->id]);
        
        $response = $this->get('/api/devices');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'devices' => [
                        '*' => [
                            'id',
                            'display_name',
                            'device_type',
                            'is_trusted',
                            'is_current',
                            'last_seen_at',
                        ]
                    ]
                ]);
    }

    public function test_device_trust_requires_2fa(): void
    {
        $this->actingAs($this->user);
        
        $device = UserDevice::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->post("/api/devices/{$device->id}/trust", [
            'code' => 'invalid'
        ]);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['code']);
    }

    public function test_device_removal_prevents_current_device(): void
    {
        $this->actingAs($this->user);
        
        $request = $this->createMockRequest();
        $device = $this->deviceService->trackDevice($this->user, $request);
        
        $response = $this->delete("/api/devices/{$device->id}");
        
        $response->assertStatus(400)
                ->assertJson(['message' => 'Cannot remove current device']);
    }

    public function test_device_name_update(): void
    {
        $this->actingAs($this->user);
        
        $device = UserDevice::factory()->create(['user_id' => $this->user->id]);
        $newName = 'My Custom Device Name';
        
        $response = $this->patch("/api/devices/{$device->id}/name", [
            'name' => $newName
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('user_devices', [
            'id' => $device->id,
            'device_name' => $newName
        ]);
    }

    public function test_unauthorized_device_access_blocked(): void
    {
        $this->actingAs($this->user);
        
        $otherUser = User::factory()->create();
        $otherDevice = UserDevice::factory()->create(['user_id' => $otherUser->id]);
        
        $response = $this->patch("/api/devices/{$otherDevice->id}/name", [
            'name' => 'Hacked Name'
        ]);
        
        $response->assertStatus(403);
    }

    private function createMockRequest()
    {
        return request()->create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Test Browser)',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
        ]);
    }
}