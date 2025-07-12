<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class AttendanceController extends Controller
{
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
            'employee_id' => 'required|exists:employees,id',
            'face_confidence' => 'required|numeric|min:0|max:1',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string|max:500',
            'metadata' => 'nullable|array'
        ]);

        try {
            DB::beginTransaction();

            $employee = Employee::findOrFail($validated['employee_id']);
            
            // Check if already checked in today
            $todayAttendance = Attendance::getTodayAttendance($employee->id);
            
            if ($todayAttendance && $todayAttendance->check_in_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already checked in today at ' . $todayAttendance->formatted_check_in
                ], 400);
            }

            // Verify location if provided
            $locationVerified = true;
            if (isset($validated['latitude']) && isset($validated['longitude'])) {
                $locationVerified = $this->verifyEmployeeLocation(
                    $employee,
                    $validated['latitude'],
                    $validated['longitude']
                );
            }

            // Create or update attendance record
            $attendance = Attendance::getOrCreateToday($employee->id);
            
            $attendance->update([
                'check_in_time' => now(),
                'check_in_confidence' => $validated['face_confidence'],
                'check_in_latitude' => $validated['latitude'] ?? null,
                'check_in_longitude' => $validated['longitude'] ?? null,
                'location_verified' => $locationVerified,
                'check_in_notes' => $validated['notes'] ?? null,
                'metadata' => array_merge($attendance->metadata ?? [], $validated['metadata'] ?? [])
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Check-in successful',
                'data' => [
                    'attendance_id' => $attendance->id,
                    'check_in_time' => $attendance->check_in_time->format('Y-m-d H:i:s'),
                    'location_verified' => $locationVerified,
                    'confidence' => $validated['face_confidence']
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Check-in failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process check-out.
     */
    public function processCheckOut(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'face_confidence' => 'required|numeric|min:0|max:1',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string|max:500',
            'metadata' => 'nullable|array'
        ]);

        try {
            DB::beginTransaction();

            $employee = Employee::findOrFail($validated['employee_id']);
            
            // Get today's attendance
            $attendance = Attendance::getTodayAttendance($employee->id);
            
            if (!$attendance || !$attendance->check_in_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'No check-in record found for today. Please check in first.'
                ], 400);
            }

            if ($attendance->check_out_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already checked out today at ' . $attendance->formatted_check_out
                ], 400);
            }

            // Verify location if provided
            $locationVerified = $attendance->location_verified; // Keep previous verification
            if (isset($validated['latitude']) && isset($validated['longitude'])) {
                $currentLocationVerified = $this->verifyEmployeeLocation(
                    $employee,
                    $validated['latitude'],
                    $validated['longitude']
                );
                $locationVerified = $locationVerified && $currentLocationVerified;
            }

            // Update attendance record
            $attendance->update([
                'check_out_time' => now(),
                'check_out_confidence' => $validated['face_confidence'],
                'check_out_latitude' => $validated['latitude'] ?? null,
                'check_out_longitude' => $validated['longitude'] ?? null,
                'location_verified' => $locationVerified,
                'check_out_notes' => $validated['notes'] ?? null,
                'metadata' => array_merge($attendance->metadata ?? [], $validated['metadata'] ?? [])
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
                    'confidence' => $validated['face_confidence']
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Check-out failed: ' . $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            $attendance = Attendance::getTodayAttendance($employeeId);
            $employee = Employee::with('user')->find($employeeId);

            if (!$attendance) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'status' => 'not_checked_in',
                        'employee' => [
                            'id' => $employee->id,
                            'name' => $employee->full_name,
                            'employee_id' => $employee->employee_id
                        ],
                        'check_in_time' => null,
                        'check_out_time' => null,
                        'total_hours' => 0,
                        'can_check_in' => true,
                        'can_check_out' => false
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $attendance->isCheckedIn() ? 'checked_in' : 'checked_out',
                    'employee' => [
                        'id' => $employee->id,
                        'name' => $employee->full_name,
                        'employee_id' => $employee->employee_id
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
                    'can_check_out' => $attendance->check_in_time && !$attendance->check_out_time
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance data for DataTables.
     */
    public function getAttendanceData(Request $request)
    {
        $query = Attendance::with(['employee.user'])
                          ->orderBy('date', 'desc')
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
                return '<span class="badge bg-' . $attendance->status_color . '">' . 
                       ucfirst(str_replace('_', ' ', $attendance->status)) . '</span>';
            })
            ->addColumn('actions', function ($attendance) {
                $actions = '<div class="btn-list">';
                
                if (auth()->user()->can('manage_all_attendance')) {
                    $actions .= '<button class="btn btn-sm btn-outline-primary view-details" data-id="' . $attendance->id . '">View</button>';
                    
                    if ($attendance->status === 'incomplete') {
                        $actions .= '<button class="btn btn-sm btn-outline-success manual-checkout" data-id="' . $attendance->id . '">Complete</button>';
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

            $attendances = Attendance::forDateRange($startDate, $endDate);

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
                'statistics' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
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
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            if ($attendance->check_out_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee already checked out'
                ], 400);
            }

            $attendance->update([
                'check_out_time' => $validated['check_out_time'],
                'check_out_notes' => $validated['notes'] ?? null,
                'metadata' => array_merge($attendance->metadata ?? [], [
                    'manual_checkout' => true,
                    'manual_checkout_by' => auth()->id(),
                    'manual_checkout_at' => now()->toISOString()
                ])
            ]);

            $attendance->updateTotalHours();
            $attendance->updateStatus();

            return response()->json([
                'success' => true,
                'message' => 'Manual check-out completed successfully',
                'data' => [
                    'total_hours' => $attendance->total_hours,
                    'status' => $attendance->status
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Manual check-out failed: ' . $e->getMessage()
            ], 500);
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
     * Get attendance details.
     */
    public function getAttendanceDetails(Attendance $attendance)
    {
        try {
            $attendance->load(['employee.user']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $attendance->id,
                    'employee' => [
                        'name' => $attendance->employee->full_name,
                        'employee_id' => $attendance->employee->employee_id,
                        'type' => $attendance->employee->employee_type
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
                    'metadata' => $attendance->metadata
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get attendance details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export attendance data to CSV.
     */
    public function exportAttendance(Request $request)
    {
        try {
            $query = Attendance::with(['employee.user'])
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
                'Notes'
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
                    $attendance->check_in_confidence ? round($attendance->check_in_confidence * 100, 1) . '%' : '',
                    $attendance->check_out_confidence ? round($attendance->check_out_confidence * 100, 1) . '%' : '',
                    trim(($attendance->check_in_notes ?? '') . ' ' . ($attendance->check_out_notes ?? ''))
                ];
            }

            // Generate filename
            $filename = 'attendance_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

            // Create response
            $response = response()->streamDownload(function () use ($csvData) {
                $handle = fopen('php://output', 'w');
                
                foreach ($csvData as $row) {
                    fputcsv($handle, $row);
                }
                
                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

            return $response;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }
}