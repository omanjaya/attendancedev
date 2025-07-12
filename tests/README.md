# Test Documentation

This directory contains the comprehensive test suite for the Attendance Management System.

## Test Structure

```
tests/
├── Feature/                    # Feature/Integration tests
│   ├── Api/                   # API endpoint tests
│   │   └── EmployeeApiTest.php
│   ├── Auth/                  # Authentication flow tests
│   ├── AttendanceTest.php     # Attendance management tests
│   └── ...
├── Unit/                      # Unit tests
│   ├── Config/                # Configuration tests
│   │   └── PayrollConfigTest.php
│   ├── Events/                # Event tests
│   │   └── UserLoginEventTest.php
│   ├── Listeners/             # Event listener tests
│   │   └── LogAuditEventListenerTest.php
│   ├── Repositories/          # Repository tests
│   │   └── EmployeeRepositoryTest.php
│   ├── Services/              # Service layer tests
│   │   ├── PayrollCalculationServiceTest.php
│   │   └── UserSecurityServiceTest.php
│   └── EmployeeTest.php       # Model tests
├── Support/                   # Test utilities and helpers
│   └── TestHelper.php
├── CreatesApplication.php     # Laravel application creation
├── TestCase.php              # Base test case with utilities
└── README.md                 # This file
```

## Test Categories

### 1. Unit Tests (`tests/Unit/`)

Test individual classes and methods in isolation:

- **Models**: Test model relationships, attributes, scopes, and business logic
- **Services**: Test service classes with mocked dependencies
- **Repositories**: Test data access layer with database interactions
- **Events**: Test event creation and data structure
- **Listeners**: Test event handling and side effects
- **Config**: Test configuration file structure and validation

### 2. Feature Tests (`tests/Feature/`)

Test complete features and user workflows:

- **API Endpoints**: Test HTTP requests, responses, and authorization
- **Authentication**: Test login, registration, and security flows
- **Business Workflows**: Test complete user journeys

### 3. Support Classes (`tests/Support/`)

Utilities and helpers for testing:

- **TestHelper**: Factory methods for creating test data scenarios
- **Mock Services**: Mock external services for isolated testing

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Categories
```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature

# Specific test class
php artisan test tests/Unit/Services/PayrollCalculationServiceTest.php

# Specific test method
php artisan test --filter=test_calculate_payroll_creates_payroll_record
```

### Run Tests with Coverage
```bash
php artisan test --coverage
php artisan test --coverage-html coverage/
```

### Parallel Testing
```bash
php artisan test --parallel
```

## Test Database

Tests use a separate SQLite database for speed and isolation:

- Database: `:memory:` or `database/testing.sqlite`
- Migrations run automatically before tests
- Database is refreshed between test classes
- Transactions used for test isolation

## Test Utilities

### Base TestCase Features

The `TestCase` class provides utilities for all tests:

```php
// Authentication helpers
$user = $this->authenticateUser();
$user = $this->authenticateUserWithPermissions(['view_employees']);
$user = $this->authenticateUserWithRole('admin');

// Employee creation with relationships
$data = $this->authenticateEmployee($userAttrs, $employeeAttrs);

// API request helpers
$response = $this->apiRequest('GET', '/api/employees');

// Custom assertions
$this->assertTableRecordCount('employees', 5);
$this->assertModelAttributes($employee, ['name' => 'John']);
$this->assertRequiresPermission('/api/employees', 'view_employees');

// Mock external services
$this->mockExternalServices();

// Create test data scenarios
$data = $this->createAttendanceTestData();
$data = $this->createLeaveTestData();
```

### TestHelper Features

The `TestHelper` class provides advanced test data creation:

```php
// Complete employee with all relationships
$data = TestHelper::createCompleteEmployee(['department' => 'IT']);

// Attendance scenarios
$attendances = TestHelper::createAttendanceScenario($employee, [
    'days' => 5,
    'hours_per_day' => 8,
    'include_overtime' => true,
]);

// Leave scenarios
$data = TestHelper::createLeaveScenario($employee, [
    'leave_days' => 3,
    'is_paid' => true,
]);

// Payroll scenarios
$data = TestHelper::createPayrollScenario($employee, [
    'basic_salary' => 5000,
    'include_overtime' => true,
    'include_bonus' => true,
]);

// Security scenarios
$data = TestHelper::createSecurityScenario($user, [
    'failed_attempts' => 3,
    'has_2fa' => true,
]);

// Mock data
$faceData = TestHelper::generateTestFaceData();
$locationData = TestHelper::generateTestLocationData();

// Time-based testing
$frozenTime = TestHelper::freezeTime(Carbon::create(2024, 1, 15));

// Performance testing
$data = TestHelper::createPerformanceTestData(1000);
```

## Test Data Management

### Factories

Laravel factories are used for creating test data:

```php
// Basic usage
$user = User::factory()->create();
$employee = Employee::factory()->create(['is_active' => true]);

// With relationships
$employee = Employee::factory()
    ->for(User::factory(), 'user')
    ->for(Location::factory(), 'location')
    ->create();

// Multiple records
$employees = Employee::factory()->count(10)->create();
```

### Database Refresh

Tests use `RefreshDatabase` trait for isolation:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;
    
    // Database is reset before each test
}
```

## Testing Best Practices

### 1. Test Structure (AAA Pattern)

```php
public function test_calculate_payroll_creates_record(): void
{
    // Arrange
    $employee = Employee::factory()->create(['salary_amount' => 5000]);
    $service = new PayrollCalculationService();
    
    // Act
    $payroll = $service->calculatePayroll($employee, now()->startOfMonth(), now()->endOfMonth());
    
    // Assert
    $this->assertInstanceOf(Payroll::class, $payroll);
    $this->assertEquals(5000, $payroll->gross_salary);
}
```

### 2. Test Naming

- Use descriptive names: `test_calculate_payroll_with_overtime_includes_overtime_pay`
- Start with `test_` prefix
- Use snake_case for test methods

### 3. Mock External Dependencies

```php
// Mock external services
$this->mock(FaceDetectionService::class, function ($mock) {
    $mock->shouldReceive('verify')->andReturn(true);
});

// Mock facades
Queue::fake();
Mail::fake();
Event::fake();
```

### 4. Test Edge Cases

```php
// Test validation
public function test_create_employee_validates_required_fields(): void
{
    $response = $this->apiRequest('POST', '/api/employees', []);
    $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name']);
}

// Test error conditions
public function test_calculate_payroll_handles_invalid_employee(): void
{
    $this->expectException(ModelNotFoundException::class);
    $service->calculatePayroll('invalid-id', now(), now());
}
```

### 5. Database Assertions

```php
// Database state
$this->assertDatabaseHas('employees', ['first_name' => 'John']);
$this->assertDatabaseMissing('employees', ['is_active' => false]);
$this->assertSoftDeleted('employees', ['id' => $employee->id]);

// Record counts
$this->assertDatabaseCount('payrolls', 1);
```

### 6. API Testing

```php
// Response structure
$response->assertJsonStructure([
    'data' => [
        'id',
        'first_name',
        'last_name',
    ],
    'meta' => ['total', 'per_page'],
]);

// Response content
$response->assertJson(['data' => ['first_name' => 'John']]);
$response->assertJsonPath('data.first_name', 'John');
```

## Configuration Testing

Test configuration files to ensure they're properly structured:

```php
public function test_payroll_config_has_required_sections(): void
{
    $config = Config::get('payroll');
    
    $this->assertArrayHasKey('calculations', $config);
    $this->assertArrayHasKey('tax', $config);
    $this->assertArrayHasKey('bonuses', $config);
}
```

## Performance Testing

For performance-critical code:

```php
public function test_large_payroll_calculation_performance(): void
{
    $employees = Employee::factory()->count(1000)->create();
    
    $startTime = microtime(true);
    $service->calculatePayrollForEmployees($employees, now(), now());
    $executionTime = microtime(true) - $startTime;
    
    $this->assertLessThan(10, $executionTime); // Should complete within 10 seconds
}
```

## Security Testing

Test security features and access controls:

```php
public function test_api_requires_authentication(): void
{
    $response = $this->apiRequest('GET', '/api/employees');
    $response->assertStatus(401);
}

public function test_api_requires_proper_permissions(): void
{
    $user = $this->authenticateUser();
    $response = $this->apiRequest('GET', '/api/employees');
    $response->assertStatus(403);
}
```

## Continuous Integration

Tests are run automatically on:

- Pull requests
- Main branch commits
- Scheduled daily runs

### GitHub Actions Configuration

```yaml
# .github/workflows/tests.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: php artisan test --coverage
```

## Coverage Requirements

- Minimum overall coverage: 80%
- Critical services coverage: 90%
- New code coverage: 100%

## Troubleshooting

### Common Issues

1. **Database errors**: Ensure test database is properly configured
2. **Permission errors**: Check that roles and permissions are seeded
3. **Mock failures**: Verify mocks are set up before they're used
4. **Timing issues**: Use `Carbon::setTestNow()` for time-dependent tests

### Debug Tests

```bash
# Run with detailed output
php artisan test --verbose

# Debug specific test
php artisan test --filter=test_name --debug

# Stop on first failure
php artisan test --stop-on-failure
```

## Contributing

When adding new features:

1. Write tests first (TDD approach)
2. Ensure all tests pass
3. Add appropriate test coverage
4. Update this documentation if needed

For questions about testing, refer to the Laravel Testing documentation or ask the development team.