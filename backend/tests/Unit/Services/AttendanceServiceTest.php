<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AttendanceService;
use App\Services\FaceRecognitionService;
use App\Services\NotificationService;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Mockery;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceService $service;
    private $faceServiceMock;
    private $notificationServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->faceServiceMock = Mockery::mock(FaceRecognitionService::class);
        $this->notificationServiceMock = Mockery::mock(NotificationService::class);

        $this->service = new AttendanceService(
            $this->faceServiceMock,
            $this->notificationServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_process_check_in()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        
        $locationData = [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'accuracy' => 10,
        ];

        $faceData = [
            'descriptor' => array_fill(0, 128, 0.1),
            'confidence' => 0.95,
        ];

        $this->faceServiceMock
            ->shouldReceive('verifyFace')
            ->once()
            ->andReturn(['success' => true, 'confidence' => 0.95]);

        $this->notificationServiceMock
            ->shouldReceive('send')
            ->once();

        $attendance = $this->service->checkIn($employee, $locationData, $faceData);

        $this->assertInstanceOf(Attendance::class, $attendance);
        $this->assertEquals($employee->id, $attendance->employee_id);
        $this->assertNotNull($attendance->check_in);
        $this->assertEquals($locationData, $attendance->check_in_location);
        $this->assertEquals(0.95, $attendance->check_in_face_confidence);
    }

    /** @test */
    public function it_prevents_duplicate_check_in()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        
        // Create existing attendance
        Attendance::create([
            'employee_id' => $employee->id,
            'date' => Carbon::today(),
            'check_in' => Carbon::now(),
        ]);

        $locationData = ['latitude' => -6.2088, 'longitude' => 106.8456];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Already checked in today');

        $this->service->checkIn($employee, $locationData);
    }

    /** @test */
    public function it_can_process_check_out()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        
        // Create check-in first
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'date' => Carbon::today(),
            'check_in' => Carbon::now()->subHours(8),
        ]);

        $locationData = [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'accuracy' => 10,
        ];

        $this->notificationServiceMock
            ->shouldReceive('send')
            ->once();

        $result = $this->service->checkOut($employee, $locationData);

        $this->assertNotNull($result->check_out);
        $this->assertEquals($locationData, $result->check_out_location);
        $this->assertGreaterThan(0, $result->working_hours);
    }

    /** @test */
    public function it_validates_location_correctly()
    {
        $location = Location::factory()->create([
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'radius' => 100, // 100 meters
        ]);

        $employee = Employee::factory()->create(['location_id' => $location->id]);

        // Within radius
        $validLocation = [
            'latitude' => -6.2089,
            'longitude' => 106.8457,
        ];
        
        $this->assertTrue($this->service->validateLocation($validLocation, $employee));

        // Outside radius
        $invalidLocation = [
            'latitude' => -6.3000,
            'longitude' => 106.9000,
        ];
        
        $this->assertFalse($this->service->validateLocation($invalidLocation, $employee));
    }

    /** @test */
    public function it_calculates_working_hours_correctly()
    {
        $attendance = new Attendance([
            'check_in' => Carbon::parse('08:00'),
            'check_out' => Carbon::parse('17:00'),
        ]);

        $hours = $this->service->calculateWorkingHours($attendance);

        // 9 hours - 1 hour break = 8 hours
        $this->assertEquals(8.0, $hours);
    }

    /** @test */
    public function it_handles_manual_entry()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $approver = User::factory()->create();

        $attendance = $this->service->manualEntry(
            $employee,
            'check_in',
            '2024-01-15 08:00:00',
            'Forgot to check in',
            $approver->id
        );

        $this->assertInstanceOf(Attendance::class, $attendance);
        $this->assertTrue($attendance->manual_entry);
        $this->assertEquals('Forgot to check in', $attendance->manual_entry_reason);
        $this->assertEquals($approver->id, $attendance->manual_entry_by);
    }

    /** @test */
    public function it_gets_attendance_status()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        
        // No attendance today
        $status = $this->service->getAttendanceStatus($employee);
        
        $this->assertFalse($status['checked_in']);
        $this->assertFalse($status['checked_out']);
        $this->assertEquals('absent', $status['status']);

        // Create attendance
        Attendance::create([
            'employee_id' => $employee->id,
            'date' => Carbon::today(),
            'check_in' => Carbon::now(),
            'status' => 'present',
        ]);

        $status = $this->service->getAttendanceStatus($employee);
        
        $this->assertTrue($status['checked_in']);
        $this->assertFalse($status['checked_out']);
        $this->assertEquals('present', $status['status']);
    }

    /** @test */
    public function it_gets_attendance_statistics()
    {
        $employee = Employee::factory()->create();
        
        // Create some attendance records
        Attendance::factory()->count(5)->create([
            'employee_id' => $employee->id,
            'check_in' => Carbon::now(),
            'check_out' => Carbon::now()->addHours(8),
            'working_hours' => 8,
            'status' => 'present',
        ]);

        Attendance::factory()->count(2)->create([
            'employee_id' => $employee->id,
            'check_in' => Carbon::now()->addMinutes(30),
            'status' => 'late',
        ]);

        Attendance::factory()->count(3)->create([
            'employee_id' => $employee->id,
            'check_in' => null,
            'status' => 'absent',
        ]);

        $stats = $this->service->getStatistics(['employee_id' => $employee->id]);

        $this->assertEquals(10, $stats['total_days']);
        $this->assertEquals(7, $stats['present_days']);
        $this->assertEquals(3, $stats['absent_days']);
        $this->assertEquals(2, $stats['late_days']);
        $this->assertEquals(70.0, $stats['attendance_rate']);
    }

    /** @test */
    public function it_handles_photo_upload_during_check_in()
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        
        $photo = UploadedFile::fake()->image('check-in.jpg');
        $locationData = ['latitude' => -6.2088, 'longitude' => 106.8456];

        $this->notificationServiceMock
            ->shouldReceive('send')
            ->once();

        $attendance = $this->service->checkIn($employee, $locationData, null, $photo);

        $this->assertNotNull($attendance->check_in_photo);
        Storage::disk('public')->assertExists($attendance->check_in_photo);
    }

    /** @test */
    public function it_exports_attendance_data()
    {
        $employee = Employee::factory()->create();
        
        Attendance::factory()->count(10)->create([
            'employee_id' => $employee->id,
        ]);

        $exportPath = $this->service->export(['employee_id' => $employee->id]);

        $this->assertStringContainsString('attendance_', $exportPath);
        $this->assertStringContainsString('.xlsx', $exportPath);
    }
}