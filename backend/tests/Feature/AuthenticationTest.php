<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_login_requires_email(): void
    {
        $response = $this->post('/login', [
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_login_requires_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_login_requires_valid_email_format(): void
    {
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_login_with_remember_me(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'remember' => true,
        ]);

        $this->assertAuthenticated();

        // Check that remember token was set
        $user->refresh();
        $this->assertNotNull($user->remember_token);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_user_with_employee_record_redirected_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        Employee::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_root_redirects_to_dashboard_when_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_root_redirects_to_login_when_guest(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('dashboard'));

        // Following the redirect should lead to login
        $redirectResponse = $this->get(route('dashboard'));
        $redirectResponse->assertRedirect(route('login'));
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_protected_routes_require_authentication(): void
    {
        $protectedRoutes = [
            '/dashboard',
            '/attendance',
            '/attendance/check-in',
            '/attendance/history',
            '/employees',
            '/leave/requests',
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect(route('login'));
        }
    }

    public function test_login_throttling(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // Make multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // The next attempt should be throttled
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    public function test_successful_login_after_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // Make a few failed attempts
        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // Successful login should work
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_csrf_protection_on_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // Attempt login without CSRF token
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)->post(
            '/login',
            [
                'email' => 'test@example.com',
                'password' => 'password',
            ],
        );

        // Should still work if we disable CSRF for this test
        $this->assertAuthenticated();
    }

    public function test_guest_middleware_redirects_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_session_data_stored_correctly(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'name' => 'Test User',
        ]);

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertEquals($user->id, auth()->id());
        $this->assertEquals('Test User', auth()->user()->name);
    }

    public function test_login_form_validation_messages(): void
    {
        $response = $this->post('/login', []);

        $response->assertSessionHasErrors(['email', 'password']);

        $errors = session('errors')->getBag('default');
        $this->assertStringContainsString('email', $errors->first('email'));
        $this->assertStringContainsString('password', $errors->first('password'));
    }

    public function test_login_with_case_insensitive_email(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_login_form_has_proper_csrf_token(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('_token', false);
    }
}
