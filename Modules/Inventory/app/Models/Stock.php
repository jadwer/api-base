<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

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
    use HasFactory, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['quantity', 'reserved_quantity', 'status', 'minimum_stock', 'reorder_point'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'stock';

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
     * Scope para búsqueda general
     */
    public function scopeSearch($query, $value)
    {
        return $query->whereHas('product', function ($q) use ($value) {
            $q->where('name', 'like', $value)
              ->orWhere('sku', 'like', $value)
              ->orWhere('description', 'like', $value);
        })->orWhereHas('warehouse', function ($q) use ($value) {
            $q->where('name', 'like', $value)
              ->orWhere('code', 'like', $value);
        })->orWhereHas('location', function ($q) use ($value) {
            $q->where('name', 'like', $value)
              ->orWhere('code', 'like', $value);
        });
    }

    public function scopeLowStock($query, $value)
    {
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            return $query->whereColumn('quantity', '<=', 'minimum_stock')
                ->where('quantity', '>', 0);
        }
        return $query;
    }

    public function scopeOutOfStock($query, $value)
    {
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            return $query->where('quantity', '<=', 0);
        }
        return $query;
    }

    public function scopeMinQuantity($query, $value)
    {
        return $query->where('quantity', '>=', $value);
    }

    public function scopeMaxQuantity($query, $value)
    {
        return $query->where('quantity', '<=', $value);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Inventory\Database\Factories\StockFactory::new();
    }
}
