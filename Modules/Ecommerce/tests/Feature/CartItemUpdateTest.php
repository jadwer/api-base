<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\CartItem;

class CartItemUpdateTest extends TestCase
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

    public function test_admin_can_update_CartItem(): void
    {
        $admin = $this->getAdminUser();
        $cartItem = CartItem::factory()->create();

        $data = [
            'type' => 'cart-items',
            'id' => (string) $cartItem->id,
            'attributes' => [
                'name' => 'Updated CartItem',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->patch("/api/v1/cart-items/{\$\{\{modelNameLower\}\}->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('cart_items', [
            'id' => \$\{\{modelNameLower\}\}->id,
            'name' => 'Updated CartItem',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_CartItem(): void
    {
        $admin = $this->getAdminUser();
        \$\{\{modelNameLower\}\} = CartItem::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'cart-items',
            'id' => (string) \$\{\{modelNameLower\}\}->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->patch("/api/v1/cart-items/{\$\{\{modelNameLower\}\}->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('cart_items', [
            'id' => \$\{\{modelNameLower\}\}->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_CartItem_metadata(): void
    {
        $admin = $this->getAdminUser();
        \$\{\{modelNameLower\}\} = CartItem::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'cart-items',
            'id' => (string) \$\{\{modelNameLower\}\}->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->patch("/api/v1/cart-items/{\$\{\{modelNameLower\}\}->id}");

        $response->assertOk();
        
        \$\{\{modelNameLower\}\}->refresh();
        $this->assertEquals($metadata, \$\{\{modelNameLower\}\}->metadata);
    }

    public function test_customer_user_cannot_update_CartItem(): void
    {
        $customer = $this->getCustomerUser();
        \$\{\{modelNameLower\}\} = CartItem::factory()->create();

        $data = [
            'type' => 'cart-items',
            'id' => (string) \$\{\{modelNameLower\}\}->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->patch("/api/v1/cart-items/{\$\{\{modelNameLower\}\}->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_CartItem(): void
    {
        \$\{\{modelNameLower\}\} = CartItem::factory()->create();

        $data = [
            'type' => 'cart-items',
            'id' => (string) \$\{\{modelNameLower\}\}->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->patch("/api/v1/cart-items/{\$\{\{modelNameLower\}\}->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_CartItem(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'cart-items',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->patch('/api/v1/cart-items/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_CartItem_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        \$\{\{modelNameLower\}\} = CartItem::factory()->create();

        $data = [
            'type' => 'cart-items',
            'id' => (string) \$\{\{modelNameLower\}\}->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->withData($data)
            ->patch("/api/v1/cart-items/{\$\{\{modelNameLower\}\}->id}");

        $response->assertStatus(422);
    }
}
