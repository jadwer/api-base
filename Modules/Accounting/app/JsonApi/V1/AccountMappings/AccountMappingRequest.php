<?php

namespace Modules\Accounting\JsonApi\V1\AccountMappings;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class AccountMappingRequest extends ResourceRequest
{
    public function rules(): array
    {
        $accountmapping = $this->model();
        $isUpdate = $accountmapping && $accountmapping->exists;

        
        return [            'mappingType' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'accountId' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'version' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'effectiveFrom' => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'effectiveTo' => ['nullable', 'date'],
            'isActive' => [$isUpdate ? 'sometimes' : 'required', 'boolean'],
            'createdById' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [            'mappingType.required' => 'El campo Mapping type es obligatorio.',
            'mappingType.string' => 'El campo Mapping type debe ser texto.',
            'mappingType.max' => 'El campo Mapping type no puede tener más de 255 caracteres.',
            'accountId.required' => 'El campo Account id es obligatorio.',
            'version.required' => 'El campo Version es obligatorio.',
            'version.integer' => 'El campo Version debe ser un número entero.',
            'effectiveFrom.required' => 'El campo Effective from es obligatorio.',
            'effectiveFrom.date' => 'El campo Effective from debe ser una fecha válida.',
            'effectiveTo.date' => 'El campo Effective to debe ser una fecha válida.',
            'isActive.required' => 'El campo Is active es obligatorio.',
            'isActive.boolean' => 'El campo Is active debe ser verdadero o falso.',
            'createdById.required' => 'El campo Created by id es obligatorio.',
            'notes.string' => 'El campo Notes debe ser texto.',
        ];
    }
}
