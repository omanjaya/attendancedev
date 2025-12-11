<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Repositories\AttendanceRepository;
use App\Repositories\EmployeeRepository;
use App\Services\AttendanceScheduleService;
use App\Services\Attendance\AttendanceValidationService;
use App\Services\Attendance\AttendanceLocationService;
use App\Services\Attendance\AttendanceStatisticsService;
use App\Services\Attendance\AttendanceDataTableService;
use App\Services\Attendance\AttendanceExportImportService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private AttendanceRepository $attendanceRepository,
        private EmployeeRepository $employeeRepository,
        private AttendanceScheduleService $scheduleService,
        private AttendanceValidationService $validationService,
        private AttendanceLocationService $locationService,
        private AttendanceStatisticsService $statisticsService,
        private AttendanceDataTableService $dataTableService,
        private AttendanceExportImportService $exportImportService
    ) {}

    /**
     * Display attendance management interface.
     */
    public function index()
    {
        return view('pages.attendance.index');
    }

    /**
     * Show check-in interface.
     */
    public function checkIn()
    {
        return view('pages.attendance.checkin');
    }

    /**
     * Show attendance history.
     */
    public function history(Request $request)
    {
        $employee = null;
        if (auth()->user()->employee) {
            $employee = auth()->user()->employee;
        }

        return view('pages.attendance.history', compact('employee'));
    }

    /**
     * Process check-in.
     */
    public function processCheckIn(Request $request)
    {
        $validated = $request->validate([
            'face_confidence' => 'required|numeric|min:0|max:1',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // Get employee from authenticated user
            $user = auth()->user();
            $employee = $user->employee;

            if (!$employee) {
                // Auto-create employee record for admin users
                if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
                    $employee = \App\Models\Employee::create([
                        'user_id' => $user->id,
                        'employee_id' => 'ADMIN-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                        'employee_type' => 'permanent',
                        'full_name' => $user->name,
                        'hire_date' => now()->format('Y-m-d'),
                        'salary_type' => 'monthly',
                        'salary_amount' => 0,
                        'is_active' => true,
                        'metadata' => ['auto_created' => true, 'role' => 'admin']
                    ]);
                } else {
                    return $this->errorResponse('Employee record not found. Please contact administrator to set up your employee profile.');
                }
            }

            // Check if already checked in today
            $todayAttendance = $this->attendanceRepository->getTodayAttendance($employee->id);

            if ($todayAttendance && $todayAttendance->check_in_time) {
                return $this->errorResponse('Anda sudah melakukan absen datang hari ini pada ' . $todayAttendance->formatted_check_in);
            }

            // ===== PHASE 1: Validate Working Day =====
            $workingDayValidation = $this->validationService->validateWorkingDay($employee);

            if (!$workingDayValidation['valid']) {
                return $this->errorResponse($workingDayValidation['message'], 400);
            }

            $schedule = $workingDayValidation['schedule'];

            // ===== PHASE 2: Calculate Lateness using Schedule Service =====
            $now = now('Asia/Makassar');
            $latenessInfo = $this->scheduleService->calculateCheckInLateness($employee, $now);
            
            $isLate = $latenessInfo['is_late'];
            $lateMinutes = $latenessInfo['late_minutes'];
            $timeWindowMessage = $latenessInfo['message'];
            $scheduleMode = $latenessInfo['schedule_mode'];
            $scheduleSource = $latenessInfo['source'] ?? 'unknown';

            // For flexible employees without teaching schedule, prevent check-in
            if ($scheduleMode === 'flexible' && $scheduleSource === 'no_teaching_schedule') {
                return $this->errorResponse('Anda tidak memiliki jadwal mengajar hari ini. Tidak perlu absen.', 400);
            }

            // Verify location if provided
            $locationVerified = true;
            if (isset($validated['latitude']) && isset($validated['longitude'])) {
                $locationVerified = $this->locationService->verifyEmployeeLocation(
                    $employee,
                    $validated['latitude'],
                    $validated['longitude'],
                );
            }

            // Create or update attendance record
            $attendance = $this->attendanceRepository->getOrCreateToday($employee->id);

            // Prepare metadata with schedule information
            $attendanceMetadata = array_merge($attendance->metadata ?? [], $validated['metadata'] ?? []);
            
            // Add schedule mode info to metadata
            $attendanceMetadata['schedule_mode'] = $scheduleMode;
            $attendanceMetadata['schedule_source'] = $scheduleSource;
            $attendanceMetadata['is_late'] = $isLate;
            $attendanceMetadata['late_minutes'] = $lateMinutes;
            $attendanceMetadata['expected_start_time'] = $latenessInfo['expected_time'];

            if ($schedule) {
                $attendanceMetadata['monthly_schedule_id'] = $schedule->id;
                $attendanceMetadata['schedule_name'] = $schedule->name;
                $attendanceMetadata['expected_end_time'] = $schedule->default_end_time;
            }

            $attendance->update([
                'check_in_time' => now('Asia/Makassar'),
                'check_in_confidence' => $validated['face_confidence'],
                'check_in_latitude' => $validated['latitude'] ?? null,
                'check_in_longitude' => $validated['longitude'] ?? null,
                'location_verified' => $locationVerified,
                'check_in_notes' => $validated['notes'] ?? null,
                'metadata' => $attendanceMetadata,
            ]);

            DB::commit();

            // Prepare response data
            $responseData = [
                'attendance_id' => $attendance->id,
                'check_in_time' => $attendance->check_in_time->format('Y-m-d H:i:s'),
                'location_verified' => $locationVerified,
                'confidence' => $validated['face_confidence'],
            ];

            // Add schedule info if available
            if ($schedule) {
                $responseData['schedule'] = [
                    'name' => $schedule->name,
                    'expected_start' => $schedule->default_start_time,
                    'expected_end' => $schedule->default_end_time,
                    'is_late' => $isLate,
                ];
            }

            // Determine success message
            $message = 'Check-in successful';
            if ($timeWindowMessage) {
                $message .= '. ' . $timeWindowMessage;
            }

            return $this->successResponse($responseData, $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverErrorResponse('Check-in failed: ' . $e->getMessage());
        }
    }

    /**
     * Process check-out.
     */
    public function processCheckOut(Request $request)
    {
        $validated = $request->validate([
            'face_confidence' => 'required|numeric|min:0|max:1',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // Get employee from authenticated user
            $user = auth()->user();
            $employee = $user->employee;

            if (!$employee) {
                // Auto-create employee record for admin users
                if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
                    $employee = \App\Models\Employee::create([
                        'user_id' => $user->id,
                        'employee_id' => 'ADMIN-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                        'employee_type' => 'permanent',
                        'full_name' => $user->name,
                        'hire_date' => now()->format('Y-m-d'),
                        'salary_type' => 'monthly',
                        'salary_amount' => 0,
                        'is_active' => true,
                        'metadata' => ['auto_created' => true, 'role' => 'admin']
                    ]);
                } else {
                    return $this->errorResponse('Employee record not found. Please contact administrator to set up your employee profile.');
                }
            }

            // Get today's attendance
            $attendance = $this->attendanceRepository->getTodayAttendance($employee->id);

            if (!$attendance || !$attendance->check_in_time) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Anda belum melakukan absen datang hari ini. Silakan absen datang terlebih dahulu.',
                    ],
                    400,
                );
            }

            if ($attendance->check_out_time) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Anda sudah melakukan absen pulang hari ini pada ' . $attendance->formatted_check_out,
                    ],
                    400,
                );
            }

            // ===== PHASE 1: Validate Working Day =====
            $workingDayValidation = $this->validationService->validateWorkingDay($employee);

            if (!$workingDayValidation['valid']) {
                return $this->errorResponse($workingDayValidation['message'], 400);
            }

            $schedule = $workingDayValidation['schedule'];

            // ===== PHASE 2: Calculate Early Checkout using Schedule Service =====
            $now = now('Asia/Makassar');
            $earlinessInfo = $this->scheduleService->calculateCheckOutEarliness($employee, $now);
            
            $isEarly = $earlinessInfo['is_early'];
            $earlyMinutes = $earlinessInfo['early_minutes'];
            $timeWindowMessage = $earlinessInfo['message'];
            $scheduleMode = $earlinessInfo['schedule_mode'];
            $scheduleSource = $earlinessInfo['source'] ?? 'unknown';

            // Verify location if provided
            $locationVerified = $attendance->location_verified; // Keep previous verification
            if (isset($validated['latitude']) && isset($validated['longitude'])) {
                $currentLocationVerified = $this->locationService->verifyEmployeeLocation(
                    $employee,
                    $validated['latitude'],
                    $validated['longitude'],
                );
                $locationVerified = $locationVerified && $currentLocationVerified;
            }

            // Prepare metadata with schedule information
            $attendanceMetadata = array_merge($attendance->metadata ?? [], $validated['metadata'] ?? []);
            
            // Add checkout schedule info to metadata
            $attendanceMetadata['is_early'] = $isEarly;
            $attendanceMetadata['early_minutes'] = $earlyMinutes;
            $attendanceMetadata['expected_end_time'] = $earlinessInfo['expected_time'];
            $attendanceMetadata['checkout_schedule_mode'] = $scheduleMode;
            $attendanceMetadata['checkout_schedule_source'] = $scheduleSource;

            // Update attendance record
            $attendance->update([
                'check_out_time' => now('Asia/Makassar'),
                'check_out_confidence' => $validated['face_confidence'],
                'check_out_latitude' => $validated['latitude'] ?? null,
                'check_out_longitude' => $validated['longitude'] ?? null,
                'location_verified' => $locationVerified,
                'check_out_notes' => $validated['notes'] ?? null,
                'metadata' => $attendanceMetadata,
            ]);

            // Calculate total hours and update status
            $attendance->updateTotalHours();
            $attendance->updateStatus();

            DB::commit();

            // Prepare response data
            $responseData = [
                'attendance_id' => $attendance->id,
                'check_out_time' => $attendance->check_out_time->format('Y-m-d H:i:s'),
                'total_hours' => $attendance->total_hours,
                'working_hours_formatted' => $attendance->working_hours_formatted,
                'status' => $attendance->status,
                'location_verified' => $locationVerified,
                'confidence' => $validated['face_confidence'],
            ];

            // Add schedule info if available
            if ($schedule) {
                $responseData['schedule'] = [
                    'name' => $schedule->name,
                    'expected_start' => $schedule->default_start_time,
                    'expected_end' => $schedule->default_end_time,
                    'is_early' => $isEarly,
                ];
            }

            // Determine success message
            $message = 'Check-out successful';
            if ($timeWindowMessage) {
                $message .= '. ' . $timeWindowMessage;
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $responseData,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Check-out failed: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get current attendance status for employee.
     */
    public function getStatus(Request $request)
    {
        try {
            $employeeId = $request->input('employee_id') ?? auth()->user()->employee?->id;

            if (!$employeeId) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Employee not found',
                    ],
                    404,
                );
            }

            $attendance = $this->attendanceRepository->getTodayAttendance($employeeId);
            $employee = Employee::with('user')->find($employeeId);

            if (!$attendance) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'status' => 'not_checked_in',
                        'employee' => [
                            'id' => $employee->id,
                            'name' => $employee->full_name,
                            'employee_id' => $employee->employee_id,
                        ],
                        'check_in_time' => null,
                        'check_out_time' => null,
                        'total_hours' => 0,
                        'can_check_in' => true,
                        'can_check_out' => false,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $attendance->isCheckedIn() ? 'checked_in' : 'checked_out',
                    'employee' => [
                        'id' => $employee->id,
                        'name' => $employee->full_name,
                        'employee_id' => $employee->employee_id,
                    ],
                    'attendance_id' => $attendance->id,
                    'date' => $attendance->date->format('Y-m-d'),
                    'check_in_time' => $attendance->check_in_time?->format('Y-m-d H:i:s'),
                    'check_out_time' => $attendance->check_out_time?->format('Y-m-d H:i:s'),
                    'total_hours' => $attendance->total_hours ?? 0,
                    'working_hours_formatted' => $attendance->working_hours_formatted,
                    'attendance_status' => $attendance->status,
                    'location_verified' => $attendance->location_verified,
                    'can_check_in' => !$attendance->check_in_time,
                    'can_check_out' => $attendance->check_in_time && !$attendance->check_out_time,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to get status: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get attendance data for DataTables.
     */
    public function getAttendanceData(Request $request)
    {
        $query = Attendance::with(['employee.user', 'employee.location']);

        // Apply role-based filtering
        $query = $this->dataTableService->applyRoleBasedFiltering($query, auth()->user());

        $query->orderBy('date', 'desc')
            ->orderBy('check_in_time', 'desc');

        // Apply filters
        $filters = $request->only(['employee_id', 'start_date', 'end_date']);
        $query = $this->dataTableService->applyFilters($query, $filters);

        return $this->dataTableService->getDataTableData($query);
    }

    /**
     * Get attendance statistics.
     */
    public function getStatistics(Request $request)
    {
        try {
            $startDate = $request->input('start_date', today()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', today()->format('Y-m-d'));

            $statistics = $this->statisticsService->getStatistics($startDate, $endDate, auth()->user());

            return response()->json([
                'success' => true,
                'statistics' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manual check-out for incomplete attendance.
     */
    public function manualCheckOut(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'check_out_time' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            if ($attendance->check_out_time) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Employee already checked out',
                    ],
                    400,
                );
            }

            $attendance->update([
                'check_out_time' => $validated['check_out_time'],
                'check_out_notes' => $validated['notes'] ?? null,
                'metadata' => array_merge($attendance->metadata ?? [], [
                    'manual_checkout' => true,
                    'manual_checkout_by' => auth()->id(),
                    'manual_checkout_at' => now()->toISOString(),
                ]),
            ]);

            $attendance->updateTotalHours();
            $attendance->updateStatus();

            return response()->json([
                'success' => true,
                'message' => 'Manual check-out completed successfully',
                'data' => [
                    'total_hours' => $attendance->total_hours,
                    'status' => $attendance->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Manual check-out failed: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get attendance details.
     */
    public function getAttendanceDetails(Attendance $attendance)
    {
        try {
            $attendance->load(['employee.user', 'employee.location']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $attendance->id,
                    'employee' => [
                        'name' => $attendance->employee->full_name,
                        'employee_id' => $attendance->employee->employee_id,
                        'type' => $attendance->employee->employee_type,
                    ],
                    'date' => $attendance->date->format('Y-m-d'),
                    'check_in_time' => $attendance->check_in_time?->format('Y-m-d H:i:s'),
                    'check_out_time' => $attendance->check_out_time?->format('Y-m-d H:i:s'),
                    'total_hours' => $attendance->total_hours,
                    'status' => $attendance->status,
                    'location_verified' => $attendance->location_verified,
                    'check_in_confidence' => $attendance->check_in_confidence,
                    'check_out_confidence' => $attendance->check_out_confidence,
                    'check_in_notes' => $attendance->check_in_notes,
                    'check_out_notes' => $attendance->check_out_notes,
                    'metadata' => $attendance->metadata,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to get attendance details: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Export attendance data to CSV.
     */
    public function exportAttendance(Request $request)
    {
        try {
            $query = Attendance::with(['employee.user', 'employee.location'])
                ->orderBy('date', 'desc')
                ->orderBy('check_in_time', 'desc');

            // Apply filters
            $filters = $request->only(['employee_id', 'start_date', 'end_date']);
            $query = $this->dataTableService->applyFilters($query, $filters);

            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            return $this->exportImportService->exportToCSV($query);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Export failed: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Download attendance import template
     */
    public function downloadTemplate(Request $request)
    {
        try {
            $format = $request->get('format', 'excel');
            return $this->exportImportService->downloadTemplate($format);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to download template: ' . $e->getMessage());
        }
    }

    /**
     * Import attendance data from file
     */
    public function importAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
            'skip_duplicates' => 'boolean',
            'update_existing' => 'boolean',
            'validate_employees' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $options = [
                'skip_duplicates' => $request->boolean('skip_duplicates', true),
                'update_existing' => $request->boolean('update_existing', false),
                'validate_employees' => $request->boolean('validate_employees', true),
            ];

            $result = $this->exportImportService->importAttendance($file, $options);

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            return redirect()->back()->with('success', $result['message']);

        } catch (\Exception $e) {
            $errorMessage = 'Import failed: ' . $e->getMessage();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Get today's work schedule and attendance status
     */
    public function getTodayScheduleAndStatus(Request $request)
    {
        try {
            $user = $request->user();
            $employee = $user->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found',
                ], 404);
            }

            $today = now('Asia/Makassar');

            // Simple fallback schedule for now
            $schedule = [
                'period_name' => 'Jadwal Kerja Umum',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'start_time_formatted' => '08:00',
                'end_time_formatted' => '16:00',
                'periods_count' => 1,
                'periods' => [
                    [
                        'name' => 'Jam Kerja',
                        'start_time' => '08:00',
                        'end_time' => '16:00',
                        'subject' => 'Kerja',
                        'room' => 'Office'
                    ]
                ]
            ];

            // Get today's attendance record (use WITA date)
            $todayDate = $today->format('Y-m-d');
            $attendance = \App\Models\Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $todayDate)
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'schedule' => $schedule,
                    'attendance' => $attendance ? [
                        'check_in_time' => $attendance->check_in_time?->format('H:i'),
                        'check_out_time' => $attendance->check_out_time?->format('H:i'),
                        'status' => $attendance->status ?? 'unknown',
                        'total_hours' => $attendance->total_hours ?? 0,
                        'can_check_out' => $attendance->check_in_time && !$attendance->check_out_time,
                    ] : null,
                    'today_date' => $today->format('Y-m-d'),
                    'today_formatted' => $today->format('l, d F Y'),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Get today schedule error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get today schedule',
            ], 500);
        }
    }

    /**
     * Get current attendance status for authenticated user
     */
    public function getCurrentStatus(Request $request)
    {
        try {
            $user = $request->user();
            $employee = $user->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found',
                ], 404);
            }

            // Get today's attendance record (use WITA timezone)
            $today = now('Asia/Makassar')->startOfDay();
            $todayDate = $today->format('Y-m-d');

            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $todayDate)
                ->first();

            // Determine current status
            $status = 'Not Checked In';
            $badge = 'Not Started';
            $nextAction = 'Check In';
            $canCheckIn = true;
            $canCheckOut = false;

            if ($attendance) {
                if ($attendance->check_in_time && !$attendance->check_out_time) {
                    $status = 'Working';
                    $badge = 'Working';
                    $nextAction = 'Check Out';
                    $canCheckIn = false;
                    $canCheckOut = true;
                } elseif ($attendance->check_in_time && $attendance->check_out_time) {
                    $status = 'Completed';
                    $badge = 'Completed';
                    $nextAction = 'Day Complete';
                    $canCheckIn = false;
                    $canCheckOut = false;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $status,
                    'badge' => $badge,
                    'nextAction' => $nextAction,
                    'canCheckIn' => $canCheckIn,
                    'canCheckOut' => $canCheckOut,
                    'attendance' => $attendance ? [
                        'check_in_time' => $attendance->check_in_time?->format('H:i'),
                        'check_out_time' => $attendance->check_out_time?->format('H:i'),
                        'total_hours' => $attendance->total_hours,
                        'status' => $attendance->status,
                    ] : null,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Get current status error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get current status',
            ], 500);
        }
    }
}
