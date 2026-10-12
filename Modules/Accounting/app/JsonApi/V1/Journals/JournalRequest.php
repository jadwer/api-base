<?php

namespace Modules\Accounting\JsonApi\V1\Journals;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class JournalRequest extends ResourceRequest
{
    public function rules(): array
    {
        $journal = $this->model();
        $isUpdate = $journal && $journal->exists;

        
        return [
            'code' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255', Rule::unique('journals')->ignore($journal?->id)],
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'prefix' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'type' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'status' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El campo clave es obligatorio.',
            'code.string' => 'El campo clave debe ser texto.',
            'code.max' => 'El campo clave no puede tener más de 255 caracteres.',
            'code.unique' => 'Este Code ya está en uso.',
            'name.required' => 'El campo nombre es obligatorio.',
            'name.string' => 'El campo nombre debe ser texto.',
            'name.max' => 'El campo nombre no puede tener más de 255 caracteres.',
            'description.string' => 'El campo descripción debe ser texto.',
            'prefix.required' => 'El campo prefijo es obligatorio.',
            'prefix.string' => 'El campo prefijo debe ser texto.',
            'prefix.max' => 'El campo prefijo no puede tener más de 255 caracteres.',
            'type.required' => 'El campo tipo es obligatorio.',
            'type.string' => 'El campo tipo debe ser texto.',
            'type.max' => 'El campo tipo no puede tener más de 255 caracteres.',
            'status.required' => 'El campo estado es obligatorio.',
            'status.string' => 'El campo estado debe ser texto.',
            'status.max' => 'El campo estado no puede tener más de 255 caracteres.',
            'metadata.array' => 'El campo metadatos debe ser un arreglo.',
        ];
    }
}
