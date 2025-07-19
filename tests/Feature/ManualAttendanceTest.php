<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class ManualAttendanceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $adminUser;
    private User $employeeUser;
    private Employee $employee;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        
        // Create employee user
        $this->employeeUser = User::factory()->create([
            'name' => 'Employee User',
            'email' => 'employee@example.com',
        ]);
        
        $this->location = Location::factory()->create([
            'name' => 'Test Office',
            'address' => '123 Test Street',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius' => 100,
        ]);
        
        $this->employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'location_id' => $this->location->id,
            'employee_id' => 'EMP001',
            'full_name' => 'Employee User',
            'is_active' => true,
        ]);
        
        // Assign permissions to admin
        $this->adminUser->givePermissionTo([
            'manage_attendance_all',
            'view_employees',
            'view_attendance_all',
        ]);
        
        // Authenticate as admin
        Sanctum::actingAs($this->adminUser);
    }

    /** @test */
    public function can_create_manual_attendance_entry()
    {
        $response = $this->postJson('/api/v1/manual-attendance', [
            'employee_id' => $this->employee->id,
            'date' => '2025-07-18',
            'check_in_time' => '08:00',
            'check_out_time' => '17:00',
            'reason' => 'System maintenance prevented normal check-in',
            'notes' => 'Employee worked full day but system was down',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Manual attendance entry created successfully',
            'data' => [
                'attendance' => [
                    'employee_id' => $this->employee->id,
                    'working_hours' => 9.0,
                ],
            ],
        ]);

        // Verify attendance was created in database
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'date' => '2025-07-18',
            'is_manual_entry' => true,
            'manual_entry_reason' => 'System maintenance prevented normal check-in',
            'manual_entry_by' => $this->adminUser->id,
        ]);
    }

    /** @test */
    public function can_update_manual_attendance_entry()
    {
        // Create manual attendance entry
        $attendance = Attendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2025-07-18',
            'check_in' => '2025-07-18 08:00:00',
            'check_out' => '2025-07-18 17:00:00',
            'working_hours' => 9.0,
            'status' => 'present',
            'is_manual_entry' => true,
            'manual_entry_reason' => 'Original reason',
            'manual_entry_by' => $this->adminUser->id,
        ]);

        $response = $this->putJson("/api/v1/manual-attendance/{$attendance->id}", [
            'check_in_time' => '08:30',
            'check_out_time' => '17:30',
            'reason' => 'Updated reason',
            'notes' => 'Updated notes',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Manual attendance entry updated successfully',
        ]);

        // Verify attendance was updated
        $attendance->refresh();
        $this->assertEquals('08:30:00', $attendance->check_in->format('H:i:s'));
        $this->assertEquals('17:30:00', $attendance->check_out->format('H:i:s'));
        $this->assertEquals('Updated reason', $attendance->manual_entry_reason);
        $this->assertEquals($this->adminUser->id, $attendance->updated_by);
    }

    /** @test */
    public function can_delete_manual_attendance_entry()
    {
        // Create manual attendance entry
        $attendance = Attendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2025-07-18',
            'check_in' => '2025-07-18 08:00:00',
            'check_out' => '2025-07-18 17:00:00',
            'working_hours' => 9.0,
            'status' => 'present',
            'is_manual_entry' => true,
            'manual_entry_reason' => 'Test reason',
            'manual_entry_by' => $this->adminUser->id,
        ]);

        $response = $this->deleteJson("/api/v1/manual-attendance/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Manual attendance entry deleted successfully',
        ]);

        // Verify attendance was deleted
        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id,
        ]);
    }

    /** @test */
    public function cannot_create_duplicate_attendance_for_same_date()
    {
        // Create existing attendance
        Attendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2025-07-18',
            'check_in' => '2025-07-18 08:00:00',
            'status' => 'present',
            'is_manual_entry' => false,
        ]);

        // Try to create manual attendance for same date
        $response = $this->postJson('/api/v1/manual-attendance', [
            'employee_id' => $this->employee->id,
            'date' => '2025-07-18',
            'check_in_time' => '08:00',
            'check_out_time' => '17:00',
            'reason' => 'Test reason',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Attendance already exists for this date',
        ]);
    }

    /** @test */
    public function cannot_update_non_manual_attendance_entry()
    {
        // Create regular attendance entry
        $attendance = Attendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2025-07-18',
            'check_in' => '2025-07-18 08:00:00',
            'check_out' => '2025-07-18 17:00:00',
            'working_hours' => 9.0,
            'status' => 'present',
            'is_manual_entry' => false,
        ]);

        $response = $this->putJson("/api/v1/manual-attendance/{$attendance->id}", [
            'check_in_time' => '08:30',
            'check_out_time' => '17:30',
            'reason' => 'Test reason',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Can only update manual attendance entries',
        ]);
    }

    /** @test */
    public function can_get_employees_for_manual_entry()
    {
        $response = $this->getJson('/api/v1/manual-attendance/employees');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                [
                    'id' => $this->employee->id,
                    'employee_id' => $this->employee->employee_id,
                    'full_name' => $this->employee->full_name,
                    'location' => $this->location->name,
                    'user_email' => $this->employeeUser->email,
                ],
            ],
        ]);
    }

    /** @test */
    public function can_search_employees_for_manual_entry()
    {
        $response = $this->getJson('/api/v1/manual-attendance/employees?search=EMP001');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                [
                    'id' => $this->employee->id,
                    'employee_id' => 'EMP001',
                ],
            ],
        ]);
    }

    /** @test */
    public function can_get_manual_attendance_entries()
    {
        // Create manual attendance entries
        $attendance1 = Attendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2025-07-18',
            'check_in' => '2025-07-18 08:00:00',
            'check_out' => '2025-07-18 17:00:00',
            'working_hours' => 9.0,
            'status' => 'present',
            'is_manual_entry' => true,
            'manual_entry_reason' => 'Test reason 1',
            'manual_entry_by' => $this->adminUser->id,
        ]);

        $attendance2 = Attendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2025-07-17',
            'check_in' => '2025-07-17 08:00:00',
            'check_out' => '2025-07-17 17:00:00',
            'working_hours' => 9.0,
            'status' => 'present',
            'is_manual_entry' => true,
            'manual_entry_reason' => 'Test reason 2',
            'manual_entry_by' => $this->adminUser->id,
        ]);

        $response = $this->getJson('/api/v1/manual-attendance/entries');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'entries' => [
                    [
                        'id' => $attendance1->id,
                        'employee_id' => $this->employee->id,
                        'date' => '2025-07-18',
                        'is_manual_entry' => true,
                    ],
                    [
                        'id' => $attendance2->id,
                        'employee_id' => $this->employee->id,
                        'date' => '2025-07-17',
                        'is_manual_entry' => true,
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function can_filter_manual_attendance_entries()
    {
        // Create manual attendance entries
        $attendance1 = Attendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2025-07-18',
            'check_in' => '2025-07-18 08:00:00',
            'working_hours' => 9.0,
            'status' => 'present',
            'is_manual_entry' => true,
            'manual_entry_reason' => 'Test reason 1',
            'manual_entry_by' => $this->adminUser->id,
        ]);

        $response = $this->getJson('/api/v1/manual-attendance/entries?employee_id=' . $this->employee->id . '&date_from=2025-07-18&date_to=2025-07-18');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'entries' => [
                    [
                        'id' => $attendance1->id,
                        'employee_id' => $this->employee->id,
                        'date' => '2025-07-18',
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function can_bulk_create_manual_attendance_entries()
    {
        $employee2 = Employee::factory()->create([
            'user_id' => User::factory()->create()->id,
            'location_id' => $this->location->id,
        ]);

        $response = $this->postJson('/api/v1/manual-attendance/bulk', [
            'entries' => [
                [
                    'employee_id' => $this->employee->id,
                    'date' => '2025-07-18',
                    'check_in_time' => '08:00',
                    'check_out_time' => '17:00',
                ],
                [
                    'employee_id' => $employee2->id,
                    'date' => '2025-07-18',
                    'check_in_time' => '08:30',
                    'check_out_time' => '17:30',
                ],
            ],
            'reason' => 'Bulk manual entry test',
            'notes' => 'Test notes',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Bulk manual attendance entries processed',
            'data' => [
                'summary' => [
                    'total' => 2,
                    'successful' => 2,
                    'failed' => 0,
                ],
            ],
        ]);

        // Verify both entries were created
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'date' => '2025-07-18',
            'is_manual_entry' => true,
            'manual_entry_reason' => 'Bulk manual entry test',
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee2->id,
            'date' => '2025-07-18',
            'is_manual_entry' => true,
            'manual_entry_reason' => 'Bulk manual entry test',
        ]);
    }

    /** @test */
    public function validates_required_fields()
    {
        $response = $this->postJson('/api/v1/manual-attendance', [
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'employee_id',
            'date',
            'check_in_time',
            'reason',
        ]);
    }

    /** @test */
    public function validates_check_out_time_after_check_in()
    {
        $response = $this->postJson('/api/v1/manual-attendance', [
            'employee_id' => $this->employee->id,
            'date' => '2025-07-18',
            'check_in_time' => '17:00',
            'check_out_time' => '08:00', // Before check-in time
            'reason' => 'Test reason',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['check_out_time']);
    }

    /** @test */
    public function unauthorized_user_cannot_access_manual_attendance()
    {
        // Create user without permissions
        $unauthorizedUser = User::factory()->create();
        Sanctum::actingAs($unauthorizedUser);

        $response = $this->postJson('/api/v1/manual-attendance', [
            'employee_id' => $this->employee->id,
            'date' => '2025-07-18',
            'check_in_time' => '08:00',
            'reason' => 'Test reason',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function can_handle_bulk_create_with_errors()
    {
        // Create existing attendance for first employee
        Attendance::create([
            'employee_id' => $this->employee->id,
            'date' => '2025-07-18',
            'check_in' => '2025-07-18 08:00:00',
            'status' => 'present',
            'is_manual_entry' => false,
        ]);

        $employee2 = Employee::factory()->create([
            'user_id' => User::factory()->create()->id,
            'location_id' => $this->location->id,
        ]);

        $response = $this->postJson('/api/v1/manual-attendance/bulk', [
            'entries' => [
                [
                    'employee_id' => $this->employee->id,
                    'date' => '2025-07-18', // Should fail - already exists
                    'check_in_time' => '08:00',
                    'check_out_time' => '17:00',
                ],
                [
                    'employee_id' => $employee2->id,
                    'date' => '2025-07-18', // Should succeed
                    'check_in_time' => '08:30',
                    'check_out_time' => '17:30',
                ],
            ],
            'reason' => 'Bulk test with errors',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'summary' => [
                    'total' => 2,
                    'successful' => 1,
                    'failed' => 1,
                ],
            ],
        ]);

        // Verify only second entry was created
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee2->id,
            'date' => '2025-07-18',
            'is_manual_entry' => true,
        ]);
    }
}