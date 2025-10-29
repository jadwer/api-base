<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $table = 'shipping_methods';

    protected $fillable = [
        'name',
        'code',
        'carrier',
        'base_cost',
        'cost_per_kg',
        'estimated_days_min',
        'estimated_days_max',
        'is_active',
        'available_countries',
        'description',
        'metadata',
    ];

    protected $casts = [
        'base_cost' => 'float',
        'cost_per_kg' => 'float',
        'estimated_days_min' => 'integer',
        'estimated_days_max' => 'integer',
        'is_active' => 'boolean',
        'available_countries' => 'array',
        'metadata' => 'array',
    ];

    // Calculated Attributes
    protected $appends = [
        'estimated_delivery',
    ];

    public function getEstimatedDeliveryAttribute(): string
    {
        return "{$this->estimated_days_min}-{$this->estimated_days_max} días";
    }

    // Business Logic Methods
    public function calculateShippingCost(float $weight): float
    {
        $cost = $this->base_cost;

        if ($this->cost_per_kg && $weight > 0) {
            $cost += ($weight * $this->cost_per_kg);
        }

        return round($cost, 2);
    }

    public function isAvailableInCountry(string $countryCode): bool
    {
        if (empty($this->available_countries)) {
            return true; // Available globally if not specified
        }

        return in_array($countryCode, $this->available_countries);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCarrier($query, string $carrier)
    {
        return $query->where('carrier', $carrier);
    }

    // Relationships
    public function checkoutSessions()
    {
        return $this->hasMany(CheckoutSession::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Ecommerce\Database\Factories\ShippingMethodFactory::new();
    }
}
