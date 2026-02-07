<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SA-M001: Shipment Item Model
 *
 * Links a shipment to a sales order item with a specific quantity.
 *
 * @property int $id
 * @property int $shipment_id
 * @property int $sales_order_item_id
 * @property float $quantity
 * @property string|null $notes
 * @property array|null $metadata
 * @property-read Shipment $shipment
 * @property-read SalesOrderItem $salesOrderItem
 */
class ShipmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id', 'sales_order_item_id', 'quantity', 'notes', 'metadata',
    ];

    protected $casts = [
        'id' => 'integer',
        'shipment_id' => 'integer',
        'sales_order_item_id' => 'integer',
        'quantity' => 'float',
        'metadata' => 'array',
    ];

    /**
     * Parent shipment.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Sales order item being shipped.
     */
    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    /**
     * Get the product through the sales order item.
     */
    public function getProductAttribute()
    {
        return $this->salesOrderItem?->product;
    }

    protected static function newFactory()
    {
        return \Modules\Sales\Database\Factories\ShipmentItemFactory::new();
    }
}
