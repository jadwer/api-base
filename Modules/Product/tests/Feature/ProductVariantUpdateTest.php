<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariant;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * PR-M003: Tests for ProductVariant update endpoint.
 */
class ProductVariantUpdateTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first()
            ?? User::factory()->create()->assignRole('admin');

        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    protected function getCustomerUser(): User
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'customer'))->first()
            ?? User::factory()->create()->assignRole('customer');

        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    public function test_admin_can_update_product_variant(): void
    {
        $this->getAdminUser();

        $product = Product::first() ?? Product::factory()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'sku' => 'UPD-ORIG-' . uniqid(),
            'name' => 'Original Variant',
            'price' => 99.99,
            'cost' => 49.99,
            'weight' => 1.5,
            'stock_quantity' => 100,
            'is_active' => true,
        ]);

        $data = [
            'type' => 'product-variants',
            'id' => (string) $variant->id,
            'attributes' => [
                'name' => 'Updated Variant Name',
                'price' => 129.99,
                'cost' => 59.99,
                'weight' => 2.0,
                'stockQuantity' => 200,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->withData($data)
            ->patch("/api/v1/product-variants/{$variant->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'type' => 'product-variants',
                    'id' => (string) $variant->id,
                    'attributes' => [
                        'name' => 'Updated Variant Name',
                        'price' => 129.99,
                        'cost' => 59.99,
                        'weight' => 2.0,
                        'stockQuantity' => 200,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'name' => 'Updated Variant Name',
            'price' => 129.99,
            'cost' => 59.99,
            'weight' => 2.0,
            'stock_quantity' => 200,
        ]);
    }

    public function test_can_partial_update_variant(): void
    {
        $this->getAdminUser();

        $product = Product::first() ?? Product::factory()->create();
        $originalSku = 'PARTIAL-' . uniqid();
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'sku' => $originalSku,
            'name' => 'Partial Update Variant',
            'price' => 50.00,
            'is_active' => true,
        ]);

        $data = [
            'type' => 'product-variants',
            'id' => (string) $variant->id,
            'attributes' => [
                'name' => 'Only Name Updated',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->withData($data)
            ->patch("/api/v1/product-variants/{$variant->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'name' => 'Only Name Updated',
                        'price' => 50.00,
                        'isActive' => true,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'name' => 'Only Name Updated',
            'sku' => $originalSku,
            'price' => 50.00,
        ]);
    }

    public function test_can_update_sku(): void
    {
        $this->getAdminUser();

        $product = Product::first() ?? Product::factory()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'sku' => 'OLD-SKU-' . uniqid(),
        ]);

        $newSku = 'NEW-SKU-' . uniqid();
        $data = [
            'type' => 'product-variants',
            'id' => (string) $variant->id,
            'attributes' => [
                'sku' => $newSku,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->withData($data)
            ->patch("/api/v1/product-variants/{$variant->id}");

        $response->assertOk();

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'sku' => $newSku,
        ]);
    }

    public function test_can_update_active_and_default_flags(): void
    {
        $this->getAdminUser();

        $product = Product::first() ?? Product::factory()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'is_active' => true,
            'is_default' => false,
        ]);

        $data = [
            'type' => 'product-variants',
            'id' => (string) $variant->id,
            'attributes' => [
                'isActive' => false,
                'isDefault' => true,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->withData($data)
            ->patch("/api/v1/product-variants/{$variant->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'isActive' => false,
                        'isDefault' => true,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'is_active' => false,
            'is_default' => true,
        ]);
    }

    public function test_update_fails_with_duplicate_sku(): void
    {
        $this->getAdminUser();

        $product = Product::first() ?? Product::factory()->create();
        $existingSku = 'EXISTING-VAR-SKU-' . uniqid();

        ProductVariant::factory()->forProduct($product)->create(['sku' => $existingSku]);

        $variantToUpdate = ProductVariant::factory()->forProduct($product)->create([
            'sku' => 'DIFFERENT-VAR-SKU-' . uniqid(),
        ]);

        $data = [
            'type' => 'product-variants',
            'id' => (string) $variantToUpdate->id,
            'attributes' => [
                'sku' => $existingSku,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->withData($data)
            ->patch("/api/v1/product-variants/{$variantToUpdate->id}");

        $response->assertStatus(422);
    }

    public function test_update_allows_keeping_same_sku(): void
    {
        $this->getAdminUser();

        $product = Product::first() ?? Product::factory()->create();
        $sameSku = 'SAME-VAR-SKU-' . uniqid();
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'sku' => $sameSku,
            'name' => 'Original Name',
        ]);

        $data = [
            'type' => 'product-variants',
            'id' => (string) $variant->id,
            'attributes' => [
                'name' => 'Updated Name',
                'sku' => $sameSku,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->withData($data)
            ->patch("/api/v1/product-variants/{$variant->id}");

        $response->assertOk();

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'name' => 'Updated Name',
            'sku' => $sameSku,
        ]);
    }

    public function test_unauthenticated_cannot_update_variant(): void
    {
        $product = Product::first() ?? Product::factory()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create();

        $data = [
            'type' => 'product-variants',
            'id' => (string) $variant->id,
            'attributes' => [
                'name' => 'Unauthorized Update',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->withData($data)
            ->patch("/api/v1/product-variants/{$variant->id}");

        $response->assertStatus(401);
    }

    public function test_customer_cannot_update_variant(): void
    {
        $this->getCustomerUser();

        $product = Product::first() ?? Product::factory()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create();

        $data = [
            'type' => 'product-variants',
            'id' => (string) $variant->id,
            'attributes' => [
                'name' => 'Customer Update Attempt',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->withData($data)
            ->patch("/api/v1/product-variants/{$variant->id}");

        $response->assertStatus(403);
    }
}
