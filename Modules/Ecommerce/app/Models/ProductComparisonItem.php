<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ecommerce\Database\Factories\ProductComparisonItemFactory;
use Modules\Product\Models\Product;

class ProductComparisonItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'comparison_id',
        'product_id',
        'position',
    ];

    protected $casts = [
        'comparison_id' => 'integer',
        'product_id' => 'integer',
        'position' => 'integer',
    ];

    protected $attributes = [
        'position' => 0,
    ];

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory()
    {
        return ProductComparisonItemFactory::new();
    }

    /**
     * Get the comparison that owns this item
     */
    public function comparison(): BelongsTo
    {
        return $this->belongsTo(ProductComparison::class, 'comparison_id');
    }

    /**
     * Get the product for this comparison item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
