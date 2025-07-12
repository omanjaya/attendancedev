<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Repository Interfaces
use App\Repositories\Interfaces\EmployeeRepositoryInterface;
use App\Repositories\Interfaces\AttendanceRepositoryInterface;
use App\Repositories\Interfaces\LeaveRepositoryInterface;

// Repository Implementations
use App\Repositories\EmployeeRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\LeaveRepository;

/**
 * Repository Service Provider
 * 
 * Binds repository interfaces to their concrete implementations.
 * This enables dependency injection and makes testing easier.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Repository bindings.
     *
     * @var array
     */
    public array $bindings = [
        EmployeeRepositoryInterface::class => EmployeeRepository::class,
        AttendanceRepositoryInterface::class => AttendanceRepository::class,
        LeaveRepositoryInterface::class => LeaveRepository::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Repositories are automatically bound through the $bindings property
        // but we can also manually bind complex services here
        
        // Example of manual binding with closure for complex instantiation:
        /*
        $this->app->bind(EmployeeRepositoryInterface::class, function ($app) {
            return new EmployeeRepository(
                $app->make(SomeService::class)
            );
        });
        */
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
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            EmployeeRepositoryInterface::class,
            AttendanceRepositoryInterface::class,
            LeaveRepositoryInterface::class,
        ];
    }
}