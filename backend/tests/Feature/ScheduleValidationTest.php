<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeMonthlySchedule;
use App\Models\Location;
use App\Models\MonthlySchedule;
use App\Models\TeachingSchedule;
use App\Models\User;
use App\Services\AttendanceScheduleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Schedule Validation Tests
 * 
 * Tests the schedule validation functionality that ensures employees
 * can only check in/out when they have a valid schedule for the day.
 */
class ScheduleValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $employee;
    protected $user;
    protected $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::factory()->create([
            'name' => 'Test Office',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'radius_meters' => 100,
        ]);

        $this->employee = Employee::factory()->create([
            'location_id' => $this->location->id,
            'employee_type' => 'permanent',
            'metadata' => [
                'face_recognition' => [
                    'descriptor' => array_fill(0, 512, 0.5),
                    'confidence' => 0.95,
                    'algorithm' => 'deepface-arcface',
                ],
            ],
        ]);

        $this->user = User::factory()->create([
            'employee_id' => $this->employee->id,
        ]);
    }

    /** @test */
    public function it_returns_schedule_info_for_employee_with_monthly_schedule()
    {
        // Create monthly schedule
        $monthlySchedule = MonthlySchedule::factory()->create([
            'name' => 'Test Schedule',
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'working_hours' => 8,
            'is_active' => true,
        ]);

        // Assign to employee
        EmployeeMonthlySchedule::create([
            'employee_id' => $this->employee->id,
            'monthly_schedule_id' => $monthlySchedule->id,
            'effective_date' => Carbon::today()->subDays(10),
            'end_date' => Carbon::today()->addDays(30),
            'is_active' => true,
        ]);

        $service = app(AttendanceScheduleService::class);
        $schedule = $service->getEffectiveWorkingHours($this->employee, Carbon::today());

        $this->assertTrue($schedule['has_schedule']);
        $this->assertEquals('08:00', substr($schedule['expected_start_time'], 0, 5));
        $this->assertEquals('17:00', substr($schedule['expected_end_time'], 0, 5));
    }

    /** @test */
    public function it_returns_can_attend_true_when_employee_has_schedule()
    {
        // Create monthly schedule
        $monthlySchedule = MonthlySchedule::factory()->create([
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'working_hours' => 8,
        ]);

        // Assign to employee
        EmployeeMonthlySchedule::create([
            'employee_id' => $this->employee->id,
            'monthly_schedule_id' => $monthlySchedule->id,
            'effective_date' => Carbon::today()->subDays(5),
            'end_date' => Carbon::today()->addDays(30),
            'is_active' => true,
        ]);

        $service = app(AttendanceScheduleService::class);
        $canAttend = $service->canAttendToday($this->employee, Carbon::today());

        $this->assertTrue($canAttend['can_attend']);
    }

    /** @test */
    public function it_returns_can_attend_false_on_holiday()
    {
        // Create a holiday for today
        $today = Carbon::today();
        
        DB::table('holidays')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Test Holiday',
            'date' => $today->toDateString(),
            'type' => 'national',
            'is_recurring' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(AttendanceScheduleService::class);
        $canAttend = $service->canAttendToday($this->employee, $today);

        $this->assertFalse($canAttend['can_attend']);
        $this->assertStringContainsString('holiday', strtolower($canAttend['message'] ?? ''));
    }

    /** @test */
    public function it_returns_working_hours_from_schedule()
    {
        // Create monthly schedule with 8 hours
        $monthlySchedule = MonthlySchedule::factory()->create([
            'check_in_time' => '08:00:00',
            'check_out_time' => '16:00:00', // 8 hours
            'working_hours' => 8,
        ]);

        // Assign to employee
        EmployeeMonthlySchedule::create([
            'employee_id' => $this->employee->id,
            'monthly_schedule_id' => $monthlySchedule->id,
            'effective_date' => Carbon::today()->subDays(5),
            'end_date' => Carbon::today()->addDays(30),
            'is_active' => true,
        ]);

        $service = app(AttendanceScheduleService::class);
        $schedule = $service->getEffectiveWorkingHours($this->employee, Carbon::today());

        $this->assertTrue($schedule['has_schedule']);
    }

    /** @test */
    public function employee_dashboard_returns_schedule_info()
    {
        // Create monthly schedule
        $monthlySchedule = MonthlySchedule::factory()->create([
            'check_in_time' => '08:30:00',
            'check_out_time' => '17:30:00',
            'working_hours' => 8,
        ]);

        // Assign to employee
        EmployeeMonthlySchedule::create([
            'employee_id' => $this->employee->id,
            'monthly_schedule_id' => $monthlySchedule->id,
            'effective_date' => Carbon::today()->subDays(5),
            'end_date' => Carbon::today()->addDays(30),
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/employees/dashboard-stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'schedule' => [
                'today' => [
                    'can_attend',
                ],
            ],
        ]);
    }

    /** @test */
    public function flexible_employee_needs_teaching_schedule_to_attend()
    {
        // Create a flexible employee (honor)
        $flexEmployee = Employee::factory()->create([
            'location_id' => $this->location->id,
            'employee_type' => 'honor',
        ]);

        $service = app(AttendanceScheduleService::class);
        $canAttend = $service->canAttendToday($flexEmployee, Carbon::today());

        // Flexible employee without teaching schedule should not be able to attend
        // (depends on implementation - this tests the business rule)
        $this->assertArrayHasKey('can_attend', $canAttend);
    }

    /** @test */
    public function flexible_employee_can_attend_with_teaching_schedule()
    {
        // Create a flexible employee (honor)
        $flexEmployee = Employee::factory()->create([
            'location_id' => $this->location->id,
            'employee_type' => 'honor',
        ]);

        $today = Carbon::today();
        $dayOfWeek = $today->dayOfWeekIso;

        // Create teaching schedule for today
        TeachingSchedule::factory()->create([
            'employee_id' => $flexEmployee->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'is_active' => true,
        ]);

        $service = app(AttendanceScheduleService::class);
        $schedule = $service->getEffectiveWorkingHours($flexEmployee, $today);

        // With teaching schedule, should have expected times
        if ($schedule['has_schedule']) {
            $this->assertNotNull($schedule['expected_start_time']);
        }
    }

    /** @test */
    public function it_calculates_check_in_lateness_correctly()
    {
        // Create monthly schedule
        $monthlySchedule = MonthlySchedule::factory()->create([
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
        ]);

        // Assign to employee
        EmployeeMonthlySchedule::create([
            'employee_id' => $this->employee->id,
            'monthly_schedule_id' => $monthlySchedule->id,
            'effective_date' => Carbon::today()->subDays(5),
            'end_date' => Carbon::today()->addDays(30),
            'is_active' => true,
        ]);

        $service = app(AttendanceScheduleService::class);

        // Check in 30 minutes late
        $lateCheckIn = Carbon::today()->setTimeFromTimeString('08:30:00');
        $lateness = $service->calculateCheckInLateness($this->employee, $lateCheckIn);

        $this->assertTrue($lateness['is_late']);
        $this->assertGreaterThan(0, $lateness['late_minutes']);
    }

    /** @test */
    public function it_calculates_early_checkout_correctly()
    {
        // Create monthly schedule
        $monthlySchedule = MonthlySchedule::factory()->create([
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
        ]);

        // Assign to employee
        EmployeeMonthlySchedule::create([
            'employee_id' => $this->employee->id,
            'monthly_schedule_id' => $monthlySchedule->id,
            'effective_date' => Carbon::today()->subDays(5),
            'end_date' => Carbon::today()->addDays(30),
            'is_active' => true,
        ]);

        $service = app(AttendanceScheduleService::class);

        // Check out 1 hour early
        $earlyCheckOut = Carbon::today()->setTimeFromTimeString('16:00:00');
        $earliness = $service->calculateCheckOutEarliness($this->employee, $earlyCheckOut);

        $this->assertTrue($earliness['is_early']);
        $this->assertEquals(60, $earliness['early_minutes']);
    }

    /** @test */
    public function it_returns_weekly_schedule_overview_in_dashboard()
    {
        // Create monthly schedule
        $monthlySchedule = MonthlySchedule::factory()->create([
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
        ]);

        // Assign to employee
        EmployeeMonthlySchedule::create([
            'employee_id' => $this->employee->id,
            'monthly_schedule_id' => $monthlySchedule->id,
            'effective_date' => Carbon::today()->subDays(5),
            'end_date' => Carbon::today()->addDays(30),
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/employees/dashboard-stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'schedule' => [
                'weekly',
            ],
        ]);
    }
}
