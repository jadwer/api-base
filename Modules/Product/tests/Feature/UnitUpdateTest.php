<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnitUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_update_unit(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $unit = Unit::factory()->create([
            'name' => 'Original Unit',
            'code' => 'orig_unit',
            'unit_type' => 'weight',
        ]);

        $data = [
            'type' => 'units',
            'id' => (string) $unit->id,
            'attributes' => [
                'name' => 'Updated Unit Name',
                'code' => 'upd_unit',
                'unitType' => 'volume',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/units/{$unit->id}");

        $response->assertOk()->assertJson([
            'data' => [
                'type' => 'units',
                'id' => (string) $unit->id,
                'attributes' => [
                    'name' => 'Updated Unit Name',
                    'code' => 'upd_unit',
                    'unitType' => 'volume',
                ],
            ],
        ]);

        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'name' => 'Updated Unit Name',
            'code' => 'upd_unit',
            'unit_type' => 'volume',
        ]);
    }

    public function test_customer_cannot_update_unit(): void
    {
        $customer = User::where('email', 'cliente1@example.com')->firstOrFail();
        $this->actingAs($customer, 'sanctum');

        $unit = Unit::factory()->create();

        $data = [
            'type' => 'units',
            'id' => (string) $unit->id,
            'attributes' => [
                'name' => 'Updated Unit Name',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/units/{$unit->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_update_unit(): void
    {
        $unit = Unit::factory()->create();

        $data = [
            'type' => 'units',
            'id' => (string) $unit->id,
            'attributes' => [
                'name' => 'Updated Unit Name',
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/units/{$unit->id}");

        $response->assertStatus(401);
    }

    public function test_unit_update_fails_with_duplicate_code(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Create existing unit with code
        Unit::factory()->create(['code' => 'exist']);

        // Create unit to update
        $unitToUpdate = Unit::factory()->create(['code' => 'diff']);

        $data = [
            'type' => 'units',
            'id' => (string) $unitToUpdate->id,
            'attributes' => [
                'code' => 'exist', // Try to use existing code
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/units/{$unitToUpdate->id}");

        $this->assertJsonApiValidationErrors([
            '/data/attributes/code',
        ], $response);
    }

    public function test_unit_update_allows_keeping_same_code(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $unit = Unit::factory()->create([
            'name' => 'Original Name',
            'code' => 'same',
        ]);

        $data = [
            'type' => 'units',
            'id' => (string) $unit->id,
            'attributes' => [
                'name' => 'Updated Name',
                'code' => 'same', // Keep the same code
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->patch("/api/v1/units/{$unit->id}");

        $response->assertOk();
        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'name' => 'Updated Name',
            'code' => 'same',
        ]);
    }
}
