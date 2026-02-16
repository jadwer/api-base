<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\Inventory\Models\ProductConversion;
use Modules\Product\Models\Product;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductConversionStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'product-conversions.store', 'guard_name' => 'api']);

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

    public function test_admin_can_create_product_conversion()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.store']);

        $source = Product::factory()->create();
        $dest = Product::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->withData([
                'type' => 'product-conversions',
                'attributes' => [
                    'sourceProductId' => $source->id,
                    'destinationProductId' => $dest->id,
                    'conversionFactor' => 10.0,
                    'wastePercentage' => 2.5,
                    'isActive' => true,
                    'notes' => 'Test conversion',
                ],
            ])
            ->post('/api/v1/product-conversions');

        $response->assertStatus(201);
        $response->assertJson([
            'data' => [
                'type' => 'product-conversions',
                'attributes' => [
                    'conversionFactor' => 10.0,
                    'wastePercentage' => 2.5,
                    'isActive' => true,
                ],
            ],
        ]);

        $this->assertDatabaseHas('product_conversions', [
            'source_product_id' => $source->id,
            'destination_product_id' => $dest->id,
            'conversion_factor' => 10.0,
        ]);
    }

    public function test_store_requires_source_product()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.store']);

        $dest = Product::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->withData([
                'type' => 'product-conversions',
                'attributes' => [
                    'destinationProductId' => $dest->id,
                    'conversionFactor' => 10.0,
                ],
            ])
            ->post('/api/v1/product-conversions');

        $response->assertStatus(422);
    }

    public function test_store_requires_conversion_factor()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.store']);

        $source = Product::factory()->create();
        $dest = Product::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->withData([
                'type' => 'product-conversions',
                'attributes' => [
                    'sourceProductId' => $source->id,
                    'destinationProductId' => $dest->id,
                ],
            ])
            ->post('/api/v1/product-conversions');

        $response->assertStatus(422);
    }

    public function test_store_validates_same_product()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.store']);

        $product = Product::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->withData([
                'type' => 'product-conversions',
                'attributes' => [
                    'sourceProductId' => $product->id,
                    'destinationProductId' => $product->id,
                    'conversionFactor' => 10.0,
                ],
            ])
            ->post('/api/v1/product-conversions');

        $response->assertStatus(422);
    }

    public function test_store_validates_duplicate_pair()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.store']);

        $source = Product::factory()->create();
        $dest = Product::factory()->create();

        ProductConversion::factory()->create([
            'source_product_id' => $source->id,
            'destination_product_id' => $dest->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->withData([
                'type' => 'product-conversions',
                'attributes' => [
                    'sourceProductId' => $source->id,
                    'destinationProductId' => $dest->id,
                    'conversionFactor' => 5.0,
                ],
            ])
            ->post('/api/v1/product-conversions');

        $response->assertStatus(422);
    }

    public function test_customer_cannot_create_product_conversion()
    {
        $customer = $this->createUserWithPermissions('customer', []);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->withData([
                'type' => 'product-conversions',
                'attributes' => [
                    'sourceProductId' => 1,
                    'destinationProductId' => 2,
                    'conversionFactor' => 10.0,
                ],
            ])
            ->post('/api/v1/product-conversions');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_create_product_conversion()
    {
        $response = $this->jsonApi()
            ->expects('product-conversions')
            ->withData([
                'type' => 'product-conversions',
                'attributes' => [
                    'sourceProductId' => 1,
                    'destinationProductId' => 2,
                    'conversionFactor' => 10.0,
                ],
            ])
            ->post('/api/v1/product-conversions');

        $response->assertStatus(401);
    }
}
