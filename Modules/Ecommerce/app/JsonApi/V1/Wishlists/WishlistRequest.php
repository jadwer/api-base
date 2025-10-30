<?php

namespace Modules\Ecommerce\JsonApi\V1\Wishlists;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class WishlistRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // Wishlist Details
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'isDefault' => [
                'sometimes',
                'boolean',
            ],
            'isPublic' => [
                'sometimes',
                'boolean',
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
            'name.string' => 'El nombre debe ser un texto válido.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'isDefault.boolean' => 'El campo predeterminado debe ser verdadero o falso.',
            'isPublic.boolean' => 'El campo público debe ser verdadero o falso.',
        ];
    }
}
