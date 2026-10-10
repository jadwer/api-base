<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\Product\Services\StorefrontRevalidator;
use Tests\TestCase;

/**
 * Revalidacion bajo demanda de la ficha publica (P1 granel, decision D5).
 */
class StorefrontRevalidationTest extends TestCase
{
    private function configure(): void
    {
        config([
            'services.storefront.revalidate_url' => 'https://tienda.test/api/revalidate',
            'services.storefront.revalidate_token' => 'secreto',
        ]);
    }

    public function test_disabled_without_config(): void
    {
        Http::fake();
        $product = Product::factory()->create(['img_path' => 'products/a.jpg']);
        $product->update(['name' => 'Otro nombre']);

        $revalidator = app(StorefrontRevalidator::class);
        $this->assertFalse($revalidator->enabled());
        $this->assertSame([], $revalidator->pending());
        $revalidator->flush();
        Http::assertNothingSent();
    }

    public function test_image_change_sends_one_request_with_product_paths_and_tags(): void
    {
        $this->configure();
        Http::fake(['tienda.test/*' => Http::response(['revalidated' => true])]);
        $product = Product::factory()->create(['img_path' => 'products/a.jpg']);
        $revalidator = app(StorefrontRevalidator::class);
        $revalidator->flush();
        Http::assertSentCount(1);

        $image = ProductImage::create(['product_id' => $product->id, 'file_path' => 'products/b.jpg']);
        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/product-images/{$image->id}/set-primary")
            ->assertOk();

        // El kernel termina la peticion (terminating) y ahi se envia el aviso.
        Http::assertSentCount(2);
        Http::assertSent(function ($request) use ($product) {
            $slug = $product->fresh()->slug;

            return $request->url() === 'https://tienda.test/api/revalidate'
                && $request->hasHeader('Authorization', 'Bearer secreto')
                && in_array("/productos/{$slug}", $request['paths'], true)
                && in_array("public-product:{$product->id}", $request['tags'], true)
                && in_array("public-product-slug:{$slug}", $request['tags'], true)
                && in_array('public-catalog', $request['tags'], true);
        });
        $this->assertSame([], app(StorefrontRevalidator::class)->pending());
    }

    public function test_counter_only_changes_do_not_revalidate(): void
    {
        $this->configure();
        Http::fake();
        $product = Product::factory()->create();
        $revalidator = app(StorefrontRevalidator::class);
        $revalidator->flush();

        $product->update(['total_sales' => 99]);

        $this->assertSame([], $revalidator->pending());
    }

    public function test_bulk_changes_collapse_to_catalog_tags(): void
    {
        $this->configure();
        Http::fake();
        $revalidator = app(StorefrontRevalidator::class);
        Product::factory()->count(StorefrontRevalidator::MAX_PRODUCTS + 1)->create();

        $this->assertSame(['paths' => [], 'tags' => ['public-products', 'public-catalog']], $revalidator->payload());
    }

    public function test_frontend_failure_is_logged_and_never_throws(): void
    {
        $this->configure();
        Http::fake(['*' => Http::response('boom', 500)]);
        $product = Product::factory()->create();
        $revalidator = app(StorefrontRevalidator::class);
        $revalidator->product($product);

        $revalidator->flush();

        Http::assertSentCount(1);
        $this->assertSame([], $revalidator->pending());
    }
}
