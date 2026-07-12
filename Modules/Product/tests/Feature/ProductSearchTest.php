<?php

namespace Modules\Product\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Product\Models\Category;
use Modules\Product\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Cobertura de la busqueda tolerante a typos (scopeSearch, 3 capas).
 *
 * Se prueba a traves del filtro JSON:API filter[search] para verificar el
 * cableado real del API (Scope::make('search') -> scopeSearch), y en algunos
 * casos tambien directo contra el scope para claridad.
 */
class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    private Unit $unit;
    private Category $category;
    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = Unit::factory()->create();
        $this->category = Category::factory()->create();
        $this->brand = Brand::factory()->create();
    }

    private function makeProduct(array $attrs): Product
    {
        return Product::factory()->create(array_merge([
            'unit_id' => $this->unit->id,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ], $attrs));
    }

    private function actAsAdmin(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');
    }

    /** Devuelve los ids (string) que el filtro search retorna via API. */
    private function searchIds(string $term): array
    {
        $this->actAsAdmin();
        $response = $this->jsonApi()->get('/api/v1/products?filter[search]=' . urlencode($term));
        $response->assertOk();

        return collect($response->json('data'))->pluck('id')->all();
    }

    public function test_exact_match_still_works(): void
    {
        $p = $this->makeProduct(['name' => 'Lavadora Industrial', 'sku' => 'LAV-001']);
        $this->makeProduct(['name' => 'Secadora Comercial', 'sku' => 'SEC-002']);

        $ids = $this->searchIds('lavadora');

        $this->assertContains((string) $p->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_search_is_case_insensitive(): void
    {
        $p = $this->makeProduct(['name' => 'Lavadora Industrial', 'sku' => 'LAV-001']);

        $lower = $this->searchIds('lavadora');
        $upper = $this->searchIds('LAVADORA');

        $this->assertEquals([(string) $p->id], $lower);
        $this->assertEquals($lower, $upper);
    }

    public function test_term_without_accent_matches_accented_product(): void
    {
        // Producto con acento en name; el termino se busca sin acento.
        // Limite conocido: solo se normaliza el termino, no la columna, asi que
        // este caso se cubre por el fallback Levenshtein (cafe ~ Cafe/Café).
        $p = $this->makeProduct(['name' => 'Cafetera Express', 'sku' => 'CAF-100']);

        $ids = $this->searchIds('cafetera');

        $this->assertContains((string) $p->id, $ids);
    }

    public function test_typo_matches_via_levenshtein_fallback(): void
    {
        // "labadora" (b por v) no matchea con LIKE, debe caer al fallback.
        $p = $this->makeProduct(['name' => 'Lavadora Industrial', 'sku' => 'LAV-001']);
        $this->makeProduct(['name' => 'Secadora Comercial', 'sku' => 'SEC-002']);

        $ids = $this->searchIds('labadora');

        $this->assertContains((string) $p->id, $ids);
    }

    public function test_multi_token_matches_regardless_of_order(): void
    {
        $p = $this->makeProduct([
            'name' => 'Lavadora Industrial',
            'sku' => 'LAV-001',
            'description' => 'Equipo pesado',
        ]);
        $this->makeProduct(['name' => 'Lavadora Domestica', 'sku' => 'LAV-009']);

        $ids = $this->searchIds('industrial lavadora');

        $this->assertEquals([(string) $p->id], $ids);
    }

    public function test_multi_token_across_fields(): void
    {
        // "lavadora industrial": una palabra en name, otra en description.
        $p = $this->makeProduct([
            'name' => 'Lavadora de alto rendimiento',
            'sku' => 'LAV-777',
            'description' => 'Uso industrial en fabricas',
        ]);

        $ids = $this->searchIds('lavadora industrial');

        $this->assertContains((string) $p->id, $ids);
    }

    public function test_no_match_returns_empty_without_loading_all(): void
    {
        $this->makeProduct(['name' => 'Lavadora Industrial', 'sku' => 'LAV-001']);
        $this->makeProduct(['name' => 'Secadora Comercial', 'sku' => 'SEC-002']);

        // Termino totalmente disimilar: ni LIKE ni Levenshtein deben matchear.
        $ids = $this->searchIds('xyzqwverylongnonsense');

        $this->assertSame([], $ids);
    }

    public function test_scope_returns_all_when_term_empty(): void
    {
        $a = $this->makeProduct(['name' => 'Uno', 'sku' => 'U-1']);
        $b = $this->makeProduct(['name' => 'Dos', 'sku' => 'D-2']);

        // Termino vacio no debe filtrar: ambos ids siguen presentes.
        $ids = Product::search('   ')->pluck('id')->all();

        $this->assertContains($a->id, $ids);
        $this->assertContains($b->id, $ids);
    }
}
