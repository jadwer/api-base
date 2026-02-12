<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;

class ProductImageShowTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first()
            ?? User::factory()->create()->assignRole('admin');

        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    public function test_admin_can_show_product_image(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();
        $image = ProductImage::factory()->forProduct($product)->create();

        $response = $this->jsonApi()
            ->expects('product-images')
            ->get("/api/v1/product-images/{$image->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'type' => 'product-images',
                    'id' => (string) $image->id,
                    'attributes' => [
                        'filePath' => $image->file_path,
                        'sortOrder' => $image->sort_order,
                    ],
                ],
            ]);
    }

    public function test_show_includes_image_url(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();
        $image = ProductImage::factory()->forProduct($product)->create([
            'file_path' => 'products/test-uuid.webp',
        ]);

        $response = $this->jsonApi()
            ->expects('product-images')
            ->get("/api/v1/product-images/{$image->id}");

        $response->assertOk();
        $imageUrl = $response->json('data.attributes.imageUrl');
        $this->assertNotNull($imageUrl);
        $this->assertStringContainsString('test-uuid.webp', $imageUrl);
    }

    public function test_show_can_include_product(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();
        $image = ProductImage::factory()->forProduct($product)->create();

        $response = $this->jsonApi()
            ->expects('product-images')
            ->includePaths('product')
            ->get("/api/v1/product-images/{$image->id}");

        $response->assertOk();
        $this->assertNotNull($response->json('included'));
    }

    public function test_show_returns_404_for_nonexistent(): void
    {
        $this->getAdminUser();

        $response = $this->jsonApi()
            ->expects('product-images')
            ->get('/api/v1/product-images/999999');

        $response->assertStatus(404);
    }

    public function test_unauthenticated_access_denied(): void
    {
        $product = Product::factory()->create();
        $image = ProductImage::factory()->forProduct($product)->create();

        $response = $this->jsonApi()
            ->expects('product-images')
            ->get("/api/v1/product-images/{$image->id}");

        $response->assertStatus(401);
    }
}
