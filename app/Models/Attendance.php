<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'date',
        'check_in_time',
        'check_out_time',
        'total_hours',
        'status',
        'check_in_confidence',
        'check_out_confidence',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_latitude',
        'check_out_longitude',
        'location_verified',
        'check_in_notes',
        'check_out_notes',
        'metadata',
        'is_manual_entry',
        'manual_entry_reason',
        'manual_entry_by',
        'updated_by',
        'notes',
        'check_in',
        'check_out',
        'working_hours',
        'check_in_location',
        'check_out_location',
        'check_in_face_confidence',
        'check_out_face_confidence',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'total_hours' => 'decimal:2',
        'working_hours' => 'decimal:2',
        'check_in_confidence' => 'decimal:4',
        'check_out_confidence' => 'decimal:4',
        'check_in_face_confidence' => 'decimal:4',
        'check_out_face_confidence' => 'decimal:4',
        'check_in_latitude' => 'decimal:8',
        'check_in_longitude' => 'decimal:8',
        'check_out_latitude' => 'decimal:8',
        'check_out_longitude' => 'decimal:8',
        'location_verified' => 'boolean',
        'is_manual_entry' => 'boolean',
        'metadata' => 'array',
        'check_in_location' => 'array',
        'check_out_location' => 'array',
        'notes' => 'array',
    ];

    /**
     * Get the employee that owns the attendance.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who made the manual entry.
     */
    public function manualEntryBy()
    {
        return $this->belongsTo(User::class, 'manual_entry_by');
    }

    /**
     * Get the user who last updated the attendance.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get holidays for the attendance date
     */
    public function holidays()
    {
        return Holiday::getHolidaysForDate($this->date);
    }

    // Schedule Management System Relationships
    public function employeeMonthlySchedule()
    {
        return $this->belongsTo(EmployeeMonthlySchedule::class);
    }

    public function teachingSchedule()
    {
        return $this->belongsTo(TeachingSchedule::class);
    }

    public function holiday()
    {
        return $this->belongsTo(NationalHoliday::class);
    }

    /**
     * Check if attendance date is a holiday
     */
    public function isHoliday()
    {
        return Holiday::isHoliday($this->date);
    }

    /**
     * Get the primary holiday for this date (if any)
     */
    public function primaryHoliday()
    {
        return Holiday::getHolidaysForDate($this->date)->first();
    }

    /**
     * Scope to get today's attendance.
     */
    public function scopeToday($query)
    {
        $today = now('Asia/Makassar')->startOfDay();
        $todayDate = $today->format('Y-m-d');
        return $query->whereDate('date', $todayDate);
    }

    /**
     * Scope to get attendance for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope to get attendance for a date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to get incomplete attendance (no check-out).
     */
    public function scopeIncomplete($query)
    {
        return $query->where('status', 'incomplete');
    }

    /**
     * Scope to get complete attendance.
     */
    public function scopeComplete($query)
    {
        return $query->where('status', '!=', 'incomplete');
    }

    /**
     * Check if employee is currently checked in.
     */
    public function isCheckedIn()
    {
        return ! is_null($this->check_in_time) && is_null($this->check_out_time);
    }

    /**
     * Check if employee is checked out.
     */
    public function isCheckedOut()
    {
        return ! is_null($this->check_in_time) && ! is_null($this->check_out_time);
    }

    /**
     * Calculate total working hours.
     */
    public function calculateTotalHours()
    {
        if ($this->check_in_time && $this->check_out_time) {
            $checkIn = Carbon::parse($this->check_in_time);
            $checkOut = Carbon::parse($this->check_out_time);

            return $checkOut->diffInMinutes($checkIn) / 60;
        }

        return 0;
    }

    /**
     * Update total hours and save.
     */
    public function updateTotalHours()
    {
        $this->total_hours = $this->calculateTotalHours();
        $this->save();

        return $this->total_hours;
    }

    /**
     * Determine attendance status based on time.
     */
    public function determineStatus()
    {
        if (! $this->check_in_time) {
            return 'absent';
        }

        if (! $this->check_out_time) {
            return 'incomplete';
        }

        // Basic status determination - can be enhanced with business rules
        $checkInTime = Carbon::parse($this->check_in_time);
        $standardStartTime = Carbon::parse($this->date->format('Y-m-d').' 09:00:00');
        $standardEndTime = Carbon::parse($this->date->format('Y-m-d').' 17:00:00');

        $isLate = $checkInTime->isAfter($standardStartTime->addMinutes(15));
        $isEarlyDeparture =
          $this->check_out_time && Carbon::parse($this->check_out_time)->isBefore($standardEndTime);

        if ($isLate && $isEarlyDeparture) {
            return 'late'; // Could be 'late_and_early' if you want more granular status
        } elseif ($isLate) {
            return 'late';
        } elseif ($isEarlyDeparture) {
            return 'early_departure';
        }

        return 'present';
    }

    /**
     * Update status and save.
     */
    public function updateStatus()
    {
        $this->status = $this->determineStatus();
        $this->save();

        return $this->status;
    }

    /**
     * Get formatted check-in time.
     */
    public function getFormattedCheckInAttribute()
    {
        return $this->check_in_time ? $this->check_in_time->format('H:i') : null;
    }

    /**
     * Get formatted check-out time.
     */
    public function getFormattedCheckOutAttribute()
    {
        return $this->check_out_time ? $this->check_out_time->format('H:i') : null;
    }

    /**
     * Get working hours in human readable format.
     */
    public function getWorkingHoursFormattedAttribute()
    {
        if (! $this->total_hours) {
            return '0h 0m';
        }

        $hours = floor($this->total_hours);
        $minutes = round(($this->total_hours - $hours) * 60);

        return "{$hours}h {$minutes}m";
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'present' => 'success',
            'absent' => 'danger',
            'late' => 'warning',
            'early_departure' => 'info',
            'incomplete' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Check if location verification is required.
     */
    public function requiresLocationVerification()
    {
        return $this->employee && $this->employee->location;
    }

    /**
     * Verify location against employee's assigned location.
     */
    public function verifyLocation($latitude, $longitude, $maxDistance = 100)
    {
        // 100 meters default
        if (! $this->requiresLocationVerification()) {
            return true;
        }

        // Implement geofencing logic here
        // For now, return true as placeholder
        return true;
    }

    /**
     * Get today's attendance for an employee.
     */
    public static function getTodayAttendance($employeeId)
    {
        return static::where('employee_id', $employeeId)->today()->first();
    }

    /**
     * Create or get today's attendance record.
     */
    public static function getOrCreateToday($employeeId)
    {
        $today = now('Asia/Makassar')->startOfDay();
        $todayDate = $today->format('Y-m-d');
        
        return static::firstOrCreate(
            [
                'employee_id' => $employeeId,
                'date' => $todayDate,
            ],
            [
                'status' => 'incomplete',
            ],
        );
    }
}
