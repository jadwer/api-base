<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'price' => 'float',
        'cost' => 'float',
        'iva' => 'boolean',
        'unit_id' => 'integer',
        'category_id' => 'integer',
        'brand_id' => 'integer',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Un producto puede tener registros de stock en diferentes bodegas.
     */
    public function stock(): HasMany
    {
        return $this->hasMany(\Modules\Inventory\Models\Stock::class);
    }

    /**
     * Un producto puede tener múltiples lotes.
     */
    public function productBatches(): HasMany
    {
        return $this->hasMany(\Modules\Inventory\Models\ProductBatch::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Product\Database\Factories\ProductFactory::new();
    }
}
