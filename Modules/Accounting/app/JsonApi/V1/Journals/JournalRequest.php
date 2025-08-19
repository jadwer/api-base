<?php

namespace Modules\Accounting\JsonApi\V1\Journals;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class JournalRequest extends ResourceRequest
{
    public function rules(): array
    {
        $journal = $this->model();
        
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('journals')->ignore($journal?->id)],
            'name' => ['required', 'string', 'max:255'],
            'auto_numbering' => ['required', 'boolean'],
            'sequence_prefix' => ['nullable', 'string', 'max:255'],
            'sequence_next' => ['required', 'integer'],
            'default_currency' => ['nullable', 'string', 'max:255'],
            'post_policy' => ['required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El campo Code es obligatorio.',
            'code.string' => 'El campo Code debe ser texto.',
            'code.max' => 'El campo Code no puede tener más de 255 caracteres.',
            'code.unique' => 'Este Code ya está en uso.',
            'name.required' => 'El campo Name es obligatorio.',
            'name.string' => 'El campo Name debe ser texto.',
            'name.max' => 'El campo Name no puede tener más de 255 caracteres.',
            'auto_numbering.required' => 'El campo Auto numbering es obligatorio.',
            'auto_numbering.boolean' => 'El campo Auto numbering debe ser verdadero o falso.',
            'sequence_prefix.string' => 'El campo Sequence prefix debe ser texto.',
            'sequence_prefix.max' => 'El campo Sequence prefix no puede tener más de 255 caracteres.',
            'sequence_next.required' => 'El campo Sequence next es obligatorio.',
            'sequence_next.integer' => 'El campo Sequence next debe ser un número entero.',
            'default_currency.string' => 'El campo Default currency debe ser texto.',
            'default_currency.max' => 'El campo Default currency no puede tener más de 255 caracteres.',
            'post_policy.required' => 'El campo Post policy es obligatorio.',
            'post_policy.string' => 'El campo Post policy debe ser texto.',
            'post_policy.max' => 'El campo Post policy no puede tener más de 255 caracteres.',
        ];
    }
}
