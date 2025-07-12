<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\User;
use App\Models\Location;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Payroll;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $employee->user);
        $this->assertEquals($user->id, $employee->user->id);
    }

    public function test_employee_belongs_to_location(): void
    {
        $location = Location::factory()->create();
        $employee = Employee::factory()->create(['location_id' => $location->id]);

        $this->assertInstanceOf(Location::class, $employee->location);
        $this->assertEquals($location->id, $employee->location->id);
    }

    public function test_full_name_attribute(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        $this->assertEquals('John Doe', $employee->full_name);
    }

    public function test_photo_url_attribute_with_photo(): void
    {
        $employee = Employee::factory()->create([
            'photo_path' => 'employees/photo.jpg'
        ]);

        $expectedUrl = asset('storage/employees/photo.jpg');
        $this->assertEquals($expectedUrl, $employee->photo_url);
    }

    public function test_photo_url_attribute_without_photo(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'photo_path' => null
        ]);

        $expectedUrl = 'https://ui-avatars.com/api/?name=' . urlencode('John Doe') . '&background=206bc4&color=fff&size=200';
        $this->assertEquals($expectedUrl, $employee->photo_url);
    }

    public function test_employee_has_many_attendances(): void
    {
        $employee = Employee::factory()->create();
        $attendances = Attendance::factory()->count(3)->create([
            'employee_id' => $employee->id
        ]);

        $this->assertCount(3, $employee->attendances);
        $this->assertInstanceOf(Attendance::class, $employee->attendances->first());
    }

    public function test_today_attendance_scope(): void
    {
        $employee = Employee::factory()->create();
        
        // Create today's attendance
        $todayAttendance = Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => today()
        ]);

        // Create yesterday's attendance
        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => today()->subDay()
        ]);

        $todayRecord = $employee->todayAttendance()->first();
        
        $this->assertNotNull($todayRecord);
        $this->assertEquals($todayAttendance->id, $todayRecord->id);
        $this->assertTrue($todayRecord->date->isToday());
    }

    public function test_is_checked_in_today(): void
    {
        $employee = Employee::factory()->create();

        // Not checked in
        $this->assertFalse($employee->isCheckedInToday());

        // Create checked in attendance
        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => today(),
            'check_in_time' => now()->subHours(2),
            'check_out_time' => null
        ]);

        // Refresh the employee to clear cached relationships
        $employee->refresh();
        $this->assertTrue($employee->isCheckedInToday());

        // Create checked out attendance
        $employee->attendances()->update(['check_out_time' => now()]);
        $employee->refresh();
        $this->assertFalse($employee->isCheckedInToday());
    }

    public function test_employee_has_many_leaves(): void
    {
        $employee = Employee::factory()->create();
        $leaves = Leave::factory()->count(2)->create([
            'employee_id' => $employee->id
        ]);

        $this->assertCount(2, $employee->leaves);
        $this->assertInstanceOf(Leave::class, $employee->leaves->first());
    }

    public function test_employee_has_many_leave_balances(): void
    {
        $employee = Employee::factory()->create();
        $leaveType = LeaveType::factory()->create();
        
        $leaveBalance = LeaveBalance::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id
        ]);

        $this->assertCount(1, $employee->leaveBalances);
        $this->assertInstanceOf(LeaveBalance::class, $employee->leaveBalances->first());
    }

    public function test_get_leave_balance(): void
    {
        $employee = Employee::factory()->create();
        $leaveType = LeaveType::factory()->create();
        
        $leaveBalance = LeaveBalance::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => date('Y'),
            'remaining_days' => 15
        ]);

        $balance = $employee->getLeaveBalance($leaveType->id);
        
        $this->assertNotNull($balance);
        $this->assertEquals($leaveBalance->id, $balance->id);
        $this->assertEquals(15, $balance->remaining_days);
    }

    public function test_get_leave_balance_for_specific_year(): void
    {
        $employee = Employee::factory()->create();
        $leaveType = LeaveType::factory()->create();
        
        // Create balance for current year
        LeaveBalance::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => date('Y'),
            'remaining_days' => 15
        ]);

        // Create balance for last year
        $lastYearBalance = LeaveBalance::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => date('Y') - 1,
            'remaining_days' => 10
        ]);

        $balance = $employee->getLeaveBalance($leaveType->id, date('Y') - 1);
        
        $this->assertNotNull($balance);
        $this->assertEquals($lastYearBalance->id, $balance->id);
        $this->assertEquals(10, $balance->remaining_days);
    }

    public function test_employee_has_many_payrolls(): void
    {
        $employee = Employee::factory()->create();
        $payrolls = Payroll::factory()->count(2)->create([
            'employee_id' => $employee->id
        ]);

        $this->assertCount(2, $employee->payrolls);
        $this->assertInstanceOf(Payroll::class, $employee->payrolls->first());
    }

    public function test_current_month_payroll(): void
    {
        $employee = Employee::factory()->create();
        
        // Create current month payroll
        $currentPayroll = Payroll::factory()->create([
            'employee_id' => $employee->id,
            'payroll_period_start' => now()->startOfMonth(),
            'payroll_period_end' => now()->endOfMonth()
        ]);

        // Create last month payroll
        Payroll::factory()->create([
            'employee_id' => $employee->id,
            'payroll_period_start' => now()->subMonth()->startOfMonth(),
            'payroll_period_end' => now()->subMonth()->endOfMonth()
        ]);

        $currentMonthPayroll = $employee->currentMonthPayroll();
        
        $this->assertNotNull($currentMonthPayroll);
        $this->assertEquals($currentPayroll->id, $currentMonthPayroll->id);
    }

    public function test_approved_leaves_relationship(): void
    {
        $manager = Employee::factory()->create();
        $employee = Employee::factory()->create();
        
        $approvedLeaves = Leave::factory()->count(2)->create([
            'employee_id' => $employee->id,
            'approved_by' => $manager->id,
            'status' => 'approved'
        ]);

        $this->assertCount(2, $manager->approvedLeaves);
        $this->assertInstanceOf(Leave::class, $manager->approvedLeaves->first());
    }

    public function test_approved_payrolls_relationship(): void
    {
        $manager = Employee::factory()->create();
        $employee = Employee::factory()->create();
        
        $approvedPayrolls = Payroll::factory()->count(2)->create([
            'employee_id' => $employee->id,
            'approved_by' => $manager->id,
            'status' => 'approved'
        ]);

        $this->assertCount(2, $manager->approvedPayrolls);
        $this->assertInstanceOf(Payroll::class, $manager->approvedPayrolls->first());
    }

    public function test_processed_payrolls_relationship(): void
    {
        $processor = Employee::factory()->create();
        $employee = Employee::factory()->create();
        
        $processedPayrolls = Payroll::factory()->count(2)->create([
            'employee_id' => $employee->id,
            'processed_by' => $processor->id,
            'status' => 'processed'
        ]);

        $this->assertCount(2, $processor->processedPayrolls);
        $this->assertInstanceOf(Payroll::class, $processor->processedPayrolls->first());
    }

    public function test_employee_uses_uuid(): void
    {
        $employee = Employee::factory()->create();
        
        // UUID should be automatically generated
        $this->assertNotNull($employee->id);
        $this->assertTrue(is_string($employee->id));
        $this->assertEquals(36, strlen($employee->id)); // UUID length with hyphens
    }

    public function test_employee_uses_soft_deletes(): void
    {
        $employee = Employee::factory()->create();
        $employeeId = $employee->id;

        // Delete the employee
        $employee->delete();

        // Should not be found in normal queries
        $this->assertNull(Employee::find($employeeId));

        // Should be found with trashed
        $this->assertNotNull(Employee::withTrashed()->find($employeeId));
    }

    public function test_employee_metadata_casting(): void
    {
        $metadata = [
            'face_embedding' => 'some_face_data',
            'preferences' => ['theme' => 'dark'],
            'skills' => ['PHP', 'JavaScript']
        ];

        $employee = Employee::factory()->create([
            'metadata' => $metadata
        ]);

        $this->assertIsArray($employee->metadata);
        $this->assertEquals($metadata, $employee->metadata);
        $this->assertEquals('dark', $employee->metadata['preferences']['theme']);
    }

    public function test_employee_salary_amount_casting(): void
    {
        $employee = Employee::factory()->create([
            'salary_amount' => 75000.50
        ]);

        $this->assertIsString($employee->salary_amount);
        $this->assertEquals('75000.50', $employee->salary_amount);
    }

    public function test_employee_hire_date_casting(): void
    {
        $hireDate = '2023-01-15';
        $employee = Employee::factory()->create([
            'hire_date' => $hireDate
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $employee->hire_date);
        $this->assertEquals($hireDate, $employee->hire_date->format('Y-m-d'));
    }

    public function test_employee_is_active_casting(): void
    {
        $employee = Employee::factory()->create([
            'is_active' => 1
        ]);

        $this->assertIsBool($employee->is_active);
        $this->assertTrue($employee->is_active);

        $employee->update(['is_active' => 0]);
        $this->assertFalse($employee->is_active);
    }
}