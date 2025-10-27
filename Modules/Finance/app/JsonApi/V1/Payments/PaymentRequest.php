<?php

namespace Modules\Finance\JsonApi\V1\Payments;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;
use Modules\Contacts\Models\Contact;

class PaymentRequest extends ResourceRequest
{
    public function rules(): array
    {
        $payment = $this->model();

        return [
            'paymentNumber' => ['nullable', 'string', 'max:255', Rule::unique('payments', 'payment_number')->ignore($payment?->id)],
            'paymentDate' => ['nullable', 'date'],
            'contactId' => [
                'nullable',
                'integer',
                'exists:contacts,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $contact = Contact::find($value);
                        if (!$contact || !$contact->is_customer) {
                            $fail('El contacto debe ser un cliente válido (is_customer = true).');
                        }
                    }
                }
            ],
            'bankAccountId' => ['nullable', 'integer'],
            'paymentMethodId' => ['nullable', 'integer'],
            'amount' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'max:255'],
            'appliedAmount' => ['nullable', 'numeric'],
            'unappliedAmount' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string', 'max:255'],
            'journalEntryId' => ['nullable', 'integer'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'isActive' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'paymentNumber.string' => 'El campo Payment number debe ser texto.',
            'paymentNumber.max' => 'El campo Payment number no puede tener más de 255 caracteres.',
            'paymentNumber.unique' => 'Este Payment number ya está en uso.',
            'paymentDate.date' => 'El campo Payment date debe ser una fecha válida.',
            'contactId.integer' => 'El campo Contact id debe ser un número entero.',
            'bankAccountId.integer' => 'El campo Bank account id debe ser un número entero.',
            'paymentMethodId.integer' => 'El campo Payment method id debe ser un número entero.',
            'amount.numeric' => 'El campo Amount debe ser un número.',
            'currency.string' => 'El campo Currency debe ser texto.',
            'currency.max' => 'El campo Currency no puede tener más de 255 caracteres.',
            'appliedAmount.numeric' => 'El campo Applied amount debe ser un número.',
            'unappliedAmount.numeric' => 'El campo Unapplied amount debe ser un número.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'journalEntryId.integer' => 'El campo Journal entry id debe ser un número entero.',
            'reference.string' => 'El campo Reference debe ser texto.',
            'reference.max' => 'El campo Reference no puede tener más de 255 caracteres.',
            'notes.string' => 'El campo Notes debe ser texto.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
            'isActive.boolean' => 'El campo Is active debe ser verdadero o falso.',
        ];
    }
}
