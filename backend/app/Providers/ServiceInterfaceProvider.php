<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ServiceInterfaceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array
     */
    public $bindings = [
        // Service Interface Bindings
        \App\Contracts\Services\AttendanceServiceInterface::class => \App\Services\AttendanceService::class,
        \App\Contracts\Services\FaceRecognitionServiceInterface::class => \App\Services\FaceRecognitionService::class,
        \App\Contracts\Services\PayrollServiceInterface::class => \App\Services\OptimizedPayrollService::class,
        \App\Contracts\Services\NotificationServiceInterface::class => \App\Services\NotificationService::class,
    ];

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
        //
    }
}