<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Tests\TestCase;

/**
 * SEO Bloque 1b: el catalogo publico expone slug y permite resolver la ficha
 * por filter[slug]; el admin lo ve pero no lo edita.
 */
class PublicProductSlugTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge([
            'unit_id' => Unit::factory()->create()->id,
            'category_id' => Category::factory()->create(['is_active' => true])->id,
            'brand_id' => Brand::factory()->create(['is_active' => true])->id,
            'is_active' => true,
            'is_public' => true,
        ], $attrs));
    }

    public function test_public_index_and_show_expose_slug(): void
    {
        $product = $this->makeProduct(['sku' => 'SLUG-IDX-1']);

        // El TestCase siembra productos demo: se acota por sku para no depender del orden
        $this->jsonApi()->get('/api/public/v1/public-products?filter[sku]=SLUG-IDX-1')
            ->assertOk()
            ->assertJsonPath('data.0.attributes.slug', $product->fresh()->slug);

        $this->jsonApi()->get("/api/public/v1/public-products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.attributes.slug', $product->fresh()->slug);
    }

    public function test_public_index_can_filter_by_slug(): void
    {
        $this->makeProduct(['name' => 'Otro producto', 'sku' => 'OT-1']);
        $target = $this->makeProduct(['name' => 'Objetivo', 'sku' => 'OB-1']);
        $slug = $target->fresh()->slug;

        $response = $this->jsonApi()
            ->get('/api/public/v1/public-products?filter[slug]=' . $slug)
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame((string) $target->id, $response->json('data.0.id'));
    }

    public function test_filter_by_unknown_slug_returns_empty_collection(): void
    {
        $this->makeProduct();

        $this->jsonApi()
            ->get('/api/public/v1/public-products?filter[slug]=no-existe')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_admin_sees_slug_but_cannot_write_it(): void
    {
        $product = $this->makeProduct();
        $admin = $this->getAdminUser();

        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->get("/api/v1/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.attributes.slug', $product->fresh()->slug);

        $original = $product->fresh()->slug;

        // readOnly en JSON:API: el atributo se ignora en escritura (no se
        // rechaza). La propiedad que importa es que el slug NO cambie.
        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->withData([
                'type' => 'products',
                'id' => (string) $product->id,
                'attributes' => ['slug' => 'hackeado', 'name' => 'Nombre nuevo'],
            ])
            ->patch("/api/v1/products/{$product->id}");

        $this->assertContains($response->getStatusCode(), [200, 400, 422]);
        $this->assertSame($original, $product->fresh()->slug, 'el slug es estable y no se edita por API');
    }
}
