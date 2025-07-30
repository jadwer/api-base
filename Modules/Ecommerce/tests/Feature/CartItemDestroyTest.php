<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\CartItem;

class CartItemDestroyTest extends TestCase
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

    public function test_admin_can_delete_CartItem(): void
    {
        $admin = $this->getAdminUser();
        $cartItem = CartItem::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->delete("/api/v1/cart-items/{$cartItem->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id
        ]);
    }

    public function test_admin_can_delete_CartItem_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $cartItem = CartItem::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->delete("/api/v1/cart-items/{$cartItem->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id
        ]);
    }

    public function test_can_delete_inactive_CartItem(): void
    {
        $admin = $this->getAdminUser();
        $cartItem = CartItem::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->delete("/api/v1/cart-items/{$cartItem->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id
        ]);
    }

    public function test_customer_user_cannot_delete_CartItem(): void
    {
        $customer = $this->getCustomerUser();
        $cartItem = CartItem::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->delete("/api/v1/cart-items/{$cartItem->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id
        ]);
    }

    public function test_guest_cannot_delete_CartItem(): void
    {
        $cartItem = CartItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('cart-items')
            ->delete("/api/v1/cart-items/{$cartItem->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_CartItem(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->delete('/api/v1/cart-items/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $cartItem = CartItem::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->delete("/api/v1/cart-items/{$cartItem->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $cartItem = CartItem::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->delete("/api/v1/cart-items/{$cartItem->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cart-items')
            ->delete("/api/v1/cart-items/{$cartItem->id}");

        $response2->assertStatus(404);
    }
}
