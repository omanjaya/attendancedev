<?php

namespace App\Http\Controllers\Api;

use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}
