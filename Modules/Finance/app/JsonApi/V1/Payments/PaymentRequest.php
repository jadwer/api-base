<?php

namespace Modules\Finance\JsonApi\V1\Payments;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends ResourceRequest
{
    public function rules(): array
    {
        $payment = $this->model();
        
        return [
            'payment_number' => ['nullable', 'string', 'max:255', Rule::unique('payments')->ignore($payment?->id)],
            'payment_date' => ['nullable', 'date'],
            'customer_id' => ['nullable', 'integer'],
            'bank_account_id' => ['nullable', 'integer'],
            'payment_method_id' => ['nullable', 'integer'],
            'amount' => ['nullable', 'string'],
            'currency' => ['nullable', 'string', 'max:255'],
            'applied_amount' => ['nullable', 'string'],
            'unapplied_amount' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:255'],
            'journal_entry_id' => ['nullable', 'integer'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_number.string' => 'El campo Payment number debe ser texto.',
            'payment_number.max' => 'El campo Payment number no puede tener más de 255 caracteres.',
            'payment_number.unique' => 'Este Payment number ya está en uso.',
            'payment_date.date' => 'El campo Payment date debe ser una fecha válida.',
            'customer_id.integer' => 'El campo Customer id debe ser un número entero.',
            'bank_account_id.integer' => 'El campo Bank account id debe ser un número entero.',
            'payment_method_id.integer' => 'El campo Payment method id debe ser un número entero.',
            'currency.string' => 'El campo Currency debe ser texto.',
            'currency.max' => 'El campo Currency no puede tener más de 255 caracteres.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'journal_entry_id.integer' => 'El campo Journal entry id debe ser un número entero.',
            'reference.string' => 'El campo Reference debe ser texto.',
            'reference.max' => 'El campo Reference no puede tener más de 255 caracteres.',
            'notes.string' => 'El campo Notes debe ser texto.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
            'is_active.boolean' => 'El campo Is active debe ser verdadero o falso.',
        ];
    }
}
