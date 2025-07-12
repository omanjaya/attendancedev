<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        $this->clearApplicationCache();
        
        // Set up test-specific configurations
        $this->setUpTestConfiguration();
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        // Clear any test data
        $this->clearTestData();
        
        parent::tearDown();
    }

    /**
     * Clear application cache for testing.
     */
    private function clearApplicationCache(): void
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
        } catch (\Exception $e) {
            // Ignore cache clearing errors in tests
        }
    }

    /**
     * Set up test-specific configurations.
     */
    private function setUpTestConfiguration(): void
    {
        // Disable performance monitoring in tests
        config(['app.performance_monitoring' => false]);
        
        // Use array cache driver for faster tests
        config(['cache.default' => 'array']);
        
        // Use sync queue driver for immediate execution
        config(['queue.default' => 'sync']);
        
        // Disable mail sending in tests
        config(['mail.default' => 'array']);
        
        // Set test-specific database configuration
        config(['database.default' => 'testing']);
    }

    /**
     * Clear test data.
     */
    private function clearTestData(): void
    {
        // Clear any cached test data
        cache()->flush();
    }

    /**
     * Create a user and authenticate them.
     */
    protected function authenticateUser(array $attributes = []): \App\Models\User
    {
        $user = \App\Models\User::factory()->create($attributes);
        $this->actingAs($user);
        return $user;
    }

    /**
     * Create a user with employee record and authenticate them.
     */
    protected function authenticateEmployee(array $userAttributes = [], array $employeeAttributes = []): array
    {
        $user = \App\Models\User::factory()->create($userAttributes);
        $employee = \App\Models\Employee::factory()->create(
            array_merge(['user_id' => $user->id], $employeeAttributes)
        );
        
        $this->actingAs($user);
        
        return ['user' => $user, 'employee' => $employee];
    }

    /**
     * Create a user with specific permissions.
     */
    protected function authenticateUserWithPermissions(array $permissions, array $attributes = []): \App\Models\User
    {
        $user = $this->authenticateUser($attributes);
        
        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }
        
        return $user;
    }

    /**
     * Create a user with a specific role.
     */
    protected function authenticateUserWithRole(string $roleName, array $attributes = []): \App\Models\User
    {
        $user = $this->authenticateUser($attributes);
        
        // Create role if it doesn't exist
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName]);
        $user->assignRole($role);
        
        return $user;
    }

    /**
     * Assert that a database table has a specific record count.
     */
    protected function assertTableRecordCount(string $table, int $count): void
    {
        $actual = $this->app['db']->table($table)->count();
        $this->assertEquals($count, $actual, "Expected {$count} records in table '{$table}', but found {$actual}.");
    }

    /**
     * Assert that a model has specific attribute values.
     */
    protected function assertModelAttributes($model, array $attributes): void
    {
        foreach ($attributes as $attribute => $expectedValue) {
            $actualValue = $model->getAttribute($attribute);
            $this->assertEquals(
                $expectedValue, 
                $actualValue, 
                "Expected model attribute '{$attribute}' to be '{$expectedValue}', but got '{$actualValue}'."
            );
        }
    }

    /**
     * Mock external services for testing.
     */
    protected function mockExternalServices(): void
    {
        // Mock face detection service
        $this->mock(\App\Services\FaceDetectionService::class, function ($mock) {
            $mock->shouldReceive('verify')->andReturn(true);
            $mock->shouldReceive('register')->andReturn(true);
        });

        // Mock location service
        $this->mock(\App\Services\LocationService::class, function ($mock) {
            $mock->shouldReceive('isWithinRadius')->andReturn(true);
            $mock->shouldReceive('validateCoordinates')->andReturn(true);
        });
    }

    /**
     * Create test data for attendance scenarios.
     */
    protected function createAttendanceTestData(): array
    {
        $user = \App\Models\User::factory()->create();
        $employee = \App\Models\Employee::factory()->create(['user_id' => $user->id]);
        $location = \App\Models\Location::factory()->create();
        
        return [
            'user' => $user,
            'employee' => $employee,
            'location' => $location,
        ];
    }

    /**
     * Create test data for leave management scenarios.
     */
    protected function createLeaveTestData(): array
    {
        $user = \App\Models\User::factory()->create();
        $employee = \App\Models\Employee::factory()->create(['user_id' => $user->id]);
        $leaveType = \App\Models\LeaveType::factory()->create();
        $leaveBalance = \App\Models\LeaveBalance::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);
        
        return [
            'user' => $user,
            'employee' => $employee,
            'leaveType' => $leaveType,
            'leaveBalance' => $leaveBalance,
        ];
    }

    /**
     * Create a test API request with proper headers.
     */
    protected function apiRequest(string $method, string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        $defaultHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        
        $headers = array_merge($defaultHeaders, $headers);
        
        return $this->json($method, $uri, $data, $headers);
    }

    /**
     * Assert that a specific permission is required for a route.
     */
    protected function assertRequiresPermission(string $route, string $permission, string $method = 'GET', array $data = []): void
    {
        // Test without permission
        $user = $this->authenticateUser();
        
        $response = $this->json($method, $route, $data);
        $response->assertStatus(403);
        
        // Test with permission
        $user->givePermissionTo($permission);
        
        $response = $this->json($method, $route, $data);
        $response->assertStatus(200);
    }
}
