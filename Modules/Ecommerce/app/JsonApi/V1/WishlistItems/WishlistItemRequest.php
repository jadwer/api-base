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
            // Foreign Keys
            'wishlistId' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'integer',
                JsonApiRule::toOne(),
            ],
            'productId' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'integer',
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
            'wishlistId.required' => 'La lista de deseos es obligatoria.',
            'wishlistId.integer' => 'La lista de deseos debe ser un número válido.',
            'productId.required' => 'El producto es obligatorio.',
            'productId.integer' => 'El producto debe ser un número válido.',
            'quantity.integer' => 'La cantidad debe ser un número.',
            'quantity.min' => 'La cantidad mínima es 1.',
            'priority.in' => 'La prioridad debe ser: low, medium o high.',
            'notes.string' => 'Las notas deben ser texto válido.',
        ];
    }
}
