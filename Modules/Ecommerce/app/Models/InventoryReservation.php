<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Product\Models\Product;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\Stock;

class InventoryReservation extends Model
{
    use HasFactory;

    protected $table = 'inventory_reservations';

    protected $fillable = [
        'checkout_session_id',
        'stock_id',
        'product_id',
        'warehouse_id',
        'quantity_reserved',
        'status',
        'expires_at',
        'released_at',
        'fulfilled_at',
        'notes',
    ];

    protected $casts = [
        'quantity_reserved' => 'float',
        'expires_at' => 'datetime',
        'released_at' => 'datetime',
        'fulfilled_at' => 'datetime',
    ];

    // Calculated Attributes
    protected $appends = [
        'is_expired',
        'is_active',
        'time_remaining',
    ];

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        return $this->expires_at->isPast() && $this->status === 'active';
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && !$this->getIsExpiredAttribute();
    }

    public function getTimeRemainingAttribute(): ?int
    {
        if ($this->status !== 'active' || $this->getIsExpiredAttribute()) {
            return null;
        }

        return now()->diffInMinutes($this->expires_at);
    }

    // Business Logic Methods
    public function canBeReleased(): bool
    {
        return $this->status === 'active';
    }

    public function canBeFulfilled(): bool
    {
        return $this->status === 'active' && !$this->getIsExpiredAttribute();
    }

    public function release(string $reason = null): void
    {
        $this->update([
            'status' => 'released',
            'released_at' => now(),
            'notes' => $reason ?? $this->notes,
        ]);
    }

    public function fulfill(): void
    {
        $this->update([
            'status' => 'fulfilled',
            'fulfilled_at' => now(),
        ]);
    }

    public function expire(): void
    {
        $this->update([
            'status' => 'expired',
            'released_at' => now(),
            'notes' => 'Reservation expired automatically',
        ]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '<=', now());
    }

    public function scopeReleased($query)
    {
        return $query->where('status', 'released');
    }

    public function scopeFulfilled($query)
    {
        return $query->where('status', 'fulfilled');
    }

    public function scopeForCheckoutSession($query, int $sessionId)
    {
        return $query->where('checkout_session_id', $sessionId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    // Relationships
    public function checkoutSession()
    {
        return $this->belongsTo(CheckoutSession::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Ecommerce\Database\Factories\InventoryReservationFactory::new();
    }
}
