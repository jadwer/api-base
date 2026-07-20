<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Product\Models\Product;
// Paquete A: era App\Models\User, la clase DOCUMENTADA COMO NO USADA (deuda de
// las dos clases User). Al exponer assignedUser/countedByUser en el Schema, la
// serializacion tronaba con "Unable to resolve App\Models\User to a resource
// object" porque el UserSchema registrado es el del modulo.
use Modules\User\Models\User;

/**
 * IV-M001: Cycle Count Model
 *
 * Represents a scheduled or completed cycle count for inventory accuracy.
 */
class CycleCount extends Model
{
    use HasFactory;

    protected $table = 'cycle_counts';

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const ABC_CLASS_A = 'A';
    public const ABC_CLASS_B = 'B';
    public const ABC_CLASS_C = 'C';

    protected $fillable = [
        'count_number',
        'warehouse_id',
        'warehouse_location_id',
        'product_id',
        'scheduled_date',
        'completed_date',
        'status',
        'system_quantity',
        'counted_quantity',
        'variance_quantity',
        'variance_value',
        'assigned_to',
        'counted_by',
        'abc_class',
        'adjustment_movement_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'system_quantity' => 'float',
        'counted_quantity' => 'float',
        'variance_quantity' => 'float',
        'variance_value' => 'float',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => self::STATUS_SCHEDULED,
    ];

    // Calculated attributes
    protected $appends = ['has_variance', 'variance_percentage'];

    public function getHasVarianceAttribute(): bool
    {
        if ($this->variance_quantity === null) {
            return false;
        }
        return abs($this->variance_quantity) > 0.0001;
    }

    public function getVariancePercentageAttribute(): ?float
    {
        if ($this->system_quantity === null || $this->system_quantity == 0) {
            return null;
        }
        if ($this->variance_quantity === null) {
            return null;
        }
        return round(($this->variance_quantity / $this->system_quantity) * 100, 2);
    }

    // Status checks
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    // Business logic
    public function startCount(): void
    {
        if (!$this->isScheduled()) {
            throw new \Exception('Can only start a scheduled count');
        }

        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
        ]);
    }

    public function recordCount(float $countedQuantity, ?int $countedById = null): void
    {
        if (!$this->isInProgress()) {
            throw new \Exception('Can only record count for in-progress counts');
        }

        // Get current system quantity
        $stock = Stock::where('warehouse_id', $this->warehouse_id)
            ->where('product_id', $this->product_id)
            ->first();

        $systemQty = $stock ? $stock->quantity : 0;
        $variance = $countedQuantity - $systemQty;

        $this->update([
            'system_quantity' => $systemQty,
            'counted_quantity' => $countedQuantity,
            'variance_quantity' => $variance,
            'counted_by' => $countedById,
            'completed_date' => now(),
            'status' => self::STATUS_COMPLETED,
        ]);
    }

    public function cancel(?string $reason = null): void
    {
        if ($this->isCompleted()) {
            throw new \Exception('Cannot cancel a completed count');
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $reason ? ($this->notes . "\nCancelled: " . $reason) : $this->notes,
        ]);
    }

    // Scopes
    /**
     * Paquete A (auditoria 10 pasos): buscador del listado. El FE ya mandaba
     * filter[search] pero el Schema no lo declaraba y el backend respondia 400.
     */
    public function scopeSearch($query, string $term)
    {
        $term = trim($term);

        return $query->where(function ($q) use ($term) {
            $q->where('count_number', 'like', "%{$term}%")
                ->orWhereHas('product', function ($p) use ($term) {
                    $p->where('name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%");
                })
                ->orWhereHas('warehouse', function ($w) use ($term) {
                    $w->where('name', 'like', "%{$term}%");
                });
        });
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeWithVariance($query)
    {
        return $query->whereNotNull('variance_quantity')
            ->where('variance_quantity', '!=', 0);
    }

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeDueToday($query)
    {
        return $query->where('scheduled_date', today())
            ->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('scheduled_date', '<', today())
            ->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeScheduledAfter($query, string $date)
    {
        return $query->where('scheduled_date', '>=', $date);
    }

    public function scopeScheduledBefore($query, string $date)
    {
        return $query->where('scheduled_date', '<=', $date);
    }

    public function scopeHasVariance($query, $value)
    {
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            return $query->whereNotNull('variance_quantity')
                ->where('variance_quantity', '!=', 0);
        }
        return $query->where(function ($q) {
            $q->whereNull('variance_quantity')
              ->orWhere('variance_quantity', '=', 0);
        });
    }

    // Relationships
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseLocation()
    {
        return $this->belongsTo(WarehouseLocation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function countedBy()
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function adjustmentMovement()
    {
        return $this->belongsTo(InventoryMovement::class, 'adjustment_movement_id');
    }

    // Auto-generate count_number on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->count_number)) {
                $model->count_number = static::generateCountNumber();
            }
        });
    }

    public static function generateCountNumber(): string
    {
        $prefix = 'CC';
        $date = now()->format('Ymd');
        $lastCount = static::where('count_number', 'LIKE', "{$prefix}{$date}%")
            ->orderBy('count_number', 'desc')
            ->first();

        if ($lastCount) {
            $lastNumber = (int) substr($lastCount->count_number, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "{$prefix}{$date}{$nextNumber}";
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Inventory\Database\Factories\CycleCountFactory::new();
    }
}
