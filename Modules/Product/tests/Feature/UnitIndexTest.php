<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnitIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_list_units(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Create test data
        Unit::factory()->count(3)->create();

        $response = $this->jsonApi()->get('/api/v1/units');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'code',
                        'unitType',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
            'jsonapi',
        ]);
    }

    public function test_admin_can_sort_units_by_name(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');
        
        Unit::factory()->create(['name' => 'Zebra Unit', 'code' => 'zu']);
        Unit::factory()->create(['name' => 'Alpha Unit', 'code' => 'au']);

        $response = $this->jsonApi()->get('/api/v1/units?sort=name');

        $response->assertOk();
        $this->assertIsArray($response->json('data'));

        $names = array_column($response->json('data'), 'attributes.name');
        $sorted = $names;
        sort($sorted, SORT_NATURAL | SORT_FLAG_CASE);

        $this->assertEquals($sorted, $names);
    }

    public function test_unauthenticated_user_cannot_list_units(): void
    {
        $response = $this->jsonApi()->get('/api/v1/units');

        $response->assertStatus(401);
    }

    public function test_customer_can_list_units(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $response = $this->jsonApi()->get('/api/v1/units');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'code',
                        'unitType',
                    ],
                ],
            ],
        ]);
    }
}
