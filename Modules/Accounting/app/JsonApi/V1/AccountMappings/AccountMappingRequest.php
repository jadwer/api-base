<?php

namespace Modules\Accounting\JsonApi\V1\AccountMappings;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class AccountMappingRequest extends ResourceRequest
{
    public function rules(): array
    {
        $accountmapping = $this->model();
        
        return [
            'company_id' => ['required', 'string'],
            'mapping_type' => ['required', 'string', 'max:255'],
            'account_id' => ['required', 'string'],
            'version' => ['required', 'integer'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
            'created_by_id' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'El campo Company id es obligatorio.',
            'mapping_type.required' => 'El campo Mapping type es obligatorio.',
            'mapping_type.string' => 'El campo Mapping type debe ser texto.',
            'mapping_type.max' => 'El campo Mapping type no puede tener más de 255 caracteres.',
            'account_id.required' => 'El campo Account id es obligatorio.',
            'version.required' => 'El campo Version es obligatorio.',
            'version.integer' => 'El campo Version debe ser un número entero.',
            'effective_from.required' => 'El campo Effective from es obligatorio.',
            'effective_from.date' => 'El campo Effective from debe ser una fecha válida.',
            'effective_to.date' => 'El campo Effective to debe ser una fecha válida.',
            'is_active.required' => 'El campo Is active es obligatorio.',
            'is_active.boolean' => 'El campo Is active debe ser verdadero o falso.',
            'created_by_id.required' => 'El campo Created by id es obligatorio.',
            'notes.string' => 'El campo Notes debe ser texto.',
        ];
    }
}
