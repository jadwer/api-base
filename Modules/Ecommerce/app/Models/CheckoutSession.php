<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\User\Models\User;
use Modules\Sales\Models\SalesOrder;

class CheckoutSession extends Model
{
    use HasFactory;

    protected $table = 'checkout_sessions';

    protected $fillable = [
        'shopping_cart_id',
        'user_id',
        'status',
        'step',
        'shipping_method_id',
        'billing_address',
        'shipping_address',
        'contact_email',
        'contact_phone',
        'payment_method',
        'payment_intent_id',
        'subtotal_amount',
        'shipping_amount',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'expires_at',
        'completed_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'subtotal_amount' => 'float',
        'shipping_amount' => 'float',
        'tax_amount' => 'float',
        'discount_amount' => 'float',
        'total_amount' => 'float',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Calculated Attributes
    protected $appends = [
        'is_expired',
        'can_proceed_to_payment',
        'time_remaining',
    ];

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getCanProceedToPaymentAttribute(): bool
    {
        return $this->step === 'payment'
            && $this->status === 'initiated'
            && !$this->getIsExpiredAttribute()
            && $this->shipping_address !== null
            && $this->shipping_method_id !== null;
    }

    public function getTimeRemainingAttribute(): ?int
    {
        if (!$this->expires_at || $this->getIsExpiredAttribute()) {
            return null;
        }

        return (int) now()->diffInMinutes($this->expires_at);
    }

    // Business Logic Methods
    public function isComplete(): bool
    {
        return $this->status === 'completed' && $this->completed_at !== null;
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'payment_confirmed'
            && !$this->getIsExpiredAttribute()
            && $this->payment_intent_id !== null;
    }

    public function hasPendingPayment(): bool
    {
        return $this->status === 'payment_pending' && $this->payment_intent_id !== null;
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['initiated', 'payment_pending', 'payment_confirmed']);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->whereNotIn('status', ['completed', 'expired']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Relationships
    public function shoppingCart()
    {
        return $this->belongsTo(ShoppingCart::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function inventoryReservations()
    {
        return $this->hasMany(InventoryReservation::class);
    }

    public function salesOrder()
    {
        return $this->hasOne(SalesOrder::class, 'checkout_session_id');
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Ecommerce\Database\Factories\CheckoutSessionFactory::new();
    }
}
