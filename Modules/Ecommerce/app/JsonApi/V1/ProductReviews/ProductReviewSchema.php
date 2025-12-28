<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductReviews;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Ecommerce\Models\ProductReview;

class ProductReviewSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = ProductReview::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),

            // Foreign Keys
            Number::make('productId', 'product_id'),
            Number::make('userId', 'user_id'),

            // Review Details
            Number::make('rating')->sortable(), // 1-5 stars
            Str::make('title')->sortable(),
            Str::make('comment'),

            // Verification & Status
            Boolean::make('isVerifiedPurchase', 'is_verified_purchase')->sortable(),
            Number::make('helpfulCount', 'helpful_count')->sortable(),
            Str::make('status')->sortable(),

            // Timestamps
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('product')->type('products'),
            BelongsTo::make('user')->type('users')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('productId', 'product_id'),
            Where::make('userId', 'user_id'),
            Where::make('status'),
            Where::make('rating'),
            Where::make('isVerifiedPurchase', 'is_verified_purchase'),
        ];
    }

    /**
     * Get the resource paginator.
     *
     * @return Paginator|null
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
