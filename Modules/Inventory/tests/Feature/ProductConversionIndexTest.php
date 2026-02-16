<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\Inventory\Models\ProductConversion;
use Modules\Product\Models\Product;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductConversionIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'product-conversions.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'product-conversions.show', 'guard_name' => 'api']);

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'tech']);
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

    public function test_admin_can_list_product_conversions()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.index']);

        $source = Product::factory()->create();
        $dest = Product::factory()->create();
        ProductConversion::factory()->create([
            'source_product_id' => $source->id,
            'destination_product_id' => $dest->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->get('/api/v1/product-conversions');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'sourceProductId',
                        'destinationProductId',
                        'conversionFactor',
                        'wastePercentage',
                        'isActive',
                    ],
                ],
            ],
        ]);
    }

    public function test_admin_can_filter_by_source_product()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.index']);

        $source1 = Product::factory()->create();
        $source2 = Product::factory()->create();
        $dest = Product::factory()->create();

        ProductConversion::factory()->create([
            'source_product_id' => $source1->id,
            'destination_product_id' => $dest->id,
        ]);
        ProductConversion::factory()->create([
            'source_product_id' => $source2->id,
            'destination_product_id' => Product::factory()->create()->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->get("/api/v1/product-conversions?filter[sourceProduct]={$source1->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_admin_can_filter_by_active_status()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.index']);

        $source1 = Product::factory()->create();
        $source2 = Product::factory()->create();

        ProductConversion::factory()->active()->create([
            'source_product_id' => $source1->id,
            'destination_product_id' => Product::factory()->create()->id,
        ]);
        ProductConversion::factory()->inactive()->create([
            'source_product_id' => $source2->id,
            'destination_product_id' => Product::factory()->create()->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->get('/api/v1/product-conversions?filter[isActive]=1');

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertTrue($item['attributes']['isActive']);
        }
    }

    public function test_admin_can_include_products()
    {
        $admin = $this->createUserWithPermissions('admin', ['product-conversions.index']);

        ProductConversion::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->get('/api/v1/product-conversions?include=sourceProduct,destinationProduct');

        $response->assertStatus(200);
        $this->assertArrayHasKey('included', $response->json());
    }

    public function test_customer_cannot_list_product_conversions()
    {
        $customer = $this->createUserWithPermissions('customer', []);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->get('/api/v1/product-conversions');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_list_product_conversions()
    {
        $response = $this->jsonApi()
            ->expects('product-conversions')
            ->get('/api/v1/product-conversions');

        $response->assertStatus(401);
    }
}
