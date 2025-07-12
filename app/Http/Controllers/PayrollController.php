<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Services\PayrollCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PayrollController extends Controller
{
    protected $payrollCalculationService;

    public function __construct(PayrollCalculationService $payrollCalculationService)
    {
        $this->middleware('auth');
        $this->payrollCalculationService = $payrollCalculationService;
    }

    /**
     * Display a listing of payroll records.
     */
    public function index(Request $request)
    {
        Gate::authorize('view_payroll');

        $query = Payroll::with(['employee', 'approver', 'processor']);

        // Filter by employee if provided
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payroll period if provided
        if ($request->filled('period_start') && $request->filled('period_end')) {
            $query->whereBetween('payroll_period_start', [
                $request->period_start,
                $request->period_end
            ]);
        }

        // Filter by pay date if provided
        if ($request->filled('pay_date_start') && $request->filled('pay_date_end')) {
            $query->whereBetween('pay_date', [
                $request->pay_date_start,
                $request->pay_date_end
            ]);
        }

        $payrolls = $query->orderBy('payroll_period_start', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $employees = Employee::where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'employee_id']);

        return view('pages.payroll.index', compact('payrolls', 'employees'));
    }

    /**
     * Get payroll data for DataTables.
     */
    public function data(Request $request): JsonResponse
    {
        Gate::authorize('view_payroll');

        $query = Payroll::with(['employee', 'approver', 'processor'])
            ->select('payrolls.*');

        // Apply filters
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('period_start') && $request->filled('period_end')) {
            $query->whereBetween('payroll_period_start', [
                $request->period_start,
                $request->period_end
            ]);
        }

        return datatables($query)
            ->addColumn('employee_name', function ($payroll) {
                return $payroll->employee ? $payroll->employee->full_name : 'N/A';
            })
            ->addColumn('employee_id', function ($payroll) {
                return $payroll->employee ? $payroll->employee->employee_id : 'N/A';
            })
            ->addColumn('period', function ($payroll) {
                return $payroll->payroll_period;
            })
            ->addColumn('gross_salary_formatted', function ($payroll) {
                return $payroll->formatted_gross_salary;
            })
            ->addColumn('net_salary_formatted', function ($payroll) {
                return $payroll->formatted_net_salary;
            })
            ->addColumn('status_badge', function ($payroll) {
                return '<span class="badge bg-' . $payroll->status_color . '">' . ucfirst($payroll->status) . '</span>';
            })
            ->addColumn('actions', function ($payroll) {
                $actions = '<div class="btn-group" role="group">';
                
                if (auth()->user()->can('view_payroll')) {
                    $actions .= '<a href="' . route('payroll.show', $payroll) . '" class="btn btn-sm btn-outline-primary">View</a>';
                }
                
                if (auth()->user()->can('edit_payroll') && $payroll->canBeEdited()) {
                    $actions .= '<a href="' . route('payroll.edit', $payroll) . '" class="btn btn-sm btn-outline-warning">Edit</a>';
                }
                
                if (auth()->user()->can('approve_payroll') && $payroll->canBeApproved()) {
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-success approve-payroll" data-id="' . $payroll->id . '">Approve</button>';
                }
                
                if (auth()->user()->can('process_payroll') && $payroll->canBeProcessed()) {
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-info process-payroll" data-id="' . $payroll->id . '">Process</button>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new payroll.
     */
    public function create(Request $request)
    {
        Gate::authorize('create_payroll');

        $employees = Employee::where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'employee_id']);

        // Default to current month
        $periodStart = $request->get('period_start', now()->startOfMonth()->toDateString());
        $periodEnd = $request->get('period_end', now()->endOfMonth()->toDateString());
        $selectedEmployeeId = $request->get('employee_id');

        return view('pages.payroll.create', compact('employees', 'periodStart', 'periodEnd', 'selectedEmployeeId'));
    }

    /**
     * Store a newly created payroll in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create_payroll');

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'force_recalculate' => 'boolean',
            'bonuses' => 'array',
            'bonuses.*.description' => 'required|string|max:255',
            'bonuses.*.amount' => 'required|numeric|min:0',
            'deductions' => 'array',
            'deductions.*.description' => 'required|string|max:255',
            'deductions.*.amount' => 'required|numeric|min:0',
            'deductions.*.category' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $employee = Employee::findOrFail($request->employee_id);
            $periodStart = Carbon::parse($request->period_start);
            $periodEnd = Carbon::parse($request->period_end);

            $options = [
                'force_recalculate' => $request->boolean('force_recalculate'),
                'bonuses' => $request->input('bonuses', []),
                'deductions' => $request->input('deductions', []),
            ];

            $payroll = $this->payrollCalculationService->calculatePayroll(
                $employee,
                $periodStart,
                $periodEnd,
                $options
            );

            DB::commit();

            return redirect()->route('payroll.show', $payroll)
                ->with('success', 'Payroll calculated successfully for ' . $employee->full_name);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to calculate payroll: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified payroll.
     */
    public function show(Payroll $payroll)
    {
        Gate::authorize('view_payroll');

        $payroll->load([
            'employee',
            'payrollItems' => function ($query) {
                $query->orderBy('type')->orderBy('category');
            },
            'approver',
            'processor'
        ]);

        $earnings = $payroll->getEarnings();
        $deductions = $payroll->getDeductions();
        $bonuses = $payroll->getBonuses();

        return view('pages.payroll.show', compact('payroll', 'earnings', 'deductions', 'bonuses'));
    }

    /**
     * Show the form for editing the specified payroll.
     */
    public function edit(Payroll $payroll)
    {
        Gate::authorize('edit_payroll');

        if (!$payroll->canBeEdited()) {
            return redirect()->route('payroll.show', $payroll)
                ->with('error', 'This payroll cannot be edited in its current status.');
        }

        $payroll->load([
            'employee',
            'payrollItems' => function ($query) {
                $query->orderBy('type')->orderBy('category');
            }
        ]);

        $earnings = $payroll->getEarnings();
        $deductions = $payroll->getDeductions();
        $bonuses = $payroll->getBonuses();

        return view('pages.payroll.edit', compact('payroll', 'earnings', 'deductions', 'bonuses'));
    }

    /**
     * Update the specified payroll in storage.
     */
    public function update(Request $request, Payroll $payroll)
    {
        Gate::authorize('edit_payroll');

        if (!$payroll->canBeEdited()) {
            return redirect()->route('payroll.show', $payroll)
                ->with('error', 'This payroll cannot be edited in its current status.');
        }

        $request->validate([
            'notes' => 'nullable|string',
            'items' => 'array',
            'items.*.id' => 'nullable|exists:payroll_items,id',
            'items.*.type' => 'required|in:earning,deduction,bonus',
            'items.*.category' => 'required|string',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric',
            'items.*.is_taxable' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            // Update payroll notes
            $payroll->update([
                'notes' => $request->notes,
            ]);

            // Update or create payroll items
            if ($request->filled('items')) {
                foreach ($request->items as $itemData) {
                    if (isset($itemData['id'])) {
                        // Update existing item
                        $item = PayrollItem::findOrFail($itemData['id']);
                        $item->update([
                            'type' => $itemData['type'],
                            'category' => $itemData['category'],
                            'description' => $itemData['description'],
                            'amount' => $itemData['amount'],
                            'is_taxable' => $itemData['is_taxable'] ?? true,
                        ]);
                    } else {
                        // Create new item
                        PayrollItem::create([
                            'payroll_id' => $payroll->id,
                            'type' => $itemData['type'],
                            'category' => $itemData['category'],
                            'description' => $itemData['description'],
                            'amount' => $itemData['amount'],
                            'is_taxable' => $itemData['is_taxable'] ?? true,
                            'calculation_method' => PayrollItem::CALCULATION_FIXED,
                        ]);
                    }
                }
            }

            // Recalculate totals
            $payroll->recalculateTotals();

            DB::commit();

            return redirect()->route('payroll.show', $payroll)
                ->with('success', 'Payroll updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to update payroll: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified payroll from storage.
     */
    public function destroy(Payroll $payroll)
    {
        Gate::authorize('delete_payroll');

        if (!$payroll->canBeEdited()) {
            return redirect()->route('payroll.index')
                ->with('error', 'This payroll cannot be deleted in its current status.');
        }

        try {
            $employeeName = $payroll->employee->full_name;
            $payroll->delete();

            return redirect()->route('payroll.index')
                ->with('success', "Payroll for {$employeeName} has been deleted successfully.");

        } catch (\Exception $e) {
            return redirect()->route('payroll.index')
                ->with('error', 'Failed to delete payroll: ' . $e->getMessage());
        }
    }

    /**
     * Approve a payroll.
     */
    public function approve(Request $request, Payroll $payroll): JsonResponse
    {
        Gate::authorize('approve_payroll');

        if (!$payroll->canBeApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'This payroll cannot be approved in its current status.'
            ], 400);
        }

        try {
            $payroll->approve();

            return response()->json([
                'success' => true,
                'message' => 'Payroll approved successfully.',
                'status' => $payroll->status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process a payroll.
     */
    public function process(Request $request, Payroll $payroll): JsonResponse
    {
        Gate::authorize('process_payroll');

        if (!$payroll->canBeProcessed()) {
            return response()->json([
                'success' => false,
                'message' => 'This payroll cannot be processed in its current status.'
            ], 400);
        }

        try {
            $payroll->process();

            return response()->json([
                'success' => true,
                'message' => 'Payroll processed successfully.',
                'status' => $payroll->status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark payroll as paid.
     */
    public function markAsPaid(Request $request, Payroll $payroll): JsonResponse
    {
        Gate::authorize('process_payroll');

        try {
            $payroll->markAsPaid();

            return response()->json([
                'success' => true,
                'message' => 'Payroll marked as paid successfully.',
                'status' => $payroll->status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark payroll as paid: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a payroll.
     */
    public function cancel(Request $request, Payroll $payroll): JsonResponse
    {
        Gate::authorize('edit_payroll');

        if (!$payroll->canBeEdited()) {
            return response()->json([
                'success' => false,
                'message' => 'This payroll cannot be cancelled in its current status.'
            ], 400);
        }

        try {
            $payroll->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Payroll cancelled successfully.',
                'status' => $payroll->status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk calculate payroll for multiple employees.
     */
    public function bulkCalculate(Request $request)
    {
        Gate::authorize('create_payroll');

        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'force_recalculate' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $employees = Employee::whereIn('id', $request->employee_ids)->get();
            $periodStart = Carbon::parse($request->period_start);
            $periodEnd = Carbon::parse($request->period_end);

            $options = [
                'force_recalculate' => $request->boolean('force_recalculate'),
            ];

            $payrolls = $this->payrollCalculationService->calculatePayrollForEmployees(
                $employees,
                $periodStart,
                $periodEnd,
                $options
            );

            DB::commit();

            return redirect()->route('payroll.index')
                ->with('success', "Payroll calculated successfully for {$payrolls->count()} employees.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to calculate bulk payroll: ' . $e->getMessage());
        }
    }

    /**
     * Show bulk calculation form.
     */
    public function bulkCalculateForm()
    {
        Gate::authorize('create_payroll');

        $employees = Employee::where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'employee_id']);

        // Default to current month
        $periodStart = now()->startOfMonth()->toDateString();
        $periodEnd = now()->endOfMonth()->toDateString();

        return view('pages.payroll.bulk-calculate', compact('employees', 'periodStart', 'periodEnd'));
    }

    /**
     * Get payroll summary for a period.
     */
    public function summary(Request $request)
    {
        Gate::authorize('view_payroll_reports');

        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $periodStart = Carbon::parse($request->period_start);
        $periodEnd = Carbon::parse($request->period_end);

        $summary = $this->payrollCalculationService->getPayrollSummary($periodStart, $periodEnd);

        return view('pages.payroll.summary', compact('summary', 'periodStart', 'periodEnd'));
    }

    /**
     * Download payroll slip as PDF.
     */
    public function downloadSlip(Payroll $payroll)
    {
        Gate::authorize('view_payroll');

        $payroll->load([
            'employee',
            'payrollItems' => function ($query) {
                $query->orderBy('type')->orderBy('category');
            }
        ]);

        // This would use a PDF library like TCPDF or DomPDF
        // For now, return a view that can be printed
        return view('pages.payroll.slip-pdf', compact('payroll'));
    }

    /**
     * Recalculate payroll totals.
     */
    public function recalculate(Payroll $payroll): JsonResponse
    {
        Gate::authorize('edit_payroll');

        if (!$payroll->canBeEdited()) {
            return response()->json([
                'success' => false,
                'message' => 'This payroll cannot be recalculated in its current status.'
            ], 400);
        }

        try {
            $payroll->recalculateTotals();

            return response()->json([
                'success' => true,
                'message' => 'Payroll totals recalculated successfully.',
                'gross_salary' => $payroll->formatted_gross_salary,
                'total_deductions' => $payroll->formatted_total_deductions,
                'total_bonuses' => $payroll->formatted_total_bonuses,
                'net_salary' => $payroll->formatted_net_salary,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to recalculate payroll: ' . $e->getMessage()
            ], 500);
        }
    }
}