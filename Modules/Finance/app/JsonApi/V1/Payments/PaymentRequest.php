<?php

namespace Modules\Finance\JsonApi\V1\Payments;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Modules\Finance\JsonApi\Concerns\ValidatesFinancialImmutability;
use Illuminate\Validation\Rule;
use Modules\Contacts\Models\Contact;

class PaymentRequest extends ResourceRequest
{
    use ValidatesFinancialImmutability;

    protected function immutableStatuses(): array
    {
        return ['applied', 'fully_applied', 'partial', 'void', 'voided'];
    }
    public function rules(): array
    {
        $payment = $this->model();
        // Columnas NOT NULL: requeridas al crear para responder 422 y no 500
        $required = $this->isCreating() ? 'required' : 'sometimes';

        return [
            // Opcional al crear: el modelo genera PAY-000001 si no viene
            'paymentNumber' => ['sometimes', 'string', 'max:255', Rule::unique('payments', 'payment_number')->ignore($payment?->id)],
            'paymentDate' => [$required, 'date'],
            'contactId' => [
                $required,
                'integer',
                'exists:contacts,id',
                // Un pago puede ser cobro a cliente (AR) o pago a proveedor (AP)
                function ($attribute, $value, $fail) {
                    $contact = Contact::whereKey($value)->first();
                    if ($contact && ! $contact->is_customer && ! $contact->is_supplier) {
                        $fail('El contacto debe ser cliente o proveedor.');
                    }
                },
            ],
            'bankAccountId' => [$required, 'integer', 'exists:bank_accounts,id'],
            'paymentMethodId' => [$required, 'integer', 'exists:payment_methods,id'],
            'amount' => [$required, 'numeric'],
            'currency' => ['sometimes', 'string', 'max:255'],
            'appliedAmount' => ['nullable', 'numeric'],
            'unappliedAmount' => ['nullable', 'numeric'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'unapplied', 'partial', 'applied', 'fully_applied', 'void', 'voided'])],
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
            'paymentDate.required' => 'La fecha de pago es obligatoria.',
            'contactId.required' => 'El contacto es obligatorio.',
            'contactId.exists' => 'El contacto no existe.',
            'bankAccountId.required' => 'La cuenta bancaria es obligatoria.',
            'bankAccountId.exists' => 'La cuenta bancaria no existe.',
            'paymentMethodId.required' => 'El metodo de pago es obligatorio.',
            'paymentMethodId.exists' => 'El metodo de pago no existe.',
            'amount.required' => 'El monto es obligatorio.',
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
            'status.in' => 'El estado del pago no es valido.',
            'journalEntryId.integer' => 'El campo Journal entry id debe ser un número entero.',
            'reference.string' => 'El campo Reference debe ser texto.',
            'reference.max' => 'El campo Reference no puede tener más de 255 caracteres.',
            'notes.string' => 'El campo Notes debe ser texto.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
            'isActive.boolean' => 'El campo Is active debe ser verdadero o falso.',
        ];
    }
}
