<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManualAttendanceRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ManualAttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly NotificationService $notificationService
    ) {
        $this->middleware('permission:manage_attendance_all');
    }

    /**
     * Display manual attendance entry form
     */
    public function index()
    {
        return view('pages.attendance.manual-entry');
    }

    /**
     * Store manual attendance entry
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after:check_in_time',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $employee = Employee::findOrFail($request->employee_id);
            
            // Check if attendance already exists for this date
            $existingAttendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $request->date)
                ->first();

            if ($existingAttendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance already exists for this date'
                ], 400);
            }

            $attendance = DB::transaction(function () use ($request, $employee) {
                $checkInDateTime = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . $request->check_in_time);
                $checkOutDateTime = null;
                
                if ($request->check_out_time) {
                    $checkOutDateTime = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . $request->check_out_time);
                }

                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'date' => $request->date,
                    'check_in' => $checkInDateTime,
                    'check_out' => $checkOutDateTime,
                    'check_in_location' => [
                        'type' => 'manual_entry',
                        'entered_by' => auth()->id(),
                        'reason' => $request->reason,
                    ],
                    'check_out_location' => $checkOutDateTime ? [
                        'type' => 'manual_entry',
                        'entered_by' => auth()->id(),
                        'reason' => $request->reason,
                    ] : null,
                    'status' => $this->determineStatus($checkInDateTime, $employee),
                    'working_hours' => $checkOutDateTime ? 
                        $checkOutDateTime->diffInHours($checkInDateTime) : 0,
                    'notes' => $request->notes,
                    'is_manual_entry' => true,
                    'manual_entry_reason' => $request->reason,
                    'manual_entry_by' => auth()->id(),
                ]);

                // Log manual entry
                Log::info('Manual attendance entry created', [
                    'attendance_id' => $attendance->id,
                    'employee_id' => $employee->id,
                    'date' => $request->date,
                    'entered_by' => auth()->id(),
                    'reason' => $request->reason,
                ]);

                return $attendance;
            });

            // Send notification to employee
            $this->notificationService->send($employee->user, 'attendance.manual_entry', [
                'date' => $request->date,
                'check_in_time' => $request->check_in_time,
                'check_out_time' => $request->check_out_time,
                'reason' => $request->reason,
                'entered_by' => auth()->user()->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Manual attendance entry created successfully',
                'data' => [
                    'attendance' => [
                        'id' => $attendance->id,
                        'employee_id' => $attendance->employee_id,
                        'date' => $attendance->date,
                        'check_in' => $attendance->check_in,
                        'check_out' => $attendance->check_out,
                        'working_hours' => $attendance->working_hours,
                        'status' => $attendance->status,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Manual attendance entry failed', [
                'employee_id' => $request->employee_id,
                'date' => $request->date,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create manual attendance entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update manual attendance entry
     */
    public function update(Request $request, Attendance $attendance): JsonResponse
    {
        $request->validate([
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after:check_in_time',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            if (!$attendance->is_manual_entry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Can only update manual attendance entries'
                ], 400);
            }

            DB::transaction(function () use ($request, $attendance) {
                $checkInDateTime = Carbon::createFromFormat('Y-m-d H:i', $attendance->date . ' ' . $request->check_in_time);
                $checkOutDateTime = null;
                
                if ($request->check_out_time) {
                    $checkOutDateTime = Carbon::createFromFormat('Y-m-d H:i', $attendance->date . ' ' . $request->check_out_time);
                }

                $attendance->update([
                    'check_in' => $checkInDateTime,
                    'check_out' => $checkOutDateTime,
                    'working_hours' => $checkOutDateTime ? 
                        $checkOutDateTime->diffInHours($checkInDateTime) : 0,
                    'notes' => $request->notes,
                    'manual_entry_reason' => $request->reason,
                    'updated_by' => auth()->id(),
                ]);

                // Log update
                Log::info('Manual attendance entry updated', [
                    'attendance_id' => $attendance->id,
                    'employee_id' => $attendance->employee_id,
                    'updated_by' => auth()->id(),
                    'reason' => $request->reason,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Manual attendance entry updated successfully',
                'data' => [
                    'attendance' => [
                        'id' => $attendance->id,
                        'check_in' => $attendance->check_in,
                        'check_out' => $attendance->check_out,
                        'working_hours' => $attendance->working_hours,
                        'notes' => $attendance->notes,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Manual attendance update failed', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update manual attendance entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete manual attendance entry
     */
    public function destroy(Attendance $attendance): JsonResponse
    {
        try {
            if (!$attendance->is_manual_entry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Can only delete manual attendance entries'
                ], 400);
            }

            // Log deletion before deleting
            Log::info('Manual attendance entry deleted', [
                'attendance_id' => $attendance->id,
                'employee_id' => $attendance->employee_id,
                'date' => $attendance->date,
                'deleted_by' => auth()->id(),
            ]);

            $attendance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Manual attendance entry deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Manual attendance deletion failed', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete manual attendance entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employees for manual entry
     */
    public function getEmployees(Request $request): JsonResponse
    {
        try {
            $query = Employee::with(['user', 'location'])
                ->where('is_active', true);

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('full_name', 'like', '%' . $request->search . '%')
                      ->orWhere('employee_id', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->location_id) {
                $query->where('location_id', $request->location_id);
            }

            $employees = $query->orderBy('full_name')->get();

            return response()->json([
                'success' => true,
                'data' => $employees->map(function ($employee) {
                    return [
                        'id' => $employee->id,
                        'employee_id' => $employee->employee_id,
                        'full_name' => $employee->full_name,
                        'location' => $employee->location->name ?? 'No Location',
                        'user_email' => $employee->user->email ?? 'No Email',
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Get employees for manual entry failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get employees: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get manual attendance entries with filters
     */
    public function getManualEntries(Request $request): JsonResponse
    {
        try {
            $query = Attendance::with(['employee', 'employee.user'])
                ->where('is_manual_entry', true);

            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->date_from) {
                $query->whereDate('date', '>=', $request->date_from);
            }

            if ($request->date_to) {
                $query->whereDate('date', '<=', $request->date_to);
            }

            if ($request->entered_by) {
                $query->where('manual_entry_by', $request->entered_by);
            }

            $entries = $query->orderBy('date', 'desc')
                ->paginate($request->per_page ?? 15);

            return response()->json([
                'success' => true,
                'data' => [
                    'entries' => $entries->items(),
                    'pagination' => [
                        'current_page' => $entries->currentPage(),
                        'last_page' => $entries->lastPage(),
                        'per_page' => $entries->perPage(),
                        'total' => $entries->total(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get manual entries failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get manual entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk create manual attendance entries
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'entries' => 'required|array|min:1|max:50',
            'entries.*.employee_id' => 'required|exists:employees,id',
            'entries.*.date' => 'required|date',
            'entries.*.check_in_time' => 'required|date_format:H:i',
            'entries.*.check_out_time' => 'nullable|date_format:H:i|after:entries.*.check_in_time',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $results = [];
            $errors = [];

            DB::transaction(function () use ($request, &$results, &$errors) {
                foreach ($request->entries as $index => $entry) {
                    try {
                        $employee = Employee::findOrFail($entry['employee_id']);
                        
                        // Check if attendance already exists
                        $existingAttendance = Attendance::where('employee_id', $employee->id)
                            ->whereDate('date', $entry['date'])
                            ->first();

                        if ($existingAttendance) {
                            $errors[] = [
                                'index' => $index,
                                'employee_id' => $entry['employee_id'],
                                'date' => $entry['date'],
                                'error' => 'Attendance already exists for this date'
                            ];
                            continue;
                        }

                        $checkInDateTime = Carbon::createFromFormat('Y-m-d H:i', $entry['date'] . ' ' . $entry['check_in_time']);
                        $checkOutDateTime = null;
                        
                        if ($entry['check_out_time']) {
                            $checkOutDateTime = Carbon::createFromFormat('Y-m-d H:i', $entry['date'] . ' ' . $entry['check_out_time']);
                        }

                        $attendance = Attendance::create([
                            'employee_id' => $employee->id,
                            'date' => $entry['date'],
                            'check_in' => $checkInDateTime,
                            'check_out' => $checkOutDateTime,
                            'check_in_location' => [
                                'type' => 'bulk_manual_entry',
                                'entered_by' => auth()->id(),
                                'reason' => $request->reason,
                            ],
                            'check_out_location' => $checkOutDateTime ? [
                                'type' => 'bulk_manual_entry',
                                'entered_by' => auth()->id(),
                                'reason' => $request->reason,
                            ] : null,
                            'status' => $this->determineStatus($checkInDateTime, $employee),
                            'working_hours' => $checkOutDateTime ? 
                                $checkOutDateTime->diffInHours($checkInDateTime) : 0,
                            'notes' => $request->notes,
                            'is_manual_entry' => true,
                            'manual_entry_reason' => $request->reason,
                            'manual_entry_by' => auth()->id(),
                        ]);

                        $results[] = [
                            'index' => $index,
                            'attendance_id' => $attendance->id,
                            'employee_id' => $attendance->employee_id,
                            'date' => $attendance->date,
                            'status' => 'created'
                        ];

                    } catch (\Exception $e) {
                        $errors[] = [
                            'index' => $index,
                            'employee_id' => $entry['employee_id'],
                            'date' => $entry['date'],
                            'error' => $e->getMessage()
                        ];
                    }
                }
            });

            Log::info('Bulk manual attendance entries processed', [
                'total_entries' => count($request->entries),
                'successful' => count($results),
                'errors' => count($errors),
                'entered_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bulk manual attendance entries processed',
                'data' => [
                    'successful' => $results,
                    'errors' => $errors,
                    'summary' => [
                        'total' => count($request->entries),
                        'successful' => count($results),
                        'failed' => count($errors),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk manual attendance entries failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process bulk manual attendance entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determine attendance status based on check-in time
     */
    private function determineStatus(Carbon $checkInTime, Employee $employee): string
    {
        // Get employee's schedule for the day
        $schedule = $employee->getTodaySchedule();
        
        if (!$schedule) {
            return 'present'; // Default if no schedule
        }

        $scheduledStartTime = Carbon::createFromFormat('H:i:s', $schedule->start_time);
        $scheduledStartTime->setDate($checkInTime->year, $checkInTime->month, $checkInTime->day);

        if ($checkInTime->lte($scheduledStartTime)) {
            return 'present';
        } elseif ($checkInTime->lte($scheduledStartTime->copy()->addMinutes(15))) {
            return 'late';
        } else {
            return 'very_late';
        }
    }
}