<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnitStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_create_unit(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'units',
            'attributes' => [
                'name' => 'Test Unit',
                'code' => 'test_unit',
                'unitType' => 'weight',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/units');

        $response->assertCreated()->assertJson([
            'data' => [
                'type' => 'units',
                'attributes' => [
                    'name' => 'Test Unit',
                    'code' => 'test_unit',
                    'unitType' => 'weight',
                ],
            ],
        ]);

        $this->assertDatabaseHas('units', [
            'name' => 'Test Unit',
            'code' => 'test_unit',
            'unit_type' => 'weight',
        ]);
    }

    public function test_customer_cannot_create_unit(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $data = [
            'type' => 'units',
            'attributes' => [
                'name' => 'Test Unit',
                'code' => 'test_unit',
                'unitType' => 'weight',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/units');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_create_unit(): void
    {
        $data = [
            'type' => 'units',
            'attributes' => [
                'name' => 'Test Unit',
                'code' => 'test_unit',
                'unitType' => 'weight',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/units');

        $response->assertStatus(401);
    }

    public function test_unit_creation_fails_with_missing_fields(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'units',
            'attributes' => [
                // Missing required fields
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/units');

        $response->assertStatus(400);
    }

    public function test_unit_creation_fails_with_duplicate_code(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Create first unit
        Unit::factory()->create(['code' => 'dup_code']);

        $data = [
            'type' => 'units',
            'attributes' => [
                'name' => 'Second Unit',
                'code' => 'dup_code',
                'unitType' => 'volume',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/units');

        $response->assertStatus(422);
    }
}
