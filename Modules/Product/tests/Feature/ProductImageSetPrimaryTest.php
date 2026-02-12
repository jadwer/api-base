<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;

class ProductImageSetPrimaryTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first()
            ?? User::factory()->create()->assignRole('admin');

        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    public function test_admin_can_set_primary_image(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();
        $img1 = ProductImage::factory()->forProduct($product)->primary()->create([
            'sort_order' => 0,
            'file_path' => 'products/old-primary.webp',
        ]);
        $img2 = ProductImage::factory()->forProduct($product)->create([
            'sort_order' => 1,
            'is_primary' => false,
            'file_path' => 'products/new-primary.webp',
        ]);

        $response = $this->postJson("/api/v1/product-images/{$img2->id}/set-primary");

        $response->assertOk()
            ->assertJson([
                'message' => 'Image set as primary successfully',
                'data' => [
                    'attributes' => [
                        'isPrimary' => true,
                    ],
                ],
            ]);

        $img1->refresh();
        $img2->refresh();
        $this->assertFalse($img1->is_primary);
        $this->assertTrue($img2->is_primary);

        // Product img_path synced
        $product->refresh();
        $this->assertEquals('products/new-primary.webp', $product->img_path);
    }

    public function test_set_primary_returns_404_for_nonexistent(): void
    {
        $this->getAdminUser();

        $response = $this->postJson('/api/v1/product-images/999999/set-primary');
        $response->assertStatus(404);
    }

    public function test_unauthenticated_set_primary_denied(): void
    {
        $product = Product::factory()->create();
        $image = ProductImage::factory()->forProduct($product)->create();

        $response = $this->postJson("/api/v1/product-images/{$image->id}/set-primary");
        $response->assertStatus(401);
    }
}
