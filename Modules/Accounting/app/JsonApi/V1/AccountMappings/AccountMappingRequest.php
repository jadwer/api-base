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
        return [            'mappingType.required' => 'El campo tipo de mapeo es obligatorio.',
            'mappingType.string' => 'El campo tipo de mapeo debe ser texto.',
            'mappingType.max' => 'El campo tipo de mapeo no puede tener más de 255 caracteres.',
            'accountId.required' => 'El campo cuenta es obligatorio.',
            'version.required' => 'El campo versión es obligatorio.',
            'version.integer' => 'El campo versión debe ser un número entero.',
            'effectiveFrom.required' => 'El campo vigente desde es obligatorio.',
            'effectiveFrom.date' => 'El campo vigente desde debe ser una fecha válida.',
            'effectiveTo.date' => 'El campo vigente hasta debe ser una fecha válida.',
            'isActive.required' => 'El campo activo es obligatorio.',
            'isActive.boolean' => 'El campo activo debe ser verdadero o falso.',
            'createdById.required' => 'El campo creado por es obligatorio.',
            'notes.string' => 'El campo notas debe ser texto.',
        ];
    }
}
