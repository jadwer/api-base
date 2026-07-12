<?php

namespace App\Services;

use Modules\AppConfig\Services\AppSettingResolver;

/**
 * TaxCalculator
 *
 * Single source of truth for IVA math across Sales, Ecommerce and Billing.
 * Centralizing the calculation lets a tenant flip between two capture modes
 * via the AppSetting `pricing.prices_include_tax` (group `pricing`):
 *
 *   - false (default, B2B like LWM): captured prices are NET. Tax is ADDED
 *     on top. This is the historical, already-correct behavior.
 *   - true (B2C): captured prices are FINAL (tax included). The tax is broken
 *     OUT of the price: net = price / (1 + rate), tax = price - net. The total
 *     paid equals the list price exactly.
 *
 * Rounding is centralized to 2 decimals, HALF_UP, so the sum of line items
 * matches the document total (avoids the classic 1-cent SAT mismatch).
 *
 * The `prices_include_tax` flag is read once per service instance (cached) via
 * AppSettingResolver, which itself caches the AppSetting for 60s. Resolve this
 * service from the container (app(TaxCalculator::class)) so callers within the
 * same request share the cached flag.
 */
class TaxCalculator
{
    private AppSettingResolver $settings;

    /**
     * Per-instance cache of the pricing.prices_include_tax flag.
     * null until first resolved.
     */
    private ?bool $pricesIncludeTax = null;

    public function __construct(AppSettingResolver $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Whether captured prices already include tax, per tenant configuration.
     * Cached per instance so repeated line calculations don't re-hit the resolver.
     */
    public function pricesIncludeTax(): bool
    {
        if ($this->pricesIncludeTax === null) {
            $this->pricesIncludeTax = $this->settings->getBool('pricing.prices_include_tax', false);
        }

        return $this->pricesIncludeTax;
    }

    /**
     * Split a line amount into net, tax and total according to the tenant mode.
     *
     * @param float     $price            The captured amount for the line (already qty * unit,
     *                                     discounts applied by the caller before this call).
     * @param float     $rate             Tax rate as a percentage (e.g. 16 for 16%). 0 or exempt => no tax.
     * @param bool|null $pricesIncludeTax Explicit override; when null, reads the tenant flag.
     *
     * @return array{net: float, tax: float, total: float}
     */
    public function netAndTax(float $price, float $rate, ?bool $pricesIncludeTax = null): array
    {
        $included = $pricesIncludeTax ?? $this->pricesIncludeTax();

        // Exempt / zero-rated: net equals total, no tax, in either mode.
        if ($rate <= 0) {
            $net = $this->round($price);

            return ['net' => $net, 'tax' => 0.0, 'total' => $net];
        }

        $fraction = $rate / 100;

        if ($included) {
            // Price is final: break the tax out from inside.
            $net = $this->round($price / (1 + $fraction));
            $tax = $this->round($price - $net);
            $total = $this->round($price);

            return ['net' => $net, 'tax' => $tax, 'total' => $total];
        }

        // Price is net: add the tax on top (historical default behavior).
        $net = $this->round($price);
        $tax = $this->round($price * $fraction);
        $total = $this->round($net + $tax);

        return ['net' => $net, 'tax' => $tax, 'total' => $total];
    }

    /**
     * Round to 2 decimals, HALF_UP.
     */
    private function round(float $value): float
    {
        return round($value, 2, PHP_ROUND_HALF_UP);
    }
}
