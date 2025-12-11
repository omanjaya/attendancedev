<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollCalculationService;
use App\Services\Payroll\PayrollReportGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PayrollReportController extends Controller
{
    public function __construct(
        private PayrollCalculationService $payrollCalculationService,
        private PayrollReportGeneratorService $reportGenerator
    ) {
        $this->middleware('auth');
    }

    /**
     * Display payroll reports dashboard.
     */
    public function index()
    {
        Gate::authorize('export_payroll_reports');

        $currentMonth = now()->format('Y-m');
        $currentYear = now()->year;

        // Get current month summary
        $currentMonthSummary = $this->payrollCalculationService->getPayrollSummary(
            now()->startOfMonth(),
            now()->endOfMonth(),
        );

        // Get year-to-date summary
        $ytdSummary = $this->payrollCalculationService->getPayrollSummary(
            Carbon::create($currentYear, 1, 1),
            now(),
        );

        // Get recent payroll records
        $recentPayrolls = Payroll::with('employee')->orderBy('created_at', 'desc')->limit(10)->get();

        return view(
            'pages.payroll.reports.index',
            compact('currentMonthSummary', 'ytdSummary', 'recentPayrolls', 'currentMonth', 'currentYear'),
        );
    }

    /**
     * Generate payroll summary report.
     */
    public function summary(Request $request)
    {
        Gate::authorize('export_payroll_reports');

        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'employee_ids' => 'array',
            'employee_ids.*' => 'exists:employees,id',
            'status' => 'nullable|string|in:draft,pending,approved,processed,paid,cancelled',
            'format' => 'nullable|string|in:html,pdf,excel',
        ]);

        $periodStart = Carbon::parse($request->period_start);
        $periodEnd = Carbon::parse($request->period_end);
        $format = $request->get('format', 'html');

        // Build query
        $query = Payroll::with(['employee', 'payrollItems'])->whereBetween('payroll_period_start', [
            $periodStart,
            $periodEnd,
        ]);

        if ($request->filled('employee_ids')) {
            $query->whereIn('employee_id', $request->employee_ids);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payrolls = $query->orderBy('payroll_period_start')->orderBy('employee_id')->get();

        // Calculate summary statistics
        $summary = [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_employees' => $payrolls->groupBy('employee_id')->count(),
            'total_payrolls' => $payrolls->count(),
            'total_gross_salary' => $payrolls->sum('gross_salary'),
            'total_deductions' => $payrolls->sum('total_deductions'),
            'total_bonuses' => $payrolls->sum('total_bonuses'),
            'total_net_salary' => $payrolls->sum('net_salary'),
            'total_worked_hours' => $payrolls->sum('worked_hours'),
            'total_overtime_hours' => $payrolls->sum('overtime_hours'),
            'payrolls' => $payrolls,
            'by_status' => $payrolls->groupBy('status')->map->count(),
            'by_employee_type' => $payrolls->groupBy('employee.employee_type')->map->count(),
        ];

        switch ($format) {
            case 'pdf':
                return $this->reportGenerator->generatePdfSummary($summary);
            case 'excel':
                return $this->reportGenerator->generateExcelSummary($summary);
            default:
                return view('pages.payroll.reports.summary', compact('summary'));
        }
    }

    /**
     * Generate detailed payroll report.
     */
    public function detailed(Request $request)
    {
        Gate::authorize('export_payroll_reports');

        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'employee_ids' => 'array',
            'employee_ids.*' => 'exists:employees,id',
            'format' => 'nullable|string|in:html,pdf,excel',
        ]);

        $periodStart = Carbon::parse($request->period_start);
        $periodEnd = Carbon::parse($request->period_end);
        $format = $request->get('format', 'html');

        // Build query
        $query = Payroll::with(['employee', 'payrollItems'])->whereBetween('payroll_period_start', [
            $periodStart,
            $periodEnd,
        ]);

        if ($request->filled('employee_ids')) {
            $query->whereIn('employee_id', $request->employee_ids);
        }

        $payrolls = $query->orderBy('payroll_period_start')->orderBy('employee_id')->get();

        $reportData = [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'payrolls' => $payrolls,
            'generated_at' => now(),
        ];

        switch ($format) {
            case 'pdf':
                return $this->reportGenerator->generatePdfDetailed($reportData);
            case 'excel':
                return $this->reportGenerator->generateExcelDetailed($reportData);
            default:
                return view('pages.payroll.reports.detailed', compact('reportData'));
        }
    }

    /**
     * Generate payroll comparison report.
     */
    public function comparison(Request $request)
    {
        Gate::authorize('export_payroll_reports');

        $request->validate([
            'period1_start' => 'required|date',
            'period1_end' => 'required|date|after_or_equal:period1_start',
            'period2_start' => 'required|date',
            'period2_end' => 'required|date|after_or_equal:period2_start',
            'employee_ids' => 'array',
            'employee_ids.*' => 'exists:employees,id',
            'format' => 'nullable|string|in:html,pdf,excel',
        ]);

        $period1Start = Carbon::parse($request->period1_start);
        $period1End = Carbon::parse($request->period1_end);
        $period2Start = Carbon::parse($request->period2_start);
        $period2End = Carbon::parse($request->period2_end);
        $format = $request->get('format', 'html');

        // Get summaries for both periods
        $summary1 = $this->payrollCalculationService->getPayrollSummary($period1Start, $period1End);
        $summary2 = $this->payrollCalculationService->getPayrollSummary($period2Start, $period2End);

        $comparison = [
            'period1' => [
                'start' => $period1Start,
                'end' => $period1End,
                'summary' => $summary1,
            ],
            'period2' => [
                'start' => $period2Start,
                'end' => $period2End,
                'summary' => $summary2,
            ],
            'differences' => [
                'total_employees' => $summary2['total_employees'] - $summary1['total_employees'],
                'total_gross_salary' => $summary2['total_gross_salary'] - $summary1['total_gross_salary'],
                'total_deductions' => $summary2['total_deductions'] - $summary1['total_deductions'],
                'total_bonuses' => $summary2['total_bonuses'] - $summary1['total_bonuses'],
                'total_net_salary' => $summary2['total_net_salary'] - $summary1['total_net_salary'],
                'total_worked_hours' => $summary2['total_worked_hours'] - $summary1['total_worked_hours'],
                'total_overtime_hours' => $summary2['total_overtime_hours'] - $summary1['total_overtime_hours'],
            ],
        ];

        switch ($format) {
            case 'pdf':
                return $this->reportGenerator->generatePdfComparison($comparison);
            case 'excel':
                return $this->reportGenerator->generateExcelComparison($comparison);
            default:
                return view('pages.payroll.reports.comparison', compact('comparison'));
        }
    }

    /**
     * Generate employee payroll history report.
     */
    public function employeeHistory(Request $request)
    {
        Gate::authorize('export_payroll_reports');

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'year' => 'nullable|integer|min:2020|max:'.(date('Y') + 1),
            'format' => 'nullable|string|in:html,pdf,excel',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        $year = $request->get('year', date('Y'));
        $format = $request->get('format', 'html');

        // Get payroll history for the employee
        $payrolls = $employee
            ->payrolls()
            ->with('payrollItems')
            ->whereYear('payroll_period_start', $year)
            ->orderBy('payroll_period_start')
            ->get();

        $historyData = [
            'employee' => $employee,
            'year' => $year,
            'payrolls' => $payrolls,
            'summary' => [
                'total_gross_salary' => $payrolls->sum('gross_salary'),
                'total_deductions' => $payrolls->sum('total_deductions'),
                'total_bonuses' => $payrolls->sum('total_bonuses'),
                'total_net_salary' => $payrolls->sum('net_salary'),
                'total_worked_hours' => $payrolls->sum('worked_hours'),
                'total_overtime_hours' => $payrolls->sum('overtime_hours'),
                'average_monthly_gross' => $payrolls->avg('gross_salary'),
                'average_monthly_net' => $payrolls->avg('net_salary'),
            ],
        ];

        switch ($format) {
            case 'pdf':
                return $this->reportGenerator->generatePdfEmployeeHistory($historyData);
            case 'excel':
                return $this->reportGenerator->generateExcelEmployeeHistory($historyData);
            default:
                return view('pages.payroll.reports.employee-history', compact('historyData'));
        }
    }

    /**
     * Generate tax report.
     */
    public function taxReport(Request $request)
    {
        Gate::authorize('export_payroll_reports');

        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'format' => 'nullable|string|in:html,pdf,excel',
        ]);

        $periodStart = Carbon::parse($request->period_start);
        $periodEnd = Carbon::parse($request->period_end);
        $format = $request->get('format', 'html');

        // Get all payrolls for the period
        $payrolls = Payroll::with(['employee', 'payrollItems'])
            ->whereBetween('payroll_period_start', [$periodStart, $periodEnd])
            ->where('status', '!=', 'cancelled')
            ->get();

        // Calculate tax summary
        $taxData = [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_taxable_income' => 0,
            'total_tax_deducted' => 0,
            'total_statutory_deductions' => 0,
            'employees' => [],
        ];

        foreach ($payrolls as $payroll) {
            $taxableIncome = $payroll->payrollItems()->where('is_taxable', true)->sum('amount');

            $taxDeducted = $payroll->payrollItems()->where('category', 'tax')->sum('amount');

            $statutoryDeductions = $payroll->payrollItems()->where('is_statutory', true)->sum('amount');

            $taxData['total_taxable_income'] += $taxableIncome;
            $taxData['total_tax_deducted'] += $taxDeducted;
            $taxData['total_statutory_deductions'] += $statutoryDeductions;

            $taxData['employees'][] = [
                'employee' => $payroll->employee,
                'payroll_period' => $payroll->payroll_period,
                'taxable_income' => $taxableIncome,
                'tax_deducted' => $taxDeducted,
                'statutory_deductions' => $statutoryDeductions,
                'net_salary' => $payroll->net_salary,
            ];
        }

        switch ($format) {
            case 'pdf':
                return $this->reportGenerator->generatePdfTaxReport($taxData);
            case 'excel':
                return $this->reportGenerator->generateExcelTaxReport($taxData);
            default:
                return view('pages.payroll.reports.tax', compact('taxData'));
        }
    }
}
