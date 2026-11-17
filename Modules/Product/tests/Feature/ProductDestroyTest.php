<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Borrado de productos (rediseno de Productos, 17-11): el withValidator del request leia
 * el documento tambien en DELETE y cualquier borrado respondia 500. Con historia el
 * backend lo rechaza y la pantalla ofrece desactivar.
 */
class ProductDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_product_without_history(): void
    {
        $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail(), 'sanctum');
        $product = Product::factory()->create();

        $this->jsonApi()->delete("/api/v1/products/{$product->id}")->assertNoContent();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_product_with_movements_is_not_deleted(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        InventoryMovement::factory()->create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'user_id' => $admin->id]);

        $response = $this->jsonApi()->delete("/api/v1/products/{$product->id}");

        $this->assertContains($response->status(), [409, 422]);
        $this->assertNotEmpty($response->json('errors.0.detail'));
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_customer_cannot_delete_product(): void
    {
        $this->actingAs(User::where('email', 'cliente1@example.com')->firstOrFail(), 'sanctum');
        $product = Product::factory()->create();

        $this->jsonApi()->delete("/api/v1/products/{$product->id}")->assertForbidden();
    }
}
