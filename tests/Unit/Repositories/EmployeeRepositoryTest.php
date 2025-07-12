<?php

namespace Tests\Unit\Repositories;

use App\Models\Employee;
use App\Models\User;
use App\Models\Location;
use App\Repositories\EmployeeRepository;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EmployeeRepository $employeeRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->employeeRepository = new EmployeeRepository();
    }

    public function test_get_all_returns_paginated_employees(): void
    {
        Employee::factory()->count(15)->create(['is_active' => true]);

        $result = $this->employeeRepository->getAll();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->total());
    }

    public function test_get_all_with_filters(): void
    {
        $location = Location::factory()->create();
        
        Employee::factory()->count(3)->create([
            'is_active' => true,
            'location_id' => $location->id,
        ]);
        
        Employee::factory()->count(2)->create([
            'is_active' => false,
            'location_id' => $location->id,
        ]);

        // Filter by active status
        $activeEmployees = $this->employeeRepository->getAll(['is_active' => true]);
        $this->assertEquals(3, $activeEmployees->total());

        // Filter by location
        $locationEmployees = $this->employeeRepository->getAll(['location_id' => $location->id]);
        $this->assertEquals(5, $locationEmployees->total());

        // Filter by both
        $filteredEmployees = $this->employeeRepository->getAll([
            'is_active' => true,
            'location_id' => $location->id,
        ]);
        $this->assertEquals(3, $filteredEmployees->total());
    }

    public function test_get_all_with_search(): void
    {
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

        // Search by name
        $result = $this->employeeRepository->getAll(['search' => 'John']);
        $this->assertEquals(1, $result->total());

        // Search by employee ID
        $result = $this->employeeRepository->getAll(['search' => 'EMP002']);
        $this->assertEquals(1, $result->total());

        // Search by partial name
        $result = $this->employeeRepository->getAll(['search' => 'Doe']);
        $this->assertEquals(1, $result->total());
    }

    public function test_find_by_id_returns_employee(): void
    {
        $employee = Employee::factory()->create();

        $result = $this->employeeRepository->findById($employee->id);

        $this->assertInstanceOf(Employee::class, $result);
        $this->assertEquals($employee->id, $result->id);
    }

    public function test_find_by_id_returns_null_for_nonexistent(): void
    {
        $result = $this->employeeRepository->findById('nonexistent-id');

        $this->assertNull($result);
    }

    public function test_create_creates_employee_with_generated_id(): void
    {
        $user = User::factory()->create();
        $location = Location::factory()->create();

        $data = [
            'user_id' => $user->id,
            'location_id' => $location->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'position' => 'Developer',
            'department' => 'IT',
            'salary_type' => 'monthly',
            'salary_amount' => 5000,
        ];

        $employee = $this->employeeRepository->create($data);

        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertEquals('John', $employee->first_name);
        $this->assertEquals('Doe', $employee->last_name);
        $this->assertNotNull($employee->employee_id);
        $this->assertTrue($employee->is_active);
    }

    public function test_create_generates_unique_employee_id(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $employee1 = $this->employeeRepository->create([
            'user_id' => $user1->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $employee2 = $this->employeeRepository->create([
            'user_id' => $user2->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $this->assertNotEquals($employee1->employee_id, $employee2->employee_id);
    }

    public function test_update_updates_employee(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'John',
            'position' => 'Junior Developer',
        ]);

        $updatedEmployee = $this->employeeRepository->update($employee->id, [
            'first_name' => 'Jonathan',
            'position' => 'Senior Developer',
        ]);

        $this->assertEquals('Jonathan', $updatedEmployee->first_name);
        $this->assertEquals('Senior Developer', $updatedEmployee->position);
    }

    public function test_delete_soft_deletes_employee(): void
    {
        $employee = Employee::factory()->create();

        $result = $this->employeeRepository->delete($employee->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_find_by_employee_id_returns_employee(): void
    {
        $employee = Employee::factory()->create(['employee_id' => 'EMP123']);

        $result = $this->employeeRepository->findByEmployeeId('EMP123');

        $this->assertInstanceOf(Employee::class, $result);
        $this->assertEquals('EMP123', $result->employee_id);
    }

    public function test_find_by_user_id_returns_employee(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $result = $this->employeeRepository->findByUserId($user->id);

        $this->assertInstanceOf(Employee::class, $result);
        $this->assertEquals($employee->id, $result->id);
    }

    public function test_get_active_employees(): void
    {
        Employee::factory()->count(3)->create(['is_active' => true]);
        Employee::factory()->count(2)->create(['is_active' => false]);

        $result = $this->employeeRepository->getActiveEmployees();

        $this->assertEquals(3, $result->count());
        
        foreach ($result as $employee) {
            $this->assertTrue($employee->is_active);
        }
    }

    public function test_get_employees_by_location(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();

        Employee::factory()->count(3)->create(['location_id' => $location1->id]);
        Employee::factory()->count(2)->create(['location_id' => $location2->id]);

        $result = $this->employeeRepository->getEmployeesByLocation($location1->id);

        $this->assertEquals(3, $result->count());
        
        foreach ($result as $employee) {
            $this->assertEquals($location1->id, $employee->location_id);
        }
    }

    public function test_get_employees_by_department(): void
    {
        Employee::factory()->count(4)->create(['department' => 'IT']);
        Employee::factory()->count(2)->create(['department' => 'HR']);

        $result = $this->employeeRepository->getEmployeesByDepartment('IT');

        $this->assertEquals(4, $result->count());
        
        foreach ($result as $employee) {
            $this->assertEquals('IT', $employee->department);
        }
    }

    public function test_get_employees_statistics(): void
    {
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();

        Employee::factory()->count(3)->create([
            'is_active' => true,
            'location_id' => $location1->id,
            'department' => 'IT',
        ]);

        Employee::factory()->count(2)->create([
            'is_active' => true,
            'location_id' => $location2->id,
            'department' => 'HR',
        ]);

        Employee::factory()->create([
            'is_active' => false,
            'location_id' => $location1->id,
            'department' => 'IT',
        ]);

        $stats = $this->employeeRepository->getEmployeesStatistics();

        $this->assertEquals(6, $stats['total_employees']);
        $this->assertEquals(5, $stats['active_employees']);
        $this->assertEquals(1, $stats['inactive_employees']);

        // Check location breakdown
        $this->assertArrayHasKey('by_location', $stats);
        $this->assertEquals(4, $stats['by_location'][$location1->id]); // 3 active + 1 inactive
        $this->assertEquals(2, $stats['by_location'][$location2->id]);

        // Check department breakdown
        $this->assertArrayHasKey('by_department', $stats);
        $this->assertEquals(4, $stats['by_department']['IT']); // 3 active + 1 inactive
        $this->assertEquals(2, $stats['by_department']['HR']);
    }

    public function test_get_employees_with_upcoming_birthdays(): void
    {
        $today = now();
        
        // Employee with birthday tomorrow
        Employee::factory()->create([
            'date_of_birth' => $today->copy()->addDay()->subYears(25),
        ]);

        // Employee with birthday in 5 days
        Employee::factory()->create([
            'date_of_birth' => $today->copy()->addDays(5)->subYears(30),
        ]);

        // Employee with birthday in 2 months (should not be included)
        Employee::factory()->create([
            'date_of_birth' => $today->copy()->addMonths(2)->subYears(28),
        ]);

        $result = $this->employeeRepository->getEmployeesWithUpcomingBirthdays(7); // 7 days

        $this->assertEquals(2, $result->count());
    }

    public function test_search_employees(): void
    {
        Employee::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'employee_id' => 'EMP001',
            'email' => 'john.doe@example.com',
        ]);

        Employee::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'employee_id' => 'EMP002',
            'email' => 'jane.smith@example.com',
        ]);

        // Search by first name
        $result = $this->employeeRepository->searchEmployees('John');
        $this->assertEquals(1, $result->count());

        // Search by last name
        $result = $this->employeeRepository->searchEmployees('Smith');
        $this->assertEquals(1, $result->count());

        // Search by employee ID
        $result = $this->employeeRepository->searchEmployees('EMP001');
        $this->assertEquals(1, $result->count());

        // Search by email
        $result = $this->employeeRepository->searchEmployees('jane.smith');
        $this->assertEquals(1, $result->count());

        // Search with no results
        $result = $this->employeeRepository->searchEmployees('NonExistent');
        $this->assertEquals(0, $result->count());
    }

    public function test_bulk_update_status(): void
    {
        $employees = Employee::factory()->count(5)->create(['is_active' => true]);
        $employeeIds = $employees->pluck('id')->toArray();

        $updated = $this->employeeRepository->bulkUpdateStatus($employeeIds, false);

        $this->assertEquals(5, $updated);

        // Verify all employees are now inactive
        $inactiveCount = Employee::whereIn('id', $employeeIds)
            ->where('is_active', false)
            ->count();
        
        $this->assertEquals(5, $inactiveCount);
    }

    public function test_get_employees_with_relations(): void
    {
        $user = User::factory()->create();
        $location = Location::factory()->create();
        
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'location_id' => $location->id,
        ]);

        $result = $this->employeeRepository->getEmployeesWithRelations(['user', 'location']);

        $employee = $result->first();
        
        // Check that relations are loaded
        $this->assertTrue($employee->relationLoaded('user'));
        $this->assertTrue($employee->relationLoaded('location'));
        $this->assertInstanceOf(User::class, $employee->user);
        $this->assertInstanceOf(Location::class, $employee->location);
    }
}