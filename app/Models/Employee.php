<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\Auditable;

class Employee extends Model
{
    use HasFactory, SoftDeletes, HasUuids, Auditable;

    protected $fillable = [
        'user_id',
        'employee_id',
        'employee_type',
        'first_name',
        'last_name',
        'phone',
        'photo_path',
        'hire_date',
        'salary_type',
        'salary_amount',
        'hourly_rate',
        'is_active',
        'location_id',
        'metadata'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'salary_amount' => 'decimal:2',
        'hourly_rate' => 'decimal:2'
    ];

    // Audit configuration
    protected $auditExclude = ['updated_at', 'created_at'];
    protected $auditTags = ['employee', 'personnel'];
    protected $auditTimestamps = false;

    /**
     * Get the user that owns the employee.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full name attribute.
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get the location that the employee belongs to.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the periods assigned to this employee.
     */
    public function periods()
    {
        return $this->belongsToMany(Period::class, 'employee_schedules')
                    ->withPivot(['effective_date', 'end_date', 'is_active', 'metadata'])
                    ->withTimestamps();
    }

    /**
     * Get the schedules for this employee.
     */
    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    /**
     * Get current active schedules.
     */
    public function currentSchedules()
    {
        return $this->schedules()->active()->current();
    }

    /**
     * Get attendance records for this employee.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get today's attendance.
     */
    public function todayAttendance()
    {
        return $this->attendances()->today();
    }

    /**
     * Check if employee is currently checked in today.
     */
    public function isCheckedInToday()
    {
        $todayAttendance = $this->attendances()->today()->first();
        return $todayAttendance && $todayAttendance->isCheckedIn();
    }

    /**
     * Get the photo URL attribute.
     */
    public function getPhotoUrlAttribute()
    {
        if ($this->photo_path) {
            return asset('storage/' . $this->photo_path);
        }
        
        // Generate default avatar using UI Avatars
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->full_name) . '&background=206bc4&color=fff&size=200';
    }

    /**
     * Get leaves for this employee.
     */
    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    /**
     * Get leave balances for this employee.
     */
    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /**
     * Get leaves approved by this employee.
     */
    public function approvedLeaves()
    {
        return $this->hasMany(Leave::class, 'approved_by');
    }

    /**
     * Get current year leave balance for a specific leave type.
     */
    public function getLeaveBalance($leaveTypeId, $year = null)
    {
        $year = $year ?? date('Y');
        
        return $this->leaveBalances()
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();
    }

    /**
     * Get payroll records for this employee.
     */
    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    /**
     * Get current month payroll.
     */
    public function currentMonthPayroll()
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        
        return $this->payrolls()
            ->where('payroll_period_start', '>=', $startOfMonth)
            ->where('payroll_period_end', '<=', $endOfMonth)
            ->first();
    }

    /**
     * Get approved payrolls processed by this employee.
     */
    public function approvedPayrolls()
    {
        return $this->hasMany(Payroll::class, 'approved_by');
    }

    /**
     * Get processed payrolls processed by this employee.
     */
    public function processedPayrolls()
    {
        return $this->hasMany(Payroll::class, 'processed_by');
    }
}
