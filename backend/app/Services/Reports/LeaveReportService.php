<?php

namespace App\Services\Reports;

use App\Models\Leave;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaveReportService
{
    /**
     * Get leave statistics by type
     */
    public function getLeaveStats(int $year): array
    {
        // PostgreSQL compatible datediff
        $dateDiffQuery = "(end_date - start_date + 1)";
        if (DB::connection()->getDriverName() === 'sqlite') {
            $dateDiffQuery = "(julianday(end_date) - julianday(start_date) + 1)";
        } elseif (DB::connection()->getDriverName() === 'mysql') {
            $dateDiffQuery = "DATEDIFF(end_date, start_date) + 1";
        }

        $stats = Leave::whereYear('start_date', $year)
            ->where('status', 'approved')
            ->select('leave_type_id')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw("SUM({$dateDiffQuery}) as total_days")
            ->groupBy('leave_type_id')
            ->with('leaveType')
            ->get()
            ->map(function ($item) {
                return [
                    'leave_type' => $item->leaveType->name ?? 'Unknown',
                    'count' => $item->count,
                    'total_days' => $item->total_days
                ];
            });

        return $stats->toArray();
    }
}
