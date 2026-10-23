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
            // P2b (D8): ninguna linea de venta en $0, venga de donde venga.
            // Solo se revisa al crear o al cambiar el precio, para no bloquear
            // ediciones de lineas historicas (envios, cantidades surtidas).
            if ((! $item->exists || $item->isDirty('unit_price')) && (float) $item->unit_price <= 0) {
                throw new \Modules\Sales\Exceptions\SaleLineWithoutPriceException(
                    $item->product_id ? (int) $item->product_id : null,
                    $item->product_id ? \Modules\Product\Models\Product::query()->whereKey($item->product_id)->value('name') : null,
                );
            }

            if ($item->quantity && $item->unit_price) {
                $gross = round($item->quantity * $item->unit_price, 2);
                // Hallazgo Atomo 22-10: el descuento podia pasar del importe (total negativo)
                if ((float) ($item->discount ?? 0) > $gross) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'discount' => 'El descuento de la partida no puede ser mayor que su importe.',
                    ]);
                }
                $item->total = round($gross - ($item->discount ?? 0), 2);
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
