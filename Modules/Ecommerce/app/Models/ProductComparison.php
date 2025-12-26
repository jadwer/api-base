<?php

namespace Modules\Ecommerce\Models;

use Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Ecommerce\Database\Factories\ProductComparisonFactory;

class ProductComparison extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'is_public',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'is_public' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($comparison) {
            if (!$comparison->user_id && auth()->check()) {
                $comparison->user_id = auth()->id();
            }
        });
    }

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory()
    {
        return ProductComparisonFactory::new();
    }

    /**
     * Get the user that owns this comparison
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items in this comparison
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductComparisonItem::class, 'comparison_id');
    }

    /**
     * Get the products in this comparison through items
     */
    public function products()
    {
        return $this->hasManyThrough(
            \Modules\Product\Models\Product::class,
            ProductComparisonItem::class,
            'comparison_id', // Foreign key on comparison_items table
            'id',            // Foreign key on products table
            'id',            // Local key on comparisons table
            'product_id'     // Local key on comparison_items table
        );
    }

    /**
     * Scope for public comparisons
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for user's comparisons
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
