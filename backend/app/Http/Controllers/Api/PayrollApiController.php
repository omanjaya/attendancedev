<?php

namespace App\Http\Controllers\Api;

use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\PayrollItem;
use App\Models\PayrollFormula;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollApiController extends BaseApiController
{
    public function periods(Request $request)
    {
        $query = PayrollPeriod::query();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($year = $request->get('year')) {
            $query->whereYear('start_date', $year);
        }

        if ($month = $request->get('month')) {
            $query->whereMonth('start_date', $month);
        }

        $query->orderBy('start_date', 'desc');

        $perPage = $request->get('per_page', 15);
        $periods = $query->paginate($perPage);

        return $this->paginatedResponse($periods, 'Payroll periods retrieved');
    }

    public function showPeriod($id)
    {
        $period = PayrollPeriod::with(['payrollItems.employee'])->find($id);

        if (!$period) {
            return $this->errorResponse('Period not found', 404);
        }

        return $this->apiResponse($period, 'Period retrieved');
    }

    public function storePeriod(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:monthly,weekly,biweekly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'pay_date' => 'required|date',
        ]);

        $period = PayrollPeriod::create(array_merge($validated, [
            'status' => 'draft',
        ]));

        return $this->apiResponse($period, 'Period created', 201);
    }

    public function updatePeriod(Request $request, $id)
    {
        $period = PayrollPeriod::find($id);

        if (!$period) {
            return $this->errorResponse('Period not found', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'pay_date' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        $period->update($validated);

        return $this->apiResponse($period->fresh(), 'Period updated');
    }

    public function destroyPeriod($id)
    {
        $period = PayrollPeriod::find($id);

        if (!$period) {
            return $this->errorResponse('Period not found', 404);
        }

        if ($period->status !== 'draft') {
            return $this->errorResponse('Only draft periods can be deleted', 422);
        }

        $period->delete();

        return $this->apiResponse(null, 'Period deleted');
    }

    public function statistics()
    {
        $stats = [
            'total_periods' => PayrollPeriod::count(),
            'draft' => PayrollPeriod::where('status', 'draft')->count(),
            'calculated' => PayrollPeriod::where('status', 'calculated')->count(),
            'approved' => PayrollPeriod::where('status', 'approved')->count(),
            'paid' => PayrollPeriod::where('status', 'paid')->count(),
        ];

        return $this->apiResponse($stats, 'Statistics retrieved');
    }

    public function config()
    {
        $config = [
            'basic_salary_min' => config('payroll.basic_salary_min', 0),
            'tax_brackets' => config('payroll.tax_brackets', []),
            'allowances' => config('payroll.allowances', []),
            'deductions' => config('payroll.deductions', []),
        ];

        return $this->apiResponse($config, 'Config retrieved');
    }

    public function employeePayroll(Request $request)
    {
        $employee = $request->user()->employee;
        
        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        $year = $request->get('year', now()->year);
        
        $payrolls = Payroll::where('employee_id', $employee->id)
            ->whereYear('payroll_period_start', $year)
            ->orderBy('payroll_period_start', 'desc')
            ->get();
        
        $result = $payrolls->map(function ($payroll) {
            return [
                'id' => $payroll->id,
                'month' => \Carbon\Carbon::parse($payroll->payroll_period_start)->format('F'),
                'year' => \Carbon\Carbon::parse($payroll->payroll_period_start)->year,
                'period_start' => $payroll->payroll_period_start->format('Y-m-d'),
                'period_end' => $payroll->payroll_period_end->format('Y-m-d'),
                'pay_date' => $payroll->pay_date?->format('Y-m-d'),
                'gross_salary' => $payroll->gross_salary,
                'total_deductions' => $payroll->total_deductions,
                'total_bonuses' => $payroll->total_bonuses,
                'net_salary' => $payroll->net_salary,
                'status' => $payroll->status,
                'approved_at' => $payroll->approved_at?->format('Y-m-d H:i:s'),
                'processed_at' => $payroll->processed_at?->format('Y-m-d H:i:s'),
            ];
        });

        return $this->apiResponse($result, 'Employee payroll retrieved');
    }

    public function downloadPayslip(Request $request, $id)
    {
        $employee = $request->user()->employee;
        
        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        $payroll = Payroll::where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$payroll) {
            return $this->errorResponse('Payroll not found', 404);
        }

        // Generate PDF (simplified version - in real implementation, you would use a PDF library)
        $pdfData = [
            'employee_name' => $employee->full_name,
            'employee_id' => $employee->employee_id,
            'period' => $payroll->payroll_period_start->format('F Y'),
            'gross_salary' => $payroll->gross_salary,
            'deductions' => $payroll->total_deductions,
            'net_salary' => $payroll->net_salary,
            'pay_date' => $payroll->pay_date?->format('d F Y'),
        ];

        // Return PDF response
        return response($pdfData)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="payslip-' . $payroll->id . '.json"');
    }

    /**
     * Get all employees with their payroll for a specific period
     */
    public function periodEmployees(Request $request, $periodId)
    {
        $period = PayrollPeriod::find($periodId);

        if (!$period) {
            return $this->errorResponse('Period not found', 404);
        }

        $query = Payroll::where('payroll_period_start', $period->start_date)
            ->where('payroll_period_end', $period->end_date)
            ->with(['employee', 'payrollItems']);

        // Optional filters
        if ($search = $request->get('search')) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'ilike', "%{$search}%")
                    ->orWhere('employee_id', 'ilike', "%{$search}%");
            });
        }

        if ($department = $request->get('department')) {
            $query->whereHas('employee', function ($q) use ($department) {
                $q->where('department_id', $department);
            });
        }

        $payrolls = $query->orderBy('created_at', 'desc')->get();

        $result = $payrolls->map(function ($payroll) {
            return [
                'id' => $payroll->id,
                'employee_id' => $payroll->employee_id,
                'employee' => [
                    'id' => $payroll->employee->id,
                    'employee_code' => $payroll->employee->employee_id,
                    'name' => $payroll->employee->full_name,
                    'department' => $payroll->employee->department,
                    'position' => $payroll->employee->position,
                ],
                'gross_salary' => $payroll->gross_salary,
                'total_deductions' => $payroll->total_deductions,
                'total_bonuses' => $payroll->total_bonuses,
                'net_salary' => $payroll->net_salary,
                'status' => $payroll->status,
                'items' => $payroll->payrollItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => $item->type,
                        'category' => $item->category,
                        'description' => $item->description,
                        'amount' => $item->amount,
                        'quantity' => $item->quantity,
                        'rate' => $item->rate,
                        'is_taxable' => $item->is_taxable,
                        'is_statutory' => $item->is_statutory,
                        'calculation_method' => $item->calculation_method,
                        'notes' => $item->notes,
                    ];
                }),
            ];
        });

        return $this->apiResponse([
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date->format('Y-m-d'),
                'end_date' => $period->end_date->format('Y-m-d'),
                'pay_date' => $period->pay_date?->format('Y-m-d'),
                'status' => $period->status,
            ],
            'employees' => $result,
        ], 'Period employees retrieved');
    }

    /**
     * Get a specific employee payroll within a period
     */
    public function showEmployeePayroll($periodId, $employeeId)
    {
        $period = PayrollPeriod::find($periodId);

        if (!$period) {
            return $this->errorResponse('Period not found', 404);
        }

        $payroll = Payroll::where('payroll_period_start', $period->start_date)
            ->where('payroll_period_end', $period->end_date)
            ->where('employee_id', $employeeId)
            ->with(['employee', 'payrollItems'])
            ->first();

        if (!$payroll) {
            return $this->errorResponse('Payroll not found for this employee', 404);
        }

        // Group items by type
        $earnings = $payroll->payrollItems->where('type', PayrollItem::TYPE_EARNING)->values();
        $deductions = $payroll->payrollItems->where('type', PayrollItem::TYPE_DEDUCTION)->values();
        $bonuses = $payroll->payrollItems->where('type', PayrollItem::TYPE_BONUS)->values();

        return $this->apiResponse([
            'id' => $payroll->id,
            'employee' => [
                'id' => $payroll->employee->id,
                'employee_code' => $payroll->employee->employee_id,
                'name' => $payroll->employee->full_name,
                'department' => $payroll->employee->department,
                'position' => $payroll->employee->position,
                'base_salary' => $payroll->employee->base_salary ?? 0,
            ],
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date->format('Y-m-d'),
                'end_date' => $period->end_date->format('Y-m-d'),
                'pay_date' => $period->pay_date?->format('Y-m-d'),
            ],
            'gross_salary' => $payroll->gross_salary,
            'total_deductions' => $payroll->total_deductions,
            'total_bonuses' => $payroll->total_bonuses,
            'net_salary' => $payroll->net_salary,
            'worked_hours' => $payroll->worked_hours,
            'overtime_hours' => $payroll->overtime_hours,
            'status' => $payroll->status,
            'notes' => $payroll->notes,
            'earnings' => $earnings->map(fn($item) => $this->formatPayrollItem($item)),
            'deductions' => $deductions->map(fn($item) => $this->formatPayrollItem($item)),
            'bonuses' => $bonuses->map(fn($item) => $this->formatPayrollItem($item)),
        ], 'Employee payroll retrieved');
    }

    /**
     * Update an employee's payroll (A1)
     */
    public function updateEmployeePayroll(Request $request, $periodId, $employeeId)
    {
        $period = PayrollPeriod::find($periodId);

        if (!$period) {
            return $this->errorResponse('Period not found', 404);
        }

        $payroll = Payroll::where('payroll_period_start', $period->start_date)
            ->where('payroll_period_end', $period->end_date)
            ->where('employee_id', $employeeId)
            ->first();

        if (!$payroll) {
            return $this->errorResponse('Payroll not found for this employee', 404);
        }

        if (!$payroll->canBeEdited()) {
            return $this->errorResponse('Payroll cannot be edited in current status: ' . $payroll->status, 422);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'items' => 'sometimes|array',
            'items.*.id' => 'sometimes|uuid|exists:payroll_items,id',
            'items.*.type' => 'required|in:earning,deduction,bonus',
            'items.*.category' => 'required|string',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.rate' => 'nullable|numeric|min:0',
            'items.*.is_taxable' => 'sometimes|boolean',
            'items.*.is_statutory' => 'sometimes|boolean',
            'items.*.calculation_method' => 'nullable|in:fixed,percentage,hourly,daily,computed',
            'items.*.notes' => 'nullable|string|max:500',
            'items.*.action' => 'nullable|in:create,update,delete',
        ]);

        try {
            DB::beginTransaction();

            // Update notes if provided
            if (isset($validated['notes'])) {
                $payroll->notes = $validated['notes'];
            }

            // Process items if provided
            if (isset($validated['items'])) {
                foreach ($validated['items'] as $itemData) {
                    $action = $itemData['action'] ?? 'update';

                    if ($action === 'delete' && isset($itemData['id'])) {
                        // Delete item
                        PayrollItem::where('id', $itemData['id'])
                            ->where('payroll_id', $payroll->id)
                            ->delete();
                    } elseif ($action === 'create') {
                        // Create new item
                        PayrollItem::create([
                            'payroll_id' => $payroll->id,
                            'type' => $itemData['type'],
                            'category' => $itemData['category'],
                            'description' => $itemData['description'],
                            'amount' => $itemData['amount'],
                            'quantity' => $itemData['quantity'] ?? null,
                            'rate' => $itemData['rate'] ?? null,
                            'is_taxable' => $itemData['is_taxable'] ?? true,
                            'is_statutory' => $itemData['is_statutory'] ?? false,
                            'calculation_method' => $itemData['calculation_method'] ?? PayrollItem::CALCULATION_FIXED,
                            'notes' => $itemData['notes'] ?? null,
                        ]);
                    } elseif (isset($itemData['id'])) {
                        // Update existing item
                        $item = PayrollItem::where('id', $itemData['id'])
                            ->where('payroll_id', $payroll->id)
                            ->first();

                        if ($item) {
                            $item->update([
                                'type' => $itemData['type'],
                                'category' => $itemData['category'],
                                'description' => $itemData['description'],
                                'amount' => $itemData['amount'],
                                'quantity' => $itemData['quantity'] ?? $item->quantity,
                                'rate' => $itemData['rate'] ?? $item->rate,
                                'is_taxable' => $itemData['is_taxable'] ?? $item->is_taxable,
                                'is_statutory' => $itemData['is_statutory'] ?? $item->is_statutory,
                                'calculation_method' => $itemData['calculation_method'] ?? $item->calculation_method,
                                'notes' => $itemData['notes'] ?? $item->notes,
                            ]);
                        }
                    }
                }
            }

            // Recalculate totals
            $payroll->recalculateTotals();

            DB::commit();

            // Refresh with relations
            $payroll->load('payrollItems');

            return $this->apiResponse([
                'id' => $payroll->id,
                'gross_salary' => $payroll->gross_salary,
                'total_deductions' => $payroll->total_deductions,
                'total_bonuses' => $payroll->total_bonuses,
                'net_salary' => $payroll->net_salary,
                'items_count' => $payroll->payrollItems->count(),
            ], 'Payroll updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update payroll: ' . $e->getMessage());
            return $this->errorResponse('Failed to update payroll: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get payroll items for a specific payroll (A2)
     */
    public function getPayrollItems($payrollId)
    {
        $payroll = Payroll::with('payrollItems')->find($payrollId);

        if (!$payroll) {
            return $this->errorResponse('Payroll not found', 404);
        }

        $items = $payroll->payrollItems->map(fn($item) => $this->formatPayrollItem($item));

        return $this->apiResponse([
            'payroll_id' => $payroll->id,
            'items' => $items,
            'summary' => [
                'earnings_count' => $payroll->payrollItems->where('type', PayrollItem::TYPE_EARNING)->count(),
                'deductions_count' => $payroll->payrollItems->where('type', PayrollItem::TYPE_DEDUCTION)->count(),
                'bonuses_count' => $payroll->payrollItems->where('type', PayrollItem::TYPE_BONUS)->count(),
                'total_earnings' => $payroll->gross_salary,
                'total_deductions' => $payroll->total_deductions,
                'total_bonuses' => $payroll->total_bonuses,
                'net_salary' => $payroll->net_salary,
            ],
        ], 'Payroll items retrieved');
    }

    /**
     * Add a new payroll item (A2)
     */
    public function storePayrollItem(Request $request, $payrollId)
    {
        $payroll = Payroll::find($payrollId);

        if (!$payroll) {
            return $this->errorResponse('Payroll not found', 404);
        }

        if (!$payroll->canBeEdited()) {
            return $this->errorResponse('Payroll cannot be edited in current status: ' . $payroll->status, 422);
        }

        $validated = $request->validate([
            'type' => 'required|in:earning,deduction,bonus',
            'category' => 'required|string',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'rate' => 'nullable|numeric|min:0',
            'is_taxable' => 'sometimes|boolean',
            'is_statutory' => 'sometimes|boolean',
            'calculation_method' => 'nullable|in:fixed,percentage,hourly,daily,computed',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $item = PayrollItem::create([
                'payroll_id' => $payroll->id,
                'type' => $validated['type'],
                'category' => $validated['category'],
                'description' => $validated['description'],
                'amount' => $validated['amount'],
                'quantity' => $validated['quantity'] ?? null,
                'rate' => $validated['rate'] ?? null,
                'is_taxable' => $validated['is_taxable'] ?? true,
                'is_statutory' => $validated['is_statutory'] ?? false,
                'calculation_method' => $validated['calculation_method'] ?? PayrollItem::CALCULATION_FIXED,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Recalculate totals
            $payroll->recalculateTotals();

            DB::commit();

            return $this->apiResponse([
                'item' => $this->formatPayrollItem($item),
                'payroll_totals' => [
                    'gross_salary' => $payroll->gross_salary,
                    'total_deductions' => $payroll->total_deductions,
                    'total_bonuses' => $payroll->total_bonuses,
                    'net_salary' => $payroll->net_salary,
                ],
            ], 'Payroll item created', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create payroll item: ' . $e->getMessage());
            return $this->errorResponse('Failed to create payroll item', 500);
        }
    }

    /**
     * Update a payroll item (A2)
     */
    public function updatePayrollItem(Request $request, $payrollId, $itemId)
    {
        $payroll = Payroll::find($payrollId);

        if (!$payroll) {
            return $this->errorResponse('Payroll not found', 404);
        }

        if (!$payroll->canBeEdited()) {
            return $this->errorResponse('Payroll cannot be edited in current status: ' . $payroll->status, 422);
        }

        $item = PayrollItem::where('id', $itemId)
            ->where('payroll_id', $payrollId)
            ->first();

        if (!$item) {
            return $this->errorResponse('Payroll item not found', 404);
        }

        $validated = $request->validate([
            'type' => 'sometimes|in:earning,deduction,bonus',
            'category' => 'sometimes|string',
            'description' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'rate' => 'nullable|numeric|min:0',
            'is_taxable' => 'sometimes|boolean',
            'is_statutory' => 'sometimes|boolean',
            'calculation_method' => 'nullable|in:fixed,percentage,hourly,daily,computed',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $item->update($validated);

            // Recalculate totals
            $payroll->recalculateTotals();

            DB::commit();

            return $this->apiResponse([
                'item' => $this->formatPayrollItem($item->fresh()),
                'payroll_totals' => [
                    'gross_salary' => $payroll->gross_salary,
                    'total_deductions' => $payroll->total_deductions,
                    'total_bonuses' => $payroll->total_bonuses,
                    'net_salary' => $payroll->net_salary,
                ],
            ], 'Payroll item updated');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update payroll item: ' . $e->getMessage());
            return $this->errorResponse('Failed to update payroll item', 500);
        }
    }

    /**
     * Delete a payroll item (A2)
     */
    public function destroyPayrollItem($payrollId, $itemId)
    {
        $payroll = Payroll::find($payrollId);

        if (!$payroll) {
            return $this->errorResponse('Payroll not found', 404);
        }

        if (!$payroll->canBeEdited()) {
            return $this->errorResponse('Payroll cannot be edited in current status: ' . $payroll->status, 422);
        }

        $item = PayrollItem::where('id', $itemId)
            ->where('payroll_id', $payrollId)
            ->first();

        if (!$item) {
            return $this->errorResponse('Payroll item not found', 404);
        }

        // Don't allow deleting basic salary or statutory items
        if ($item->category === PayrollItem::CATEGORY_BASIC_SALARY) {
            return $this->errorResponse('Cannot delete basic salary item', 422);
        }

        if ($item->is_statutory) {
            return $this->errorResponse('Cannot delete statutory items', 422);
        }

        try {
            DB::beginTransaction();

            $item->delete();

            // Recalculate totals
            $payroll->recalculateTotals();

            DB::commit();

            return $this->apiResponse([
                'payroll_totals' => [
                    'gross_salary' => $payroll->gross_salary,
                    'total_deductions' => $payroll->total_deductions,
                    'total_bonuses' => $payroll->total_bonuses,
                    'net_salary' => $payroll->net_salary,
                ],
            ], 'Payroll item deleted');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete payroll item: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete payroll item', 500);
        }
    }

    /**
     * Get available categories for payroll items
     */
    public function itemCategories()
    {
        return $this->apiResponse([
            'earning' => [
                ['value' => PayrollItem::CATEGORY_BASIC_SALARY, 'label' => 'Gaji Pokok'],
                ['value' => PayrollItem::CATEGORY_OVERTIME, 'label' => 'Lembur'],
                ['value' => PayrollItem::CATEGORY_ALLOWANCE, 'label' => 'Tunjangan'],
                ['value' => PayrollItem::CATEGORY_COMMISSION, 'label' => 'Komisi'],
                ['value' => PayrollItem::CATEGORY_HOLIDAY_PAY, 'label' => 'Tunjangan Hari Raya'],
                ['value' => PayrollItem::CATEGORY_SICK_LEAVE, 'label' => 'Cuti Sakit'],
                ['value' => PayrollItem::CATEGORY_VACATION_PAY, 'label' => 'Cuti Tahunan'],
                ['value' => PayrollItem::CATEGORY_OTHER, 'label' => 'Lainnya'],
            ],
            'deduction' => [
                ['value' => PayrollItem::CATEGORY_TAX, 'label' => 'Pajak (PPh 21)'],
                ['value' => PayrollItem::CATEGORY_INSURANCE, 'label' => 'BPJS Kesehatan'],
                ['value' => PayrollItem::CATEGORY_RETIREMENT, 'label' => 'BPJS Ketenagakerjaan'],
                ['value' => PayrollItem::CATEGORY_LOAN_DEDUCTION, 'label' => 'Potongan Pinjaman'],
                ['value' => PayrollItem::CATEGORY_UNPAID_LEAVE, 'label' => 'Cuti Tidak Dibayar'],
                ['value' => PayrollItem::CATEGORY_GARNISHMENT, 'label' => 'Pemotongan Gaji'],
                ['value' => PayrollItem::CATEGORY_OTHER, 'label' => 'Lainnya'],
            ],
            'bonus' => [
                ['value' => PayrollItem::CATEGORY_BONUS, 'label' => 'Bonus'],
                ['value' => PayrollItem::CATEGORY_COMMISSION, 'label' => 'Komisi'],
                ['value' => PayrollItem::CATEGORY_OTHER, 'label' => 'Lainnya'],
            ],
            'calculation_methods' => [
                ['value' => PayrollItem::CALCULATION_FIXED, 'label' => 'Nilai Tetap'],
                ['value' => PayrollItem::CALCULATION_PERCENTAGE, 'label' => 'Persentase'],
                ['value' => PayrollItem::CALCULATION_HOURLY, 'label' => 'Per Jam'],
                ['value' => PayrollItem::CALCULATION_DAILY, 'label' => 'Per Hari'],
                ['value' => PayrollItem::CALCULATION_COMPUTED, 'label' => 'Formula'],
            ],
        ], 'Categories retrieved');
    }

    /**
     * Format payroll item for API response
     */
    private function formatPayrollItem(PayrollItem $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type,
            'category' => $item->category,
            'category_label' => $item->category_display_name,
            'description' => $item->description,
            'amount' => $item->amount,
            'quantity' => $item->quantity,
            'rate' => $item->rate,
            'is_taxable' => $item->is_taxable,
            'is_statutory' => $item->is_statutory,
            'calculation_method' => $item->calculation_method,
            'notes' => $item->notes,
            'can_edit' => !$item->is_statutory,
            'can_delete' => !$item->is_statutory && $item->category !== PayrollItem::CATEGORY_BASIC_SALARY,
        ];
    }

    // ============================================
    // FORMULA MANAGEMENT (A3)
    // ============================================

    /**
     * Get all payroll formulas
     */
    public function getFormulas(Request $request)
    {
        $query = PayrollFormula::query();

        // Filter by type
        if ($type = $request->get('type')) {
            $query->byType($type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        $formulas = $query->ordered()->get();

        return $this->apiResponse([
            'formulas' => $formulas->map(fn($formula) => $this->formatFormula($formula)),
            'meta' => [
                'total' => $formulas->count(),
                'types' => PayrollFormula::getTypes(),
                'formula_types' => PayrollFormula::getFormulaTypes(),
                'base_fields' => PayrollFormula::getBaseFields(),
            ],
        ], 'Formulas retrieved');
    }

    /**
     * Get a specific formula
     */
    public function showFormula($id)
    {
        $formula = PayrollFormula::find($id);

        if (!$formula) {
            return $this->errorResponse('Formula not found', 404);
        }

        return $this->apiResponse($this->formatFormula($formula), 'Formula retrieved');
    }

    /**
     * Create a new formula
     */
    public function storeFormula(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:payroll_formulas,code',
            'type' => 'required|in:earning,deduction,bonus',
            'formula_type' => 'required|in:fixed,percentage,conditional,custom',
            'formula_expression' => 'nullable|string',
            'base_field' => 'nullable|string|max:50',
            'default_amount' => 'nullable|numeric|min:0',
            'percentage_rate' => 'nullable|numeric|min:0|max:100',
            'is_taxable' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|min:0',
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'conditions' => 'nullable|array',
        ]);

        try {
            $formula = PayrollFormula::create(array_merge($validated, [
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]));

            return $this->apiResponse($this->formatFormula($formula), 'Formula created', 201);
        } catch (\Exception $e) {
            Log::error('Failed to create formula: ' . $e->getMessage());
            return $this->errorResponse('Failed to create formula', 500);
        }
    }

    /**
     * Update a formula
     */
    public function updateFormula(Request $request, $id)
    {
        $formula = PayrollFormula::find($id);

        if (!$formula) {
            return $this->errorResponse('Formula not found', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'code' => 'sometimes|string|max:50|unique:payroll_formulas,code,' . $id,
            'type' => 'sometimes|in:earning,deduction,bonus',
            'formula_type' => 'sometimes|in:fixed,percentage,conditional,custom',
            'formula_expression' => 'nullable|string',
            'base_field' => 'nullable|string|max:50',
            'default_amount' => 'nullable|numeric|min:0',
            'percentage_rate' => 'nullable|numeric|min:0|max:100',
            'is_taxable' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|min:0',
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'conditions' => 'nullable|array',
        ]);

        try {
            $formula->update(array_merge($validated, [
                'updated_by' => auth()->id(),
            ]));

            return $this->apiResponse($this->formatFormula($formula->fresh()), 'Formula updated');
        } catch (\Exception $e) {
            Log::error('Failed to update formula: ' . $e->getMessage());
            return $this->errorResponse('Failed to update formula', 500);
        }
    }

    /**
     * Delete a formula
     */
    public function destroyFormula($id)
    {
        $formula = PayrollFormula::find($id);

        if (!$formula) {
            return $this->errorResponse('Formula not found', 404);
        }

        try {
            $formula->delete();
            return $this->apiResponse(null, 'Formula deleted');
        } catch (\Exception $e) {
            Log::error('Failed to delete formula: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete formula', 500);
        }
    }

    /**
     * Toggle formula active status
     */
    public function toggleFormulaStatus($id)
    {
        $formula = PayrollFormula::find($id);

        if (!$formula) {
            return $this->errorResponse('Formula not found', 404);
        }

        $formula->update([
            'is_active' => !$formula->is_active,
            'updated_by' => auth()->id(),
        ]);

        return $this->apiResponse([
            'id' => $formula->id,
            'is_active' => $formula->is_active,
        ], $formula->is_active ? 'Formula activated' : 'Formula deactivated');
    }

    /**
     * Preview formula calculation
     */
    public function previewFormula(Request $request, $id)
    {
        $formula = PayrollFormula::find($id);

        if (!$formula) {
            return $this->errorResponse('Formula not found', 404);
        }

        $context = $request->validate([
            'base_salary' => 'nullable|numeric|min:0',
            'gross_salary' => 'nullable|numeric|min:0',
            'net_salary' => 'nullable|numeric|min:0',
            'worked_days' => 'nullable|integer|min:0',
            'worked_hours' => 'nullable|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'attendance_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        // Set defaults for preview
        $context = array_merge([
            'base_salary' => 5000000,
            'gross_salary' => 6000000,
            'net_salary' => 5000000,
            'worked_days' => 22,
            'worked_hours' => 176,
            'overtime_hours' => 10,
            'attendance_rate' => 95,
        ], array_filter($context));

        $result = $formula->calculate($context);

        return $this->apiResponse([
            'formula' => $this->formatFormula($formula),
            'context' => $context,
            'result' => $result,
            'formatted_result' => 'Rp ' . number_format($result, 0, ',', '.'),
        ], 'Preview calculated');
    }

    /**
     * Get formula configuration options
     */
    public function formulaConfig()
    {
        return $this->apiResponse([
            'types' => PayrollFormula::getTypes(),
            'formula_types' => PayrollFormula::getFormulaTypes(),
            'base_fields' => PayrollFormula::getBaseFields(),
            'categories' => [
                'earning' => [
                    ['value' => PayrollItem::CATEGORY_BASIC_SALARY, 'label' => 'Gaji Pokok'],
                    ['value' => PayrollItem::CATEGORY_OVERTIME, 'label' => 'Lembur'],
                    ['value' => PayrollItem::CATEGORY_ALLOWANCE, 'label' => 'Tunjangan'],
                    ['value' => PayrollItem::CATEGORY_COMMISSION, 'label' => 'Komisi'],
                    ['value' => PayrollItem::CATEGORY_HOLIDAY_PAY, 'label' => 'Tunjangan Hari Raya'],
                    ['value' => PayrollItem::CATEGORY_OTHER, 'label' => 'Lainnya'],
                ],
                'deduction' => [
                    ['value' => PayrollItem::CATEGORY_TAX, 'label' => 'Pajak (PPh 21)'],
                    ['value' => PayrollItem::CATEGORY_INSURANCE, 'label' => 'BPJS Kesehatan'],
                    ['value' => PayrollItem::CATEGORY_RETIREMENT, 'label' => 'BPJS Ketenagakerjaan'],
                    ['value' => PayrollItem::CATEGORY_LOAN_DEDUCTION, 'label' => 'Potongan Pinjaman'],
                    ['value' => PayrollItem::CATEGORY_UNPAID_LEAVE, 'label' => 'Cuti Tidak Dibayar'],
                    ['value' => PayrollItem::CATEGORY_OTHER, 'label' => 'Lainnya'],
                ],
                'bonus' => [
                    ['value' => PayrollItem::CATEGORY_BONUS, 'label' => 'Bonus'],
                    ['value' => PayrollItem::CATEGORY_COMMISSION, 'label' => 'Komisi'],
                    ['value' => PayrollItem::CATEGORY_OTHER, 'label' => 'Lainnya'],
                ],
            ],
            'operators' => [
                ['value' => '>=', 'label' => 'Lebih dari atau sama dengan'],
                ['value' => '>', 'label' => 'Lebih dari'],
                ['value' => '<=', 'label' => 'Kurang dari atau sama dengan'],
                ['value' => '<', 'label' => 'Kurang dari'],
                ['value' => '==', 'label' => 'Sama dengan'],
                ['value' => '!=', 'label' => 'Tidak sama dengan'],
            ],
        ], 'Config retrieved');
    }

    /**
     * Format formula for API response
     */
    private function formatFormula(PayrollFormula $formula): array
    {
        return [
            'id' => $formula->id,
            'name' => $formula->name,
            'code' => $formula->code,
            'type' => $formula->type,
            'type_label' => $formula->type_label,
            'formula_type' => $formula->formula_type,
            'formula_type_label' => $formula->formula_type_label,
            'formula_expression' => $formula->formula_expression,
            'formula_display' => $formula->formula_display,
            'base_field' => $formula->base_field,
            'base_field_label' => PayrollFormula::BASE_FIELDS[$formula->base_field] ?? $formula->base_field,
            'default_amount' => $formula->default_amount,
            'percentage_rate' => $formula->percentage_rate,
            'is_taxable' => $formula->is_taxable,
            'is_active' => $formula->is_active,
            'priority' => $formula->priority,
            'category' => $formula->category,
            'description' => $formula->description,
            'conditions' => $formula->conditions,
            'created_at' => $formula->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $formula->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
