<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BrandIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_list_brands(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Create test data
        Brand::factory()->count(3)->create();

        $response = $this->jsonApi()->get('/api/v1/brands');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'description',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
            'jsonapi',
        ]);
    }

    public function test_admin_can_sort_brands_by_name(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');
        
        Brand::factory()->create(['name' => 'Zebra Brand']);
        Brand::factory()->create(['name' => 'Alpha Brand']);

        $response = $this->jsonApi()->get('/api/v1/brands?sort=name');

        $response->assertOk();
        $this->assertIsArray($response->json('data'));

        $names = array_column($response->json('data'), 'attributes.name');
        $sorted = $names;
        sort($sorted, SORT_NATURAL | SORT_FLAG_CASE);

        $this->assertEquals($sorted, $names);
    }

    public function test_unauthenticated_user_cannot_list_brands(): void
    {
        $response = $this->jsonApi()->get('/api/v1/brands');

        $response->assertStatus(401);
    }

    public function test_customer_can_list_brands(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $response = $this->jsonApi()->get('/api/v1/brands');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'description',
                    ],
                ],
            ],
        ]);
    }
}
