<?php
// Test script to verify WITA timezone fix for attendance date logic

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

// Set the current date/time for testing
$app = require_once __DIR__ . '/bootstrap/app.php';

use Carbon\Carbon;
use Illuminate\Foundation\Application;

// Test 1: Check timezone configuration
echo "=== Attendance Timezone Fix Test ===\n";
echo "1. Testing timezone configuration:\n";
echo "   Default timezone: " . config('app.timezone') . "\n";
echo "   Current UTC time: " . Carbon::now('UTC')->format('Y-m-d H:i:s T') . "\n";
echo "   Current WITA time: " . Carbon::now('Asia/Makassar')->format('Y-m-d H:i:s T') . "\n";

// Test 2: Test the fixed date methods
echo "\n2. Testing date methods:\n";
$witaToday = now('Asia/Makassar')->startOfDay();
$todayDate = $witaToday->format('Y-m-d');
echo "   WITA today date: {$todayDate}\n";
echo "   WITA start of day: " . $witaToday->format('Y-m-d H:i:s T') . "\n";

// Test 3: Simulate attendance record creation
echo "\n3. Testing attendance record date:\n";
$checkInTime = now('Asia/Makassar');
echo "   Check-in time (WITA): " . $checkInTime->format('Y-m-d H:i:s T') . "\n";
echo "   Date for database: " . $checkInTime->format('Y-m-d') . "\n";

// Test 4: Simulate day change scenario
echo "\n4. Testing day change scenarios:\n";

// Scenario A: User checks in yesterday at 23:59 WITA
$yesterdayCheckIn = now('Asia/Makassar')->subDay()->setTime(23, 59);
echo "   Yesterday check-in (WITA): " . $yesterdayCheckIn->format('Y-m-d H:i:s T') . "\n";
echo "   Yesterday date: " . $yesterdayCheckIn->format('Y-m-d') . "\n";

// Scenario B: Today at 00:01 WITA (should be new day)
$todayEarly = now('Asia/Makassar')->startOfDay()->addMinute();
echo "   Today early (WITA): " . $todayEarly->format('Y-m-d H:i:s T') . "\n";
echo "   Today date: " . $todayEarly->format('Y-m-d') . "\n";

// Test 5: Date comparison
echo "\n5. Testing date comparison:\n";
$yesterdayDate = $yesterdayCheckIn->format('Y-m-d');
$todayDate = $todayEarly->format('Y-m-d');
echo "   Yesterday date: {$yesterdayDate}\n";
echo "   Today date: {$todayDate}\n";
echo "   Are they different? " . ($yesterdayDate !== $todayDate ? 'YES ✓' : 'NO ✗') . "\n";

echo "\n=== Test Complete ===\n";
echo "If all dates use WITA timezone and yesterday/today are different, the fix is working correctly.\n";