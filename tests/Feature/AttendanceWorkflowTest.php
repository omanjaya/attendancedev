<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Location;
use App\Services\AttendanceService;
use App\Services\FaceRecognitionService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Employee $employee;
    private Location $location;
    private AttendanceService $attendanceService;
    private FaceRecognitionService $faceService;
    private NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        
        // Create test user and employee
        $this->user = User::factory()->create([
            'name' => 'Test Employee',
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
        
        // Initialize services
        $this->attendanceService = app(AttendanceService::class);
        $this->faceService = app(FaceRecognitionService::class);
        $this->notificationService = app(NotificationService::class);
    }

    /** @test */
    public function can_register_employee_face()
    {
        $descriptor = array_fill(0, 128, 0.5); // Mock face descriptor
        $confidence = 0.95;
        
        $result = $this->faceService->registerFace(
            $this->employee,
            $descriptor,
            null,
            [
                'confidence' => $confidence,
                'algorithm' => 'face-api.js',
                'model_version' => '1.0',
            ]
        );
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->employee->id, $result['employee_id']);
        $this->assertEquals($confidence, $result['confidence']);
        
        // Verify face data is stored
        $this->employee->refresh();
        $faceData = $this->employee->metadata['face_recognition'];
        $this->assertNotNull($faceData);
        $this->assertEquals($descriptor, $faceData['descriptor']);
        $this->assertEquals($confidence, $faceData['confidence']);
    }

    /** @test */
    public function can_verify_registered_face()
    {
        // First register a face
        $descriptor = array_fill(0, 128, 0.5);
        $this->faceService->registerFace($this->employee, $descriptor);
        
        // Verify the same face
        $result = $this->faceService->verifyFace($descriptor, $this->employee);
        
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0.6, $result['confidence']);
        $this->assertEquals($this->employee->id, $result['employee']['id']);
    }

    /** @test */
    public function face_verification_fails_with_different_face()
    {
        // Register a face
        $originalDescriptor = array_fill(0, 128, 0.5);
        $this->faceService->registerFace($this->employee, $originalDescriptor);
        
        // Try to verify with a different face
        $differentDescriptor = array_fill(0, 128, 0.1);
        $result = $this->faceService->verifyFace($differentDescriptor, $this->employee);
        
        $this->assertFalse($result['success']);
        $this->assertLessThan(0.6, $result['confidence']);
    }

    /** @test */
    public function can_check_in_with_face_recognition()
    {
        // Register face first
        $descriptor = array_fill(0, 128, 0.5);
        $this->faceService->registerFace($this->employee, $descriptor);
        
        // Mock location data
        $locationData = [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 10,
            'address' => '123 Test Street',
        ];
        
        // Mock face data
        $faceData = [
            'descriptor' => $descriptor,
            'confidence' => 0.95,
        ];
        
        // Perform check-in
        $attendance = $this->attendanceService->checkIn(
            $this->employee,
            $locationData,
            $faceData
        );
        
        $this->assertNotNull($attendance);
        $this->assertEquals($this->employee->id, $attendance->employee_id);
        $this->assertNotNull($attendance->check_in);
        $this->assertEquals($locationData, $attendance->check_in_location);
        $this->assertEquals(0.95, $attendance->check_in_face_confidence);
    }

    /** @test */
    public function check_in_fails_with_invalid_face()
    {
        // Register face first
        $originalDescriptor = array_fill(0, 128, 0.5);
        $this->faceService->registerFace($this->employee, $originalDescriptor);
        
        // Mock location data
        $locationData = [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 10,
        ];
        
        // Mock different face data
        $faceData = [
            'descriptor' => array_fill(0, 128, 0.1),
            'confidence' => 0.3,
        ];
        
        // Expect exception when checking in with wrong face
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Face verification failed');
        
        $this->attendanceService->checkIn(
            $this->employee,
            $locationData,
            $faceData
        );
    }

    /** @test */
    public function can_check_out_with_face_recognition()
    {
        // Register face and check in first
        $descriptor = array_fill(0, 128, 0.5);
        $this->faceService->registerFace($this->employee, $descriptor);
        
        $locationData = [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 10,
        ];
        
        $faceData = [
            'descriptor' => $descriptor,
            'confidence' => 0.95,
        ];
        
        // Check in
        $attendance = $this->attendanceService->checkIn(
            $this->employee,
            $locationData,
            $faceData
        );
        
        // Wait a moment and check out
        Carbon::setTestNow(Carbon::now()->addHours(8));
        
        $attendance = $this->attendanceService->checkOut(
            $this->employee,
            $locationData,
            $faceData
        );
        
        $this->assertNotNull($attendance->check_out);
        $this->assertEquals($locationData, $attendance->check_out_location);
        $this->assertEquals(0.95, $attendance->check_out_face_confidence);
        $this->assertGreaterThan(0, $attendance->working_hours);
    }

    /** @test */
    public function prevents_duplicate_check_in()
    {
        // Register face first
        $descriptor = array_fill(0, 128, 0.5);
        $this->faceService->registerFace($this->employee, $descriptor);
        
        $locationData = [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 10,
        ];
        
        $faceData = [
            'descriptor' => $descriptor,
            'confidence' => 0.95,
        ];
        
        // First check-in should work
        $attendance = $this->attendanceService->checkIn(
            $this->employee,
            $locationData,
            $faceData
        );
        
        $this->assertNotNull($attendance);
        
        // Second check-in should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Already checked in today');
        
        $this->attendanceService->checkIn(
            $this->employee,
            $locationData,
            $faceData
        );
    }

    /** @test */
    public function can_get_attendance_status()
    {
        // Register face and check in
        $descriptor = array_fill(0, 128, 0.5);
        $this->faceService->registerFace($this->employee, $descriptor);
        
        $locationData = [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 10,
        ];
        
        $faceData = [
            'descriptor' => $descriptor,
            'confidence' => 0.95,
        ];
        
        // Initially no attendance
        $status = $this->attendanceService->getAttendanceStatus($this->employee);
        $this->assertFalse($status['checked_in']);
        $this->assertFalse($status['checked_out']);
        
        // After check in
        $this->attendanceService->checkIn($this->employee, $locationData, $faceData);
        $status = $this->attendanceService->getAttendanceStatus($this->employee);
        $this->assertTrue($status['checked_in']);
        $this->assertFalse($status['checked_out']);
        
        // After check out
        Carbon::setTestNow(Carbon::now()->addHours(8));
        $this->attendanceService->checkOut($this->employee, $locationData, $faceData);
        $status = $this->attendanceService->getAttendanceStatus($this->employee);
        $this->assertTrue($status['checked_in']);
        $this->assertTrue($status['checked_out']);
        $this->assertGreaterThan(0, $status['working_hours']);
    }

    /** @test */
    public function can_check_liveness()
    {
        $faceData = [
            'blink_detected' => true,
            'head_movement' => 0.5,
            'expressions' => ['happy' => 0.8, 'neutral' => 0.2],
            'texture_score' => 0.9,
        ];
        
        $result = $this->faceService->checkLiveness($faceData);
        
        $this->assertTrue($result);
        
        // Test with poor liveness indicators
        $poorFaceData = [
            'blink_detected' => false,
            'head_movement' => 0.0,
            'expressions' => ['neutral' => 1.0],
            'texture_score' => 0.3,
        ];
        
        $result = $this->faceService->checkLiveness($poorFaceData);
        $this->assertFalse($result);
    }

    /** @test */
    public function can_batch_verify_faces()
    {
        // Register multiple faces
        $employee1 = Employee::factory()->create(['user_id' => User::factory()->create()->id]);
        $employee2 = Employee::factory()->create(['user_id' => User::factory()->create()->id]);
        
        $descriptor1 = array_fill(0, 128, 0.5);
        $descriptor2 = array_fill(0, 128, 0.3);
        
        $this->faceService->registerFace($employee1, $descriptor1);
        $this->faceService->registerFace($employee2, $descriptor2);
        
        // Batch verify
        $faces = [$descriptor1, $descriptor2, array_fill(0, 128, 0.1)]; // Third is unregistered
        $results = $this->faceService->batchVerify($faces);
        
        $this->assertCount(3, $results);
        $this->assertTrue($results[0]['success']);
        $this->assertTrue($results[1]['success']);
        $this->assertFalse($results[2]['success']);
        
        $this->assertEquals($employee1->id, $results[0]['employee_id']);
        $this->assertEquals($employee2->id, $results[1]['employee_id']);
    }

    /** @test */
    public function can_update_face_data()
    {
        // Register initial face
        $originalDescriptor = array_fill(0, 128, 0.5);
        $this->faceService->registerFace($this->employee, $originalDescriptor);
        
        // Update face data
        $newDescriptor = array_fill(0, 128, 0.7);
        $result = $this->faceService->updateFaceData(
            $this->employee,
            $newDescriptor,
            ['confidence' => 0.98]
        );
        
        $this->assertTrue($result);
        
        // Verify new face data
        $this->employee->refresh();
        $faceData = $this->employee->metadata['face_recognition'];
        $this->assertEquals($newDescriptor, $faceData['descriptor']);
        $this->assertEquals(0.98, $faceData['confidence']);
    }

    /** @test */
    public function can_delete_face_data()
    {
        // Register face
        $descriptor = array_fill(0, 128, 0.5);
        $this->faceService->registerFace($this->employee, $descriptor);
        
        // Verify face is registered
        $this->employee->refresh();
        $this->assertNotNull($this->employee->metadata['face_recognition']);
        
        // Delete face data
        $result = $this->faceService->deleteFaceData($this->employee);
        
        $this->assertTrue($result);
        
        // Verify face data is deleted
        $this->employee->refresh();
        $this->assertNull($this->employee->metadata['face_recognition'] ?? null);
    }

    /** @test */
    public function can_get_face_recognition_statistics()
    {
        // Register some faces
        $employees = Employee::factory()->count(3)->create();
        
        foreach ($employees as $employee) {
            $descriptor = array_fill(0, 128, rand(1, 100) / 100);
            $this->faceService->registerFace($employee, $descriptor);
        }
        
        // Get statistics
        $stats = $this->faceService->getStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_employees', $stats);
        $this->assertArrayHasKey('registered_faces', $stats);
        $this->assertArrayHasKey('registration_percentage', $stats);
        $this->assertArrayHasKey('recognition_accuracy', $stats);
        
        $this->assertEquals(4, $stats['total_employees']); // 3 + 1 from setUp
        $this->assertEquals(3, $stats['registered_faces']); // Only 3 registered
    }

    /** @test */
    public function notifications_are_sent_on_attendance_actions()
    {
        // Register face
        $descriptor = array_fill(0, 128, 0.5);
        $this->faceService->registerFace($this->employee, $descriptor);
        
        $locationData = [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 10,
        ];
        
        $faceData = [
            'descriptor' => $descriptor,
            'confidence' => 0.95,
        ];
        
        // Check notification service is called
        $this->expectsEvents(\Illuminate\Notifications\Events\NotificationSent::class);
        
        // Perform check-in
        $attendance = $this->attendanceService->checkIn(
            $this->employee,
            $locationData,
            $faceData
        );
        
        $this->assertNotNull($attendance);
        
        // Verify notification was sent
        $this->assertTrue($this->user->notifications()->exists());
    }

    /** @test */
    public function can_validate_attendance_before_processing()
    {
        // Register face
        $descriptor = array_fill(0, 128, 0.5);
        $this->faceService->registerFace($this->employee, $descriptor);
        
        // Mock API request
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/attendance-face/validate', [
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
            'data' => [
                'valid' => true,
                'can_proceed' => true,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}