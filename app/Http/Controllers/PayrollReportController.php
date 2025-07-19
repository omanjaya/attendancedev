<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PayrollReportController extends Controller
{
    protected $payrollCalculationService;

    public function __construct(PayrollCalculationService $payrollCalculationService)
    {
        $this->middleware('auth');
        $this->payrollCalculationService = $payrollCalculationService;
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
                return $this->generatePdfSummary($summary);
            case 'excel':
                return $this->generateExcelSummary($summary);
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
                return $this->generatePdfDetailed($reportData);
            case 'excel':
                return $this->generateExcelDetailed($reportData);
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
                return $this->generatePdfComparison($comparison);
            case 'excel':
                return $this->generateExcelComparison($comparison);
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
                return $this->generatePdfEmployeeHistory($historyData);
            case 'excel':
                return $this->generateExcelEmployeeHistory($historyData);
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
                return $this->generatePdfTaxReport($taxData);
            case 'excel':
                return $this->generateExcelTaxReport($taxData);
            default:
                return view('pages.payroll.reports.tax', compact('taxData'));
        }
    }

    /**
     * Generate PDF summary report.
     */
    protected function generatePdfSummary($summary)
    {
        // For now, return HTML that can be converted to PDF
        // In a real implementation, you would use a PDF library like TCPDF or DomPDF
        return view('pages.payroll.reports.pdf.summary', compact('summary'))->with('isPdf', true);
    }

    /**
     * Generate Excel summary report.
     */
    protected function generateExcelSummary($summary)
    {
        // For now, return CSV format
        // In a real implementation, you would use a library like PhpSpreadsheet
        $filename =
          'payroll_summary_'.
          $summary['period_start']->format('Y-m-d').
          '_to_'.
          $summary['period_end']->format('Y-m-d').
          '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($summary) {
            $file = fopen('php://output', 'w');

            // Write header
            fputcsv($file, ['Payroll Summary Report']);
            fputcsv($file, [
                'Period',
                $summary['period_start']->format('Y-m-d').
                ' to '.
                $summary['period_end']->format('Y-m-d'),
            ]);
            fputcsv($file, ['Generated', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []);

            // Write summary
            fputcsv($file, ['Summary']);
            fputcsv($file, ['Total Employees', $summary['total_employees']]);
            fputcsv($file, ['Total Payrolls', $summary['total_payrolls']]);
            fputcsv($file, [
                'Total Gross Salary',
                '$'.number_format($summary['total_gross_salary'], 2),
            ]);
            fputcsv($file, ['Total Deductions', '$'.number_format($summary['total_deductions'], 2)]);
            fputcsv($file, ['Total Bonuses', '$'.number_format($summary['total_bonuses'], 2)]);
            fputcsv($file, ['Total Net Salary', '$'.number_format($summary['total_net_salary'], 2)]);
            fputcsv($file, []);

            // Write payroll details
            fputcsv($file, [
                'Employee ID',
                'Employee Name',
                'Period',
                'Gross Salary',
                'Deductions',
                'Bonuses',
                'Net Salary',
                'Status',
            ]);
            foreach ($summary['payrolls'] as $payroll) {
                fputcsv($file, [
                    $payroll->employee->employee_id,
                    $payroll->employee->full_name,
                    $payroll->payroll_period,
                    '$'.number_format($payroll->gross_salary, 2),
                    '$'.number_format($payroll->total_deductions, 2),
                    '$'.number_format($payroll->total_bonuses, 2),
                    '$'.number_format($payroll->net_salary, 2),
                    ucfirst($payroll->status),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate PDF detailed report.
     */
    protected function generatePdfDetailed($reportData)
    {
        return view('pages.payroll.reports.pdf.detailed', compact('reportData'))->with('isPdf', true);
    }

    /**
     * Generate Excel detailed report.
     */
    protected function generateExcelDetailed($reportData)
    {
        $filename =
          'payroll_detailed_'.
          $reportData['period_start']->format('Y-m-d').
          '_to_'.
          $reportData['period_end']->format('Y-m-d').
          '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($reportData) {
            $file = fopen('php://output', 'w');

            // Write header
            fputcsv($file, ['Detailed Payroll Report']);
            fputcsv($file, [
                'Period',
                $reportData['period_start']->format('Y-m-d').
                ' to '.
                $reportData['period_end']->format('Y-m-d'),
            ]);
            fputcsv($file, ['Generated', $reportData['generated_at']->format('Y-m-d H:i:s')]);
            fputcsv($file, []);

            foreach ($reportData['payrolls'] as $payroll) {
                fputcsv($file, [
                    'Employee',
                    $payroll->employee->full_name.' ('.$payroll->employee->employee_id.')',
                ]);
                fputcsv($file, ['Period', $payroll->payroll_period]);
                fputcsv($file, ['Status', ucfirst($payroll->status)]);
                fputcsv($file, []);

                fputcsv($file, ['Type', 'Description', 'Category', 'Amount']);
                foreach ($payroll->payrollItems as $item) {
                    fputcsv($file, [
                        ucfirst($item->type),
                        $item->description,
                        $item->category_display_name,
                        '$'.number_format($item->amount, 2),
                    ]);
                }

                fputcsv($file, []);
                fputcsv($file, ['Gross Salary', '$'.number_format($payroll->gross_salary, 2)]);
                fputcsv($file, ['Total Deductions', '$'.number_format($payroll->total_deductions, 2)]);
                fputcsv($file, ['Total Bonuses', '$'.number_format($payroll->total_bonuses, 2)]);
                fputcsv($file, ['Net Salary', '$'.number_format($payroll->net_salary, 2)]);
                fputcsv($file, []);
                fputcsv($file, ['---']);
                fputcsv($file, []);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate PDF comparison report.
     */
    protected function generatePdfComparison($comparison)
    {
        return view('pages.payroll.reports.pdf.comparison', compact('comparison'))->with('isPdf', true);
    }

    /**
     * Generate Excel comparison report.
     */
    protected function generateExcelComparison($comparison)
    {
        $filename = 'payroll_comparison_'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($comparison) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Payroll Comparison Report']);
            fputcsv($file, ['Generated', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []);

            fputcsv($file, ['Metric', 'Period 1', 'Period 2', 'Difference', '% Change']);

            $metrics = [
                'Total Employees' => ['total_employees', 0],
                'Total Gross Salary' => ['total_gross_salary', 2],
                'Total Deductions' => ['total_deductions', 2],
                'Total Bonuses' => ['total_bonuses', 2],
                'Total Net Salary' => ['total_net_salary', 2],
                'Total Worked Hours' => ['total_worked_hours', 2],
                'Total Overtime Hours' => ['total_overtime_hours', 2],
            ];

            foreach ($metrics as $label => $config) {
                $key = $config[0];
                $decimals = $config[1];

                $value1 = $comparison['period1']['summary'][$key];
                $value2 = $comparison['period2']['summary'][$key];
                $difference = $comparison['differences'][$key];
                $percentChange = $value1 > 0 ? ($difference / $value1) * 100 : 0;

                fputcsv($file, [
                    $label,
                    $decimals > 0 ? '$'.number_format($value1, $decimals) : number_format($value1),
                    $decimals > 0 ? '$'.number_format($value2, $decimals) : number_format($value2),
                    $decimals > 0 ? '$'.number_format($difference, $decimals) : number_format($difference),
                    number_format($percentChange, 2).'%',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate PDF employee history report.
     */
    protected function generatePdfEmployeeHistory($historyData)
    {
        return view('pages.payroll.reports.pdf.employee-history', compact('historyData'))->with(
            'isPdf',
            true,
        );
    }

    /**
     * Generate Excel employee history report.
     */
    protected function generateExcelEmployeeHistory($historyData)
    {
        $filename =
          'employee_payroll_history_'.
          $historyData['employee']->employee_id.
          '_'.
          $historyData['year'].
          '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($historyData) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Employee Payroll History']);
            fputcsv($file, ['Employee', $historyData['employee']->full_name]);
            fputcsv($file, ['Employee ID', $historyData['employee']->employee_id]);
            fputcsv($file, ['Year', $historyData['year']]);
            fputcsv($file, ['Generated', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []);

            fputcsv($file, [
                'Period',
                'Gross Salary',
                'Deductions',
                'Bonuses',
                'Net Salary',
                'Worked Hours',
                'Overtime Hours',
                'Status',
            ]);
            foreach ($historyData['payrolls'] as $payroll) {
                fputcsv($file, [
                    $payroll->payroll_period,
                    '$'.number_format($payroll->gross_salary, 2),
                    '$'.number_format($payroll->total_deductions, 2),
                    '$'.number_format($payroll->total_bonuses, 2),
                    '$'.number_format($payroll->net_salary, 2),
                    number_format($payroll->worked_hours, 2),
                    number_format($payroll->overtime_hours, 2),
                    ucfirst($payroll->status),
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, ['Summary']);
            fputcsv($file, [
                'Total Gross Salary',
                '$'.number_format($historyData['summary']['total_gross_salary'], 2),
            ]);
            fputcsv($file, [
                'Total Deductions',
                '$'.number_format($historyData['summary']['total_deductions'], 2),
            ]);
            fputcsv($file, [
                'Total Bonuses',
                '$'.number_format($historyData['summary']['total_bonuses'], 2),
            ]);
            fputcsv($file, [
                'Total Net Salary',
                '$'.number_format($historyData['summary']['total_net_salary'], 2),
            ]);
            fputcsv($file, [
                'Average Monthly Gross',
                '$'.number_format($historyData['summary']['average_monthly_gross'], 2),
            ]);
            fputcsv($file, [
                'Average Monthly Net',
                '$'.number_format($historyData['summary']['average_monthly_net'], 2),
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate PDF tax report.
     */
    protected function generatePdfTaxReport($taxData)
    {
        return view('pages.payroll.reports.pdf.tax', compact('taxData'))->with('isPdf', true);
    }

    /**
     * Generate Excel tax report.
     */
    protected function generateExcelTaxReport($taxData)
    {
        $filename =
          'tax_report_'.
          $taxData['period_start']->format('Y-m-d').
          '_to_'.
          $taxData['period_end']->format('Y-m-d').
          '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($taxData) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Tax Report']);
            fputcsv($file, [
                'Period',
                $taxData['period_start']->format('Y-m-d').
                ' to '.
                $taxData['period_end']->format('Y-m-d'),
            ]);
            fputcsv($file, ['Generated', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []);

            fputcsv($file, ['Summary']);
            fputcsv($file, [
                'Total Taxable Income',
                '$'.number_format($taxData['total_taxable_income'], 2),
            ]);
            fputcsv($file, [
                'Total Tax Deducted',
                '$'.number_format($taxData['total_tax_deducted'], 2),
            ]);
            fputcsv($file, [
                'Total Statutory Deductions',
                '$'.number_format($taxData['total_statutory_deductions'], 2),
            ]);
            fputcsv($file, []);

            fputcsv($file, [
                'Employee ID',
                'Employee Name',
                'Period',
                'Taxable Income',
                'Tax Deducted',
                'Statutory Deductions',
                'Net Salary',
            ]);
            foreach ($taxData['employees'] as $employeeData) {
                fputcsv($file, [
                    $employeeData['employee']->employee_id,
                    $employeeData['employee']->full_name,
                    $employeeData['payroll_period'],
                    '$'.number_format($employeeData['taxable_income'], 2),
                    '$'.number_format($employeeData['tax_deducted'], 2),
                    '$'.number_format($employeeData['statutory_deductions'], 2),
                    '$'.number_format($employeeData['net_salary'], 2),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
