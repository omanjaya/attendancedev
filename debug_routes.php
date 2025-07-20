<?php
// Quick route debug script
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

try {
    $routes = $app->make('router')->getRoutes();
    
    echo "=== Employee Routes Debug ===\n\n";
    
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'employees') && str_contains($route->uri(), '{employee}')) {
            echo "Route: {$route->methods()[0]} {$route->uri()}\n";
            echo "Name: {$route->getName()}\n";
            echo "Action: {$route->getActionName()}\n";
            echo "Where constraints: " . json_encode($route->wheres) . "\n";
            echo "Middleware: " . implode(', ', $route->gatherMiddleware()) . "\n";
            echo "---\n";
        }
    }
    
    // Test UUID validation
    $testUuid = '01982188-1852-7009-a743-f87f8fd30a81';
    $pattern = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';
    
    echo "\n=== UUID Test ===\n";
    echo "Test UUID: {$testUuid}\n";
    echo "Pattern: {$pattern}\n";
    echo "Matches: " . (preg_match("/^{$pattern}$/", $testUuid) ? 'YES' : 'NO') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}