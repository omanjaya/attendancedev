<?php

namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FaceDetectionApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        $this->employee = Employee::factory()->create([
            'metadata' => [],
        ]);

        $this->user = User::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        // Give necessary permissions
        $this->user->givePermissionTo([
            'manage_employees',
            'view_employees',
            'manage_own_attendance',
            'view_attendance_reports',
        ]);
    }

    /** @test */
    public function it_can_register_face_via_api()
    {
        Sanctum::actingAs($this->user);

        $faceData = [
            'employee_id' => $this->employee->id,
            'face_data' => [
                'descriptor' => array_fill(0, 128, 0.5),
                'confidence' => 0.85,
                'algorithm' => 'face-api.js',
                'model_version' => '1.0',
            ],
        ];

        $response = $this->postJson('/api/v1/face-detection/register', $faceData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'success' => true,
                    'employee_id' => $this->employee->id,
                    'confidence' => 0.85,
                ],
            ]);

        // Check database
        $this->assertDatabaseHas('face_recognition_logs', [
            'action' => 'register',
            'employee_id' => $this->employee->id,
        ]);
    }

    /** @test */
    public function it_can_register_face_with_image()
    {
        Sanctum::actingAs($this->user);

        $image = UploadedFile::fake()->image('face.jpg', 640, 480);

        $response = $this->post('/api/v1/face-detection/register', [
            'employee_id' => $this->employee->id,
            'face_data' => json_encode([
                'descriptor' => array_fill(0, 128, 0.5),
                'confidence' => 0.85,
            ]),
            'face_image' => $image,
        ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertTrue($data['image_stored']);
    }

    /** @test */
    public function it_validates_face_registration_data()
    {
        Sanctum::actingAs($this->user);

        $invalidData = [
            'employee_id' => $this->employee->id,
            'face_data' => [
                'descriptor' => array_fill(0, 100, 0.5), // Wrong size
                'confidence' => 0.5, // Too low
            ],
        ];

        $response = $this->postJson('/api/v1/face-detection/register', $invalidData);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_verify_face_via_api()
    {
        Sanctum::actingAs($this->user);

        // First register a face
        $descriptor = array_fill(0, 128, 0.5);
        $this->employee->update([
            'metadata' => [
                'face_recognition' => [
                    'descriptor' => $descriptor,
                    'confidence' => 0.85,
                    'registered_at' => now()->toISOString(),
                ],
            ],
        ]);

        // Now verify
        $verifyData = [
            'face_data' => [
                'descriptor' => $descriptor,
                'confidence' => 0.8,
            ],
            'require_liveness' => false,
        ];

        $response = $this->postJson('/api/v1/face-detection/verify', $verifyData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'success' => true,
                    'employee' => [
                        'id' => $this->employee->id,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_can_get_registered_faces()
    {
        Sanctum::actingAs($this->user);

        // Register a face
        $this->employee->update([
            'metadata' => [
                'face_recognition' => [
                    'descriptor' => array_fill(0, 128, 0.5),
                    'confidence' => 0.85,
                    'registered_at' => now()->toISOString(),
                ],
            ],
        ]);

        $response = $this->getJson('/api/v1/face-detection/faces');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'faces' => [
                        '*' => [
                            'employee_id',
                            'name',
                            'employee_code',
                            'descriptor',
                            'confidence',
                        ],
                    ],
                    'count',
                ],
            ]);
    }

    /** @test */
    public function it_can_update_face_data()
    {
        Sanctum::actingAs($this->user);

        // Register a face first
        $this->employee->update([
            'metadata' => [
                'face_recognition' => [
                    'descriptor' => array_fill(0, 128, 0.5),
                    'confidence' => 0.85,
                ],
            ],
        ]);

        $updateData = [
            'face_data' => [
                'descriptor' => array_fill(0, 128, 0.7),
                'confidence' => 0.9,
            ],
        ];

        $response = $this->putJson("/api/v1/face-detection/faces/{$this->employee->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'success' => true,
                    'employee_id' => $this->employee->id,
                    'confidence' => 0.9,
                ],
            ]);
    }

    /** @test */
    public function it_can_delete_face_data()
    {
        Sanctum::actingAs($this->user);

        // Register a face first
        $this->employee->update([
            'metadata' => [
                'face_recognition' => [
                    'descriptor' => array_fill(0, 128, 0.5),
                    'confidence' => 0.85,
                ],
            ],
        ]);

        $response = $this->deleteJson("/api/v1/face-detection/faces/{$this->employee->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'deleted' => true,
                ],
            ]);

        // Check that face data was removed
        $this->employee->refresh();
        $this->assertNull($this->employee->metadata['face_recognition'] ?? null);
    }

    /** @test */
    public function it_can_perform_batch_verification()
    {
        Sanctum::actingAs($this->user);

        // Register multiple faces
        $employee2 = Employee::factory()->create();

        $this->employee->update([
            'metadata' => [
                'face_recognition' => [
                    'descriptor' => array_fill(0, 128, 0.5),
                    'confidence' => 0.85,
                ],
            ],
        ]);

        $employee2->update([
            'metadata' => [
                'face_recognition' => [
                    'descriptor' => array_fill(0, 128, 0.7),
                    'confidence' => 0.9,
                ],
            ],
        ]);

        $batchData = [
            'faces' => [
                [
                    'descriptor' => array_fill(0, 128, 0.5),
                    'confidence' => 0.8,
                ],
                [
                    'descriptor' => array_fill(0, 128, 0.7),
                    'confidence' => 0.8,
                ],
                [
                    'descriptor' => array_fill(0, 128, 0.9),
                    'confidence' => 0.8,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/face-detection/batch-verify', $batchData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'results' => [
                        '*' => [
                            'index',
                            'success',
                            'employee_id',
                            'confidence',
                        ],
                    ],
                    'total_processed',
                    'successful_matches',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(3, $data['total_processed']);
        $this->assertEquals(2, $data['successful_matches']);
    }

    /** @test */
    public function it_can_get_performance_metrics()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/face-detection/performance-metrics?days=30');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_attempts',
                    'successful_attempts',
                    'failed_attempts',
                    'success_rate',
                    'average_confidence',
                    'hourly_distribution',
                ],
            ]);
    }

    /** @test */
    public function it_can_get_employees_without_face()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/face-detection/employees-without-face');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'employees',
                    'count',
                ],
            ]);
    }

    /** @test */
    public function it_can_get_low_quality_faces()
    {
        Sanctum::actingAs($this->user);

        // Create employee with low quality face
        $this->employee->update([
            'metadata' => [
                'face_recognition' => [
                    'descriptor' => array_fill(0, 128, 0.5),
                    'confidence' => 0.85,
                    'quality_score' => 0.6, // Low quality
                ],
            ],
        ]);

        $response = $this->getJson('/api/v1/face-detection/low-quality-faces?threshold=0.7');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'employees',
                    'count',
                    'threshold',
                ],
            ]);
    }

    /** @test */
    public function it_can_search_by_face_status()
    {
        Sanctum::actingAs($this->user);

        $searchData = [
            'status' => 'not_registered',
            'query' => $this->employee->full_name,
        ];

        $response = $this->getJson('/api/v1/face-detection/search-by-status?'.http_build_query($searchData));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'employees',
                    'count',
                    'status',
                ],
            ]);
    }

    /** @test */
    public function it_can_get_face_detection_statistics()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/face-detection/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_employees',
                    'registered_faces',
                    'registration_percentage',
                    'recognition_accuracy',
                    'algorithms_used',
                    'average_confidence',
                    'quality_distribution',
                ],
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/v1/face-detection/faces');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_proper_permissions()
    {
        $userWithoutPermissions = User::factory()->create();
        Sanctum::actingAs($userWithoutPermissions);

        $response = $this->getJson('/api/v1/face-detection/faces');
        $response->assertStatus(403);
    }

    /** @test */
    public function it_handles_invalid_employee_id()
    {
        Sanctum::actingAs($this->user);

        $faceData = [
            'employee_id' => 'non-existent-id',
            'face_data' => [
                'descriptor' => array_fill(0, 128, 0.5),
                'confidence' => 0.85,
            ],
        ];

        $response = $this->postJson('/api/v1/face-detection/register', $faceData);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_handles_large_batch_requests()
    {
        Sanctum::actingAs($this->user);

        $largeBatch = [
            'faces' => array_fill(0, 51, [ // Over limit of 50
                'descriptor' => array_fill(0, 128, 0.5),
                'confidence' => 0.8,
            ]),
        ];

        $response = $this->postJson('/api/v1/face-detection/batch-verify', $largeBatch);

        $response->assertStatus(422);
    }
}
