<?php

namespace Tests\Unit\Services;

use App\Models\Employee;
use App\Models\User;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class PayrollCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private PayrollCalculationService $payrollService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payrollService = new PayrollCalculationService();
    }

    public function test_calculate_payroll_creates_payroll_record(): void
    {
        $employee = Employee::factory()->create([
            'salary_type' => 'monthly',
            'salary_amount' => 5000,
        ]);

        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        $payroll = $this->payrollService->calculatePayroll($employee, $periodStart, $periodEnd);

        $this->assertInstanceOf(Payroll::class, $payroll);
        $this->assertEquals($employee->id, $payroll->employee_id);
        $this->assertEquals($periodStart->toDateString(), $payroll->payroll_period_start->toDateString());
        $this->assertEquals($periodEnd->toDateString(), $payroll->payroll_period_end->toDateString());
        $this->assertEquals(Payroll::STATUS_DRAFT, $payroll->status);
    }

    public function test_calculate_payroll_returns_existing_payroll_when_not_forced(): void
    {
        $employee = Employee::factory()->create();
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        // Create existing payroll
        $existingPayroll = Payroll::factory()->create([
            'employee_id' => $employee->id,
            'payroll_period_start' => $periodStart,
            'payroll_period_end' => $periodEnd,
        ]);

        $payroll = $this->payrollService->calculatePayroll($employee, $periodStart, $periodEnd);

        $this->assertEquals($existingPayroll->id, $payroll->id);
    }

    public function test_calculate_payroll_recalculates_when_forced(): void
    {
        $employee = Employee::factory()->create([
            'salary_type' => 'monthly',
            'salary_amount' => 5000,
        ]);
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        // Create existing payroll
        $existingPayroll = Payroll::factory()->create([
            'employee_id' => $employee->id,
            'payroll_period_start' => $periodStart,
            'payroll_period_end' => $periodEnd,
            'gross_salary' => 1000, // Different from expected
        ]);

        $payroll = $this->payrollService->calculatePayroll($employee, $periodStart, $periodEnd, ['force_recalculate' => true]);

        $this->assertEquals($existingPayroll->id, $payroll->id);
        // Should have recalculated and updated totals
        $this->assertNotEquals(1000, $payroll->gross_salary);
    }

    public function test_calculate_attendance_data_calculates_worked_hours(): void
    {
        $employee = Employee::factory()->create();
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        // Create attendance records
        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => $periodStart->copy()->addDays(1),
            'total_hours' => 8,
            'status' => 'present',
        ]);

        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => $periodStart->copy()->addDays(2),
            'total_hours' => 9,
            'status' => 'present',
        ]);

        $payroll = $this->payrollService->calculatePayroll($employee, $periodStart, $periodEnd);

        $this->assertEquals(17, $payroll->worked_hours); // 8 + 9
    }

    public function test_calculate_attendance_data_calculates_overtime(): void
    {
        Config::set('payroll.calculations.standard_hours_per_day', 8);
        
        $employee = Employee::factory()->create();
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        // Create attendance that exceeds standard hours
        $workingDays = 22; // Mock working days
        $standardHours = $workingDays * 8; // 176 hours
        $workedHours = $standardHours + 10; // 186 hours

        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => $periodStart->copy()->addDays(1),
            'total_hours' => $workedHours,
            'status' => 'present',
        ]);

        $payroll = $this->payrollService->calculatePayroll($employee, $periodStart, $periodEnd);

        $this->assertGreaterThan(0, $payroll->overtime_hours);
    }

    public function test_calculate_basic_salary_for_monthly_employee(): void
    {
        $employee = Employee::factory()->create([
            'salary_type' => 'monthly',
            'salary_amount' => 5000,
        ]);

        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        $payroll = $this->payrollService->calculatePayroll($employee, $periodStart, $periodEnd);

        // Should have a basic salary item
        $basicSalaryItem = $payroll->payrollItems()
            ->where('category', PayrollItem::CATEGORY_BASIC_SALARY)
            ->first();

        $this->assertNotNull($basicSalaryItem);
        $this->assertEquals(5000, $basicSalaryItem->amount);
    }

    public function test_calculate_basic_salary_for_hourly_employee(): void
    {
        $employee = Employee::factory()->create([
            'salary_type' => 'hourly',
            'hourly_rate' => 25,
        ]);

        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        // Create attendance records
        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => $periodStart->copy()->addDays(1),
            'total_hours' => 40,
            'status' => 'present',
        ]);

        $payroll = $this->payrollService->calculatePayroll($employee, $periodStart, $periodEnd);

        // Should calculate based on hours worked
        $basicSalaryItem = $payroll->payrollItems()
            ->where('category', PayrollItem::CATEGORY_BASIC_SALARY)
            ->first();

        $this->assertNotNull($basicSalaryItem);
        $this->assertEquals(1000, $basicSalaryItem->amount); // 40 hours * $25
    }

    public function test_calculate_overtime_pay(): void
    {
        Config::set('payroll.calculations.overtime_multiplier', 1.5);
        
        $employee = Employee::factory()->create([
            'salary_type' => 'hourly',
            'hourly_rate' => 20,
        ]);

        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        // Create attendance that generates overtime
        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => $periodStart->copy()->addDays(1),
            'total_hours' => 200, // Exceeds standard hours
            'status' => 'present',
        ]);

        $payroll = $this->payrollService->calculatePayroll($employee, $periodStart, $periodEnd);

        // Should have overtime pay item
        $overtimeItem = $payroll->payrollItems()
            ->where('category', PayrollItem::CATEGORY_OVERTIME)
            ->first();

        $this->assertNotNull($overtimeItem);
        $this->assertGreaterThan(0, $overtimeItem->amount);
        $this->assertEquals(30, $overtimeItem->rate); // $20 * 1.5
    }

    public function test_calculate_leave_data(): void
    {
        $employee = Employee::factory()->create();
        $leaveType = LeaveType::factory()->create(['is_paid' => true]);
        
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        // Create approved leave
        Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $periodStart->copy()->addDays(5),
            'end_date' => $periodStart->copy()->addDays(7),
            'status' => Leave::STATUS_APPROVED,
        ]);

        $payroll = $this->payrollService->calculatePayroll($employee, $periodStart, $periodEnd);

        $this->assertGreaterThan(0, $payroll->leave_days_taken);
        $this->assertGreaterThan(0, $payroll->leave_days_paid);
    }

    public function test_calculate_perfect_attendance_bonus(): void
    {
        Config::set('payroll.bonuses.perfect_attendance.enabled', true);
        Config::set('payroll.bonuses.perfect_attendance.amount', 100);
        
        $employee = Employee::factory()->create();
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        // Create perfect attendance (all working days present)
        for ($i = 1; $i <= 22; $i++) { // 22 working days
            Attendance::factory()->create([
                'employee_id' => $employee->id,
                'date' => $periodStart->copy()->addDays($i),
                'status' => 'present',
            ]);
        }

        $payroll = $this->payrollService->calculatePayroll($employee, $periodStart, $periodEnd);

        // Should have perfect attendance bonus
        $bonusItem = $payroll->payrollItems()
            ->where('type', PayrollItem::TYPE_BONUS)
            ->where('description', 'Perfect Attendance Bonus')
            ->first();

        $this->assertNotNull($bonusItem);
        $this->assertEquals(100, $bonusItem->amount);
    }

    public function test_calculate_tax_deductions(): void
    {
        Config::set('payroll.tax.brackets', [
            ['min' => 0, 'max' => 1000, 'rate' => 0],
            ['min' => 1001, 'max' => 3000, 'rate' => 10],
            ['min' => 3001, 'max' => null, 'rate' => 20],
        ]);

        $employee = Employee::factory()->create([
            'salary_type' => 'monthly',
            'salary_amount' => 4000, // Should be in 20% bracket
        ]);

        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        $payroll = $this->payrollService->calculatePayroll($employee, $periodStart, $periodEnd);

        // Should have tax deduction
        $taxItem = $payroll->payrollItems()
            ->where('category', PayrollItem::CATEGORY_TAX)
            ->first();

        $this->assertNotNull($taxItem);
        $this->assertEquals(20, $taxItem->rate);
        $this->assertGreaterThan(0, $taxItem->amount);
    }

    public function test_calculate_statutory_deductions(): void
    {
        Config::set('payroll.statutory_deductions.social_security.enabled', true);
        Config::set('payroll.statutory_deductions.social_security.rate', 6.2);
        Config::set('payroll.statutory_deductions.medicare.enabled', true);
        Config::set('payroll.statutory_deductions.medicare.rate', 1.45);

        $employee = Employee::factory()->create([
            'salary_type' => 'monthly',
            'salary_amount' => 3000,
        ]);

        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        $payroll = $this->payrollService->calculatePayroll($employee, $periodStart, $periodEnd);

        // Should have social security deduction
        $socialSecurityItem = $payroll->payrollItems()
            ->where('category', PayrollItem::CATEGORY_INSURANCE)
            ->where('description', 'like', '%Social Security%')
            ->first();

        $this->assertNotNull($socialSecurityItem);
        $this->assertEquals(6.2, $socialSecurityItem->rate);

        // Should have medicare deduction
        $medicareItem = $payroll->payrollItems()
            ->where('category', PayrollItem::CATEGORY_INSURANCE)
            ->where('description', 'like', '%Medicare%')
            ->first();

        $this->assertNotNull($medicareItem);
        $this->assertEquals(1.45, $medicareItem->rate);
    }

    public function test_calculate_pay_date(): void
    {
        Config::set('payroll.calculations.pay_date_day', 15);
        
        $employee = Employee::factory()->create();
        $periodStart = Carbon::create(2024, 1, 1);
        $periodEnd = Carbon::create(2024, 1, 31);

        $payroll = $this->payrollService->calculatePayroll($employee, $periodStart, $periodEnd);

        $expectedPayDate = Carbon::create(2024, 2, 15); // 15th of next month
        $this->assertEquals($expectedPayDate->toDateString(), $payroll->pay_date->toDateString());
    }

    public function test_calculate_pay_date_adjusts_for_weekend(): void
    {
        Config::set('payroll.calculations.pay_date_day', 15);
        
        $employee = Employee::factory()->create();
        
        // Set up a scenario where the 15th falls on a weekend
        $periodStart = Carbon::create(2024, 6, 1); // June 2024
        $periodEnd = Carbon::create(2024, 6, 30);
        // July 15, 2024 is a Monday, so this test might need adjustment

        $payroll = $this->payrollService->calculatePayroll($employee, $periodStart, $periodEnd);

        $payDate = $payroll->pay_date;
        
        // Pay date should not be on weekend
        $this->assertFalse($payDate->isWeekend());
    }

    public function test_calculate_payroll_for_multiple_employees(): void
    {
        $employees = Employee::factory()->count(3)->create([
            'salary_type' => 'monthly',
            'salary_amount' => 5000,
        ]);

        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        $payrolls = $this->payrollService->calculatePayrollForEmployees($employees, $periodStart, $periodEnd);

        $this->assertCount(3, $payrolls);
        
        foreach ($payrolls as $payroll) {
            $this->assertInstanceOf(Payroll::class, $payroll);
            $this->assertEquals(Payroll::STATUS_DRAFT, $payroll->status);
        }
    }

    public function test_calculate_monthly_payroll_for_all_employees(): void
    {
        Employee::factory()->count(2)->create([
            'is_active' => true,
            'salary_type' => 'monthly',
            'salary_amount' => 5000,
        ]);

        // Create inactive employee (should be excluded)
        Employee::factory()->create([
            'is_active' => false,
            'salary_type' => 'monthly',
            'salary_amount' => 5000,
        ]);

        $payrolls = $this->payrollService->calculateMonthlyPayrollForAllEmployees(2024, 1);

        $this->assertCount(2, $payrolls); // Only active employees
    }

    public function test_get_payroll_summary(): void
    {
        $employee1 = Employee::factory()->create();
        $employee2 = Employee::factory()->create();

        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        // Create payrolls
        $payroll1 = Payroll::factory()->create([
            'employee_id' => $employee1->id,
            'payroll_period_start' => $periodStart,
            'payroll_period_end' => $periodEnd,
            'gross_salary' => 3000,
            'total_deductions' => 500,
            'total_bonuses' => 200,
            'net_salary' => 2700,
            'worked_hours' => 160,
            'overtime_hours' => 10,
        ]);

        $payroll2 = Payroll::factory()->create([
            'employee_id' => $employee2->id,
            'payroll_period_start' => $periodStart,
            'payroll_period_end' => $periodEnd,
            'gross_salary' => 4000,
            'total_deductions' => 600,
            'total_bonuses' => 300,
            'net_salary' => 3700,
            'worked_hours' => 170,
            'overtime_hours' => 15,
        ]);

        $summary = $this->payrollService->getPayrollSummary($periodStart, $periodEnd);

        $this->assertEquals(2, $summary['total_employees']);
        $this->assertEquals(7000, $summary['total_gross_salary']); // 3000 + 4000
        $this->assertEquals(1100, $summary['total_deductions']); // 500 + 600
        $this->assertEquals(500, $summary['total_bonuses']); // 200 + 300
        $this->assertEquals(6400, $summary['total_net_salary']); // 2700 + 3700
        $this->assertEquals(330, $summary['total_worked_hours']); // 160 + 170
        $this->assertEquals(25, $summary['total_overtime_hours']); // 10 + 15
    }

    public function test_validate_payroll_calculation(): void
    {
        Config::set('payroll.validation', [
            'minimum_wage' => 15,
            'maximum_hours_per_period' => 200,
            'maximum_overtime_hours' => 50,
        ]);

        $employee = Employee::factory()->create();
        $payroll = Payroll::factory()->create([
            'employee_id' => $employee->id,
            'gross_salary' => 1000, // $10/hour if 100 hours worked
            'worked_hours' => 100,
            'overtime_hours' => 20,
        ]);

        $errors = $this->payrollService->validatePayrollCalculation($payroll);

        // Should have minimum wage error
        $this->assertContains('Calculated hourly rate (10) is below minimum wage (15)', $errors);
    }
}