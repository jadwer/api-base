<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;

class ProductImageDestroyTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first()
            ?? User::factory()->create()->assignRole('admin');

        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    public function test_admin_can_delete_product_image(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();
        $image = ProductImage::factory()->forProduct($product)->create(['is_primary' => false]);

        $response = $this->jsonApi()
            ->expects('product-images')
            ->delete("/api/v1/product-images/{$image->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
    }

    public function test_deleting_primary_promotes_next_image(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();
        $primary = ProductImage::factory()->forProduct($product)->primary()->create([
            'sort_order' => 0,
            'file_path' => 'products/primary.webp',
        ]);
        $secondary = ProductImage::factory()->forProduct($product)->create([
            'sort_order' => 1,
            'is_primary' => false,
            'file_path' => 'products/secondary.webp',
        ]);

        $response = $this->jsonApi()
            ->expects('product-images')
            ->delete("/api/v1/product-images/{$primary->id}");

        $response->assertNoContent();

        $secondary->refresh();
        $this->assertTrue($secondary->is_primary);

        $product->refresh();
        $this->assertEquals('products/secondary.webp', $product->img_path);
    }

    public function test_deleting_last_image_clears_product_img_path(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create(['img_path' => 'products/only-image.webp']);

        // Clear any existing images and create a single one
        ProductImage::where('product_id', $product->id)->delete();
        $image = ProductImage::factory()->forProduct($product)->primary()->create([
            'file_path' => 'products/only-image.webp',
        ]);

        $response = $this->jsonApi()
            ->expects('product-images')
            ->delete("/api/v1/product-images/{$image->id}");

        $response->assertNoContent();

        $product->refresh();
        $this->assertNull($product->img_path);
    }

    public function test_unauthenticated_destroy_denied(): void
    {
        $product = Product::factory()->create();
        $image = ProductImage::factory()->forProduct($product)->create();

        $response = $this->jsonApi()
            ->expects('product-images')
            ->delete("/api/v1/product-images/{$image->id}");

        $response->assertStatus(401);
    }

    public function test_destroy_returns_404_for_nonexistent(): void
    {
        $this->getAdminUser();

        $response = $this->jsonApi()
            ->expects('product-images')
            ->delete('/api/v1/product-images/999999');

        $response->assertStatus(404);
    }
}
