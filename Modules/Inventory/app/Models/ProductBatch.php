<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
 * @property string $quality_status IV-009: Quality control status (pending|in_testing|passed|failed|quarantine)
 * @property string|null $supplier_name
 * @property string|null $supplier_batch
 * @property string|null $quality_notes
 * @property array|null $test_results
 * @property array|null $certifications
 * @property array|null $metadata
 * @property-read \Modules\Product\Models\Product $product
 * @property-read Warehouse $warehouse
 * @property-read WarehouseLocation|null $warehouseLocation
 * @property-read \Illuminate\Database\Eloquent\Collection|InventoryMovement[] $inventoryMovements IV-M003: Movements affecting this batch
 * @property-read \Illuminate\Database\Eloquent\Collection|InventoryMovement[] $destinationMovements IV-M003: Movements where this batch is destination
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
        'initial_quantity' => 'float',
        'current_quantity' => 'float',
        'reserved_quantity' => 'float',
        'available_quantity' => 'float',
        'unit_cost' => 'float',
        'total_value' => 'float',
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
     * IV-M003: Movimientos de inventario que afectan este lote (origen).
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'product_batch_id');
    }

    /**
     * IV-M003: Movimientos de inventario donde este lote es destino.
     */
    public function destinationMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'destination_batch_id');
    }

    /**
     * IV-M003: Obtener todos los movimientos relacionados con este lote (origen o destino).
     */
    public function allMovements()
    {
        return InventoryMovement::where('product_batch_id', $this->id)
            ->orWhere('destination_batch_id', $this->id)
            ->orderBy('movement_date', 'desc');
    }

    /**
     * IV-M003: Verificar si el lote está próximo a expirar.
     *
     * @param int $daysThreshold Días de anticipación para considerar próximo a expirar
     * @return bool
     */
    public function isExpiringSoon(int $daysThreshold = 30): bool
    {
        if (!$this->expiration_date) {
            return false;
        }

        return $this->expiration_date->lte(now()->addDays($daysThreshold));
    }

    /**
     * IV-M003: Verificar si el lote ya expiró.
     */
    public function isExpired(): bool
    {
        if (!$this->expiration_date) {
            return false;
        }

        return $this->expiration_date->lt(now());
    }

    /**
     * IV-M003: Scope para lotes próximos a expirar (FEFO).
     */
    public function scopeExpiringSoon($query, int $daysThreshold = 30)
    {
        return $query->whereNotNull('expiration_date')
            ->where('expiration_date', '<=', now()->addDays($daysThreshold))
            ->where('expiration_date', '>', now())
            ->orderBy('expiration_date', 'asc');
    }

    /**
     * IV-M003: Scope para lotes activos ordenados por FEFO (First Expire, First Out).
     */
    public function scopeFefo($query)
    {
        return $query->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->orderBy('expiration_date', 'asc')
            ->orderBy('created_at', 'asc');
    }

    /**
     * IV-M003: Scope para lotes con stock disponible.
     */
    public function scopeWithAvailableStock($query)
    {
        return $query->where('status', 'active')
            ->whereRaw('current_quantity - reserved_quantity > 0');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Inventory\Database\Factories\ProductBatchFactory::new();
    }
}
