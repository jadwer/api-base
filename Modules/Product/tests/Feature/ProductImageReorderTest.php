<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;

class ProductImageReorderTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first()
            ?? User::factory()->create()->assignRole('admin');

        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    public function test_admin_can_reorder_images(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();
        $img1 = ProductImage::factory()->forProduct($product)->create(['sort_order' => 0]);
        $img2 = ProductImage::factory()->forProduct($product)->create(['sort_order' => 1, 'is_primary' => false]);
        $img3 = ProductImage::factory()->forProduct($product)->create(['sort_order' => 2, 'is_primary' => false]);

        // Reverse the order: 3, 1, 2
        $response = $this->postJson('/api/v1/product-images/reorder', [
            'image_ids' => [$img3->id, $img1->id, $img2->id],
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Images reordered successfully']);

        $img1->refresh();
        $img2->refresh();
        $img3->refresh();

        $this->assertEquals(1, $img1->sort_order);
        $this->assertEquals(2, $img2->sort_order);
        $this->assertEquals(0, $img3->sort_order);
    }

    public function test_reorder_rejects_mixed_product_images(): void
    {
        $this->getAdminUser();

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $img1 = ProductImage::factory()->forProduct($product1)->create();
        $img2 = ProductImage::factory()->forProduct($product2)->create();

        $response = $this->postJson('/api/v1/product-images/reorder', [
            'image_ids' => [$img1->id, $img2->id],
        ]);

        $response->assertStatus(422);
    }

    public function test_reorder_validation_requires_image_ids(): void
    {
        $this->getAdminUser();

        $response = $this->postJson('/api/v1/product-images/reorder', []);

        $response->assertStatus(422);
    }

    public function test_reorder_validation_rejects_nonexistent_ids(): void
    {
        $this->getAdminUser();

        $response = $this->postJson('/api/v1/product-images/reorder', [
            'image_ids' => [999998, 999999],
        ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_reorder_denied(): void
    {
        $response = $this->postJson('/api/v1/product-images/reorder', [
            'image_ids' => [1, 2],
        ]);

        $response->assertStatus(401);
    }
}
