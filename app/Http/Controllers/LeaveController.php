<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Imports\LeaveImport;
use App\Exports\LeaveExportTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class LeaveController extends Controller
{
    /**
     * Display a listing of employee's leave requests.
     */
    public function index()
    {
        return view('pages.leave.index');
    }

    /**
     * Get leave requests data for DataTables.
     */
    public function data(Request $request)
    {
        $employeeId = auth()->user()->employee?->id;

        if (! $employeeId) {
            return DataTables::of(collect([]))->make(true);
        }

        $query = Leave::with(['leaveType', 'approver'])
            ->where('employee_id', $employeeId)
            ->select('leaves.*');

        return DataTables::of($query)
            ->addColumn('leave_type', function ($leave) {
                return $leave->leaveType->name;
            })
            ->addColumn('date_range', function ($leave) {
                return $leave->date_range;
            })
            ->addColumn('duration', function ($leave) {
                return $leave->duration;
            })
            ->addColumn('status_badge', function ($leave) {
                return '<span class="badge bg-'.
                  $leave->status_color.
                  '">'.
                  ucfirst($leave->status).
                  '</span>';
            })
            ->addColumn('approver_name', function ($leave) {
                return $leave->approver ? $leave->approver->full_name : '-';
            })
            ->addColumn('actions', function ($leave) {
                $actions = '<div class="btn-list">';

                $actions .=
                  '<a href="'.route('leave.show', $leave).'" class="btn btn-sm btn-info">View</a>';

                if ($leave->canBeCancelled()) {
                    $actions .=
                      '<button class="btn btn-sm btn-danger cancel-leave" data-id="'.
                      $leave->id.
                      '">Cancel</button>';
                }

                $actions .= '</div>';

                return $actions;
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new leave request.
     */
    public function create()
    {
        $leaveTypes = LeaveType::active()->get();
        $employeeId = auth()->user()->employee?->id;

        if (! $employeeId) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Employee profile not found. Please contact administrator.');
        }

        // Get current year leave balances
        $leaveBalances = LeaveBalance::where('employee_id', $employeeId)
            ->currentYear()
            ->with('leaveType')
            ->get()
            ->keyBy('leave_type_id');

        return view('pages.leave.create', compact('leaveTypes', 'leaveBalances'));
    }

    /**
     * Store a newly created leave request.
     */
    public function store(Request $request)
    {
        $employeeId = auth()->user()->employee?->id;

        if (! $employeeId) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Employee profile not found. Please contact administrator.');
        }

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
            'is_emergency' => 'boolean',
        ]);

        // Calculate working days
        $workingDays = Leave::calculateWorkingDays($validated['start_date'], $validated['end_date']);

        if ($workingDays <= 0) {
            return back()->withInput()->with('error', 'Selected dates do not include any working days.');
        }

        // Check leave balance
        $leaveBalance = LeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $validated['leave_type_id'])
            ->currentYear()
            ->first();

        if ($leaveBalance && ! $leaveBalance->canTakeDays($workingDays)) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    "Insufficient leave balance. You have {$leaveBalance->remaining_days} days remaining.",
                );
        }

        // Check for overlapping leaves
        $overlapping = Leave::where('employee_id', $employeeId)
            ->where('status', '!=', Leave::STATUS_REJECTED)
            ->where('status', '!=', Leave::STATUS_CANCELLED)
            ->where(function ($query) use ($validated) {
                $query
                    ->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('start_date', '<=', $validated['start_date'])->where(
                            'end_date',
                            '>=',
                            $validated['end_date'],
                        );
                    });
            })
            ->exists();

        if ($overlapping) {
            return back()
                ->withInput()
                ->with('error', 'You already have a leave request for the selected dates.');
        }

        DB::beginTransaction();
        try {
            // Create leave request
            $leave = Leave::create([
                'employee_id' => $employeeId,
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'days_requested' => $workingDays,
                'reason' => $validated['reason'],
                'is_emergency' => $validated['is_emergency'] ?? false,
                'status' => Leave::STATUS_PENDING,
            ]);

            // If leave balance exists, temporarily deduct days (will be finalized on approval)
            if ($leaveBalance) {
                $leaveBalance->deductDays($workingDays);
            }

            DB::commit();

            return redirect()
                ->route('leave.requests')
                ->with('success', 'Leave request submitted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Failed to submit leave request: '.$e->getMessage());
        }
    }

    /**
     * Display the specified leave request.
     */
    public function show(Leave $leave)
    {
        // Check if user can view this leave
        if (
            ! auth()->user()->can('view_leave_all') &&
            $leave->employee_id !== auth()->user()->employee?->id
        ) {
            abort(403, 'Unauthorized to view this leave request.');
        }

        $leave->load(['employee.user', 'leaveType', 'approver']);

        return view('pages.leave.show', compact('leave'));
    }

    /**
     * Cancel a leave request.
     */
    public function cancel(Leave $leave)
    {
        // Check if user can cancel this leave
        if ($leave->employee_id !== auth()->user()->employee?->id) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Unauthorized to cancel this leave request.',
                ],
                403,
            );
        }

        if (! $leave->canBeCancelled()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'This leave request cannot be cancelled.',
                ],
                400,
            );
        }

        DB::beginTransaction();
        try {
            // Update leave status
            $leave->update(['status' => Leave::STATUS_CANCELLED]);

            // Add days back to balance if leave was approved
            if ($leave->isApproved()) {
                $leaveBalance = $leave->employee->getLeaveBalance($leave->leave_type_id);
                if ($leaveBalance) {
                    $leaveBalance->addDays($leave->days_requested);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request cancelled successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to cancel leave request: '.$e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get leave balance for a specific leave type.
     */
    public function getBalance(Request $request)
    {
        $employeeId = auth()->user()->employee?->id;
        $leaveTypeId = $request->leave_type_id;

        if (! $employeeId || ! $leaveTypeId) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Missing required parameters.',
                ],
                400,
            );
        }

        $balance = LeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->currentYear()
            ->first();

        if (! $balance) {
            return response()->json([
                'success' => false,
                'message' => 'Leave balance not found.',
                'balance' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'balance' => [
                'allocated_days' => $balance->allocated_days,
                'used_days' => $balance->used_days,
                'remaining_days' => $balance->remaining_days,
            ],
        ]);
    }

    /**
     * Calculate working days between two dates.
     */
    public function calculateDays(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $workingDays = Leave::calculateWorkingDays($validated['start_date'], $validated['end_date']);

        return response()->json([
            'working_days' => $workingDays,
        ]);
    }

    /**
     * Get calendar data for leave calendar view.
     */
    public function calendarData(Request $request)
    {
        $employeeId = auth()->user()->employee?->id;

        if (! $employeeId) {
            return response()->json([]);
        }

        // Get leave data for calendar
        $leaves = Leave::where('employee_id', $employeeId)
            ->with(['leaveType'])
            ->whereIn('status', [Leave::STATUS_APPROVED, Leave::STATUS_PENDING])
            ->get();

        $events = [];
        foreach ($leaves as $leave) {
            $events[] = [
                'id' => $leave->id,
                'title' => $leave->leaveType->name,
                'start' => $leave->start_date->format('Y-m-d'),
                'end' => $leave->end_date->addDay()->format('Y-m-d'), // FullCalendar end date is exclusive
                'backgroundColor' => $leave->status === Leave::STATUS_APPROVED ? '#10b981' : '#f59e0b',
                'borderColor' => $leave->status === Leave::STATUS_APPROVED ? '#059669' : '#d97706',
                'extendedProps' => [
                    'status' => $leave->status,
                    'days' => $leave->days_requested,
                    'reason' => $leave->reason,
                ],
            ];
        }

        return response()->json($events);
    }

    /**
     * Get leave statistics for calendar dashboard.
     */
    public function calendarStats(Request $request)
    {
        $employeeId = auth()->user()->employee?->id;

        if (! $employeeId) {
            return response()->json([
                'total_leaves' => 0,
                'approved_leaves' => 0,
                'pending_leaves' => 0,
                'active_leaves' => 0,
            ]);
        }

        $currentYear = now()->year;

        $stats = [
            'total_leaves' => Leave::where('employee_id', $employeeId)
                ->whereYear('created_at', $currentYear)
                ->count(),
            'approved_leaves' => Leave::where('employee_id', $employeeId)
                ->where('status', Leave::STATUS_APPROVED)
                ->whereYear('created_at', $currentYear)
                ->count(),
            'pending_leaves' => Leave::where('employee_id', $employeeId)
                ->where('status', Leave::STATUS_PENDING)
                ->count(),
            'active_leaves' => Leave::where('employee_id', $employeeId)
                ->where('status', Leave::STATUS_APPROVED)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get leave details for calendar popup.
     */
    public function calendarDetails($id)
    {
        $leave = Leave::with(['leaveType', 'approver'])
            ->where('id', $id)
            ->where('employee_id', auth()->user()->employee?->id)
            ->first();

        if (! $leave) {
            return response()->json([
                'success' => false,
                'message' => 'Leave not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $leave->id,
                'type' => $leave->leaveType->name,
                'start_date' => $leave->start_date->format('d M Y'),
                'end_date' => $leave->end_date->format('d M Y'),
                'days' => $leave->days_requested,
                'status' => $leave->status,
                'reason' => $leave->reason,
                'approver' => $leave->approver?->first_name.' '.$leave->approver?->last_name,
                'created_at' => $leave->created_at->format('d M Y H:i'),
            ],
        ]);
    }

    /**
     * Download leave import template
     */
    public function downloadTemplate(Request $request)
    {
        try {
            $format = $request->get('format', 'excel');

            if ($format === 'excel') {
                return Excel::download(new LeaveExportTemplate(), 'leave_import_template.xlsx');
            } else {
                // Generate CSV template
                $csvData = [
                    ['Employee ID', 'Leave Type', 'Start Date', 'End Date', 'Days', 'Reason', 'Status', 'Applied Date', 'Notes', 'Emergency', 'Half Day'],
                    ['EMP001', 'annual', '2025-02-01', '2025-02-05', '5', 'Family vacation planned for months', 'pending', '2025-01-15', 'Personal time off', 'false', 'false'],
                    ['EMP002', 'sick', '2025-01-22', '2025-01-24', '3', 'Medical treatment required for back injury', 'approved', '2025-01-21', 'Medical certificate provided', 'true', 'false'],
                    ['EMP003', 'maternity', '2025-03-01', '2025-05-30', '90', 'Maternity leave for newborn care', 'approved', '2025-01-10', 'HR approved extended leave', 'false', 'false']
                ];

                return response()->streamDownload(
                    function () use ($csvData) {
                        $handle = fopen('php://output', 'w');
                        foreach ($csvData as $row) {
                            fputcsv($handle, $row);
                        }
                        fclose($handle);
                    },
                    'leave_import_template.csv',
                    ['Content-Type' => 'text/csv']
                );
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to download template: ' . $e->getMessage());
        }
    }

    /**
     * Import leave data from file
     */
    public function importLeave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // Max 10MB
            'skip_duplicates' => 'boolean',
            'auto_approve' => 'boolean',
            'validate_balance' => 'boolean',
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
                'auto_approve' => $request->boolean('auto_approve', false),
                'validate_balance' => $request->boolean('validate_balance', true),
                'validate_employees' => $request->boolean('validate_employees', true),
                'default_status' => $request->boolean('auto_approve', false) ? 'approved' : 'pending',
            ];

            $import = new LeaveImport($options);
            Excel::import($import, $file);
            
            $results = $import->getResults();

            $message = "Import completed! {$results['success']} leave requests imported successfully.";
            
            if ($results['skipped'] > 0) {
                $message .= " {$results['skipped']} requests skipped.";
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
}
