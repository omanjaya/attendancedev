<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Employee Management Tests
 *
 * Comprehensive test coverage for all employee operations
 */
class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private Role $adminRole;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->adminRole = Role::create(['name' => 'admin']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($this->adminRole);
        $this->location = Location::factory()->create();

        Storage::fake('public');
    }

    /** @test */
    public function admin_can_view_employee_index()
    {
        $employees = Employee::factory(3)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('employees.index'));

        $response->assertOk()
            ->assertViewIs('pages.management.employees.index')
            ->assertViewHas('employees');
    }

    /** @test */
    public function admin_can_create_employee()
    {
        $employeeData = [
            'employee_id' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'employee_type' => 'permanent',
            'hire_date' => '2024-01-01',
            'salary_type' => 'monthly',
            'salary_amount' => 5000000,
            'location_id' => $this->location->id,
            'role' => $this->adminRole->name,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('employees.store'), $employeeData);

        $response->assertRedirect(route('employees.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('employees', [
            'employee_id' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
        ]);
    }

    /** @test */
    public function admin_can_upload_employee_photo()
    {
        $photo = UploadedFile::fake()->image('employee.jpg');

        $employeeData = [
            'employee_id' => 'EMP002',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'employee_type' => 'permanent',
            'hire_date' => '2024-01-01',
            'salary_type' => 'monthly',
            'salary_amount' => 4500000,
            'photo' => $photo,
            'role' => $this->adminRole->name,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('employees.store'), $employeeData);

        $response->assertRedirect(route('employees.index'));

        $employee = Employee::where('employee_id', 'EMP002')->first();
        $this->assertNotNull($employee->photo_path);
        Storage::disk('public')->assertExists($employee->photo_path);
    }

    /** @test */
    public function admin_can_update_employee()
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Name',
        ]);

        $updateData = [
            'employee_id' => $employee->employee_id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => $employee->user->email,
            'employee_type' => 'honorary',
            'hire_date' => $employee->hire_date->format('Y-m-d'),
            'salary_type' => 'hourly',
            'hourly_rate' => 75000,
            'role' => $employee->user->roles->first()->name,
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('employees.update', $employee), $updateData);

        $response->assertRedirect(route('employees.index'));

        $employee->refresh();
        $this->assertEquals('Updated', $employee->first_name);
        $this->assertEquals('honorary', $employee->employee_type);
        $this->assertEquals(75000, $employee->hourly_rate);
    }

    /** @test */
    public function admin_can_delete_employee()
    {
        $employee = Employee::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->delete(route('employees.destroy', $employee));

        $response->assertRedirect(route('employees.index'));
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    /** @test */
    public function validation_prevents_duplicate_employee_id()
    {
        $existingEmployee = Employee::factory()->create(['employee_id' => 'EMP001']);

        $employeeData = [
            'employee_id' => 'EMP001', // Duplicate
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'employee_type' => 'permanent',
            'hire_date' => '2024-01-01',
            'salary_type' => 'monthly',
            'salary_amount' => 5000000,
            'role' => $this->adminRole->name,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('employees.store'), $employeeData);

        $response->assertSessionHasErrors('employee_id');
    }

    /** @test */
    public function validation_prevents_duplicate_email()
    {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $employeeData = [
            'employee_id' => 'EMP002',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'existing@example.com', // Duplicate
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'employee_type' => 'permanent',
            'hire_date' => '2024-01-01',
            'salary_type' => 'monthly',
            'salary_amount' => 5000000,
            'role' => $this->adminRole->name,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('employees.store'), $employeeData);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function hourly_employees_require_hourly_rate()
    {
        $employeeData = [
            'employee_id' => 'EMP003',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'employee_type' => 'permanent',
            'hire_date' => '2024-01-01',
            'salary_type' => 'hourly',
            // Missing hourly_rate
            'role' => $this->adminRole->name,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('employees.store'), $employeeData);

        $response->assertSessionHasErrors('hourly_rate');
    }

    /** @test */
    public function monthly_employees_require_salary_amount()
    {
        $employeeData = [
            'employee_id' => 'EMP004',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'employee_type' => 'permanent',
            'hire_date' => '2024-01-01',
            'salary_type' => 'monthly',
            // Missing salary_amount
            'role' => $this->adminRole->name,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('employees.store'), $employeeData);

        $response->assertSessionHasErrors('salary_amount');
    }

    /** @test */
    public function datatable_endpoint_returns_correct_data()
    {
        Employee::factory(5)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('employees.data'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'department',
                        'status',
                        'actions',
                    ],
                ],
            ]);
    }

    /** @test */
    public function bulk_delete_removes_multiple_employees()
    {
        $employees = Employee::factory(3)->create();
        $employeeIds = $employees->pluck('id')->implode(',');

        $response = $this->actingAs($this->adminUser)
            ->post(route('employees.bulk'), [
                'operation' => 'delete',
                'employee_ids' => $employeeIds,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        foreach ($employees as $employee) {
            $this->assertSoftDeleted('employees', ['id' => $employee->id]);
        }
    }

    /** @test */
    public function bulk_status_update_works()
    {
        $employees = Employee::factory(3)->create(['is_active' => true]);
        $employeeIds = $employees->pluck('id')->implode(',');

        $response = $this->actingAs($this->adminUser)
            ->post(route('employees.bulk'), [
                'operation' => 'deactivate',
                'employee_ids' => $employeeIds,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        foreach ($employees as $employee) {
            $this->assertDatabaseHas('employees', [
                'id' => $employee->id,
                'is_active' => false,
            ]);
        }
    }
}
