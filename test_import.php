<?php

require_once 'bootstrap/app.php';

use App\Services\EmployeeService;
use App\Services\EmployeeIdGeneratorService;
use App\Services\ExcelTemplateService;

// Create test data
$testData = [
    [
        'full_name' => 'Test User 1',
        'email' => 'test1@example.com',
        'phone' => '081234567890',
        'employee_type' => 'permanent',
        'role' => 'guru',
        'salary_type' => 'monthly',
        'salary_amount' => '5000000',
        'hourly_rate' => '',
        'hire_date' => '2024-01-15',
        'department' => 'SD Negeri 1',
        'position' => 'Guru Kelas',
        'status' => 'Aktif'
    ]
];

echo "Testing Employee ID Generator...\n";

$idGenerator = new EmployeeIdGeneratorService();
$id = $idGenerator->generateUniqueEmployeeId('guru', 'permanent');
echo "Generated ID: " . $id . "\n";

echo "\nTesting Employee Service Import...\n";

// Test mapImportData method
$employeeService = app(EmployeeService::class);

try {
    $reflection = new ReflectionClass($employeeService);
    $method = $reflection->getMethod('mapImportData');
    $method->setAccessible(true);
    
    $mappedData = $method->invoke($employeeService, $testData[0]);
    echo "Mapped data:\n";
    print_r($mappedData);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";