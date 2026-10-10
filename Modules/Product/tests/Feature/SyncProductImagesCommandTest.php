<?php

namespace Modules\Product\Tests\Feature;

use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Tests\TestCase;

/**
 * products:sync-images (P1 granel): galeria desalineada con img_path.
 * Los datos legados se escriben con query builder para no pasar por los
 * observers (asi llegaron del import).
 */
class SyncProductImagesCommandTest extends TestCase
{
    /** @return array{0: Product, 1: Product, 2: Product, 3: Product, 4: Product} */
    private function legacyData(): array
    {
        $noRows = Product::factory()->create();
        Product::query()->whereKey($noRows->id)->update(['img_path' => 'legacy.jpg']);

        $mismatch = Product::factory()->create(['img_path' => 'products/old.jpg']);
        Product::query()->whereKey($mismatch->id)->update(['img_path' => 'products/import.jpg']);

        $promote = Product::factory()->create(['img_path' => 'products/p1.jpg']);
        ProductImage::query()->insert(['product_id' => $promote->id, 'file_path' => 'products/p2.jpg', 'sort_order' => 1, 'is_primary' => false]);
        Product::query()->whereKey($promote->id)->update(['img_path' => 'products/p2.jpg']);

        $noImgPath = Product::factory()->create(['img_path' => 'products/g.jpg']);
        Product::query()->whereKey($noImgPath->id)->update(['img_path' => null]);

        $ok = Product::factory()->create(['img_path' => 'products/ok.jpg']);

        return [$noRows, $mismatch, $promote, $noImgPath, $ok];
    }

    public function test_report_only_does_not_change_anything(): void
    {
        [$noRows] = $this->legacyData();
        $before = ProductImage::count();

        $this->artisan('products:sync-images')
            ->expectsOutputToContain('Por corregir: 4')
            ->expectsOutputToContain('Solo reporte')
            ->assertSuccessful();

        $this->assertSame($before, ProductImage::count());
        $this->assertSame(0, ProductImage::where('product_id', $noRows->id)->count());
    }

    public function test_apply_aligns_gallery_and_img_path_and_writes_csv(): void
    {
        [$noRows, $mismatch, $promote, $noImgPath, $ok] = $this->legacyData();
        $csv = tempnam(sys_get_temp_dir(), 'sync-images');

        $this->artisan('products:sync-images', ['--apply' => true, '--csv' => $csv])
            ->expectsOutputToContain('Correcciones aplicadas: 4')
            ->assertSuccessful();

        $primaryOf = fn (Product $p) => ProductImage::where('product_id', $p->id)->where('is_primary', true)->sole();

        // Sin filas: se crea la primaria con img_path.
        $this->assertSame('legacy.jpg', $primaryOf($noRows)->file_path);
        $this->assertSame(0, $primaryOf($noRows)->sort_order);

        // Primaria con otro archivo: img_path entra como primaria y la vieja se conserva.
        $this->assertSame('products/import.jpg', $primaryOf($mismatch)->file_path);
        $this->assertSame(0, $primaryOf($mismatch)->sort_order);
        $this->assertTrue(ProductImage::where('product_id', $mismatch->id)->where('file_path', 'products/old.jpg')->exists());

        // Otra fila ya tenia img_path: pasa a primaria y al frente.
        $this->assertSame('products/p2.jpg', $primaryOf($promote)->file_path);
        $this->assertSame(0, $primaryOf($promote)->sort_order);
        $this->assertSame(2, ProductImage::where('product_id', $promote->id)->count());

        // Galeria sin img_path: se copia la primaria.
        $this->assertSame('products/g.jpg', $noImgPath->fresh()->img_path);

        // Sin cambios para el que ya estaba bien.
        $this->assertSame(1, ProductImage::where('product_id', $ok->id)->count());

        $lines = array_map('str_getcsv', file($csv, FILE_IGNORE_NEW_LINES));
        $this->assertSame('product_id', $lines[0][0]);
        $this->assertCount(5, $lines);
        @unlink($csv);

        // Idempotente: una segunda pasada no encuentra nada.
        $this->artisan('products:sync-images')
            ->expectsOutputToContain('Por corregir: 0')
            ->assertSuccessful();
    }
}
