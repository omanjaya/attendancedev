<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Services\DeepFaceLoadBalancer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * DeepFace Service Integration Tests
 * 
 * Tests the DeepFace load balancer and face recognition
 * service integration with Python DeepFace backend.
 */
class DeepFaceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $employee;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = Employee::factory()->create([
            'metadata' => [
                'face_recognition' => [
                    'descriptor' => array_fill(0, 512, 0.5),
                    'confidence' => 0.95,
                    'algorithm' => 'deepface-arcface',
                    'model_version' => 'arcface-1.0',
                    'registered_at' => now()->toISOString(),
                ],
            ],
        ]);

        $this->user = User::factory()->create([
            'employee_id' => $this->employee->id,
        ]);
    }

    /** @test */
    public function it_can_check_deepface_health_via_api()
    {
        Http::fake([
            '*/health*' => Http::response([
                'status' => 'healthy',
                'model' => 'ArcFace',
                'version' => '1.0.0',
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/face/deepface/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'service',
        ]);
    }

    /** @test */
    public function it_returns_cluster_status()
    {
        Http::fake([
            '*/health*' => Http::response(['status' => 'healthy'], 200),
        ]);

        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/face/deepface/status');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
        ]);
    }

    /** @test */
    public function it_can_extract_embedding_from_image()
    {
        Http::fake([
            '*/extract-embedding*' => Http::response([
                'success' => true,
                'embedding' => array_fill(0, 512, 0.5),
                'dimension' => 512,
                'confidence' => 0.95,
                'model' => 'ArcFace',
                'quality' => [
                    'quality_ok' => true,
                    'blur_score' => 0.1,
                    'brightness' => 0.8,
                    'issues' => [],
                ],
            ], 200),
        ]);

        $this->actingAs($this->user);

        $image = UploadedFile::fake()->image('face.jpg', 640, 480);

        $response = $this->postJson('/api/v1/face/deepface/extract-embedding', [
            'image' => $image,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'dimension' => 512,
        ]);
    }

    /** @test */
    public function it_can_verify_face_against_registered_employees()
    {
        Http::fake([
            '*/extract-embedding*' => Http::response([
                'success' => true,
                'embedding' => array_fill(0, 512, 0.5),
                'dimension' => 512,
                'confidence' => 0.95,
            ], 200),
        ]);

        $this->actingAs($this->user);

        $image = UploadedFile::fake()->image('verify.jpg', 640, 480);

        $response = $this->postJson('/api/v1/face/deepface/verify', [
            'image' => $image,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
        ]);
    }

    /** @test */
    public function it_can_check_liveness()
    {
        Http::fake([
            '*/check-liveness*' => Http::response([
                'success' => true,
                'is_live' => true,
                'result' => 'real',
                'confidence' => 0.92,
            ], 200),
        ]);

        $this->actingAs($this->user);

        $image = UploadedFile::fake()->image('liveness.jpg', 640, 480);

        $response = $this->postJson('/api/v1/face/deepface/check-liveness', [
            'image' => $image,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    /** @test */
    public function it_handles_deepface_service_unavailable()
    {
        Http::fake([
            '*' => Http::response(['error' => 'Service unavailable'], 503),
        ]);

        $this->actingAs($this->user);

        $image = UploadedFile::fake()->image('face.jpg', 640, 480);

        $response = $this->postJson('/api/v1/face/deepface/extract-embedding', [
            'image' => $image,
        ]);

        // Should handle error gracefully
        $response->assertStatus(500);
    }

    /** @test */
    public function it_validates_image_input()
    {
        $this->actingAs($this->user);

        // Missing image
        $response = $this->postJson('/api/v1/face/deepface/extract-embedding', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function it_requires_authentication_for_protected_endpoints()
    {
        $image = UploadedFile::fake()->image('face.jpg', 640, 480);

        // Without auth
        $response = $this->postJson('/api/v1/face/deepface/verify', [
            'image' => $image,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function health_endpoint_is_public()
    {
        Http::fake([
            '*' => Http::response(['status' => 'healthy'], 200),
        ]);

        // Without auth - should still work for health check
        $response = $this->getJson('/api/v1/face/deepface/health');

        $response->assertStatus(200);
    }
}
