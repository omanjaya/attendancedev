<?php

use App\Http\Controllers\AcademicScheduleController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Schedule Management Routes
|--------------------------------------------------------------------------
|
| Routes for employee schedules, academic schedules, and calendar management
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Employee Schedule Management
    Route::prefix('schedules')
        ->middleware('permission:view_schedules')
        ->group(function () {
            Route::get('/', [ScheduleController::class, 'index'])->name('schedules.index');
            Route::get('/calendar', [ScheduleController::class, 'calendar'])->name('schedules.calendar');
            Route::get('/builder', function () {
                return view('pages.schedules.builder');
            })->name('schedules.builder');
            Route::get('/{schedule}', [ScheduleController::class, 'show'])->name('schedules.show');

            // Schedule CRUD (managers only)
            Route::middleware('permission:create_schedules')->group(function () {
                Route::get('/create', function () {
                    $periods = \App\Models\Period::active()
                        ->orderBy('day_of_week')
                        ->orderBy('start_time')
                        ->get();
                    $teachers = \App\Models\Employee::with('user')->where('is_active', true)->get();

                    return view('pages.schedules.create', compact('periods', 'teachers'));
                })->name('schedules.create');

                Route::post('/', [ScheduleController::class, 'store'])->name('schedules.store');
                Route::get('/{schedule}/edit', [ScheduleController::class, 'edit'])->name('schedules.edit');
                Route::put('/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
                Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])->name(
                    'schedules.destroy',
                );

                // Advanced schedule operations
                Route::post('/assign-employees', [ScheduleController::class, 'assignEmployees'])->name(
                    'schedules.assign-employees',
                );
                Route::delete('/assignments/{schedule}', [
                    ScheduleController::class,
                    'removeAssignment',
                ])->name('schedules.remove-assignment');
                Route::post('/import', [ScheduleController::class, 'import'])->name('schedules.import');
            });

            // AJAX/DataTable routes
            Route::prefix('data')->group(function () {
                Route::get('/periods', [ScheduleController::class, 'getPeriodsData'])->name(
                    'schedules.periods.data',
                );
                Route::get('/calendar', [ScheduleController::class, 'getCalendarData'])->name(
                    'schedules.calendar.data',
                );
                Route::get('/periods/{period}/assignments', [
                    ScheduleController::class,
                    'getPeriodAssignments',
                ])->name('schedules.period.assignments');
            });

            // Template download
            Route::get('/download-template', [ScheduleController::class, 'downloadTemplate'])->name(
                'schedules.download-template',
            );
        });

    // Academic Schedule Management (for schools)
    Route::prefix('academic')
        ->middleware('permission:view_schedules')
        ->group(function () {
            Route::get('/schedules', [AcademicScheduleController::class, 'index'])->name(
                'academic.schedules.index',
            );

            // Academic calendar with static data for now
            Route::get('/schedules/calendar', function () {
                // Simple static data to get the interface working
                $academicClasses = collect([
                    (object) [
                        'id' => 1,
                        'grade_level' => 10,
                        'major' => 'IPA',
                        'class_number' => 1,
                        'name' => 'X IPA 1',
                        'section' => '1',
                    ],
                    (object) [
                        'id' => 2,
                        'grade_level' => 10,
                        'major' => 'IPA',
                        'class_number' => 2,
                        'name' => 'X IPA 2',
                        'section' => '2',
                    ],
                    (object) [
                        'id' => 3,
                        'grade_level' => 10,
                        'major' => 'IPS',
                        'class_number' => 1,
                        'name' => 'X IPS 1',
                        'section' => '1',
                    ],
                    (object) [
                        'id' => 4,
                        'grade_level' => 11,
                        'major' => 'IPA',
                        'class_number' => 1,
                        'name' => 'XI IPA 1',
                        'section' => '1',
                    ],
                    (object) [
                        'id' => 5,
                        'grade_level' => 11,
                        'major' => 'IPA',
                        'class_number' => 2,
                        'name' => 'XI IPA 2',
                        'section' => '2',
                    ],
                    (object) [
                        'id' => 6,
                        'grade_level' => 11,
                        'major' => 'IPS',
                        'class_number' => 1,
                        'name' => 'XI IPS 1',
                        'section' => '1',
                    ],
                    (object) [
                        'id' => 7,
                        'grade_level' => 12,
                        'major' => 'IPA',
                        'class_number' => 1,
                        'name' => 'XII IPA 1',
                        'section' => '1',
                    ],
                    (object) [
                        'id' => 8,
                        'grade_level' => 12,
                        'major' => 'IPA',
                        'class_number' => 2,
                        'name' => 'XII IPA 2',
                        'section' => '2',
                    ],
                    (object) [
                        'id' => 9,
                        'grade_level' => 12,
                        'major' => 'IPS',
                        'class_number' => 1,
                        'name' => 'XII IPS 1',
                        'section' => '1',
                    ],
                ]);

                $timeSlots = collect([
                    (object) ['id' => 1, 'name' => 'Jam 1', 'start_time' => '07:00', 'end_time' => '07:45'],
                    (object) ['id' => 2, 'name' => 'Jam 2', 'start_time' => '07:45', 'end_time' => '08:30'],
                    (object) ['id' => 3, 'name' => 'Jam 3', 'start_time' => '08:30', 'end_time' => '09:15'],
                    (object) ['id' => 4, 'name' => 'Jam 4', 'start_time' => '09:35', 'end_time' => '10:20'],
                    (object) ['id' => 5, 'name' => 'Jam 5', 'start_time' => '10:20', 'end_time' => '11:05'],
                ]);

                return view('pages.academic.schedules.calendar', compact('academicClasses', 'timeSlots'));
            })->name('academic.schedules.calendar');

            // Academic schedule API routes
            Route::prefix('api')->group(function () {
                Route::get('/teachers-with-subjects', [
                    AcademicScheduleController::class,
                    'getTeachersWithSubjects',
                ]);
                Route::get('/schedules/grid/{classId}', [
                    AcademicScheduleController::class,
                    'getScheduleGrid',
                ]);
                Route::get('/schedules/conflicts', [AcademicScheduleController::class, 'getConflicts']);
                Route::get('/schedules/export', [AcademicScheduleController::class, 'exportSchedules']);

                // Management operations (with permissions)
                Route::middleware('permission:create_schedules')->group(function () {
                    Route::post('/schedules', [AcademicScheduleController::class, 'store']);
                    Route::post('/schedules/import', [AcademicScheduleController::class, 'importSchedules']);
                });

                Route::middleware('permission:edit_schedules')->group(function () {
                    Route::put('/schedules/{schedule}', [AcademicScheduleController::class, 'update']);
                    Route::post('/schedules/swap', [AcademicScheduleController::class, 'swapSchedules']);
                });

                Route::middleware('permission:delete_schedules')->group(function () {
                    Route::delete('/schedules/{schedule}', [AcademicScheduleController::class, 'destroy']);
                });

                Route::middleware('permission:lock_schedules')->group(function () {
                    Route::post('/schedules/{schedule}/lock', [
                        AcademicScheduleController::class,
                        'lockSchedule',
                    ]);
                    Route::delete('/schedules/{schedule}/lock', [
                        AcademicScheduleController::class,
                        'unlockSchedule',
                    ]);
                });

                Route::middleware('permission:resolve_schedule_conflicts')->group(function () {
                    Route::post('/schedules/conflicts/{conflict}/resolve', [
                        AcademicScheduleController::class,
                        'resolveConflict',
                    ]);
                });
            });
        });
});
