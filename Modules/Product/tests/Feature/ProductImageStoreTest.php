<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;

class ProductImageStoreTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first()
            ?? User::factory()->create()->assignRole('admin');

        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    public function test_admin_can_create_product_image(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();

        $data = [
            'type' => 'product-images',
            'attributes' => [
                'filePath' => 'products/test-image.webp',
                'altText' => 'Test product image',
                'sortOrder' => 0,
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
            ->expects('product-images')
            ->withData($data)
            ->post('/api/v1/product-images');

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'type' => 'product-images',
                    'attributes' => [
                        'filePath' => 'products/test-image.webp',
                        'altText' => 'Test product image',
                    ],
                ],
            ]);
    }

    public function test_first_image_auto_sets_primary(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create(['img_path' => null]);

        // Ensure no images exist for this product
        ProductImage::where('product_id', $product->id)->delete();

        $data = [
            'type' => 'product-images',
            'attributes' => [
                'filePath' => 'products/first-image.webp',
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
            ->expects('product-images')
            ->withData($data)
            ->post('/api/v1/product-images');

        $response->assertCreated();

        $imageId = $response->json('data.id');
        $image = ProductImage::find($imageId);
        $this->assertTrue($image->is_primary);

        // Product img_path should be synced
        $product->refresh();
        $this->assertEquals('products/first-image.webp', $product->img_path);
    }

    public function test_validation_requires_file_path(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();

        $data = [
            'type' => 'product-images',
            'attributes' => [
                'altText' => 'No file path',
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
            ->expects('product-images')
            ->withData($data)
            ->post('/api/v1/product-images');

        $response->assertStatus(422);
    }

    public function test_validation_rejects_invalid_file_path(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();

        $data = [
            'type' => 'product-images',
            'attributes' => [
                'filePath' => '../../../etc/passwd',
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
            ->expects('product-images')
            ->withData($data)
            ->post('/api/v1/product-images');

        $response->assertStatus(422);
    }

    public function test_unauthenticated_store_denied(): void
    {
        $product = Product::factory()->create();

        $data = [
            'type' => 'product-images',
            'attributes' => [
                'filePath' => 'products/test.webp',
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
            ->expects('product-images')
            ->withData($data)
            ->post('/api/v1/product-images');

        $response->assertStatus(401);
    }
}
