<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Ecommerce\Database\factories\CurrencyFactory;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'exchange_rate' => 'float',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory()
    {
        return CurrencyFactory::new();
    }

    /**
     * Scope for active currencies only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for default currency
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the default currency
     *
     * @return Currency|null
     */
    public static function getDefault(): ?Currency
    {
        return static::default()->first();
    }

    /**
     * Convert amount from this currency to another currency
     *
     * @param float $amount
     * @param Currency $toCurrency
     * @return float
     */
    public function convertTo(float $amount, Currency $toCurrency): float
    {
        if ($this->exchange_rate == 0) {
            throw new \DivisionByZeroError('Exchange rate cannot be zero for currency: ' . $this->code);
        }

        // Convert to base currency first, then to target currency
        $baseAmount = $amount / $this->exchange_rate;
        return $baseAmount * $toCurrency->exchange_rate;
    }

    /**
     * Convert amount from this currency to base currency
     *
     * @param float $amount
     * @return float
     */
    public function toBaseCurrency(float $amount): float
    {
        if ($this->exchange_rate == 0) {
            throw new \DivisionByZeroError('Exchange rate cannot be zero for currency: ' . $this->code);
        }

        return $amount / $this->exchange_rate;
    }

    /**
     * Convert amount from base currency to this currency
     *
     * @param float $amount
     * @return float
     */
    public function fromBaseCurrency(float $amount): float
    {
        return $amount * $this->exchange_rate;
    }

    /**
     * Format amount with currency symbol
     *
     * @param float $amount
     * @return string
     */
    public function format(float $amount): string
    {
        return $this->symbol . ' ' . number_format($amount, 2);
    }
}
