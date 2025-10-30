<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductReviews;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class ProductReviewResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     * @return array
     */
    public function attributes($request): array
    {
        return [
            'productId' => $this->product_id,
            'userId' => $this->user_id,
            'rating' => $this->rating,
            'title' => $this->title,
            'comment' => $this->comment,
            'isVerifiedPurchase' => $this->is_verified_purchase,
            'helpfulCount' => $this->helpful_count,
            'status' => $this->status,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     * @return array
     */
    public function relationships($request): array
    {
        return [
            $this->relation('product'),
            $this->relation('user'),
        ];
    }
}
