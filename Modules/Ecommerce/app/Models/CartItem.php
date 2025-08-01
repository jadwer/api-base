<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Modules\Product\Models\Product;

class CartItem extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'cart_items';
    
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

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function shoppingCart()
    {
        return $this->belongsTo(ShoppingCart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Ecommerce\Database\Factories\CartItemFactory::new();
    }
}
