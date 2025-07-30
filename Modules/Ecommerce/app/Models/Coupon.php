<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\Database\Factories\CouponFactory;

class Coupon extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return CouponFactory::new();
    }

    protected $table = "coupons";

    protected $fillable = [
        'code', 'name', 'description', 'type', 'value', 'min_amount', 'max_amount', 'max_uses', 'used_count', 'starts_at', 'expires_at', 'is_active', 'customer_ids', 'product_ids', 'category_ids'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'customer_ids' => 'array',
        'product_ids' => 'array',
        'category_ids' => 'array'
    ];


}