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
            'code.required' => 'El campo Code es obligatorio.',
            'code.string' => 'El campo Code debe ser texto.',
            'code.max' => 'El campo Code no puede tener más de 255 caracteres.',
            'code.unique' => 'Este Code ya está en uso.',
            'name.required' => 'El campo Name es obligatorio.',
            'name.string' => 'El campo Name debe ser texto.',
            'name.max' => 'El campo Name no puede tener más de 255 caracteres.',
            'description.string' => 'El campo Description debe ser texto.',
            'prefix.required' => 'El campo Prefix es obligatorio.',
            'prefix.string' => 'El campo Prefix debe ser texto.',
            'prefix.max' => 'El campo Prefix no puede tener más de 255 caracteres.',
            'type.required' => 'El campo Type es obligatorio.',
            'type.string' => 'El campo Type debe ser texto.',
            'type.max' => 'El campo Type no puede tener más de 255 caracteres.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
        ];
    }
}
