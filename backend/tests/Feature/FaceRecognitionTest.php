<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Repositories\FaceRecognitionRepository;
use App\Services\FaceRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaceRecognitionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $faceRecognitionService;

    protected $faceRecognitionRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faceRecognitionService = app(FaceRecognitionService::class);
        $this->faceRecognitionRepository = app(FaceRecognitionRepository::class);

        // Create test employee with user
        $this->employee = Employee::factory()->create([
            'metadata' => [],
        ]);

        $this->user = User::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        Storage::fake('private');
    }

    /** @test */
    public function it_can_register_face_for_employee()
    {
        $faceData = [
            'descriptor' => array_fill(0, 128, 0.5),
            'confidence' => 0.85,
            'algorithm' => 'face-api.js',
            'model_version' => '1.0',
        ];

        $result = $this->faceRecognitionService->registerFace(
            $this->employee->id,
            $faceData
        );

        $this->assertTrue($result['success']);
        $this->assertEquals($this->employee->id, $result['employee_id']);
        $this->assertEquals(0.85, $result['confidence']);

        // Check database
        $this->employee->refresh();
        $this->assertNotNull($this->employee->metadata['face_recognition']);
        $this->assertEquals($faceData['descriptor'], $this->employee->metadata['face_recognition']['descriptor']);
    }

    /** @test */
    public function it_can_register_face_with_image()
    {
        $image = UploadedFile::fake()->image('face.jpg', 640, 480);

        $faceData = [
            'descriptor' => array_fill(0, 128, 0.5),
            'confidence' => 0.85,
        ];

        $result = $this->faceRecognitionService->registerFace(
            $this->employee->id,
            $faceData,
            $image
        );

        $this->assertTrue($result['success']);
        $this->assertTrue($result['image_stored']);

        // Check if image was stored
        $this->employee->refresh();
        $imagePath = $this->employee->metadata['face_recognition']['image_path'];
        $this->assertNotNull($imagePath);
        Storage::disk('private')->assertExists($imagePath);
    }

    /** @test */
    public function it_validates_face_data_structure()
    {
        $invalidFaceData = [
            'descriptor' => array_fill(0, 100, 0.5), // Wrong size
            'confidence' => 0.5, // Too low
        ];

        $this->expectException(\InvalidArgumentException::class);

        $this->faceRecognitionService->registerFace(
            $this->employee->id,
            $invalidFaceData
        );
    }

    /** @test */
    public function it_prevents_duplicate_face_registration()
    {
        // Register face first time
        $faceData = [
            'descriptor' => array_fill(0, 128, 0.5),
            'confidence' => 0.85,
        ];

        $this->faceRecognitionService->registerFace(
            $this->employee->id,
            $faceData
        );

        // Try to register again
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Employee already has a registered face');

        $this->faceRecognitionService->registerFace(
            $this->employee->id,
            $faceData
        );
    }

    /** @test */
    public function it_can_verify_registered_face()
    {
        // Register face first
        $descriptor = array_fill(0, 128, 0.5);
        $faceData = [
            'descriptor' => $descriptor,
            'confidence' => 0.85,
        ];

        $this->faceRecognitionService->registerFace(
            $this->employee->id,
            $faceData
        );

        // Verify with same descriptor (should match)
        $verifyData = [
            'descriptor' => $descriptor,
            'confidence' => 0.8,
        ];

        $result = $this->faceRecognitionService->verifyFace($verifyData);

        $this->assertTrue($result['success']);
        $this->assertEquals($this->employee->id, $result['employee']['id']);
        $this->assertGreaterThan(0.6, $result['confidence']);
    }

    /** @test */
    public function it_rejects_unregistered_face()
    {
        $verifyData = [
            'descriptor' => array_fill(0, 128, 0.9), // Different descriptor
            'confidence' => 0.8,
        ];

        $result = $this->faceRecognitionService->verifyFace($verifyData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Face not recognized', $result['message']);
    }

    /** @test */
    public function it_can_update_face_data()
    {
        // Register face first
        $originalData = [
            'descriptor' => array_fill(0, 128, 0.5),
            'confidence' => 0.85,
        ];

        $this->faceRecognitionService->registerFace(
            $this->employee->id,
            $originalData
        );

        // Update with new data
        $newData = [
            'descriptor' => array_fill(0, 128, 0.7),
            'confidence' => 0.9,
        ];

        $result = $this->faceRecognitionService->updateFace(
            $this->employee->id,
            $newData
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(0.9, $result['confidence']);

        // Check database
        $this->employee->refresh();
        $this->assertEquals($newData['descriptor'], $this->employee->metadata['face_recognition']['descriptor']);
        $this->assertEquals(1, $this->employee->metadata['face_recognition']['update_count']);
    }

    /** @test */
    public function it_can_delete_face_data()
    {
        // Register face first
        $faceData = [
            'descriptor' => array_fill(0, 128, 0.5),
            'confidence' => 0.85,
        ];

        $this->faceRecognitionService->registerFace(
            $this->employee->id,
            $faceData
        );

        // Delete face
        $result = $this->faceRecognitionService->deleteFace($this->employee->id);

        $this->assertTrue($result);

        // Check database
        $this->employee->refresh();
        $this->assertNull($this->employee->metadata['face_recognition'] ?? null);
    }

    /** @test */
    public function it_performs_liveness_detection()
    {
        $faceData = [
            'descriptor' => array_fill(0, 128, 0.5),
            'confidence' => 0.85,
            'blink_detected' => true,
            'head_movement' => 0.2,
            'expressions' => ['happy' => 0.6, 'neutral' => 0.4],
        ];

        $options = ['require_liveness' => true];

        $result = $this->faceRecognitionService->verifyFace($faceData, $options);

        // Should fail because no registered face exists yet
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function it_can_perform_batch_verification()
    {
        // Register multiple faces
        $employee2 = Employee::factory()->create();

        $face1Data = ['descriptor' => array_fill(0, 128, 0.5), 'confidence' => 0.85];
        $face2Data = ['descriptor' => array_fill(0, 128, 0.7), 'confidence' => 0.9];

        $this->faceRecognitionService->registerFace($this->employee->id, $face1Data);
        $this->faceRecognitionService->registerFace($employee2->id, $face2Data);

        // Batch verify
        $batchData = [
            ['descriptor' => array_fill(0, 128, 0.5), 'confidence' => 0.8], // Should match employee 1
            ['descriptor' => array_fill(0, 128, 0.7), 'confidence' => 0.8], // Should match employee 2
            ['descriptor' => array_fill(0, 128, 0.9), 'confidence' => 0.8], // Should not match
        ];

        $results = $this->faceRecognitionService->batchVerify($batchData);

        $this->assertCount(3, $results);
        $this->assertTrue($results[0]['success']);
        $this->assertTrue($results[1]['success']);
        $this->assertFalse($results[2]['success']);
    }

    /** @test */
    public function it_logs_face_recognition_activities()
    {
        $faceData = [
            'descriptor' => array_fill(0, 128, 0.5),
            'confidence' => 0.85,
        ];

        $this->faceRecognitionService->registerFace(
            $this->employee->id,
            $faceData
        );

        // Check if log was created
        $this->assertDatabaseHas('face_recognition_logs', [
            'action' => 'register',
            'employee_id' => $this->employee->id,
        ]);
    }

    /** @test */
    public function it_calculates_quality_score()
    {
        $highQualityData = [
            'descriptor' => array_fill(0, 128, 0.5),
            'confidence' => 0.95,
            'face_bounds' => ['width' => 200, 'height' => 200],
            'pose' => ['yaw' => 5, 'pitch' => 3],
            'lighting_score' => 0.9,
            'blur_score' => 0.1,
        ];

        $result = $this->faceRecognitionService->registerFace(
            $this->employee->id,
            $highQualityData
        );

        $this->assertGreaterThan(0.7, $result['quality_score']);
    }

    /** @test */
    public function it_generates_comprehensive_statistics()
    {
        // Register some faces
        $employee2 = Employee::factory()->create();

        $faceData1 = ['descriptor' => array_fill(0, 128, 0.5), 'confidence' => 0.85];
        $faceData2 = ['descriptor' => array_fill(0, 128, 0.7), 'confidence' => 0.9];

        $this->faceRecognitionService->registerFace($this->employee->id, $faceData1);
        $this->faceRecognitionService->registerFace($employee2->id, $faceData2);

        $stats = $this->faceRecognitionService->getStatistics();

        $this->assertArrayHasKey('total_employees', $stats);
        $this->assertArrayHasKey('registered_faces', $stats);
        $this->assertArrayHasKey('registration_percentage', $stats);
        $this->assertArrayHasKey('recognition_accuracy', $stats);
        $this->assertEquals(2, $stats['registered_faces']);
    }
}
