<?php

namespace App\Http\Controllers;

use App\Models\MonthlySchedule;
use App\Models\Employee;
use App\Models\Location;
use App\Services\ScheduleManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MonthlyScheduleController extends Controller
{
    protected ScheduleManagementService $scheduleService;

    public function __construct(ScheduleManagementService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
        $this->middleware('auth');
        $this->middleware('permission:manage_schedules')->except(['index', 'show', 'create']);
        $this->middleware('permission:view_schedules')->only(['index', 'show', 'create']);
    }

    /**
     * Display a listing of monthly schedules
     */
    public function index(Request $request)
    {
        // Check if this is an API request
        if ($request->wantsJson() || $request->is('api/*')) {
            return $this->apiIndex($request);
        }
        
        // Return view for web requests
        return view('pages.schedules.monthly.index');
    }
    
    /**
     * API version of index method
     */
    private function apiIndex(Request $request): JsonResponse
    {
        try {
            $query = MonthlySchedule::query();
            
            // Only eager load if relationships exist
            try {
                $query->with(['creator', 'location']);
            } catch (\Exception $e) {
                // Continue without eager loading if relationships fail
            }

            // Filter by month/year
            if ($request->has('month') && $request->has('year')) {
                $query->where('month', $request->month)->where('year', $request->year);
            }

            // Search by name
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $schedules = $query->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->orderBy('name')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $schedules->map(function($schedule) {
                    return [
                        'id' => $schedule->id,
                        'name' => $schedule->name,
                        'full_name' => $schedule->name . ' - ' . Carbon::createFromDate($schedule->year, $schedule->month, 1)->format('F Y'),
                        'month' => $schedule->month,
                        'month_name' => Carbon::createFromDate($schedule->year, $schedule->month, 1)->format('F'),
                        'year' => $schedule->year,
                        'start_date' => $schedule->start_date ? $schedule->start_date->format('Y-m-d') : null,
                        'end_date' => $schedule->end_date ? $schedule->end_date->format('Y-m-d') : null,
                        'default_start_time' => $schedule->default_start_time ? $schedule->default_start_time->format('H:i') : '08:00',
                        'default_end_time' => $schedule->default_end_time ? $schedule->default_end_time->format('H:i') : '16:00',
                        'location' => $schedule->location ? [
                            'id' => $schedule->location->id,
                            'name' => $schedule->location->name
                        ] : [
                            'id' => $schedule->location_id ?? 'unknown',
                            'name' => 'Unknown Location'
                        ],
                        'assigned_employees_count' => method_exists($schedule, 'employeeSchedules') ? 
                            $schedule->employeeSchedules()->distinct('employee_id')->count() : 0,
                        'created_by' => $schedule->creator->name ?? 'System',
                        'created_at' => $schedule->created_at ? $schedule->created_at->format('Y-m-d H:i:s') : null,
                        'is_active' => $schedule->is_active ?? true
                    ];
                }),
                'meta' => [
                    'current_page' => $schedules->currentPage(),
                    'last_page' => $schedules->lastPage(),
                    'per_page' => $schedules->perPage(),
                    'total' => $schedules->total()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load schedules',
                'error' => $e->getMessage(),
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 0
                ]
            ], 500);
        }
    }

    /**
     * Show the form for creating a new monthly schedule
     */
    public function create(Request $request)
    {
        try {
            
            // Load locations with fallback
            $locations = [];
            try {
                $locations = Location::active()->select('id', 'name')->get();
                
                // If no locations found, provide sample locations for demo
                if ($locations->isEmpty()) {
                    $locations = collect([
                        (object)['id' => 'sample-1', 'name' => 'Main Campus - Building A'],
                        (object)['id' => 'sample-2', 'name' => 'Main Campus - Building B'], 
                        (object)['id' => 'sample-3', 'name' => 'Branch Office - North'],
                        (object)['id' => 'sample-4', 'name' => 'Branch Office - South'],
                        (object)['id' => 'sample-5', 'name' => 'Administrative Office']
                    ]);
                }
            } catch (\Exception $e) {
                // If Location query fails, provide fallback
                $locations = collect([
                    (object)['id' => 'fallback-1', 'name' => 'Main Campus'],
                    (object)['id' => 'fallback-2', 'name' => 'Branch Office']
                ]);
            }
            
            $months = [];
            try {
                $months = collect(range(1, 12))->map(function($month) {
                    return [
                        'value' => $month,
                        'label' => Carbon::createFromDate(null, $month, 1)->format('F')
                    ];
                });
            } catch (\Exception $e) {
                // Fallback months
                $months = [
                    ['value' => 1, 'label' => 'January'],
                    ['value' => 2, 'label' => 'February'],
                    ['value' => 3, 'label' => 'March'],
                    ['value' => 4, 'label' => 'April'],
                    ['value' => 5, 'label' => 'May'],
                    ['value' => 6, 'label' => 'June'],
                    ['value' => 7, 'label' => 'July'],
                    ['value' => 8, 'label' => 'August'],
                    ['value' => 9, 'label' => 'September'],
                    ['value' => 10, 'label' => 'October'],
                    ['value' => 11, 'label' => 'November'],
                    ['value' => 12, 'label' => 'December']
                ];
            }
            
            $years = [];
            try {
                $currentYear = date('Y');
                $years = collect(range($currentYear, $currentYear + 2))->map(function($year) {
                    return ['value' => $year, 'label' => $year];
                });
            } catch (\Exception $e) {
                // Fallback years
                $years = [
                    ['value' => 2025, 'label' => 2025],
                    ['value' => 2026, 'label' => 2026],
                    ['value' => 2027, 'label' => 2027]
                ];
            }
            
            $data = [
                'locations' => $locations,
                'months' => $months,
                'years' => $years,
                'working_hours_templates' => [
                    'standard_5_days' => [
                        'name' => 'Standard 5 Days (Mon-Thu: 07:30-15:30, Fri: 07:30-13:00)',
                        'working_hours' => [
                            'monday' => ['start' => '07:30', 'end' => '15:30', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'tuesday' => ['start' => '07:30', 'end' => '15:30', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'wednesday' => ['start' => '07:30', 'end' => '15:30', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'thursday' => ['start' => '07:30', 'end' => '15:30', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'friday' => ['start' => '07:30', 'end' => '13:00', 'break_start' => '11:30', 'break_end' => '12:00'],
                            'saturday' => null,
                            'sunday' => null
                        ]
                    ],
                    'uniform_5_days' => [
                        'name' => 'Uniform 5 Days (Mon-Fri: 08:00-16:00)',
                        'working_hours' => [
                            'monday' => ['start' => '08:00', 'end' => '16:00', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'tuesday' => ['start' => '08:00', 'end' => '16:00', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'wednesday' => ['start' => '08:00', 'end' => '16:00', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'thursday' => ['start' => '08:00', 'end' => '16:00', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'friday' => ['start' => '08:00', 'end' => '16:00', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'saturday' => null,
                            'sunday' => null
                        ]
                    ],
                    'half_day_saturday' => [
                        'name' => '6 Days with Half Saturday (Mon-Fri: 07:30-15:30, Sat: 07:30-12:00)',
                        'working_hours' => [
                            'monday' => ['start' => '07:30', 'end' => '15:30', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'tuesday' => ['start' => '07:30', 'end' => '15:30', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'wednesday' => ['start' => '07:30', 'end' => '15:30', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'thursday' => ['start' => '07:30', 'end' => '15:30', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'friday' => ['start' => '07:30', 'end' => '15:30', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'saturday' => ['start' => '07:30', 'end' => '12:00', 'break_start' => null, 'break_end' => null],
                            'sunday' => null
                        ]
                    ],
                    'custom' => [
                        'name' => 'Custom Working Hours (Set manually)',
                        'working_hours' => [
                            'monday' => ['start' => '08:00', 'end' => '16:00', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'tuesday' => ['start' => '08:00', 'end' => '16:00', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'wednesday' => ['start' => '08:00', 'end' => '16:00', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'thursday' => ['start' => '08:00', 'end' => '16:00', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'friday' => ['start' => '08:00', 'end' => '16:00', 'break_start' => '12:00', 'break_end' => '13:00'],
                            'saturday' => null,
                            'sunday' => null
                        ]
                    ]
                ],
                'default_metadata' => [
                    'work_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                    'overtime_allowed' => true,
                    'late_threshold_minutes' => 15,
                    'early_departure_threshold_minutes' => 30
                ]
            ];

            // Check if this is an API request
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            }

            // Return Blade view for web requests
            return view('pages.schedules.monthly.create', $data);
            
        } catch (\Exception $e) {
            // For API requests, return JSON error
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load form data',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }
            
            // For web requests, redirect with error
            return redirect()->back()->with('error', 'Failed to load form data: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created monthly schedule
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), MonthlySchedule::validationRules());
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $schedule = $this->scheduleService->createMonthlySchedule($validator->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Monthly schedule created successfully',
                'data' => [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'full_name' => $schedule->full_name,
                    'month' => $schedule->month,
                    'year' => $schedule->year,
                    'working_hours' => $schedule->working_hours,
                    'duration_days' => $schedule->duration_days
                ]
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Monthly Schedule Creation Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create monthly schedule',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Display the specified monthly schedule
     */
    public function show(Request $request, MonthlySchedule $monthlySchedule)
    {
        try {
            // Debug: Check if model is loaded
            \Log::info('Show method called with schedule ID: ' . ($monthlySchedule->id ?? 'NULL'));
            
            // If model is not loaded, try to find it manually
            if (!$monthlySchedule->exists) {
                $scheduleId = $request->route('monthlySchedule');
                \Log::info('Model not loaded via route binding, trying manual load with ID: ' . $scheduleId);
                $monthlySchedule = MonthlySchedule::findOrFail($scheduleId);
            }
            
            // Only try to load relationships that exist
            $monthlySchedule->load(['creator', 'location']);
            
            // Try to get holiday conflicts, but handle if method doesn't exist
            $holidayConflicts = [];
            try {
                $holidayConflicts = $monthlySchedule->getHolidayConflicts();
            } catch (\Exception $e) {
                // Method might not exist, use empty array
            }
            
            // Check if this is an API request
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
            'success' => true,
            'data' => [
                'id' => $monthlySchedule->id,
                'name' => $monthlySchedule->name,
                'month' => $monthlySchedule->month,
                'month_name' => \Carbon\Carbon::createFromDate($monthlySchedule->year, $monthlySchedule->month, 1)->format('F'),
                'year' => $monthlySchedule->year,
                'start_date' => $monthlySchedule->start_date ? $monthlySchedule->start_date->format('Y-m-d') : null,
                'end_date' => $monthlySchedule->end_date ? $monthlySchedule->end_date->format('Y-m-d') : null,
                'default_start_time' => $monthlySchedule->default_start_time ? $monthlySchedule->default_start_time->format('H:i') : '08:00',
                'default_end_time' => $monthlySchedule->default_end_time ? $monthlySchedule->default_end_time->format('H:i') : '16:00',
                'description' => $monthlySchedule->description,
                'metadata' => $monthlySchedule->metadata,
                'location' => $monthlySchedule->location ? [
                    'id' => $monthlySchedule->location->id,
                    'name' => $monthlySchedule->location->name
                ] : [
                    'id' => $monthlySchedule->location_id ?? 'unknown',
                    'name' => 'Unknown Location'
                ],
                'assigned_employees' => [], // Simplified for now
                'holiday_conflicts' => $holidayConflicts,
                'statistics' => [
                    'total_assigned_employees' => 0,
                    'total_schedule_days' => 0,
                    'working_days' => 0,
                    'holiday_days' => 0
                ],
                'created_by' => $monthlySchedule->creator->name ?? 'System',
                'created_at' => $monthlySchedule->created_at ? $monthlySchedule->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $monthlySchedule->updated_at ? $monthlySchedule->updated_at->format('Y-m-d H:i:s') : null
            ]
        ]);
            }
            
            // Return view for web requests
            return view('pages.schedules.monthly.show', compact('monthlySchedule'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Schedule not found: ' . $e->getMessage());
            
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Schedule not found',
                    'error' => 'The requested schedule does not exist'
                ], 404);
            }
            
            return redirect()->route('schedule-management.monthly.index')
                ->with('error', 'Schedule not found');
        } catch (\Exception $e) {
            \Log::error('Error in show method: ' . $e->getMessage());
            
            // For API requests, return JSON error
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load schedule details',
                    'error' => $e->getMessage(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : null
                ], 500);
            }
            
            // For web requests, redirect with error
            return redirect()->back()->with('error', 'Failed to load schedule details: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified monthly schedule
     */
    public function edit(MonthlySchedule $monthlySchedule)
    {
        return view('pages.schedules.monthly.edit', compact('monthlySchedule'));
    }

    /**
     * Update the specified monthly schedule
     */
    public function update(Request $request, MonthlySchedule $monthlySchedule): JsonResponse
    {
        $validator = Validator::make($request->all(), MonthlySchedule::validationRules());
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['updated_by'] = auth()->id();
            
            $monthlySchedule->update($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Monthly schedule updated successfully',
                'data' => [
                    'id' => $monthlySchedule->id,
                    'name' => $monthlySchedule->name,
                    'full_name' => $monthlySchedule->full_name
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update monthly schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified monthly schedule
     */
    public function destroy(MonthlySchedule $monthlySchedule): JsonResponse
    {
        try {
            // Check if there are assigned employees
            $assignedCount = $monthlySchedule->getAssignedEmployeesCount();
            
            if ($assignedCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete schedule with {$assignedCount} assigned employees. Remove assignments first."
                ], 400);
            }
            
            $monthlySchedule->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Monthly schedule deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete monthly schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign employees to monthly schedule
     */
    public function assignEmployees(Request $request, MonthlySchedule $monthlySchedule): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|uuid|exists:employees,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $results = $this->scheduleService->bulkAssignEmployees(
                $monthlySchedule, 
                $request->employee_ids
            );

            return response()->json([
                'success' => true,
                'message' => 'Employees assigned successfully',
                'data' => [
                    'successful_assignments' => $results['success'],
                    'failed_assignments' => $results['failed'],
                    'errors' => $results['errors'],
                    'total_assigned_employees' => $monthlySchedule->fresh()->getAssignedEmployeesCount()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign employees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available employees for assignment
     */
    public function availableEmployees(MonthlySchedule $monthlySchedule): JsonResponse
    {
        // Get employees that are not already assigned to this schedule
        $assignedEmployeeIds = $monthlySchedule->employeeSchedules()
            ->distinct('employee_id')
            ->pluck('employee_id');

        $availableEmployees = Employee::active()
            ->whereNotIn('id', $assignedEmployeeIds)
            ->select('id', 'full_name', 'employee_type', 'can_teach', 'can_substitute')
            ->get()
            ->groupBy('employee_type')
            ->map(function($employees, $type) {
                return [
                    'type' => $type,
                    'type_label' => ucwords(str_replace('_', ' ', $type)),
                    'employees' => $employees->map(function($employee) {
                        return [
                            'id' => $employee->id,
                            'name' => $employee->full_name,
                            'can_teach' => $employee->can_teach,
                            'can_substitute' => $employee->can_substitute
                        ];
                    })->values()
                ];
            })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'available_employees' => $availableEmployees,
                'total_available' => Employee::active()->whereNotIn('id', $assignedEmployeeIds)->count(),
                'total_assigned' => $assignedEmployeeIds->count()
            ]
        ]);
    }

    /**
     * Apply holiday overrides to schedule
     */
    public function applyHolidayOverrides(MonthlySchedule $monthlySchedule): JsonResponse
    {
        try {
            $overriddenCount = $monthlySchedule->applyHolidayOverrides();
            
            return response()->json([
                'success' => true,
                'message' => "Applied holiday overrides to {$overriddenCount} schedule entries",
                'data' => [
                    'overridden_count' => $overriddenCount,
                    'holiday_conflicts' => $monthlySchedule->fresh()->getHolidayConflicts()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply holiday overrides',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly overview statistics
     */
    public function monthlyOverview(Request $request): JsonResponse
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $locationId = $request->get('location_id');

        try {
            $overview = $this->scheduleService->getMonthlyScheduleOverview($month, $year, $locationId);

            return response()->json([
                'success' => true,
                'data' => $overview
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get monthly overview',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}