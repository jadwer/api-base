<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnitShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_authenticated_user_can_view_unit(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $unit = Unit::factory()->create([
            'name' => 'Test Unit',
            'code' => 'test_unit',
            'unit_type' => 'weight',
        ]);

        $response = $this->jsonApi()->get("/api/v1/units/{$unit->id}");

        $response->assertOk()->assertJson([
            'data' => [
                'id' => (string) $unit->id,
                'type' => 'units',
                'attributes' => [
                    'name' => 'Test Unit',
                    'code' => 'test_unit',
                    'unitType' => 'weight',
                ],
            ],
        ]);
    }

    public function test_unauthenticated_user_cannot_view_unit(): void
    {
        $unit = Unit::factory()->create();

        $response = $this->jsonApi()->get("/api/v1/units/{$unit->id}");

        $response->assertStatus(401);
    }

    public function test_customer_can_view_unit(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $unit = Unit::factory()->create([
            'name' => 'Customer Unit',
            'code' => 'cust_unit',
            'unit_type' => 'volume',
        ]);

        $response = $this->jsonApi()->get("/api/v1/units/{$unit->id}");

        $response->assertOk()->assertJson([
            'data' => [
                'id' => (string) $unit->id,
                'type' => 'units',
                'attributes' => [
                    'name' => 'Customer Unit',
                    'code' => 'cust_unit',
                    'unitType' => 'volume',
                ],
            ],
        ]);
    }
}
