<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;

class Payroll extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'employee_id',
        'payroll_period_start',
        'payroll_period_end',
        'pay_date',
        'gross_salary',
        'total_deductions',
        'total_bonuses',
        'net_salary',
        'worked_hours',
        'overtime_hours',
        'leave_days_taken',
        'leave_days_paid',
        'leave_days_unpaid',
        'status',
        'approved_by',
        'approved_at',
        'processed_by',
        'processed_at',
        'notes',
        'metadata'
    ];

    protected $casts = [
        'payroll_period_start' => 'date',
        'payroll_period_end' => 'date',
        'pay_date' => 'date',
        'gross_salary' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_bonuses' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'worked_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'leave_days_taken' => 'decimal:2',
        'leave_days_paid' => 'decimal:2',
        'leave_days_unpaid' => 'decimal:2',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * The statuses available for payroll.
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PROCESSED = 'processed';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the employee that owns this payroll record.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the payroll items for this payroll.
     */
    public function payrollItems()
    {
        return $this->hasMany(PayrollItem::class);
    }

    /**
     * Get the employee who approved this payroll.
     */
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    /**
     * Get the employee who processed this payroll.
     */
    public function processor()
    {
        return $this->belongsTo(Employee::class, 'processed_by');
    }

    /**
     * Get attendance records for this payroll period.
     */
    public function attendanceRecords()
    {
        return $this->employee->attendances()
            ->whereBetween('date', [$this->payroll_period_start, $this->payroll_period_end]);
    }

    /**
     * Get leave records for this payroll period.
     */
    public function leaveRecords()
    {
        return $this->employee->leaves()
            ->where('status', Leave::STATUS_APPROVED)
            ->where(function ($query) {
                $query->whereBetween('start_date', [$this->payroll_period_start, $this->payroll_period_end])
                    ->orWhereBetween('end_date', [$this->payroll_period_start, $this->payroll_period_end])
                    ->orWhere(function ($q) {
                        $q->where('start_date', '<=', $this->payroll_period_start)
                          ->where('end_date', '>=', $this->payroll_period_end);
                    });
            });
    }

    /**
     * Scope to get payroll records by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get draft payroll records.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope to get pending payroll records.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get approved payroll records.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to get processed payroll records.
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', self::STATUS_PROCESSED);
    }

    /**
     * Scope to get paid payroll records.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope to get payroll records for a specific period.
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('payroll_period_start', '>=', $startDate)
            ->where('payroll_period_end', '<=', $endDate);
    }

    /**
     * Scope to get current month payroll records.
     */
    public function scopeCurrentMonth($query)
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        return $query->forPeriod($startOfMonth, $endOfMonth);
    }

    /**
     * Check if payroll is in draft status.
     */
    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if payroll is pending approval.
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payroll is approved.
     */
    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if payroll is processed.
     */
    public function isProcessed()
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    /**
     * Check if payroll is paid.
     */
    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if payroll can be edited.
     */
    public function canBeEdited()
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    /**
     * Check if payroll can be approved.
     */
    public function canBeApproved()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payroll can be processed.
     */
    public function canBeProcessed()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Get the payroll period in human readable format.
     */
    public function getPayrollPeriodAttribute()
    {
        return $this->payroll_period_start->format('M j') . ' - ' . $this->payroll_period_end->format('M j, Y');
    }

    /**
     * Get the status badge color.
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'info',
            self::STATUS_PROCESSED => 'primary',
            self::STATUS_PAID => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get the formatted gross salary.
     */
    public function getFormattedGrossSalaryAttribute()
    {
        return '$' . number_format($this->gross_salary, 2);
    }

    /**
     * Get the formatted net salary.
     */
    public function getFormattedNetSalaryAttribute()
    {
        return '$' . number_format($this->net_salary, 2);
    }

    /**
     * Get the formatted total deductions.
     */
    public function getFormattedTotalDeductionsAttribute()
    {
        return '$' . number_format($this->total_deductions, 2);
    }

    /**
     * Get the formatted total bonuses.
     */
    public function getFormattedTotalBonusesAttribute()
    {
        return '$' . number_format($this->total_bonuses, 2);
    }

    /**
     * Calculate net salary from gross salary, deductions, and bonuses.
     */
    public function calculateNetSalary()
    {
        return $this->gross_salary - $this->total_deductions + $this->total_bonuses;
    }

    /**
     * Update net salary based on current values.
     */
    public function updateNetSalary()
    {
        $this->net_salary = $this->calculateNetSalary();
        $this->save();
        return $this->net_salary;
    }

    /**
     * Generate payroll reference number.
     */
    public function generateReferenceNumber()
    {
        $employeeId = $this->employee->employee_id;
        $period = $this->payroll_period_start->format('Ym');
        return "PAY-{$employeeId}-{$period}";
    }

    /**
     * Get the payroll reference number.
     */
    public function getReferenceNumberAttribute()
    {
        return $this->generateReferenceNumber();
    }

    /**
     * Approve the payroll.
     */
    public function approve($approvedBy = null)
    {
        if (!$this->canBeApproved()) {
            throw new \Exception('Payroll cannot be approved in current status: ' . $this->status);
        }

        $this->status = self::STATUS_APPROVED;
        $this->approved_by = $approvedBy ?? auth()->user()->employee?->id;
        $this->approved_at = now();
        $this->save();

        return $this;
    }

    /**
     * Process the payroll.
     */
    public function process($processedBy = null)
    {
        if (!$this->canBeProcessed()) {
            throw new \Exception('Payroll cannot be processed in current status: ' . $this->status);
        }

        $this->status = self::STATUS_PROCESSED;
        $this->processed_by = $processedBy ?? auth()->user()->employee?->id;
        $this->processed_at = now();
        $this->save();

        return $this;
    }

    /**
     * Mark payroll as paid.
     */
    public function markAsPaid()
    {
        if ($this->status !== self::STATUS_PROCESSED) {
            throw new \Exception('Payroll must be processed before marking as paid');
        }

        $this->status = self::STATUS_PAID;
        $this->save();

        return $this;
    }

    /**
     * Cancel the payroll.
     */
    public function cancel()
    {
        if (!$this->canBeEdited()) {
            throw new \Exception('Payroll cannot be cancelled in current status: ' . $this->status);
        }

        $this->status = self::STATUS_CANCELLED;
        $this->save();

        return $this;
    }

    /**
     * Get payroll items by type.
     */
    public function getItemsByType($type)
    {
        return $this->payrollItems()->where('type', $type)->get();
    }

    /**
     * Get earnings items.
     */
    public function getEarnings()
    {
        return $this->getItemsByType('earning');
    }

    /**
     * Get deduction items.
     */
    public function getDeductions()
    {
        return $this->getItemsByType('deduction');
    }

    /**
     * Get bonus items.
     */
    public function getBonuses()
    {
        return $this->getItemsByType('bonus');
    }

    /**
     * Calculate total earnings from payroll items.
     */
    public function calculateTotalEarnings()
    {
        return $this->payrollItems()
            ->where('type', 'earning')
            ->sum('amount');
    }

    /**
     * Calculate total deductions from payroll items.
     */
    public function calculateTotalDeductions()
    {
        return $this->payrollItems()
            ->where('type', 'deduction')
            ->sum('amount');
    }

    /**
     * Calculate total bonuses from payroll items.
     */
    public function calculateTotalBonuses()
    {
        return $this->payrollItems()
            ->where('type', 'bonus')
            ->sum('amount');
    }

    /**
     * Recalculate all totals from payroll items.
     */
    public function recalculateTotals()
    {
        $this->gross_salary = $this->calculateTotalEarnings();
        $this->total_deductions = $this->calculateTotalDeductions();
        $this->total_bonuses = $this->calculateTotalBonuses();
        $this->net_salary = $this->calculateNetSalary();
        $this->save();

        return $this;
    }
}