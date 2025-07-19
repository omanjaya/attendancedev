<?php

use App\Http\Controllers\HolidayController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Holiday Management Routes
|--------------------------------------------------------------------------
|
| Routes for managing holidays, school calendar, and public holidays
| Includes CRUD operations, calendar view, and import/export functionality
|
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // Holiday Management Routes
    Route::prefix('holidays')->name('holidays.')->group(function () {

        // Basic CRUD operations
        Route::get('/', [HolidayController::class, 'index'])->name('index');
        Route::get('/create', [HolidayController::class, 'create'])->name('create');
        Route::post('/', [HolidayController::class, 'store'])->name('store');
        Route::get('/{holiday}', [HolidayController::class, 'show'])->name('show');
        Route::get('/{holiday}/edit', [HolidayController::class, 'edit'])->name('edit');
        Route::put('/{holiday}', [HolidayController::class, 'update'])->name('update');
        Route::delete('/{holiday}', [HolidayController::class, 'destroy'])->name('destroy');

        // Calendar view
        Route::get('/calendar/view', [HolidayController::class, 'calendar'])->name('calendar');

        // Import/Export functionality
        Route::post('/import', [HolidayController::class, 'import'])->name('import');
        Route::post('/export', [HolidayController::class, 'export'])->name('export');

        // Recurring holidays
        Route::post('/generate-recurring', [HolidayController::class, 'generateRecurring'])->name('generate-recurring');

        // Holiday check API
        Route::post('/check-date', [HolidayController::class, 'checkDate'])->name('check-date');
    });

    // API Routes for AJAX/JSON requests
    Route::prefix('api/holidays')->name('api.holidays.')->group(function () {

        // Calendar data for frontend calendars
        Route::get('/calendar-data', [HolidayController::class, 'calendar'])->name('calendar-data');

        // Check if specific date is holiday
        Route::get('/check/{date}', function ($date) {
            return app(HolidayController::class)->checkDate(request()->merge(['date' => $date]));
        })->name('check');

        // Get holidays for date range
        Route::get('/range/{start}/{end}', function ($start, $end) {
            $holidays = \App\Models\Holiday::active()
                ->dateRange($start, $end)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $holidays,
            ]);
        })->name('range');

        // Get holidays by type
        Route::get('/type/{type}', function ($type) {
            $holidays = \App\Models\Holiday::active()
                ->byType($type)
                ->orderBy('date')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $holidays,
            ]);
        })->name('by-type');

        // Get working days between dates
        Route::get('/working-days/{start}/{end}', function ($start, $end) {
            $role = request()->get('role');
            $workingDays = \App\Models\Holiday::getWorkingDaysBetween(
                \Carbon\Carbon::parse($start),
                \Carbon\Carbon::parse($end),
                $role
            );

            return response()->json([
                'success' => true,
                'working_days' => $workingDays,
            ]);
        })->name('working-days');

    });

    // School Calendar Routes (specific for school operations)
    Route::prefix('school-calendar')->name('school-calendar.')->group(function () {

        // Academic year calendar
        Route::get('/', function () {
            $currentYear = \Carbon\Carbon::now()->year;
            $holidays = \App\Models\Holiday::active()
                ->whereYear('date', $currentYear)
                ->orderBy('date')
                ->get()
                ->groupBy('type');

            return view('pages.school-calendar.index', compact('holidays', 'currentYear'));
        })->name('index');

        // Semester breaks and school holidays
        Route::get('/academic', function () {
            $schoolHolidays = \App\Models\Holiday::active()
                ->byType(\App\Models\Holiday::TYPE_SCHOOL)
                ->orderBy('date')
                ->get();

            return view('pages.school-calendar.academic', compact('schoolHolidays'));
        })->name('academic');

        // Public holidays that affect school
        Route::get('/public', function () {
            $publicHolidays = \App\Models\Holiday::active()
                ->whereIn('type', [
                    \App\Models\Holiday::TYPE_PUBLIC,
                    \App\Models\Holiday::TYPE_RELIGIOUS,
                    \App\Models\Holiday::TYPE_SUBSTITUTE,
                ])
                ->orderBy('date')
                ->get();

            return view('pages.school-calendar.public', compact('publicHolidays'));
        })->name('public');

    });

});

// Public API (no authentication required) for external integrations
Route::prefix('public/api/holidays')->name('public.api.holidays.')->group(function () {

    // Get public holidays only (for external systems)
    Route::get('/public/{year?}', function ($year = null) {
        $year = $year ?? \Carbon\Carbon::now()->year;

        $holidays = \App\Models\Holiday::active()
            ->whereIn('type', [
                \App\Models\Holiday::TYPE_PUBLIC,
                \App\Models\Holiday::TYPE_RELIGIOUS,
            ])
            ->whereYear('date', $year)
            ->orderBy('date')
            ->get(['name', 'date', 'end_date', 'type']);

        return response()->json([
            'success' => true,
            'year' => $year,
            'data' => $holidays,
        ]);
    })->name('public');

    // Check if today is holiday
    Route::get('/today', function () {
        $today = \Carbon\Carbon::today();
        $isHoliday = \App\Models\Holiday::isHoliday($today);
        $holidays = \App\Models\Holiday::getHolidaysForDate($today);

        return response()->json([
            'success' => true,
            'date' => $today->format('Y-m-d'),
            'is_holiday' => $isHoliday,
            'holidays' => $holidays->pluck('name'),
        ]);
    })->name('today');

});
