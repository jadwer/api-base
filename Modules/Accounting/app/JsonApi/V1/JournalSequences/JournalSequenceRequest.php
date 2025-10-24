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
            'journal_id' => ['required', 'string'],
            'fiscal_year' => ['required', 'integer'],
            'current_number' => ['required', 'integer'],
            'metadata' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'journal_id.required' => 'El campo Journal id es obligatorio.',
            'fiscal_year.required' => 'El campo Fiscal year es obligatorio.',
            'fiscal_year.integer' => 'El campo Fiscal year debe ser un número entero.',
            'current_number.required' => 'El campo Current number es obligatorio.',
            'current_number.integer' => 'El campo Current number debe ser un número entero.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
        ];
    }
}
