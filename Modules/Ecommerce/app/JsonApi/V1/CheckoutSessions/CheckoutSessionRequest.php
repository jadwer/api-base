<?php

namespace Modules\Ecommerce\JsonApi\V1\CheckoutSessions;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class CheckoutSessionRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // Foreign Keys
            'shoppingCartId' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'integer',
                Rule::exists('shopping_carts', 'id'),
            ],
            'shippingMethodId' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('shipping_methods', 'id'),
            ],

            // Session Status
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    'initiated',
                    'address_set',
                    'shipping_selected',
                    'payment_pending',
                    'payment_confirmed',
                    'completed',
                    'cancelled',
                    'expired',
                ]),
            ],
            'step' => [
                'sometimes',
                'string',
                Rule::in(['cart', 'address', 'shipping', 'payment', 'confirmation']),
            ],

            // Contact Information
            'contactEmail' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
            ],
            'contactPhone' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            // Addresses (JSON)
            'billingAddress' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'billingAddress.firstName' => ['sometimes', 'string', 'max:255'],
            'billingAddress.lastName' => ['sometimes', 'string', 'max:255'],
            'billingAddress.addressLine1' => ['sometimes', 'string', 'max:255'],
            'billingAddress.addressLine2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billingAddress.city' => ['sometimes', 'string', 'max:255'],
            'billingAddress.state' => ['sometimes', 'string', 'max:255'],
            'billingAddress.postalCode' => ['sometimes', 'string', 'max:20'],
            'billingAddress.country' => ['sometimes', 'string', 'max:2'], // ISO 3166-1 alpha-2

            'shippingAddress' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'shippingAddress.firstName' => ['sometimes', 'string', 'max:255'],
            'shippingAddress.lastName' => ['sometimes', 'string', 'max:255'],
            'shippingAddress.addressLine1' => ['sometimes', 'string', 'max:255'],
            'shippingAddress.addressLine2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shippingAddress.city' => ['sometimes', 'string', 'max:255'],
            'shippingAddress.state' => ['sometimes', 'string', 'max:255'],
            'shippingAddress.postalCode' => ['sometimes', 'string', 'max:20'],
            'shippingAddress.country' => ['sometimes', 'string', 'max:2'],

            // Payment Information
            'paymentMethod' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(['card', 'bank_transfer', 'paypal', 'cash_on_delivery']),
            ],

            // Amounts (read-only - calculated from cart)
            'subtotalAmount' => ['sometimes', 'numeric', 'min:0'],
            'shippingAmount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'taxAmount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'discountAmount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'totalAmount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'], // ISO 4217

            // Metadata
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'shoppingCartId.required' => 'A shopping cart is required to initiate checkout.',
            'shoppingCartId.exists' => 'The selected shopping cart does not exist.',
            'shippingMethodId.exists' => 'The selected shipping method does not exist.',
            'contactEmail.email' => 'Please provide a valid email address.',
            'status.in' => 'Invalid checkout session status.',
            'step.in' => 'Invalid checkout step.',
            'paymentMethod.in' => 'Invalid payment method selected.',
            'shippingAddress.country.max' => 'Country code must be 2 characters (ISO 3166-1 alpha-2).',
            'currency.size' => 'Currency code must be 3 characters (ISO 4217).',
        ];
    }
}
