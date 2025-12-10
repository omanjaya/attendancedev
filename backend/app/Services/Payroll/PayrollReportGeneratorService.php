<?php

namespace App\Services\Payroll;

use Illuminate\Support\Facades\Response;

class PayrollReportGeneratorService
{
    public function generatePdfSummary($summary)
    {
        return view('pages.payroll.reports.pdf.summary', compact('summary'))->with('isPdf', true);
    }

    public function generateExcelSummary($summary)
    {
        $filename = 'payroll_summary_'.now()->format('Y-m-d').'.csv';

        $csvData = [
            ['Metric', 'Value'],
            ['Total Employees', $summary['total_employees'] ?? 0],
            ['Total Gross Salary', $summary['total_gross_salary'] ?? 0],
            ['Total Deductions', $summary['total_deductions'] ?? 0],
            ['Total Net Salary', $summary['total_net_salary'] ?? 0],
        ];

        return Response::streamDownload(function () use ($csvData) {
            $handle = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function generatePdfDetailed($reportData)
    {
        return view('pages.payroll.reports.pdf.detailed', compact('reportData'))->with('isPdf', true);
    }

    public function generateExcelDetailed($reportData)
    {
        $filename = 'payroll_detailed_'.now()->format('Y-m-d').'.csv';

        $csvData = [['Employee', 'Gross Salary', 'Deductions', 'Net Salary', 'Period']];

        foreach ($reportData as $record) {
            $csvData[] = [
                $record->employee->full_name ?? 'Unknown',
                $record->gross_salary ?? 0,
                $record->total_deductions ?? 0,
                $record->net_salary ?? 0,
                $record->period ?? '',
            ];
        }

        return Response::streamDownload(function () use ($csvData) {
            $handle = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function generatePdfComparison($comparison)
    {
        return view('pages.payroll.reports.pdf.comparison', compact('comparison'))->with('isPdf', true);
    }

    public function generateExcelComparison($comparison)
    {
        $filename = 'payroll_comparison_'.now()->format('Y-m-d').'.csv';

        $csvData = [['Period', 'Total Gross', 'Total Deductions', 'Total Net', 'Employee Count']];

        foreach ($comparison as $period) {
            $csvData[] = [
                $period['period'] ?? '',
                $period['total_gross'] ?? 0,
                $period['total_deductions'] ?? 0,
                $period['total_net'] ?? 0,
                $period['employee_count'] ?? 0,
            ];
        }

        return Response::streamDownload(function () use ($csvData) {
            $handle = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function generatePdfEmployeeHistory($historyData)
    {
        return view('pages.payroll.reports.pdf.employee-history', compact('historyData'))->with('isPdf', true);
    }

    public function generateExcelEmployeeHistory($historyData)
    {
        $filename = 'employee_payroll_history_'.now()->format('Y-m-d').'.csv';

        $csvData = [['Date', 'Gross Salary', 'Deductions', 'Net Salary', 'Status']];

        foreach ($historyData as $record) {
            $csvData[] = [
                $record->date ?? '',
                $record->gross_salary ?? 0,
                $record->total_deductions ?? 0,
                $record->net_salary ?? 0,
                $record->status ?? '',
            ];
        }

        return Response::streamDownload(function () use ($csvData) {
            $handle = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function generatePdfTaxReport($taxData)
    {
        return view('pages.payroll.reports.pdf.tax-report', compact('taxData'))->with('isPdf', true);
    }

    public function generateExcelTaxReport($taxData)
    {
        $filename = 'tax_report_'.now()->format('Y-m-d').'.csv';

        $csvData = [['Employee', 'Gross Income', 'Tax Amount', 'Tax Rate', 'Period']];

        foreach ($taxData as $record) {
            $csvData[] = [
                $record['employee_name'] ?? '',
                $record['gross_income'] ?? 0,
                $record['tax_amount'] ?? 0,
                $record['tax_rate'] ?? 0,
                $record['period'] ?? '',
            ];
        }

        return Response::streamDownload(function () use ($csvData) {
            $handle = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
