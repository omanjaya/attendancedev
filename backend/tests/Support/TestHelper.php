<?php

namespace Tests\Support;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class TestHelper
{
    /**
     * Create a complete employee with user and all necessary relationships.
     */
    public static function createCompleteEmployee(array $overrides = []): array
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $location = Location::factory()->create();

        $employee = Employee::factory()->create(
            array_merge(
                [
                    'user_id' => $user->id,
                    'location_id' => $location->id,
                    'is_active' => true,
                ],
                $overrides,
            ),
        );

        return [
            'user' => $user,
            'employee' => $employee,
            'location' => $location,
        ];
    }

    /**
     * Create attendance records for testing scenarios.
     */
    public static function createAttendanceScenario(Employee $employee, array $config = []): array
    {
        $config = array_merge(
            [
                'days' => 5,
                'start_date' => Carbon::now()->startOfWeek(),
                'hours_per_day' => 8,
                'include_overtime' => false,
            ],
            $config,
        );

        $attendances = [];
        $currentDate = $config['start_date'];

        for ($i = 0; $i < $config['days']; $i++) {
            $hours = $config['hours_per_day'];

            if ($config['include_overtime'] && $i === $config['days'] - 1) {
                $hours += 2; // Add 2 hours overtime on last day
            }

            $attendance = Attendance::factory()->create([
                'employee_id' => $employee->id,
                'date' => $currentDate->copy(),
                'check_in_time' => $currentDate->copy()->setTime(9, 0),
                'check_out_time' => $currentDate->copy()->setTime(9 + $hours, 0),
                'total_hours' => $hours,
                'status' => 'present',
            ]);

            $attendances[] = $attendance;
            $currentDate->addDay();
        }

        return $attendances;
    }

    /**
     * Create leave scenario for testing.
     */
    public static function createLeaveScenario(Employee $employee, array $config = []): array
    {
        $config = array_merge(
            [
                'leave_days' => 3,
                'start_date' => Carbon::now()->addDays(7),
                'status' => 'approved',
                'is_paid' => true,
            ],
            $config,
        );

        $leaveType = LeaveType::factory()->create([
            'name' => 'Annual Leave',
            'is_paid' => $config['is_paid'],
        ]);

        $leave = Leave::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $config['start_date'],
            'end_date' => $config['start_date']->copy()->addDays($config['leave_days'] - 1),
            'total_days' => $config['leave_days'],
            'status' => $config['status'],
        ]);

        return [
            'leave' => $leave,
            'leave_type' => $leaveType,
        ];
    }

    /**
     * Create payroll scenario with items.
     */
    public static function createPayrollScenario(Employee $employee, array $config = []): array
    {
        $config = array_merge(
            [
                'period_start' => Carbon::now()->startOfMonth(),
                'period_end' => Carbon::now()->endOfMonth(),
                'basic_salary' => 5000,
                'include_overtime' => false,
                'include_bonus' => false,
                'include_deductions' => false,
            ],
            $config,
        );

        $payroll = Payroll::factory()->create([
            'employee_id' => $employee->id,
            'payroll_period_start' => $config['period_start'],
            'payroll_period_end' => $config['period_end'],
            'status' => 'draft',
        ]);

        $items = [];

        // Basic salary
        $items['basic_salary'] = PayrollItem::factory()->create([
            'payroll_id' => $payroll->id,
            'type' => 'earning',
            'category' => 'basic_salary',
            'description' => 'Basic Salary',
            'amount' => $config['basic_salary'],
        ]);

        // Overtime
        if ($config['include_overtime']) {
            $items['overtime'] = PayrollItem::factory()->create([
                'payroll_id' => $payroll->id,
                'type' => 'earning',
                'category' => 'overtime',
                'description' => 'Overtime Pay',
                'amount' => 500,
                'quantity' => 10,
                'rate' => 50,
            ]);
        }

        // Bonus
        if ($config['include_bonus']) {
            $items['bonus'] = PayrollItem::factory()->create([
                'payroll_id' => $payroll->id,
                'type' => 'bonus',
                'category' => 'bonus',
                'description' => 'Performance Bonus',
                'amount' => 1000,
            ]);
        }

        // Deductions
        if ($config['include_deductions']) {
            $items['tax'] = PayrollItem::factory()->create([
                'payroll_id' => $payroll->id,
                'type' => 'deduction',
                'category' => 'tax',
                'description' => 'Income Tax',
                'amount' => 750,
            ]);
        }

        // Recalculate totals
        $payroll->recalculateTotals();

        return [
            'payroll' => $payroll,
            'items' => $items,
        ];
    }

    /**
     * Create security scenario for testing.
     */
    public static function createSecurityScenario(User $user, array $config = []): array
    {
        $config = array_merge(
            [
                'failed_attempts' => 3,
                'is_locked' => false,
                'has_2fa' => false,
                'trusted_devices' => [],
            ],
            $config,
        );

        $user->update([
            'failed_login_attempts' => $config['failed_attempts'],
            'is_locked' => $config['is_locked'],
            'locked_until' => $config['is_locked'] ? Carbon::now()->addHours(1) : null,
            'two_factor_secret' => $config['has_2fa'] ? 'test_secret' : null,
            'trusted_devices' => $config['trusted_devices'],
        ]);

        return [
            'user' => $user->fresh(),
        ];
    }

    /**
     * Generate test face data for face recognition testing.
     */
    public static function generateTestFaceData(): array
    {
        return [
            'embedding' => array_fill(0, 128, rand(0, 100) / 100), // Mock face embedding
            'confidence' => 0.95,
            'landmarks' => [
                'left_eye' => [100, 120],
                'right_eye' => [180, 120],
                'nose' => [140, 150],
                'mouth' => [140, 180],
            ],
            'bounding_box' => [80, 100, 120, 150],
        ];
    }

    /**
     * Generate test location data for GPS testing.
     */
    public static function generateTestLocationData(bool $withinRadius = true): array
    {
        $baseLatitude = -6.2088; // Jakarta
        $baseLongitude = 106.8456;

        if (! $withinRadius) {
            // Generate coordinates far from base location
            $baseLatitude += 0.1; // ~11km away
            $baseLongitude += 0.1;
        }

        return [
            'latitude' => $baseLatitude + rand(-100, 100) / 100000, // Small random variation
            'longitude' => $baseLongitude + rand(-100, 100) / 100000,
            'accuracy' => rand(5, 20),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Create test API headers.
     */
    public static function getApiHeaders(array $additional = []): array
    {
        return array_merge(
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ],
            $additional,
        );
    }

    /**
     * Assert that an audit log entry exists for an action.
     */
    public static function assertAuditLogExists(array $criteria): void
    {
        $auditLog = \App\Models\AuditLog::where($criteria)->first();

        if (! $auditLog) {
            throw new \Exception('Expected audit log entry not found: '.json_encode($criteria));
        }
    }

    /**
     * Create test notification data.
     */
    public static function createTestNotification(User $user, array $config = []): array
    {
        $config = array_merge(
            [
                'type' => 'security_alert',
                'title' => 'Security Alert',
                'message' => 'A security event has been detected',
                'data' => [],
            ],
            $config,
        );

        $notification = $user->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => $config['type'],
            'data' => array_merge(
                [
                    'title' => $config['title'],
                    'message' => $config['message'],
                ],
                $config['data'],
            ),
            'read_at' => null,
        ]);

        return [
            'notification' => $notification,
        ];
    }

    /**
     * Mock external services for testing.
     */
    public static function mockExternalServices(): void
    {
        // Mock face detection service
        app()->instance(
            \App\Services\FaceDetectionService::class,
            new class
            {
                public function verify($faceData, $storedEmbedding)
                {
                    return true;
                }

                public function register($faceData)
                {
                    return true;
                }

                public function extractFeatures($imageData)
                {
                    return TestHelper::generateTestFaceData();
                }
            },
        );

        // Mock location service
        app()->instance(
            \App\Services\LocationService::class,
            new class
            {
                public function isWithinRadius($userCoords, $allowedCoords, $radius)
                {
                    return true;
                }

                public function validateCoordinates($latitude, $longitude)
                {
                    return true;
                }

                public function calculateDistance($coords1, $coords2)
                {
                    return 0.5;
                }
            },
        );
    }

    /**
     * Clean up test data and reset state.
     */
    public static function cleanup(): void
    {
        // Clear any cached data
        cache()->flush();

        // Reset any global state
        Carbon::setTestNow();

        // Clear any mocked services
        app()->forgetInstance(\App\Services\FaceDetectionService::class);
        app()->forgetInstance(\App\Services\LocationService::class);
    }

    /**
     * Set up time-based testing.
     */
    public static function freezeTime(?Carbon $time = null): Carbon
    {
        $frozenTime = $time ?: Carbon::now();
        Carbon::setTestNow($frozenTime);

        return $frozenTime;
    }

    /**
     * Create performance test data.
     */
    public static function createPerformanceTestData(int $count = 1000): array
    {
        $locations = Location::factory()->count(5)->create();
        $users = User::factory()->count($count)->create();

        $employees = [];
        foreach ($users as $user) {
            $employees[] = Employee::factory()->create([
                'user_id' => $user->id,
                'location_id' => $locations->random()->id,
            ]);
        }

        return [
            'users' => $users,
            'employees' => collect($employees),
            'locations' => $locations,
        ];
    }
}
