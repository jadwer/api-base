<?php

namespace Modules\Accounting\JsonApi\V1\JournalSequences;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class JournalSequenceRequest extends ResourceRequest
{
    public function rules(): array
    {
        $journalsequence = $this->model();
        
        return [
            'journalId' => ['required', 'string'],
            'fiscalYear' => ['required', 'integer'],
            'currentNumber' => ['required', 'integer'],
            'metadata' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'journalId.required' => 'El campo Journal id es obligatorio.',
            'fiscalYear.required' => 'El campo Fiscal year es obligatorio.',
            'fiscalYear.integer' => 'El campo Fiscal year debe ser un número entero.',
            'currentNumber.required' => 'El campo Current number es obligatorio.',
            'currentNumber.integer' => 'El campo Current number debe ser un número entero.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
        ];
    }
}
