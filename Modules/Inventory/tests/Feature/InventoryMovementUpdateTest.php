<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;

class InventoryMovementUpdateTest extends TestCase
{
    public function test_admin_can_update_inventory_movement(): void
    {
        $admin = $this->getAdminUser();

        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $movement = InventoryMovement::factory()->entry()->pending()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $admin->id,
            'quantity' => 100.5000,
            'unit_cost' => 25.75,
            'description' => 'Original description',
        ]);

        $updatedData = [
            'type' => 'inventory-movements',
            'id' => (string) $movement->id,
            'attributes' => [
                'productId' => $product->id,
                'warehouseId' => $warehouse->id,
                'userId' => $admin->id,
                'movementType' => 'entry',
                'movementDate' => '2024-08-13T10:00:00Z',
                'quantity' => 200.0000,
                'unitCost' => 30.50,
                'description' => 'Updated description',
                'status' => 'pending',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($updatedData)
            ->patch("/api/v1/inventory-movements/{$movement->id}");

        $response->assertStatus(200);

        // Verificar que el movimiento fue actualizado en la base de datos
        $this->assertDatabaseHas('inventory_movements', [
            'id' => $movement->id,
            'quantity' => 200.0000,
            'unit_cost' => 30.50,
            'description' => 'Updated description',
        ]);

        // Verificar estructura de respuesta JSON:API
        $response->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'movementType',
                    'quantity',
                    'unitCost',
                    'description',
                    'status',
                ],
            ],
        ]);

        $response->assertJson([
            'data' => [
                'type' => 'inventory-movements',
                'id' => (string) $movement->id,
                'attributes' => [
                    'description' => 'Updated description',
                ],
            ],
        ]);
    }

    public function test_admin_can_update_movement_status_and_notes(): void
    {
        $admin = $this->getAdminUser();

        $movement = InventoryMovement::factory()->entry()->pending()->create([
            'user_id' => $admin->id,
            'description' => 'Initial notes',
        ]);

        $updatedData = [
            'type' => 'inventory-movements',
            'id' => (string) $movement->id,
            'attributes' => [
                'productId' => $movement->product_id,
                'warehouseId' => $movement->warehouse_id,
                'userId' => $movement->user_id,
                'movementType' => $movement->movement_type,
                'movementDate' => $movement->movement_date->toIso8601String(),
                'quantity' => $movement->quantity,
                'unitCost' => $movement->unit_cost,
                'status' => 'completed',
                'description' => 'Updated notes after completion',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($updatedData)
            ->patch("/api/v1/inventory-movements/{$movement->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('inventory_movements', [
            'id' => $movement->id,
            'status' => 'completed',
            'description' => 'Updated notes after completion',
        ]);
    }

    public function test_update_validates_invalid_status(): void
    {
        $admin = $this->getAdminUser();

        $movement = InventoryMovement::factory()->entry()->pending()->create([
            'user_id' => $admin->id,
        ]);

        $updatedData = [
            'type' => 'inventory-movements',
            'id' => (string) $movement->id,
            'attributes' => [
                'productId' => $movement->product_id,
                'warehouseId' => $movement->warehouse_id,
                'userId' => $movement->user_id,
                'movementType' => 'entry',
                'movementDate' => '2024-08-13T10:00:00Z',
                'quantity' => $movement->quantity,
                'unitCost' => $movement->unit_cost,
                'status' => 'invalid_status',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($updatedData)
            ->patch("/api/v1/inventory-movements/{$movement->id}");

        $response->assertStatus(422);
    }

    public function test_update_validates_invalid_movement_type(): void
    {
        $admin = $this->getAdminUser();

        $movement = InventoryMovement::factory()->entry()->pending()->create([
            'user_id' => $admin->id,
        ]);

        $updatedData = [
            'type' => 'inventory-movements',
            'id' => (string) $movement->id,
            'attributes' => [
                'productId' => $movement->product_id,
                'warehouseId' => $movement->warehouse_id,
                'userId' => $movement->user_id,
                'movementType' => 'invalid_type',
                'movementDate' => '2024-08-13T10:00:00Z',
                'quantity' => $movement->quantity,
                'unitCost' => $movement->unit_cost,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($updatedData)
            ->patch("/api/v1/inventory-movements/{$movement->id}");

        $response->assertStatus(422);
    }

    public function test_update_validates_zero_quantity(): void
    {
        $admin = $this->getAdminUser();

        $movement = InventoryMovement::factory()->entry()->pending()->create([
            'user_id' => $admin->id,
        ]);

        $updatedData = [
            'type' => 'inventory-movements',
            'id' => (string) $movement->id,
            'attributes' => [
                'productId' => $movement->product_id,
                'warehouseId' => $movement->warehouse_id,
                'userId' => $movement->user_id,
                'movementType' => 'entry',
                'movementDate' => '2024-08-13T10:00:00Z',
                'quantity' => 0,
                'unitCost' => $movement->unit_cost,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($updatedData)
            ->patch("/api/v1/inventory-movements/{$movement->id}");

        $response->assertStatus(422);
    }

    public function test_cannot_update_completed_movement(): void
    {
        $admin = $this->getAdminUser();

        // Create a completed entry movement (entries don't require quality check)
        $movement = InventoryMovement::factory()->entry()->create([
            'user_id' => $admin->id,
            'status' => 'completed',
        ]);

        $updatedData = [
            'type' => 'inventory-movements',
            'id' => (string) $movement->id,
            'attributes' => [
                'productId' => $movement->product_id,
                'warehouseId' => $movement->warehouse_id,
                'userId' => $movement->user_id,
                'movementType' => 'entry',
                'movementDate' => '2024-08-13T10:00:00Z',
                'quantity' => 999,
                'unitCost' => $movement->unit_cost,
                'description' => 'Trying to update completed movement',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($updatedData)
            ->patch("/api/v1/inventory-movements/{$movement->id}");

        $response->assertForbidden();
    }

    public function test_update_nonexistent_movement_returns_404(): void
    {
        $admin = $this->getAdminUser();

        $updatedData = [
            'type' => 'inventory-movements',
            'id' => '99999',
            'attributes' => [
                'productId' => 1,
                'warehouseId' => 1,
                'userId' => $admin->id,
                'movementType' => 'entry',
                'movementDate' => '2024-08-13T10:00:00Z',
                'quantity' => 100,
                'unitCost' => 25.75,
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($updatedData)
            ->patch('/api/v1/inventory-movements/99999');

        $response->assertStatus(404);
    }

    public function test_tech_user_can_update_inventory_movement(): void
    {
        $tech = $this->getTechUser();

        $movement = InventoryMovement::factory()->entry()->pending()->create();

        $updatedData = [
            'type' => 'inventory-movements',
            'id' => (string) $movement->id,
            'attributes' => [
                'productId' => $movement->product_id,
                'warehouseId' => $movement->warehouse_id,
                'userId' => $movement->user_id,
                'movementType' => 'entry',
                'movementDate' => '2024-08-13T10:00:00Z',
                'quantity' => $movement->quantity,
                'unitCost' => $movement->unit_cost,
                'description' => 'Updated by tech user',
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($updatedData)
            ->patch("/api/v1/inventory-movements/{$movement->id}");

        $response->assertStatus(200);
    }

    public function test_customer_cannot_update_inventory_movement(): void
    {
        $customer = $this->getCustomerUser();

        $movement = InventoryMovement::factory()->entry()->pending()->create();

        $updatedData = [
            'type' => 'inventory-movements',
            'id' => (string) $movement->id,
            'attributes' => [
                'productId' => $movement->product_id,
                'warehouseId' => $movement->warehouse_id,
                'userId' => $movement->user_id,
                'movementType' => 'entry',
                'movementDate' => '2024-08-13T10:00:00Z',
                'quantity' => $movement->quantity,
                'unitCost' => $movement->unit_cost,
                'description' => 'Customer attempt',
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->withData($updatedData)
            ->patch("/api/v1/inventory-movements/{$movement->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_update_inventory_movement(): void
    {
        $movement = InventoryMovement::factory()->entry()->pending()->create();

        $updatedData = [
            'type' => 'inventory-movements',
            'id' => (string) $movement->id,
            'attributes' => [
                'productId' => $movement->product_id,
                'warehouseId' => $movement->warehouse_id,
                'userId' => $movement->user_id,
                'movementType' => 'entry',
                'movementDate' => '2024-08-13T10:00:00Z',
                'quantity' => $movement->quantity,
                'unitCost' => $movement->unit_cost,
                'description' => 'Unauthenticated attempt',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('inventory-movements')
            ->withData($updatedData)
            ->patch("/api/v1/inventory-movements/{$movement->id}");

        $response->assertStatus(401);
    }
}
