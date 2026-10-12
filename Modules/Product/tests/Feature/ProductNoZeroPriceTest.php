<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * P2b (D8, riesgo PROFECO): ningun producto queda vendible en $0. Sin precio,
 * con precio <= 0 o quote_only = "producto sin precio de venta"
 * (Product::requiresQuote()); se ve en el catalogo pero solo se cotiza.
 * Tambien fija la regla 9: los 422 de productos llegan en espanol.
 */
class ProductNoZeroPriceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail(), 'sanctum');
    }

    private function payload(array $attributes): array
    {
        return [
            'type' => 'products',
            'attributes' => array_merge([
                'name' => 'Frasco 1 L',
                'sku' => 'P2B-' . uniqid(),
                'description' => 'Frasco',
                'fullDescription' => 'Frasco de vidrio',
                'iva' => true,
            ], $attributes),
            'relationships' => [
                'unit' => ['data' => ['type' => 'units', 'id' => (string) Unit::factory()->create()->id]],
                'category' => ['data' => ['type' => 'categories', 'id' => (string) Category::factory()->create()->id]],
                'brand' => ['data' => ['type' => 'brands', 'id' => (string) Brand::factory()->create()->id]],
            ],
        ];
    }

    private function detailFor(\Illuminate\Testing\TestResponse $response, string $pointer): ?string
    {
        return collect($response->json('errors'))
            ->first(fn ($error) => ($error['source']['pointer'] ?? null) === $pointer)['detail'] ?? null;
    }

    public function test_requires_quote_covers_quote_only_null_and_non_positive_prices(): void
    {
        $product = new Product(['pricing_type' => Product::PRICING_FIXED, 'price' => 10]);
        $this->assertFalse($product->requiresQuote());

        $product->price = 0;
        $this->assertTrue($product->requiresQuote());
        $product->price = -5;
        $this->assertTrue($product->requiresQuote());
        $product->price = null;
        $this->assertTrue($product->requiresQuote());

        $quoteOnly = new Product(['pricing_type' => Product::PRICING_QUOTE_ONLY, 'price' => null]);
        $this->assertTrue($quoteOnly->requiresQuote());
    }

    public function test_fixed_product_saved_without_a_positive_price_becomes_quote_only(): void
    {
        // Rutas que no pasan por ProductRequest (imports, consola, factories).
        $zero = Product::factory()->create(['price' => 0]);
        $this->assertSame(Product::PRICING_QUOTE_ONLY, $zero->fresh()->pricing_type);
        $this->assertNull($zero->fresh()->price);

        $fixed = Product::factory()->create(['price' => 99.5]);
        $fixed->price = null;
        $fixed->save();
        $this->assertSame(Product::PRICING_QUOTE_ONLY, $fixed->fresh()->pricing_type);
    }

    public function test_migration_rule_marks_fixed_products_without_price_as_quote_only(): void
    {
        $product = Product::factory()->create(['price' => 50]);
        // Carga directa a la BD sin observers (como la del CRM viejo).
        DB::table('products')->where('id', $product->id)->update(['price' => 0]);
        $this->assertTrue($product->fresh()->requiresQuote());

        $migration = require base_path('Modules/Product/Database/migrations/2026_10_10_160000_mark_zero_price_products_as_quote_only.php');
        $migration->up();

        $this->assertSame(Product::PRICING_QUOTE_ONLY, $product->fresh()->pricing_type);
    }

    public function test_store_fixed_with_zero_price_is_rejected_in_spanish(): void
    {
        $response = $this->jsonApi()->withData($this->payload(['pricingType' => 'fixed', 'price' => 0]))->post('/api/v1/products');

        $this->assertJsonApiValidationErrors(['/data/attributes/price'], $response);
        $this->assertStringContainsString('mayor a 0', $this->detailFor($response, '/data/attributes/price'));
    }

    public function test_store_without_price_says_so_in_spanish(): void
    {
        $response = $this->jsonApi()->withData($this->payload([]))->post('/api/v1/products');

        $this->assertSame(
            'El precio de venta es obligatorio cuando el tipo de precio es fijo.',
            $this->detailFor($response, '/data/attributes/price')
        );
    }

    public function test_generic_rule_message_uses_the_spanish_field_name(): void
    {
        $payload = $this->payload(['price' => 10]);
        unset($payload['attributes']['name']);

        $response = $this->jsonApi()->withData($payload)->post('/api/v1/products');

        $this->assertSame('El campo nombre es obligatorio.', $this->detailFor($response, '/data/attributes/name'));
        // El titulo del error JSON:API tambien sale del paquete en espanol.
        $this->assertStringNotContainsString('field is required', (string) $response->getContent());
    }

    public function test_store_quote_only_with_zero_price_is_allowed(): void
    {
        $response = $this->jsonApi()->expects('products')
            ->withData($this->payload(['pricingType' => 'quote_only', 'price' => 0]))
            ->post('/api/v1/products');

        $response->assertCreated();
        $this->assertNull($response->json('data.attributes.price'));
    }
}
