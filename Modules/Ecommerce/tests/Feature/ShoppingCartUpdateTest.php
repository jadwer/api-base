<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\ShoppingCart;

class ShoppingCartUpdateTest extends TestCase
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

    public function test_admin_can_update_ShoppingCart(): void
    {
        $admin = $this->getAdminUser();
        $shoppingCart = ShoppingCart::factory()->create();

        $data = [
            'type' => 'shopping-carts',
            'id' => (string) $shoppingCart->id,
            'attributes' => [
                'name' => 'Updated ShoppingCart',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->patch("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('shopping_carts', [
            'id' => $shoppingCart->id,
            'name' => 'Updated ShoppingCart',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_ShoppingCart(): void
    {
        $admin = $this->getAdminUser();
        $shoppingCart = ShoppingCart::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'shopping-carts',
            'id' => (string) $shoppingCart->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->patch("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('shopping_carts', [
            'id' => $shoppingCart->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_ShoppingCart_metadata(): void
    {
        $admin = $this->getAdminUser();
        $shoppingCart = ShoppingCart::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'shopping-carts',
            'id' => (string) $shoppingCart->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->patch("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertOk();
        
        $shoppingCart->refresh();
        $this->assertEquals($metadata, $shoppingCart->metadata);
    }

    public function test_customer_user_cannot_update_ShoppingCart(): void
    {
        $customer = $this->getCustomerUser();
        $shoppingCart = ShoppingCart::factory()->create();

        $data = [
            'type' => 'shopping-carts',
            'id' => (string) $shoppingCart->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->patch("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_ShoppingCart(): void
    {
        $shoppingCart = ShoppingCart::factory()->create();

        $data = [
            'type' => 'shopping-carts',
            'id' => (string) $shoppingCart->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->patch("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_ShoppingCart(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'shopping-carts',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->patch('/api/v1/shopping-carts/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_ShoppingCart_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $shoppingCart = ShoppingCart::factory()->create();

        $data = [
            'type' => 'shopping-carts',
            'id' => (string) $shoppingCart->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('shopping-carts')
            ->withData($data)
            ->patch("/api/v1/shopping-carts/{$shoppingCart->id}");

        $response->assertStatus(422);
    }
}
