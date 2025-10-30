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
            'paymentGateway' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                Rule::in(['mock', 'stripe', 'paypal']),
            ],
            'paymentMethod' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                Rule::in(['card', 'bank_transfer', 'paypal', 'cash_on_delivery']),
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
            'paymentGateway.in' => 'Invalid payment gateway selected.',
            'paymentMethod.in' => 'Invalid payment method selected.',
            'amount.min' => 'Payment amount must be greater than zero.',
            'currency.size' => 'Currency code must be 3 characters.',
        ];
    }
}
