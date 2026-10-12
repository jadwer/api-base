<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * P2 granel: tipo de precio fixed | quote_only ("Precio sujeto a cotizacion").
 * Invariantes: price obligatorio solo con fixed (tambien en PATCH con el merge
 * de JSON:API), quote_only guarda price null sin oferta, y la lista de tipos es
 * la del modelo (el frontend la fija con su propio test de contrato).
 */
class ProductPricingTypeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($this->admin, 'sanctum');
    }

    private function payload(array $attributes): array
    {
        return [
            'type' => 'products',
            'attributes' => array_merge([
                'name' => 'Alcohol isopropilico granel',
                'sku' => 'GRANEL-' . uniqid(),
                'description' => 'Contenedor de 20,000 L',
                'fullDescription' => 'Granel para fraccionar',
                'iva' => true,
            ], $attributes),
            'relationships' => [
                'unit' => ['data' => ['type' => 'units', 'id' => (string) Unit::factory()->create()->id]],
                'category' => ['data' => ['type' => 'categories', 'id' => (string) Category::factory()->create()->id]],
                'brand' => ['data' => ['type' => 'brands', 'id' => (string) Brand::factory()->create()->id]],
            ],
        ];
    }

    private function patchProduct(Product $product, array $attributes)
    {
        return $this->jsonApi()
            ->withData(['type' => 'products', 'id' => (string) $product->id, 'attributes' => $attributes])
            ->patch("/api/v1/products/{$product->id}");
    }

    public function test_pricing_types_are_the_model_list(): void
    {
        $this->assertSame(['fixed', 'quote_only'], Product::PRICING_TYPES);
        $this->assertSame('Precio sujeto a cotización', Product::QUOTE_ONLY_LABEL);
    }

    public function test_store_without_pricing_type_defaults_to_fixed_and_requires_price(): void
    {
        $response = $this->jsonApi()->withData($this->payload([]))->post('/api/v1/products');
        $this->assertJsonApiValidationErrors(['/data/attributes/price'], $response);

        $response = $this->jsonApi()->expects('products')->withData($this->payload(['price' => 10]))->post('/api/v1/products');
        $response->assertCreated();
        $this->assertSame('fixed', $response->json('data.attributes.pricingType'));
    }

    public function test_store_fixed_without_price_fails(): void
    {
        $response = $this->jsonApi()
            ->withData($this->payload(['pricingType' => 'fixed', 'price' => null]))
            ->post('/api/v1/products');

        $this->assertJsonApiValidationErrors(['/data/attributes/price'], $response);
        $this->assertStringContainsString('tipo de precio es fijo', $response->json('errors.0.detail'));
    }

    public function test_store_quote_only_without_price_is_created_with_null_price(): void
    {
        $response = $this->jsonApi()
            ->expects('products')
            ->withData($this->payload(['pricingType' => 'quote_only', 'cost' => 12.5]))
            ->post('/api/v1/products');

        $response->assertCreated();
        $this->assertSame('quote_only', $response->json('data.attributes.pricingType'));
        $this->assertNull($response->json('data.attributes.price'));
        $this->assertEquals(12.5, $response->json('data.attributes.cost'));
    }

    public function test_store_quote_only_discards_price_sent_by_client(): void
    {
        $response = $this->jsonApi()
            ->expects('products')
            ->withData($this->payload(['pricingType' => 'quote_only', 'price' => 99]))
            ->post('/api/v1/products');

        $response->assertCreated();
        $product = Product::findOrFail($response->json('data.id'));
        $this->assertNull($product->price);
        $this->assertTrue($product->isQuoteOnly());
    }

    public function test_store_quote_only_on_sale_is_rejected(): void
    {
        $response = $this->jsonApi()
            ->withData($this->payload(['pricingType' => 'quote_only', 'isOnSale' => true]))
            ->post('/api/v1/products');

        $this->assertJsonApiValidationErrors(['/data/attributes/isOnSale'], $response);
    }

    public function test_store_rejects_unknown_pricing_type(): void
    {
        $response = $this->jsonApi()
            ->withData($this->payload(['pricingType' => 'on_request', 'price' => 5]))
            ->post('/api/v1/products');

        $this->assertJsonApiValidationErrors(['/data/attributes/pricingType'], $response);
    }

    public function test_patch_fixed_to_quote_only_without_price_clears_price_and_offer(): void
    {
        $product = Product::factory()->create([
            'price' => 150,
            'compare_at_price' => 200,
            'is_on_sale' => true,
        ]);

        // El merge de JSON:API trae price=150 e isOnSale=true vigentes: no
        // debe exigir mandarlos ni rechazar la oferta heredada.
        $response = $this->patchProduct($product, ['pricingType' => 'quote_only']);

        $response->assertOk();
        $product->refresh();
        $this->assertSame('quote_only', $product->pricing_type);
        $this->assertNull($product->price);
        $this->assertNull($product->compare_at_price);
        $this->assertFalse($product->is_on_sale);
    }

    public function test_patch_quote_only_to_fixed_without_price_fails(): void
    {
        $product = Product::factory()->quoteOnly()->create();

        $response = $this->patchProduct($product, ['pricingType' => 'fixed']);

        $this->assertJsonApiValidationErrors(['/data/attributes/price'], $response);
        $this->assertTrue($product->fresh()->isQuoteOnly());
    }

    public function test_patch_quote_only_to_fixed_with_price_succeeds(): void
    {
        $product = Product::factory()->quoteOnly()->create();

        $this->patchProduct($product, ['pricingType' => 'fixed', 'price' => 320.5])->assertOk();

        $product->refresh();
        $this->assertSame('fixed', $product->pricing_type);
        $this->assertEquals(320.5, $product->price);
    }

    public function test_patch_quote_only_other_field_keeps_null_price(): void
    {
        $product = Product::factory()->quoteOnly()->create();

        $this->patchProduct($product, ['name' => 'Granel renombrado'])->assertOk();

        $this->assertNull($product->fresh()->price);
    }

    public function test_patch_fixed_product_can_not_remove_price(): void
    {
        $product = Product::factory()->create(['price' => 10]);

        $response = $this->patchProduct($product, ['price' => null]);

        $this->assertJsonApiValidationErrors(['/data/attributes/price'], $response);
    }

    public function test_filter_by_pricing_type(): void
    {
        $quoteOnly = Product::factory()->quoteOnly()->create();
        Product::factory()->create();

        $response = $this->jsonApi()->expects('products')
            ->filter(['pricing_type' => 'quote_only'])
            ->get('/api/v1/products');

        $response->assertOk();
        $this->assertSame([(string) $quoteOnly->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_eloquent_writes_also_keep_the_invariant(): void
    {
        // Import, bulk o consola: el observer normaliza aunque no pase por la API.
        $product = Product::factory()->create(['price' => 50, 'is_on_sale' => true]);
        $product->update(['pricing_type' => 'quote_only', 'price' => 70]);

        $product->refresh();
        $this->assertNull($product->price);
        $this->assertFalse($product->is_on_sale);
        $this->assertNull($product->discount_percentage);
        $this->assertNull($product->savings);
        $this->assertFalse($product->isCurrentlyOnSale());
    }

    public function test_on_sale_scope_excludes_quote_only(): void
    {
        $onSale = Product::factory()->create(['price' => 10, 'is_on_sale' => true]);
        $quoteOnly = Product::factory()->quoteOnly()->create();
        // Dato legado escrito por query builder (sin observer).
        \DB::table('products')->where('id', $quoteOnly->id)->update(['is_on_sale' => true]);

        $ids = Product::onSale()->pluck('id')->all();

        $this->assertContains($onSale->id, $ids);
        $this->assertNotContains($quoteOnly->id, $ids);
    }
}
