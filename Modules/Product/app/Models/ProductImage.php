<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'file_path',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'id' => 'integer',
        'product_id' => 'integer',
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
    ];

    protected $attributes = [
        'sort_order' => 0,
        'is_primary' => false,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get full URL for the image.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->file_path)) {
            return null;
        }

        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        if (str_starts_with($this->file_path, '/images/') || str_starts_with($this->file_path, '/storage/')) {
            return asset($this->file_path);
        }

        if (str_starts_with($this->file_path, 'products/')) {
            return asset('storage/' . $this->file_path);
        }

        return asset('storage/products/' . $this->file_path);
    }

    /**
     * Scope: ordered by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope: primary image only.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    protected static function newFactory()
    {
        return \Modules\Product\Database\Factories\ProductImageFactory::new();
    }
}
