<?php

namespace Tests\Browser;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FaceRecognitionBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $user;

    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = Employee::factory()->create([
            'metadata' => [],
        ]);

        $this->user = User::factory()->create([
            'employee_id' => $this->employee->id,
            'email_verified_at' => now(),
        ]);

        // Give necessary permissions
        $this->user->givePermissionTo([
            'manage_employees',
            'view_employees',
            'manage_own_attendance',
        ]);
    }

    /**
     * Test face recognition camera initialization
     */
    public function test_face_recognition_camera_initialization()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/attendance')
                ->waitFor('#face-detection-container', 10)
                ->assertSee('Face Recognition')
                ->click('#start-camera-btn')
                ->waitFor('#camera-video', 10)
                ->assertVisible('#camera-video')
                ->assertVisible('#camera-canvas');
        });
    }

    /**
     * Test face registration workflow
     */
    public function test_face_registration_workflow()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/employees')
                ->clickLink($this->employee->full_name)
                ->waitForText('Register Face')
                ->click('#register-face-btn')
                ->waitFor('#face-registration-modal')
                ->assertSee('Face Registration')
                ->click('#start-face-capture')
                ->waitFor('#face-preview', 10)
                ->pause(3000) // Allow time for face detection
                ->whenAvailable('#face-detected-indicator', function ($modal) {
                    $modal->assertSee('Face Detected')
                        ->click('#confirm-registration');
                })
                ->waitForText('Face registered successfully')
                ->assertSee('Face registered successfully');
        });
    }

    /**
     * Test attendance check-in with face recognition
     */
    public function test_attendance_checkin_with_face_recognition()
    {
        // First register a face
        $this->employee->update([
            'metadata' => [
                'face_recognition' => [
                    'descriptor' => array_fill(0, 128, 0.5),
                    'confidence' => 0.85,
                    'registered_at' => now()->toISOString(),
                ],
            ],
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/attendance')
                ->waitFor('#check-in-section')
                ->click('#face-recognition-checkin')
                ->waitFor('#face-verification-modal')
                ->assertSee('Face Verification')
                ->click('#start-verification')
                ->waitFor('#verification-video', 10)
                ->pause(3000) // Allow time for face detection
                ->whenAvailable('#face-match-indicator', function ($modal) {
                    $modal->assertSee('Face Verified')
                        ->click('#confirm-checkin');
                })
                ->waitForText('Check-in successful')
                ->assertSee('Check-in successful');
        });
    }

    /**
     * Test liveness detection prompts
     */
    public function test_liveness_detection_prompts()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/attendance')
                ->click('#face-recognition-checkin')
                ->waitFor('#face-verification-modal')
                ->script([
                    'window.faceDetectionService.livenessDetection.isActive = true',
                ])
                ->click('#start-verification')
                ->waitFor('#liveness-prompts', 10)
                ->assertSeeIn('#liveness-prompts', 'Please blink')
                ->pause(2000)
                ->assertSeeIn('#liveness-prompts', 'Please smile')
                ->pause(2000)
                ->assertSeeIn('#liveness-prompts', 'Please nod');
        });
    }

    /**
     * Test face quality feedback
     */
    public function test_face_quality_feedback()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/employees')
                ->clickLink($this->employee->full_name)
                ->click('#register-face-btn')
                ->waitFor('#face-registration-modal')
                ->click('#start-face-capture')
                ->waitFor('#quality-indicators', 10)
                ->assertVisible('#lighting-indicator')
                ->assertVisible('#pose-indicator')
                ->assertVisible('#confidence-indicator')
                ->assertVisible('#blur-indicator');
        });
    }

    /**
     * Test camera permissions handling
     */
    public function test_camera_permissions_handling()
    {
        $this->browse(function (Browser $browser) {
            // This would require manual camera permission denial
            // In real testing, you might mock the getUserMedia API
            $browser->loginAs($this->user)
                ->visit('/attendance')
                ->click('#face-recognition-checkin')
                ->waitFor('#face-verification-modal')
                ->script([
                    'navigator.mediaDevices.getUserMedia = () => Promise.reject(new Error("Permission denied"))',
                ])
                ->click('#start-verification')
                ->waitForText('Camera access denied', 10)
                ->assertSee('Camera access denied')
                ->assertSee('Please allow camera access');
        });
    }

    /**
     * Test face recognition error handling
     */
    public function test_face_recognition_error_handling()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/attendance')
                ->click('#face-recognition-checkin')
                ->waitFor('#face-verification-modal')
                ->click('#start-verification')
                ->waitFor('#verification-video', 10)
                    // Simulate no face detected
                ->script([
                    'window.faceDetectionService.detectFaces = () => Promise.resolve([])',
                ])
                ->click('#verify-face-btn')
                ->waitForText('No face detected', 5)
                ->assertSee('No face detected')
                ->assertSee('Please ensure your face is clearly visible');
        });
    }

    /**
     * Test multiple faces detection
     */
    public function test_multiple_faces_detection()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/attendance')
                ->click('#face-recognition-checkin')
                ->waitFor('#face-verification-modal')
                ->click('#start-verification')
                ->waitFor('#verification-video', 10)
                    // Simulate multiple faces detected
                ->script([
                    'window.faceDetectionService.detectFaces = () => Promise.resolve([{}, {}])',
                ])
                ->click('#verify-face-btn')
                ->waitForText('Multiple faces detected', 5)
                ->assertSee('Multiple faces detected')
                ->assertSee('Please ensure only one person is in frame');
        });
    }

    /**
     * Test face recognition performance metrics
     */
    public function test_face_recognition_performance_metrics()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/reports/face-recognition')
                ->waitFor('#performance-metrics', 10)
                ->assertVisible('#success-rate-chart')
                ->assertVisible('#confidence-distribution')
                ->assertVisible('#hourly-usage-chart')
                ->assertSee('Face Recognition Analytics')
                ->assertSee('Success Rate')
                ->assertSee('Average Confidence')
                ->assertSee('Total Attempts');
        });
    }

    /**
     * Test face quality threshold settings
     */
    public function test_face_quality_threshold_settings()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/settings/face-recognition')
                ->waitFor('#quality-settings', 10)
                ->assertVisible('#confidence-threshold')
                ->assertVisible('#similarity-threshold')
                ->assertVisible('#liveness-threshold')
                ->type('#confidence-threshold', '0.8')
                ->type('#similarity-threshold', '0.7')
                ->type('#liveness-threshold', '0.9')
                ->click('#save-settings')
                ->waitForText('Settings saved successfully')
                ->assertSee('Settings saved successfully');
        });
    }

    /**
     * Test face recognition batch operations
     */
    public function test_face_recognition_batch_operations()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/employees/face-management')
                ->waitFor('#batch-operations', 10)
                ->check('#select-all-employees')
                ->click('#bulk-quality-check')
                ->waitFor('#batch-progress', 10)
                ->assertVisible('#progress-bar')
                ->waitForText('Batch operation completed', 30)
                ->assertSee('Quality check completed')
                ->assertSee('Low quality faces found');
        });
    }
}
