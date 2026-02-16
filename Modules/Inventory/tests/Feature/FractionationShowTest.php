<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\Inventory\Models\Fractionation;
use Modules\Inventory\Models\Warehouse;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FractionationShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_admin_can_show_fractionation()
    {
        $admin = $this->createUserWithPermissions('admin', ['fractionations.show']);

        $fractionation = Fractionation::factory()->create([
            'user_id' => $admin->id,
            'source_quantity' => 100,
            'produced_quantity' => 980,
            'waste_percentage' => 2,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fractionations')
            ->get("/api/v1/fractionations/{$fractionation->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => (string) $fractionation->id,
                'type' => 'fractionations',
                'attributes' => [
                    'sourceQuantity' => 100.0,
                    'producedQuantity' => 980.0,
                    'status' => 'completed',
                ],
            ],
        ]);
    }

    public function test_admin_can_show_with_includes()
    {
        $admin = $this->createUserWithPermissions('admin', ['fractionations.show']);

        $fractionation = Fractionation::factory()->create([
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fractionations')
            ->get("/api/v1/fractionations/{$fractionation->id}?include=sourceProduct,destinationProduct,warehouse");

        $response->assertStatus(200);
        $this->assertArrayHasKey('included', $response->json());
    }

    public function test_show_returns_404_for_nonexistent()
    {
        $admin = $this->createUserWithPermissions('admin', ['fractionations.show']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('fractionations')
            ->get('/api/v1/fractionations/99999');

        $response->assertStatus(404);
    }

    public function test_customer_cannot_show_fractionation()
    {
        $customer = $this->createUserWithPermissions('customer', []);

        $fractionation = Fractionation::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('fractionations')
            ->get("/api/v1/fractionations/{$fractionation->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_show_fractionation()
    {
        $fractionation = Fractionation::factory()->create();

        $response = $this->jsonApi()
            ->expects('fractionations')
            ->get("/api/v1/fractionations/{$fractionation->id}");

        $response->assertStatus(401);
    }
}
