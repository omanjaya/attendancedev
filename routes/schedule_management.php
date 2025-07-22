<?php

use App\Http\Controllers\MonthlyScheduleController;
use App\Http\Controllers\TeachingScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Schedule Management Routes
|--------------------------------------------------------------------------
|
| Routes for the multi-layered schedule management system including
| monthly schedules, employee assignments, teaching schedules, and holidays.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Legacy redirects for old URLs
    Route::get('/schedules', function() {
        return redirect('/schedule-management');
    });
    Route::get('/schedules/monthly/create', function() {
        return redirect('/schedule-management/monthly/create');
    });
    Route::get('/schedules/teaching', function() {
        return redirect('/schedule-management/teaching');
    });
    Route::get('/schedules/holidays', function() {
        return redirect('/schedule-management/holidays');
    });
    Route::get('/schedules/assign', function() {
        return redirect('/schedule-management/assign');
    });
    Route::get('/schedules/dashboard', function() {
        return redirect('/schedule-management/dashboard');
    });
    
    // Legacy API redirects
    Route::get('/api/schedules/monthly/create', function() {
        return redirect('/api/schedule-management/monthly/create');
    });
    Route::post('/api/schedules/monthly', function() {
        return redirect('/api/schedule-management/monthly');
    });
    
    // Main Schedule Management Index
    Route::get('/schedule-management', function() {
        return view('pages.schedules.index');
    })->name('schedule-management.index');
    
    // Schedule Dashboard
    Route::get('/schedule-management/dashboard', function() {
        return view('pages.schedules.dashboard');
    })->name('schedule-management.dashboard');
    
    // Employee Assignment
    Route::get('/schedule-management/assign', function() {
        $employees = \App\Models\Employee::with('user')->active()->get();
        $schedules = \App\Models\MonthlySchedule::active()->get();
        return view('pages.schedules.assign', compact('employees', 'schedules'));
    })->name('schedule-management.assign');
    
    // Holiday Management - Redirect to main holidays controller
    Route::get('/schedule-management/holidays', function() {
        return redirect()->route('holidays.index');
    })->name('schedule-management.holidays.calendar');
    
    // Monthly Schedule Management
    Route::prefix('schedule-management/monthly')->name('schedule-management.monthly.')->group(function () {
        Route::get('/', [MonthlyScheduleController::class, 'index'])->name('index');
        Route::get('/create', [MonthlyScheduleController::class, 'create'])->name('create');
        Route::post('/', [MonthlyScheduleController::class, 'store'])->name('store');
        Route::get('/{monthlySchedule}', [MonthlyScheduleController::class, 'show'])->name('show');
        Route::get('/{monthlySchedule}/edit', [MonthlyScheduleController::class, 'edit'])->name('edit');
        Route::put('/{monthlySchedule}', [MonthlyScheduleController::class, 'update'])->name('update');
        Route::delete('/{monthlySchedule}', [MonthlyScheduleController::class, 'destroy'])->name('destroy');
        
        // Employee Assignment
        Route::post('/{monthlySchedule}/assign-employees', [MonthlyScheduleController::class, 'assignEmployees'])->name('assign-employees');
        Route::get('/{monthlySchedule}/available-employees', [MonthlyScheduleController::class, 'availableEmployees'])->name('available-employees');
        Route::post('/{monthlySchedule}/apply-holiday-overrides', [MonthlyScheduleController::class, 'applyHolidayOverrides'])->name('apply-holiday-overrides');
        
        // Overview and Statistics
        Route::get('/overview/monthly', [MonthlyScheduleController::class, 'monthlyOverview'])->name('overview');
    });
    
    // Teaching Schedule Management
    Route::prefix('schedule-management/teaching')->name('schedule-management.teaching.')->group(function () {
        Route::get('/', [TeachingScheduleController::class, 'index'])->name('index');
        Route::get('/create', [TeachingScheduleController::class, 'create'])->name('create');
        Route::post('/', [TeachingScheduleController::class, 'store'])->name('store');
        Route::get('/{teachingSchedule}', [TeachingScheduleController::class, 'show'])->name('show');
        Route::put('/{teachingSchedule}', [TeachingScheduleController::class, 'update'])->name('update');
        Route::delete('/{teachingSchedule}', [TeachingScheduleController::class, 'destroy'])->name('destroy');
        
        // Substitute Teacher Management
        Route::post('/{teachingSchedule}/assign-substitute', [TeachingScheduleController::class, 'assignSubstitute'])->name('assign-substitute');
        Route::delete('/{teachingSchedule}/remove-substitute', [TeachingScheduleController::class, 'removeSubstitute'])->name('remove-substitute');
        Route::get('/{teachingSchedule}/available-substitutes', [TeachingScheduleController::class, 'availableSubstitutes'])->name('available-substitutes');
        
        // Teacher Workload
        Route::get('/workload/summary', [TeachingScheduleController::class, 'teacherWorkload'])->name('workload');
    });
    
    // API Routes for Frontend Components
    Route::prefix('api/schedule-management')->name('api.schedule-management.')->group(function () {
        // Schedule Dashboard API
        Route::get('dashboard/stats', function() {
            try {
                return response()->json([
                    'total_employees' => \App\Models\Employee::count(),
                    'active_schedules' => \App\Models\MonthlySchedule::count(),
                    'today_holidays' => 0, // Simplified for now
                    'teaching_schedules' => 0 // Simplified for now
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'total_employees' => 0,
                    'active_schedules' => 0,
                    'today_holidays' => 0,
                    'teaching_schedules' => 0
                ]);
            }
        })->name('dashboard.stats');
        
        Route::get('dashboard/events', function() {
            $events = collect();
            
            // Add holidays
            $holidays = \App\Models\NationalHoliday::whereBetween('holiday_date', [
                request('start', now()->startOfMonth()),
                request('end', now()->endOfMonth())
            ])->get();
            
            $events = $events->concat($holidays->map(function($holiday) {
                return [
                    'id' => 'holiday-' . $holiday->id,
                    'title' => $holiday->name,
                    'start' => $holiday->holiday_date->format('Y-m-d'),
                    'backgroundColor' => '#ffc107',
                    'type' => 'holiday'
                ];
            }));
            
            return response()->json($events);
        })->name('dashboard.events');
        
        Route::get('dashboard/today', function() {
            $today = today();
            $schedules = \App\Models\EmployeeMonthlySchedule::with('employee')
                ->where('effective_date', $today)
                ->where('status', 'active')
                ->limit(10)
                ->get();
                
            return response()->json($schedules->map(function($schedule) {
                return [
                    'employee_name' => $schedule->employee->full_name,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'location' => $schedule->location->name ?? 'Default',
                    'type' => 'monthly'
                ];
            }));
        })->name('dashboard.today');
        
        Route::get('dashboard/holidays', function() {
            return response()->json(
                \App\Models\NationalHoliday::where('holiday_date', '>', now())
                    ->orderBy('holiday_date')
                    ->limit(5)
                    ->get()
            );
        })->name('dashboard.holidays');
        
        Route::get('dashboard/employee-distribution', function() {
            $schedules = \App\Models\MonthlySchedule::withCount('employeeMonthlySchedules')->get();
            return response()->json($schedules->map(function($schedule) {
                return [
                    'schedule_name' => $schedule->name,
                    'employee_count' => $schedule->employee_monthly_schedules_count
                ];
            }));
        })->name('dashboard.employee-distribution');
        
        Route::get('dashboard/teaching-status', function() {
            $statuses = \App\Models\TeachingSchedule::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();
            return response()->json($statuses);
        })->name('dashboard.teaching-status');
        
        Route::get('dashboard/conflicts', function() {
            // Mock conflict data - implement real conflict detection later
            return response()->json([
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0
            ]);
        })->name('dashboard.conflicts');
        
        // Monthly Schedule API
        Route::get('monthly/create', [MonthlyScheduleController::class, 'create'])->name('monthly.create');
        
        // Simple test route
        Route::get('monthly/test', function() {
            return response()->json(['test' => 'API working', 'timestamp' => now()]);
        });
        
        // Temporary debug route to test model issues
        Route::get('debug/model-test', function() {
            try {
                $count = \App\Models\MonthlySchedule::count();
                return response()->json(['success' => true, 'count' => $count, 'message' => 'Model works']);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'error' => $e->getMessage(), 'line' => $e->getLine(), 'file' => basename($e->getFile())]);
            }
        });
        
        Route::apiResource('monthly', MonthlyScheduleController::class)->parameters([
            'monthly' => 'monthlySchedule'
        ]);
        Route::post('monthly/{monthlySchedule}/assign-employees', [MonthlyScheduleController::class, 'assignEmployees']);
        Route::get('monthly/{monthlySchedule}/available-employees', [MonthlyScheduleController::class, 'availableEmployees']);
        Route::get('monthly/overview/data', [MonthlyScheduleController::class, 'monthlyOverview']);
        
        // Teaching Schedule API
        Route::apiResource('teaching', TeachingScheduleController::class);
        Route::post('teaching/{teachingSchedule}/assign-substitute', [TeachingScheduleController::class, 'assignSubstitute']);
        Route::delete('teaching/{teachingSchedule}/remove-substitute', [TeachingScheduleController::class, 'removeSubstitute']);
        Route::get('teaching/workload/summary', [TeachingScheduleController::class, 'teacherWorkload']);
        
        // Teaching Schedule Frontend Data
        Route::get('teaching/data', function() {
            return response()->json([
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0
            ]);
        })->name('teaching.data');
        
        Route::get('teaching/events', function() {
            return response()->json([]);
        })->name('teaching.events');
        
        Route::get('teaching/weekly-grid', function() {
            return response()->json([]);
        })->name('teaching.weekly-grid');
        
        // Holiday Management API (already handled by HolidayManagementController)
    });
});