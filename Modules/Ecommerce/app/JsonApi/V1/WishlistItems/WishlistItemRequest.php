<?php

namespace Modules\Ecommerce\JsonApi\V1\WishlistItems;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class WishlistItemRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // Relationships (JSON:API format)
            'wishlist' => [
                $this->isCreating() ? 'required' : 'sometimes',
                JsonApiRule::toOne(),
            ],
            'product' => [
                $this->isCreating() ? 'required' : 'sometimes',
                JsonApiRule::toOne(),
            ],

            // Item Details
            'quantity' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'priority' => [
                'sometimes',
                'string',
                Rule::in(['low', 'medium', 'high']),
            ],
            'notes' => [
                'nullable',
                'string',
            ],
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
            'wishlist.required' => 'La lista de deseos es obligatoria.',
            'product.required' => 'El producto es obligatorio.',
            'quantity.integer' => 'La cantidad debe ser un número.',
            'quantity.min' => 'La cantidad mínima es 1.',
            'priority.in' => 'La prioridad debe ser: low, medium o high.',
            'notes.string' => 'Las notas deben ser texto válido.',
        ];
    }
}
