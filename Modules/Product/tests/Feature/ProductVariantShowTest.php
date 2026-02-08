<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariant;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * PR-M003: Tests for ProductVariant show endpoint.
 */
class ProductVariantShowTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first()
            ?? User::factory()->create()->assignRole('admin');

        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    public function test_admin_can_view_product_variant(): void
    {
        $this->getAdminUser();

        $product = Product::first() ?? Product::factory()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'sku' => 'SHOW-TEST-' . uniqid(),
            'name' => 'Show Test Variant',
            'price' => 149.99,
            'stockQuantity' => 50,
            'is_active' => true,
        ]);

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->get("/api/v1/product-variants/{$variant->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'type' => 'product-variants',
                    'id' => (string) $variant->id,
                    'attributes' => [
                        'name' => 'Show Test Variant',
                        'price' => 149.99,
                        'isActive' => true,
                    ],
                ],
            ]);
    }

    public function test_response_contains_expected_attributes(): void
    {
        $this->getAdminUser();

        $product = Product::first() ?? Product::factory()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'sku' => 'ATTR-TEST-' . uniqid(),
            'weight' => 3.5,
            'barcode' => '9876543210123',
            'stock_quantity' => 75,
            'low_stock_threshold' => 15,
        ]);

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->get("/api/v1/product-variants/{$variant->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'sku',
                        'weight',
                        'barcode',
                        'stockQuantity',
                        'lowStockThreshold',
                        'isActive',
                        'isDefault',
                    ],
                ],
            ]);
    }

    public function test_can_include_product_relationship(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create();

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->includePaths('product')
            ->get("/api/v1/product-variants/{$variant->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'product' => ['data' => ['id', 'type']],
                ],
            ],
            'included' => [
                '*' => ['id', 'type', 'attributes'],
            ],
        ]);
    }

    public function test_unauthenticated_access_denied(): void
    {
        $product = Product::first() ?? Product::factory()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create();

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->get("/api/v1/product-variants/{$variant->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_variant(): void
    {
        $this->getAdminUser();

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->get('/api/v1/product-variants/99999');

        $response->assertNotFound();
    }
}
