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
        'shopping_cart_id', 'product_id', 'quantity', 'unit_price', 'original_price', 'discount_percent', 'discount_amount', 'subtotal', 'tax_rate', 'tax_amount', 'total', 'metadata', 'status'
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'original_price' => 'float',
        'discount_percent' => 'float',
        'discount_amount' => 'float',
        'subtotal' => 'float',
        'tax_rate' => 'float',
        'tax_amount' => 'float',
        'total' => 'float',
        'metadata' => 'array'
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
