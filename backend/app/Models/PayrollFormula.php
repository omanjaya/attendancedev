<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollFormula extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'formula_type',
        'formula_expression',
        'base_field',
        'default_amount',
        'percentage_rate',
        'is_taxable',
        'is_active',
        'priority',
        'category',
        'description',
        'conditions',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'percentage_rate' => 'decimal:4',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'conditions' => 'array',
    ];

    /**
     * Formula types
     */
    const TYPE_EARNING = 'earning';
    const TYPE_DEDUCTION = 'deduction';
    const TYPE_BONUS = 'bonus';

    /**
     * Formula calculation types
     */
    const FORMULA_FIXED = 'fixed';
    const FORMULA_PERCENTAGE = 'percentage';
    const FORMULA_CONDITIONAL = 'conditional';
    const FORMULA_CUSTOM = 'custom';

    /**
     * Available base fields for percentage calculation
     */
    const BASE_FIELDS = [
        'base_salary' => 'Gaji Pokok',
        'gross_salary' => 'Gaji Kotor',
        'net_salary' => 'Gaji Bersih',
        'worked_days' => 'Hari Kerja',
        'worked_hours' => 'Jam Kerja',
        'overtime_hours' => 'Jam Lembur',
        'attendance_rate' => 'Tingkat Kehadiran (%)',
    ];

    /**
     * Get the user who created this formula.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this formula.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get only active formulas.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get formulas by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to order by priority.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Calculate the amount based on formula configuration.
     */
    public function calculate(array $context = []): float
    {
        switch ($this->formula_type) {
            case self::FORMULA_FIXED:
                return (float) $this->default_amount;

            case self::FORMULA_PERCENTAGE:
                $baseValue = $context[$this->base_field] ?? 0;
                return $baseValue * ($this->percentage_rate / 100);

            case self::FORMULA_CONDITIONAL:
                return $this->evaluateConditional($context);

            case self::FORMULA_CUSTOM:
                return $this->evaluateCustomExpression($context);

            default:
                return (float) $this->default_amount;
        }
    }

    /**
     * Evaluate conditional formula.
     */
    protected function evaluateConditional(array $context): float
    {
        if (empty($this->conditions)) {
            return (float) $this->default_amount;
        }

        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '>=';
            $value = $condition['value'] ?? 0;
            $result = $condition['result'] ?? 0;

            if (!$field || !isset($context[$field])) {
                continue;
            }

            $fieldValue = $context[$field];
            $matches = match ($operator) {
                '=', '==' => $fieldValue == $value,
                '!=' => $fieldValue != $value,
                '>' => $fieldValue > $value,
                '>=' => $fieldValue >= $value,
                '<' => $fieldValue < $value,
                '<=' => $fieldValue <= $value,
                default => false,
            };

            if ($matches) {
                // Result can be a fixed value or a percentage
                if (isset($condition['is_percentage']) && $condition['is_percentage']) {
                    $baseValue = $context[$this->base_field] ?? 0;
                    return $baseValue * ($result / 100);
                }
                return (float) $result;
            }
        }

        return (float) $this->default_amount;
    }

    /**
     * Evaluate custom expression.
     * IMPORTANT: Only use with trusted expressions!
     */
    protected function evaluateCustomExpression(array $context): float
    {
        if (empty($this->formula_expression)) {
            return (float) $this->default_amount;
        }

        // Simple expression evaluator for basic math operations
        // Supports: +, -, *, /, parentheses, and variable substitution
        $expression = $this->formula_expression;

        // Replace variables with values
        foreach ($context as $key => $value) {
            $expression = str_replace('{' . $key . '}', (string) $value, $expression);
        }

        // Only allow safe characters in expression
        if (!preg_match('/^[\d\s\+\-\*\/\(\)\.\,]+$/', $expression)) {
            return (float) $this->default_amount;
        }

        try {
            // Simple eval for mathematical expressions only
            // This is safe because we've filtered to only allow math characters
            $result = eval('return ' . $expression . ';');
            return is_numeric($result) ? (float) $result : (float) $this->default_amount;
        } catch (\Throwable $e) {
            return (float) $this->default_amount;
        }
    }

    /**
     * Get available formula types.
     */
    public static function getFormulaTypes(): array
    {
        return [
            self::FORMULA_FIXED => 'Nilai Tetap',
            self::FORMULA_PERCENTAGE => 'Persentase',
            self::FORMULA_CONDITIONAL => 'Kondisional',
            self::FORMULA_CUSTOM => 'Kustom',
        ];
    }

    /**
     * Get available item types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_EARNING => 'Pendapatan',
            self::TYPE_DEDUCTION => 'Potongan',
            self::TYPE_BONUS => 'Bonus',
        ];
    }

    /**
     * Get available base fields.
     */
    public static function getBaseFields(): array
    {
        return self::BASE_FIELDS;
    }

    /**
     * Convert formula to a displayable string.
     */
    public function getFormulaDisplayAttribute(): string
    {
        return match ($this->formula_type) {
            self::FORMULA_FIXED => 'Rp ' . number_format($this->default_amount, 0, ',', '.'),
            self::FORMULA_PERCENTAGE => $this->percentage_rate . '% dari ' . (self::BASE_FIELDS[$this->base_field] ?? $this->base_field),
            self::FORMULA_CONDITIONAL => 'Kondisional (' . count($this->conditions ?? []) . ' aturan)',
            self::FORMULA_CUSTOM => 'Kustom: ' . ($this->formula_expression ?? '-'),
            default => '-',
        };
    }

    /**
     * Get type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    /**
     * Get formula type label.
     */
    public function getFormulaTypeLabelAttribute(): string
    {
        return self::getFormulaTypes()[$this->formula_type] ?? $this->formula_type;
    }
}
