<?php

namespace Modules\Accounting\JsonApi\V1\JournalSequences;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class JournalSequenceRequest extends ResourceRequest
{
    public function rules(): array
    {
        $journalsequence = $this->model();
        $isUpdate = $journalsequence && $journalsequence->exists;

        
        return [
            'journalId' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'fiscalYear' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'currentNumber' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'metadata' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'journalId.required' => 'El campo diario es obligatorio.',
            'fiscalYear.required' => 'El campo ejercicio fiscal es obligatorio.',
            'fiscalYear.integer' => 'El campo ejercicio fiscal debe ser un número entero.',
            'currentNumber.required' => 'El campo número actual es obligatorio.',
            'currentNumber.integer' => 'El campo número actual debe ser un número entero.',
            'metadata.array' => 'El campo metadatos debe ser un arreglo.',
        ];
    }
}
