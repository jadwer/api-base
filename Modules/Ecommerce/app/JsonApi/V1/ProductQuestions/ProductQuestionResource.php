<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductQuestions;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class ProductQuestionResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function attributes($request): iterable
    {
        return [
            'productId' => $this->product_id,
            'userId' => $this->user_id,
            'question' => $this->question,
            'status' => $this->status,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function relationships($request): iterable
    {
        return [
            $this->relation('product'),
            $this->relation('user'),
            $this->relation('answers'),
        ];
    }
}
