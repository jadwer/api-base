<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\Inventory\Models\ProductConversion;
use Modules\Product\Models\Product;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductConversionShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'product-conversions.show', 'guard_name' => 'api']);

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

    public function test_admin_can_show_product_conversion()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.show']);

        $conversion = ProductConversion::factory()->create([
            'conversion_factor' => 10.5,
            'waste_percentage' => 2.5,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->get("/api/v1/product-conversions/{$conversion->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => (string) $conversion->id,
                'type' => 'product-conversions',
                'attributes' => [
                    'conversionFactor' => 10.5,
                    'wastePercentage' => 2.5,
                    'isActive' => true,
                ],
            ],
        ]);
    }

    public function test_admin_can_show_with_includes()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.show']);

        $conversion = ProductConversion::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->get("/api/v1/product-conversions/{$conversion->id}?include=sourceProduct,destinationProduct");

        $response->assertStatus(200);
        $this->assertArrayHasKey('included', $response->json());
    }

    public function test_show_returns_404_for_nonexistent()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.show']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->get('/api/v1/product-conversions/99999');

        $response->assertStatus(404);
    }

    public function test_customer_cannot_show_product_conversion()
    {
        $customer = $this->createUserWithPermissions('customer', []);

        $conversion = ProductConversion::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->get("/api/v1/product-conversions/{$conversion->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_show_product_conversion()
    {
        $conversion = ProductConversion::factory()->create();

        $response = $this->jsonApi()
            ->expects('product-conversions')
            ->get("/api/v1/product-conversions/{$conversion->id}");

        $response->assertStatus(401);
    }
}
