<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollItem extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'payroll_id',
        'type',
        'category',
        'description',
        'amount',
        'quantity',
        'rate',
        'is_taxable',
        'is_statutory',
        'calculation_method',
        'reference_data',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_statutory' => 'boolean',
        'reference_data' => 'array',
        'metadata' => 'array',
    ];

    /**
     * The types available for payroll items.
     */
    const TYPE_EARNING = 'earning';

    const TYPE_DEDUCTION = 'deduction';

    const TYPE_BONUS = 'bonus';

    /**
     * The categories available for payroll items.
     */
    const CATEGORY_BASIC_SALARY = 'basic_salary';

    const CATEGORY_OVERTIME = 'overtime';

    const CATEGORY_ALLOWANCE = 'allowance';

    const CATEGORY_COMMISSION = 'commission';

    const CATEGORY_HOLIDAY_PAY = 'holiday_pay';

    const CATEGORY_SICK_LEAVE = 'sick_leave';

    const CATEGORY_VACATION_PAY = 'vacation_pay';

    const CATEGORY_BONUS = 'bonus';

    const CATEGORY_TAX = 'tax';

    const CATEGORY_INSURANCE = 'insurance';

    const CATEGORY_RETIREMENT = 'retirement';

    const CATEGORY_LOAN_DEDUCTION = 'loan_deduction';

    const CATEGORY_UNPAID_LEAVE = 'unpaid_leave';

    const CATEGORY_GARNISHMENT = 'garnishment';

    const CATEGORY_OTHER = 'other';

    /**
     * The calculation methods available for payroll items.
     */
    const CALCULATION_FIXED = 'fixed';

    const CALCULATION_PERCENTAGE = 'percentage';

    const CALCULATION_HOURLY = 'hourly';

    const CALCULATION_DAILY = 'daily';

    const CALCULATION_COMPUTED = 'computed';

    /**
     * Get the payroll that owns this item.
     */
    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * Scope to get items by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get earning items.
     */
    public function scopeEarnings($query)
    {
        return $query->where('type', self::TYPE_EARNING);
    }

    /**
     * Scope to get deduction items.
     */
    public function scopeDeductions($query)
    {
        return $query->where('type', self::TYPE_DEDUCTION);
    }

    /**
     * Scope to get bonus items.
     */
    public function scopeBonuses($query)
    {
        return $query->where('type', self::TYPE_BONUS);
    }

    /**
     * Scope to get items by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get taxable items.
     */
    public function scopeTaxable($query)
    {
        return $query->where('is_taxable', true);
    }

    /**
     * Scope to get statutory items.
     */
    public function scopeStatutory($query)
    {
        return $query->where('is_statutory', true);
    }

    /**
     * Check if item is an earning.
     */
    public function isEarning()
    {
        return $this->type === self::TYPE_EARNING;
    }

    /**
     * Check if item is a deduction.
     */
    public function isDeduction()
    {
        return $this->type === self::TYPE_DEDUCTION;
    }

    /**
     * Check if item is a bonus.
     */
    public function isBonus()
    {
        return $this->type === self::TYPE_BONUS;
    }

    /**
     * Check if item is taxable.
     */
    public function isTaxable()
    {
        return $this->is_taxable;
    }

    /**
     * Check if item is statutory.
     */
    public function isStatutory()
    {
        return $this->is_statutory;
    }

    /**
     * Get the formatted amount.
     */
    public function getFormattedAmountAttribute()
    {
        return '$'.number_format($this->amount, 2);
    }

    /**
     * Get the formatted rate.
     */
    public function getFormattedRateAttribute()
    {
        return '$'.number_format($this->rate, 2);
    }

    /**
     * Get the type badge color.
     */
    public function getTypeColorAttribute()
    {
        return match ($this->type) {
            self::TYPE_EARNING => 'success',
            self::TYPE_DEDUCTION => 'danger',
            self::TYPE_BONUS => 'info',
            default => 'secondary',
        };
    }

    /**
     * Get the category display name.
     */
    public function getCategoryDisplayNameAttribute()
    {
        return match ($this->category) {
            self::CATEGORY_BASIC_SALARY => 'Basic Salary',
            self::CATEGORY_OVERTIME => 'Overtime',
            self::CATEGORY_ALLOWANCE => 'Allowance',
            self::CATEGORY_COMMISSION => 'Commission',
            self::CATEGORY_HOLIDAY_PAY => 'Holiday Pay',
            self::CATEGORY_SICK_LEAVE => 'Sick Leave',
            self::CATEGORY_VACATION_PAY => 'Vacation Pay',
            self::CATEGORY_BONUS => 'Bonus',
            self::CATEGORY_TAX => 'Tax',
            self::CATEGORY_INSURANCE => 'Insurance',
            self::CATEGORY_RETIREMENT => 'Retirement',
            self::CATEGORY_LOAN_DEDUCTION => 'Loan Deduction',
            self::CATEGORY_UNPAID_LEAVE => 'Unpaid Leave',
            self::CATEGORY_GARNISHMENT => 'Garnishment',
            self::CATEGORY_OTHER => 'Other',
            default => ucwords(str_replace('_', ' ', $this->category)),
        };
    }

    /**
     * Calculate amount based on calculation method.
     */
    public function calculateAmount($baseAmount = null)
    {
        switch ($this->calculation_method) {
            case self::CALCULATION_FIXED:
                return $this->amount;

            case self::CALCULATION_PERCENTAGE:
                if ($baseAmount === null) {
                    throw new \InvalidArgumentException('Base amount is required for percentage calculation');
                }

                return ($baseAmount * $this->rate) / 100;

            case self::CALCULATION_HOURLY:
                return $this->quantity * $this->rate;

            case self::CALCULATION_DAILY:
                return $this->quantity * $this->rate;

            case self::CALCULATION_COMPUTED:
                // For computed items, the amount is already calculated
                return $this->amount;

            default:
                return $this->amount;
        }
    }

    /**
     * Update the amount based on calculation method.
     */
    public function updateCalculatedAmount($baseAmount = null)
    {
        if ($this->calculation_method !== self::CALCULATION_FIXED) {
            $this->amount = $this->calculateAmount($baseAmount);
            $this->save();
        }

        return $this->amount;
    }

    /**
     * Get available categories for a specific type.
     */
    public static function getCategoriesForType($type)
    {
        return match ($type) {
            self::TYPE_EARNING => [
                self::CATEGORY_BASIC_SALARY,
                self::CATEGORY_OVERTIME,
                self::CATEGORY_ALLOWANCE,
                self::CATEGORY_COMMISSION,
                self::CATEGORY_HOLIDAY_PAY,
                self::CATEGORY_SICK_LEAVE,
                self::CATEGORY_VACATION_PAY,
                self::CATEGORY_OTHER,
            ],
            self::TYPE_DEDUCTION => [
                self::CATEGORY_TAX,
                self::CATEGORY_INSURANCE,
                self::CATEGORY_RETIREMENT,
                self::CATEGORY_LOAN_DEDUCTION,
                self::CATEGORY_UNPAID_LEAVE,
                self::CATEGORY_GARNISHMENT,
                self::CATEGORY_OTHER,
            ],
            self::TYPE_BONUS => [self::CATEGORY_BONUS, self::CATEGORY_COMMISSION, self::CATEGORY_OTHER],
            default => [],
        };
    }

    /**
     * Get available calculation methods.
     */
    public static function getCalculationMethods()
    {
        return [
            self::CALCULATION_FIXED => 'Fixed Amount',
            self::CALCULATION_PERCENTAGE => 'Percentage',
            self::CALCULATION_HOURLY => 'Hourly Rate',
            self::CALCULATION_DAILY => 'Daily Rate',
            self::CALCULATION_COMPUTED => 'Computed',
        ];
    }

    /**
     * Create a basic salary item.
     */
    public static function createBasicSalaryItem($payrollId, $amount, $description = 'Basic Salary')
    {
        return self::create([
            'payroll_id' => $payrollId,
            'type' => self::TYPE_EARNING,
            'category' => self::CATEGORY_BASIC_SALARY,
            'description' => $description,
            'amount' => $amount,
            'calculation_method' => self::CALCULATION_FIXED,
            'is_taxable' => true,
            'is_statutory' => false,
        ]);
    }

    /**
     * Create an overtime item.
     */
    public static function createOvertimeItem($payrollId, $hours, $rate, $description = 'Overtime')
    {
        return self::create([
            'payroll_id' => $payrollId,
            'type' => self::TYPE_EARNING,
            'category' => self::CATEGORY_OVERTIME,
            'description' => $description,
            'amount' => $hours * $rate,
            'quantity' => $hours,
            'rate' => $rate,
            'calculation_method' => self::CALCULATION_HOURLY,
            'is_taxable' => true,
            'is_statutory' => false,
        ]);
    }

    /**
     * Create an unpaid leave deduction item.
     */
    public static function createUnpaidLeaveItem(
        $payrollId,
        $days,
        $dailyRate,
        $description = 'Unpaid Leave',
    ) {
        return self::create([
            'payroll_id' => $payrollId,
            'type' => self::TYPE_DEDUCTION,
            'category' => self::CATEGORY_UNPAID_LEAVE,
            'description' => $description,
            'amount' => $days * $dailyRate,
            'quantity' => $days,
            'rate' => $dailyRate,
            'calculation_method' => self::CALCULATION_DAILY,
            'is_taxable' => false,
            'is_statutory' => false,
        ]);
    }

    /**
     * Create a tax deduction item.
     */
    public static function createTaxItem(
        $payrollId,
        $taxableAmount,
        $taxRate,
        $description = 'Income Tax',
    ) {
        return self::create([
            'payroll_id' => $payrollId,
            'type' => self::TYPE_DEDUCTION,
            'category' => self::CATEGORY_TAX,
            'description' => $description,
            'amount' => ($taxableAmount * $taxRate) / 100,
            'rate' => $taxRate,
            'calculation_method' => self::CALCULATION_PERCENTAGE,
            'is_taxable' => false,
            'is_statutory' => true,
        ]);
    }

    /**
     * Create a bonus item.
     */
    public static function createBonusItem($payrollId, $amount, $description = 'Bonus')
    {
        return self::create([
            'payroll_id' => $payrollId,
            'type' => self::TYPE_BONUS,
            'category' => self::CATEGORY_BONUS,
            'description' => $description,
            'amount' => $amount,
            'calculation_method' => self::CALCULATION_FIXED,
            'is_taxable' => true,
            'is_statutory' => false,
        ]);
    }
}
