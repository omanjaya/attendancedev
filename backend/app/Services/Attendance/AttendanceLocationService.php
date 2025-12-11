<?php

namespace App\Services\Attendance;

use App\Models\Employee;

class AttendanceLocationService
{
    /**
     * Verify employee location
     */
    public function verifyEmployeeLocation(Employee $employee, float $latitude, float $longitude): bool
    {
        // Basic location verification - can be enhanced with proper geofencing
        if (!$employee->location) {
            return true; // No location restriction
        }

        // For now, return true - implement proper geofencing logic
        // You could use the Haversine formula to calculate distance
        return true;
    }
}
