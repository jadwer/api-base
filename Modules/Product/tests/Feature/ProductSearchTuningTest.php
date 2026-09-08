<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AppConfig\Models\AppSetting;
use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * P1 buscador (junta cliente 2026-09-01). Invariantes:
 * - "9093-03" (SKU con guion) matchea por LIKE sin caer al fuzzy.
 * - search.fuzzy_enabled=false apaga el fallback Levenshtein por tenant.
 * - Con el flag encendido (default) los typos siguen encontrando.
 * Se prueba el scope directo (Scope::make('search') mapea aqui para
 * admin y publico).
 */
class ProductSearchTuningTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'is_active' => true,
            'is_public' => true,
        ], $overrides));
    }

    public function test_hyphenated_sku_matches_by_like_without_fuzzy(): void
    {
        $product = $this->makeProduct(['name' => 'Electrodo de pH', 'sku' => '9093-03']);
        $this->makeProduct(['name' => 'Otro producto', 'sku' => 'AAA-111']);

        // El guion se tokeniza: 9093 AND 03, ambos via LIKE.
        $bySku = Product::search('9093-03')->pluck('id');
        $this->assertContains($product->id, $bySku->all());

        // Variantes de captura del mismo SKU tambien matchean.
        $this->assertContains($product->id, Product::search('9093 03')->pluck('id')->all());
        $this->assertContains($product->id, Product::search('9093/03')->pluck('id')->all());
    }

    public function test_fuzzy_disabled_returns_empty_for_typos(): void
    {
        AppSetting::set('search.fuzzy_enabled', false, 'search', 'boolean');
        $this->makeProduct(['name' => 'Microscopio binocular', 'sku' => 'MIC-100']);

        // Typo que solo el Levenshtein encontraria.
        $result = Product::search('microscoipo')->get();

        $this->assertCount(0, $result);
    }

    public function test_fuzzy_enabled_still_finds_typos(): void
    {
        AppSetting::set('search.fuzzy_enabled', true, 'search', 'boolean');
        $product = $this->makeProduct(['name' => 'Microscopio binocular', 'sku' => 'MIC-100']);

        $result = Product::search('microscoipo')->pluck('id');

        $this->assertContains($product->id, $result->all());
    }

    public function test_direct_match_never_enters_fuzzy_even_disabled(): void
    {
        AppSetting::set('search.fuzzy_enabled', false, 'search', 'boolean');
        $product = $this->makeProduct(['name' => 'Vaso de precipitado', 'sku' => 'VP-500']);

        $result = Product::search('precipitado')->pluck('id');

        $this->assertContains($product->id, $result->all());
    }
}
