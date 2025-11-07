<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CheckoutSession;

class CheckoutSessionStoreTest extends TestCase
{
    public function test_admin_can_create_checkout_session(): void
    {
        $admin = $this->getAdminUser();
        $cart = ShoppingCart::factory()->create();

        $data = [
            'type' => 'checkout-sessions',
            'attributes' => [
                'shoppingCartId' => $cart->id,
                'status' => 'initiated',
                'step' => 'address',
                'contactEmail' => 'test@example.com',
                'contactPhone' => '1234567890',
                'subtotalAmount' => 100.00,
                'shippingAmount' => 10.00,
                'taxAmount' => 16.00,
                'discountAmount' => 0.00,
                'totalAmount' => 126.00,
                'currency' => 'MXN',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->withData($data)
            ->post('/api/v1/checkout-sessions');

        $response->assertCreated();
        $this->assertDatabaseHas('checkout_sessions', [
            'shoppingCartId' => $cart->id,
            'status' => 'initiated',
            'totalAmount' => 126.00,
        ]);
    }

    public function test_admin_can_create_checkout_session_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();
        $cart = ShoppingCart::factory()->create();

        $data = [
            'type' => 'checkout-sessions',
            'attributes' => [
                'shoppingCartId' => $cart->id,
                'status' => 'initiated',
                'step' => 'address',
                'subtotalAmount' => 100.00,
                'totalAmount' => 100.00,
                'currency' => 'MXN',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->withData($data)
            ->post('/api/v1/checkout-sessions');

        $response->assertCreated();
    }

    public function test_customer_user_can_create_checkout_session(): void
    {
        $customer = $this->getCustomerUser();
        $cart = ShoppingCart::factory()->create(['userId' => $customer->id]);

        $data = [
            'type' => 'checkout-sessions',
            'attributes' => [
                'shoppingCartId' => $cart->id,
                'status' => 'initiated',
                'step' => 'address',
                'subtotalAmount' => 200.00,
                'totalAmount' => 200.00,
                'currency' => 'MXN',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->withData($data)
            ->post('/api/v1/checkout-sessions');

        $response->assertCreated();
        $this->assertDatabaseHas('checkout_sessions', [
            'shoppingCartId' => $cart->id,
            'totalAmount' => 200.00,
        ]);
    }

    public function test_guest_cannot_create_checkout_session(): void
    {
        $cart = ShoppingCart::factory()->create();

        $data = [
            'type' => 'checkout-sessions',
            'attributes' => [
                'shoppingCartId' => $cart->id,
                'status' => 'initiated',
                'totalAmount' => 100.00,
                'currency' => 'MXN',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('checkout-sessions')
            ->withData($data)
            ->post('/api/v1/checkout-sessions');

        $response->assertStatus(401);
    }

    public function test_cannot_create_checkout_session_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'checkout-sessions',
            'attributes' => [
                'status' => 'initiated',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->withData($data)
            ->post('/api/v1/checkout-sessions');

        $response->assertStatus(422);
    }

    public function test_cannot_create_checkout_session_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'checkout-sessions',
            'attributes' => [
                'shoppingCartId' => 'invalid',
                'status' => 'invalid_status',
                'totalAmount' => 'not_a_number',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('checkout-sessions')
            ->withData($data)
            ->post('/api/v1/checkout-sessions');

        $response->assertStatus(422);
    }
}
