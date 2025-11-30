<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    private Employee $employee;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and employee
        $this->user = User::factory()->create();
        $this->employee = Employee::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $this->location = Location::factory()->create();

        $this->actingAs($this->user);
    }

    public function test_can_view_attendance_check_in_page(): void
    {
        $response = $this->get(route('attendance.check-in'));

        $response->assertStatus(200);
        $response->assertViewIs('attendance.check-in');
    }

    public function test_can_check_in_with_valid_data(): void
    {
        $attendanceData = [
            'location' => [
                'latitude' => 40.7128,
                'longitude' => -74.006,
                'accuracy' => 10,
            ],
            'face_detection' => [
                'confidence' => 0.95,
                'embedding' => 'test_embedding_data',
            ],
        ];

        $response = $this->postJson(route('api.attendance.check-in'), $attendanceData);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['attendance_id', 'check_in_time', 'status'],
        ]);

        // Verify attendance record was created
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'status' => 'present',
            'date' => today()->format('Y-m-d'),
        ]);
    }

    public function test_cannot_check_in_twice_same_day(): void
    {
        // Create existing attendance for today
        Attendance::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => today(),
            'check_in_time' => now()->subHours(2),
            'status' => 'present',
        ]);

        $attendanceData = [
            'location' => [
                'latitude' => 40.7128,
                'longitude' => -74.006,
                'accuracy' => 10,
            ],
            'face_detection' => [
                'confidence' => 0.95,
                'embedding' => 'test_embedding_data',
            ],
        ];

        $response = $this->postJson(route('api.attendance.check-in'), $attendanceData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['check_in']);
    }

    public function test_can_check_out_after_check_in(): void
    {
        // Create attendance with check-in
        $attendance = Attendance::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => today(),
            'check_in_time' => now()->subHours(4),
            'status' => 'present',
        ]);

        $checkOutData = [
            'location' => [
                'latitude' => 40.7128,
                'longitude' => -74.006,
                'accuracy' => 10,
            ],
            'face_detection' => [
                'confidence' => 0.93,
                'embedding' => 'test_embedding_data',
            ],
        ];

        $response = $this->postJson(route('api.attendance.check-out'), $checkOutData);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['attendance_id', 'check_out_time', 'working_hours'],
        ]);

        // Verify attendance was updated
        $attendance->refresh();
        $this->assertNotNull($attendance->check_out_time);
        $this->assertGreaterThan(0, $attendance->working_hours);
    }

    public function test_cannot_check_out_without_check_in(): void
    {
        $checkOutData = [
            'location' => [
                'latitude' => 40.7128,
                'longitude' => -74.006,
                'accuracy' => 10,
            ],
            'face_detection' => [
                'confidence' => 0.93,
                'embedding' => 'test_embedding_data',
            ],
        ];

        $response = $this->postJson(route('api.attendance.check-out'), $checkOutData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['check_out']);
    }

    public function test_location_validation_enforced(): void
    {
        $attendanceData = [
            'location' => [
                'latitude' => 90.1, // Invalid latitude
                'longitude' => -200, // Invalid longitude
                'accuracy' => 10,
            ],
            'face_detection' => [
                'confidence' => 0.95,
                'embedding' => 'test_embedding_data',
            ],
        ];

        $response = $this->postJson(route('api.attendance.check-in'), $attendanceData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['location.latitude', 'location.longitude']);
    }

    public function test_face_detection_confidence_validation(): void
    {
        $attendanceData = [
            'location' => [
                'latitude' => 40.7128,
                'longitude' => -74.006,
                'accuracy' => 10,
            ],
            'face_detection' => [
                'confidence' => 0.5, // Too low confidence
                'embedding' => 'test_embedding_data',
            ],
        ];

        $response = $this->postJson(route('api.attendance.check-in'), $attendanceData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['face_detection.confidence']);
    }

    public function test_can_view_attendance_history(): void
    {
        // Create some attendance records
        Attendance::factory()
            ->count(5)
            ->create([
                'employee_id' => $this->employee->id,
            ]);

        $response = $this->get(route('attendance.history'));

        $response->assertStatus(200);
        $response->assertViewIs('attendance.history');
        $response->assertViewHas('attendances');
    }

    public function test_can_export_attendance_csv(): void
    {
        // Create attendance records
        Attendance::factory()
            ->count(3)
            ->create([
                'employee_id' => $this->employee->id,
            ]);

        $response = $this->post(route('attendance.export'), [
            'format' => 'csv',
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
    }

    public function test_working_hours_calculated_correctly(): void
    {
        $checkInTime = now()->setHour(9)->setMinute(0)->setSecond(0);
        $checkOutTime = now()->setHour(17)->setMinute(30)->setSecond(0);

        $attendance = Attendance::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => today(),
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
        ]);

        // Calculate expected working hours (8.5 hours)
        $expectedHours = $checkOutTime->diffInMinutes($checkInTime) / 60;

        $this->assertEquals($expectedHours, $attendance->working_hours);
    }

    public function test_late_arrival_marked_correctly(): void
    {
        // Assume work starts at 9:00 AM, arriving at 9:30 AM
        $lateCheckIn = now()->setHour(9)->setMinute(30);

        $attendance = Attendance::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => today(),
            'check_in_time' => $lateCheckIn,
            'status' => 'late',
        ]);

        $this->assertEquals('late', $attendance->status);
        $this->assertNotNull($attendance->check_in_time);
    }

    public function test_can_get_attendance_status_api(): void
    {
        // Create today's attendance
        Attendance::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => today(),
            'check_in_time' => now()->subHours(2),
            'status' => 'present',
        ]);

        $response = $this->getJson(route('api.attendance.status'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['is_checked_in', 'check_in_time', 'working_hours', 'status'],
        ]);
    }

    public function test_attendance_statistics_endpoint(): void
    {
        // Create varied attendance records
        Attendance::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => today(),
            'status' => 'present',
            'working_hours' => 8,
        ]);

        Attendance::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => today()->subDay(),
            'status' => 'late',
            'working_hours' => 7.5,
        ]);

        $response = $this->getJson(route('api.attendance.statistics'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_days',
                'present_days',
                'late_days',
                'absent_days',
                'average_hours',
                'attendance_rate',
            ],
        ]);
    }

    public function test_attendance_requires_authentication(): void
    {
        auth()->logout();

        $response = $this->get(route('attendance.check-in'));
        $response->assertRedirect(route('login'));

        $response = $this->postJson(route('api.attendance.check-in'), []);
        $response->assertStatus(401);
    }

    public function test_mobile_attendance_interface(): void
    {
        $response = $this->get(route('attendance.mobile'));

        $response->assertStatus(200);
        $response->assertViewIs('attendance.mobile.check-in');
    }
}
