<?php

namespace Tests\Unit\Listeners;

use App\Events\AttendanceEvent;
use App\Events\SecurityEvent;
use App\Events\UserLoginEvent;
use App\Listeners\LogAuditEventListener;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LogAuditEventListenerTest extends TestCase
{
    use RefreshDatabase;

    private LogAuditEventListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = new LogAuditEventListener;

        // Use sync queue for testing
        Queue::fake();
    }

    public function test_handle_user_login_creates_audit_log(): void
    {
        $user = User::factory()->create();
        $event = new UserLoginEvent(
            user: $user,
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0 (Test Browser)',
            deviceFingerprint: 'device_123',
        );

        $this->listener->handleUserLogin($event);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => 'user_login',
            'action' => 'login',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (Test Browser)',
        ]);
    }

    public function test_handle_user_login_logs_to_file(): void
    {
        Log::shouldReceive('channel')->with('audit')->once()->andReturnSelf();

        Log::shouldReceive('log')->with('info', 'user_login', \Mockery::type('array'))->once();

        $user = User::factory()->create();
        $event = new UserLoginEvent(
            user: $user,
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0 (Test Browser)',
        );

        $this->listener->handleUserLogin($event);
    }

    public function test_handle_attendance_creates_audit_log(): void
    {
        $employee = Employee::factory()->create();
        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $event = new AttendanceEvent(
            employee: $employee,
            action: 'check_in',
            attendance: $attendance,
            locationData: ['verified' => true],
            faceData: ['verified' => true],
        );

        $this->listener->handleAttendance($event);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $employee->user_id,
            'event_type' => 'attendance_check_in',
            'action' => 'check_in',
            'model_type' => 'Attendance',
            'model_id' => $attendance->id,
        ]);
    }

    public function test_handle_attendance_with_high_risk_logs_warning(): void
    {
        Log::shouldReceive('warning')
            ->with('High-risk attendance event detected', \Mockery::type('array'))
            ->once();

        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('log')->andReturn(true);

        $employee = Employee::factory()->create();
        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $event = new AttendanceEvent(
            employee: $employee,
            action: 'check_in',
            attendance: $attendance,
            locationData: ['verified' => false], // High risk - location not verified
            faceData: ['verified' => false], // High risk - face not verified
        );

        $this->listener->handleAttendance($event);
    }

    public function test_handle_security_creates_audit_log(): void
    {
        $user = User::factory()->create();
        $event = new SecurityEvent(
            eventType: 'failed_login',
            user: $user,
            severity: 'medium',
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0 (Test Browser)',
            metadata: ['attempted_email' => $user->email],
        );

        $this->listener->handleSecurity($event);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => 'failed_login',
            'action' => 'login',
            'ip_address' => '192.168.1.1',
            'risk_level' => 'medium',
        ]);
    }

    public function test_handle_security_with_critical_event_logs_to_security_channel(): void
    {
        Log::shouldReceive('channel')->with('security')->once()->andReturnSelf();

        Log::shouldReceive('critical')
            ->with('Critical security event', \Mockery::type('array'))
            ->once();

        Log::shouldReceive('channel')->with('audit')->once()->andReturnSelf();

        Log::shouldReceive('log')->once()->andReturn(true);

        $user = User::factory()->create();
        $event = new SecurityEvent(
            eventType: 'data_breach_attempt',
            user: $user,
            severity: 'critical',
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0 (Test Browser)',
        );

        $this->listener->handleSecurity($event);
    }

    public function test_extract_action_from_event_type(): void
    {
        $reflection = new \ReflectionClass($this->listener);
        $method = $reflection->getMethod('extractAction');
        $method->setAccessible(true);

        $this->assertEquals('login', $method->invoke($this->listener, 'user_login'));
        $this->assertEquals('check_in', $method->invoke($this->listener, 'attendance_check_in'));
        $this->assertEquals('failed', $method->invoke($this->listener, 'login_failed'));
        $this->assertEquals('created', $method->invoke($this->listener, 'payroll_created'));
    }

    public function test_extract_model_type_from_event_data(): void
    {
        $reflection = new \ReflectionClass($this->listener);
        $method = $reflection->getMethod('extractModelType');
        $method->setAccessible(true);

        // Test with attendance_id
        $eventData = ['attendance_id' => '123'];
        $this->assertEquals('Attendance', $method->invoke($this->listener, $eventData));

        // Test with employee_id
        $eventData = ['employee_id' => '456'];
        $this->assertEquals('Employee', $method->invoke($this->listener, $eventData));

        // Test with user_id only
        $eventData = ['user_id' => '789'];
        $this->assertEquals('User', $method->invoke($this->listener, $eventData));

        // Test with no relevant IDs
        $eventData = ['other_field' => 'value'];
        $this->assertNull($method->invoke($this->listener, $eventData));
    }

    public function test_calculate_risk_level_for_security_events(): void
    {
        $reflection = new \ReflectionClass($this->listener);
        $method = $reflection->getMethod('calculateRiskLevel');
        $method->setAccessible(true);

        // Security event should use severity
        $eventData = ['event_type' => 'security_failed_login', 'severity' => 'high'];
        $this->assertEquals('high', $method->invoke($this->listener, $eventData));
    }

    public function test_calculate_risk_level_for_attendance_events(): void
    {
        $reflection = new \ReflectionClass($this->listener);
        $method = $reflection->getMethod('calculateRiskLevel');
        $method->setAccessible(true);

        // High risk - both location and face not verified
        $eventData = [
            'event_type' => 'attendance_check_in',
            'metadata' => [
                'location_verified' => false,
                'face_verified' => false,
            ],
        ];
        $this->assertEquals('high', $method->invoke($this->listener, $eventData));

        // Medium risk - one verification failed
        $eventData = [
            'event_type' => 'attendance_check_in',
            'metadata' => [
                'location_verified' => true,
                'face_verified' => false,
            ],
        ];
        $this->assertEquals('medium', $method->invoke($this->listener, $eventData));

        // Low risk - both verified
        $eventData = [
            'event_type' => 'attendance_check_in',
            'metadata' => [
                'location_verified' => true,
                'face_verified' => true,
            ],
        ];
        $this->assertEquals('low', $method->invoke($this->listener, $eventData));
    }

    public function test_calculate_risk_level_for_login_events(): void
    {
        $reflection = new \ReflectionClass($this->listener);
        $method = $reflection->getMethod('calculateRiskLevel');
        $method->setAccessible(true);

        // Medium risk - 2FA required
        $eventData = [
            'event_type' => 'user_login',
            'two_factor_required' => true,
        ];
        $this->assertEquals('medium', $method->invoke($this->listener, $eventData));

        // Low risk - normal login
        $eventData = [
            'event_type' => 'user_login',
            'two_factor_required' => false,
        ];
        $this->assertEquals('low', $method->invoke($this->listener, $eventData));
    }

    public function test_failed_job_handling(): void
    {
        Log::shouldReceive('error')->with('Audit logging failed', \Mockery::type('array'))->once();

        $user = User::factory()->create();
        $event = new UserLoginEvent(
            user: $user,
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0 (Test Browser)',
        );

        $exception = new \Exception('Database connection failed');

        $this->listener->failed($event, $exception);
    }

    public function test_log_event_fallback_on_database_failure(): void
    {
        // Mock database failure
        Log::shouldReceive('error')->with('Failed to log audit event', \Mockery::type('array'))->once();

        // Force a database error by using invalid data
        $eventData = [
            'user_id' => 'invalid-uuid-format', // This should cause a database error
            'event_type' => 'test_event',
            'action' => 'test',
            'ip_address' => '192.168.1.1',
        ];

        $reflection = new \ReflectionClass($this->listener);
        $method = $reflection->getMethod('logEvent');
        $method->setAccessible(true);

        // This should not throw an exception, but should log the error
        $method->invoke($this->listener, $eventData, 'info');
    }

    public function test_audit_log_contains_all_required_fields(): void
    {
        $user = User::factory()->create();
        $event = new UserLoginEvent(
            user: $user,
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0 (Test Browser)',
            deviceFingerprint: 'device_123',
            metadata: ['login_method' => 'password'],
        );

        $this->listener->handleUserLogin($event);

        $auditLog = AuditLog::where('user_id', $user->id)->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($user->id, $auditLog->user_id);
        $this->assertEquals('user_login', $auditLog->event_type);
        $this->assertEquals('login', $auditLog->action);
        $this->assertEquals('192.168.1.1', $auditLog->ip_address);
        $this->assertEquals('Mozilla/5.0 (Test Browser)', $auditLog->user_agent);
        $this->assertEquals('low', $auditLog->risk_level);
        $this->assertIsArray($auditLog->metadata);
        $this->assertArrayHasKey('device_fingerprint', $auditLog->metadata);
    }
}
