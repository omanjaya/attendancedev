<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\Facades\DataTables;

class AttendanceDataTableService
{
    /**
     * Apply role-based filtering to attendance query
     */
    public function applyRoleBasedFiltering(Builder $query, $user): Builder
    {
        if (!$user->hasRole(['superadmin', 'admin'])) {
            if ($user->hasRole('kepala_sekolah')) {
                // Principal can see attendance for their school location
                $userLocationId = $user->employee?->location_id;
                if ($userLocationId) {
                    $query->whereHas('employee', function ($q) use ($userLocationId) {
                        $q->where('location_id', $userLocationId);
                    });
                } else {
                    // If no location assigned, see no data
                    $query->whereRaw('1 = 0');
                }
            } elseif ($user->hasRole(['guru', 'teacher', 'pegawai', 'staff'])) {
                // Teachers and staff can only see their own attendance
                $query->where('employee_id', $user->employee?->id ?? 0);
            } else {
                // Unknown roles get no access
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    /**
     * Apply filters to attendance query
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Filter by employee if specified
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        // Filter by date range
        if (!empty($filters['start_date'])) {
            $query->whereDate('date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('date', '<=', $filters['end_date']);
        }

        return $query;
    }

    /**
     * Get DataTables response
     */
    public function getDataTableData(Builder $query)
    {
        return DataTables::of($query)
            ->addColumn('employee_name', function ($attendance) {
                return $attendance->employee->full_name;
            })
            ->addColumn('employee_id', function ($attendance) {
                return $attendance->employee->employee_id;
            })
            ->addColumn('date_formatted', function ($attendance) {
                return $attendance->date->format('M d, Y');
            })
            ->addColumn('check_in_formatted', function ($attendance) {
                return $attendance->formatted_check_in ?? '-';
            })
            ->addColumn('check_out_formatted', function ($attendance) {
                return $attendance->formatted_check_out ?? '-';
            })
            ->addColumn('status_badge', function ($attendance) {
                return '<span class="badge bg-' .
                    $attendance->status_color .
                    '">' .
                    ucfirst(str_replace('_', ' ', $attendance->status)) .
                    '</span>';
            })
            ->addColumn('actions', function ($attendance) {
                $actions = '<div class="btn-list">';

                if (auth()->user()->can('manage_attendance_all')) {
                    $actions .=
                        '<button class="btn btn-sm btn-outline-primary view-details" data-id="' .
                        $attendance->id .
                        '">View</button>';

                    if ($attendance->status === 'incomplete') {
                        $actions .=
                            '<button class="btn btn-sm btn-outline-success manual-checkout" data-id="' .
                            $attendance->id .
                            '">Complete</button>';
                    }
                }

                $actions .= '</div>';

                return $actions;
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }
}
