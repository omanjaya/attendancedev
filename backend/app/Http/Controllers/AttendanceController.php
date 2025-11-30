<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Repositories\AttendanceRepository;
use App\Repositories\EmployeeRepository;
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

    public function __construct(
        AttendanceRepository $attendanceRepository,
        EmployeeRepository $employeeRepository
    ) {
        $this->attendanceRepository = $attendanceRepository;
        $this->employeeRepository = $employeeRepository;
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
                return $this->errorResponse('Already checked in today at '.$todayAttendance->formatted_check_in);
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

            $attendance->update([
                'check_in_time' => now('Asia/Makassar'),
                'check_in_confidence' => $validated['face_confidence'],
                'check_in_latitude' => $validated['latitude'] ?? null,
                'check_in_longitude' => $validated['longitude'] ?? null,
                'location_verified' => $locationVerified,
                'check_in_notes' => $validated['notes'] ?? null,
                'metadata' => array_merge($attendance->metadata ?? [], $validated['metadata'] ?? []),
            ]);

            DB::commit();

            return $this->successResponse([
                'attendance_id' => $attendance->id,
                'check_in_time' => $attendance->check_in_time->format('Y-m-d H:i:s'),
                'location_verified' => $locationVerified,
                'confidence' => $validated['face_confidence'],
            ], 'Check-in successful');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverErrorResponse('Check-in failed: '.$e->getMessage());
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

            if (! $attendance || ! $attendance->check_in_time) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'No check-in record found for today. Please check in first.',
                    ],
                    400,
                );
            }

            if ($attendance->check_out_time) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Already checked out today at '.$attendance->formatted_check_out,
                    ],
                    400,
                );
            }

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

            // Update attendance record
            $attendance->update([
                'check_out_time' => now('Asia/Makassar'),
                'check_out_confidence' => $validated['face_confidence'],
                'check_out_latitude' => $validated['latitude'] ?? null,
                'check_out_longitude' => $validated['longitude'] ?? null,
                'location_verified' => $locationVerified,
                'check_out_notes' => $validated['notes'] ?? null,
                'metadata' => array_merge($attendance->metadata ?? [], $validated['metadata'] ?? []),
            ]);

            // Calculate total hours and update status
            $attendance->updateTotalHours();
            $attendance->updateStatus();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Check-out successful',
                'data' => [
                    'attendance_id' => $attendance->id,
                    'check_out_time' => $attendance->check_out_time->format('Y-m-d H:i:s'),
                    'total_hours' => $attendance->total_hours,
                    'working_hours_formatted' => $attendance->working_hours_formatted,
                    'status' => $attendance->status,
                    'location_verified' => $locationVerified,
                    'confidence' => $validated['face_confidence'],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Check-out failed: '.$e->getMessage(),
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

            if (! $employeeId) {
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

            if (! $attendance) {
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
                    'can_check_in' => ! $attendance->check_in_time,
                    'can_check_out' => $attendance->check_in_time && ! $attendance->check_out_time,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to get status: '.$e->getMessage(),
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
        if (! $user->hasRole(['superadmin', 'admin'])) {
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
                return '<span class="badge bg-'.
                  $attendance->status_color.
                  '">'.
                  ucfirst(str_replace('_', ' ', $attendance->status)).
                  '</span>';
            })
            ->addColumn('actions', function ($attendance) {
                $actions = '<div class="btn-list">';

                if (auth()->user()->can('manage_attendance_all')) {
                    $actions .=
                      '<button class="btn btn-sm btn-outline-primary view-details" data-id="'.
                      $attendance->id.
                      '">View</button>';

                    if ($attendance->status === 'incomplete') {
                        $actions .=
                          '<button class="btn btn-sm btn-outline-success manual-checkout" data-id="'.
                          $attendance->id.
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

            $attendances = $this->attendanceRepository->getAttendanceForDateRange($startDate, $endDate);

            // Apply role-based filtering to statistics
            $user = auth()->user();
            if (! $user->hasRole(['superadmin', 'admin'])) {
                if ($user->hasRole('kepala_sekolah')) {
                    // Principal can see statistics for their school location
                    $userLocationId = $user->employee?->location_id;
                    if ($userLocationId) {
                        $attendances = $attendances->whereHas('employee', function ($q) use ($userLocationId) {
                            $q->where('location_id', $userLocationId);
                        });
                    } else {
                        // If no location assigned, see no data
                        $attendances = $attendances->whereRaw('1 = 0');
                    }
                } elseif ($user->hasRole(['guru', 'teacher', 'pegawai', 'staff'])) {
                    // Teachers and staff can only see their own statistics
                    $attendances = $attendances->where('employee_id', $user->employee?->id ?? 0);
                } else {
                    // Unknown roles get no access
                    $attendances = $attendances->whereRaw('1 = 0');
                }
            }

            $statistics = [
                'total_records' => $attendances->count(),
                'present_count' => $attendances->where('status', 'present')->count(),
                'late_count' => $attendances->where('status', 'late')->count(),
                'absent_count' => $attendances->where('status', 'absent')->count(),
                'incomplete_count' => $attendances->where('status', 'incomplete')->count(),
                'average_hours' => round($attendances->avg('total_hours') ?? 0, 2),
                'total_hours' => round($attendances->sum('total_hours') ?? 0, 2),
            ];

            return response()->json([
                'success' => true,
                'statistics' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to get statistics: '.$e->getMessage(),
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
                    'message' => 'Manual check-out failed: '.$e->getMessage(),
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
        if (! $employee->location) {
            return true; // No location restriction
        }

        // For now, return true - implement proper geofencing logic
        // You could use the Haversine formula to calculate distance
        return true;
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
                    'message' => 'Failed to get attendance details: '.$e->getMessage(),
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
                      ? round($attendance->check_in_confidence * 100, 1).'%'
                      : '',
                    $attendance->check_out_confidence
                      ? round($attendance->check_out_confidence * 100, 1).'%'
                      : '',
                    trim(($attendance->check_in_notes ?? '').' '.($attendance->check_out_notes ?? '')),
                ];
            }

            // Generate filename
            $filename = 'attendance_export_'.now()->format('Y-m-d_H-i-s').'.csv';

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
                    'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                ],
            );

            return $response;
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Export failed: '.$e->getMessage(),
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
            \Log::error('Get today schedule error: '.$e->getMessage());

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

            if (! $employee) {
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
                if ($attendance->check_in_time && ! $attendance->check_out_time) {
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
            \Log::error('Get current status error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get current status',
            ], 500);
        }
    }
}
