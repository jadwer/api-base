<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\Inventory\Models\ProductConversion;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductConversionDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'product-conversions.destroy', 'guard_name' => 'api']);

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

    public function test_admin_can_delete_product_conversion()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.destroy']);

        $conversion = ProductConversion::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->delete("/api/v1/product-conversions/{$conversion->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('product_conversions', ['id' => $conversion->id]);
    }

    public function test_delete_returns_404_for_nonexistent()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.destroy']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->delete('/api/v1/product-conversions/99999');

        $response->assertStatus(404);
    }

    public function test_customer_cannot_delete_product_conversion()
    {
        $customer = $this->createUserWithPermissions('customer', []);

        $conversion = ProductConversion::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->delete("/api/v1/product-conversions/{$conversion->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_delete_product_conversion()
    {
        $conversion = ProductConversion::factory()->create();

        $response = $this->jsonApi()
            ->expects('product-conversions')
            ->delete("/api/v1/product-conversions/{$conversion->id}");

        $response->assertStatus(401);
    }
}
