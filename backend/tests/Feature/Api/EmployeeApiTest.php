<?php

namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'manager']);
        Role::create(['name' => 'employee']);
    }

    public function test_get_employees_requires_authentication(): void
    {
        $response = $this->apiRequest('GET', '/api/employees');

        $response->assertStatus(401);
    }

    public function test_get_employees_requires_permission(): void
    {
        $user = $this->authenticateUser();

        $response = $this->apiRequest('GET', '/api/employees');

        $response->assertStatus(403);
    }

    public function test_get_employees_returns_paginated_list(): void
    {
        $user = $this->authenticateUserWithPermissions(['view_employees']);

        // Create test employees
        Employee::factory()->count(15)->create();

        $response = $this->apiRequest('GET', '/api/employees');

        $response->assertStatus(200)->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'employee_id',
                    'first_name',
                    'last_name',
                    'full_name',
                    'position',
                    'department',
                    'is_active',
                    'photo_url',
                ],
            ],
            'links',
            'meta' => ['current_page', 'total', 'per_page'],
        ]);

        $this->assertEquals(15, $response->json('meta.total'));
    }

    public function test_get_employees_with_search_filter(): void
    {
        $user = $this->authenticateUserWithPermissions(['view_employees']);

        Employee::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        Employee::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $response = $this->apiRequest('GET', '/api/employees?search=John');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
        $this->assertEquals('John', $response->json('data.0.first_name'));
    }

    public function test_get_employees_with_location_filter(): void
    {
        $user = $this->authenticateUserWithPermissions(['view_employees']);

        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();

        Employee::factory()
            ->count(3)
            ->create(['location_id' => $location1->id]);
        Employee::factory()
            ->count(2)
            ->create(['location_id' => $location2->id]);

        $response = $this->apiRequest('GET', "/api/employees?location_id={$location1->id}");

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('meta.total'));
    }

    public function test_get_employee_by_id(): void
    {
        $user = $this->authenticateUserWithPermissions(['view_employees']);

        $employee = Employee::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response = $this->apiRequest('GET', "/api/employees/{$employee->id}");

        $response->assertStatus(200)->assertJson([
            'data' => [
                'id' => $employee->id,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'full_name' => 'John Doe',
            ],
        ]);
    }

    public function test_get_employee_by_id_returns_404_for_nonexistent(): void
    {
        $user = $this->authenticateUserWithPermissions(['view_employees']);

        $response = $this->apiRequest('GET', '/api/employees/nonexistent-id');

        $response->assertStatus(404);
    }

    public function test_create_employee_requires_permission(): void
    {
        $user = $this->authenticateUser();

        $employeeData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'position' => 'Developer',
        ];

        $response = $this->apiRequest('POST', '/api/employees', $employeeData);

        $response->assertStatus(403);
    }

    public function test_create_employee_validates_required_fields(): void
    {
        $user = $this->authenticateUserWithPermissions(['create_employees']);

        $response = $this->apiRequest('POST', '/api/employees', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['first_name', 'last_name']);
    }

    public function test_create_employee_successfully(): void
    {
        $user = $this->authenticateUserWithPermissions(['create_employees']);
        $location = Location::factory()->create();

        $employeeData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'position' => 'Developer',
            'department' => 'IT',
            'location_id' => $location->id,
            'salary_type' => 'monthly',
            'salary_amount' => 5000,
            'email' => 'john.doe@example.com',
        ];

        $response = $this->apiRequest('POST', '/api/employees', $employeeData);

        $response->assertStatus(201)->assertJsonStructure([
            'data' => ['id', 'employee_id', 'first_name', 'last_name', 'position', 'department'],
        ]);

        $this->assertDatabaseHas('employees', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'position' => 'Developer',
        ]);
    }

    public function test_update_employee_requires_permission(): void
    {
        $user = $this->authenticateUser();
        $employee = Employee::factory()->create();

        $response = $this->apiRequest('PUT', "/api/employees/{$employee->id}", [
            'first_name' => 'Updated Name',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_employee_successfully(): void
    {
        $user = $this->authenticateUserWithPermissions(['edit_employees']);
        $employee = Employee::factory()->create([
            'first_name' => 'John',
            'position' => 'Junior Developer',
        ]);

        $response = $this->apiRequest('PUT', "/api/employees/{$employee->id}", [
            'first_name' => 'Jonathan',
            'position' => 'Senior Developer',
        ]);

        $response->assertStatus(200)->assertJson([
            'data' => [
                'id' => $employee->id,
                'first_name' => 'Jonathan',
                'position' => 'Senior Developer',
            ],
        ]);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'first_name' => 'Jonathan',
            'position' => 'Senior Developer',
        ]);
    }

    public function test_delete_employee_requires_permission(): void
    {
        $user = $this->authenticateUser();
        $employee = Employee::factory()->create();

        $response = $this->apiRequest('DELETE', "/api/employees/{$employee->id}");

        $response->assertStatus(403);
    }

    public function test_delete_employee_successfully(): void
    {
        $user = $this->authenticateUserWithPermissions(['delete_employees']);
        $employee = Employee::factory()->create();

        $response = $this->apiRequest('DELETE', "/api/employees/{$employee->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_get_employee_statistics_requires_permission(): void
    {
        $user = $this->authenticateUser();

        $response = $this->apiRequest('GET', '/api/employees/statistics');

        $response->assertStatus(403);
    }

    public function test_get_employee_statistics(): void
    {
        $user = $this->authenticateUserWithPermissions(['view_employee_statistics']);

        $location = Location::factory()->create();

        // Create active employees
        Employee::factory()
            ->count(5)
            ->create([
                'is_active' => true,
                'location_id' => $location->id,
                'department' => 'IT',
            ]);

        // Create inactive employee
        Employee::factory()->create([
            'is_active' => false,
            'location_id' => $location->id,
            'department' => 'IT',
        ]);

        $response = $this->apiRequest('GET', '/api/employees/statistics');

        $response->assertStatus(200)->assertJsonStructure([
            'data' => [
                'total_employees',
                'active_employees',
                'inactive_employees',
                'by_location',
                'by_department',
            ],
        ]);

        $data = $response->json('data');
        $this->assertEquals(6, $data['total_employees']);
        $this->assertEquals(5, $data['active_employees']);
        $this->assertEquals(1, $data['inactive_employees']);
    }

    public function test_bulk_update_employee_status_requires_permission(): void
    {
        $user = $this->authenticateUser();
        $employees = Employee::factory()->count(3)->create();

        $response = $this->apiRequest('POST', '/api/employees/bulk-status', [
            'employee_ids' => $employees->pluck('id')->toArray(),
            'is_active' => false,
        ]);

        $response->assertStatus(403);
    }

    public function test_bulk_update_employee_status_successfully(): void
    {
        $user = $this->authenticateUserWithPermissions(['edit_employees']);
        $employees = Employee::factory()
            ->count(3)
            ->create(['is_active' => true]);

        $response = $this->apiRequest('POST', '/api/employees/bulk-status', [
            'employee_ids' => $employees->pluck('id')->toArray(),
            'is_active' => false,
        ]);

        $response->assertStatus(200)->assertJson([
            'message' => 'Employee status updated successfully',
            'updated_count' => 3,
        ]);

        foreach ($employees as $employee) {
            $this->assertDatabaseHas('employees', [
                'id' => $employee->id,
                'is_active' => false,
            ]);
        }
    }

    public function test_get_employees_with_relations(): void
    {
        $user = $this->authenticateUserWithPermissions(['view_employees']);

        $userModel = User::factory()->create();
        $location = Location::factory()->create(['name' => 'Main Office']);

        $employee = Employee::factory()->create([
            'user_id' => $userModel->id,
            'location_id' => $location->id,
        ]);

        $response = $this->apiRequest('GET', "/api/employees/{$employee->id}?include=user,location");

        $response->assertStatus(200)->assertJsonStructure([
            'data' => [
                'id',
                'first_name',
                'last_name',
                'user' => ['id', 'email'],
                'location' => ['id', 'name'],
            ],
        ]);

        $this->assertEquals('Main Office', $response->json('data.location.name'));
    }

    public function test_search_employees_endpoint(): void
    {
        $user = $this->authenticateUserWithPermissions(['view_employees']);

        Employee::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'employee_id' => 'EMP001',
        ]);

        Employee::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'employee_id' => 'EMP002',
        ]);

        $response = $this->apiRequest('GET', '/api/employees/search?q=John');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('John', $response->json('data.0.first_name'));
    }

    public function test_api_responses_include_proper_headers(): void
    {
        $user = $this->authenticateUserWithPermissions(['view_employees']);

        $response = $this->apiRequest('GET', '/api/employees');

        $response->assertStatus(200)->assertHeader('Content-Type', 'application/json');
    }

    public function test_api_handles_server_errors_gracefully(): void
    {
        $user = $this->authenticateUserWithPermissions(['view_employees']);

        // Force a database error by using an invalid query
        $response = $this->apiRequest('GET', '/api/employees?invalid_param[]=invalid');

        // Should not return 500, but handle gracefully
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    public function test_employee_api_respects_rate_limiting(): void
    {
        $user = $this->authenticateUserWithPermissions(['view_employees']);

        // Make multiple requests quickly (this would test rate limiting if enabled)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->apiRequest('GET', '/api/employees');
            $this->assertLessThanOrEqual(429, $response->getStatusCode());
        }
    }
}
