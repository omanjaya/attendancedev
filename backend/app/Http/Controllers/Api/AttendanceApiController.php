<?php

namespace App\Http\Controllers\Api;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\TimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceApiController extends BaseApiController
{
    public function __construct(
        private readonly TimeService $timeService
    ) {}
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Attendance::query()
            ->with(['employee:id,employee_id,full_name']);

        // SECURITY: Employee/Guru role can only see their own attendance
        if ($user->hasRole(['pegawai', 'guru'])) {
            if (!$user->employee) {
                return $this->apiResponse([], 'No employee data');
            }
            $query->where('employee_id', $user->employee->id);
        } elseif ($employeeId = $request->get('employee_id')) {
            // Admin/Super Admin can filter by specific employee
            $query->where('employee_id', $employeeId);
        }

        if ($date = $request->get('date')) {
            $query->whereDate('date', $date);
        }

        if ($startDate = $request->get('start_date') ?? $request->get('date_from')) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate = $request->get('end_date') ?? $request->get('date_to')) {
            $query->where('date', '<=', $endDate);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Support month filter (format: YYYY-MM)
        if ($month = $request->get('month')) {
            $startDate = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
            $endDate = \Carbon\Carbon::parse($month . '-01')->endOfMonth();
            $query->whereDate('date', '>=', $startDate)
                  ->whereDate('date', '<=', $endDate);
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

    public function today(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        // Use WITA timezone to match AttendanceService
        $today = Carbon::now('Asia/Makassar')->format('Y-m-d');

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        return $this->apiResponse([
            'attendance' => $attendance,
            'has_checked_in' => $attendance && $attendance->check_in_time,
            'has_checked_out' => $attendance && $attendance->check_out_time,
        ], 'Today attendance retrieved');
    }

    /**
     * Validate attendance time before proceeding to verification flow
     * Returns whether check-in/check-out is allowed based on schedule time boundaries
     */
    public function validateTime(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:check_in,check_out,check-in,check-out',
        ]);

        // Normalize type
        $type = str_replace('-', '_', $validated['type']);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->errorResponse('Employee not found', 404);
        }

        // Get server time
        $now = $this->timeService->now();
        $currentTime = $now->format('H:i:s');

        // === GURU HONORER: Use TeachingSchedule for validation ===
        if ($employee->isGuruHonorer()) {
            return $this->validateTimeForGuruHonorer($employee, $type, $now);
        }

        // === REGULAR EMPLOYEE: Use MonthlySchedule for validation ===

        // Check if employee has schedule for today
        $effectiveSchedule = $employee->getEffectiveScheduleForDate($now);
        $canAttend = $effectiveSchedule['can_attend'] ?? false;

        if (!$canAttend) {
            $scheduleType = $effectiveSchedule['schedule_type'] ?? 'none';
            $message = 'Tidak dapat melakukan absensi';

            if ($scheduleType === 'none') {
                $message = 'Tidak ada jadwal yang di-assign untuk hari ini. Hubungi administrator.';
            } elseif ($scheduleType === 'holiday') {
                $message = $effectiveSchedule['holiday_name']
                    ? 'Hari ini adalah hari libur: ' . $effectiveSchedule['holiday_name']
                    : 'Hari ini adalah hari libur';
            }

            return $this->apiResponse([
                'allowed' => false,
                'message' => $message,
                'server_time' => $now->format('H:i:s'),
            ], $message);
        }

        // Get employee's schedule for time boundaries
        $employeeSchedule = $employee->getScheduleForDate($now);

        if (!$employeeSchedule || !$employeeSchedule->monthlySchedule) {
            return $this->apiResponse([
                'allowed' => true,
                'message' => 'Silakan lanjutkan absensi',
                'server_time' => $now->format('H:i:s'),
            ], 'Validation passed');
        }

        $monthlySchedule = $employeeSchedule->monthlySchedule;

        if ($type === 'check_in') {
            // Validate check-in time boundaries
            $checkinStartTime = $monthlySchedule->getRawOriginal('checkin_start_time');

            if ($checkinStartTime && $currentTime < $checkinStartTime) {
                $formattedTime = Carbon::parse($checkinStartTime)->format('H:i');
                return $this->apiResponse([
                    'allowed' => false,
                    'message' => "Belum waktunya absen masuk. Absen masuk dibuka mulai pukul {$formattedTime}",
                    'server_time' => $now->format('H:i:s'),
                    'boundary' => [
                        'start_time' => $formattedTime,
                        'type' => 'too_early',
                    ],
                ], 'Check-in not allowed yet');
            }
        } else {
            // Validate check-out time boundaries
            $checkoutStartTime = $monthlySchedule->getRawOriginal('checkout_start_time');
            $checkoutEndTime = $monthlySchedule->getRawOriginal('checkout_end_time');

            if ($checkoutStartTime && $currentTime < $checkoutStartTime) {
                $formattedTime = Carbon::parse($checkoutStartTime)->format('H:i');
                return $this->apiResponse([
                    'allowed' => false,
                    'message' => "Belum waktunya absen pulang. Absen pulang dibuka mulai pukul {$formattedTime}",
                    'server_time' => $now->format('H:i:s'),
                    'boundary' => [
                        'start_time' => $formattedTime,
                        'type' => 'too_early',
                    ],
                ], 'Check-out not allowed yet');
            }

            if ($checkoutEndTime && $currentTime > $checkoutEndTime) {
                $formattedTime = Carbon::parse($checkoutEndTime)->format('H:i');
                return $this->apiResponse([
                    'allowed' => false,
                    'message' => "Sudah lewat batas absen pulang. Batas akhir absen pulang adalah pukul {$formattedTime}",
                    'server_time' => $now->format('H:i:s'),
                    'boundary' => [
                        'end_time' => $formattedTime,
                        'type' => 'too_late',
                    ],
                ], 'Check-out time exceeded');
            }
        }

        // All validations passed
        return $this->apiResponse([
            'allowed' => true,
            'message' => 'Silakan lanjutkan absensi',
            'server_time' => $now->format('H:i:s'),
        ], 'Validation passed');
    }

    /**
     * Validate attendance time for Guru Honorer
     * Uses TeachingSchedule instead of MonthlySchedule
     * NOTE: No blocking - guru honorer can check-in/out anytime, but status will be determined
     */
    private function validateTimeForGuruHonorer($employee, string $type, Carbon $now)
    {
        if ($type === 'check_in') {
            // Get check-in boundaries from TeachingSchedule
            $boundaries = $employee->getGuruHonorerCheckInBoundaries($now);

            if (!$boundaries['has_schedule']) {
                // No teaching schedule - BLOCK check-in for guru honorer
                return $this->apiResponse([
                    'allowed' => false,
                    'message' => 'Tidak ada jadwal mengajar hari ini. Anda tidak dapat melakukan absensi.',
                    'server_time' => $now->format('H:i:s'),
                    'schedule_type' => 'no_teaching',
                    'has_teaching_schedule' => false,
                ], 'Tidak dapat absen', 403);
            }

            // Check if will be late (after first session start)
            $isLate = $boundaries['late_after'] && $now->gt($boundaries['late_after']);

            return $this->apiResponse([
                'allowed' => true,
                'message' => $isLate
                    ? 'Anda terlambat dari jadwal sesi pertama'
                    : 'Silakan lanjutkan absensi',
                'server_time' => $now->format('H:i:s'),
                'schedule_type' => 'teaching_based',
                'has_teaching_schedule' => true,
                'will_be_late' => $isLate,
                'first_session' => $boundaries['first_session'] ?? null,
            ], 'Validation passed');

        } else {
            // Check-out validation for Guru Honorer
            $boundaries = $employee->getGuruHonorerCheckOutBoundaries($now);

            if (!$boundaries['has_schedule']) {
                // No teaching schedule - BLOCK check-out for guru honorer
                return $this->apiResponse([
                    'allowed' => false,
                    'message' => 'Tidak ada jadwal mengajar hari ini. Anda tidak dapat melakukan absensi pulang.',
                    'server_time' => $now->format('H:i:s'),
                    'schedule_type' => 'no_teaching',
                    'has_teaching_schedule' => false,
                ], 'Tidak dapat absen', 403);
            }

            // Check if will be early leave (before last session ends)
            $isEarlyLeave = $boundaries['early_leave_before'] && $now->lt($boundaries['early_leave_before']);

            return $this->apiResponse([
                'allowed' => true,
                'message' => $isEarlyLeave
                    ? 'Anda akan tercatat pulang cepat (sebelum sesi terakhir selesai)'
                    : 'Silakan lanjutkan absensi',
                'server_time' => $now->format('H:i:s'),
                'schedule_type' => 'teaching_based',
                'has_teaching_schedule' => true,
                'will_be_early_leave' => $isEarlyLeave,
                'last_session' => $boundaries['last_session'] ?? null,
            ], 'Validation passed');
        }
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
                ? min(100, round((($stats->present ?? 0) + ($stats->late ?? 0)) / $totalEmployees * 100, 1))
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

    public function adminStats(Request $request)
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
            'presentToday' => $stats->present ?? 0,
            'lateToday' => $stats->late ?? 0,
            'absentToday' => max(0, $totalEmployees - ($stats->present ?? 0) - ($stats->late ?? 0) - ($stats->on_leave ?? 0)),
            'onLeaveToday' => $stats->on_leave ?? 0,
            'attendanceRate' => $totalEmployees > 0
                ? min(100, round((($stats->present ?? 0) + ($stats->late ?? 0)) / $totalEmployees * 100, 1))
                : 0,
        ], 'Admin attendance statistics retrieved');
    }

    public function adminRecords(Request $request)
    {
        $date = $request->get('date', today()->format('Y-m-d'));
        $search = $request->get('search');
        $status = $request->get('status');

        $query = Attendance::with(['employee:id,employee_id,full_name,employee_type'])
            ->whereDate('date', $date);

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $records = $query->orderBy('check_in_time', 'asc')
            ->get()
            ->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'employee' => [
                        'id' => $attendance->employee->id,
                        'name' => $attendance->employee->full_name,
                        'employeeId' => $attendance->employee->employee_id,
                        'type' => $attendance->employee->employee_type,
                    ],
                    'date' => $attendance->date,
                    'checkIn' => $attendance->check_in_time?->format('H:i'),
                    'checkOut' => $attendance->check_out_time?->format('H:i'),
                    'status' => $attendance->status,
                    'notes' => $attendance->notes,
                ];
            });

        return $this->apiResponse($records, 'Admin attendance records retrieved');
    }

    public function approve(Request $request, $id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return $this->errorResponse('Attendance not found', 404);
        }

        $attendance->update([
            'status' => 'present',
            'notes' => $attendance->notes . ' | Approved by admin',
        ]);

        return $this->apiResponse($attendance->fresh(), 'Attendance approved');
    }

    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $attendance = Attendance::find($id);

        if (!$attendance) {
            return $this->errorResponse('Attendance not found', 404);
        }

        $attendance->update([
            'status' => 'absent',
            'notes' => ($attendance->notes ? $attendance->notes . ' | ' : '') . 'Rejected: ' . $validated['reason'],
        ]);

        return $this->apiResponse($attendance->fresh(), 'Attendance rejected');
    }

    public function manualEntry(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'check_in' => 'required|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        $attendance = Attendance::create([
            'employee_id' => $validated['employee_id'],
            'date' => $validated['date'],
            'check_in_time' => $validated['check_in'],
            'check_out_time' => $validated['check_out'] ?? null,
            'status' => $validated['check_out'] ? 'present' : 'present',
            'notes' => $validated['notes'] ?? 'Manual entry by admin',
        ]);

        return $this->apiResponse($attendance->fresh(), 'Manual attendance created');
    }
}
