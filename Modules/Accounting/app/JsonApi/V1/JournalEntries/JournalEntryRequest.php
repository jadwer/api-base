<?php

namespace Modules\Accounting\JsonApi\V1\JournalEntries;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class JournalEntryRequest extends ResourceRequest
{
    public function rules(): array
    {
        $journalentry = $this->model();
        
        return [
            'journal_id' => ['required', 'string'],
            'period_id' => ['required', 'string'],
            'number' => ['nullable', 'string', 'max:255', Rule::unique('journal_entries')->ignore($journalentry?->id)],
            'date' => ['required', 'date'],
            'currency' => ['nullable', 'string', 'max:255'],
            'exchange_rate' => ['nullable', 'string'],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:255'],
            'approved_by_id' => ['nullable', 'string'],
            'posted_by_id' => ['nullable', 'string'],
            'posted_at' => ['nullable', 'string'],
            'reversal_of_id' => ['nullable', 'string'],
            'source_type' => ['nullable', 'string', 'max:255'],
            'source_id' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'journal_id.required' => 'El campo Journal id es obligatorio.',
            'period_id.required' => 'El campo Period id es obligatorio.',
            'number.string' => 'El campo Number debe ser texto.',
            'number.max' => 'El campo Number no puede tener más de 255 caracteres.',
            'number.unique' => 'Este Number ya está en uso.',
            'date.required' => 'El campo Date es obligatorio.',
            'date.date' => 'El campo Date debe ser una fecha válida.',
            'currency.string' => 'El campo Currency debe ser texto.',
            'currency.max' => 'El campo Currency no puede tener más de 255 caracteres.',
            'reference.string' => 'El campo Reference debe ser texto.',
            'reference.max' => 'El campo Reference no puede tener más de 255 caracteres.',
            'description.string' => 'El campo Description debe ser texto.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'source_type.string' => 'El campo Source type debe ser texto.',
            'source_type.max' => 'El campo Source type no puede tener más de 255 caracteres.',
        ];
    }
}
