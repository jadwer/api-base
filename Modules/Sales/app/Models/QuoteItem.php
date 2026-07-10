<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $quote_id
 * @property int $product_id
 * @property float $quantity
 * @property float $unit_price
 * @property float $quoted_price
 * @property float $discount_percentage
 * @property float $discount_amount
 * @property float $tax_rate
 * @property float $tax_amount
 * @property float $total
 * @property string|null $product_name
 * @property string|null $product_sku
 * @property string|null $notes
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class QuoteItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'quote_id', 'product_id', 'quantity', 'unit_price', 'quoted_price',
        'discount_percentage', 'discount_amount', 'tax_rate', 'tax_amount', 'total',
        'product_name', 'product_sku', 'notes', 'metadata',
    ];

    protected $casts = [
        'id' => 'integer',
        'quote_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'float',
        'unit_price' => 'float',
        'quoted_price' => 'float',
        'discount_percentage' => 'float',
        'discount_amount' => 'float',
        'tax_rate' => 'float',
        'tax_amount' => 'float',
        'total' => 'float',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['quantity', 'quoted_price', 'discount_percentage', 'total'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Boot the model.
     * Auto-calculate totals when values change
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate before saving
        static::saving(function ($item) {
            $item->calculateTotals();
        });

        // Recalculate quote totals after save
        static::saved(function ($item) {
            $item->quote?->recalculateTotals();
        });

        // Recalculate quote totals after delete
        static::deleted(function ($item) {
            $item->quote?->recalculateTotals();
        });
    }

    /**
     * Calculate totals based on quantity, price, discount, and tax
     *
     * Regla de precedencia del descuento: si en este save solo cambio
     * discount_amount (dirty) y NO discount_percentage, el monto es la fuente
     * de verdad: se capea al subtotal y se deriva el porcentaje. En cualquier
     * otro caso (solo porcentaje, ambos, o ninguno) el porcentaje manda y el
     * monto se deriva de el, como siempre. Invariante: monto y porcentaje
     * quedan consistentes tras guardar.
     */
    public function calculateTotals(): void
    {
        $quantity = $this->quantity ?? 1;
        $quotedPrice = $this->quoted_price ?? $this->unit_price ?? 0;
        $taxRate = $this->tax_rate ?? 16; // 16% IVA México

        // Calculate subtotal before discount
        $subtotal = $quantity * $quotedPrice;

        $amountIsSource = $this->isDirty('discount_amount')
            && ! $this->isDirty('discount_percentage');

        if ($amountIsSource) {
            // Explicit amount: clamp to [0, subtotal] and derive the percentage
            $amount = min(max((float) ($this->discount_amount ?? 0), 0.0), $subtotal);
            $this->discount_amount = $amount;
            $this->discount_percentage = $subtotal > 0
                ? round(($amount / $subtotal) * 100, 2)
                : 0;
        } else {
            // Percentage drives the amount (default behavior)
            $discountPercentage = $this->discount_percentage ?? 0;
            $this->discount_amount = $subtotal * ($discountPercentage / 100);
        }

        // Subtotal after discount
        $subtotalAfterDiscount = $subtotal - $this->discount_amount;

        // Calculate tax
        $this->tax_amount = $subtotalAfterDiscount * ($taxRate / 100);

        // Final total (subtotal after discount + tax)
        $this->total = $subtotalAfterDiscount + $this->tax_amount;
    }

    // Relationships
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Computed Attributes
    public function getSubtotalBeforeDiscountAttribute(): float
    {
        return $this->quantity * $this->quoted_price;
    }

    public function getSubtotalAfterDiscountAttribute(): float
    {
        return $this->subtotalBeforeDiscount - $this->discount_amount;
    }

    public function getPriceVarianceAttribute(): float
    {
        if ($this->unit_price == 0) {
            return 0;
        }
        return (($this->quoted_price - $this->unit_price) / $this->unit_price) * 100;
    }

    public function getEffectiveDiscountPercentageAttribute(): float
    {
        if ($this->unit_price == 0) {
            return 0;
        }
        $totalDiscount = ($this->unit_price - $this->quoted_price) + ($this->quoted_price * $this->discount_percentage / 100);
        return ($totalDiscount / $this->unit_price) * 100;
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Sales\Database\Factories\QuoteItemFactory::new();
    }
}
