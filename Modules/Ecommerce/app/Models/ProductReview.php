<?php

namespace Modules\Ecommerce\Models;

use Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProductReview extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['rating', 'title', 'status', 'is_verified_purchase', 'helpful_count'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'title',
        'comment',
        'is_verified_purchase',
        'helpful_count',
        'status',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'user_id' => 'integer',
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'helpful_count' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($review) {
            if (!$review->user_id && auth('sanctum')->check()) {
                $review->user_id = auth('sanctum')->id();
            }
            if (!$review->status) {
                $review->status = 'pending';
            }
        });
    }

    /**
     * Product being reviewed
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * User who wrote the review
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only approved reviews
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get only verified purchase reviews
     */
    public function scopeVerifiedPurchase($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope to filter by rating
     */
    public function scopeRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Ecommerce\Database\Factories\ProductReviewFactory::new();
    }
}
