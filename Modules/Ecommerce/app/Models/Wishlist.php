<?php

namespace Modules\Ecommerce\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Product\Models\Product;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'is_default',
        'is_public',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'is_default' => 'boolean',
        'is_public' => 'boolean',
    ];

    /**
     * User who owns the wishlist
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Items in the wishlist
     */
    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    /**
     * Products in the wishlist (through items)
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlist_items')
            ->withPivot('quantity', 'priority', 'notes')
            ->withTimestamps();
    }

    /**
     * Scope to get user's default wishlist
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to get public wishlists
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Ecommerce\Database\Factories\WishlistFactory::new();
    }
}
