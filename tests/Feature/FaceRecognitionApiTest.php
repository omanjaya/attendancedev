<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class FaceRecognitionApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Employee $employee;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        
        // Create test user with permissions
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        $this->location = Location::factory()->create([
            'name' => 'Test Office',
            'address' => '123 Test Street',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius' => 100,
        ]);
        
        $this->employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'location_id' => $this->location->id,
            'employee_id' => 'EMP001',
            'full_name' => 'Test Employee',
            'is_active' => true,
        ]);
        
        // Assign permissions
        $this->user->givePermissionTo([
            'manage_employees',
            'manage_attendance_own',
            'view_employees',
        ]);
        
        // Authenticate user
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function can_register_face_via_api()
    {
        $descriptor = array_fill(0, 128, 0.5);
        
        $response = $this->postJson('/api/v1/face-recognition/register', [
            'employee_id' => $this->employee->id,
            'descriptor' => $descriptor,
            'confidence' => 0.95,
            'algorithm' => 'face-api.js',
            'model_version' => '1.0',
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Face registered successfully',
            'data' => [
                'success' => true,
                'employee_id' => $this->employee->id,
                'confidence' => 0.95,
            ],
        ]);
    }

    /** @test */
    public function can_verify_face_via_api()
    {
        // First register a face
        $descriptor = array_fill(0, 128, 0.5);
        $this->postJson('/api/v1/face-recognition/register', [
            'employee_id' => $this->employee->id,
            'descriptor' => $descriptor,
            'confidence' => 0.95,
        ]);
        
        // Then verify it
        $response = $this->postJson('/api/v1/face-recognition/verify', [
            'descriptor' => $descriptor,
            'confidence' => 0.95,
            'employee_id' => $this->employee->id,
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'success' => true,
                'employee' => [
                    'id' => $this->employee->id,
                    'name' => $this->employee->full_name,
                ],
            ],
        ]);
    }

    /** @test */
    public function can_check_in_via_api()
    {
        // Register face first
        $descriptor = array_fill(0, 128, 0.5);
        $this->postJson('/api/v1/face-recognition/register', [
            'employee_id' => $this->employee->id,
            'descriptor' => $descriptor,
            'confidence' => 0.95,
        ]);
        
        // Check in
        $response = $this->postJson('/api/v1/attendance-face/check-in', [
            'employee_id' => $this->employee->id,
            'face_descriptor' => $descriptor,
            'face_confidence' => 0.95,
            'location' => [
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'accuracy' => 10,
                'address' => '123 Test Street',
            ],
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Check-in successful',
            'data' => [
                'attendance' => [
                    'employee_id' => $this->employee->id,
                ],
                'face_verification' => [
                    'confidence' => 1.0, // Perfect match
                ],
            ],
        ]);
    }

    /** @test */
    public function can_check_out_via_api()
    {
        // Register face and check in first
        $descriptor = array_fill(0, 128, 0.5);
        $this->postJson('/api/v1/face-recognition/register', [
            'employee_id' => $this->employee->id,
            'descriptor' => $descriptor,
            'confidence' => 0.95,
        ]);
        
        $this->postJson('/api/v1/attendance-face/check-in', [
            'employee_id' => $this->employee->id,
            'face_descriptor' => $descriptor,
            'face_confidence' => 0.95,
            'location' => [
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'accuracy' => 10,
            ],
        ]);
        
        // Check out
        $response = $this->postJson('/api/v1/attendance-face/check-out', [
            'employee_id' => $this->employee->id,
            'face_descriptor' => $descriptor,
            'face_confidence' => 0.95,
            'location' => [
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'accuracy' => 10,
            ],
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Check-out successful',
            'data' => [
                'attendance' => [
                    'employee_id' => $this->employee->id,
                ],
            ],
        ]);
    }

    /** @test */
    public function face_registration_requires_valid_descriptor()
    {
        // Invalid descriptor (wrong length)
        $response = $this->postJson('/api/v1/face-recognition/register', [
            'employee_id' => $this->employee->id,
            'descriptor' => array_fill(0, 64, 0.5), // Wrong length
            'confidence' => 0.95,
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['descriptor']);
    }

    /** @test */
    public function face_verification_fails_with_unregistered_face()
    {
        $descriptor = array_fill(0, 128, 0.5);
        
        $response = $this->postJson('/api/v1/face-recognition/verify', [
            'descriptor' => $descriptor,
            'confidence' => 0.95,
            'employee_id' => $this->employee->id,
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'success' => false,
                'message' => 'Face not recognized',
            ],
        ]);
    }

    /** @test */
    public function can_update_face_data_via_api()
    {
        // Register face first
        $originalDescriptor = array_fill(0, 128, 0.5);
        $this->postJson('/api/v1/face-recognition/register', [
            'employee_id' => $this->employee->id,
            'descriptor' => $originalDescriptor,
            'confidence' => 0.95,
        ]);
        
        // Update face data
        $newDescriptor = array_fill(0, 128, 0.7);
        $response = $this->postJson('/api/v1/face-recognition/update', [
            'employee_id' => $this->employee->id,
            'descriptor' => $newDescriptor,
            'confidence' => 0.98,
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Face data updated successfully',
            'data' => [
                'updated' => true,
            ],
        ]);
    }

    /** @test */
    public function can_delete_face_data_via_api()
    {
        // Register face first
        $descriptor = array_fill(0, 128, 0.5);
        $this->postJson('/api/v1/face-recognition/register', [
            'employee_id' => $this->employee->id,
            'descriptor' => $descriptor,
            'confidence' => 0.95,
        ]);
        
        // Delete face data
        $response = $this->postJson('/api/v1/face-recognition/delete', [
            'employee_id' => $this->employee->id,
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Face data deleted successfully',
            'data' => [
                'deleted' => true,
            ],
        ]);
    }

    /** @test */
    public function can_get_face_data_via_api()
    {
        // Register face first
        $descriptor = array_fill(0, 128, 0.5);
        $this->postJson('/api/v1/face-recognition/register', [
            'employee_id' => $this->employee->id,
            'descriptor' => $descriptor,
            'confidence' => 0.95,
        ]);
        
        // Get face data
        $response = $this->postJson('/api/v1/face-recognition/get-data', [
            'employee_id' => $this->employee->id,
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Face data retrieved successfully',
            'data' => [
                'has_face_data' => true,
                'face_data' => [
                    'confidence' => 0.95,
                    'algorithm' => 'face-api.js',
                ],
            ],
        ]);
    }

    /** @test */
    public function can_batch_verify_faces_via_api()
    {
        // Register multiple faces
        $descriptor1 = array_fill(0, 128, 0.5);
        $descriptor2 = array_fill(0, 128, 0.3);
        
        $employee2 = Employee::factory()->create([
            'user_id' => User::factory()->create()->id,
            'location_id' => $this->location->id,
        ]);
        
        $this->postJson('/api/v1/face-recognition/register', [
            'employee_id' => $this->employee->id,
            'descriptor' => $descriptor1,
            'confidence' => 0.95,
        ]);
        
        $this->postJson('/api/v1/face-recognition/register', [
            'employee_id' => $employee2->id,
            'descriptor' => $descriptor2,
            'confidence' => 0.95,
        ]);
        
        // Batch verify
        $response = $this->postJson('/api/v1/face-recognition/batch-verify', [
            'faces' => [$descriptor1, $descriptor2, array_fill(0, 128, 0.1)],
            'threshold' => 0.6,
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Batch verification completed',
            'data' => [
                'total_faces' => 3,
                'successful_matches' => 2,
            ],
        ]);
    }

    /** @test */
    public function can_check_liveness_via_api()
    {
        $response = $this->postJson('/api/v1/face-recognition/check-liveness', [
            'face_data' => [],
            'blink_detected' => true,
            'head_movement' => 0.5,
            'expressions' => ['happy' => 0.8],
            'texture_score' => 0.9,
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Liveness check completed',
            'data' => [
                'is_live' => true,
                'score' => 0.9,
            ],
        ]);
    }

    /** @test */
    public function can_validate_attendance_via_api()
    {
        // Register face first
        $descriptor = array_fill(0, 128, 0.5);
        $this->postJson('/api/v1/face-recognition/register', [
            'employee_id' => $this->employee->id,
            'descriptor' => $descriptor,
            'confidence' => 0.95,
        ]);
        
        // Validate attendance
        $response = $this->postJson('/api/v1/attendance-face/validate', [
            'employee_id' => $this->employee->id,
            'face_descriptor' => $descriptor,
            'location' => [
                'latitude' => -6.200000,
                'longitude' => 106.816666,
            ],
            'action' => 'check_in',
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Validation completed',
            'data' => [
                'valid' => true,
                'can_proceed' => true,
            ],
        ]);
    }

    /** @test */
    public function can_get_attendance_status_via_api()
    {
        $response = $this->postJson('/api/v1/attendance-face/status', [
            'employee_id' => $this->employee->id,
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Status retrieved successfully',
            'data' => [
                'checked_in' => false,
                'checked_out' => false,
            ],
        ]);
    }

    /** @test */
    public function can_get_face_recognition_statistics_via_api()
    {
        $response = $this->getJson('/api/v1/face-recognition/statistics');
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Statistics retrieved successfully',
            'data' => [
                'total_employees' => 1,
                'registered_faces' => 0,
                'registration_percentage' => 0,
            ],
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_access_endpoints()
    {
        // Remove authentication
        $this->withoutMiddleware();
        
        $response = $this->postJson('/api/v1/face-recognition/register', [
            'employee_id' => $this->employee->id,
            'descriptor' => array_fill(0, 128, 0.5),
            'confidence' => 0.95,
        ]);
        
        $response->assertStatus(401);
    }

    /** @test */
    public function validates_required_fields()
    {
        $response = $this->postJson('/api/v1/face-recognition/register', [
            // Missing required fields
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'employee_id',
            'descriptor',
            'confidence',
        ]);
    }

    /** @test */
    public function prevents_access_to_other_employees_data()
    {
        $otherUser = User::factory()->create();
        $otherEmployee = Employee::factory()->create([
            'user_id' => $otherUser->id,
            'location_id' => Location::factory()->create()->id,
        ]);
        
        $response = $this->postJson('/api/v1/face-recognition/register', [
            'employee_id' => $otherEmployee->id,
            'descriptor' => array_fill(0, 128, 0.5),
            'confidence' => 0.95,
        ]);
        
        // Should fail due to permission check
        $response->assertStatus(403);
    }

    /** @test */
    public function handles_invalid_employee_id()
    {
        $response = $this->postJson('/api/v1/face-recognition/register', [
            'employee_id' => 'invalid-id',
            'descriptor' => array_fill(0, 128, 0.5),
            'confidence' => 0.95,
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['employee_id']);
    }

    /** @test */
    public function handles_low_confidence_face_data()
    {
        $response = $this->postJson('/api/v1/face-recognition/register', [
            'employee_id' => $this->employee->id,
            'descriptor' => array_fill(0, 128, 0.5),
            'confidence' => 0.3, // Low confidence
        ]);
        
        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
        ]);
    }
}