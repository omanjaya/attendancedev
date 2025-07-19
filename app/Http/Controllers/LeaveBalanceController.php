<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LeaveBalanceController extends Controller
{
    /**
     * Display employee's leave balance dashboard.
     */
    public function index()
    {
        $employeeId = auth()->user()->employee?->id;

        if (! $employeeId) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Employee profile not found. Please contact administrator.');
        }

        $currentYear = date('Y');
        $employee = Employee::with('user')->find($employeeId);

        // Get current year balances
        $leaveBalances = LeaveBalance::where('employee_id', $employeeId)
            ->currentYear()
            ->with('leaveType')
            ->get();

        // Get all leave types to show missing balances
        $leaveTypes = LeaveType::active()->get();

        // Calculate summary statistics
        $totalAllocated = $leaveBalances->sum('allocated_days');
        $totalUsed = $leaveBalances->sum('used_days');
        $totalRemaining = $leaveBalances->sum('remaining_days');

        // Get recent leave balance history (last 5 years)
        $balanceHistory = LeaveBalance::where('employee_id', $employeeId)
            ->with('leaveType')
            ->whereBetween('year', [$currentYear - 4, $currentYear])
            ->orderBy('year', 'desc')
            ->orderBy('leave_type_id')
            ->get()
            ->groupBy('year');

        return view(
            'pages.leave.balance.index',
            compact(
                'employee',
                'leaveBalances',
                'leaveTypes',
                'totalAllocated',
                'totalUsed',
                'totalRemaining',
                'balanceHistory',
                'currentYear',
            ),
        );
    }

    /**
     * Display admin leave balance management interface.
     */
    public function manage()
    {
        $this->authorize('manage_leave_balances');

        $currentYear = date('Y');
        $employees = Employee::with('user')->where('is_active', true)->get();
        $leaveTypes = LeaveType::active()->get();

        return view('pages.leave.balance.manage', compact('employees', 'leaveTypes', 'currentYear'));
    }

    /**
     * Get leave balance data for DataTables in admin interface.
     */
    public function data(Request $request)
    {
        $this->authorize('manage_leave_balances');

        $year = $request->get('year', date('Y'));
        $employeeId = $request->get('employee_id');
        $leaveTypeId = $request->get('leave_type_id');

        $query = LeaveBalance::with(['employee.user', 'leaveType'])->forYear($year);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($leaveTypeId) {
            $query->where('leave_type_id', $leaveTypeId);
        }

        return DataTables::of($query)
            ->addColumn('employee_name', function ($balance) {
                return $balance->employee->full_name;
            })
            ->addColumn('employee_id_display', function ($balance) {
                return $balance->employee->employee_id;
            })
            ->addColumn('leave_type_name', function ($balance) {
                return $balance->leaveType->name;
            })
            ->addColumn('utilization_percentage', function ($balance) {
                if ($balance->allocated_days > 0) {
                    $percentage = ($balance->used_days / $balance->allocated_days) * 100;

                    return round($percentage, 1).'%';
                }

                return '0%';
            })
            ->addColumn('progress_bar', function ($balance) {
                if ($balance->allocated_days > 0) {
                    $percentage = ($balance->used_days / $balance->allocated_days) * 100;
                    $color = $percentage < 50 ? 'success' : ($percentage < 80 ? 'warning' : 'danger');

                    return '<div class="progress progress-sm">
                        <div class="progress-bar bg-'.
                      $color.
                      '" style="width: '.
                      $percentage.
                      '%"></div>
                    </div>';
                }

                return '<div class="progress progress-sm">
                    <div class="progress-bar bg-secondary" style="width: 0%"></div>
                </div>';
            })
            ->addColumn('actions', function ($balance) {
                $actions = '<div class="btn-list">';
                $actions .=
                  '<button class="btn btn-sm btn-primary edit-balance" data-id="'.
                  $balance->id.
                  '">Edit</button>';
                $actions .=
                  '<button class="btn btn-sm btn-info view-history" data-employee-id="'.
                  $balance->employee_id.
                  '" data-leave-type-id="'.
                  $balance->leave_type_id.
                  '">History</button>';
                $actions .= '</div>';

                return $actions;
            })
            ->rawColumns(['progress_bar', 'actions'])
            ->make(true);
    }

    /**
     * Update leave balance.
     */
    public function update(Request $request, LeaveBalance $leaveBalance)
    {
        $this->authorize('manage_leave_balances');

        $validated = $request->validate([
            'allocated_days' => 'required|numeric|min:0|max:365',
            'used_days' => 'required|numeric|min:0',
            'carried_forward' => 'nullable|numeric|min:0|max:365',
            'reason' => 'nullable|string|max:500',
        ]);

        // Validate that used days don't exceed allocated days
        if ($validated['used_days'] > $validated['allocated_days']) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Used days cannot exceed allocated days.',
                ],
                400,
            );
        }

        DB::beginTransaction();
        try {
            $oldBalance = $leaveBalance->toArray();

            $leaveBalance->update([
                'allocated_days' => $validated['allocated_days'],
                'used_days' => $validated['used_days'],
                'carried_forward' => $validated['carried_forward'] ?? $leaveBalance->carried_forward,
                'metadata' => array_merge($leaveBalance->metadata ?? [], [
                    'last_updated_by' => auth()->user()->id,
                    'last_updated_at' => now()->toISOString(),
                    'update_reason' => $validated['reason'] ?? 'Manual adjustment',
                ]),
            ]);

            $leaveBalance->updateRemainingDays();

            // Log the change for audit trail
            $this->logBalanceChange(
                $leaveBalance,
                $oldBalance,
                $validated['reason'] ?? 'Manual adjustment',
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave balance updated successfully.',
                'balance' => [
                    'allocated_days' => $leaveBalance->allocated_days,
                    'used_days' => $leaveBalance->used_days,
                    'remaining_days' => $leaveBalance->remaining_days,
                    'carried_forward' => $leaveBalance->carried_forward,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to update leave balance: '.$e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Create or update leave balance for an employee.
     */
    public function store(Request $request)
    {
        $this->authorize('manage_leave_balances');

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'year' => 'required|integer|min:2020|max:'.(date('Y') + 5),
            'allocated_days' => 'required|numeric|min:0|max:365',
            'used_days' => 'nullable|numeric|min:0',
            'carried_forward' => 'nullable|numeric|min:0|max:365',
            'reason' => 'nullable|string|max:500',
        ]);

        $validated['used_days'] = $validated['used_days'] ?? 0;

        // Check if balance already exists
        $existingBalance = LeaveBalance::where('employee_id', $validated['employee_id'])
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('year', $validated['year'])
            ->first();

        if ($existingBalance) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Leave balance already exists for this employee, leave type, and year.',
                ],
                400,
            );
        }

        // Validate that used days don't exceed allocated days
        if ($validated['used_days'] > $validated['allocated_days']) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Used days cannot exceed allocated days.',
                ],
                400,
            );
        }

        DB::beginTransaction();
        try {
            $leaveBalance = LeaveBalance::create([
                'employee_id' => $validated['employee_id'],
                'leave_type_id' => $validated['leave_type_id'],
                'year' => $validated['year'],
                'allocated_days' => $validated['allocated_days'],
                'used_days' => $validated['used_days'],
                'carried_forward' => $validated['carried_forward'] ?? 0,
                'metadata' => [
                    'created_by' => auth()->user()->id,
                    'created_at' => now()->toISOString(),
                    'creation_reason' => $validated['reason'] ?? 'Manual creation',
                ],
            ]);

            $leaveBalance->updateRemainingDays();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave balance created successfully.',
                'balance' => $leaveBalance->load(['employee.user', 'leaveType']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to create leave balance: '.$e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Bulk create leave balances for all employees.
     */
    public function bulkCreate(Request $request)
    {
        $this->authorize('manage_leave_balances');

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:'.(date('Y') + 5),
            'leave_type_id' => 'required|exists:leave_types,id',
            'allocated_days' => 'required|numeric|min:0|max:365',
            'overwrite_existing' => 'boolean',
        ]);

        $leaveType = LeaveType::find($validated['leave_type_id']);
        $employees = Employee::where('is_active', true)->get();

        $created = 0;
        $updated = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($employees as $employee) {
                $existingBalance = LeaveBalance::where('employee_id', $employee->id)
                    ->where('leave_type_id', $validated['leave_type_id'])
                    ->where('year', $validated['year'])
                    ->first();

                if ($existingBalance) {
                    if ($validated['overwrite_existing'] ?? false) {
                        $existingBalance->update([
                            'allocated_days' => $validated['allocated_days'],
                            'metadata' => array_merge($existingBalance->metadata ?? [], [
                                'bulk_updated_by' => auth()->user()->id,
                                'bulk_updated_at' => now()->toISOString(),
                            ]),
                        ]);
                        $existingBalance->updateRemainingDays();
                        $updated++;
                    }
                } else {
                    LeaveBalance::create([
                        'employee_id' => $employee->id,
                        'leave_type_id' => $validated['leave_type_id'],
                        'year' => $validated['year'],
                        'allocated_days' => $validated['allocated_days'],
                        'used_days' => 0,
                        'remaining_days' => $validated['allocated_days'],
                        'carried_forward' => 0,
                        'metadata' => [
                            'bulk_created_by' => auth()->user()->id,
                            'bulk_created_at' => now()->toISOString(),
                        ],
                    ]);
                    $created++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Bulk operation completed. Created: {$created}, Updated: {$updated}",
                'stats' => [
                    'created' => $created,
                    'updated' => $updated,
                    'total_employees' => $employees->count(),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Bulk operation failed: '.$e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get leave balance history for an employee.
     */
    public function history(Request $request)
    {
        $this->authorize('view_leave_balance_history');

        $employeeId = $request->get('employee_id');
        $leaveTypeId = $request->get('leave_type_id');

        if (! $employeeId || ! $leaveTypeId) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Employee ID and Leave Type ID are required.',
                ],
                400,
            );
        }

        $history = LeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->with('leaveType')
            ->orderBy('year', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    /**
     * Get leave balance for a specific employee and leave type.
     */
    public function getBalance(Request $request)
    {
        $employeeId = $request->get('employee_id');
        $leaveTypeId = $request->get('leave_type_id');
        $year = $request->get('year', date('Y'));

        if (! $employeeId || ! $leaveTypeId) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Employee ID and Leave Type ID are required.',
                ],
                400,
            );
        }

        $balance = LeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();

        if (! $balance) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Leave balance not found.',
                ],
                404,
            );
        }

        return response()->json([
            'success' => true,
            'balance' => [
                'allocated_days' => $balance->allocated_days,
                'used_days' => $balance->used_days,
                'remaining_days' => $balance->remaining_days,
                'carried_forward' => $balance->carried_forward,
            ],
        ]);
    }

    /**
     * Log balance change for audit trail.
     */
    private function logBalanceChange(LeaveBalance $balance, array $oldBalance, string $reason)
    {
        // This would typically log to a separate audit table
        // For now, we'll store it in the metadata
        $changes = [];
        foreach (['allocated_days', 'used_days', 'remaining_days', 'carried_forward'] as $field) {
            if ($oldBalance[$field] != $balance->$field) {
                $changes[$field] = [
                    'old' => $oldBalance[$field],
                    'new' => $balance->$field,
                ];
            }
        }

        if (! empty($changes)) {
            $metadata = $balance->metadata ?? [];
            $metadata['audit_trail'] = $metadata['audit_trail'] ?? [];
            $metadata['audit_trail'][] = [
                'timestamp' => now()->toISOString(),
                'user_id' => auth()->user()->id,
                'reason' => $reason,
                'changes' => $changes,
            ];

            $balance->update(['metadata' => $metadata]);
        }
    }
}
