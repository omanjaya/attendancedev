<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\DeviceService;
use App\Services\SecurityNotificationService;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    private TwoFactorService $twoFactorService;

    private DeviceService $deviceService;

    private SecurityNotificationService $notificationService;

    public function __construct(
        TwoFactorService $twoFactorService,
        DeviceService $deviceService,
        SecurityNotificationService $notificationService,
    ) {
        $this->twoFactorService = $twoFactorService;
        $this->deviceService = $deviceService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('pages.auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // Update last login information
        $user->updateLastLogin($request->ip());

        // Track device
        $device = $this->deviceService->trackDevice($user, $request);
        $isNewDevice = $this->deviceService->isNewDevice($user, $request);

        // Send new device notification if this is a new device
        if ($isNewDevice) {
            $this->notificationService->notifyNewDeviceLogin($user, $device, $request);
        }

        $request->session()->regenerate();

        // Check if user has 2FA enabled
        if ($user->two_factor_enabled) {
            // Check if device is trusted and not a new device
            if ($device->is_trusted && ! $isNewDevice) {
                // Trusted device - skip 2FA
                return redirect()->intended(route('dashboard', absolute: false));
            }

            // Store login session but don't mark as fully authenticated yet
            session(['login_verified' => $user->id]);
            session(['2fa_device_id' => $device->id]);

            // Store intended URL for after 2FA verification
            $intended = $request->input('intended', route('dashboard'));
            session(['intended' => $intended]);

            // Redirect to 2FA verification
            return redirect()->route('2fa.verify');
        }

        // Check if 2FA is required for this user's role
        if ($this->twoFactorService->isRequiredForUser($user)) {
            // Store login session
            session(['login_verified' => $user->id]);

            // Store intended URL for after 2FA setup
            $intended = $request->input('intended', route('dashboard'));
            session(['intended' => $intended]);

            // Redirect to 2FA setup with warning
            return redirect()
                ->route('2fa.setup')
                ->with('warning', 'Two-factor authentication is required for your account.');
        }

        // No 2FA required, proceed to dashboard
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Clear any impersonation session first
        if ($request->session()->has('impersonated_by')) {
            $request->session()->forget('impersonated_by');
        }

        // Clear any role switching session
        if ($request->session()->has('original_role')) {
            $request->session()->forget('original_role');
        }

        // Clear any 2FA related sessions
        $request->session()->forget(['login_verified', '2fa_device_id', 'intended']);

        // Logout the user
        Auth::guard('web')->logout();

        // Invalidate the entire session
        $request->session()->invalidate();

        // Regenerate the CSRF token
        $request->session()->regenerateToken();

        // Clear remember me cookie if exists
        if ($request->cookies->has(Auth::getRecallerName())) {
            $cookie = cookie()->forget(Auth::getRecallerName());

            return redirect('/')->withCookie($cookie);
        }

        // For AJAX requests, return JSON response
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'redirect' => route('login')]);
        }

        return redirect('/');
    }
}
