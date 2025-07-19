<?php

namespace App\Providers;

use App\View\Composers\NavigationComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configurePersistentAuth();
        $this->configureViewComposers();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Error tracking rate limiter
        RateLimiter::for('error-tracking', function (Request $request) {
            // Allow 50 errors per minute per IP address
            // This prevents spam but allows legitimate error reporting
            return Limit::perMinute(50)->by($request->ip());
        });
    }

    /**
     * Configure persistent authentication settings.
     */
    protected function configurePersistentAuth(): void
    {
        // Extended session and remember me configuration is handled via:
        // - config/auth.php: 'guards.web.remember' => 525600 (365 days)
        // - config/session.php: 'lifetime' => 43200 (30 days)
        // - .env: SESSION_LIFETIME=43200, SESSION_EXPIRE_ON_CLOSE=false

        // The configuration is already set in the config files
        // This method is kept for future enhancements
    }

    /**
     * Configure view composers for shared data
     */
    protected function configureViewComposers(): void
    {
        View::composer([
            'layouts.authenticated',
            'layouts.authenticated-unified',
            'components.navigation.*',
            'partials.navigation.*',
        ], NavigationComposer::class);
    }
}
