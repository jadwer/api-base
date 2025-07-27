<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo ProductBatch
 *
 * Representa un lote de producto con cantidad y caducidad.
 *
 * @property int $id
 * @property int $product_id
 * @property int $warehouse_id
 * @property int|null $warehouse_location_id
 * @property string $batch_number
 * @property string|null $lot_number
 * @property string|null $manufacturing_date
 * @property string|null $expiration_date
 * @property string|null $best_before_date
 * @property float $initial_quantity
 * @property float $current_quantity
 * @property float $reserved_quantity
 * @property float $available_quantity
 * @property float $unit_cost
 * @property float $total_value
 * @property string $status
 * @property string|null $supplier_name
 * @property string|null $supplier_batch
 * @property string|null $quality_notes
 * @property array|null $test_results
 * @property array|null $certifications
 * @property array|null $metadata
 * @property-read \Modules\Product\Models\Product $product
 * @property-read Warehouse $warehouse
 * @property-read WarehouseLocation|null $warehouseLocation
 */
class ProductBatch extends Model
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
        'manufacturing_date' => 'date',
        'expiration_date' => 'date',
        'best_before_date' => 'date',
        'initial_quantity' => 'decimal:4',
        'current_quantity' => 'decimal:4',
        'reserved_quantity' => 'decimal:4',
        'available_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_value' => 'decimal:4',
        'test_results' => 'array',
        'certifications' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Un lote pertenece a un producto.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Product\Models\Product::class);
    }

    /**
     * Un lote pertenece a una bodega.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Un lote puede pertenecer a una ubicación específica.
     */
    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Inventory\Database\Factories\ProductBatchFactory::new();
    }
}
