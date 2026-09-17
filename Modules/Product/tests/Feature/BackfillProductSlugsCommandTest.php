<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Tests\TestCase;

/**
 * SEO Bloque 1b: products:backfill-slugs es idempotente, por lotes y no toca
 * updated_at ni los slugs ya asignados.
 */
class BackfillProductSlugsCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @var int[] */
    private array $ids = [];

    /**
     * El TestCase siembra productos demo (ya con slug por el observer): solo se
     * anulan los creados aqui, que simulan filas previas a la migracion.
     */
    private function seedWithoutSlugs(int $count, array $attrs = []): void
    {
        $unit = Unit::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create(['name' => 'Marca']);

        $this->ids = [];
        for ($i = 0; $i < $count; $i++) {
            $this->ids[] = Product::factory()->create(array_merge([
                'unit_id' => $unit->id,
                'category_id' => $category->id,
                'brand_id' => $brand->id,
            ], $attrs))->id;
        }
        DB::table('products')->whereIn('id', $this->ids)->update(['slug' => null]);
    }

    public function test_fills_missing_slugs_in_chunks_and_is_idempotent(): void
    {
        $this->seedWithoutSlugs(7);
        $before = DB::table('products')->pluck('updated_at', 'id');

        $this->artisan('products:backfill-slugs', ['--chunk' => 3])
            ->expectsOutputToContain('Productos sin slug: 7')
            ->expectsOutputToContain('Slugs generados: 7')
            ->assertSuccessful();

        $this->assertSame(0, DB::table('products')->whereNull('slug')->count());
        $this->assertSame(7, DB::table('products')->whereIn('id', $this->ids)->distinct()->count('slug'), 'todos unicos');
        $this->assertSame(
            DB::table('products')->count(),
            DB::table('products')->distinct()->count('slug'),
            'unicos tambien frente a los sembrados'
        );
        $this->assertEquals($before, DB::table('products')->pluck('updated_at', 'id'), 'no toca updated_at');

        $this->artisan('products:backfill-slugs')
            ->expectsOutputToContain('Productos sin slug: 0')
            ->assertSuccessful();
    }

    public function test_collisions_get_numeric_suffix(): void
    {
        // products.sku es UNIQUE: la colision real se da con SKU nulo (12 casos en prod)
        $this->seedWithoutSlugs(3, ['name' => 'Mismo nombre', 'sku' => null]);

        $this->artisan('products:backfill-slugs')->assertSuccessful();

        $slugs = DB::table('products')->whereIn('id', $this->ids)->orderBy('id')->pluck('slug')->all();
        $this->assertSame(['mismo-nombre-marca', 'mismo-nombre-marca-2', 'mismo-nombre-marca-3'], $slugs);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->seedWithoutSlugs(2);

        $this->artisan('products:backfill-slugs', ['--dry-run' => true])
            ->expectsOutputToContain('Productos sin slug: 2')
            ->assertSuccessful();

        $this->assertSame(2, DB::table('products')->whereIn('id', $this->ids)->whereNull('slug')->count());
    }
}
