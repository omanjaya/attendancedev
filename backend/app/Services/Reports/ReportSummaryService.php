<?php

namespace App\Services\Reports;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Payroll;
use Carbon\Carbon;

class ReportSummaryService
{
    public function getSummaryReportData(array $filters): array
    {
        $startDate = Carbon::parse($filters['start_date']);
        $endDate = Carbon::parse($filters['end_date']);
        $grouping = $filters['grouping'];

        $summaryData = [];

        // Group data based on grouping parameter
        switch ($grouping) {
            case 'daily':
                $current = $startDate->copy();
                while ($current <= $endDate) {
                    $summaryData[] = $this->getDailySummary($current);
                    $current->addDay();
                }
                break;
            case 'weekly':
                $current = $startDate->copy()->startOfWeek();
                while ($current <= $endDate) {
                    $weekEnd = $current->copy()->endOfWeek();
                    if ($weekEnd > $endDate) {
                        $weekEnd = $endDate;
                    }
                    $summaryData[] = $this->getWeeklySummary($current, $weekEnd);
                    $current->addWeek();
                }
                break;
            case 'monthly':
                $current = $startDate->copy()->startOfMonth();
                while ($current <= $endDate) {
                    $monthEnd = $current->copy()->endOfMonth();
                    if ($monthEnd > $endDate) {
                        $monthEnd = $endDate;
                    }
                    $summaryData[] = $this->getMonthlySummary($current, $monthEnd);
                    $current->addMonth();
                }
                break;
        }

        return [
            'summary_data' => $summaryData,
            'grouping' => $grouping,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
        ];
    }

    public function getDailySummary(Carbon $date): array
    {
        $attendance = Attendance::whereDate('date', $date)->get();
        $leaves = Leave::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('status', 'approved')
            ->get();

        return [
            'date' => $date->format('Y-m-d'),
            'attendance_count' => $attendance->count(),
            'present_count' => $attendance->whereIn('status', ['present', 'late'])->count(),
            'leave_count' => $leaves->count(),
            'total_hours' => $attendance->sum('total_hours'),
        ];
    }

    public function getWeeklySummary(Carbon $startDate, Carbon $endDate): array
    {
        $attendance = Attendance::whereBetween('date', [$startDate, $endDate])->get();
        $leaves = Leave::where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->where('status', 'approved')
            ->get();

        return [
            'week_start' => $startDate->format('Y-m-d'),
            'week_end' => $endDate->format('Y-m-d'),
            'attendance_count' => $attendance->count(),
            'present_count' => $attendance->whereIn('status', ['present', 'late'])->count(),
            'leave_count' => $leaves->count(),
            'total_hours' => $attendance->sum('total_hours'),
        ];
    }

    public function getMonthlySummary(Carbon $startDate, Carbon $endDate): array
    {
        $attendance = Attendance::whereBetween('date', [$startDate, $endDate])->get();
        $leaves = Leave::where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->where('status', 'approved')
            ->get();
        $payrolls = Payroll::whereBetween('payroll_period_start', [$startDate, $endDate])->get();

        return [
            'month' => $startDate->format('Y-m'),
            'month_name' => $startDate->format('F Y'),
            'attendance_count' => $attendance->count(),
            'present_count' => $attendance->whereIn('status', ['present', 'late'])->count(),
            'leave_count' => $leaves->count(),
            'payroll_count' => $payrolls->count(),
            'total_hours' => $attendance->sum('total_hours'),
            'total_payroll_amount' => $payrolls->sum('net_salary'),
        ];
    }
}
