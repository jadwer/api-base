<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\Product\Models\VariantAttribute;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * PR-M003: Tests for VariantAttribute index endpoint.
 */
class VariantAttributeIndexTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first()
            ?? User::factory()->create()->assignRole('admin');

        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    public function test_admin_can_list_variant_attributes(): void
    {
        $this->getAdminUser();

        VariantAttribute::factory()->create(['name' => 'Size', 'code' => 'size']);
        VariantAttribute::factory()->create(['name' => 'Color', 'code' => 'color']);

        $response = $this->jsonApi()->expects('variant-attributes')->get('/api/v1/variant-attributes');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'attributes' => ['name', 'code', 'isActive', 'sortOrder'],
                    ],
                ],
            ]);
    }

    public function test_can_filter_by_code(): void
    {
        $this->getAdminUser();

        VariantAttribute::factory()->create(['code' => 'test-filter-size']);
        VariantAttribute::factory()->create(['code' => 'test-filter-color']);

        $response = $this->jsonApi()
            ->expects('variant-attributes')
            ->filter(['code' => 'test-filter-size'])
            ->get('/api/v1/variant-attributes');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('test-filter-size', $data[0]['attributes']['code']);
    }

    public function test_can_sort_by_name(): void
    {
        $this->getAdminUser();

        VariantAttribute::factory()->create(['name' => 'Zebra Attr']);
        VariantAttribute::factory()->create(['name' => 'Alpha Attr']);

        $response = $this->jsonApi()
            ->expects('variant-attributes')
            ->sort('name')
            ->get('/api/v1/variant-attributes');

        $response->assertOk();
    }

    public function test_unauthenticated_access_denied(): void
    {
        $response = $this->jsonApi()->expects('variant-attributes')->get('/api/v1/variant-attributes');
        $response->assertStatus(401);
    }
}
