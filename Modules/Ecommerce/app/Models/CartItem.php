<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\Database\Factories\CartItemFactory;

class CartItem extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return CartItemFactory::new();
    }

    protected $table = "cart_items";

    protected $fillable = [
        'shopping_cart_id', 'product_id', 'quantity', 'unit_price', 'original_price', 'discount_percent', 'discount_amount', 'subtotal', 'tax_rate', 'tax_amount', 'total'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    public function shoppingCart()
    {
        return $this->belongsTo(ShoppingCart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}