<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;

class WishlistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'wishlist_id',
        'product_id',
        'quantity',
        'priority',
        'notes',
    ];

    protected $casts = [
        'wishlist_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
    ];

    /**
     * Wishlist that contains this item
     */
    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class);
    }

    /**
     * Product in the wishlist
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to filter by priority
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to get high priority items
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    /**
     * Scope to get medium priority items
     */
    public function scopeMediumPriority($query)
    {
        return $query->where('priority', 'medium');
    }

    /**
     * Scope to get low priority items
     */
    public function scopeLowPriority($query)
    {
        return $query->where('priority', 'low');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Ecommerce\Database\Factories\WishlistItemFactory::new();
    }
}
