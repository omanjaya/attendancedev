<?php

namespace App\Services\Reports;

use App\Models\Attendance;
use App\Models\Employee;

class DepartmentReportService
{
    /**
     * Get department statistics
     */
    public function getDepartmentStats(string $startDate, string $endDate): array
    {
        // Check if department column exists, otherwise return empty data
        try {
            $departments = Employee::select('department')
                ->distinct()
                ->whereNotNull('department')
                ->pluck('department');
        } catch (\Exception $e) {
            return [];
        }

        $data = [];

        foreach ($departments as $dept) {
            $employeeIds = Employee::where('department', $dept)->pluck('id');

            $stats = Attendance::whereIn('employee_id', $employeeIds)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                ")
                ->first();

            $total = $stats->total ?? 1;

            $data[] = [
                'department' => $dept,
                'employee_count' => $employeeIds->count(),
                'attendance_rate' => min(100, round((($stats->present ?? 0) + ($stats->late ?? 0)) / max($total, 1) * 100, 1)),
                'present' => $stats->present ?? 0,
                'late' => $stats->late ?? 0,
            ];
        }

        return $data;
    }
}
