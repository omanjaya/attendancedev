<?php

namespace Tests\Unit\Config;

use Tests\TestCase;
use Illuminate\Support\Facades\Config;

class PayrollConfigTest extends TestCase
{
    public function test_payroll_config_file_exists(): void
    {
        $configPath = config_path('payroll.php');
        $this->assertFileExists($configPath);
    }

    public function test_payroll_config_has_required_sections(): void
    {
        $config = Config::get('payroll');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('calculations', $config);
        $this->assertArrayHasKey('tax', $config);
        $this->assertArrayHasKey('statutory_deductions', $config);
        $this->assertArrayHasKey('bonuses', $config);
        $this->assertArrayHasKey('validation', $config);
        $this->assertArrayHasKey('features', $config);
    }

    public function test_calculations_config_has_required_values(): void
    {
        $calculations = Config::get('payroll.calculations');

        $this->assertArrayHasKey('standard_hours_per_day', $calculations);
        $this->assertArrayHasKey('working_days_per_month', $calculations);
        $this->assertArrayHasKey('overtime_multiplier', $calculations);
        $this->assertArrayHasKey('pay_date_day', $calculations);

        // Test default values
        $this->assertEquals(8, $calculations['standard_hours_per_day']);
        $this->assertEquals(22, $calculations['working_days_per_month']);
        $this->assertEquals(1.5, $calculations['overtime_multiplier']);
        $this->assertEquals(15, $calculations['pay_date_day']);
    }

    public function test_tax_brackets_config_structure(): void
    {
        $taxBrackets = Config::get('payroll.tax.brackets');

        $this->assertIsArray($taxBrackets);
        $this->assertNotEmpty($taxBrackets);

        foreach ($taxBrackets as $bracket) {
            $this->assertArrayHasKey('min', $bracket);
            $this->assertArrayHasKey('max', $bracket);
            $this->assertArrayHasKey('rate', $bracket);
            
            $this->assertIsNumeric($bracket['min']);
            $this->assertTrue($bracket['max'] === null || is_numeric($bracket['max']));
            $this->assertIsNumeric($bracket['rate']);
        }
    }

    public function test_statutory_deductions_config_structure(): void
    {
        $deductions = Config::get('payroll.statutory_deductions');

        $this->assertArrayHasKey('social_security', $deductions);
        $this->assertArrayHasKey('medicare', $deductions);

        // Test social security config
        $socialSecurity = $deductions['social_security'];
        $this->assertArrayHasKey('rate', $socialSecurity);
        $this->assertArrayHasKey('cap', $socialSecurity);
        $this->assertArrayHasKey('enabled', $socialSecurity);
        $this->assertIsNumeric($socialSecurity['rate']);
        $this->assertIsBool($socialSecurity['enabled']);

        // Test medicare config
        $medicare = $deductions['medicare'];
        $this->assertArrayHasKey('rate', $medicare);
        $this->assertArrayHasKey('enabled', $medicare);
        $this->assertIsNumeric($medicare['rate']);
        $this->assertIsBool($medicare['enabled']);
    }

    public function test_bonuses_config_structure(): void
    {
        $bonuses = Config::get('payroll.bonuses');

        $this->assertArrayHasKey('perfect_attendance', $bonuses);
        $this->assertArrayHasKey('performance', $bonuses);

        // Test perfect attendance bonus
        $perfectAttendance = $bonuses['perfect_attendance'];
        $this->assertArrayHasKey('enabled', $perfectAttendance);
        $this->assertArrayHasKey('amount', $perfectAttendance);
        $this->assertIsBool($perfectAttendance['enabled']);
        $this->assertIsNumeric($perfectAttendance['amount']);
    }

    public function test_validation_config_structure(): void
    {
        $validation = Config::get('payroll.validation');

        $this->assertArrayHasKey('minimum_wage', $validation);
        $this->assertArrayHasKey('maximum_hours_per_period', $validation);
        $this->assertArrayHasKey('maximum_overtime_hours', $validation);

        $this->assertIsNumeric($validation['minimum_wage']);
        $this->assertIsNumeric($validation['maximum_hours_per_period']);
        $this->assertIsNumeric($validation['maximum_overtime_hours']);
    }

    public function test_features_config_structure(): void
    {
        $features = Config::get('payroll.features');

        $this->assertArrayHasKey('auto_calculate_overtime', $features);
        $this->assertArrayHasKey('auto_apply_bonuses', $features);
        $this->assertArrayHasKey('auto_calculate_taxes', $features);

        $this->assertIsBool($features['auto_calculate_overtime']);
        $this->assertIsBool($features['auto_apply_bonuses']);
        $this->assertIsBool($features['auto_calculate_taxes']);
    }

    public function test_config_can_be_overridden_by_environment(): void
    {
        // Test that config values can be overridden by environment variables
        config(['payroll.calculations.standard_hours_per_day' => 7]);
        
        $this->assertEquals(7, Config::get('payroll.calculations.standard_hours_per_day'));
    }

    public function test_payroll_config_values_are_reasonable(): void
    {
        $standardHours = Config::get('payroll.calculations.standard_hours_per_day');
        $workingDays = Config::get('payroll.calculations.working_days_per_month');
        $overtimeMultiplier = Config::get('payroll.calculations.overtime_multiplier');
        $payDateDay = Config::get('payroll.calculations.pay_date_day');

        // Reasonable ranges
        $this->assertGreaterThan(0, $standardHours);
        $this->assertLessThanOrEqual(24, $standardHours);

        $this->assertGreaterThan(0, $workingDays);
        $this->assertLessThanOrEqual(31, $workingDays);

        $this->assertGreaterThanOrEqual(1, $overtimeMultiplier);
        $this->assertLessThanOrEqual(5, $overtimeMultiplier);

        $this->assertGreaterThanOrEqual(1, $payDateDay);
        $this->assertLessThanOrEqual(31, $payDateDay);
    }

    public function test_tax_brackets_are_properly_ordered(): void
    {
        $taxBrackets = Config::get('payroll.tax.brackets');

        $previousMax = -1;
        foreach ($taxBrackets as $bracket) {
            $this->assertGreaterThan($previousMax, $bracket['min']);
            
            if ($bracket['max'] !== null) {
                $this->assertGreaterThanOrEqual($bracket['min'], $bracket['max']);
                $previousMax = $bracket['max'];
            }
        }
    }

    public function test_statutory_deduction_rates_are_percentages(): void
    {
        $socialSecurityRate = Config::get('payroll.statutory_deductions.social_security.rate');
        $medicareRate = Config::get('payroll.statutory_deductions.medicare.rate');

        // Rates should be reasonable percentages (0-100)
        $this->assertGreaterThanOrEqual(0, $socialSecurityRate);
        $this->assertLessThanOrEqual(100, $socialSecurityRate);

        $this->assertGreaterThanOrEqual(0, $medicareRate);
        $this->assertLessThanOrEqual(100, $medicareRate);
    }

    public function test_config_supports_disabled_features(): void
    {
        // Test that features can be disabled
        Config::set('payroll.bonuses.perfect_attendance.enabled', false);
        Config::set('payroll.statutory_deductions.social_security.enabled', false);

        $this->assertFalse(Config::get('payroll.bonuses.perfect_attendance.enabled'));
        $this->assertFalse(Config::get('payroll.statutory_deductions.social_security.enabled'));
    }

    public function test_rounding_config_structure(): void
    {
        $rounding = Config::get('payroll.rounding');

        $this->assertArrayHasKey('currency_precision', $rounding);
        $this->assertArrayHasKey('hours_precision', $rounding);
        $this->assertArrayHasKey('rate_precision', $rounding);

        $this->assertIsInt($rounding['currency_precision']);
        $this->assertIsInt($rounding['hours_precision']);
        $this->assertIsInt($rounding['rate_precision']);

        // Precision should be reasonable
        $this->assertGreaterThanOrEqual(0, $rounding['currency_precision']);
        $this->assertLessThanOrEqual(10, $rounding['currency_precision']);
    }

    public function test_formatting_config_structure(): void
    {
        $formatting = Config::get('payroll.formatting');

        $this->assertArrayHasKey('currency_symbol', $formatting);
        $this->assertArrayHasKey('currency_position', $formatting);
        $this->assertArrayHasKey('thousand_separator', $formatting);
        $this->assertArrayHasKey('decimal_separator', $formatting);

        $this->assertIsString($formatting['currency_symbol']);
        $this->assertContains($formatting['currency_position'], ['before', 'after']);
    }

    public function test_periods_config_structure(): void
    {
        $periods = Config::get('payroll.periods');

        $this->assertArrayHasKey('default_type', $periods);
        $this->assertArrayHasKey('monthly', $periods);
        $this->assertArrayHasKey('weekly', $periods);
        $this->assertArrayHasKey('biweekly', $periods);

        $this->assertContains($periods['default_type'], ['weekly', 'biweekly', 'monthly']);
    }

    public function test_security_config_structure(): void
    {
        $security = Config::get('payroll.security');

        $this->assertArrayHasKey('encrypt_payroll_data', $security);
        $this->assertArrayHasKey('audit_all_changes', $security);
        $this->assertArrayHasKey('require_approval_for_changes', $security);

        $this->assertIsBool($security['encrypt_payroll_data']);
        $this->assertIsBool($security['audit_all_changes']);
        $this->assertIsBool($security['require_approval_for_changes']);
    }
}