<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\Inventory\Models\ProductConversion;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\Fractionation;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Product\Models\Product;
use Modules\Sales\Models\FolioSequence;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FractionationExecuteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'fractionations.store', 'guard_name' => 'api']);

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'customer']);

        // Create fractionation folio sequence
        FolioSequence::firstOrCreate(
            ['document_type' => 'fractionation'],
            [
                'prefix' => 'FRAC',
                'include_year' => true,
                'year_format' => 'y',
                'separator' => '-',
                'padding' => 6,
                'current_sequence' => 0,
                'reset_yearly' => false,
                'is_active' => true,
            ]
        );
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

    public function test_admin_can_execute_fractionation()
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
            'unit_cost' => 50.0,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/fraccionamiento/execute', [
                'source_product_id' => $source->id,
                'destination_product_id' => $dest->id,
                'source_quantity' => 5,
                'warehouse_id' => $warehouse->id,
                'notes' => 'Test fractionation',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'folioNumber',
                'sourceQuantity',
                'producedQuantity',
                'wastePercentage',
                'wasteQuantity',
                'status',
                'sourceProduct',
                'destinationProduct',
                'warehouse',
            ],
            'message',
        ]);

        $data = $response->json('data');
        $this->assertStringStartsWith('FRAC-', $data['folioNumber']);
        $this->assertEquals(5, $data['sourceQuantity']);
        $this->assertEquals(49.0, $data['producedQuantity']); // 5 * 10 * (1 - 0.02)
        $this->assertEquals(1.0, $data['wasteQuantity']); // 5 * 10 * 0.02
        $this->assertEquals('completed', $data['status']);

        // Verify fractionation record was created
        $this->assertDatabaseHas('fractionations', [
            'id' => $data['id'],
            'source_product_id' => $source->id,
            'destination_product_id' => $dest->id,
            'status' => 'completed',
        ]);

        // Verify source stock was decremented
        $sourceStock = Stock::where('product_id', $source->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();
        $this->assertEquals(95, $sourceStock->quantity); // 100 - 5

        // Verify exit movement was created
        $exitMovement = InventoryMovement::where('product_id', $source->id)
            ->where('movement_type', 'exit')
            ->where('reference_type', 'fractionation')
            ->first();
        $this->assertNotNull($exitMovement);

        // Verify entry movement was created
        $entryMovement = InventoryMovement::where('product_id', $dest->id)
            ->where('movement_type', 'entry')
            ->where('reference_type', 'fractionation')
            ->first();
        $this->assertNotNull($entryMovement);
    }

    public function test_execute_generates_sequential_folios()
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
            'quantity' => 1000,
            'unit_cost' => 10.0,
        ]);

        // Execute twice
        $response1 = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/fraccionamiento/execute', [
                'source_product_id' => $source->id,
                'destination_product_id' => $dest->id,
                'source_quantity' => 1,
                'warehouse_id' => $warehouse->id,
            ]);
        $response1->assertStatus(201);

        $response2 = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/fraccionamiento/execute', [
                'source_product_id' => $source->id,
                'destination_product_id' => $dest->id,
                'source_quantity' => 1,
                'warehouse_id' => $warehouse->id,
            ]);
        $response2->assertStatus(201);

        $folio1 = $response1->json('data.folioNumber');
        $folio2 = $response2->json('data.folioNumber');

        $this->assertNotEquals($folio1, $folio2);
        $this->assertStringStartsWith('FRAC-', $folio1);
        $this->assertStringStartsWith('FRAC-', $folio2);
    }

    public function test_execute_fails_with_insufficient_stock()
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
            'unit_cost' => 10.0,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/fraccionamiento/execute', [
                'source_product_id' => $source->id,
                'destination_product_id' => $dest->id,
                'source_quantity' => 10,
                'warehouse_id' => $warehouse->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);

        // Verify no fractionation was created (transaction rolled back)
        $this->assertDatabaseCount('fractionations', 0);
    }

    public function test_execute_fails_for_invalid_conversion_pair()
    {
        $admin = $this->createUserWithPermissions('admin', ['fractionations.store']);

        $source = Product::factory()->create();
        $dest = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // No conversion created

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/fraccionamiento/execute', [
                'source_product_id' => $source->id,
                'destination_product_id' => $dest->id,
                'source_quantity' => 5,
                'warehouse_id' => $warehouse->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_execute_validates_required_fields()
    {
        $admin = $this->createUserWithPermissions('admin', ['fractionations.store']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/fraccionamiento/execute', []);

        $response->assertStatus(422);
    }

    public function test_customer_cannot_execute_fractionation()
    {
        $customer = $this->createUserWithPermissions('customer', []);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/fraccionamiento/execute', [
                'source_product_id' => 1,
                'destination_product_id' => 2,
                'source_quantity' => 5,
                'warehouse_id' => 1,
            ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_execute()
    {
        $response = $this->postJson('/api/v1/fraccionamiento/execute', [
            'source_product_id' => 1,
            'destination_product_id' => 2,
            'source_quantity' => 5,
            'warehouse_id' => 1,
        ]);

        $response->assertStatus(401);
    }
}
