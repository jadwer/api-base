<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariant;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * PR-M003: Tests for ProductVariant index endpoint.
 */
class ProductVariantIndexTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first()
            ?? User::factory()->create()->assignRole('admin');

        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    public function test_admin_can_list_product_variants(): void
    {
        $this->getAdminUser();

        $product = Product::first() ?? Product::factory()->create();
        ProductVariant::factory()->forProduct($product)->create();

        $response = $this->jsonApi()->expects('product-variants')->get('/api/v1/product-variants');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'attributes' => ['sku', 'isActive', 'stockQuantity'],
                    ],
                ],
            ]);
    }

    public function test_can_filter_by_product_id(): void
    {
        $this->getAdminUser();

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        ProductVariant::factory()->forProduct($product1)->create(['sku' => 'FILTER-P1-' . uniqid()]);
        ProductVariant::factory()->forProduct($product2)->create(['sku' => 'FILTER-P2-' . uniqid()]);

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->filter(['product_id' => $product1->id])
            ->get('/api/v1/product-variants');

        $response->assertOk();
        $data = $response->json('data');
        foreach ($data as $variant) {
            $this->assertStringContains('FILTER-P1', $variant['attributes']['sku']);
        }
    }

    public function test_can_include_product_relationship(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();
        ProductVariant::factory()->forProduct($product)->create();

        $response = $this->jsonApi()
            ->expects('product-variants')
            ->includePaths('product')
            ->get('/api/v1/product-variants');

        $response->assertOk();
    }

    public function test_unauthenticated_access_denied(): void
    {
        $response = $this->jsonApi()->expects('product-variants')->get('/api/v1/product-variants');
        $response->assertStatus(401);
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '$haystack' contains '$needle'"
        );
    }
}
