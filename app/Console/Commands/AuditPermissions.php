<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class AuditPermissions extends Command
{
    protected $signature = 'permission:audit';

    protected $description = 'Audit routes for missing permission checks';

    public function handle()
    {
        $routes = Route::getRoutes();
        $unprotectedRoutes = [];
        $protectedRoutes = [];

        foreach ($routes as $route) {
            $middleware = $route->getAction('middleware') ?? [];

            // Skip public routes
            if ($this->isPublicRoute($route)) {
                continue;
            }

            $hasAuth = collect($middleware)->contains(fn ($m) => str_contains($m, 'auth'));
            $hasPermission = collect($middleware)->contains(fn ($m) => str_contains($m, 'permission'));

            if ($hasAuth && ! $hasPermission) {
                $unprotectedRoutes[] = [
                    'method' => implode('|', $route->methods()),
                    'uri' => $route->uri(),
                    'name' => $route->getName() ?? 'unnamed',
                    'action' => $route->getActionName(),
                ];
            } elseif ($hasAuth && $hasPermission) {
                $protectedRoutes[] = [
                    'method' => implode('|', $route->methods()),
                    'uri' => $route->uri(),
                    'name' => $route->getName() ?? 'unnamed',
                    'permission' => $this->extractPermission($middleware),
                ];
            }
        }

        $this->info('=== PERMISSION AUDIT RESULTS ===');
        $this->info('Protected routes: '.count($protectedRoutes));
        $this->info('Unprotected routes: '.count($unprotectedRoutes));

        if (! empty($unprotectedRoutes)) {
            $this->error("\n🚨 UNPROTECTED ROUTES (require permission middleware):");
            $this->table(['Method', 'URI', 'Name', 'Action'], $unprotectedRoutes);
        }

        if (! empty($protectedRoutes) && $this->option('verbose')) {
            $this->info("\n✅ PROTECTED ROUTES:");
            $this->table(['Method', 'URI', 'Name', 'Permission'], $protectedRoutes);
        }

        if (empty($unprotectedRoutes)) {
            $this->info('✅ All authenticated routes are properly protected!');
        } else {
            $this->warn(
                "\n⚠️  Found ".count($unprotectedRoutes).' routes that need permission checks!',
            );
        }

        return empty($unprotectedRoutes) ? 0 : 1;
    }

    private function isPublicRoute($route): bool
    {
        $publicPatterns = [
            'login',
            'register',
            'password',
            'verification',
            'up',
            'api/documentation',
            'sanctum/csrf-cookie',
        ];

        $uri = $route->uri();
        $name = $route->getName() ?? '';

        foreach ($publicPatterns as $pattern) {
            if (str_contains($uri, $pattern) || str_contains($name, $pattern)) {
                return true;
            }
        }

        // Skip routes without authentication
        $middleware = $route->getAction('middleware') ?? [];
        $hasAuth = collect($middleware)->contains(fn ($m) => str_contains($m, 'auth'));

        return ! $hasAuth;
    }

    private function extractPermission(array $middleware): string
    {
        foreach ($middleware as $m) {
            if (str_contains($m, 'permission:')) {
                return str_replace('permission:', '', $m);
            }
        }

        return 'unknown';
    }
}
