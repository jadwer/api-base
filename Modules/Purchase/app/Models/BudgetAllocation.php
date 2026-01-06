<?php

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PU-M003: Budget Allocation Model
 *
 * Tracks which purchase orders are allocated against which budgets.
 */
class BudgetAllocation extends Model
{
    use HasFactory;

    protected $table = 'budget_allocations';

    public const STATUS_COMMITTED = 'committed';
    public const STATUS_SPENT = 'spent';
    public const STATUS_RELEASED = 'released';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'budget_id',
        'purchase_order_id',
        'allocated_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'budget_id' => 'integer',
        'purchase_order_id' => 'integer',
        'allocated_amount' => 'float',
    ];

    protected $attributes = [
        'status' => self::STATUS_COMMITTED,
    ];

    // ========== RELATIONSHIPS ==========

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    // ========== SCOPES ==========

    public function scopeCommitted($query)
    {
        return $query->where('status', self::STATUS_COMMITTED);
    }

    public function scopeSpent($query)
    {
        return $query->where('status', self::STATUS_SPENT);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_COMMITTED, self::STATUS_SPENT]);
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Purchase\Database\Factories\BudgetAllocationFactory::new();
    }
}
