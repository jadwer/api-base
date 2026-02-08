<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductReviews;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ProductReviewRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // Relationship
            'product' => [
                $this->isCreating() ? 'required' : 'sometimes',
                JsonApiRule::toOne(),
            ],

            // Review Details
            'rating' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'integer',
                'min:1',
                'max:5',
            ],
            'title' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                'max:255',
            ],
            'comment' => [
                'nullable',
                'string',
            ],

            // Verification & Status (admin-only, excluded from customer requests)
            // These fields are managed server-side; not accepted via API
        ];
    }

    /**
     * Get the validation messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'product.required' => 'El producto es obligatorio.',
            'rating.required' => 'La calificación es obligatoria.',
            'rating.integer' => 'La calificación debe ser un número.',
            'rating.min' => 'La calificación mínima es 1 estrella.',
            'rating.max' => 'La calificación máxima es 5 estrellas.',
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede exceder 255 caracteres.',
            'status.in' => 'El estado debe ser: pending, approved o rejected.',
        ];
    }
}
