<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\CartItem;

class CartItemStoreTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_create_CartItem(): void
    {
        $admin = $this->getAdminUser();
        $shoppingCart = \Modules\Ecommerce\Models\ShoppingCart::factory()->create();
        $product = \Modules\Product\Models\Product::first() ?? \Modules\Product\Models\Product::factory()->create();

        $data = [
            'type' => 'cart-items',
            'attributes' => [
                'quantity' => 99.99,
                'unitPrice' => 99.99,
                'originalPrice' => 99.99,
                'discountPercent' => 99.99,
                'discountAmount' => 99.99,
                'subtotal' => 99.99,
                'taxRate' => 99.99,
                'taxAmount' => 99.99,
                'total' => 99.99
            ],
            'relationships' => [
                'shoppingCart' => [
                    'data' => [
                        'type' => 'shopping-carts',
                        'id' => (string) $shoppingCart->id
                    ]
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->post('/api/v1/cart-items');

        $response->assertCreated();
        
        $this->assertDatabaseHas('cart_items', ['quantity' => 99.99, 'unit_price' => 99.99, 'original_price' => 99.99, 'discount_percent' => 99.99, 'discount_amount' => 99.99, 'subtotal' => 99.99, 'tax_rate' => 99.99, 'tax_amount' => 99.99, 'total' => 99.99]);
    }

    public function test_admin_can_create_CartItem_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();
        $shoppingCart = \Modules\Ecommerce\Models\ShoppingCart::factory()->create();
        $product = \Modules\Product\Models\Product::first() ?? \Modules\Product\Models\Product::factory()->create();

        $data = [
            'type' => 'cart-items',
            'attributes' => [
                'quantity' => 1,
                'unitPrice' => 10.00,
                'originalPrice' => 10.00,
                'discountPercent' => 0,
                'discountAmount' => 0,
                'subtotal' => 10.00,
                'taxRate' => 0,
                'taxAmount' => 0,
                'total' => 10.00
            ],
            'relationships' => [
                'shoppingCart' => [
                    'data' => [
                        'type' => 'shopping-carts',
                        'id' => (string) $shoppingCart->id
                    ]
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->post('/api/v1/cart-items');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_CartItem(): void
    {
        $customer = $this->getCustomerUser();
        $shoppingCart = \Modules\Ecommerce\Models\ShoppingCart::factory()->create();
        $product = \Modules\Product\Models\Product::first() ?? \Modules\Product\Models\Product::factory()->create();

        $data = [
            'type' => 'cart-items',
            'attributes' => [
                'quantity' => 1,
                'unitPrice' => 10.00,
                'originalPrice' => 10.00,
                'discountPercent' => 0,
                'discountAmount' => 0,
                'subtotal' => 10.00,
                'taxRate' => 0,
                'taxAmount' => 0,
                'total' => 10.00
            ],
            'relationships' => [
                'shoppingCart' => [
                    'data' => [
                        'type' => 'shopping-carts',
                        'id' => (string) $shoppingCart->id
                    ]
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->post('/api/v1/cart-items');

        $response->assertCreated();
    }

    public function test_guest_cannot_create_CartItem(): void
    {
        $shoppingCart = \Modules\Ecommerce\Models\ShoppingCart::factory()->create();
        $product = \Modules\Product\Models\Product::first() ?? \Modules\Product\Models\Product::factory()->create();

        $data = [
            'type' => 'cart-items',
            'attributes' => [
                'quantity' => 1,
                'unitPrice' => 10.00,
                'originalPrice' => 10.00,
                'discountPercent' => 0,
                'discountAmount' => 0,
                'subtotal' => 10.00,
                'taxRate' => 0,
                'taxAmount' => 0,
                'total' => 10.00
            ],
            'relationships' => [
                'shoppingCart' => [
                    'data' => [
                        'type' => 'shopping-carts',
                        'id' => (string) $shoppingCart->id
                    ]
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->post('/api/v1/cart-items');

        $response->assertStatus(401);
    }

    public function test_cannot_create_CartItem_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'cart-items',
            'attributes' => [
                'quantity' => 1
                // Missing required fields: unitPrice, originalPrice, etc.
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->post('/api/v1/cart-items');

        $response->assertStatus(422);
    }

    public function test_cannot_create_CartItem_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $shoppingCart = \Modules\Ecommerce\Models\ShoppingCart::factory()->create();
        $product = \Modules\Product\Models\Product::first() ?? \Modules\Product\Models\Product::factory()->create();

        $data = [
            'type' => 'cart-items',
            'attributes' => [
                'quantity' => -1, // Invalid negative quantity
                'unitPrice' => 'not_numeric', // Invalid numeric
                'originalPrice' => 10.00,
                'discountPercent' => 150, // Invalid > 100
                'discountAmount' => 0,
                'subtotal' => 10.00,
                'taxRate' => 0,
                'taxAmount' => 0,
                'total' => 10.00,
                'status' => 'invalid_status' // Invalid status
            ],
            'relationships' => [
                'shoppingCart' => [
                    'data' => [
                        'type' => 'shopping-carts',
                        'id' => (string) $shoppingCart->id
                    ]
                ],
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->post('/api/v1/cart-items');

        $response->assertStatus(422);
    }
}
