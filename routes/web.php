<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Main application routes including dashboard, profile, and core functionality.
| Domain-specific routes are organized in separate files for better maintainability.
|
*/

// Root redirect
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Dashboard routes
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Main Dashboard (modern floating version)
    Route::get('/dashboard', function () {
        return view('pages.dashboard');
    })->name('dashboard');
    
    // Profile Management (Laravel Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Settings redirect
    Route::get('/settings', function () {
        return redirect()->route('system.settings');
    })->name('settings');
    
    // Demo routes (development only - restrict in production)
    Route::prefix('demo')->middleware('permission:admin_access')->group(function () {
        Route::get('/notifications', function () {
            return view('demo.notifications');
        })->name('demo.notifications');
        
        Route::post('/notifications/test', function () {
            auth()->user()->notify(new \App\Notifications\TestNotification([
                'message' => 'This is a demo notification sent at ' . now()->format('H:i:s'),
                'type' => 'demo'
            ]));
            
            return response()->json([
                'message' => 'Demo notification sent successfully',
                'timestamp' => now()
            ]);
        })->name('demo.notifications.test');
        
        Route::get('/components', function () {
            return view('pages.demo.components');
        })->name('demo.components');
        
        Route::get('/mobile', function () {
            return view('pages.demo.mobile');
        })->name('demo.mobile');
        
        Route::get('/performance', function () {
            return view('pages.demo.performance');
        })->name('demo.performance');
    });
});

/*
|--------------------------------------------------------------------------
| Include Domain-Specific Route Files
|--------------------------------------------------------------------------
|
| Each domain has its own route file for better organization and maintainability
|
*/

// Include all domain-specific route files
require __DIR__.'/auth.php';           // Authentication routes (Laravel Breeze)
require __DIR__.'/security.php';       // Two-factor auth and security
require __DIR__.'/attendance.php';     // Attendance management
require __DIR__.'/employees.php';      // Employee management
require __DIR__.'/leave.php';          // Leave management
require __DIR__.'/schedules.php';      // Schedule management
require __DIR__.'/payroll.php';        // Payroll management
require __DIR__.'/system.php';         // System administration
require __DIR__.'/reports.php';        // Reports and analytics