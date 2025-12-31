<?php

namespace Modules\Finance\JsonApi\V1\ARPayments;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ARPaymentRequest extends ResourceRequest
{
    public function rules(): array
    {
        $isCreating = $this->isCreating();

        return [
            'paymentNumber' => [
                $isCreating ? 'required' : 'sometimes',
                'string',
                'max:50',
            ],
            'paymentDate' => [
                $isCreating ? 'required' : 'sometimes',
                'date',
            ],
            'paymentMethod' => [
                'sometimes',
                'string',
                'in:cash,check,transfer,card',
            ],
            'currency' => [
                'sometimes',
                'string',
                'size:3',
            ],
            'paymentAmount' => [
                $isCreating ? 'required' : 'sometimes',
                'numeric',
                'min:0.01',
            ],
            'status' => [
                'sometimes',
                'string',
                'in:draft,posted,voided',
            ],
            'reference' => [
                'nullable',
                'string',
                'max:100',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            'contact' => [
                $isCreating ? 'required' : 'sometimes',
                JsonApiRule::toOne(),
            ],
            'fiscalPeriod' => [
                $isCreating ? 'required' : 'sometimes',
                JsonApiRule::toOne(),
            ],
            'bankAccount' => [
                'nullable',
                JsonApiRule::toOne(),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors in Spanish.
     */
    public function messages(): array
    {
        return [
            'paymentNumber.required' => 'El número de pago es obligatorio.',
            'paymentNumber.string' => 'El número de pago debe ser texto.',
            'paymentNumber.max' => 'El número de pago no puede exceder 50 caracteres.',
            'paymentDate.required' => 'La fecha de pago es obligatoria.',
            'paymentDate.date' => 'La fecha de pago debe ser una fecha válida.',
            'paymentMethod.in' => 'El método de pago debe ser: cash, check, transfer o card.',
            'currency.size' => 'La moneda debe tener exactamente 3 caracteres.',
            'paymentAmount.required' => 'El monto del pago es obligatorio.',
            'paymentAmount.numeric' => 'El monto del pago debe ser un número.',
            'paymentAmount.min' => 'El monto del pago debe ser mayor a cero.',
            'status.in' => 'El estado debe ser: draft, posted o voided.',
            'contact.required' => 'El cliente es obligatorio.',
            'contact.to_one' => 'El cliente debe ser una relación válida.',
            'fiscalPeriod.required' => 'El período fiscal es obligatorio.',
            'fiscalPeriod.to_one' => 'El período fiscal debe ser una relación válida.',
        ];
    }
}
