<?php

namespace App\Http\Controllers\Api;

use App\Models\PayrollPeriod;
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
}
