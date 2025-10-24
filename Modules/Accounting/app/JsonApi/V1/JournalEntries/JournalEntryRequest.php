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
            'fiscal_period_id' => ['required', 'string'],
            'number' => ['nullable', 'string', 'max:255', Rule::unique('journal_entries')->ignore($journalentry?->id)],
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'total_debit' => ['required', 'string'],
            'total_credit' => ['required', 'string'],
            'company_id' => ['required', 'string'],
            'status' => ['required', 'string', 'max:255'],
            'approved_at' => ['nullable', 'string'],
            'approved_by_id' => ['nullable', 'string'],
            'posted_at' => ['nullable', 'string'],
            'posted_by_id' => ['nullable', 'string'],
            'reversal_of_id' => ['nullable', 'string'],
            'reversal_reason' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'journal_id.required' => 'El campo Journal id es obligatorio.',
            'fiscal_period_id.required' => 'El campo Fiscal period id es obligatorio.',
            'number.string' => 'El campo Number debe ser texto.',
            'number.max' => 'El campo Number no puede tener más de 255 caracteres.',
            'number.unique' => 'Este Number ya está en uso.',
            'date.required' => 'El campo Date es obligatorio.',
            'date.date' => 'El campo Date debe ser una fecha válida.',
            'reference.string' => 'El campo Reference debe ser texto.',
            'reference.max' => 'El campo Reference no puede tener más de 255 caracteres.',
            'description.string' => 'El campo Description debe ser texto.',
            'total_debit.required' => 'El campo Total debit es obligatorio.',
            'total_credit.required' => 'El campo Total credit es obligatorio.',
            'company_id.required' => 'El campo Company id es obligatorio.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'reversal_reason.string' => 'El campo Reversal reason debe ser texto.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
        ];
    }
}
