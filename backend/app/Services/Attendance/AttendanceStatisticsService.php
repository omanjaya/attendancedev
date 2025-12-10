<?php

namespace App\Services\Attendance;

use Illuminate\Support\Facades\DB;

class AttendanceStatisticsService
{
    /**
     * Get attendance statistics with role-based filtering
     */
    public function getStatistics(string $startDate, string $endDate, $user): array
    {
        $query = DB::table('attendances')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at');

        // Apply role-based filtering
        if (!$user->hasRole(['superadmin', 'admin'])) {
            if ($user->hasRole('kepala_sekolah')) {
                $userLocationId = $user->employee?->location_id;
                if ($userLocationId) {
                    // Join with employees table to filter by location
                    $query->join('employees', 'attendances.employee_id', '=', 'employees.id')
                        ->where('employees.location_id', $userLocationId);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } elseif ($user->hasRole(['guru', 'teacher', 'pegawai', 'staff'])) {
                $query->where('attendances.employee_id', $user->employee?->id ?? 0);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $stats = $query->selectRaw("
            COUNT(*) as total_records,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = 'incomplete' THEN 1 ELSE 0 END) as incomplete_count,
            AVG(total_hours) as average_hours,
            SUM(total_hours) as total_hours
        ")->first();

        return [
            'total_records' => $stats->total_records ?? 0,
            'present_count' => $stats->present_count ?? 0,
            'late_count' => $stats->late_count ?? 0,
            'absent_count' => $stats->absent_count ?? 0,
            'incomplete_count' => $stats->incomplete_count ?? 0,
            'average_hours' => round($stats->average_hours ?? 0, 2),
            'total_hours' => round($stats->total_hours ?? 0, 2),
        ];
    }
}
