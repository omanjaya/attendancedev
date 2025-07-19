<?php

namespace Tests\Unit\Services;

use App\Models\Employee;
use App\Repositories\AttendanceRepository;
use App\Repositories\EmployeeRepository;
use App\Services\FaceRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class FaceRecognitionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $faceRecognitionService;

    protected $employeeRepository;

    protected $attendanceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employeeRepository = Mockery::mock(EmployeeRepository::class);
        $this->attendanceRepository = Mockery::mock(AttendanceRepository::class);

        $this->faceRecognitionService = new FaceRecognitionService(
            $this->employeeRepository,
            $this->attendanceRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_validates_face_descriptor_size()
    {
        $employee = Employee::factory()->make(['id' => 'test-id']);

        $this->employeeRepository
            ->shouldReceive('findOrFail')
            ->with('test-id')
            ->andReturn($employee);

        $invalidFaceData = [
            'descriptor' => array_fill(0, 100, 0.5), // Wrong size (should be 128)
            'confidence' => 0.8,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid descriptor format');

        $this->faceRecognitionService->registerFace('test-id', $invalidFaceData);
    }

    /** @test */
    public function it_validates_minimum_confidence_score()
    {
        $employee = Employee::factory()->make(['id' => 'test-id']);

        $this->employeeRepository
            ->shouldReceive('findOrFail')
            ->with('test-id')
            ->andReturn($employee);

        $lowConfidenceData = [
            'descriptor' => array_fill(0, 128, 0.5),
            'confidence' => 0.6, // Below minimum threshold of 0.7
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Confidence score too low');

        $this->faceRecognitionService->registerFace('test-id', $lowConfidenceData);
    }

    /** @test */
    public function it_calculates_cosine_similarity_correctly()
    {
        $descriptor1 = [1, 0, 0];
        $descriptor2 = [0, 1, 0];
        $descriptor3 = [1, 0, 0]; // Same as descriptor1

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->faceRecognitionService);
        $method = $reflection->getMethod('calculateSimilarity');
        $method->setAccessible(true);

        // Different vectors should have low similarity
        $similarity1 = $method->invoke($this->faceRecognitionService, $descriptor1, $descriptor2);
        $this->assertEquals(0, $similarity1);

        // Same vectors should have high similarity
        $similarity2 = $method->invoke($this->faceRecognitionService, $descriptor1, $descriptor3);
        $this->assertEquals(1, $similarity2);
    }

    /** @test */
    public function it_calculates_quality_score_properly()
    {
        $reflection = new \ReflectionClass($this->faceRecognitionService);
        $method = $reflection->getMethod('calculateQualityScore');
        $method->setAccessible(true);

        $highQualityData = [
            'confidence' => 0.9,
            'face_bounds' => ['width' => 200, 'height' => 200],
            'pose' => ['yaw' => 5, 'pitch' => 3],
            'lighting_score' => 0.9,
            'blur_score' => 0.1,
        ];

        $lowQualityData = [
            'confidence' => 0.7,
            'face_bounds' => ['width' => 50, 'height' => 50],
            'pose' => ['yaw' => 45, 'pitch' => 30],
            'lighting_score' => 0.3,
            'blur_score' => 0.8,
        ];

        $highScore = $method->invoke($this->faceRecognitionService, $highQualityData);
        $lowScore = $method->invoke($this->faceRecognitionService, $lowQualityData);

        $this->assertGreaterThan($lowScore, $highScore);
        $this->assertLessThanOrEqual(1.0, $highScore);
        $this->assertGreaterThanOrEqual(0.0, $lowScore);
    }

    /** @test */
    public function it_performs_liveness_detection()
    {
        $reflection = new \ReflectionClass($this->faceRecognitionService);
        $method = $reflection->getMethod('checkLiveness');
        $method->setAccessible(true);

        $faceDataWithLiveness = [
            'blink_detected' => true,
            'head_movement' => 0.2,
            'expressions' => ['happy' => 0.6, 'neutral' => 0.4],
            'texture_score' => 0.9,
        ];

        $faceDataWithoutLiveness = [
            'blink_detected' => false,
            'head_movement' => 0.05,
            'expressions' => ['neutral' => 1.0],
            'texture_score' => 0.5,
        ];

        $livenessResult1 = $method->invoke($this->faceRecognitionService, $faceDataWithLiveness);
        $livenessResult2 = $method->invoke($this->faceRecognitionService, $faceDataWithoutLiveness);

        $this->assertTrue($livenessResult1['is_live']);
        $this->assertGreaterThan(0.8, $livenessResult1['score']);

        $this->assertFalse($livenessResult2['is_live']);
        $this->assertLessThan(0.8, $livenessResult2['score']);
    }

    /** @test */
    public function it_finds_best_match_among_registered_faces()
    {
        $reflection = new \ReflectionClass($this->faceRecognitionService);
        $method = $reflection->getMethod('findBestMatch');
        $method->setAccessible(true);

        $inputDescriptor = array_fill(0, 128, 0.5);

        $registeredFaces = [
            [
                'employee_id' => 'emp1',
                'descriptor' => array_fill(0, 128, 0.3), // Different
            ],
            [
                'employee_id' => 'emp2',
                'descriptor' => array_fill(0, 128, 0.5), // Same as input
            ],
            [
                'employee_id' => 'emp3',
                'descriptor' => array_fill(0, 128, 0.7), // Different
            ],
        ];

        $match = $method->invoke($this->faceRecognitionService, $inputDescriptor, $registeredFaces);

        $this->assertNotNull($match);
        $this->assertEquals('emp2', $match['employee_id']);
        $this->assertEquals(1.0, $match['similarity']); // Perfect match
    }

    /** @test */
    public function it_verifies_employee_constraints()
    {
        $reflection = new \ReflectionClass($this->faceRecognitionService);
        $method = $reflection->getMethod('verifyConstraints');
        $method->setAccessible(true);

        $activeEmployee = Employee::factory()->make(['is_active' => true]);
        $inactiveEmployee = Employee::factory()->make(['is_active' => false]);

        $result1 = $method->invoke($this->faceRecognitionService, $activeEmployee, []);
        $result2 = $method->invoke($this->faceRecognitionService, $inactiveEmployee, []);

        $this->assertTrue($result1['valid']);
        $this->assertFalse($result2['valid']);
        $this->assertEquals('Employee is not active', $result2['message']);
    }

    /** @test */
    public function it_extracts_additional_features()
    {
        $reflection = new \ReflectionClass($this->faceRecognitionService);
        $method = $reflection->getMethod('extractAdditionalFeatures');
        $method->setAccessible(true);

        $faceData = [
            'landmarks' => array_fill(0, 68, ['x' => 100, 'y' => 100]),
            'expressions' => ['happy' => 0.8, 'sad' => 0.2],
            'pose' => ['yaw' => 5, 'pitch' => -2],
            'age' => 25,
            'gender' => 'male',
            'emotions' => ['joy' => 0.8],
        ];

        $features = $method->invoke($this->faceRecognitionService, $faceData);

        $this->assertEquals(68, $features['landmarks_count']);
        $this->assertTrue($features['has_expressions']);
        $this->assertEquals(['yaw' => 5, 'pitch' => -2], $features['pose_data']);
        $this->assertEquals(25, $features['age_estimation']);
        $this->assertEquals('male', $features['gender_estimation']);
    }

    /** @test */
    public function it_handles_face_cache_operations()
    {
        Cache::shouldReceive('remember')
            ->with('registered_faces', 3600, Mockery::any())
            ->once()
            ->andReturn([]);

        Cache::shouldReceive('forget')
            ->with('registered_faces')
            ->once();

        Cache::shouldReceive('forget')
            ->with('face_recognition_statistics')
            ->once();

        $reflection = new \ReflectionClass($this->faceRecognitionService);

        $getMethod = $reflection->getMethod('getRegisteredFaces');
        $getMethod->setAccessible(true);
        $getMethod->invoke($this->faceRecognitionService);

        $clearMethod = $reflection->getMethod('clearFaceCache');
        $clearMethod->setAccessible(true);
        $clearMethod->invoke($this->faceRecognitionService);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $reflection = new \ReflectionClass($this->faceRecognitionService);
        $method = $reflection->getMethod('validateFaceData');
        $method->setAccessible(true);

        $validData = [
            'descriptor' => array_fill(0, 128, 0.5),
            'confidence' => 0.8,
        ];

        $invalidData1 = [
            'confidence' => 0.8,
            // Missing descriptor
        ];

        $invalidData2 = [
            'descriptor' => array_fill(0, 128, 0.5),
            // Missing confidence
        ];

        // Valid data should not throw exception
        $method->invoke($this->faceRecognitionService, $validData);

        // Invalid data should throw exceptions
        $this->expectException(\InvalidArgumentException::class);
        $method->invoke($this->faceRecognitionService, $invalidData1);

        $this->expectException(\InvalidArgumentException::class);
        $method->invoke($this->faceRecognitionService, $invalidData2);
    }
}
