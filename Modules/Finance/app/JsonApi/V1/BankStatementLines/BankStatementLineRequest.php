<?php

namespace Modules\Finance\JsonApi\V1\BankStatementLines;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class BankStatementLineRequest extends ResourceRequest
{
    public function rules(): array
    {
        $bankstatementline = $this->model();
        
        return [
            'bank_statement_id' => ['required', 'string'],
            'txn_date' => ['required', 'date'],
            'amount' => ['required', 'string'],
            'counterparty' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'fitid' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_statement_id.required' => 'El campo Bank statement id es obligatorio.',
            'txn_date.required' => 'El campo Txn date es obligatorio.',
            'txn_date.date' => 'El campo Txn date debe ser una fecha válida.',
            'amount.required' => 'El campo Amount es obligatorio.',
            'counterparty.string' => 'El campo Counterparty debe ser texto.',
            'counterparty.max' => 'El campo Counterparty no puede tener más de 255 caracteres.',
            'reference.string' => 'El campo Reference debe ser texto.',
            'reference.max' => 'El campo Reference no puede tener más de 255 caracteres.',
            'fitid.string' => 'El campo Fitid debe ser texto.',
            'fitid.max' => 'El campo Fitid no puede tener más de 255 caracteres.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
        ];
    }
}
