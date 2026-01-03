<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use App\Support\DatabaseCompatibilityHelper as DBHelper;

/**
 * SA-M002: Backorder Model
 *
 * Represents a pending order for items with insufficient stock.
 * Created automatically when order quantity exceeds available stock.
 *
 * @property int $id
 * @property int $sales_order_id
 * @property int $sales_order_item_id
 * @property int $product_id
 * @property int|null $warehouse_id
 * @property string $backorder_number
 * @property float $original_quantity
 * @property float $backorder_quantity
 * @property float $fulfilled_quantity
 * @property string $status
 * @property string $priority
 * @property string|null $expected_date
 * @property string|null $promised_date
 * @property string|null $notes
 * @property string|null $customer_notes
 * @property array|null $metadata
 * @property-read SalesOrder $salesOrder
 * @property-read SalesOrderItem $salesOrderItem
 * @property-read Product $product
 * @property-read Warehouse|null $warehouse
 */
class Backorder extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'sales_order_id' => 'integer',
        'sales_order_item_id' => 'integer',
        'product_id' => 'integer',
        'warehouse_id' => 'integer',
        'original_quantity' => 'float',
        'backorder_quantity' => 'float',
        'fulfilled_quantity' => 'float',
        'expected_date' => 'date',
        'promised_date' => 'date',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'pending',
        'priority' => 'normal',
        'fulfilled_quantity' => 0,
    ];

    /**
     * Sales order this backorder belongs to.
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Sales order item this backorder is for.
     */
    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    /**
     * Product being backordered.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Warehouse where stock is expected.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Generate unique backorder number.
     */
    public static function generateBackorderNumber(): string
    {
        $prefix = 'BO';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Get remaining quantity to fulfill.
     */
    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->backorder_quantity - $this->fulfilled_quantity);
    }

    /**
     * Check if backorder is fully fulfilled.
     */
    public function isFullyFulfilled(): bool
    {
        return $this->remaining_quantity <= 0;
    }

    /**
     * Check if backorder is pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'partially_fulfilled']);
    }

    /**
     * Check if backorder can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'partially_fulfilled']);
    }

    /**
     * Check if backorder is overdue.
     */
    public function isOverdue(): bool
    {
        if (!$this->expected_date) {
            return false;
        }
        return $this->expected_date->isPast() && $this->isPending();
    }

    /**
     * Update status based on fulfilled quantity.
     */
    public function updateStatus(): void
    {
        if ($this->isFullyFulfilled()) {
            $this->status = 'fulfilled';
        } elseif ($this->fulfilled_quantity > 0) {
            $this->status = 'partially_fulfilled';
        } else {
            $this->status = 'pending';
        }
        $this->save();
    }

    /**
     * Scope for pending backorders.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'partially_fulfilled']);
    }

    /**
     * Scope for a specific product.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope for a specific warehouse.
     */
    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope ordered by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderByRaw(DBHelper::orderByPriority('priority', ['urgent', 'high', 'normal', 'low']));
    }

    /**
     * Scope for overdue backorders.
     */
    public function scopeOverdue($query)
    {
        return $query->pending()
            ->whereNotNull('expected_date')
            ->where('expected_date', '<', now()->toDateString());
    }

    protected static function newFactory()
    {
        return \Modules\Sales\Database\Factories\BackorderFactory::new();
    }

    protected static function booted()
    {
        static::creating(function (Backorder $backorder) {
            if (empty($backorder->backorder_number)) {
                $backorder->backorder_number = self::generateBackorderNumber();
            }
        });
    }
}
