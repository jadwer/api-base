<?php

namespace Modules\Ecommerce\JsonApi\V1\CheckoutSessions;

use Illuminate\Validation\Rule;
use Modules\Ecommerce\Models\CheckoutSession;
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
                Rule::in(CheckoutSession::STATUSES),
            ],
            'step' => [
                'sometimes',
                'string',
                Rule::in(CheckoutSession::STEPS),
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
     * P2 granel: no se abre sesion de pago para un carrito con productos de
     * precio sujeto a cotizacion (QuoteOnlyCartGuard).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->isCreating()) {
                return;
            }

            $cartId = $this->validationData()['shoppingCartId'] ?? null;
            $cart = $cartId ? \Modules\Ecommerce\Models\ShoppingCart::find($cartId) : null;
            if (! $cart) {
                return;
            }

            $items = \Modules\Ecommerce\Support\QuoteOnlyCartGuard::quoteOnlyItems($cart);
            if ($items !== []) {
                $validator->errors()->add(
                    'shoppingCartId',
                    \Modules\Ecommerce\Support\QuoteOnlyCartGuard::MESSAGE . ' Productos: ' . \Modules\Ecommerce\Support\QuoteOnlyCartGuard::names($items) . '.'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'shoppingCartId.required' => 'Se necesita un carrito para iniciar el pago.',
            'shoppingCartId.exists' => 'El carrito seleccionado no existe.',
            'shippingMethodId.exists' => 'El método de envío seleccionado no existe.',
            'contactEmail.email' => 'Escribe un correo electrónico válido.',
            'status.in' => 'El estado de la sesión de pago no es válido.',
            'step.in' => 'El paso del pago no es válido.',
            'paymentMethod.in' => 'El método de pago seleccionado no es válido.',
            'shippingAddress.country.max' => 'El código de país debe tener 2 letras (ISO 3166-1, por ejemplo MX).',
            'currency.size' => 'El código de moneda debe tener 3 letras (ISO 4217).',
        ];
    }
}
