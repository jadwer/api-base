<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariant;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * PR-M003: Tests for ProductVariant destroy endpoint.
 */
class ProductVariantDestroyTest extends TestCase
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

    public function test_admin_can_delete_product_variant(): void
    {
        $this->getAdminUser();

        $product = Product::first() ?? Product::factory()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'sku' => 'DEL-VAR-' . uniqid(),
            'name' => 'Variant to Delete',
        ]);

        $variantId = $variant->id;

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->delete("/api/v1/product-variants/{$variantId}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('product_variants', [
            'id' => $variantId,
        ]);
    }

    public function test_deleted_variant_no_longer_accessible(): void
    {
        $this->getAdminUser();

        $product = Product::first() ?? Product::factory()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'sku' => 'DEL-ACCESS-' . uniqid(),
        ]);

        $variantId = $variant->id;

        $this->jsonApi()
            ->expects('product-variants')
            ->delete("/api/v1/product-variants/{$variantId}")
            ->assertNoContent();

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->get("/api/v1/product-variants/{$variantId}");

        $response->assertNotFound();
    }

    public function test_unauthenticated_cannot_delete_variant(): void
    {
        $product = Product::first() ?? Product::factory()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create();

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->delete("/api/v1/product-variants/{$variant->id}");

        $response->assertStatus(401);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
        ]);
    }

    public function test_customer_cannot_delete_variant(): void
    {
        $this->getCustomerUser();

        $product = Product::first() ?? Product::factory()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create();

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->delete("/api/v1/product-variants/{$variant->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
        ]);
    }

    public function test_delete_nonexistent_variant_returns_404(): void
    {
        $this->getAdminUser();

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->delete('/api/v1/product-variants/99999');

        $response->assertNotFound();
    }
}
