<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasPermissions;
use Modules\User\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ShoppingCart extends Model
{
    use HasFactory, HasPermissions, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_amount', 'coupon_code', 'discount_amount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'shopping_carts';
    
    protected $fillable = [
        'session_id', 'user_id', 'status', 'expires_at', 'total_amount', 'currency', 'coupon_code', 'discount_amount', 'tax_amount', 'shipping_amount', 'notes', 'metadata'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'total_amount' => 'float',
        'discount_amount' => 'float',
        'tax_amount' => 'float',
        'shipping_amount' => 'float',
        'metadata' => 'array'
    ];

    protected $appends = [
        'itemsCount',
        'subtotalAmount', 
        'finalTotal',
        'isExpired',
        'canApplyCoupon'
    ];

    // Campos Calculados (similar a Finance module)
    public function getItemsCountAttribute(): int
    {
        return $this->cartItems()->count();
    }

    public function getSubtotalAmountAttribute(): float
    {
        return $this->cartItems()->sum('total') ?? 0.00;
    }

    public function getFinalTotalAttribute(): float
    {
        $subtotal = $this->getSubtotalAmountAttribute();
        $discount = $this->discount_amount ?? 0.00;
        $tax = $this->tax_amount ?? 0.00;
        $shipping = $this->shipping_amount ?? 0.00;
        
        return $subtotal - $discount + $tax + $shipping;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getCanApplyCouponAttribute(): bool
    {
        return !$this->coupon_code && $this->status === 'active' && !$this->getIsExpiredAttribute();
    }

    // Business Logic Methods
    public function isEmpty(): bool
    {
        return $this->getItemsCountAttribute() === 0;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->getIsExpiredAttribute();
    }

    public function canAddItems(): bool
    {
        return $this->isActive() && !$this->isEmpty();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>=', now())->orWhereNull('expires_at');
    }

    // Relationships
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
