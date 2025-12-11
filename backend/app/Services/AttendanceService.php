<?php

namespace App\Services;

use App\Contracts\Services\AttendanceServiceInterface;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Location;
use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;

class AttendanceService implements AttendanceServiceInterface
{
    public function __construct(
        private readonly FaceRecognitionService $faceService,
        private readonly NotificationService $notificationService,
        private readonly TimeService $timeService,
        private readonly EmailNotificationService $emailService
    ) {}

    /**
     * Process employee check-in
     */
    public function checkIn(
        Employee $employee,
        array $locationData,
        ?array $faceData = null,
        ?UploadedFile $photo = null,
        bool $overwrite = false
    ): Attendance {
        return DB::transaction(function () use ($employee, $locationData, $faceData, $photo, $overwrite) {
            // Check if already checked in today
            $existingAttendance = $this->getTodayAttendance($employee);
            if ($existingAttendance && $existingAttendance->check_in_time && !$overwrite) {
                throw new \Exception('Already checked in today');
            }

            // === Validate schedule before allowing check-in ===
            // IMPORTANT: Default to FALSE if can_attend is not set (strict validation)
            $effectiveSchedule = $employee->getEffectiveScheduleForDate(now());
            $canAttend = $effectiveSchedule['can_attend'] ?? false;

            if (!$canAttend) {
                $scheduleType = $effectiveSchedule['schedule_type'] ?? 'none';
                $message = $effectiveSchedule['message'] ?? 'Tidak memiliki jadwal untuk absen hari ini';

                // Provide specific error messages based on schedule type
                if ($scheduleType === 'none') {
                    $message = 'Tidak ada jadwal yang di-assign untuk hari ini';
                } elseif ($scheduleType === 'holiday') {
                    $message = $effectiveSchedule['holiday_name']
                        ? 'Hari ini adalah hari libur: ' . $effectiveSchedule['holiday_name']
                        : 'Hari ini adalah hari libur';
                } elseif ($scheduleType === 'no_teaching') {
                    $message = 'Tidak ada jadwal mengajar hari ini';
                }

                throw new \Exception($message);
            }

            // === Validate check-in time boundaries ===
            $this->validateCheckInTime($employee);

            // Validate location if required
            if (config('attendance.require_location_verification')) {
                if (!$this->validateLocation($locationData, $employee)) {
                    throw new \Exception('Invalid location for check-in');
                }
            }

            // Verify face if descriptor provided (skip if already verified via DeepFace API)
            if ($faceData && config('attendance.require_face_verification') && !empty($faceData['descriptor'])) {
                $verification = $this->faceService->verifyFace($faceData['descriptor'], $employee);
                if (!$verification['success']) {
                    throw new \Exception('Face verification failed');
                }
            }

            // Store photo if provided
            $photoPath = null;
            if ($photo) {
                $photoPath = $photo->store('attendance-photos/' . date('Y/m/d'), 'public');
            }

            // Get accurate WITA time from TimeService
            $attendanceTime = $this->timeService->getAttendanceTime();
            $currentTime = $attendanceTime['timestamp'];
            
            // Prepare metadata
            $metadata = [];
            if ($photoPath) {
                $metadata['check_in_photo'] = $photoPath;
            }

            // Create or update attendance record
            // Create or update attendance record
            $date = $this->timeService->today()->format('Y-m-d');
            
            $attendance = Attendance::withTrashed()
                ->where('employee_id', $employee->id)
                ->where('date', $date)
                ->first();

            if ($attendance) {
                if ($attendance->trashed()) {
                    $attendance->restore();
                }
                $attendance->update([
                    'check_in_time' => $currentTime->format('Y-m-d H:i:s'),
                    'check_in_latitude' => $locationData['latitude'] ?? null,
                    'check_in_longitude' => $locationData['longitude'] ?? null,
                    'check_in_confidence' => $faceData['confidence'] ?? null,
                    'status' => $this->determineStatus($currentTime, 'check_in', $employee),
                    'time_verification' => $attendanceTime['verification'],
                    'metadata' => $metadata,
                ]);
            } else {
                try {
                    $attendance = Attendance::create([
                        'employee_id' => $employee->id,
                        'date' => $date,
                        'check_in_time' => $currentTime->format('Y-m-d H:i:s'),
                        'check_in_latitude' => $locationData['latitude'] ?? null,
                        'check_in_longitude' => $locationData['longitude'] ?? null,
                        'check_in_confidence' => $faceData['confidence'] ?? null,
                        'status' => $this->determineStatus($currentTime, 'check_in', $employee),
                        'time_verification' => $attendanceTime['verification'],
                        'metadata' => $metadata,
                    ]);
                } catch (\Exception $e) {
                    // Check for duplicate entry/integrity constraint violation
                    // MySQL: 1062, SQLSTATE: 23000
                    $isDuplicate = false;
                    if ($e instanceof \Illuminate\Database\QueryException) {
                        if (($e->errorInfo[1] ?? null) === 1062) $isDuplicate = true;
                        if ($e->getCode() === '23000') $isDuplicate = true;
                    }
                    if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'Integrity constraint violation')) {
                        $isDuplicate = true;
                    }

                    if ($isDuplicate) {
                        // Record exists (likely race condition or soft deleted), so update it
                        // Use whereDate to handle potential time component differences in DB (especially SQLite)
                        $attendance = Attendance::withTrashed()
                            ->where('employee_id', $employee->id)
                            ->whereDate('date', $date)
                            ->first();
                            
                        if ($attendance) {
                            if ($attendance->trashed()) {
                                $attendance->restore();
                            }
                            
                            $attendance->update([
                                'check_in_time' => $currentTime->format('Y-m-d H:i:s'),
                                'check_in_latitude' => $locationData['latitude'] ?? null,
                                'check_in_longitude' => $locationData['longitude'] ?? null,
                                'check_in_confidence' => $faceData['confidence'] ?? null,
                                'status' => $this->determineStatus($currentTime, 'check_in', $employee),
                                'time_verification' => $attendanceTime['verification'],
                                'metadata' => $metadata,
                            ]);
                        } else {
                            // If we still can't find it, it's a true mystery. 
                            // Log it and re-throw.
                            \Illuminate\Support\Facades\Log::error('Duplicate entry error but record not found via whereDate', [
                                'employee_id' => $employee->id,
                                'date' => $date,
                                'original_error' => $e->getMessage()
                            ]);
                            throw $e;
                        }
                    } else {
                        throw $e;
                    }
                }
            }

            // Send notification
            $this->notificationService->send(
                $employee->user,
                'attendance.checked_in',
                [
                    'time' => $attendanceTime['formatted']['time'],
                    'date' => $attendanceTime['formatted']['date'],
                ]
            );

            // Send email notification (queued)
            $this->emailService->sendCheckInEmail($employee, $attendance);

            return $attendance;
        });
    }

    /**
     * Process employee check-out
     */
    public function checkOut(
        Employee $employee,
        array $locationData,
        ?array $faceData = null,
        ?UploadedFile $photo = null,
        bool $overwrite = false
    ): Attendance {
        return DB::transaction(function () use ($employee, $locationData, $faceData, $photo, $overwrite) {
            $attendance = $this->getTodayAttendance($employee);
            
            if (!$attendance || !$attendance->check_in_time) {
                throw new \Exception('No check-in found for today');
            }

            if ($attendance->check_out_time && !$overwrite) {
                throw new \Exception('Already checked out today');
            }

            // === Validate check-out time boundaries ===
            $this->validateCheckOutTime($employee);

            // Validate location if required
            if (config('attendance.require_location_verification')) {
                if (!$this->validateLocation($locationData, $employee)) {
                    throw new \Exception('Invalid location for check-out');
                }
            }

            // Verify face if provided
            if ($faceData && config('attendance.require_face_verification')) {
                if (!empty($faceData['descriptor'])) {
                    $verification = $this->faceService->verifyFace($faceData['descriptor'], $employee);
                    if (!$verification['success']) {
                        throw new \Exception('Face verification failed');
                    }
                }
            }

            // Store photo if provided
            $photoPath = null;
            if ($photo) {
                $photoPath = $photo->store('attendance-photos/' . date('Y/m/d'), 'public');
            }

            // Get accurate WITA time from TimeService
            $attendanceTime = $this->timeService->getAttendanceTime();
            $currentTime = $attendanceTime['timestamp'];
            
            // Set check-out time first for calculations
            $attendance->check_out_time = $currentTime;
            
            // Calculate working hours
            $totalHours = $this->calculateWorkingHours($attendance);
            $attendance->total_hours = $totalHours;

            // Calculate overtime
            $overtimeHours = $this->calculateOvertimeHours($attendance, $employee);
            
            // Prepare metadata
            $metadata = $attendance->metadata ?? [];
            if ($photoPath) {
                $metadata['check_out_photo'] = $photoPath;
            }
            $metadata['overtime_hours'] = $overtimeHours;

            // Determine if early leave
            $checkOutStatus = $this->determineCheckOutStatus($currentTime, $employee);

            // Update attendance record
            $updateData = [
                'check_out_time' => $currentTime,
                'check_out_latitude' => $locationData['latitude'] ?? null,
                'check_out_longitude' => $locationData['longitude'] ?? null,
                'check_out_confidence' => $faceData['confidence'] ?? null,
                'total_hours' => $totalHours,
                'time_verification' => $attendanceTime['verification'],
                'metadata' => $metadata,
            ];

            // Update status if early leave
            if ($checkOutStatus === 'early_leave') {
                $updateData['status'] = 'early_leave';
            }

            $attendance->update($updateData);

            // Send notification
            $this->notificationService->send(
                $employee->user,
                'attendance.checked_out',
                [
                    'time' => Carbon::now()->format('H:i'),
                    'hours' => $attendance->working_hours,
                ]
            );

            // Send email notification (queued)
            $this->emailService->sendCheckOutEmail($employee, $attendance);

            return $attendance->fresh();
        });
    }

    /**
     * Get attendance status for an employee
     */
    public function getAttendanceStatus(Employee $employee): array
    {
        $today = $this->getTodayAttendance($employee);
        $schedule = $employee->getTodaySchedule();

        return [
            'checked_in' => $today && $today->check_in !== null,
            'checked_out' => $today && $today->check_out !== null,
            'check_in_time' => $today?->check_in?->format('H:i:s'),
            'check_out_time' => $today?->check_out?->format('H:i:s'),
            'working_hours' => $today?->working_hours ?? 0,
            'status' => $today?->status ?? 'absent',
            'schedule' => $schedule ? [
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
            ] : null,
        ];
    }

    /**
     * Get attendance statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $query = Attendance::query();

        // Apply filters
        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        $attendances = $query->get();

        return [
            'total_days' => $attendances->count(),
            'present_days' => $attendances->whereNotNull('check_in')->count(),
            'absent_days' => $attendances->whereNull('check_in')->count(),
            'late_days' => $attendances->where('status', 'late')->count(),
            'early_leave_days' => $attendances->where('status', 'early_leave')->count(),
            'total_working_hours' => $attendances->sum('working_hours'),
            'total_overtime_hours' => $attendances->sum('overtime_hours'),
            'attendance_rate' => $attendances->count() > 0
                ? ($attendances->whereNotNull('check_in')->count() / $attendances->count()) * 100
                : 0,
        ];
    }

    /**
     * Get attendance history
     */
    public function getHistory(Employee $employee, array $filters = []): Collection
    {
        $query = $employee->attendances();

        if (isset($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Process manual attendance entry
     */
    public function manualEntry(
        Employee $employee,
        string $type,
        string $time,
        string $reason,
        ?int $approvedBy = null
    ): Attendance {
        return DB::transaction(function () use ($employee, $type, $time, $reason, $approvedBy) {
            $date = Carbon::parse($time)->toDateString();
            $attendance = Attendance::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => $date,
                ]
            );

            $updateData = [
                'manual_entry' => true,
                'manual_entry_reason' => $reason,
                'manual_entry_by' => $approvedBy ?? auth()->id(),
            ];

            if ($type === 'check_in') {
                $updateData['check_in'] = Carbon::parse($time);
                $updateData['status'] = $this->determineStatus(Carbon::parse($time), 'check_in', $employee);
            } else {
                $updateData['check_out'] = Carbon::parse($time);
                $updateData['working_hours'] = $this->calculateWorkingHours($attendance);
                $updateData['overtime_hours'] = $this->calculateOvertimeHours($attendance, $employee);
            }

            $attendance->update($updateData);

            return $attendance->fresh();
        });
    }

    /**
     * Validate attendance location
     */
    public function validateLocation(array $locationData, Employee $employee): bool
    {
        $employeeLocation = $employee->location;
        if (!$employeeLocation) {
            return true; // No location restriction
        }

        // Check if employee is WFA/Remote (location has no GPS or very large radius)
        $isWfa = !$employeeLocation->latitude
            || !$employeeLocation->longitude
            || ($employeeLocation->radius_meters ?? 0) >= 9999999
            || str_contains(strtolower($employeeLocation->name ?? ''), 'remote')
            || str_contains(strtolower($employeeLocation->name ?? ''), 'wfa');

        if ($isWfa) {
            return true; // WFA employees are always location-verified
        }

        $distance = $this->calculateDistance(
            $locationData['latitude'],
            $locationData['longitude'],
            $employeeLocation->latitude,
            $employeeLocation->longitude
        );

        return $distance <= ($employeeLocation->radius_meters ?? $employeeLocation->radius ?? config('attendance.default_location_radius', 100));
    }

    /**
     * Calculate working hours
     */
    public function calculateWorkingHours(Attendance $attendance): float
    {
        if (!$attendance->check_in_time || !$attendance->check_out_time) {
            return 0;
        }

        // Get the employee for this attendance
        $employee = $attendance->employee;

        // === GURU HONORER: Use sum of teaching hours, not range ===
        if ($employee && $employee->isGuruHonorer()) {
            return $this->calculateWorkingHoursForGuruHonorer($employee, $attendance->date);
        }

        // === REGULAR EMPLOYEE: Use check-in to check-out range ===
        $checkIn = Carbon::parse($attendance->check_in_time);
        $checkOut = Carbon::parse($attendance->check_out_time);

        // Subtract break time if configured
        $workingMinutes = $checkOut->diffInMinutes($checkIn);
        $breakMinutes = config('attendance.break_duration_minutes', 60);

        if ($workingMinutes > config('attendance.minimum_hours_for_break', 240)) {
            $workingMinutes -= $breakMinutes;
        }

        return round($workingMinutes / 60, 2);
    }

    /**
     * Export attendance data
     */
    public function export(array $filters = [], string $format = 'xlsx'): string
    {
        $filename = 'attendance_' . date('Y-m-d_His') . '.' . $format;
        $path = 'exports/' . $filename;

        Excel::store(new AttendanceExport($filters), $path, 'public');

        return Storage::disk('public')->url($path);
    }

    /**
     * Get today's attendance for an employee
     */
    private function getTodayAttendance(Employee $employee): ?Attendance
    {
        $today = now('Asia/Makassar')->startOfDay();
        $todayDate = $today->format('Y-m-d');
        
        return $employee->attendances()
            ->whereDate('date', $todayDate)
            ->first();
    }

    /**
     * Determine attendance status
     * - Check-in: 'late' if time > checkin_end_time (batas terlambat)
     * - Check-out: 'early_leave' if time < default_end_time (jam pulang)
     */
    private function determineStatus(Carbon $time, string $type, Employee $employee): string
    {
        // === GURU HONORER: Use TeachingSchedule for late detection ===
        if ($employee->isGuruHonorer()) {
            return $this->determineStatusForGuruHonorer($time, $type, $employee);
        }

        // === REGULAR EMPLOYEE: Use MonthlySchedule ===
        // Get employee's schedule for today
        $employeeSchedule = $employee->getScheduleForDate($time);

        if ($type === 'check_in') {
            // Check late status using checkin_end_time (batas terlambat)
            if ($employeeSchedule && $employeeSchedule->monthlySchedule) {
                $checkinEndTime = $employeeSchedule->monthlySchedule->getRawOriginal('checkin_end_time');
                if ($checkinEndTime) {
                    $lateThreshold = Carbon::parse($time->format('Y-m-d') . ' ' . $checkinEndTime);
                    if ($time->gt($lateThreshold)) {
                        return 'late';
                    }
                }
            }

            // Fallback to effective schedule logic
            $effectiveSchedule = $employee->getEffectiveScheduleForDate($time);
            if (($effectiveSchedule['can_attend'] ?? false) && $effectiveSchedule['start_time']) {
                $scheduledTime = Carbon::parse($effectiveSchedule['start_time']);
                $graceMinutes = $effectiveSchedule['late_tolerance'] ?? config('attendance.late_grace_minutes', 15);
                if ($time->gt($scheduledTime->addMinutes($graceMinutes))) {
                    return 'late';
                }
            }
        }

        return 'present';
    }

    /**
     * Determine attendance status for Guru Honorer
     * Late = check_in_time > teaching_start_time (first session, no tolerance)
     */
    private function determineStatusForGuruHonorer(Carbon $time, string $type, Employee $employee): string
    {
        if ($type === 'check_in') {
            $boundaries = $employee->getGuruHonorerCheckInBoundaries($time);

            if ($boundaries['has_schedule'] && $boundaries['late_after']) {
                // Late if check-in after teaching_start_time (no tolerance)
                if ($time->gt($boundaries['late_after'])) {
                    return 'late';
                }
            }
        }

        return 'present';
    }

    /**
     * Determine check-out status
     * - 'early_leave' if time < default_end_time (jam pulang)
     */
    private function determineCheckOutStatus(Carbon $time, Employee $employee): ?string
    {
        // === GURU HONORER: Use TeachingSchedule for early leave detection ===
        if ($employee->isGuruHonorer()) {
            return $this->determineCheckOutStatusForGuruHonorer($time, $employee);
        }

        // === REGULAR EMPLOYEE: Use MonthlySchedule ===
        $employeeSchedule = $employee->getScheduleForDate($time);

        if ($employeeSchedule && $employeeSchedule->monthlySchedule) {
            $defaultEndTime = $employeeSchedule->monthlySchedule->getRawOriginal('default_end_time');
            if ($defaultEndTime) {
                $endTimeThreshold = Carbon::parse($time->format('Y-m-d') . ' ' . $defaultEndTime);
                if ($time->lt($endTimeThreshold)) {
                    return 'early_leave';
                }
            }
        }

        return null; // No status change needed
    }

    /**
     * Determine check-out status for Guru Honorer
     * Early leave = check_out_time < teaching_end_time (last session)
     */
    private function determineCheckOutStatusForGuruHonorer(Carbon $time, Employee $employee): ?string
    {
        $boundaries = $employee->getGuruHonorerCheckOutBoundaries($time);

        if ($boundaries['has_schedule'] && $boundaries['early_leave_before']) {
            // Early leave if check-out before teaching_end_time (last session)
            if ($time->lt($boundaries['early_leave_before'])) {
                return 'early_leave';
            }
        }

        return null; // No status change needed
    }

    /**
     * Calculate overtime hours
     */
    private function calculateOvertimeHours(Attendance $attendance, Employee $employee): float
    {
        if (!$attendance->check_out_time) {
            return 0;
        }

        $schedule = $employee->getScheduleForDate($attendance->date);
        if (!$schedule) {
            return 0;
        }

        $scheduledHours = Carbon::parse($schedule->start_time)
            ->diffInHours(Carbon::parse($schedule->end_time));

        $overtime = max(0, $attendance->total_hours - $scheduledHours);

        return round($overtime, 2);
    }

    /**
     * Calculate distance between two coordinates
     */
    private function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6371000; // Earth radius in meters

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLatRad = deg2rad($lat2 - $lat1);
        $deltaLonRad = deg2rad($lon2 - $lon1);

        $a = sin($deltaLatRad / 2) * sin($deltaLatRad / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($deltaLonRad / 2) * sin($deltaLonRad / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Validate check-in time boundaries
     * - Must be >= checkin_start_time (mulai absen masuk)
     */
    private function validateCheckInTime(Employee $employee): void
    {
        $now = $this->timeService->now();

        // === GURU HONORER: Use TeachingSchedule for validation ===
        if ($employee->isGuruHonorer()) {
            $this->validateCheckInTimeForGuruHonorer($employee, $now);
            return;
        }

        // === REGULAR EMPLOYEE: Use MonthlySchedule ===
        $currentTime = $now->format('H:i:s');

        // Get employee's schedule for today
        $employeeSchedule = $employee->getScheduleForDate($now);
        if (!$employeeSchedule || !$employeeSchedule->monthlySchedule) {
            return; // No schedule, skip time validation
        }

        $monthlySchedule = $employeeSchedule->monthlySchedule;

        // Get check-in start time (mulai absen masuk)
        $checkinStartTime = $monthlySchedule->getRawOriginal('checkin_start_time');
        if (!$checkinStartTime) {
            return; // No boundary set, allow check-in
        }

        // Compare times
        if ($currentTime < $checkinStartTime) {
            $formattedTime = Carbon::parse($checkinStartTime)->format('H:i');
            throw new \Exception("Belum waktunya absen masuk. Absen masuk dibuka mulai pukul {$formattedTime}");
        }
    }

    /**
     * Validate check-in time for Guru Honorer
     * NOTE: No blocking - guru honorer can check-in anytime
     * Late status will be determined by determineStatus()
     */
    private function validateCheckInTimeForGuruHonorer(Employee $employee, Carbon $now): void
    {
        // No blocking for guru honorer - they can check-in anytime
        // Late status will be determined by determineStatusForGuruHonorer()
    }

    /**
     * Validate check-out time boundaries
     * - Must be >= checkout_start_time (mulai absen pulang)
     * - Must be <= checkout_end_time (batas akhir absen pulang)
     */
    private function validateCheckOutTime(Employee $employee): void
    {
        $now = $this->timeService->now();

        // === GURU HONORER: Use TeachingSchedule for validation ===
        if ($employee->isGuruHonorer()) {
            $this->validateCheckOutTimeForGuruHonorer($employee, $now);
            return;
        }

        // === REGULAR EMPLOYEE: Use MonthlySchedule ===
        $currentTime = $now->format('H:i:s');

        // Get employee's schedule for today
        $employeeSchedule = $employee->getScheduleForDate($now);
        if (!$employeeSchedule || !$employeeSchedule->monthlySchedule) {
            return; // No schedule, skip time validation
        }

        $monthlySchedule = $employeeSchedule->monthlySchedule;

        // Get check-out time boundaries
        $checkoutStartTime = $monthlySchedule->getRawOriginal('checkout_start_time');
        $checkoutEndTime = $monthlySchedule->getRawOriginal('checkout_end_time');

        // Validate check-out start time (mulai absen pulang)
        if ($checkoutStartTime && $currentTime < $checkoutStartTime) {
            $formattedTime = Carbon::parse($checkoutStartTime)->format('H:i');
            throw new \Exception("Belum waktunya absen pulang. Absen pulang dibuka mulai pukul {$formattedTime}");
        }

        // Validate check-out end time (batas akhir)
        if ($checkoutEndTime && $currentTime > $checkoutEndTime) {
            $formattedTime = Carbon::parse($checkoutEndTime)->format('H:i');
            throw new \Exception("Sudah lewat batas absen pulang. Batas akhir absen pulang adalah pukul {$formattedTime}");
        }
    }

    /**
     * Validate check-out time for Guru Honorer
     * NOTE: No blocking - guru honorer can check-out anytime
     * Early leave status will be determined by determineCheckOutStatus()
     */
    private function validateCheckOutTimeForGuruHonorer(Employee $employee, Carbon $now): void
    {
        // No blocking for guru honorer - they can check-out anytime
        // Early leave status will be determined by determineCheckOutStatusForGuruHonorer()
    }

    /**
     * Calculate working hours for Guru Honorer
     * Returns sum of teaching hours (not range between check-in and check-out)
     */
    public function calculateWorkingHoursForGuruHonorer(Employee $employee, $date): float
    {
        return $employee->getTotalTeachingHoursForDate($date);
    }
}