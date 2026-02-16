<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * ProductConversion Model
 *
 * Defines a conversion relationship between two products.
 * E.g., 1 unit of "Reactivo Granel 15,000L" produces 1500 units of "Reactivo Botella 10L" with 2% waste.
 *
 * @property int $id
 * @property int $source_product_id
 * @property int $destination_product_id
 * @property float $conversion_factor
 * @property float $waste_percentage
 * @property bool $is_active
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProductConversion extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'product_conversions';

    protected $fillable = [
        'source_product_id',
        'destination_product_id',
        'conversion_factor',
        'waste_percentage',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'id' => 'integer',
        'source_product_id' => 'integer',
        'destination_product_id' => 'integer',
        'conversion_factor' => 'float',
        'waste_percentage' => 'float',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'source_product_id', 'destination_product_id',
                'conversion_factor', 'waste_percentage', 'is_active',
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

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForSourceProduct($query, int $productId)
    {
        return $query->where('source_product_id', $productId);
    }

    public function scopeForDestinationProduct($query, int $productId)
    {
        return $query->where('destination_product_id', $productId);
    }

    /**
     * Calculate produced quantity for a given source quantity.
     */
    public function calculateProducedQuantity(float $sourceQuantity): float
    {
        return $sourceQuantity * $this->conversion_factor * (1 - $this->waste_percentage / 100);
    }

    /**
     * Calculate waste quantity for a given source quantity.
     */
    public function calculateWasteQuantity(float $sourceQuantity): float
    {
        return $sourceQuantity * $this->conversion_factor * ($this->waste_percentage / 100);
    }

    protected static function newFactory()
    {
        return \Modules\Inventory\Database\Factories\ProductConversionFactory::new();
    }
}
