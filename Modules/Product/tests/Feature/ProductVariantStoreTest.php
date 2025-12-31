<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariant;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * PR-M003: Tests for ProductVariant store endpoint.
 */
class ProductVariantStoreTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first()
            ?? User::factory()->create()->assignRole('admin');

        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    public function test_admin_can_create_product_variant(): void
    {
        $this->getAdminUser();

        $product = Product::first() ?? Product::factory()->create();

        $data = [
            'type' => 'product-variants',
            'attributes' => [
                'sku' => 'TEST-VAR-' . uniqid(),
                'name' => 'Test Variant Red Large',
                'price' => 99.99,
                'cost' => 49.99,
                'stockQuantity' => 100,
                'isActive' => true,
                'isDefault' => false,
            ],
            'relationships' => [
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->withData($data)
            ->post('/api/v1/product-variants');

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'type' => 'product-variants',
                    'attributes' => [
                        'name' => 'Test Variant Red Large',
                        'price' => 99.99,
                        'isActive' => true,
                    ],
                ],
            ]);
    }

    public function test_validation_requires_product_relationship(): void
    {
        $this->getAdminUser();

        $data = [
            'type' => 'product-variants',
            'attributes' => [
                'sku' => 'TEST-NO-PROD-' . uniqid(),
                'name' => 'Missing Product',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->withData($data)
            ->post('/api/v1/product-variants');

        $response->assertStatus(422);
    }

    public function test_validation_requires_unique_sku(): void
    {
        $this->getAdminUser();

        $product = Product::first() ?? Product::factory()->create();
        $existingSku = 'UNIQUE-SKU-TEST-' . uniqid();
        ProductVariant::factory()->forProduct($product)->create(['sku' => $existingSku]);

        $data = [
            'type' => 'product-variants',
            'attributes' => [
                'sku' => $existingSku, // Duplicate
            ],
            'relationships' => [
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->withData($data)
            ->post('/api/v1/product-variants');

        $response->assertStatus(422);
    }

    public function test_can_create_variant_with_weight_and_barcode(): void
    {
        $this->getAdminUser();

        $product = Product::first() ?? Product::factory()->create();

        $data = [
            'type' => 'product-variants',
            'attributes' => [
                'sku' => 'WEIGHT-BC-' . uniqid(),
                'weight' => 2.5,
                'barcode' => '1234567890123',
                'stockQuantity' => 50,
                'lowStockThreshold' => 10,
            ],
            'relationships' => [
                'product' => [
                    'data' => [
                        'type' => 'products',
                        'id' => (string) $product->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->withData($data)
            ->post('/api/v1/product-variants');

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'weight' => 2.5,
                        'barcode' => '1234567890123',
                        'lowStockThreshold' => 10,
                    ],
                ],
            ]);
    }
}
