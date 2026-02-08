<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Product\Models\Product;
use Modules\Product\Models\Category;
use Carbon\Carbon;

/**
 * SA-M003: Discount Rule Model
 *
 * Represents an automatic discount rule that can be applied to orders.
 */
class DiscountRule extends Model
{
    use HasFactory;

    protected $table = 'discount_rules';

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED_AMOUNT = 'fixed_amount';
    public const TYPE_BUY_X_GET_Y = 'buy_x_get_y';

    public const APPLIES_TO_ORDER = 'order';
    public const APPLIES_TO_PRODUCT = 'product';
    public const APPLIES_TO_CATEGORY = 'category';
    public const APPLIES_TO_CUSTOMER = 'customer';

    protected $fillable = [
        'name',
        'code',
        'description',
        'discount_type',
        'discount_value',
        'buy_quantity',
        'get_quantity',
        'applies_to',
        'min_order_amount',
        'min_quantity',
        'max_discount_amount',
        'product_ids',
        'category_ids',
        'customer_ids',
        'customer_classifications',
        'start_date',
        'end_date',
        'usage_limit',
        'usage_per_customer',
        'current_usage',
        'priority',
        'is_combinable',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'min_order_amount' => 'float',
        'max_discount_amount' => 'float',
        'buy_quantity' => 'integer',
        'get_quantity' => 'integer',
        'min_quantity' => 'integer',
        'usage_limit' => 'integer',
        'usage_per_customer' => 'integer',
        'current_usage' => 'integer',
        'priority' => 'integer',
        'product_ids' => 'array',
        'category_ids' => 'array',
        'customer_ids' => 'array',
        'customer_classifications' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_combinable' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'discount_type' => self::TYPE_PERCENTAGE,
        'applies_to' => self::APPLIES_TO_ORDER,
        'current_usage' => 0,
        'priority' => 0,
        'is_combinable' => true,
        'is_active' => true,
    ];

    // Calculated attributes
    protected $appends = ['is_valid', 'is_expired', 'usage_remaining'];

    public function getIsValidAttribute(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now()->startOfDay();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        if ($this->usage_limit && $this->current_usage >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->end_date) {
            return false;
        }
        return now()->startOfDay()->gt($this->end_date);
    }

    public function getUsageRemainingAttribute(): ?int
    {
        if (!$this->usage_limit) {
            return null;
        }
        return max(0, $this->usage_limit - $this->current_usage);
    }

    // Business logic
    public function incrementUsage(): void
    {
        $this->increment('current_usage');
    }

    public function canBeUsedBy(?int $customerId): bool
    {
        if (!$this->is_valid) {
            return false;
        }

        // Check customer restrictions
        if (!empty($this->customer_ids) && $customerId) {
            if (!in_array($customerId, $this->customer_ids)) {
                return false;
            }
        }

        return true;
    }

    public function appliesToProduct(Product $product): bool
    {
        if ($this->applies_to === self::APPLIES_TO_ORDER) {
            return true;
        }

        if ($this->applies_to === self::APPLIES_TO_PRODUCT) {
            return empty($this->product_ids) || in_array($product->id, $this->product_ids);
        }

        if ($this->applies_to === self::APPLIES_TO_CATEGORY) {
            return empty($this->category_ids) || in_array($product->category_id, $this->category_ids);
        }

        return false;
    }

    public function calculateDiscount(float $amount, int $quantity = 1): float
    {
        if (!$this->is_valid) {
            return 0;
        }

        // Check minimum requirements
        if ($this->min_order_amount && $amount < $this->min_order_amount) {
            return 0;
        }

        if ($this->min_quantity && $quantity < $this->min_quantity) {
            return 0;
        }

        $discount = match ($this->discount_type) {
            self::TYPE_PERCENTAGE => $amount * ($this->discount_value / 100),
            self::TYPE_FIXED_AMOUNT => $this->discount_value,
            self::TYPE_BUY_X_GET_Y => $this->calculateBuyXGetYDiscount($amount, $quantity),
            default => 0,
        };

        // Apply maximum discount cap
        if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
            $discount = $this->max_discount_amount;
        }

        // Discount cannot exceed the amount
        return min($discount, $amount);
    }

    private function calculateBuyXGetYDiscount(float $amount, int $quantity): float
    {
        if (!$this->buy_quantity || !$this->get_quantity || $quantity <= 0) {
            return 0;
        }

        $setSize = $this->buy_quantity + $this->get_quantity;
        $completeSets = floor($quantity / $setSize);

        if ($completeSets <= 0) {
            return 0;
        }

        $unitPrice = $amount / $quantity;
        return min($completeSets * $this->get_quantity * $unitPrice, $amount);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $now = now()->startOfDay();

        return $query->active()
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $now);
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                  ->orWhereColumn('current_usage', '<', 'usage_limit');
            });
    }

    public function scopeForOrder($query)
    {
        return $query->where('applies_to', self::APPLIES_TO_ORDER);
    }

    public function scopeForProduct($query)
    {
        return $query->whereIn('applies_to', [self::APPLIES_TO_PRODUCT, self::APPLIES_TO_CATEGORY]);
    }

    public function scopeByPriority($query)
    {
        return $query->orderByDesc('priority');
    }

    public function scopeForSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Sales\Database\Factories\DiscountRuleFactory::new();
    }
}
