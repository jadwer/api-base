<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;

/**
 * SA-M006: RemissionItem Model
 *
 * Individual line item in a remission document.
 *
 * @property int $id
 * @property int $remission_id
 * @property int $sales_order_item_id
 * @property int|null $product_id
 * @property float $quantity
 * @property string|null $product_name
 * @property string|null $product_sku
 * @property string|null $unit
 * @property string|null $notes
 * @property string|null $batch_number
 * @property \Carbon\Carbon|null $expiry_date
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class RemissionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'remission_id', 'sales_order_item_id', 'product_id',
        'quantity', 'product_name', 'product_sku', 'unit',
        'notes', 'batch_number', 'expiry_date', 'metadata',
    ];

    protected $casts = [
        'id' => 'integer',
        'remission_id' => 'integer',
        'sales_order_item_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'float',
        'expiry_date' => 'date',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'unit' => 'PZA',
    ];

    // Relationships

    public function remission(): BelongsTo
    {
        return $this->belongsTo(Remission::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Factory

    protected static function newFactory()
    {
        return \Modules\Sales\Database\Factories\RemissionItemFactory::new();
    }

    // Boot - Populate snapshot data on create

    protected static function booted()
    {
        static::creating(function (RemissionItem $item) {
            // Auto-populate product snapshot if not provided
            if ($item->product_id && empty($item->product_name)) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $item->product_name = $item->product_name ?? $product->name;
                    $item->product_sku = $item->product_sku ?? $product->sku;
                    $item->unit = $item->unit ?? $product->unit ?? 'PZA';
                }
            }

            // Fallback to sales order item if available
            if ($item->sales_order_item_id && empty($item->product_name)) {
                $orderItem = SalesOrderItem::with('product')->find($item->sales_order_item_id);
                if ($orderItem) {
                    $item->product_id = $item->product_id ?? $orderItem->product_id;
                    $item->product_name = $item->product_name ?? $orderItem->product?->name;
                    $item->product_sku = $item->product_sku ?? $orderItem->product?->sku;
                }
            }
        });
    }
}
