<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $sales_order_id
 * @property int $product_id
 * @property float $quantity
 * @property float $unit_price
 * @property float $discount
 * @property float $total
 * @property array|null $metadata
 * @property int|null $ar_invoice_line_id
 * @property float $invoiced_quantity
 * @property float $invoiced_amount
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Modules\Sales\Models\SalesOrder $salesOrder
 * @property \Modules\Product\Models\Product $product
 */
class SalesOrderItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'sales_order_id', 'product_id', 'quantity', 'shipped_quantity',
        'unit_price', 'discount', 'total',
        'original_currency_code', 'original_unit_price', 'exchange_rate_used',
        'metadata', 'fulfillment_status',
        'ar_invoice_line_id', 'invoiced_quantity', 'invoiced_amount',
    ];

    protected $casts = [
        'id' => 'integer',
        'sales_order_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'float',
        'shipped_quantity' => 'float',
        'unit_price' => 'float',
        'discount' => 'float',
        'total' => 'float',
        'original_unit_price' => 'float',
        'exchange_rate_used' => 'float',
        'metadata' => 'array',
        'ar_invoice_line_id' => 'integer',
        'invoiced_quantity' => 'float',
        'invoiced_amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'shipped_quantity' => 0,
        'fulfillment_status' => 'pending',
    ];

    /**
     * Boot the model.
     * Auto-calculate total when quantity, unit_price, or discount changes
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate total before saving
        static::saving(function ($item) {
            if ($item->quantity && $item->unit_price) {
                $item->total = ($item->quantity * $item->unit_price) - ($item->discount ?? 0);
            }
        });
    }

    // Activity Log configuration
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['quantity', 'unit_price', 'discount', 'total', 'metadata'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Product\Models\Product::class);
    }

    /**
     * SA-M001: Shipment items for this order item.
     */
    public function shipmentItems(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    /**
     * SA-M001: Get remaining quantity to ship.
     */
    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->quantity - $this->shipped_quantity);
    }

    /**
     * SA-M001: Check if item is fully shipped.
     */
    public function isFullyShipped(): bool
    {
        return $this->shipped_quantity >= $this->quantity;
    }

    /**
     * SA-M001: Update fulfillment status based on shipped quantity.
     */
    public function updateFulfillmentStatus(): void
    {
        if ($this->shipped_quantity <= 0) {
            $this->fulfillment_status = 'pending';
        } elseif ($this->shipped_quantity < $this->quantity) {
            $this->fulfillment_status = 'partially_shipped';
        } else {
            $this->fulfillment_status = 'shipped';
        }
        $this->saveQuietly();
    }

    /**
     * SA-M001: Recalculate shipped quantity from shipment items.
     */
    public function recalculateShippedQuantity(): void
    {
        $this->shipped_quantity = $this->shipmentItems()
            ->whereHas('shipment', fn($q) => $q->whereIn('status', ['shipped', 'delivered']))
            ->sum('quantity');
        $this->updateFulfillmentStatus();
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Sales\Database\Factories\SalesOrderItemFactory::new();
    }
}
