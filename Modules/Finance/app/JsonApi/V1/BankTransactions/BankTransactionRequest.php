<?php

namespace Modules\Finance\JsonApi\V1\BankTransactions;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class BankTransactionRequest extends ResourceRequest
{
    public function rules(): array
    {
        $transaction = $this->model();

        return [
            'bankAccountId' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'integer',
                'exists:bank_accounts,id',
            ],
            'transactionDate' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'date',
            ],
            'amount' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'numeric',
            ],
            'transactionType' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                Rule::in(['debit', 'credit']),
            ],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'reconciliationStatus' => [
                'nullable',
                'string',
                Rule::in(['unreconciled', 'reconciled', 'pending']),
            ],
            'reconciledById' => ['nullable', 'integer', 'exists:users,id'],
            'reconciledAt' => ['nullable', 'date'],
            'reconciliationNotes' => ['nullable', 'string'],
            'statementNumber' => ['nullable', 'string', 'max:255'],
            'runningBalance' => ['nullable', 'numeric'],
            'isActive' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'bankAccountId.required' => 'La cuenta bancaria es obligatoria.',
            'bankAccountId.exists' => 'La cuenta bancaria seleccionada no existe.',
            'transactionDate.required' => 'La fecha de transacción es obligatoria.',
            'transactionDate.date' => 'La fecha de transacción debe ser una fecha valida.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número.',
            'transactionType.required' => 'El tipo de transacción es obligatorio.',
            'transactionType.in' => 'El tipo de transacción debe ser debit o credit.',
            'reconciliationStatus.in' => 'El estado de conciliación debe ser unreconciled, reconciled o pending.',
            'reconciledById.exists' => 'El usuario de conciliación no existe.',
        ];
    }
}
