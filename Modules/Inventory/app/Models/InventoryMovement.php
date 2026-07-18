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
 * @property int|null $product_batch_id IV-M003: Direct FK to ProductBatch for lot traceability
 * @property int|null $destination_batch_id IV-M003: Destination batch for transfer/split operations
 * @property array|null $batch_info Legacy JSON field - prefer product_batch_id for traceability queries
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Modules\Product\Models\Product $product
 * @property-read Warehouse $warehouse
 * @property-read WarehouseLocation|null $location
 * @property-read Warehouse|null $destinationWarehouse
 * @property-read WarehouseLocation|null $destinationLocation
 * @property-read ProductBatch|null $productBatch IV-M003: Source batch for traceability
 * @property-read ProductBatch|null $destinationBatch IV-M003: Destination batch for transfers
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
        'product_batch_id' => 'integer',
        'destination_warehouse_id' => 'integer',
        'destination_location_id' => 'integer',
        'destination_batch_id' => 'integer',
        'quantity' => 'float',
        'unit_cost' => 'float',
        'total_value' => 'float',
        'user_id' => 'integer',
        'previous_stock' => 'float',
        'new_stock' => 'float',
        'batch_info' => 'array',
        'metadata' => 'array',
        'gl_journal_entry_id' => 'integer',
        'gl_posting_status' => 'string',
        'cost_per_unit' => 'float',
        'total_cost' => 'float',
        'quality_checked' => 'boolean',
        'quality_checked_at' => 'datetime',
        'quality_checked_by' => 'integer',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
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
     * R4 (diseno post-refactor): reference_type de movimientos generados por el
     * SISTEMA como parte de un flujo ya validado (entrega de remision u orden,
     * reversas por cancelacion de venta/compra). La regla IV-009 regula inspecciones
     * de calidad de operaciones manuales; estos quedan exentos POR ORIGEN, en la
     * regla y no en cada call-site: un caller nuevo que olvide un flag no puede
     * revivir el 422 de "requieren verificacion de calidad".
     */
    public const QUALITY_EXEMPT_REFERENCE_TYPES = [
        'remission',
        'sales_order',
        'sales_cancel',
        'purchase_cancel',
    ];

    /**
     * Boot method to dispatch events
     * IV-010: Trigger GL posting when movement is created
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($movement) {
            // IV-009: Quality check validation for exits and transfers
            if ($movement->requiresQualityCheck() && $movement->status === self::STATUS_COMPLETED && !$movement->quality_checked) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'quality_checked' => sprintf(
                        'Los movimientos de tipo "%s" requieren verificación de calidad antes de completarse.',
                        $movement->movement_type
                    )
                ]);
            }
        });

        static::updating(function ($movement) {
            // IV-009: Quality check validation when updating to completed status
            if ($movement->isDirty('status') && $movement->status === self::STATUS_COMPLETED) {
                if ($movement->requiresQualityCheck() && !$movement->quality_checked) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'quality_checked' => sprintf(
                            'Los movimientos de tipo "%s" requieren verificación de calidad antes de completarse.',
                            $movement->movement_type
                        )
                    ]);
                }
            }

            // Auto-set quality_checked_at when quality_checked changes to true
            if ($movement->isDirty('quality_checked') && $movement->quality_checked && !$movement->quality_checked_at) {
                $movement->quality_checked_at = now();
            }
        });

        static::created(function ($movement) {
            // Dispatch event for GL integration
            event(new \Modules\Inventory\Events\InventoryMovementCreated($movement));
        });
    }

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
     * IV-M003: Scope to filter by product batch
     */
    public function scopeByBatch($query, int $batchId)
    {
        return $query->where(function ($q) use ($batchId) {
            $q->where('product_batch_id', $batchId)
              ->orWhere('destination_batch_id', $batchId);
        });
    }

    /**
     * IV-M003: Scope to filter movements with batch traceability
     */
    public function scopeWithBatchTraceability($query)
    {
        return $query->whereNotNull('product_batch_id');
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
     * Determine if this movement type requires quality check
     *
     * IV-009: Exits and transfers require quality check before completion
     *
     * @return bool
     */
    public function requiresQualityCheck(): bool
    {
        // R4: exencion por origen para movimientos del sistema (la entrega o reversa
        // confirmada ES la validacion del flujo; no son inspecciones de calidad).
        if (in_array($this->reference_type, self::QUALITY_EXEMPT_REFERENCE_TYPES, true)) {
            return false;
        }

        return in_array($this->movement_type, [
            self::MOVEMENT_TYPE_EXIT,
            self::MOVEMENT_TYPE_TRANSFER,
        ]);
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
     * IV-M003: El movimiento puede pertenecer a un lote de producto (trazabilidad).
     */
    public function productBatch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class);
    }

    /**
     * IV-M003: El movimiento puede tener un lote destino (para transferencias/splits).
     */
    public function destinationBatch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'destination_batch_id');
    }

    /**
     * El movimiento pertenece a un usuario.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\User\Models\User::class);
    }

    /**
     * El movimiento puede tener un usuario que verificó la calidad.
     */
    public function qualityChecker(): BelongsTo
    {
        return $this->belongsTo(\Modules\User\Models\User::class, 'quality_checked_by');
    }

    /**
     * IV-007: El movimiento puede tener un usuario que aprobó el ajuste.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\User\Models\User::class, 'approved_by');
    }

    /**
     * IV-007: Approve adjustment movement
     *
     * Only adjustments require approval. This method updates the approval status
     * and marks the movement as approved.
     *
     * @param int $userId User ID performing the approval
     * @param string|null $notes Optional approval notes
     * @return bool
     * @throws \Exception If movement is not an adjustment or already approved
     */
    public function approve(int $userId, ?string $notes = null): bool
    {
        if (!$this->isAdjustment()) {
            throw new \Exception('Only adjustment movements require approval');
        }

        if ($this->approval_status === 'approved') {
            return true; // Already approved
        }

        if ($this->approval_status === 'rejected') {
            throw new \Exception('Cannot approve a rejected movement');
        }

        $this->update([
            'approval_status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);

        return true;
    }

    /**
     * IV-007: Reject adjustment movement
     *
     * @param int $userId User ID performing the rejection
     * @param string $reason Rejection reason
     * @return bool
     * @throws \Exception If movement is not an adjustment
     */
    public function reject(int $userId, string $reason): bool
    {
        if (!$this->isAdjustment()) {
            throw new \Exception('Only adjustment movements can be rejected');
        }

        if ($this->approval_status === 'approved') {
            throw new \Exception('Cannot reject an approved movement');
        }

        $this->update([
            'approval_status' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => now(),
            'approval_notes' => $reason,
        ]);

        return true;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Inventory\Database\Factories\InventoryMovementFactory::new();
    }
}