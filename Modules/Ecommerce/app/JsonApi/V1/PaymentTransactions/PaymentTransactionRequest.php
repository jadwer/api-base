<?php

namespace Modules\Ecommerce\JsonApi\V1\PaymentTransactions;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class PaymentTransactionRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'checkoutSessionId' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'integer',
                Rule::exists('checkout_sessions', 'id'),
            ],
            'transactionId' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                'max:255',
            ],
            'gateway' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                'max:50',
            ],
            'paymentMethod' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                'max:50',
            ],
            'amount' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'numeric',
                'min:0.01',
            ],
            'currency' => [
                'sometimes',
                'string',
                'size:3',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['pending', 'authorized', 'captured', 'failed', 'cancelled', 'refunded']),
            ],
            'metadata' => [
                'sometimes',
                'nullable',
                'array',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'checkoutSessionId.required' => 'Checkout session is required.',
            'amount.min' => 'Payment amount must be greater than zero.',
            'currency.size' => 'Currency code must be 3 characters.',
        ];
    }
}
