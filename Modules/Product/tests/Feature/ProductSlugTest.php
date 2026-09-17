<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Product\Support\ProductSlug;
use Tests\TestCase;

/**
 * SEO Bloque 1b: slug de producto (helper + observer).
 */
class ProductSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_joins_name_brand_and_sku_as_ascii_slug(): void
    {
        // Str::slug quita la puntuacion (J.T. -> jt) y pasa espacios a guion
        $this->assertSame(
            'metanol-hplc-jt-baker-9093-03',
            ProductSlug::build('Metanol HPLC', 'J.T. Baker', '9093-03')
        );
        $this->assertSame('acido-clorhidrico-fermont', ProductSlug::build('Ácido Clorhídrico', 'Fermont', null));
        $this->assertSame('producto', ProductSlug::build('***', null, null));
        $this->assertSame('producto', ProductSlug::build(null));
    }

    public function test_build_truncates_without_cutting_a_word(): void
    {
        $name = str_repeat('palabra ', 40); // ~320 chars
        $slug = ProductSlug::build($name, 'Marca', 'SKU-1');

        $this->assertLessThanOrEqual(ProductSlug::MAX_LENGTH, strlen($slug));
        $this->assertStringEndsNotWith('-', $slug);
        $this->assertMatchesRegularExpression('/^(palabra-)*palabra$/', $slug);
    }

    public function test_unique_appends_numeric_suffix_on_collision(): void
    {
        $this->makeProduct(['slug' => 'base-x']);
        $this->makeProduct(['slug' => 'base-x-2']);

        $this->assertSame('base-x-3', ProductSlug::unique('base-x'));
        $this->assertSame('otro', ProductSlug::unique('otro'));
    }

    public function test_unique_respects_in_memory_reservations_and_ignores_own_id(): void
    {
        $product = $this->makeProduct(['slug' => 'mio']);

        $taken = [];
        $this->assertSame('libre', ProductSlug::unique('libre', null, $taken));
        $this->assertSame('libre-2', ProductSlug::unique('libre', null, $taken));
        // El propio registro no cuenta como colision
        $this->assertSame('mio', ProductSlug::unique('mio', (int) $product->id));
    }

    public function test_observer_generates_slug_on_create_and_keeps_it_on_rename(): void
    {
        $brand = Brand::factory()->create(['name' => 'Hach']);
        $product = $this->makeProduct(['name' => 'Jumper 2 pos', 'sku' => 'HA-001215', 'brand_id' => $brand->id]);

        $this->assertSame('jumper-2-pos-hach-ha-001215', $product->fresh()->slug);

        $product->update(['name' => 'Jumper renombrado']);
        $this->assertSame('jumper-2-pos-hach-ha-001215', $product->fresh()->slug, 'el slug es estable');

        // products.sku es UNIQUE: la colision real se da con SKU nulo (12 casos en prod)
        $a = $this->makeProduct(['name' => 'Jumper 2 pos', 'sku' => null, 'brand_id' => $brand->id]);
        $b = $this->makeProduct(['name' => 'Jumper 2 pos', 'sku' => null, 'brand_id' => $brand->id]);
        $this->assertSame('jumper-2-pos-hach', $a->fresh()->slug);
        $this->assertSame('jumper-2-pos-hach-2', $b->fresh()->slug);
    }

    public function test_observer_fills_missing_slug_on_update(): void
    {
        $product = $this->makeProduct(['name' => 'Sin slug', 'sku' => 'SS-1']);
        \DB::table('products')->where('id', $product->id)->update(['slug' => null]);

        $product->fresh()->update(['price' => 10]);

        $this->assertNotEmpty($product->fresh()->slug);
    }

    private function makeProduct(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge([
            'unit_id' => Unit::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'brand_id' => Brand::factory()->create()->id,
            'is_active' => true,
        ], $attrs));
    }
}
