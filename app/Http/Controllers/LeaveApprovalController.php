<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class LeaveApprovalController extends Controller
{
    /**
     * Display the leave approvals dashboard.
     */
    public function index()
    {
        // Get approval statistics
        $stats = $this->getApprovalStatistics();
        
        return view('pages.leave.approvals.index', compact('stats'));
    }

    /**
     * Get leave requests data for approval DataTables.
     */
    public function data(Request $request)
    {
        $query = Leave::with(['employee.user', 'leaveType'])
            ->select('leaves.*');

        // Filter by status if specified
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addColumn('employee_info', function ($leave) {
                return '
                    <div class="d-flex align-items-center">
                        <img src="' . $leave->employee->photo_url . '" alt="' . $leave->employee->full_name . '" class="avatar avatar-sm me-2">
                        <div>
                            <strong>' . $leave->employee->full_name . '</strong>
                            <div class="text-muted small">' . $leave->employee->employee_id . '</div>
                        </div>
                    </div>
                ';
            })
            ->addColumn('leave_details', function ($leave) {
                $emergencyBadge = $leave->is_emergency ? '<span class="badge bg-red-lt ms-1">Emergency</span>' : '';
                return '
                    <div>
                        <strong>' . $leave->leaveType->name . '</strong>' . $emergencyBadge . '
                        <div class="text-muted small">' . $leave->date_range . '</div>
                        <div class="text-muted small">' . $leave->duration . '</div>
                    </div>
                ';
            })
            ->addColumn('status_badge', function ($leave) {
                return '<span class="badge bg-' . $leave->status_color . '">' . ucfirst($leave->status) . '</span>';
            })
            ->addColumn('priority', function ($leave) {
                if ($leave->is_emergency) {
                    return '<span class="badge bg-red">High</span>';
                } elseif ($leave->start_date <= Carbon::today()->addDays(3)) {
                    return '<span class="badge bg-yellow">Medium</span>';
                } else {
                    return '<span class="badge bg-green">Normal</span>';
                }
            })
            ->addColumn('submitted_date', function ($leave) {
                return $leave->created_at->format('M j, Y') . '<br><small class="text-muted">' . $leave->created_at->diffForHumans() . '</small>';
            })
            ->addColumn('actions', function ($leave) {
                $actions = '<div class="btn-list">';
                
                $actions .= '<a href="' . route('leave.approvals.show', $leave) . '" class="btn btn-sm btn-info">Review</a>';
                
                if ($leave->isPending()) {
                    $actions .= '<button class="btn btn-sm btn-success approve-leave" data-id="' . $leave->id . '">Approve</button>';
                    $actions .= '<button class="btn btn-sm btn-danger reject-leave" data-id="' . $leave->id . '">Reject</button>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['employee_info', 'leave_details', 'status_badge', 'priority', 'submitted_date', 'actions'])
            ->make(true);
    }

    /**
     * Show detailed view of a leave request for approval.
     */
    public function show(Leave $leave)
    {
        $leave->load(['employee.user', 'leaveType', 'approver']);
        
        // Get employee's leave balance for this leave type
        $leaveBalance = $leave->employee->getLeaveBalance($leave->leave_type_id);
        
        // Get employee's recent leave history
        $recentLeaves = Leave::where('employee_id', $leave->employee_id)
            ->where('id', '!=', $leave->id)
            ->with('leaveType')
            ->orderBy('start_date', 'desc')
            ->limit(5)
            ->get();

        return view('pages.leave.approvals.show', compact('leave', 'leaveBalance', 'recentLeaves'));
    }

    /**
     * Approve a leave request.
     */
    public function approve(Request $request, Leave $leave)
    {
        if (!$leave->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This leave request cannot be approved. Current status: ' . $leave->status
            ], 400);
        }

        $validated = $request->validate([
            'approval_notes' => 'nullable|string|max:1000'
        ]);

        $approverId = auth()->user()->employee?->id;
        
        if (!$approverId) {
            return response()->json([
                'success' => false,
                'message' => 'Approver employee profile not found.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Update leave status
            $leave->update([
                'status' => Leave::STATUS_APPROVED,
                'approved_by' => $approverId,
                'approved_at' => now(),
                'approval_notes' => $validated['approval_notes']
            ]);

            // No need to deduct from balance again as it was already done during request creation
            // The balance deduction is finalized by approval

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request approved successfully.',
                'leave' => [
                    'id' => $leave->id,
                    'status' => $leave->status,
                    'employee_name' => $leave->employee->full_name
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve leave request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a leave request.
     */
    public function reject(Request $request, Leave $leave)
    {
        if (!$leave->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This leave request cannot be rejected. Current status: ' . $leave->status
            ], 400);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ]);

        $approverId = auth()->user()->employee?->id;
        
        if (!$approverId) {
            return response()->json([
                'success' => false,
                'message' => 'Approver employee profile not found.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Update leave status
            $leave->update([
                'status' => Leave::STATUS_REJECTED,
                'approved_by' => $approverId,
                'approved_at' => now(),
                'rejection_reason' => $validated['rejection_reason']
            ]);

            // Add days back to employee's balance since request is rejected
            $leaveBalance = $leave->employee->getLeaveBalance($leave->leave_type_id);
            if ($leaveBalance) {
                $leaveBalance->addDays($leave->days_requested);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request rejected successfully.',
                'leave' => [
                    'id' => $leave->id,
                    'status' => $leave->status,
                    'employee_name' => $leave->employee->full_name
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject leave request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk approve multiple leave requests.
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'leave_ids' => 'required|array',
            'leave_ids.*' => 'exists:leaves,id',
            'approval_notes' => 'nullable|string|max:1000'
        ]);

        $approverId = auth()->user()->employee?->id;
        
        if (!$approverId) {
            return response()->json([
                'success' => false,
                'message' => 'Approver employee profile not found.'
            ], 400);
        }

        $leaves = Leave::whereIn('id', $validated['leave_ids'])
            ->where('status', Leave::STATUS_PENDING)
            ->get();

        if ($leaves->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No pending leave requests found to approve.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $approvedCount = 0;
            
            foreach ($leaves as $leave) {
                $leave->update([
                    'status' => Leave::STATUS_APPROVED,
                    'approved_by' => $approverId,
                    'approved_at' => now(),
                    'approval_notes' => $validated['approval_notes']
                ]);
                $approvedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully approved {$approvedCount} leave request(s)."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve leave requests: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get approval statistics for dashboard.
     */
    private function getApprovalStatistics()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'pending_requests' => Leave::pending()->count(),
            'emergency_requests' => Leave::pending()->where('is_emergency', true)->count(),
            'urgent_requests' => Leave::pending()
                ->where('start_date', '<=', $today->copy()->addDays(3))
                ->count(),
            'approved_this_month' => Leave::approved()
                ->where('approved_at', '>=', $thisMonth)
                ->count(),
            'rejected_this_month' => Leave::rejected()
                ->where('approved_at', '>=', $thisMonth)
                ->count(),
            'total_this_month' => Leave::where('created_at', '>=', $thisMonth)->count()
        ];
    }

    /**
     * Get approval analytics data.
     */
    public function analytics()
    {
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            $monthlyData[] = [
                'month' => $month->format('M Y'),
                'approved' => Leave::approved()
                    ->whereBetween('approved_at', [$monthStart, $monthEnd])
                    ->count(),
                'rejected' => Leave::rejected()
                    ->whereBetween('approved_at', [$monthStart, $monthEnd])
                    ->count(),
                'total' => Leave::whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count()
            ];
        }

        return response()->json([
            'monthly_data' => $monthlyData,
            'leave_types' => Leave::join('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
                ->selectRaw('leave_types.name, COUNT(*) as total')
                ->whereYear('leaves.created_at', date('Y'))
                ->groupBy('leave_types.id', 'leave_types.name')
                ->orderBy('total', 'desc')
                ->get()
        ]);
    }
}