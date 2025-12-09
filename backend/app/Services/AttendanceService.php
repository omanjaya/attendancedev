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

            // === NEW: Validate schedule before allowing check-in ===
            $effectiveSchedule = $employee->getEffectiveScheduleForDate(now());
            if (!($effectiveSchedule['can_attend'] ?? true)) {
                $message = $effectiveSchedule['message'] ?? 'Tidak memiliki jadwal untuk absen hari ini';
                throw new \Exception($message);
            }

            // Validate location if required
            if (config('attendance.require_location_verification')) {
                if (!$this->validateLocation($locationData, $employee)) {
                    throw new \Exception('Invalid location for check-in');
                }
            }

            // Verify face if provided
            if ($faceData && config('attendance.require_face_verification')) {
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

            // Update attendance record
            $attendance->update([
                'check_out_time' => $currentTime,
                'check_out_latitude' => $locationData['latitude'] ?? null,
                'check_out_longitude' => $locationData['longitude'] ?? null,
                'check_out_confidence' => $faceData['confidence'] ?? null,
                'total_hours' => $totalHours,
                'time_verification' => $attendanceTime['verification'],
                'metadata' => $metadata,
            ]);

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

        $distance = $this->calculateDistance(
            $locationData['latitude'],
            $locationData['longitude'],
            $employeeLocation->latitude,
            $employeeLocation->longitude
        );

        return $distance <= ($employeeLocation->radius ?? config('attendance.default_location_radius', 100));
    }

    /**
     * Calculate working hours
     */
    public function calculateWorkingHours(Attendance $attendance): float
    {
        if (!$attendance->check_in_time || !$attendance->check_out_time) {
            return 0;
        }

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
     */
    private function determineStatus(Carbon $time, string $type, Employee $employee): string
    {
        // Use effective schedule which considers employee type and teaching schedules
        $effectiveSchedule = $employee->getEffectiveScheduleForDate($time);
        
        // If no schedule or cannot attend, return present (validation should happen before)
        if (!($effectiveSchedule['can_attend'] ?? false) || !$effectiveSchedule['start_time']) {
            return 'present';
        }

        if ($type === 'check_in') {
            $scheduledTime = Carbon::parse($effectiveSchedule['start_time']);
            // Use dynamic late tolerance from employee type
            $graceMinutes = $effectiveSchedule['late_tolerance'] ?? config('attendance.late_grace_minutes', 15);

            if ($time->gt($scheduledTime->addMinutes($graceMinutes))) {
                return 'late';
            }
        }

        return 'present';
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
}