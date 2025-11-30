<?php

namespace App\Http\Controllers\Api;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Attendance::query()
            ->with(['employee:id,employee_id,full_name']);

        // Apply filters
        if ($employeeId = $request->get('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        if ($date = $request->get('date')) {
            $query->whereDate('date', $date);
        }

        if ($startDate = $request->get('start_date')) {
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate = $request->get('end_date')) {
            $query->whereDate('date', '<=', $endDate);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $query->orderBy('date', 'desc');

        $perPage = $request->get('per_page', 15);
        $attendance = $query->paginate($perPage);

        return $this->paginatedResponse($attendance, 'Attendance data retrieved');
    }

    public function show($id)
    {
        $attendance = Attendance::with(['employee'])->find($id);

        if (!$attendance) {
            return $this->errorResponse('Attendance not found', 404);
        }

        return $this->apiResponse($attendance, 'Attendance retrieved');
    }

    public function today()
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', today())
            ->first();

        return $this->apiResponse([
            'attendance' => $attendance,
            'has_checked_in' => $attendance && $attendance->check_in_time,
            'has_checked_out' => $attendance && $attendance->check_out_time,
        ], 'Today attendance retrieved');
    }

    public function statistics(Request $request)
    {
        $date = $request->get('date', today()->format('Y-m-d'));
        $targetDate = Carbon::parse($date);

        $totalEmployees = Employee::where('is_active', true)->count();

        $stats = Attendance::whereDate('date', $targetDate)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as on_leave
            ")
            ->first();

        return $this->apiResponse([
            'date' => $date,
            'total_employees' => $totalEmployees,
            'present' => $stats->present ?? 0,
            'late' => $stats->late ?? 0,
            'absent' => max(0, $totalEmployees - ($stats->present ?? 0) - ($stats->late ?? 0) - ($stats->on_leave ?? 0)),
            'on_leave' => $stats->on_leave ?? 0,
            'attendance_rate' => $totalEmployees > 0
                ? round((($stats->present ?? 0) + ($stats->late ?? 0)) / $totalEmployees * 100, 1)
                : 0,
        ], 'Statistics retrieved');
    }

    public function trends(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $trends = Attendance::whereBetween('date', [$startDate, $endDate])
            ->selectRaw("
                date,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
            ")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->apiResponse($trends, 'Trends retrieved');
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return $this->errorResponse('Attendance not found', 404);
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:present,late,absent,leave,half_day',
            'check_in_time' => 'sometimes|date',
            'check_out_time' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        $attendance->update($validated);

        return $this->apiResponse($attendance->fresh(), 'Attendance updated');
    }

    public function destroy($id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return $this->errorResponse('Attendance not found', 404);
        }

        $attendance->delete();

        return $this->apiResponse(null, 'Attendance deleted');
    }
}
