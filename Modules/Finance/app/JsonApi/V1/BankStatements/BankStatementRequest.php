<?php

namespace Modules\Finance\JsonApi\V1\BankStatements;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class BankStatementRequest extends ResourceRequest
{
    public function rules(): array
    {
        $bankstatement = $this->model();
        
        return [
            'bank_account_id' => ['required', 'string'],
            'statement_date' => ['required', 'date'],
            'import_source' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_account_id.required' => 'El campo Bank account id es obligatorio.',
            'statement_date.required' => 'El campo Statement date es obligatorio.',
            'statement_date.date' => 'El campo Statement date debe ser una fecha válida.',
            'import_source.string' => 'El campo Import source debe ser texto.',
            'import_source.max' => 'El campo Import source no puede tener más de 255 caracteres.',
        ];
    }
}
