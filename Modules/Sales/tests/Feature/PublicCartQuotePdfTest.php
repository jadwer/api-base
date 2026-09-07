<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AppConfig\Models\AppSetting;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Sales\Services\CartQuotePdfService;
use Tests\TestCase;

/**
 * Cotizacion informativa publica del carrito (pedido cliente 2026-09-01).
 * Invariantes: endpoint anonimo, precios SIEMPRE desde la tabla products
 * (regla 7), recorte identico al catalogo publico, y el desglose de IVA
 * cuadra en AMBOS modos del tenant (R7).
 */
class PublicCartQuotePdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // El throttle real (10/min) persiste entre corridas con cache no-array
        // y convierte 422 esperados en 429; se prueba aparte que la ruta lo tiene.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    public function test_route_is_public_and_throttled(): void
    {
        $route = collect(app('router')->getRoutes())->first(
            fn ($r) => $r->uri() === 'api/v1/public/cart-quote-pdf'
        );

        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();
        $this->assertContains('throttle:10,1', $middleware);
        $this->assertNotContains('auth:sanctum', $middleware);
    }

    protected function makePublicProduct(array $overrides = []): Product
    {
        $brand = Brand::factory()->create(['is_active' => true]);
        $category = Category::factory()->create(['is_active' => true]);

        return Product::factory()->create(array_merge([
            'is_active' => true,
            'is_public' => true,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'price' => 100,
            'tax_rate' => 16,
            'iva' => true,
        ], $overrides));
    }

    public function test_guest_can_download_informative_pdf(): void
    {
        $product = $this->makePublicProduct();

        $response = $this->postJson('/api/v1/public/cart-quote-pdf', [
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_validation_rejects_empty_and_invalid_payloads(): void
    {
        $this->postJson('/api/v1/public/cart-quote-pdf', ['items' => []])
            ->assertStatus(422);

        $this->postJson('/api/v1/public/cart-quote-pdf', [
            'items' => [['product_id' => 1, 'quantity' => 0]],
        ])->assertStatus(422);

        $this->postJson('/api/v1/public/cart-quote-pdf', [
            'items' => [['product_id' => 'abc', 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_non_public_products_are_excluded_and_alone_yield_422(): void
    {
        $hidden = $this->makePublicProduct(['is_public' => false]);

        $this->postJson('/api/v1/public/cart-quote-pdf', [
            'items' => [['product_id' => $hidden->id, 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_lines_priced_from_db_in_tax_excluded_mode(): void
    {
        AppSetting::set('pricing.prices_include_tax', false, 'pricing', 'boolean');
        $product = $this->makePublicProduct(['price' => 100, 'tax_rate' => 16]);

        $priced = app(CartQuotePdfService::class)->buildLines([
            ['product_id' => $product->id, 'quantity' => 2],
        ]);

        $this->assertCount(1, $priced['lines']);
        $this->assertEquals(200.0, $priced['totals']['net']);
        $this->assertEquals(32.0, $priced['totals']['tax']);
        $this->assertEquals(232.0, $priced['totals']['total']);
    }

    public function test_lines_priced_from_db_in_tax_included_mode(): void
    {
        AppSetting::set('pricing.prices_include_tax', true, 'pricing', 'boolean');
        $product = $this->makePublicProduct(['price' => 116, 'tax_rate' => 16]);

        $priced = app(CartQuotePdfService::class)->buildLines([
            ['product_id' => $product->id, 'quantity' => 1],
        ]);

        $this->assertEquals(100.0, $priced['totals']['net']);
        $this->assertEquals(16.0, $priced['totals']['tax']);
        $this->assertEquals(116.0, $priced['totals']['total']);
    }

    public function test_duplicated_product_ids_accumulate_quantity(): void
    {
        AppSetting::set('pricing.prices_include_tax', false, 'pricing', 'boolean');
        $product = $this->makePublicProduct(['price' => 10, 'tax_rate' => 0, 'iva' => false]);

        $priced = app(CartQuotePdfService::class)->buildLines([
            ['product_id' => $product->id, 'quantity' => 2],
            ['product_id' => $product->id, 'quantity' => 3],
        ]);

        $this->assertCount(1, $priced['lines']);
        $this->assertEquals(5.0, $priced['lines'][0]['quantity']);
        $this->assertEquals(50.0, $priced['totals']['total']);
    }

    public function test_client_supplied_prices_are_ignored(): void
    {
        // Regla 7: aunque el payload traiga un precio, no se usa.
        AppSetting::set('pricing.prices_include_tax', false, 'pricing', 'boolean');
        $product = $this->makePublicProduct(['price' => 500, 'tax_rate' => 0, 'iva' => false]);

        $priced = app(CartQuotePdfService::class)->buildLines([
            ['product_id' => $product->id, 'quantity' => 1, 'price' => 0.01],
        ]);

        $this->assertEquals(500.0, $priced['totals']['total']);
    }
}
