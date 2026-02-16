<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\Inventory\Models\ProductConversion;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FractionationCalculateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'fractionations.store', 'guard_name' => 'api']);

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'customer']);
    }

    private function createUserWithPermissions(string $role, array $permissions = []): User
    {
        $user = User::factory()->create();
        $roleModel = Role::findByName($role);

        if (!empty($permissions)) {
            $roleModel->givePermissionTo($permissions);
        }

        $user->assignRole($role);
        return $user;
    }

    public function test_admin_can_calculate_fractionation_preview()
    {
        $admin = $this->createUserWithPermissions('admin', ['fractionations.store']);

        $source = Product::factory()->create(['name' => 'Granel 15L']);
        $dest = Product::factory()->create(['name' => 'Botella 1L']);
        $warehouse = Warehouse::factory()->create();

        ProductConversion::factory()->create([
            'source_product_id' => $source->id,
            'destination_product_id' => $dest->id,
            'conversion_factor' => 10.0,
            'waste_percentage' => 2.0,
            'is_active' => true,
        ]);

        Stock::factory()->create([
            'product_id' => $source->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/fraccionamiento/calculate', [
                'source_product_id' => $source->id,
                'destination_product_id' => $dest->id,
                'source_quantity' => 5,
                'warehouse_id' => $warehouse->id,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'source_product',
                'destination_product',
                'source_quantity',
                'conversion_factor',
                'waste_percentage',
                'produced_quantity',
                'waste_quantity',
                'available_stock',
                'has_enough_stock',
            ],
        ]);

        $data = $response->json('data');
        $this->assertEquals(5, $data['source_quantity']);
        $this->assertEquals(10.0, $data['conversion_factor']);
        $this->assertEquals(2.0, $data['waste_percentage']);
        // produced = 5 * 10 * (1 - 2/100) = 49
        $this->assertEquals(49.0, $data['produced_quantity']);
        // waste = 5 * 10 * (2/100) = 1
        $this->assertEquals(1.0, $data['waste_quantity']);
        $this->assertTrue($data['has_enough_stock']);
    }

    public function test_calculate_shows_insufficient_stock()
    {
        $admin = $this->createUserWithPermissions('admin', ['fractionations.store']);

        $source = Product::factory()->create();
        $dest = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        ProductConversion::factory()->create([
            'source_product_id' => $source->id,
            'destination_product_id' => $dest->id,
            'conversion_factor' => 10.0,
            'waste_percentage' => 0,
            'is_active' => true,
        ]);

        Stock::factory()->create([
            'product_id' => $source->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 3,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/fraccionamiento/calculate', [
                'source_product_id' => $source->id,
                'destination_product_id' => $dest->id,
                'source_quantity' => 10,
                'warehouse_id' => $warehouse->id,
            ]);

        $response->assertStatus(200);
        $this->assertFalse($response->json('data.has_enough_stock'));
        $this->assertEquals(3.0, $response->json('data.available_stock'));
    }

    public function test_calculate_fails_for_invalid_conversion_pair()
    {
        $admin = $this->createUserWithPermissions('admin', ['fractionations.store']);

        $source = Product::factory()->create();
        $dest = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // No conversion created for this pair

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/fraccionamiento/calculate', [
                'source_product_id' => $source->id,
                'destination_product_id' => $dest->id,
                'source_quantity' => 5,
                'warehouse_id' => $warehouse->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);
    }

    public function test_calculate_validates_required_fields()
    {
        $admin = $this->createUserWithPermissions('admin', ['fractionations.store']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/fraccionamiento/calculate', []);

        $response->assertStatus(422);
    }

    public function test_customer_cannot_calculate()
    {
        $customer = $this->createUserWithPermissions('customer', []);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/fraccionamiento/calculate', [
                'source_product_id' => 1,
                'destination_product_id' => 2,
                'source_quantity' => 5,
                'warehouse_id' => 1,
            ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_calculate()
    {
        $response = $this->postJson('/api/v1/fraccionamiento/calculate', [
            'source_product_id' => 1,
            'destination_product_id' => 2,
            'source_quantity' => 5,
            'warehouse_id' => 1,
        ]);

        $response->assertStatus(401);
    }
}
