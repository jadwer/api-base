<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Modelo InventoryMovement
 *
 * Representa un movimiento de inventario (entrada, salida, transferencia, ajuste).
 *
 * @property int $id
 * @property string $movement_type
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property \Carbon\Carbon $movement_date
 * @property string|null $description
 * @property int $product_id
 * @property int $warehouse_id
 * @property int|null $warehouse_location_id
 * @property int|null $destination_warehouse_id
 * @property int|null $destination_location_id
 * @property float $quantity
 * @property float $unit_cost
 * @property float $total_value
 * @property string $status
 * @property int $user_id
 * @property float|null $previous_stock
 * @property float|null $new_stock
 * @property array|null $batch_info
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Modules\Product\Models\Product $product
 * @property-read Warehouse $warehouse
 * @property-read WarehouseLocation|null $location
 * @property-read Warehouse|null $destinationWarehouse
 * @property-read WarehouseLocation|null $destinationLocation
 * @property-read \Modules\User\Models\User $user
 */
class InventoryMovement extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The table associated with the model.
     */
    protected $table = 'inventory_movements';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'id' => 'integer',
        'reference_id' => 'integer',
        'movement_date' => 'datetime',
        'product_id' => 'integer',
        'warehouse_id' => 'integer',
        'warehouse_location_id' => 'integer',
        'destination_warehouse_id' => 'integer',
        'destination_location_id' => 'integer',
        'quantity' => 'float',
        'unit_cost' => 'float',
        'total_value' => 'float',
        'user_id' => 'integer',
        'previous_stock' => 'float',
        'new_stock' => 'float',
        'batch_info' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Movement types constants
     */
    public const MOVEMENT_TYPE_ENTRY = 'entry';
    public const MOVEMENT_TYPE_EXIT = 'exit';
    public const MOVEMENT_TYPE_TRANSFER = 'transfer';
    public const MOVEMENT_TYPE_ADJUSTMENT = 'adjustment';

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Reference types constants
     */
    public const REFERENCE_TYPE_PURCHASE = 'purchase';
    public const REFERENCE_TYPE_SALE = 'sale';
    public const REFERENCE_TYPE_TRANSFER = 'transfer';
    public const REFERENCE_TYPE_ADJUSTMENT = 'adjustment';
    public const REFERENCE_TYPE_MANUAL = 'manual';

    /**
     * Configuración de Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'movement_type', 'reference_type', 'reference_id', 'movement_date',
                'description', 'product_id', 'warehouse_id', 'quantity', 'unit_cost',
                'status', 'user_id'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scopes útiles
     */
    public function scopeEntries($query)
    {
        return $query->where('movement_type', self::MOVEMENT_TYPE_ENTRY);
    }

    public function scopeExits($query)
    {
        return $query->where('movement_type', self::MOVEMENT_TYPE_EXIT);
    }

    public function scopeTransfers($query)
    {
        return $query->where('movement_type', self::MOVEMENT_TYPE_TRANSFER);
    }

    public function scopeAdjustments($query)
    {
        return $query->where('movement_type', self::MOVEMENT_TYPE_ADJUSTMENT);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeByWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('movement_date', [$startDate, $endDate]);
    }

    /**
     * Helper methods
     */
    public function isEntry(): bool
    {
        return $this->movement_type === self::MOVEMENT_TYPE_ENTRY;
    }

    public function isExit(): bool
    {
        return $this->movement_type === self::MOVEMENT_TYPE_EXIT;
    }

    public function isTransfer(): bool
    {
        return $this->movement_type === self::MOVEMENT_TYPE_TRANSFER;
    }

    public function isAdjustment(): bool
    {
        return $this->movement_type === self::MOVEMENT_TYPE_ADJUSTMENT;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Get the signed quantity (negative for exits, positive for entries)
     */
    public function getSignedQuantity(): float
    {
        return $this->isExit() ? -abs($this->quantity) : abs($this->quantity);
    }

    /**
     * El movimiento pertenece a un producto.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Product\Models\Product::class);
    }

    /**
     * El movimiento pertenece a una bodega origen.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * El movimiento puede pertenecer a una ubicación origen.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }

    /**
     * El movimiento puede tener una bodega destino (para transferencias).
     */
    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    /**
     * El movimiento puede tener una ubicación destino (para transferencias).
     */
    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'destination_location_id');
    }

    /**
     * El movimiento pertenece a un usuario.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\User\Models\User::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Inventory\Database\Factories\InventoryMovementFactory::new();
    }
}