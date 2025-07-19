<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->employee = Employee::factory()->create(['user_id' => $this->user->id]);
        
        // Assign permissions
        $this->user->givePermissionTo([
            'view_attendance_own',
            'manage_attendance_own',
        ]);
    }

    /** @test */
    public function it_can_get_attendance_status()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/attendance/status');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'checked_in',
                    'checked_out',
                    'check_in_time',
                    'check_out_time',
                    'working_hours',
                    'status',
                    'schedule',
                ],
            ]);
    }

    /** @test */
    public function it_can_process_check_in()
    {
        Sanctum::actingAs($this->user);

        $data = [
            'location' => [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'accuracy' => 10,
            ],
            'face_data' => [
                'descriptor' => array_fill(0, 128, 0.1),
                'confidence' => 0.95,
            ],
        ];

        $response = $this->postJson('/api/v1/attendance/check-in', $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'employee_id',
                    'date',
                    'check_in',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'date' => Carbon::today()->toDateString(),
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_check_in()
    {
        Sanctum::actingAs($this->user);

        // Create existing attendance
        Attendance::create([
            'employee_id' => $this->employee->id,
            'date' => Carbon::today(),
            'check_in' => Carbon::now(),
        ]);

        $data = [
            'location' => [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ],
        ];

        $response = $this->postJson('/api/v1/attendance/check-in', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['check_in']);
    }

    /** @test */
    public function it_can_process_check_out()
    {
        Sanctum::actingAs($this->user);

        // Create check-in first
        Attendance::create([
            'employee_id' => $this->employee->id,
            'date' => Carbon::today(),
            'check_in' => Carbon::now()->subHours(8),
        ]);

        $data = [
            'location' => [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ],
        ];

        $response = $this->postJson('/api/v1/attendance/check-out', $data);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'check_out',
                    'working_hours',
                ],
            ]);
    }

    /** @test */
    public function it_validates_location_when_required()
    {
        Sanctum::actingAs($this->user);

        // Set location for employee
        $location = Location::factory()->create([
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'radius' => 100,
        ]);
        
        $this->employee->update(['location_id' => $location->id]);

        // Invalid location (too far)
        $data = [
            'location' => [
                'latitude' => -6.3000,
                'longitude' => 106.9000,
            ],
        ];

        config(['attendance.require_location_verification' => true]);

        $response = $this->postJson('/api/v1/attendance/check-in', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['location']);
    }

    /** @test */
    public function it_can_get_attendance_data()
    {
        Sanctum::actingAs($this->user);

        // Create some attendance records
        Attendance::factory()->count(5)->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJson('/api/v1/attendance/data');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'date',
                        'check_in',
                        'check_out',
                        'status',
                        'working_hours',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'total',
                ],
            ]);
    }

    /** @test */
    public function it_can_get_attendance_statistics()
    {
        Sanctum::actingAs($this->user);
        $this->user->givePermissionTo('view_attendance_reports');

        // Create attendance records
        Attendance::factory()->count(10)->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJson('/api/v1/attendance/statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_days',
                    'present_days',
                    'absent_days',
                    'late_days',
                    'attendance_rate',
                ],
            ]);
    }

    /** @test */
    public function it_can_export_attendance_data()
    {
        Sanctum::actingAs($this->user);
        $this->user->givePermissionTo('view_attendance_reports');

        Attendance::factory()->count(5)->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJson('/api/v1/attendance/export?format=xlsx');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'download_url',
                ],
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/v1/attendance/status');

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_checks_permissions()
    {
        Sanctum::actingAs($this->user);
        $this->user->revokePermissionTo('view_attendance_own');

        $response = $this->getJson('/api/v1/attendance/status');

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_perform_manual_checkout()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage_attendance_all');
        
        Sanctum::actingAs($admin);

        $attendance = Attendance::create([
            'employee_id' => $this->employee->id,
            'date' => Carbon::today(),
            'check_in' => Carbon::now()->subHours(10),
        ]);

        $response = $this->postJson("/api/v1/attendance/{$attendance->id}/manual-checkout", [
            'time' => Carbon::now()->format('H:i:s'),
            'reason' => 'Employee forgot to check out',
        ]);

        $response->assertOk();
        
        $attendance->refresh();
        $this->assertNotNull($attendance->check_out);
        $this->assertTrue($attendance->manual_entry);
    }
}