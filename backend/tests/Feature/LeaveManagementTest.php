<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LeaveManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    private Employee $employee;

    private LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->employee = Employee::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $this->leaveType = LeaveType::factory()->create([
            'name' => 'Annual Leave',
            'days_per_year' => 21,
        ]);

        // Create leave balance
        LeaveBalance::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => date('Y'),
            'allocated_days' => 21,
            'used_days' => 0,
            'remaining_days' => 21,
        ]);

        $this->actingAs($this->user);
    }

    public function test_can_view_leave_request_form(): void
    {
        $response = $this->get(route('leave.create'));

        $response->assertStatus(200);
        $response->assertViewIs('leave.create');
        $response->assertViewHas('leaveTypes');
    }

    public function test_can_submit_leave_request(): void
    {
        $leaveData = [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(9)->format('Y-m-d'),
            'reason' => 'Family vacation',
            'is_half_day' => false,
        ];

        $response = $this->post(route('leave.store'), $leaveData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('leaves', [
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $leaveData['start_date'],
            'end_date' => $leaveData['end_date'],
            'status' => 'pending',
        ]);
    }

    public function test_cannot_submit_leave_without_sufficient_balance(): void
    {
        // Update balance to have no remaining days
        $this->employee->leaveBalances()->update([
            'used_days' => 21,
            'remaining_days' => 0,
        ]);

        $leaveData = [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(9)->format('Y-m-d'),
            'reason' => 'Family vacation',
            'is_half_day' => false,
        ];

        $response = $this->post(route('leave.store'), $leaveData);

        $response->assertSessionHasErrors(['days_requested']);
    }

    public function test_cannot_submit_overlapping_leave_requests(): void
    {
        // Create existing leave
        Leave::factory()->create([
            'employee_id' => $this->employee->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
            'status' => 'approved',
        ]);

        $leaveData = [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(7)->format('Y-m-d'), // Overlaps with existing
            'end_date' => now()->addDays(9)->format('Y-m-d'),
            'reason' => 'Another vacation',
            'is_half_day' => false,
        ];

        $response = $this->post(route('leave.store'), $leaveData);

        $response->assertSessionHasErrors(['date_range']);
    }

    public function test_can_view_leave_requests(): void
    {
        Leave::factory()
            ->count(3)
            ->create([
                'employee_id' => $this->employee->id,
            ]);

        $response = $this->get(route('leave.requests'));

        $response->assertStatus(200);
        $response->assertViewIs('leave.requests');
    }

    public function test_can_view_leave_balance(): void
    {
        $response = $this->get(route('leave.balance.index'));

        $response->assertStatus(200);
        $response->assertViewIs('leave.balance.index');
        $response->assertViewHas('balances');
    }

    public function test_manager_can_view_approval_queue(): void
    {
        // Give user approval permission
        $this->user->givePermissionTo('approve_leave');

        Leave::factory()
            ->count(3)
            ->create([
                'status' => 'pending',
            ]);

        $response = $this->get(route('leave.approvals'));

        $response->assertStatus(200);
        $response->assertViewIs('leave.approvals.index');
    }

    public function test_manager_can_approve_leave(): void
    {
        $this->user->givePermissionTo('approve_leave');

        $leave = Leave::factory()->create([
            'status' => 'pending',
            'days_requested' => 3,
        ]);

        $response = $this->post(route('leave.approvals.approve', $leave), [
            'admin_notes' => 'Approved for family vacation',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $leave->refresh();
        $this->assertEquals('approved', $leave->status);
        $this->assertEquals($this->user->employee->id, $leave->approved_by);
        $this->assertNotNull($leave->approved_at);
    }

    public function test_manager_can_reject_leave(): void
    {
        $this->user->givePermissionTo('approve_leave');

        $leave = Leave::factory()->create([
            'status' => 'pending',
        ]);

        $response = $this->post(route('leave.approvals.reject', $leave), [
            'admin_notes' => 'Insufficient coverage during requested period',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $leave->refresh();
        $this->assertEquals('rejected', $leave->status);
        $this->assertEquals($this->user->employee->id, $leave->approved_by);
    }

    public function test_half_day_leave_calculation(): void
    {
        $leaveData = [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'reason' => 'Medical appointment',
            'is_half_day' => true,
            'half_day_period' => 'morning',
        ];

        $response = $this->post(route('leave.store'), $leaveData);

        $response->assertRedirect();

        $leave = Leave::where('employee_id', $this->employee->id)->first();
        $this->assertEquals(0.5, $leave->days_requested);
        $this->assertTrue($leave->is_half_day);
        $this->assertEquals('morning', $leave->half_day_period);
    }

    public function test_leave_balance_updated_after_approval(): void
    {
        $this->user->givePermissionTo('approve_leave');

        $initialBalance = $this->employee->leaveBalances()->first();
        $initialRemaining = $initialBalance->remaining_days;

        $leave = Leave::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'status' => 'pending',
            'days_requested' => 3,
        ]);

        $this->post(route('leave.approvals.approve', $leave));

        $initialBalance->refresh();
        $this->assertEquals($initialRemaining - 3, $initialBalance->remaining_days);
        $this->assertEquals(3, $initialBalance->used_days);
    }

    public function test_can_view_leave_calendar(): void
    {
        $response = $this->get(route('leave.calendar'));

        $response->assertStatus(200);
        $response->assertViewIs('leave.calendar.index');
    }

    public function test_leave_calendar_api_returns_events(): void
    {
        Leave::factory()->create([
            'employee_id' => $this->employee->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7),
            'status' => 'approved',
        ]);

        $response = $this->getJson(route('leave.calendar.data'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'title', 'start', 'end', 'backgroundColor'],
        ]);
    }

    public function test_bulk_leave_approval(): void
    {
        $this->user->givePermissionTo('approve_leave');

        $leaves = Leave::factory()
            ->count(3)
            ->create([
                'status' => 'pending',
            ]);

        $leaveIds = $leaves->pluck('id')->toArray();

        $response = $this->post(route('leave.approvals.bulk-approve'), [
            'leave_ids' => $leaveIds,
            'action' => 'approve',
            'admin_notes' => 'Bulk approved',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        foreach ($leaves as $leave) {
            $leave->refresh();
            $this->assertEquals('approved', $leave->status);
        }
    }

    public function test_employee_cannot_approve_own_leave(): void
    {
        $this->user->givePermissionTo('approve_leave');

        $leave = Leave::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => 'pending',
        ]);

        $response = $this->post(route('leave.approvals.approve', $leave));

        $response->assertStatus(403);
    }

    public function test_leave_request_validation_rules(): void
    {
        $invalidData = [
            'leave_type_id' => 999, // Non-existent
            'start_date' => now()->subDays(1)->format('Y-m-d'), // Past date
            'end_date' => now()->subDays(2)->format('Y-m-d'), // Before start date
            'reason' => '', // Empty reason
        ];

        $response = $this->post(route('leave.store'), $invalidData);

        $response->assertSessionHasErrors(['leave_type_id', 'start_date', 'end_date', 'reason']);
    }

    public function test_mobile_leave_interface(): void
    {
        $response = $this->get(route('leave.mobile'));

        $response->assertStatus(200);
        $response->assertViewIs('leave.mobile.index');
    }

    public function test_mobile_leave_request_form(): void
    {
        $response = $this->get(route('leave.mobile.request'));

        $response->assertStatus(200);
        $response->assertViewIs('leave.mobile.request');
    }

    public function test_leave_analytics_for_managers(): void
    {
        $this->user->givePermissionTo('approve_leave');

        // Create various leave records
        Leave::factory()
            ->count(5)
            ->create(['status' => 'approved']);
        Leave::factory()
            ->count(2)
            ->create(['status' => 'pending']);
        Leave::factory()
            ->count(1)
            ->create(['status' => 'rejected']);

        $response = $this->get(route('leave.analytics'));

        $response->assertStatus(200);
        $response->assertViewIs('leave.analytics');
    }

    public function test_leave_type_specific_rules(): void
    {
        // Create a leave type with specific rules
        $sickLeave = LeaveType::factory()->create([
            'name' => 'Sick Leave',
            'days_per_year' => 10,
            'requires_medical_certificate' => true,
            'min_days_notice' => 0,
        ]);

        LeaveBalance::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $sickLeave->id,
            'allocated_days' => 10,
            'remaining_days' => 10,
        ]);

        $leaveData = [
            'leave_type_id' => $sickLeave->id,
            'start_date' => now()->format('Y-m-d'), // Same day (allowed for sick leave)
            'end_date' => now()->format('Y-m-d'),
            'reason' => 'Flu symptoms',
        ];

        $response = $this->post(route('leave.store'), $leaveData);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
