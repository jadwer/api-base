<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductQuestions;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ProductQuestionRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'min:10', 'max:1000'],
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected'])],
            'product' => JsonApiRule::toOne(),
            'user' => JsonApiRule::toOne(),
            'answers' => JsonApiRule::toMany(),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'question.required' => 'La pregunta es obligatoria.',
            'question.min' => 'La pregunta debe tener al menos 10 caracteres.',
            'question.max' => 'La pregunta no puede exceder 1000 caracteres.',
            'status.in' => 'El estado debe ser: pending, approved o rejected.',
        ];
    }
}
