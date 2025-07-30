<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\Database\Factories\ShoppingCartFactory;

class ShoppingCart extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return ShoppingCartFactory::new();
    }

    protected $table = "shopping_carts";

    protected $fillable = [
        'session_id', 'user_id', 'status', 'expires_at', 'total_amount', 'currency', 'coupon_code', 'discount_amount', 'tax_amount', 'shipping_amount', 'notes'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2'
    ];

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}