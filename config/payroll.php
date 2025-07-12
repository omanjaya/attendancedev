<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payroll Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains payroll calculation configuration options for the
    | attendance management system.
    |
    */

    'calculations' => [
        /*
        |--------------------------------------------------------------------------
        | Basic Calculation Settings
        |--------------------------------------------------------------------------
        |
        | Configure fundamental payroll calculation parameters.
        |
        */
        'standard_hours_per_day' => env('PAYROLL_STANDARD_HOURS_PER_DAY', 8),
        'working_days_per_month' => env('PAYROLL_WORKING_DAYS_PER_MONTH', 22),
        'overtime_multiplier' => env('PAYROLL_OVERTIME_MULTIPLIER', 1.5),
        'pay_date_day' => env('PAYROLL_PAY_DATE_DAY', 15), // Day of month to pay
    ],

    'tax' => [
        /*
        |--------------------------------------------------------------------------
        | Tax Configuration
        |--------------------------------------------------------------------------
        |
        | Configure tax brackets and rates for payroll calculations.
        |
        */
        'brackets' => [
            [
                'min' => 0,
                'max' => env('PAYROLL_TAX_BRACKET_1_MAX', 1000),
                'rate' => env('PAYROLL_TAX_BRACKET_1_RATE', 0), // 0% tax
            ],
            [
                'min' => env('PAYROLL_TAX_BRACKET_1_MAX', 1000) + 1,
                'max' => env('PAYROLL_TAX_BRACKET_2_MAX', 3000),
                'rate' => env('PAYROLL_TAX_BRACKET_2_RATE', 10), // 10% tax
            ],
            [
                'min' => env('PAYROLL_TAX_BRACKET_2_MAX', 3000) + 1,
                'max' => env('PAYROLL_TAX_BRACKET_3_MAX', 5000),
                'rate' => env('PAYROLL_TAX_BRACKET_3_RATE', 15), // 15% tax
            ],
            [
                'min' => env('PAYROLL_TAX_BRACKET_3_MAX', 5000) + 1,
                'max' => null, // No upper limit
                'rate' => env('PAYROLL_TAX_BRACKET_4_RATE', 20), // 20% tax
            ],
        ],
    ],

    'statutory_deductions' => [
        /*
        |--------------------------------------------------------------------------
        | Statutory Deductions Configuration
        |--------------------------------------------------------------------------
        |
        | Configure mandatory deductions like social security, medicare, etc.
        |
        */
        'social_security' => [
            'rate' => env('PAYROLL_SOCIAL_SECURITY_RATE', 6.2), // Percentage
            'cap' => env('PAYROLL_SOCIAL_SECURITY_CAP', 10000), // Monthly cap
            'enabled' => env('PAYROLL_SOCIAL_SECURITY_ENABLED', true),
        ],
        'medicare' => [
            'rate' => env('PAYROLL_MEDICARE_RATE', 1.45), // Percentage
            'cap' => env('PAYROLL_MEDICARE_CAP', null), // No cap
            'enabled' => env('PAYROLL_MEDICARE_ENABLED', true),
        ],
        'unemployment' => [
            'rate' => env('PAYROLL_UNEMPLOYMENT_RATE', 0.6), // Percentage
            'cap' => env('PAYROLL_UNEMPLOYMENT_CAP', 7000), // Annual cap
            'enabled' => env('PAYROLL_UNEMPLOYMENT_ENABLED', false),
        ],
    ],

    'bonuses' => [
        /*
        |--------------------------------------------------------------------------
        | Bonus Configuration
        |--------------------------------------------------------------------------
        |
        | Configure automatic bonus calculations and thresholds.
        |
        */
        'perfect_attendance' => [
            'enabled' => env('PAYROLL_PERFECT_ATTENDANCE_BONUS_ENABLED', true),
            'amount' => env('PAYROLL_PERFECT_ATTENDANCE_BONUS_AMOUNT', 100),
            'minimum_days_required' => env('PAYROLL_PERFECT_ATTENDANCE_MIN_DAYS', null), // null = all working days
        ],
        'performance' => [
            'enabled' => env('PAYROLL_PERFORMANCE_BONUS_ENABLED', false),
            'base_amount' => env('PAYROLL_PERFORMANCE_BONUS_BASE', 50),
            'max_amount' => env('PAYROLL_PERFORMANCE_BONUS_MAX', 500),
        ],
        'overtime_bonus' => [
            'enabled' => env('PAYROLL_OVERTIME_BONUS_ENABLED', false),
            'threshold_hours' => env('PAYROLL_OVERTIME_BONUS_THRESHOLD', 20),
            'bonus_per_hour' => env('PAYROLL_OVERTIME_BONUS_PER_HOUR', 5),
        ],
    ],

    'leave' => [
        /*
        |--------------------------------------------------------------------------
        | Leave Configuration
        |--------------------------------------------------------------------------
        |
        | Configure leave-related payroll calculations.
        |
        */
        'paid_leave_calculation' => [
            'method' => env('PAYROLL_PAID_LEAVE_METHOD', 'daily_rate'), // daily_rate, average_earnings
            'include_overtime_in_average' => env('PAYROLL_PAID_LEAVE_INCLUDE_OVERTIME', false),
            'average_period_days' => env('PAYROLL_PAID_LEAVE_AVERAGE_PERIOD', 90),
        ],
        'unpaid_leave_deduction' => [
            'method' => env('PAYROLL_UNPAID_LEAVE_METHOD', 'daily_rate'), // daily_rate, proportional
            'round_to_nearest_day' => env('PAYROLL_UNPAID_LEAVE_ROUND_DAYS', true),
        ],
    ],

    'rounding' => [
        /*
        |--------------------------------------------------------------------------
        | Rounding Configuration
        |--------------------------------------------------------------------------
        |
        | Configure how payroll amounts should be rounded.
        |
        */
        'currency_precision' => env('PAYROLL_CURRENCY_PRECISION', 2),
        'hours_precision' => env('PAYROLL_HOURS_PRECISION', 2),
        'rate_precision' => env('PAYROLL_RATE_PRECISION', 4),
        'tax_rounding_method' => env('PAYROLL_TAX_ROUNDING_METHOD', 'round'), // round, floor, ceil
    ],

    'validation' => [
        /*
        |--------------------------------------------------------------------------
        | Validation Rules
        |--------------------------------------------------------------------------
        |
        | Configure validation rules for payroll calculations.
        |
        */
        'minimum_wage' => env('PAYROLL_MINIMUM_WAGE', 7.25), // Per hour
        'maximum_hours_per_period' => env('PAYROLL_MAX_HOURS_PER_PERIOD', 200),
        'maximum_overtime_hours' => env('PAYROLL_MAX_OVERTIME_HOURS', 60),
        'validate_against_schedule' => env('PAYROLL_VALIDATE_AGAINST_SCHEDULE', true),
    ],

    'formatting' => [
        /*
        |--------------------------------------------------------------------------
        | Display Formatting
        |--------------------------------------------------------------------------
        |
        | Configure how payroll amounts are displayed.
        |
        */
        'currency_symbol' => env('PAYROLL_CURRENCY_SYMBOL', '$'),
        'currency_position' => env('PAYROLL_CURRENCY_POSITION', 'before'), // before, after
        'thousand_separator' => env('PAYROLL_THOUSAND_SEPARATOR', ','),
        'decimal_separator' => env('PAYROLL_DECIMAL_SEPARATOR', '.'),
        'negative_format' => env('PAYROLL_NEGATIVE_FORMAT', '($amount)'), // ($amount), -$amount
    ],

    'periods' => [
        /*
        |--------------------------------------------------------------------------
        | Pay Period Configuration
        |--------------------------------------------------------------------------
        |
        | Configure pay period types and schedules.
        |
        */
        'default_type' => env('PAYROLL_DEFAULT_PERIOD_TYPE', 'monthly'), // weekly, biweekly, monthly
        'monthly' => [
            'cutoff_day' => env('PAYROLL_MONTHLY_CUTOFF_DAY', 'last'), // last, 15, 30, etc.
            'pay_delay_days' => env('PAYROLL_MONTHLY_PAY_DELAY_DAYS', 15),
        ],
        'weekly' => [
            'week_start_day' => env('PAYROLL_WEEKLY_START_DAY', 'monday'), // monday, sunday
            'pay_delay_days' => env('PAYROLL_WEEKLY_PAY_DELAY_DAYS', 7),
        ],
        'biweekly' => [
            'reference_date' => env('PAYROLL_BIWEEKLY_REFERENCE_DATE', '2024-01-01'), // First Monday of reference period
            'pay_delay_days' => env('PAYROLL_BIWEEKLY_PAY_DELAY_DAYS', 10),
        ],
    ],

    'features' => [
        /*
        |--------------------------------------------------------------------------
        | Feature Toggles
        |--------------------------------------------------------------------------
        |
        | Enable or disable specific payroll features.
        |
        */
        'auto_calculate_overtime' => env('PAYROLL_AUTO_CALCULATE_OVERTIME', true),
        'auto_apply_bonuses' => env('PAYROLL_AUTO_APPLY_BONUSES', true),
        'auto_calculate_taxes' => env('PAYROLL_AUTO_CALCULATE_TAXES', true),
        'auto_apply_statutory_deductions' => env('PAYROLL_AUTO_APPLY_STATUTORY_DEDUCTIONS', true),
        'prorate_partial_periods' => env('PAYROLL_PRORATE_PARTIAL_PERIODS', true),
        'weekend_overtime_premium' => env('PAYROLL_WEEKEND_OVERTIME_PREMIUM', false),
        'holiday_overtime_premium' => env('PAYROLL_HOLIDAY_OVERTIME_PREMIUM', false),
    ],

    'reporting' => [
        /*
        |--------------------------------------------------------------------------
        | Reporting Configuration
        |--------------------------------------------------------------------------
        |
        | Configure payroll reporting options.
        |
        */
        'default_export_format' => env('PAYROLL_DEFAULT_EXPORT_FORMAT', 'pdf'), // pdf, excel, csv
        'include_sensitive_data' => env('PAYROLL_INCLUDE_SENSITIVE_DATA', false),
        'watermark_reports' => env('PAYROLL_WATERMARK_REPORTS', true),
        'digital_signature' => env('PAYROLL_DIGITAL_SIGNATURE', false),
    ],

    'security' => [
        /*
        |--------------------------------------------------------------------------
        | Security Configuration
        |--------------------------------------------------------------------------
        |
        | Configure security options for payroll data.
        |
        */
        'encrypt_payroll_data' => env('PAYROLL_ENCRYPT_DATA', true),
        'audit_all_changes' => env('PAYROLL_AUDIT_ALL_CHANGES', true),
        'require_approval_for_changes' => env('PAYROLL_REQUIRE_APPROVAL_FOR_CHANGES', true),
        'max_adjustment_percentage' => env('PAYROLL_MAX_ADJUSTMENT_PERCENTAGE', 10), // Max % adjustment without approval
        'retain_history_months' => env('PAYROLL_RETAIN_HISTORY_MONTHS', 84), // 7 years
    ],
];