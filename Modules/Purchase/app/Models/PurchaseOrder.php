<?php

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Modules\Purchase\Database\Factories\PurchaseOrderFactory;

class PurchaseOrder extends Model
{
    use HasFactory; // LogsActivity temporalmente desactivado para testing

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'supplier_id' => 'integer',
            'order_date' => 'date',
            'total_amount' => 'float',
        ];
    }

    /**
     * Get the activity log options.
     * Temporalmente comentado para testing
     */
    /*
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['supplier_id', 'order_date', 'status', 'total_amount', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    */

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PurchaseOrderFactory
    {
        return PurchaseOrderFactory::new();
    }

    // ========== RELATIONSHIPS ==========

    /**
     * Get the supplier that owns the purchase order.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the purchase order items for the purchase order.
     */
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    // ========== SCOPES ==========

    /**
     * Apply filters to the query.
     */
    public function scopeFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('supplier_id'), function ($query) use ($request) {
                return $query->where('supplier_id', $request->supplier_id);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->when($request->filled('order_date_from'), function ($query) use ($request) {
                return $query->where('order_date', '>=', $request->order_date_from);
            })
            ->when($request->filled('order_date_to'), function ($query) use ($request) {
                return $query->where('order_date', '<=', $request->order_date_to);
            });
    }
}
