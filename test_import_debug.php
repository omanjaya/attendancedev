<?php
// Quick debug script to test the import endpoint

// Check if we can access the Laravel app
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "Laravel not found\n";
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Check route registration
try {
    $routes = $app->make('router')->getRoutes();
    
    echo "Looking for preview-import route...\n";
    
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'preview-import')) {
            echo "Found route: {$route->methods()[0]} {$route->uri()}\n";
            echo "Name: {$route->getName()}\n";
            echo "Action: {$route->getActionName()}\n";
            echo "Middleware: " . implode(', ', $route->gatherMiddleware()) . "\n";
        }
    }
    
    // Check if the controller exists
    if (class_exists('App\Http\Controllers\EmployeeController')) {
        echo "\nEmployeeController exists\n";
        
        $controller = new App\Http\Controllers\EmployeeController(
            app('App\Services\EmployeeService'),
            app('App\Services\ExcelTemplateService')
        );
        
        if (method_exists($controller, 'previewImport')) {
            echo "previewImport method exists\n";
        } else {
            echo "previewImport method NOT found\n";
        }
    } else {
        echo "\nEmployeeController NOT found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}