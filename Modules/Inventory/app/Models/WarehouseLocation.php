<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo WarehouseLocation
 *
 * Ubicaciones internas dentro de una bodega.
 *
 * @property int $id
 * @property int $warehouse_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string $location_type
 * @property string|null $aisle
 * @property string|null $rack
 * @property string|null $shelf
 * @property string|null $level
 * @property string|null $position
 * @property string|null $barcode
 * @property float|null $max_weight
 * @property float|null $max_volume
 * @property string|null $dimensions
 * @property bool $is_active
 * @property bool $is_pickable
 * @property bool $is_receivable
 * @property int $priority
 * @property array|null $metadata
 * @property-read Warehouse $warehouse
 * @property-read \Illuminate\Database\Eloquent\Collection $stock
 * @property-read \Illuminate\Database\Eloquent\Collection $productBatches
 */
class WarehouseLocation extends Model
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
        'warehouse_id' => 'integer',
        'is_active' => 'boolean',
        'is_pickable' => 'boolean',
        'is_receivable' => 'boolean',
        'priority' => 'integer',
        'max_weight' => 'decimal:2',
        'max_volume' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Una ubicación pertenece a una bodega.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Una ubicación puede tener muchos registros de stock.
     */
    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class, 'warehouse_location_id');
    }

    /**
     * Una ubicación puede tener muchos lotes de productos.
     */
    public function productBatches(): HasMany
    {
        return $this->hasMany(ProductBatch::class, 'warehouse_location_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Inventory\Database\Factories\WarehouseLocationFactory::new();
    }
}
