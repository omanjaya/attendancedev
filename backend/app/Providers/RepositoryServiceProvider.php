<?php

namespace App\Providers;

use App\Models\Attendance;
// Models
use App\Models\Employee;
use App\Models\User;
use App\Repositories\AttendanceRepository;
// Repository Implementations
use App\Repositories\EmployeeRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 *
 * Registers all repository bindings for dependency injection.
 * Provides clean separation of data access layer.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repositories with their model dependencies
        $this->app->bind(UserRepository::class, function ($app) {
            return new UserRepository($app->make(User::class));
        });

        $this->app->bind(EmployeeRepository::class, function ($app) {
            return new EmployeeRepository($app->make(Employee::class));
        });

        $this->app->bind(AttendanceRepository::class, function ($app) {
            return new AttendanceRepository($app->make(Attendance::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register any additional repository-related services here

        // Example: Register repository events or observers
        /*
        Employee::observe(EmployeeRepositoryObserver::class);
        */
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            UserRepository::class,
            EmployeeRepository::class,
            AttendanceRepository::class,
        ];
    }
}
