<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Product extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'sku', 'price', 'cost', 'is_active',
                'category_id', 'brand_id', 'unit_id'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

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
        'is_active' => 'boolean',
        'average_rating' => 'float',
        'total_reviews' => 'integer',
        'total_sales' => 'integer',
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
     * Un producto puede tener múltiples reviews (Advanced Ecommerce - Phase 4.3.1)
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(\Modules\Ecommerce\Models\ProductReview::class);
    }

    /**
     * Only approved reviews
     */
    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', 'approved');
    }

    /**
     * Update cached rating and review count
     * Call this method when reviews are added/updated/deleted
     */
    public function updateCachedReviewStats(): void
    {
        $this->average_rating = round($this->approvedReviews()->avg('rating') ?? 0.0, 1);
        $this->total_reviews = $this->approvedReviews()->count();
        $this->saveQuietly();
    }

    /**
     * Scope para búsqueda global en nombre, SKU y descripción
     */
    public function scopeSearch($query, $term)
    {
        $searchTerm = "%{$term}%";
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', $searchTerm)
              ->orWhere('sku', 'like', $searchTerm)
              ->orWhere('description', 'like', $searchTerm);
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Product\Database\Factories\ProductFactory::new();
    }
}
