<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;

class ProductImageIndexTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first()
            ?? User::factory()->create()->assignRole('admin');

        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    public function test_admin_can_list_product_images(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();
        ProductImage::factory()->forProduct($product)->create();

        $response = $this->jsonApi()->expects('product-images')->get('/api/v1/product-images');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'attributes' => ['filePath', 'sortOrder', 'isPrimary'],
                    ],
                ],
            ]);
    }

    public function test_can_filter_by_product_id(): void
    {
        $this->getAdminUser();

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        ProductImage::factory()->forProduct($product1)->create();
        ProductImage::factory()->forProduct($product2)->create();

        $response = $this->jsonApi()
            ->expects('product-images')
            ->filter(['product_id' => $product1->id])
            ->get('/api/v1/product-images');

        $response->assertOk();
        $data = $response->json('data');
        foreach ($data as $image) {
            $dbImage = ProductImage::find($image['id']);
            $this->assertEquals($product1->id, $dbImage->product_id);
        }
    }

    public function test_can_filter_by_is_primary(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();
        ProductImage::factory()->forProduct($product)->primary()->create();
        ProductImage::factory()->forProduct($product)->create(['is_primary' => false, 'sort_order' => 1]);

        $response = $this->jsonApi()
            ->expects('product-images')
            ->filter(['is_primary' => '1'])
            ->get('/api/v1/product-images');

        $response->assertOk();
        $data = $response->json('data');
        foreach ($data as $image) {
            $this->assertTrue($image['attributes']['isPrimary']);
        }
    }

    public function test_can_include_product_relationship(): void
    {
        $this->getAdminUser();

        $product = Product::factory()->create();
        ProductImage::factory()->forProduct($product)->create();

        $response = $this->jsonApi()
            ->expects('product-images')
            ->includePaths('product')
            ->get('/api/v1/product-images');

        $response->assertOk();
    }

    public function test_unauthenticated_access_denied(): void
    {
        $response = $this->jsonApi()->expects('product-images')->get('/api/v1/product-images');
        $response->assertStatus(401);
    }
}
