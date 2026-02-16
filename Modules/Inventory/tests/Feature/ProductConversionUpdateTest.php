<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\Inventory\Models\ProductConversion;
use Modules\Product\Models\Product;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductConversionUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'product-conversions.update', 'guard_name' => 'api']);

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

    public function test_admin_can_update_product_conversion()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.update']);

        $conversion = ProductConversion::factory()->create([
            'conversion_factor' => 10.0,
            'waste_percentage' => 2.0,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->withData([
                'type' => 'product-conversions',
                'id' => (string) $conversion->id,
                'attributes' => [
                    'conversionFactor' => 15.0,
                    'wastePercentage' => 3.5,
                ],
            ])
            ->patch("/api/v1/product-conversions/{$conversion->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'conversionFactor' => 15.0,
                    'wastePercentage' => 3.5,
                ],
            ],
        ]);
    }

    public function test_admin_can_toggle_active_status()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.update']);

        $conversion = ProductConversion::factory()->active()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->withData([
                'type' => 'product-conversions',
                'id' => (string) $conversion->id,
                'attributes' => [
                    'isActive' => false,
                ],
            ])
            ->patch("/api/v1/product-conversions/{$conversion->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'attributes' => [
                    'isActive' => false,
                ],
            ],
        ]);
    }

    public function test_update_validates_waste_percentage_range()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.update']);

        $conversion = ProductConversion::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->withData([
                'type' => 'product-conversions',
                'id' => (string) $conversion->id,
                'attributes' => [
                    'wastePercentage' => 101,
                ],
            ])
            ->patch("/api/v1/product-conversions/{$conversion->id}");

        $response->assertStatus(422);
    }

    public function test_update_returns_404_for_nonexistent()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.update']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->withData([
                'type' => 'product-conversions',
                'id' => '99999',
                'attributes' => [
                    'conversionFactor' => 5.0,
                ],
            ])
            ->patch('/api/v1/product-conversions/99999');

        $response->assertStatus(404);
    }

    public function test_customer_cannot_update_product_conversion()
    {
        $customer = $this->createUserWithPermissions('customer', []);

        $conversion = ProductConversion::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->withData([
                'type' => 'product-conversions',
                'id' => (string) $conversion->id,
                'attributes' => [
                    'conversionFactor' => 5.0,
                ],
            ])
            ->patch("/api/v1/product-conversions/{$conversion->id}");

        $response->assertStatus(403);
    }
}
