<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Stock
 *
 * Representa la existencia de un producto en una bodega.
 *
 * @property int $id
 * @property int $product_id
 * @property int $warehouse_id
 * @property int|null $warehouse_location_id
 * @property float $quantity
 * @property float $reserved_quantity
 * @property float $available_quantity
 * @property float $minimum_stock
 * @property float|null $maximum_stock
 * @property float $reorder_point
 * @property float $unit_cost
 * @property float $total_value
 * @property string $status
 * @property string|null $last_movement_date
 * @property string|null $last_movement_type
 * @property array|null $batch_info
 * @property array|null $metadata
 * @property-read \Modules\Product\Models\Product $product
 * @property-read Warehouse $warehouse
 * @property-read WarehouseLocation|null $location
 */
class Stock extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'id' => 'integer',
        'product_id' => 'integer',
        'warehouse_id' => 'integer',
        'warehouse_location_id' => 'integer',
        'quantity' => 'decimal:4',
        'reserved_quantity' => 'decimal:4',
        'available_quantity' => 'decimal:4',
        'minimum_stock' => 'decimal:4',
        'maximum_stock' => 'decimal:4',
        'reorder_point' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_value' => 'decimal:4',
        'last_movement_date' => 'datetime',
        'batch_info' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * El stock pertenece a un producto.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Product\Models\Product::class);
    }

    /**
     * El stock pertenece a una bodega.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * El stock puede pertenecer a una ubicación interna.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Inventory\Database\Factories\StockFactory::new();
    }
}
