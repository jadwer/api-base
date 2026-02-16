<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\Inventory\Models\Fractionation;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FractionationIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'fractionations.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'fractionations.show', 'guard_name' => 'api']);

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

    public function test_admin_can_list_fractionations()
    {
        $admin = $this->createUserWithPermissions('admin', ['fractionations.index']);

        $warehouse = Warehouse::factory()->create();
        Fractionation::factory()->create([
            'warehouse_id' => $warehouse->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fractionations')
            ->get('/api/v1/fractionations');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'folioNumber',
                        'sourceQuantity',
                        'producedQuantity',
                        'wastePercentage',
                        'wasteQuantity',
                        'status',
                    ],
                ],
            ],
        ]);
    }

    public function test_admin_can_filter_by_status()
    {
        $admin = $this->createUserWithPermissions('admin', ['fractionations.index']);

        $warehouse = Warehouse::factory()->create();
        Fractionation::factory()->completed()->create([
            'warehouse_id' => $warehouse->id,
            'user_id' => $admin->id,
        ]);
        Fractionation::factory()->cancelled()->create([
            'warehouse_id' => $warehouse->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fractionations')
            ->get("/api/v1/fractionations?filter[warehouse]={$warehouse->id}&filter[status]=completed");

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals('completed', $item['attributes']['status']);
        }
    }

    public function test_admin_can_filter_by_warehouse()
    {
        $admin = $this->createUserWithPermissions('admin', ['fractionations.index']);

        $wh1 = Warehouse::factory()->create();
        $wh2 = Warehouse::factory()->create();

        Fractionation::factory()->create(['warehouse_id' => $wh1->id, 'user_id' => $admin->id]);
        Fractionation::factory()->create(['warehouse_id' => $wh2->id, 'user_id' => $admin->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fractionations')
            ->get("/api/v1/fractionations?filter[warehouse]={$wh1->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_admin_can_sort_by_executed_at()
    {
        $admin = $this->createUserWithPermissions('admin', ['fractionations.index']);

        $warehouse = Warehouse::factory()->create();
        Fractionation::factory()->create([
            'warehouse_id' => $warehouse->id,
            'user_id' => $admin->id,
            'executed_at' => now()->subDays(2),
        ]);
        Fractionation::factory()->create([
            'warehouse_id' => $warehouse->id,
            'user_id' => $admin->id,
            'executed_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fractionations')
            ->get("/api/v1/fractionations?filter[warehouse]={$warehouse->id}&sort=-executedAt");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_customer_cannot_list_fractionations()
    {
        $customer = $this->createUserWithPermissions('customer', []);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('fractionations')
            ->get('/api/v1/fractionations');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_list_fractionations()
    {
        $response = $this->jsonApi()
            ->expects('fractionations')
            ->get('/api/v1/fractionations');

        $response->assertStatus(401);
    }
}
