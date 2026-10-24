<?php

namespace Tests\Feature\Demo;

use Database\Seeders\DemoWorkflowSeeder;
use Illuminate\Support\Facades\Storage;
use Modules\Inventory\Models\ProductConversion;
use Modules\Inventory\Models\Stock;
use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * Datos de la demo marca blanca que venden (23-10): catalogo con acentos,
 * existencias, ejemplos de granel, fotos y sin productos TEST en la tienda.
 */
class DemoWorkflowSeederTest extends TestCase
{
    public function test_demo_catalog_is_sellable(): void
    {
        \Modules\Inventory\Models\Warehouse::firstOrCreate(['code' => 'WH-001'], ['name' => 'Almacén Principal', 'slug' => 'almacen-principal', 'is_active' => true]);
        Product::factory()->create(['sku' => 'TEST-001', 'is_public' => true]);
        Storage::fake('public');

        $this->seed(DemoWorkflowSeeder::class);

        // Cada producto demo trae su foto de estudio en el disco publico.
        $this->assertSame(0, Product::where('sku', 'like', 'DEMO-%')->whereNull('img_path')->count());
        Storage::disk('public')->assertExists('products/demo/DEMO-003.webp');

        $this->assertSame('Báscula de precisión 30 kg', Product::where('sku', 'DEMO-004')->value('name'));
        $this->assertSame(2, Product::where('sku', 'like', 'DEMO-%')->where('pricing_type', Product::PRICING_QUOTE_ONLY)->count());
        $this->assertTrue(ProductConversion::whereHas('sourceProduct', fn ($q) => $q->where('sku', 'DEMO-015'))->exists());
        $this->assertFalse((bool) Product::where('sku', 'TEST-001')->value('is_public'));

        $equipo = Product::where('sku', 'DEMO-001')->firstOrFail();
        $this->assertEquals(12, (float) Stock::where('product_id', $equipo->id)->sum('quantity'));
        $this->assertEquals(400, (float) Stock::where('product_id', Product::where('sku', 'DEMO-015')->value('id'))->sum('quantity'));
    }
}
