<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Services\DeepFaceLoadBalancer;
use App\Services\FaceRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

/**
 * Face Recognition Feature Tests
 * 
 * Tests face recognition functionality using DeepFace (ArcFace 512-d) 
 * as the primary recognition engine implementing server-side processing.
 */
class FaceRecognitionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $faceRecognitionService;
    protected $employee;
    protected $user;
    protected $deepFaceLoadBalancerMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test employee with user
        $this->employee = Employee::factory()->create([
            'metadata' => [],
        ]);

        $this->user = User::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        Storage::fake('private');
        
        // Get service from container (with mocked DeepFace in tests)
        $this->faceRecognitionService = app(FaceRecognitionService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_register_face_with_512d_deepface_descriptor()
    {
        // 512-d descriptor from DeepFace ArcFace
        $faceData = [
            'descriptor' => array_fill(0, 512, 0.5),
            'confidence' => 0.95,
            'algorithm' => 'deepface-arcface',
            'model_version' => 'arcface-1.0',
        ];

        $result = $this->faceRecognitionService->registerFace(
            $this->employee,
            $faceData['descriptor'],
            null,
            $faceData
        );

        $this->assertTrue($result['success']);
        $this->assertEquals($this->employee->id, $result['employee_id']);
        $this->assertEquals(0.95, $result['confidence']);

        // Check database
        $this->employee->refresh();
        $this->assertNotNull($this->employee->metadata['face_recognition']);
        $this->assertEquals(512, count($this->employee->metadata['face_recognition']['descriptor']));
    }

    /** @test */
    public function it_can_register_face_with_128d_descriptor()
    {
        // 128-d descriptor (legacy support)
        $faceData = [
            'descriptor' => array_fill(0, 128, 0.5),
            'confidence' => 0.85,
            'algorithm' => 'deepface-facenet',
            'model_version' => '1.0',
        ];

        $result = $this->faceRecognitionService->registerFace(
            $this->employee,
            $faceData['descriptor'],
            null,
            $faceData
        );

        $this->assertTrue($result['success']);
        $this->assertEquals($this->employee->id, $result['employee_id']);
        
        // Check database
        $this->employee->refresh();
        $this->assertEquals(128, count($this->employee->metadata['face_recognition']['descriptor']));
    }

    /** @test */
    public function it_can_register_face_with_image()
    {
        $image = UploadedFile::fake()->image('face.jpg', 640, 480);

        $faceData = [
            'descriptor' => array_fill(0, 512, 0.5),
            'confidence' => 0.92,
            'algorithm' => 'deepface-arcface',
        ];

        $result = $this->faceRecognitionService->registerFace(
            $this->employee,
            $faceData['descriptor'],
            $image,
            $faceData
        );

        $this->assertTrue($result['success']);
        $this->assertTrue($result['image_stored']);

        // Check if image was stored
        $this->employee->refresh();
        $imagePath = $this->employee->metadata['face_recognition']['image_path'];
        $this->assertNotNull($imagePath);
        $this->assertTrue(Storage::disk('private')->exists($imagePath));
    }

    /** @test */
    public function it_validates_face_descriptor_size()
    {
        $invalidFaceData = [
            'descriptor' => array_fill(0, 100, 0.5), // Wrong size - not 128 or 512
            'confidence' => 0.9,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid face descriptor format');

        $this->faceRecognitionService->registerFace(
            $this->employee,
            $invalidFaceData['descriptor'],
            null,
            $invalidFaceData
        );
    }

    /** @test */
    public function it_prevents_duplicate_face_registration()
    {
        // Register face first time
        $faceData = [
            'descriptor' => array_fill(0, 512, 0.5),
            'confidence' => 0.9,
        ];

        $this->faceRecognitionService->registerFace(
            $this->employee,
            $faceData['descriptor'],
            null,
            $faceData
        );

        // Try to register again
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Employee already has a registered face');

        $this->faceRecognitionService->registerFace(
            $this->employee,
            $faceData['descriptor'],
            null,
            $faceData
        );
    }

    /** @test */
    public function it_can_verify_registered_face_with_512d_descriptor()
    {
        // Register face first with 512-d
        $descriptor = array_fill(0, 512, 0.5);
        $faceData = [
            'descriptor' => $descriptor,
            'confidence' => 0.95,
            'algorithm' => 'deepface-arcface',
        ];

        $this->faceRecognitionService->registerFace(
            $this->employee,
            $faceData['descriptor'],
            null,
            $faceData
        );

        // Verify with same descriptor (should match)
        $result = $this->faceRecognitionService->verifyFace($descriptor, null, 0.6);

        $this->assertTrue($result['success']);
        $this->assertEquals($this->employee->id, $result['employee']['id']);
        $this->assertGreaterThan(0.6, $result['confidence']);
    }

    /** @test */
    public function it_rejects_unregistered_face()
    {
        // Verify without any registered faces
        $verifyDescriptor = array_fill(0, 512, 0.9);
        
        $result = $this->faceRecognitionService->verifyFace($verifyDescriptor);

        $this->assertFalse($result['success']);
        $this->assertEquals('Face not recognized', $result['message']);
    }

    /** @test */
    public function it_can_update_face_data()
    {
        // Register face first
        $originalFaceData = [
            'descriptor' => array_fill(0, 512, 0.5),
            'confidence' => 0.9,
        ];

        $this->faceRecognitionService->registerFace(
            $this->employee,
            $originalFaceData['descriptor'],
            null,
            $originalFaceData
        );

        // Update with new data
        $newDescriptor = array_fill(0, 512, 0.7);
        $newFaceData = [
            'descriptor' => $newDescriptor,
            'confidence' => 0.95,
        ];

        $result = $this->faceRecognitionService->updateFaceData(
            $this->employee,
            $newDescriptor,
            $newFaceData
        );

        $this->assertTrue($result);

        // Check database
        $this->employee->refresh();
        $this->assertEquals($newDescriptor, $this->employee->metadata['face_recognition']['descriptor']);
        $this->assertEquals(1, $this->employee->metadata['face_recognition']['update_count']);
    }

    /** @test */
    public function it_can_delete_face_data()
    {
        // Register face first
        $faceData = [
            'descriptor' => array_fill(0, 512, 0.5),
            'confidence' => 0.9,
        ];

        $this->faceRecognitionService->registerFace(
            $this->employee,
            $faceData['descriptor'],
            null,
            $faceData
        );

        // Delete face
        $result = $this->faceRecognitionService->deleteFaceData($this->employee);

        $this->assertTrue($result);

        // Check database
        $this->employee->refresh();
        $this->assertNull($this->employee->metadata['face_recognition'] ?? null);
    }

    /** @test */
    public function it_calculates_cosine_similarity_correctly()
    {
        $descriptor1 = [1, 0, 0];
        $descriptor2 = [0, 1, 0];
        $descriptor3 = [1, 0, 0]; // Same as descriptor1

        // Different vectors should have low similarity
        $similarity1 = $this->faceRecognitionService->calculateSimilarity($descriptor1, $descriptor2);
        $this->assertEquals(0, $similarity1);

        // Same vectors should have high similarity
        $similarity2 = $this->faceRecognitionService->calculateSimilarity($descriptor1, $descriptor3);
        $this->assertEquals(1, $similarity2);
    }

    /** @test */
    public function it_calculates_quality_score()
    {
        $highQualityData = [
            'descriptor' => array_fill(0, 512, 0.5),
            'confidence' => 0.95,
            'face_bounds' => ['width' => 200, 'height' => 200],
            'pose' => ['yaw' => 5, 'pitch' => 3],
            'lighting_score' => 0.9,
            'blur_score' => 0.1,
        ];

        $result = $this->faceRecognitionService->registerFace(
            $this->employee,
            $highQualityData['descriptor'],
            null,
            $highQualityData
        );

        $this->assertGreaterThan(0.5, $result['quality_score']);
    }

    /** @test */
    public function it_generates_comprehensive_statistics()
    {
        // Register some faces
        $employee2 = Employee::factory()->create();

        $faceData1 = ['descriptor' => array_fill(0, 512, 0.5), 'confidence' => 0.9, 'algorithm' => 'deepface-arcface'];
        $faceData2 = ['descriptor' => array_fill(0, 512, 0.7), 'confidence' => 0.95, 'algorithm' => 'deepface-arcface'];

        $this->faceRecognitionService->registerFace($this->employee, $faceData1['descriptor'], null, $faceData1);
        $this->faceRecognitionService->registerFace($employee2, $faceData2['descriptor'], null, $faceData2);

        $stats = $this->faceRecognitionService->getStatistics();

        $this->assertArrayHasKey('total_employees', $stats);
        $this->assertArrayHasKey('registered_faces', $stats);
        $this->assertArrayHasKey('registration_percentage', $stats);
        $this->assertArrayHasKey('recognition_accuracy', $stats);
        $this->assertEquals(2, $stats['registered_faces']);
    }

    /** @test */
    public function it_can_perform_batch_verification()
    {
        // Register multiple faces
        $employee2 = Employee::factory()->create();

        $face1Data = ['descriptor' => array_fill(0, 512, 0.5), 'confidence' => 0.9];
        $face2Data = ['descriptor' => array_fill(0, 512, 0.7), 'confidence' => 0.95];

        $this->faceRecognitionService->registerFace($this->employee, $face1Data['descriptor'], null, $face1Data);
        $this->faceRecognitionService->registerFace($employee2, $face2Data['descriptor'], null, $face2Data);

        // Batch verify - match employee 1
        $result1 = $this->faceRecognitionService->verifyFace(array_fill(0, 512, 0.5));
        $this->assertTrue($result1['success']);

        // Batch verify - match employee 2
        $result2 = $this->faceRecognitionService->verifyFace(array_fill(0, 512, 0.7));
        $this->assertTrue($result2['success']);

        // Batch verify - no match
        $result3 = $this->faceRecognitionService->verifyFace(array_fill(0, 512, 0.1));
        $this->assertFalse($result3['success']);
    }

    /** @test */
    public function it_logs_face_recognition_activities()
    {
        $faceData = [
            'descriptor' => array_fill(0, 512, 0.5),
            'confidence' => 0.9,
        ];

        $this->faceRecognitionService->registerFace(
            $this->employee,
            $faceData['descriptor'],
            null,
            $faceData
        );

        // Check if log was created
        $this->assertDatabaseHas('face_recognition_logs', [
            'action' => 'register',
            'employee_id' => $this->employee->id,
        ]);
    }

    /** @test */
    public function it_supports_one_to_one_verification()
    {
        // Register face for specific employee
        $faceData = [
            'descriptor' => array_fill(0, 512, 0.5),
            'confidence' => 0.92,
        ];

        $this->faceRecognitionService->registerFace(
            $this->employee,
            $faceData['descriptor'],
            null,
            $faceData
        );

        // 1:1 verification - should match target employee
        $result = $this->faceRecognitionService->verifyFace(
            $faceData['descriptor'],
            $this->employee, // Target specific employee
            0.6
        );

        $this->assertTrue($result['success']);
        $this->assertEquals($this->employee->id, $result['employee']['id']);
    }

    /** @test */
    public function it_rejects_inactive_employee_during_verification()
    {
        // Register face
        $faceData = [
            'descriptor' => array_fill(0, 512, 0.5),
            'confidence' => 0.9,
        ];

        $this->faceRecognitionService->registerFace(
            $this->employee,
            $faceData['descriptor'],
            null,
            $faceData
        );

        // Deactivate employee
        $this->employee->update(['is_active' => false]);

        // Verify - should fail because employee is inactive
        $result = $this->faceRecognitionService->verifyFace($faceData['descriptor']);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not active', $result['message']);
    }
}
