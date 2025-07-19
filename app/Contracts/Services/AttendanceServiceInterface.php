<?php

namespace App\Contracts\Services;

use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;

interface AttendanceServiceInterface
{
    /**
     * Process employee check-in
     */
    public function checkIn(
        Employee $employee,
        array $locationData,
        ?array $faceData = null,
        ?UploadedFile $photo = null
    ): Attendance;

    /**
     * Process employee check-out
     */
    public function checkOut(
        Employee $employee,
        array $locationData,
        ?array $faceData = null,
        ?UploadedFile $photo = null
    ): Attendance;

    /**
     * Get attendance status for an employee
     */
    public function getAttendanceStatus(Employee $employee): array;

    /**
     * Get attendance statistics
     */
    public function getStatistics(array $filters = []): array;

    /**
     * Get attendance history
     */
    public function getHistory(Employee $employee, array $filters = []): Collection;

    /**
     * Process manual attendance entry
     */
    public function manualEntry(
        Employee $employee,
        string $type,
        string $time,
        string $reason,
        ?int $approvedBy = null
    ): Attendance;

    /**
     * Validate attendance location
     */
    public function validateLocation(array $locationData, Employee $employee): bool;

    /**
     * Calculate working hours
     */
    public function calculateWorkingHours(Attendance $attendance): float;

    /**
     * Export attendance data
     */
    public function export(array $filters = [], string $format = 'xlsx'): string;
}