<?php

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Modules\Purchase\Database\Factories\PurchaseOrderItemFactory;

class PurchaseOrderItem extends Model
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
            'purchase_order_id' => 'integer',
            'product_id' => 'integer',
            'unit_price' => 'float',
            'discount' => 'float',
            'subtotal' => 'float',
            'total' => 'float',
            'received_quantity' => 'float',
            'metadata' => 'array',
            'invoiced_quantity' => 'float',
            'invoiced_amount' => 'float',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Calcular subtotal y total automáticamente antes de guardar
        static::saving(function ($item) {
            if ($item->quantity && $item->unit_price) {
                $item->subtotal = $item->quantity * $item->unit_price;
                $item->total = $item->subtotal - ($item->discount ?? 0);
            }

            // PU-005: Validate received quantity with tolerance
            if ($item->received_quantity !== null && $item->quantity) {
                $tolerance = config('purchase.receiving_tolerance_percent', 5);
                $maxAllowed = $item->quantity * (1 + ($tolerance / 100));

                if ($item->received_quantity > $maxAllowed) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'received_quantity' => sprintf(
                            'La cantidad recibida (%.2f) excede la cantidad ordenada (%.2f) más la tolerancia del %d%%. Máximo permitido: %.2f',
                            $item->received_quantity,
                            $item->quantity,
                            $tolerance,
                            $maxAllowed
                        )
                    ]);
                }

                if ($item->received_quantity < 0) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'received_quantity' => 'La cantidad recibida no puede ser negativa.'
                    ]);
                }
            }
        });
    }

    /**
     * Get the activity log options.
     * Temporalmente comentado para testing
     */
    /*
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['purchase_order_id', 'product_id', 'quantity', 'unit_price', 'subtotal'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    */

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PurchaseOrderItemFactory
    {
        return PurchaseOrderItemFactory::new();
    }

    // ========== RELATIONSHIPS ==========

    /**
     * Get the purchase order that owns the purchase order item.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the product that owns the purchase order item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Product\Models\Product::class);
    }

    // ========== SCOPES ==========

    /**
     * Apply filters to the query.
     */
    public function scopeFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('purchase_order_id'), function ($query) use ($request) {
                return $query->where('purchase_order_id', $request->purchase_order_id);
            })
            ->when($request->filled('product_id'), function ($query) use ($request) {
                return $query->where('product_id', $request->product_id);
            });
    }
}
