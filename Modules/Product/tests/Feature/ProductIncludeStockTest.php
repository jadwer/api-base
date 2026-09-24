<?php

namespace Modules\Product\Tests\Feature;

use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * E2E dev 2026-09-18: products?include=stock venia vacio porque el Resource
 * manual no listaba la relacion que el Schema declara; el detalle de
 * cotizacion mostraba todo "sin stock" y bloqueaba Generar venta.
 */
class ProductIncludeStockTest extends TestCase
{
    public function test_product_show_includes_stock(): void
    {
        $admin = $this->getAdminUser();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $stock = Stock::factory()->create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 10]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('products')
            ->includePaths('stock')
            ->get("/api/v1/products/{$product->id}");

        $response->assertFetchedOne($product);
        $this->assertContains('stock', array_keys($response->json('data.relationships')));
        $response->assertIsIncluded('stocks', $stock);
    }
}
