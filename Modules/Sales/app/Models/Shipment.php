<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inventory\Models\Warehouse;

/**
 * SA-M001: Shipment Model
 *
 * Represents a shipment for a sales order, supporting partial fulfillment.
 *
 * @property int $id
 * @property int $sales_order_id
 * @property int|null $warehouse_id
 * @property string $shipment_number
 * @property string $status
 * @property string|null $carrier
 * @property string|null $tracking_number
 * @property string|null $tracking_url
 * @property string|null $ship_date
 * @property string|null $estimated_delivery
 * @property string|null $actual_delivery
 * @property string|null $shipping_address
 * @property string|null $notes
 * @property float $shipping_cost
 * @property float|null $weight
 * @property array|null $metadata
 * @property-read SalesOrder $salesOrder
 * @property-read Warehouse|null $warehouse
 * @property-read \Illuminate\Database\Eloquent\Collection|ShipmentItem[] $items
 */
class Shipment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'sales_order_id' => 'integer',
        'warehouse_id' => 'integer',
        'ship_date' => 'date',
        'estimated_delivery' => 'date',
        'actual_delivery' => 'date',
        'shipping_cost' => 'float',
        'weight' => 'float',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'pending',
        'shipping_cost' => 0,
    ];

    /**
     * Sales order this shipment belongs to.
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Warehouse this shipment ships from.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Items in this shipment.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    /**
     * Generate unique shipment number.
     */
    public static function generateShipmentNumber(): string
    {
        $prefix = 'SHP';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Check if shipment can be modified.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Check if shipment is in transit or delivered.
     */
    public function isShipped(): bool
    {
        return in_array($this->status, ['shipped', 'delivered']);
    }

    /**
     * Get total items count.
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->count();
    }

    /**
     * Get total quantity shipped.
     */
    public function getTotalQuantityAttribute(): float
    {
        return $this->items->sum('quantity');
    }

    /**
     * Scope for pending shipments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for shipped shipments.
     */
    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    /**
     * Scope for delivered shipments.
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope by order.
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('sales_order_id', $orderId);
    }

    protected static function newFactory()
    {
        return \Modules\Sales\Database\Factories\ShipmentFactory::new();
    }

    protected static function booted()
    {
        static::creating(function (Shipment $shipment) {
            if (empty($shipment->shipment_number)) {
                $shipment->shipment_number = self::generateShipmentNumber();
            }
        });
    }
}
