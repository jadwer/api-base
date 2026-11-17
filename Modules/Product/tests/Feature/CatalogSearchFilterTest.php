<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Unit;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Busqueda parcial de categorias, marcas y unidades (rediseno de Productos, 17-11):
 * antes solo existia filter[name] exacto.
 */
class CatalogSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_part_of_name_slug_or_code(): void
    {
        $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail(), 'sanctum');
        Brand::factory()->create(['name' => 'Becton Dickinson', 'slug' => 'becton-dickinson']);
        Brand::factory()->create(['name' => 'Otra Marca', 'slug' => 'otra-marca']);
        Category::factory()->create(['name' => 'Equipo de laboratorio', 'slug' => 'equipo-lab']);
        Unit::factory()->create(['name' => 'Kilogramo', 'code' => 'KGM']);

        $names = fn (string $url) => collect($this->jsonApi()->get($url)->assertOk()->json('data'))->pluck('attributes.name')->all();

        $this->assertSame(['Becton Dickinson'], $names('/api/v1/brands?filter[search]=dickin'));
        $this->assertSame(['Becton Dickinson'], $names('/api/v1/brands?filter[search]=becton-d'));
        $this->assertContains('Equipo de laboratorio', $names('/api/v1/categories?filter[search]=laborat'));
        $this->assertContains('Kilogramo', $names('/api/v1/units?filter[search]=kgm'));
        $this->assertNotContains('Otra Marca', $names('/api/v1/brands?filter[search]=dickin'));
    }
}
