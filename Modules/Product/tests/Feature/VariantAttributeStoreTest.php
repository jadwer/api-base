<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\Product\Models\VariantAttribute;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * PR-M003: Tests for VariantAttribute store endpoint.
 */
class VariantAttributeStoreTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first()
            ?? User::factory()->create()->assignRole('admin');

        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    public function test_admin_can_create_variant_attribute(): void
    {
        $this->getAdminUser();

        $data = [
            'type' => 'variant-attributes',
            'attributes' => [
                'name' => 'Material Test',
                'code' => 'material-test-' . uniqid(),
                'description' => 'Product material type',
                'isActive' => true,
                'sortOrder' => 10,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('variant-attributes')
            ->withData($data)
            ->post('/api/v1/variant-attributes');

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'type' => 'variant-attributes',
                    'attributes' => [
                        'name' => 'Material Test',
                        'isActive' => true,
                        'sortOrder' => 10,
                    ],
                ],
            ]);
    }

    public function test_validation_requires_name(): void
    {
        $this->getAdminUser();

        $data = [
            'type' => 'variant-attributes',
            'attributes' => [
                'code' => 'test-no-name-' . uniqid(),
            ],
        ];

        $response = $this->jsonApi()
            ->expects('variant-attributes')
            ->withData($data)
            ->post('/api/v1/variant-attributes');

        $response->assertStatus(422);
    }

    public function test_validation_requires_unique_code(): void
    {
        $this->getAdminUser();

        $existing = VariantAttribute::factory()->create(['code' => 'unique-code-test']);

        $data = [
            'type' => 'variant-attributes',
            'attributes' => [
                'name' => 'Duplicate Code',
                'code' => 'unique-code-test', // Same code
            ],
        ];

        $response = $this->jsonApi()
            ->expects('variant-attributes')
            ->withData($data)
            ->post('/api/v1/variant-attributes');

        $response->assertStatus(422);
    }
}
