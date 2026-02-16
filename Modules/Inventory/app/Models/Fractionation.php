<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Fractionation Model
 *
 * Represents a product fractionation operation where a bulk product
 * is split into smaller presentation products.
 *
 * @property int $id
 * @property string $folio_number
 * @property int $source_product_id
 * @property int $destination_product_id
 * @property int|null $product_conversion_id
 * @property int $warehouse_id
 * @property int $user_id
 * @property float $source_quantity
 * @property float $produced_quantity
 * @property float $waste_percentage
 * @property float $waste_quantity
 * @property float $conversion_factor_used
 * @property int|null $exit_movement_id
 * @property int|null $entry_movement_id
 * @property string $status
 * @property string|null $notes
 * @property \Carbon\Carbon|null $executed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Fractionation extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'fractionations';

    protected $fillable = [
        'folio_number',
        'source_product_id',
        'destination_product_id',
        'product_conversion_id',
        'warehouse_id',
        'user_id',
        'source_quantity',
        'produced_quantity',
        'waste_percentage',
        'waste_quantity',
        'conversion_factor_used',
        'exit_movement_id',
        'entry_movement_id',
        'status',
        'notes',
        'executed_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'source_product_id' => 'integer',
        'destination_product_id' => 'integer',
        'product_conversion_id' => 'integer',
        'warehouse_id' => 'integer',
        'user_id' => 'integer',
        'source_quantity' => 'float',
        'produced_quantity' => 'float',
        'waste_percentage' => 'float',
        'waste_quantity' => 'float',
        'conversion_factor_used' => 'float',
        'exit_movement_id' => 'integer',
        'entry_movement_id' => 'integer',
        'executed_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'folio_number', 'source_product_id', 'destination_product_id',
                'source_quantity', 'produced_quantity', 'waste_percentage',
                'status', 'warehouse_id', 'user_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships

    public function sourceProduct(): BelongsTo
    {
        return $this->belongsTo(\Modules\Product\Models\Product::class, 'source_product_id');
    }

    public function destinationProduct(): BelongsTo
    {
        return $this->belongsTo(\Modules\Product\Models\Product::class, 'destination_product_id');
    }

    public function productConversion(): BelongsTo
    {
        return $this->belongsTo(ProductConversion::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\User\Models\User::class);
    }

    public function exitMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'exit_movement_id');
    }

    public function entryMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'entry_movement_id');
    }

    // Helpers

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

    // Scopes

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeByWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeBySourceProduct($query, int $productId)
    {
        return $query->where('source_product_id', $productId);
    }

    protected static function newFactory()
    {
        return \Modules\Inventory\Database\Factories\FractionationFactory::new();
    }
}
