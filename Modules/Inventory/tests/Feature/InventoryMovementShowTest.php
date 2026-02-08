<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;

class InventoryMovementShowTest extends TestCase
{
    public function test_admin_can_show_inventory_movement(): void
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
            'description' => 'Test entry movement',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->get("/api/v1/inventory-movements/{$movement->id}");

        $response->assertStatus(200);

        // Verificar estructura de respuesta
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'movementType',
                    'movementDate',
                    'description',
                    'quantity',
                    'unitCost',
                    'status',
                    'createdAt',
                    'updatedAt',
                ],
            ],
        ]);

        // Verificar datos especificos
        $data = $response->json('data');
        $this->assertEquals($movement->id, $data['id']);
        $this->assertEquals('inventory-movements', $data['type']);
        $this->assertEquals('entry', $data['attributes']['movementType']);
        $this->assertEquals('pending', $data['attributes']['status']);
    }

    public function test_admin_can_show_inventory_movement_with_product_include(): void
    {
        $admin = $this->getAdminUser();

        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $movement = InventoryMovement::factory()->entry()->pending()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->get("/api/v1/inventory-movements/{$movement->id}?include=product");

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'product' => ['data'],
                ],
            ],
            'included' => [
                '*' => [
                    'type',
                    'id',
                    'attributes',
                ],
            ],
        ]);

        // Verificar que el producto incluido es correcto
        $included = $response->json('included');
        $this->assertCount(1, $included);
        $this->assertEquals('products', $included[0]['type']);
        $this->assertEquals($product->id, $included[0]['id']);
    }

    public function test_admin_can_show_inventory_movement_with_warehouse_include(): void
    {
        $admin = $this->getAdminUser();

        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $movement = InventoryMovement::factory()->entry()->pending()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->get("/api/v1/inventory-movements/{$movement->id}?include=warehouse");

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'warehouse' => ['data'],
                ],
            ],
            'included',
        ]);

        // Verificar que el warehouse incluido es correcto
        $included = $response->json('included');
        $this->assertCount(1, $included);
        $this->assertEquals('warehouses', $included[0]['type']);
        $this->assertEquals($warehouse->id, $included[0]['id']);
    }

    public function test_admin_can_show_inventory_movement_with_multiple_includes(): void
    {
        $admin = $this->getAdminUser();

        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $movement = InventoryMovement::factory()->entry()->pending()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->get("/api/v1/inventory-movements/{$movement->id}?include=product,warehouse");

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'product',
                    'warehouse',
                ],
            ],
            'included',
        ]);

        $included = $response->json('included');
        $includedTypes = collect($included)->pluck('type')->toArray();
        $this->assertContains('products', $includedTypes);
        $this->assertContains('warehouses', $includedTypes);
    }

    public function test_tech_user_can_show_inventory_movement(): void
    {
        $tech = $this->getTechUser();

        $movement = InventoryMovement::factory()->entry()->pending()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->get("/api/v1/inventory-movements/{$movement->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals($movement->id, $data['id']);
    }

    public function test_customer_user_cannot_show_inventory_movement(): void
    {
        $customer = $this->getCustomerUser();

        $movement = InventoryMovement::factory()->entry()->pending()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->get("/api/v1/inventory-movements/{$movement->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_show_inventory_movement(): void
    {
        $movement = InventoryMovement::factory()->entry()->pending()->create();

        $response = $this->jsonApi()
            ->expects('inventory-movements')
            ->get("/api/v1/inventory-movements/{$movement->id}");

        $response->assertStatus(401);
    }

    public function test_show_nonexistent_inventory_movement_returns_404(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->get('/api/v1/inventory-movements/99999');

        $response->assertStatus(404);
    }
}
