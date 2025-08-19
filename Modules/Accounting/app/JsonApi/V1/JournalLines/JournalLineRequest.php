<?php

namespace Modules\Accounting\JsonApi\V1\JournalLines;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class JournalLineRequest extends ResourceRequest
{
    public function rules(): array
    {
        $journalline = $this->model();
        
        return [
            'journal_entry_id' => ['required', 'string'],
            'account_id' => ['required', 'string'],
            'debit' => ['required', 'string'],
            'credit' => ['required', 'string'],
            'base_amount' => ['nullable', 'string'],
            'cost_center_id' => ['nullable', 'string'],
            'partner_id' => ['nullable', 'string'],
            'memo' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'journal_entry_id.required' => 'El campo Journal entry id es obligatorio.',
            'account_id.required' => 'El campo Account id es obligatorio.',
            'debit.required' => 'El campo Debit es obligatorio.',
            'credit.required' => 'El campo Credit es obligatorio.',
            'memo.string' => 'El campo Memo debe ser texto.',
            'memo.max' => 'El campo Memo no puede tener más de 255 caracteres.',
        ];
    }
}
