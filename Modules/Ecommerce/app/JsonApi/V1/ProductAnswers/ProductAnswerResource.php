<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductAnswers;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class ProductAnswerResource extends JsonApiResource
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
            'questionId' => $this->question_id,
            'userId' => $this->user_id,
            'answer' => $this->answer,
            'isVerified' => $this->is_verified,
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
            $this->relation('question'),
            $this->relation('user'),
        ];
    }
}
