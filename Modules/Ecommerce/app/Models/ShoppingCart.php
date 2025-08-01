<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Modules\User\Models\User;

class ShoppingCart extends Model
{
    use HasFactory, HasPermissions;

    protected $table = 'shopping_carts';
    
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

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Ecommerce\Database\Factories\ShoppingCartFactory::new();
    }
}
