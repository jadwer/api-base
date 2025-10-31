<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductAnswers;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ProductAnswerRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        return [
            'answer' => ['required', 'string', 'min:10', 'max:1000'],
            'isVerified' => ['sometimes', 'boolean'],
            'question' => JsonApiRule::toOne(),
            'user' => JsonApiRule::toOne(),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'answer.required' => 'La respuesta es obligatoria.',
            'answer.min' => 'La respuesta debe tener al menos 10 caracteres.',
            'answer.max' => 'La respuesta no puede exceder 1000 caracteres.',
            'isVerified.boolean' => 'El campo verificado debe ser verdadero o falso.',
        ];
    }
}
