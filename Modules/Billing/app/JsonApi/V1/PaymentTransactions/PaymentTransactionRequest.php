<?php

namespace Modules\Billing\JsonApi\V1\PaymentTransactions;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class PaymentTransactionRequest extends ResourceRequest
{
    public function rules(): array
    {
        $creating = $this->isMethod('POST');
        $transactionId = $this->route('payment_transaction');

        return [
            'gateway' => [
                $creating ? 'required' : 'sometimes',
                'string',
                'max:50',
                'in:stripe,conekta,paypal',
            ],
            'paymentIntentId' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('payment_transactions', 'payment_intent_id')->ignore($transactionId),
            ],
            'transactionId' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('payment_transactions', 'transaction_id')->ignore($transactionId),
            ],
            'clientSecret' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'amount' => [
                $creating ? 'required' : 'sometimes',
                'numeric',
                'min:0',
                'max:9999999.99',
            ],
            'currency' => [
                'sometimes',
                'string',
                'size:3',
                'in:MXN,USD,EUR,GBP,JPY,CAD,AUD,CHF,CNY,BRL',
            ],
            'status' => [
                'sometimes',
                'string',
                'max:50',
                'in:pending,authorized,captured,failed,refunded,cancelled',
            ],
            'paymentMethod' => [
                'sometimes',
                'string',
                'max:50',
            ],
            'cardBrand' => [
                'sometimes',
                'string',
                'max:50',
            ],
            'cardLast4' => [
                'sometimes',
                'string',
                'size:4',
                'regex:/^\d{4}$/',
            ],
            'gatewayResponse' => [
                'sometimes',
                'array',
            ],
            'errorMessage' => [
                'sometimes',
                'string',
            ],
            'authorizedAt' => [
                'sometimes',
                'date',
            ],
            'capturedAt' => [
                'sometimes',
                'date',
            ],
            'failedAt' => [
                'sometimes',
                'date',
            ],
            'refundedAt' => [
                'sometimes',
                'date',
            ],
            'metadata' => [
                'sometimes',
                'array',
            ],
            // Relationships are set via foreign key IDs (checkoutSessionId, salesOrderId, arInvoiceId)
            // No need for relationship validation rules here
        ];
    }

    /**
     * MANDATORY: Get custom messages in Spanish.
     */
    public function messages(): array
    {
        return [
            'gateway.string' => 'La pasarela debe ser un texto.',
            'gateway.max' => 'La pasarela no puede exceder 50 caracteres.',
            'gateway.in' => 'La pasarela debe ser: stripe, conekta o paypal.',
            'paymentIntentId.string' => 'El ID de intención de pago debe ser un texto.',
            'paymentIntentId.max' => 'El ID de intención de pago no puede exceder 255 caracteres.',
            'paymentIntentId.unique' => 'Ya existe una transacción con este ID de intención de pago.',
            'transactionId.string' => 'El ID de transacción debe ser un texto.',
            'transactionId.max' => 'El ID de transacción no puede exceder 255 caracteres.',
            'transactionId.unique' => 'Ya existe una transacción con este ID.',
            'clientSecret.string' => 'El client secret debe ser un texto.',
            'clientSecret.max' => 'El client secret no puede exceder 255 caracteres.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número.',
            'amount.min' => 'El monto debe ser mayor o igual a 0.',
            'amount.max' => 'El monto no puede exceder 9,999,999.99.',
            'currency.string' => 'La moneda debe ser un texto.',
            'currency.size' => 'La moneda debe tener exactamente 3 caracteres.',
            'currency.in' => 'La moneda debe ser una de las siguientes: MXN, USD, EUR, GBP, JPY, CAD, AUD, CHF, CNY, BRL.',
            'status.string' => 'El estado debe ser un texto.',
            'status.max' => 'El estado no puede exceder 50 caracteres.',
            'status.in' => 'El estado debe ser: pending, authorized, captured, failed, refunded o cancelled.',
            'paymentMethod.string' => 'El método de pago debe ser un texto.',
            'paymentMethod.max' => 'El método de pago no puede exceder 50 caracteres.',
            'cardBrand.string' => 'La marca de tarjeta debe ser un texto.',
            'cardBrand.max' => 'La marca de tarjeta no puede exceder 50 caracteres.',
            'cardLast4.string' => 'Los últimos 4 dígitos deben ser un texto.',
            'cardLast4.size' => 'Los últimos 4 dígitos deben tener exactamente 4 caracteres.',
            'cardLast4.regex' => 'Los últimos 4 dígitos deben ser numéricos.',
            'gatewayResponse.array' => 'La respuesta de la pasarela debe ser un objeto JSON.',
            'errorMessage.string' => 'El mensaje de error debe ser un texto.',
            'authorizedAt.date' => 'La fecha de autorización debe ser una fecha válida.',
            'capturedAt.date' => 'La fecha de captura debe ser una fecha válida.',
            'failedAt.date' => 'La fecha de fallo debe ser una fecha válida.',
            'refundedAt.date' => 'La fecha de reembolso debe ser una fecha válida.',
            'metadata.array' => 'Los metadatos deben ser un objeto JSON.',
            'checkoutSession.to_one' => 'La sesión de checkout debe ser una relación válida.',
            'salesOrder.to_one' => 'La orden de venta debe ser una relación válida.',
            'arInvoice.to_one' => 'La factura AR debe ser una relación válida.',
        ];
    }
}
