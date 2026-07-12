<?php

namespace Modules\AppConfig\Tests\Feature;

use App\Services\TaxCalculator;
use Modules\AppConfig\Models\AppSetting;
use Modules\AppConfig\Services\AppSettingResolver;
use Tests\TestCase;

/**
 * Money math for the configurable-IVA feature (etapa 2).
 *
 * Covers the six scenarios in DESIGN_IVA_CONFIGURABLE.md, in both capture modes
 * (prices_include_tax false/true), plus the tenant flag being read from the
 * AppSetting via AppSettingResolver.
 */
class TaxCalculatorTest extends TestCase
{
    private function calc(): TaxCalculator
    {
        return new TaxCalculator(new AppSettingResolver());
    }

    /** Mode false (B2B default): price is net, IVA added on top. */
    public function test_prices_exclude_tax_adds_iva_on_top(): void
    {
        $result = $this->calc()->netAndTax(100.0, 16, false);

        $this->assertEqualsWithDelta(100.0, $result['net'], 0.001);
        $this->assertEqualsWithDelta(16.0, $result['tax'], 0.001);
        $this->assertEqualsWithDelta(116.0, $result['total'], 0.001);
    }

    /** Mode true (B2C): price 116 is final, IVA broken out exactly. */
    public function test_prices_include_tax_breaks_iva_out_exactly(): void
    {
        $result = $this->calc()->netAndTax(116.0, 16, true);

        $this->assertEqualsWithDelta(100.0, $result['net'], 0.001);
        $this->assertEqualsWithDelta(16.0, $result['tax'], 0.001);
        $this->assertEqualsWithDelta(116.0, $result['total'], 0.001);
    }

    /** Mode true with rounding: price 100 -> net 86.21, iva 13.79, total 100. */
    public function test_prices_include_tax_rounds_half_up(): void
    {
        $result = $this->calc()->netAndTax(100.0, 16, true);

        $this->assertEqualsWithDelta(86.21, $result['net'], 0.001);
        $this->assertEqualsWithDelta(13.79, $result['tax'], 0.001);
        $this->assertEqualsWithDelta(100.0, $result['total'], 0.001);
        // Net + tax must reconstruct the total (no lost cent).
        $this->assertEqualsWithDelta(
            $result['total'],
            $result['net'] + $result['tax'],
            0.001
        );
    }

    /** Sum of 3 lines reconciles with the sum of totals, both modes, no cent drift. */
    public function test_three_lines_reconcile_in_both_modes(): void
    {
        $calc = $this->calc();
        $prices = [100.0, 100.0, 100.0];

        foreach ([false, true] as $included) {
            $netSum = 0.0;
            $taxSum = 0.0;
            $totalSum = 0.0;

            foreach ($prices as $price) {
                $line = $calc->netAndTax($price, 16, $included);
                $netSum += $line['net'];
                $taxSum += $line['tax'];
                $totalSum += $line['total'];
            }

            // Each line's net + tax equals its total, so the sums must agree too.
            $this->assertEqualsWithDelta(
                $totalSum,
                round($netSum + $taxSum, 2),
                0.001,
                "Cent drift in mode " . ($included ? 'included' : 'excluded')
            );

            if ($included) {
                // 3 * 100 captured = 300 paid exactly.
                $this->assertEqualsWithDelta(300.0, $totalSum, 0.001);
            } else {
                // 3 * (100 + 16) = 348.
                $this->assertEqualsWithDelta(348.0, $totalSum, 0.001);
            }
        }
    }

    /** Exempt product (rate 0): net = total, tax = 0, in both modes. */
    public function test_exempt_rate_zero_has_no_tax_in_both_modes(): void
    {
        foreach ([false, true] as $included) {
            $result = $this->calc()->netAndTax(100.0, 0, $included);

            $this->assertEqualsWithDelta(100.0, $result['net'], 0.001);
            $this->assertEqualsWithDelta(0.0, $result['tax'], 0.001);
            $this->assertEqualsWithDelta(100.0, $result['total'], 0.001);
        }
    }

    /** Flag is read from the AppSetting via the resolver when no override is passed. */
    public function test_flag_is_read_from_app_setting(): void
    {
        AppSetting::firstOrCreate(
            ['key' => 'pricing.prices_include_tax'],
            ['value' => 'false', 'type' => 'boolean', 'group' => 'pricing', 'label' => 'Precios con IVA incluido']
        );

        // Default false: adds on top.
        $calc = $this->calc();
        $this->assertFalse($calc->pricesIncludeTax());
        $result = $calc->netAndTax(100.0, 16);
        $this->assertEqualsWithDelta(116.0, $result['total'], 0.001);

        // Flip to true and use a fresh instance (flag cached per instance).
        AppSetting::set('pricing.prices_include_tax', true);
        $calc2 = $this->calc();
        $this->assertTrue($calc2->pricesIncludeTax());
        $result2 = $calc2->netAndTax(100.0, 16);
        $this->assertEqualsWithDelta(100.0, $result2['total'], 0.001);
        $this->assertEqualsWithDelta(86.21, $result2['net'], 0.001);
    }
}
