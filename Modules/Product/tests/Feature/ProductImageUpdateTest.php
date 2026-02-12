<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;

class ProductImageUpdateTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first()
            ?? User::factory()->create()->assignRole('admin');

        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    public function test_admin_can_update_alt_text(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();
        $image = ProductImage::factory()->forProduct($product)->create([
            'alt_text' => 'Old text',
        ]);

        $data = [
            'type' => 'product-images',
            'id' => (string) $image->id,
            'attributes' => [
                'altText' => 'Updated alt text',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-images')
            ->withData($data)
            ->patch("/api/v1/product-images/{$image->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'altText' => 'Updated alt text',
                    ],
                ],
            ]);
    }

    public function test_admin_can_update_sort_order(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();
        $image = ProductImage::factory()->forProduct($product)->create(['sort_order' => 0]);

        $data = [
            'type' => 'product-images',
            'id' => (string) $image->id,
            'attributes' => [
                'sortOrder' => 5,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-images')
            ->withData($data)
            ->patch("/api/v1/product-images/{$image->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'sortOrder' => 5,
                    ],
                ],
            ]);
    }

    public function test_setting_primary_unsets_other_primaries(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();
        $image1 = ProductImage::factory()->forProduct($product)->primary()->create(['sort_order' => 0]);
        $image2 = ProductImage::factory()->forProduct($product)->create(['sort_order' => 1, 'is_primary' => false]);

        $data = [
            'type' => 'product-images',
            'id' => (string) $image2->id,
            'attributes' => [
                'isPrimary' => true,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-images')
            ->withData($data)
            ->patch("/api/v1/product-images/{$image2->id}");

        $response->assertOk();

        $image1->refresh();
        $image2->refresh();
        $this->assertFalse($image1->is_primary);
        $this->assertTrue($image2->is_primary);

        // Product img_path should be synced
        $product->refresh();
        $this->assertEquals($image2->file_path, $product->img_path);
    }

    public function test_unauthenticated_update_denied(): void
    {
        $product = Product::factory()->create();
        $image = ProductImage::factory()->forProduct($product)->create();

        $data = [
            'type' => 'product-images',
            'id' => (string) $image->id,
            'attributes' => [
                'altText' => 'Should fail',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('product-images')
            ->withData($data)
            ->patch("/api/v1/product-images/{$image->id}");

        $response->assertStatus(401);
    }
}
