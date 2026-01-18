<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;


class Coupon extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'coupons';
    
    protected $fillable = [
        'code', 'name', 'description', 'type', 'value', 'min_amount', 'max_amount', 'max_uses', 'used_count', 'starts_at', 'expires_at', 'is_active', 'customer_ids', 'product_ids', 'category_ids'
    ];

    protected $casts = [
        'value' => 'float',
        'min_amount' => 'float',
        'max_amount' => 'float',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'customer_ids' => 'array',
        'product_ids' => 'array',
        'category_ids' => 'array'
    ];

    protected $appends = [
        'isValid',
        'remainingUses',
        'isExpired'
    ];

    // Campos Calculados
    public function getIsValidAttribute(): bool
    {
        return $this->is_active && 
               !$this->getIsExpiredAttribute() && 
               ($this->max_uses === null || $this->used_count < $this->max_uses);
    }

    public function getRemainingUsesAttribute(): ?int
    {
        return $this->max_uses ? $this->max_uses - $this->used_count : null;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    // Business Logic Methods
    public function canBeUsed(float $cartAmount = 0): bool
    {
        if (!$this->getIsValidAttribute()) {
            return false;
        }

        if ($this->min_amount && $cartAmount < $this->min_amount) {
            return false;
        }

        if ($this->max_amount && $cartAmount > $this->max_amount) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $cartAmount): float
    {
        if (!$this->canBeUsed($cartAmount)) {
            return 0.00;
        }

        return match ($this->type) {
            'percentage' => $cartAmount * ($this->value / 100),
            'fixed_amount' => min($this->value, $cartAmount),
            'free_shipping' => 0.00, // Shipping discount handled separately
            default => 0.00,
        };
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('max_uses')
                          ->orWhereRaw('used_count < max_uses');
                    });
    }



    // Factory
    protected static function newFactory()
    {
        return \Modules\Ecommerce\Database\Factories\CouponFactory::new();
    }
}
