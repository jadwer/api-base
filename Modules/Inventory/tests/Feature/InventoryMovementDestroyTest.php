<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;

class InventoryMovementDestroyTest extends TestCase
{
    public function test_admin_can_delete_inventory_movement(): void
    {
        $admin = $this->getAdminUser();

        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $movement = InventoryMovement::factory()->entry()->pending()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('inventory_movements', ['id' => $movement->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->delete("/api/v1/inventory-movements/{$movement->id}");

        $response->assertStatus(204);

        // Verificar que el movimiento fue eliminado de la base de datos
        $this->assertDatabaseMissing('inventory_movements', ['id' => $movement->id]);
    }

    public function test_cannot_delete_completed_movement(): void
    {
        $admin = $this->getAdminUser();

        // Create a completed entry movement (entries don't require quality check)
        $movement = InventoryMovement::factory()->entry()->create([
            'user_id' => $admin->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->delete("/api/v1/inventory-movements/{$movement->id}");

        $response->assertForbidden();

        // Verificar que el movimiento no fue eliminado
        $this->assertDatabaseHas('inventory_movements', ['id' => $movement->id]);
    }

    public function test_delete_nonexistent_movement_returns_404(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->delete('/api/v1/inventory-movements/99999');

        $response->assertStatus(404);
    }

    public function test_tech_user_can_delete_pending_movement(): void
    {
        $tech = $this->getTechUser();

        $movement = InventoryMovement::factory()->entry()->pending()->create();

        $this->assertDatabaseHas('inventory_movements', ['id' => $movement->id]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->delete("/api/v1/inventory-movements/{$movement->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('inventory_movements', ['id' => $movement->id]);
    }

    public function test_customer_cannot_delete_inventory_movement(): void
    {
        $customer = $this->getCustomerUser();

        $movement = InventoryMovement::factory()->entry()->pending()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->delete("/api/v1/inventory-movements/{$movement->id}");

        $response->assertForbidden();

        // Verificar que el movimiento no fue eliminado
        $this->assertDatabaseHas('inventory_movements', ['id' => $movement->id]);
    }

    public function test_unauthenticated_user_cannot_delete_inventory_movement(): void
    {
        $movement = InventoryMovement::factory()->entry()->pending()->create();

        $response = $this->jsonApi()
            ->expects('inventory-movements')
            ->delete("/api/v1/inventory-movements/{$movement->id}");

        $response->assertStatus(401);

        // Verificar que el movimiento no fue eliminado
        $this->assertDatabaseHas('inventory_movements', ['id' => $movement->id]);
    }

    public function test_can_delete_cancelled_movement(): void
    {
        $admin = $this->getAdminUser();

        $movement = InventoryMovement::factory()->entry()->create([
            'user_id' => $admin->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('inventory_movements', ['id' => $movement->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->delete("/api/v1/inventory-movements/{$movement->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('inventory_movements', ['id' => $movement->id]);
    }
}
