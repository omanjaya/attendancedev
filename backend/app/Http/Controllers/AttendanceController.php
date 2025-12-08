<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\MonthlySchedule;
use App\Models\EmployeeMonthlySchedule;
use App\Repositories\AttendanceRepository;
use App\Repositories\EmployeeRepository;
use App\Services\AttendanceScheduleService;
use App\Traits\ApiResponseTrait;
use App\Imports\AttendanceImport;
use App\Exports\AttendanceExportTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class AttendanceController extends Controller
{
    use ApiResponseTrait;

    private AttendanceRepository $attendanceRepository;

    private EmployeeRepository $employeeRepository;
    
    private AttendanceScheduleService $scheduleService;

    public function __construct(
        AttendanceRepository $attendanceRepository,
        EmployeeRepository $employeeRepository,
        AttendanceScheduleService $scheduleService
    ) {
        $this->attendanceRepository = $attendanceRepository;
        $this->employeeRepository = $employeeRepository;
        $this->scheduleService = $scheduleService;
    }

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
            $workingDayValidation = $this->validateWorkingDay($employee);

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
                $locationVerified = $this->verifyEmployeeLocation(
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
            $workingDayValidation = $this->validateWorkingDay($employee);

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
                $currentLocationVerified = $this->verifyEmployeeLocation(
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

        // Apply role-based filtering FIRST
        $user = auth()->user();
        if (!$user->hasRole(['superadmin', 'admin'])) {
            if ($user->hasRole('kepala_sekolah')) {
                // Principal can see attendance for their school location
                $userLocationId = $user->employee?->location_id;
                if ($userLocationId) {
                    $query->whereHas('employee', function ($q) use ($userLocationId) {
                        $q->where('location_id', $userLocationId);
                    });
                } else {
                    // If no location assigned, see no data
                    $query->whereRaw('1 = 0');
                }
            } elseif ($user->hasRole(['guru', 'teacher', 'pegawai', 'staff'])) {
                // Teachers and staff can only see their own attendance
                $query->where('employee_id', $user->employee?->id ?? 0);
            } else {
                // Unknown roles get no access
                $query->whereRaw('1 = 0');
            }
        }

        $query->orderBy('date', 'desc')
            ->orderBy('check_in_time', 'desc');

        // Filter by employee if specified
        if ($request->has('employee_id') && $request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        return DataTables::of($query)
            ->addColumn('employee_name', function ($attendance) {
                return $attendance->employee->full_name;
            })
            ->addColumn('employee_id', function ($attendance) {
                return $attendance->employee->employee_id;
            })
            ->addColumn('date_formatted', function ($attendance) {
                return $attendance->date->format('M d, Y');
            })
            ->addColumn('check_in_formatted', function ($attendance) {
                return $attendance->formatted_check_in ?? '-';
            })
            ->addColumn('check_out_formatted', function ($attendance) {
                return $attendance->formatted_check_out ?? '-';
            })
            ->addColumn('status_badge', function ($attendance) {
                return '<span class="badge bg-' .
                    $attendance->status_color .
                    '">' .
                    ucfirst(str_replace('_', ' ', $attendance->status)) .
                    '</span>';
            })
            ->addColumn('actions', function ($attendance) {
                $actions = '<div class="btn-list">';

                if (auth()->user()->can('manage_attendance_all')) {
                    $actions .=
                        '<button class="btn btn-sm btn-outline-primary view-details" data-id="' .
                        $attendance->id .
                        '">View</button>';

                    if ($attendance->status === 'incomplete') {
                        $actions .=
                            '<button class="btn btn-sm btn-outline-success manual-checkout" data-id="' .
                            $attendance->id .
                            '">Complete</button>';
                    }
                }

                $actions .= '</div>';

                return $actions;
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Get attendance statistics.
     */
    public function getStatistics(Request $request)
    {
        try {
            $startDate = $request->input('start_date', today()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', today()->format('Y-m-d'));

            $query = DB::table('attendances')
                ->whereBetween('date', [$startDate, $endDate])
                ->whereNull('deleted_at');

            // Apply role-based filtering
            $user = auth()->user();
            if (!$user->hasRole(['superadmin', 'admin'])) {
                if ($user->hasRole('kepala_sekolah')) {
                    $userLocationId = $user->employee?->location_id;
                    if ($userLocationId) {
                        // Join with employees table to filter by location
                        $query->join('employees', 'attendances.employee_id', '=', 'employees.id')
                            ->where('employees.location_id', $userLocationId);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                } elseif ($user->hasRole(['guru', 'teacher', 'pegawai', 'staff'])) {
                    $query->where('attendances.employee_id', $user->employee?->id ?? 0);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            $stats = $query->selectRaw('
                COUNT(*) as total_records,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN status = "incomplete" THEN 1 ELSE 0 END) as incomplete_count,
                AVG(total_hours) as average_hours,
                SUM(total_hours) as total_hours
            ')->first();

            $statistics = [
                'total_records' => $stats->total_records,
                'present_count' => $stats->present_count,
                'late_count' => $stats->late_count,
                'absent_count' => $stats->absent_count,
                'incomplete_count' => $stats->incomplete_count,
                'average_hours' => round($stats->average_hours ?? 0, 2),
                'total_hours' => round($stats->total_hours ?? 0, 2),
            ];

            return response()->json([
                'success' => true,
                'statistics' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to get statistics: ' . $e->getMessage(),
                ],
                500,
            );
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
     * Verify employee location.
     */
    private function verifyEmployeeLocation($employee, $latitude, $longitude)
    {
        // Basic location verification - can be enhanced with proper geofencing
        if (!$employee->location) {
            return true; // No location restriction
        }

        // For now, return true - implement proper geofencing logic
        // You could use the Haversine formula to calculate distance
        return true;
    }

    /**
     * Get employee's active monthly schedule for a specific date
     *
     * @param Employee $employee
     * @param Carbon|null $date
     * @return MonthlySchedule|null
     */
    private function getEmployeeScheduleForDate(Employee $employee, ?Carbon $date = null): ?MonthlySchedule
    {
        $date = $date ?? now('Asia/Makassar');
        $month = $date->month;
        $year = $date->year;

        // Get the assigned schedule for this employee in specified month/year
        $employeeSchedule = EmployeeMonthlySchedule::where('employee_id', $employee->id)
            ->whereHas('monthlySchedule', function ($query) use ($month, $year) {
                $query->where('month', $month)
                      ->where('year', $year)
                      ->where('is_active', true);
            })
            ->with('monthlySchedule')
            ->first();

        return $employeeSchedule?->monthlySchedule;
    }

    /**
     * Check if user can bypass schedule validation
     *
     * @param User|null $user
     * @param Employee $employee
     * @param string $reason
     * @return bool
     */
    private function canBypassValidation(?User $user, Employee $employee, string $reason = 'general'): bool
    {
        // Check maintenance mode
        if (config('attendance.maintenance_mode', false)) {
            if (config('attendance.log_bypass', true)) {
                \Log::warning('Attendance validation bypassed: MAINTENANCE MODE', [
                    'employee_id' => $employee->id,
                    'reason' => 'maintenance_mode',
                ]);
            }
            return true;
        }

        // Check if strict mode is disabled globally
        if (!config('attendance.strict_mode', true)) {
            if (config('attendance.log_bypass', true)) {
                \Log::info('Attendance validation bypassed: STRICT MODE DISABLED', [
                    'employee_id' => $employee->id,
                    'reason' => 'strict_mode_disabled',
                ]);
            }
            return true;
        }

        // Check user role bypass
        if ($user) {
            $bypassRoles = config('attendance.bypass_roles', ['super_admin']);
            if ($user->hasAnyRole($bypassRoles)) {
                if (config('attendance.log_bypass', true)) {
                    \Log::info('Attendance validation bypassed: ROLE PRIVILEGE', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'user_roles' => $user->roles->pluck('name')->toArray(),
                        'employee_id' => $employee->id,
                        'reason' => $reason,
                        'timestamp' => now()->toISOString(),
                    ]);
                }
                return true;
            }
        }

        // Check employee metadata bypass flag
        if ($employee->metadata && isset($employee->metadata['bypass_schedule_validation'])) {
            if ($employee->metadata['bypass_schedule_validation'] === true) {
                if (config('attendance.log_bypass', true)) {
                    \Log::info('Attendance validation bypassed: EMPLOYEE FLAG', [
                        'employee_id' => $employee->id,
                        'reason' => 'employee_metadata_flag',
                    ]);
                }
                return true;
            }
        }

        return false;
    }

    /**
     * Validate if current date is a working day according to employee's schedule
     *
     * @param Employee $employee
     * @param Carbon|null $date
     * @return array ['valid' => bool, 'message' => string|null, 'schedule' => MonthlySchedule|null]
     */
    private function validateWorkingDay(Employee $employee, ?Carbon $date = null): array
    {
        $date = $date ?? now('Asia/Makassar');
        $dateStr = $date->toDateString(); // Format: "2025-02-01"

        // ===== CHECK BYPASS CONDITIONS =====
        $user = auth()->user();
        if ($this->canBypassValidation($user, $employee, 'working_day_validation')) {
            return [
                'valid' => true,
                'message' => null,
                'schedule' => null,
                'bypass' => true,
            ];
        }

        // ===== WORKING DAY VALIDATION (if not bypassed) =====

        // Check if working day validation is enabled
        if (!config('attendance.validate_working_days', true)) {
            return [
                'valid' => true,
                'message' => null,
                'schedule' => null,
            ];
        }

        // Get employee's schedule
        $schedule = $this->getEmployeeScheduleForDate($employee, $date);

        // If no schedule assigned, REJECT attendance (strict mode for regular employees)
        if (!$schedule) {
            return [
                'valid' => false,
                'message' => 'Anda belum memiliki jadwal kerja untuk bulan ini. Silakan hubungi admin untuk pengaturan jadwal.',
                'schedule' => null,
            ];
        }

        // Check if today is in the working_days array
        $workingDays = $schedule->working_days ?? [];
        $isWorkingDay = in_array($dateStr, $workingDays);

        if (!$isWorkingDay) {
            return [
                'valid' => false,
                'message' => 'Hari ini bukan hari kerja menurut jadwal Anda. Silakan hubungi admin jika terjadi kesalahan.',
                'schedule' => $schedule,
            ];
        }

        return [
            'valid' => true,
            'message' => null,
            'schedule' => $schedule,
        ];
    }

    /**
     * Validate if current time is within check-in window
     *
     * @param MonthlySchedule|null $schedule
     * @param Carbon|null $time
     * @return array ['valid' => bool, 'message' => string|null, 'is_late' => bool]
     */
    private function validateCheckInWindow(?MonthlySchedule $schedule, ?Carbon $time = null): array
    {
        // Check if time window validation is enabled
        if (!config('attendance.validate_time_windows', true)) {
            return [
                'valid' => true,
                'message' => null,
                'is_late' => false,
            ];
        }

        // ===== BYPASS FOR ADMIN ROLES (No schedule = admin bypass) =====
        if (!$schedule) {
            return [
                'valid' => true,
                'message' => null,
                'is_late' => false,
                'bypass' => true,
            ];
        }

        $time = $time ?? now('Asia/Makassar');
        $currentTime = $time->format('H:i:s');

        // Parse schedule times
        $checkinStart = Carbon::createFromFormat('H:i', $schedule->checkin_start_time)->format('H:i:s');
        $checkinEnd = Carbon::createFromFormat('H:i', $schedule->checkin_end_time)->format('H:i:s');
        $workStart = Carbon::createFromFormat('H:i', $schedule->default_start_time)->format('H:i:s');

        // Check if within allowed window
        if ($currentTime < $checkinStart) {
            return [
                'valid' => false,
                'message' => "Check-in hanya diperbolehkan mulai pukul {$schedule->checkin_start_time}. Saat ini terlalu awal.",
                'is_late' => false,
            ];
        }

        if ($currentTime > $checkinEnd) {
            return [
                'valid' => false,
                'message' => "Window check-in telah berakhir pada pukul {$schedule->checkin_end_time}. Silakan hubungi admin.",
                'is_late' => true,
            ];
        }

        // Check if late (after work start time)
        $isLate = $currentTime > $workStart;

        return [
            'valid' => true,
            'message' => $isLate ? "Anda terlambat. Jam kerja dimulai pada {$schedule->default_start_time}." : null,
            'is_late' => $isLate,
        ];
    }

    /**
     * Validate if current time is within check-out window
     *
     * @param MonthlySchedule|null $schedule
     * @param Carbon|null $time
     * @return array ['valid' => bool, 'message' => string|null, 'is_early' => bool]
     */
    private function validateCheckOutWindow(?MonthlySchedule $schedule, ?Carbon $time = null): array
    {
        // Check if time window validation is enabled
        if (!config('attendance.validate_time_windows', true)) {
            return [
                'valid' => true,
                'message' => null,
                'is_early' => false,
            ];
        }

        // ===== BYPASS FOR ADMIN ROLES (No schedule = admin bypass) =====
        if (!$schedule) {
            return [
                'valid' => true,
                'message' => null,
                'is_early' => false,
                'bypass' => true,
            ];
        }

        $time = $time ?? now('Asia/Makassar');
        $currentTime = $time->format('H:i:s');

        // Parse schedule times
        $checkoutStart = Carbon::createFromFormat('H:i', $schedule->checkout_start_time)->format('H:i:s');
        $checkoutEnd = Carbon::createFromFormat('H:i', $schedule->checkout_end_time)->format('H:i:s');
        $workEnd = Carbon::createFromFormat('H:i', $schedule->default_end_time)->format('H:i:s');

        // Check if within allowed window
        if ($currentTime < $checkoutStart) {
            return [
                'valid' => false,
                'message' => "Check-out hanya diperbolehkan mulai pukul {$schedule->checkout_start_time}. Saat ini terlalu awal.",
                'is_early' => true,
            ];
        }

        if ($currentTime > $checkoutEnd) {
            return [
                'valid' => false,
                'message' => "Window check-out telah berakhir pada pukul {$schedule->checkout_end_time}. Silakan hubungi admin.",
                'is_early' => false,
            ];
        }

        // Check if early (before work end time)
        $isEarly = $currentTime < $workEnd;

        return [
            'valid' => true,
            'message' => $isEarly ? "Anda pulang lebih awal. Jam kerja berakhir pada {$schedule->default_end_time}." : null,
            'is_early' => $isEarly,
        ];
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
            if ($request->has('employee_id') && $request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->has('start_date') && $request->start_date) {
                $query->whereDate('date', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date) {
                $query->whereDate('date', '<=', $request->end_date);
            }

            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            $attendances = $query->get();

            // Create CSV content
            $csvData = [];
            $csvData[] = [
                'Date',
                'Employee Name',
                'Employee ID',
                'Check In Time',
                'Check Out Time',
                'Total Hours',
                'Status',
                'Location Verified',
                'Check In Confidence',
                'Check Out Confidence',
                'Notes',
            ];

            foreach ($attendances as $attendance) {
                $csvData[] = [
                    $attendance->date->format('Y-m-d'),
                    $attendance->employee->full_name,
                    $attendance->employee->employee_id,
                    $attendance->check_in_time?->format('Y-m-d H:i:s') ?? '',
                    $attendance->check_out_time?->format('Y-m-d H:i:s') ?? '',
                    $attendance->total_hours ?? 0,
                    ucfirst(str_replace('_', ' ', $attendance->status)),
                    $attendance->location_verified ? 'Yes' : 'No',
                    $attendance->check_in_confidence
                    ? round($attendance->check_in_confidence * 100, 1) . '%'
                    : '',
                    $attendance->check_out_confidence
                    ? round($attendance->check_out_confidence * 100, 1) . '%'
                    : '',
                    trim(($attendance->check_in_notes ?? '') . ' ' . ($attendance->check_out_notes ?? '')),
                ];
            }

            // Generate filename
            $filename = 'attendance_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

            // Create response
            $response = response()->streamDownload(
                function () use ($csvData) {
                    $handle = fopen('php://output', 'w');

                    foreach ($csvData as $row) {
                        fputcsv($handle, $row);
                    }

                    fclose($handle);
                },
                $filename,
                [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ],
            );

            return $response;
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

            if ($format === 'excel') {
                return Excel::download(new AttendanceExportTemplate(), 'attendance_import_template.xlsx');
            } else {
                // Generate CSV template
                $csvData = [
                    ['Employee ID', 'Date', 'Check In', 'Check Out', 'Status', 'Working Hours', 'Notes', 'Reason'],
                    ['EMP001', '2025-01-20', '08:00', '17:00', 'present', '9.0', 'Regular working day', 'Bulk import example'],
                    ['EMP002', '2025-01-20', '08:30', '17:30', 'late', '9.0', 'Late arrival', 'Traffic jam'],
                    ['EMP003', '2025-01-20', '09:00', '', 'incomplete', '', 'Forgot to check out', 'System issue']
                ];

                return response()->streamDownload(
                    function () use ($csvData) {
                        $handle = fopen('php://output', 'w');
                        foreach ($csvData as $row) {
                            fputcsv($handle, $row);
                        }
                        fclose($handle);
                    },
                    'attendance_import_template.csv',
                    ['Content-Type' => 'text/csv']
                );
            }
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
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // Max 10MB
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

            $import = new AttendanceImport($options);
            Excel::import($import, $file);

            $results = $import->getResults();

            $message = "Import completed! {$results['success']} records imported successfully.";

            if ($results['skipped'] > 0) {
                $message .= " {$results['skipped']} records skipped.";
            }

            if (count($results['errors']) > 0) {
                $message .= " " . count($results['errors']) . " errors occurred.";
            }

            $responseData = [
                'success' => true,
                'message' => $message,
                'data' => [
                    'summary' => [
                        'total_processed' => $results['success'] + $results['skipped'] + count($results['errors']),
                        'successful' => $results['success'],
                        'skipped' => $results['skipped'],
                        'failed' => count($results['errors']),
                    ],
                    'errors' => $results['errors'],
                    'warnings' => $results['warnings'] ?? []
                ]
            ];

            if ($request->expectsJson()) {
                return response()->json($responseData);
            }

            return redirect()->back()->with('success', $message);

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
