<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Tests\TestCase;

/**
 * Galeria de imagenes (P1 granel, 2026-10-10): img_path y la fila primaria
 * de product_images se mantienen iguales en ambas direcciones, la primaria
 * queda al frente y los archivos huerfanos se borran del disco.
 */
class ProductImageLifecycleTest extends TestCase
{
    private function image(Product $product, string $path, array $extra = []): ProductImage
    {
        return ProductImage::create(['product_id' => $product->id, 'file_path' => $path] + $extra);
    }

    public function test_creating_a_product_with_img_path_creates_the_primary_row(): void
    {
        $product = Product::factory()->create(['img_path' => 'products/a.jpg']);

        $rows = ProductImage::where('product_id', $product->id)->get();
        $this->assertCount(1, $rows);
        $this->assertTrue($rows[0]->is_primary);
        $this->assertSame('products/a.jpg', $rows[0]->file_path);
        $this->assertSame(0, $rows[0]->sort_order);
    }

    public function test_patch_img_path_updates_the_primary_row_and_deletes_the_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/old.jpg', 'x');
        Storage::disk('public')->put('products/new.jpg', 'y');
        $product = Product::factory()->create(['img_path' => 'products/old.jpg']);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('products')
            ->withData(['type' => 'products', 'id' => (string) $product->id, 'attributes' => ['imgPath' => 'products/new.jpg']])
            ->patch("/api/v1/products/{$product->id}")
            ->assertOk();

        $rows = ProductImage::where('product_id', $product->id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame('products/new.jpg', $rows[0]->file_path);
        $this->assertTrue($rows[0]->is_primary);
        $this->assertSame('products/new.jpg', $product->fresh()->img_path);
        Storage::disk('public')->assertMissing('products/old.jpg');
        Storage::disk('public')->assertExists('products/new.jpg');
    }

    public function test_img_path_matching_a_secondary_image_promotes_it(): void
    {
        $product = Product::factory()->create(['img_path' => 'products/a.jpg']);
        $second = $this->image($product, 'products/b.jpg', ['sort_order' => 1]);

        $product->update(['img_path' => 'products/b.jpg']);

        $this->assertTrue($second->fresh()->is_primary);
        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(2, ProductImage::where('product_id', $product->id)->count());
        $this->assertSame(1, ProductImage::where('product_id', $product->id)->where('is_primary', true)->count());
    }

    public function test_clearing_img_path_removes_the_primary_row(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/a.jpg', 'x');
        $product = Product::factory()->create(['img_path' => 'products/a.jpg']);

        $product->update(['img_path' => null]);

        $this->assertSame(0, ProductImage::where('product_id', $product->id)->count());
        $this->assertNull($product->fresh()->img_path);
        Storage::disk('public')->assertMissing('products/a.jpg');
    }

    public function test_destroy_primary_promotes_the_next_image_and_syncs_img_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/a.jpg', 'x');
        Storage::disk('public')->put('products/b.jpg', 'y');
        $product = Product::factory()->create(['img_path' => 'products/a.jpg']);
        $primary = ProductImage::where('product_id', $product->id)->firstOrFail();
        $second = $this->image($product, 'products/b.jpg', ['sort_order' => 1]);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->delete("/api/v1/product-images/{$primary->id}")
            ->assertNoContent();

        $this->assertTrue($second->fresh()->is_primary);
        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame('products/b.jpg', $product->fresh()->img_path);
        Storage::disk('public')->assertMissing('products/a.jpg');
        Storage::disk('public')->assertExists('products/b.jpg');
    }

    public function test_destroy_last_image_clears_img_path(): void
    {
        $product = Product::factory()->create(['img_path' => 'products/a.jpg']);
        $only = ProductImage::where('product_id', $product->id)->firstOrFail();

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->delete("/api/v1/product-images/{$only->id}")
            ->assertNoContent();

        $this->assertNull($product->fresh()->img_path);
    }

    public function test_replacing_the_file_of_the_primary_syncs_img_path_and_deletes_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/a.jpg', 'x');
        Storage::disk('public')->put('products/c.jpg', 'z');
        $product = Product::factory()->create(['img_path' => 'products/a.jpg']);
        $primary = ProductImage::where('product_id', $product->id)->firstOrFail();

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('product-images')
            ->withData(['type' => 'product-images', 'id' => (string) $primary->id, 'attributes' => ['filePath' => 'products/c.jpg']])
            ->patch("/api/v1/product-images/{$primary->id}")
            ->assertOk();

        $this->assertSame('products/c.jpg', $product->fresh()->img_path);
        Storage::disk('public')->assertMissing('products/a.jpg');
    }

    public function test_shared_file_is_kept_when_another_product_uses_it(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/shared.jpg', 'x');
        $a = Product::factory()->create(['img_path' => 'products/shared.jpg']);
        Product::factory()->create(['img_path' => 'shared.jpg']);

        ProductImage::where('product_id', $a->id)->firstOrFail()->delete();

        Storage::disk('public')->assertExists('products/shared.jpg');
    }

    public function test_files_outside_products_folder_are_never_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('datasheets/x.pdf', 'x');
        $product = Product::factory()->create();
        $row = $this->image($product, '/storage/datasheets/x.pdf');

        $row->delete();

        Storage::disk('public')->assertExists('datasheets/x.pdf');
    }

    public function test_orphan_deletion_can_be_disabled_by_config(): void
    {
        config(['products.delete_orphan_files' => false]);
        Storage::fake('public');
        Storage::disk('public')->put('products/a.jpg', 'x');
        $product = Product::factory()->create(['img_path' => 'products/a.jpg']);

        ProductImage::where('product_id', $product->id)->firstOrFail()->delete();

        Storage::disk('public')->assertExists('products/a.jpg');
    }

    public function test_set_primary_moves_the_image_to_the_front(): void
    {
        $product = Product::factory()->create(['img_path' => 'products/a.jpg']);
        $b = $this->image($product, 'products/b.jpg', ['sort_order' => 1]);
        $c = $this->image($product, 'products/c.jpg', ['sort_order' => 2]);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/product-images/{$c->id}/set-primary")
            ->assertOk()
            ->assertJsonPath('data.attributes.sortOrder', 0);

        $order = ProductImage::where('product_id', $product->id)->orderBy('sort_order')->pluck('file_path')->all();
        $this->assertSame(['products/c.jpg', 'products/a.jpg', 'products/b.jpg'], $order);
        $this->assertSame('products/c.jpg', $product->fresh()->img_path);
        $this->assertSame(2, $b->fresh()->sort_order);
    }

    public function test_uploading_first_image_for_legacy_product_replaces_and_removes_legacy_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/legacy.jpg', 'x');
        $product = Product::factory()->create();
        // Dato legado: img_path sin fila en la galeria (escrito sin eventos).
        Product::query()->whereKey($product->id)->update(['img_path' => 'legacy.jpg']);

        $this->image($product, 'products/new.jpg');

        $this->assertSame('products/new.jpg', $product->fresh()->img_path);
        Storage::disk('public')->assertMissing('products/legacy.jpg');
    }

    public function test_deleting_a_product_removes_its_orphan_image_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/a.jpg', 'x');
        Storage::disk('public')->put('products/b.jpg', 'y');
        $product = Product::factory()->create(['img_path' => 'products/a.jpg']);
        $this->image($product, 'products/b.jpg');

        $product->delete();

        $this->assertSame(0, ProductImage::where('product_id', $product->id)->count());
        Storage::disk('public')->assertMissing('products/a.jpg');
        Storage::disk('public')->assertMissing('products/b.jpg');
    }

    public function test_product_images_relationship_is_read_only(): void
    {
        $product = Product::factory()->create();
        $other = Product::factory()->create(['img_path' => 'products/x.jpg']);
        $image = ProductImage::where('product_id', $other->id)->firstOrFail();

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('products')
            ->withData([
                'type' => 'products',
                'id' => (string) $product->id,
                'relationships' => ['images' => ['data' => [['type' => 'product-images', 'id' => (string) $image->id]]]],
            ])
            ->patch("/api/v1/products/{$product->id}");

        $this->assertSame($other->id, $image->fresh()->product_id);
    }
}
