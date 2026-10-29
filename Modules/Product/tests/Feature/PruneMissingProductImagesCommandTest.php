<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Tests\TestCase;

/**
 * products:prune-missing-images: imagenes que apuntan a archivos inexistentes.
 * Los datos legados se escriben con query builder (asi llegaron del import y
 * de products:sync-images), sin pasar por los observers.
 */
class PruneMissingProductImagesCommandTest extends TestCase
{
    /** @return array{0: Product, 1: Product, 2: Product, 3: Product} */
    private function legacyData(): array
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/good-a.png', 'a');
        Storage::disk('public')->put('products/good-b.png', 'b');
        Storage::disk('public')->put('products/MR-01.webp', 'mr');

        // Principal ".webp" rota y dos buenas detras (el caso de la ficha 232281).
        $mixed = Product::factory()->create();
        $this->legacy($mixed, '.webp', [['.webp', 0, true], ['products/good-a.png', 1, false], ['products/good-b.png', 2, false]]);

        // Solo una imagen y su archivo nunca se subio.
        $onlyBroken = Product::factory()->create();
        $this->legacy($onlyBroken, 'CH0412S.webp', [['CH0412S.webp', 0, true]]);

        // Nombre sin carpeta que si existe en products/: no se toca.
        $bareOk = Product::factory()->create();
        $this->legacy($bareOk, 'MR-01.webp', [['MR-01.webp', 0, true]]);

        // URL externa: no es un archivo nuestro, no se toca.
        $external = Product::factory()->create();
        $this->legacy($external, 'https://cdn.example.com/x.jpg', [['https://cdn.example.com/x.jpg', 0, true]]);

        return [$mixed, $onlyBroken, $bareOk, $external];
    }

    /** @param list<array{0: string, 1: int, 2: bool}> $images */
    private function legacy(Product $product, string $imgPath, array $images): void
    {
        ProductImage::query()->where('product_id', $product->id)->delete();
        foreach ($images as [$path, $order, $primary]) {
            ProductImage::query()->insert(['product_id' => $product->id, 'file_path' => $path, 'sort_order' => $order, 'is_primary' => $primary]);
        }
        Product::query()->whereKey($product->id)->update(['img_path' => $imgPath]);
    }

    public function test_report_only_does_not_change_anything(): void
    {
        [$mixed] = $this->legacyData();
        $before = ProductImage::count();

        $this->artisan('products:prune-missing-images')
            ->expectsOutputToContain('Productos con imagen inexistente: 2. Filas de galeria a quitar: 2.')
            ->expectsOutputToContain('Solo reporte')
            ->assertSuccessful();

        $this->assertSame($before, ProductImage::count());
        $this->assertSame('.webp', $mixed->fresh()->img_path);
    }

    public function test_apply_removes_missing_files_and_promotes_the_next_image(): void
    {
        [$mixed, $onlyBroken, $bareOk, $external] = $this->legacyData();
        $csv = tempnam(sys_get_temp_dir(), 'prune-images');

        $this->artisan('products:prune-missing-images', ['--apply' => true, '--csv' => $csv])
            ->expectsOutputToContain('Correcciones aplicadas: 2 productos (1 con otra imagen como principal, 1 sin imagen).')
            ->assertSuccessful();

        // La siguiente imagen buena pasa a principal, al frente y a img_path.
        $gallery = ProductImage::where('product_id', $mixed->id)->orderBy('sort_order')->get();
        $this->assertSame(['products/good-a.png', 'products/good-b.png'], $gallery->pluck('file_path')->all());
        $this->assertTrue((bool) $gallery[0]->is_primary);
        $this->assertSame(0, (int) $gallery[0]->sort_order);
        $this->assertFalse((bool) $gallery[1]->is_primary);
        $this->assertSame('products/good-a.png', $mixed->fresh()->img_path);

        // Sin imagenes validas: galeria vacia e img_path null.
        $this->assertSame(0, ProductImage::where('product_id', $onlyBroken->id)->count());
        $this->assertNull($onlyBroken->fresh()->img_path);

        // Lo que existe o no es nuestro queda igual.
        $this->assertSame('MR-01.webp', $bareOk->fresh()->img_path);
        $this->assertSame(1, ProductImage::where('product_id', $bareOk->id)->count());
        $this->assertSame('https://cdn.example.com/x.jpg', $external->fresh()->img_path);

        // El CSV guarda lo previo para revertir.
        $lines = array_map('str_getcsv', file($csv, FILE_IGNORE_NEW_LINES));
        $this->assertCount(3, $lines);
        $this->assertSame(['.webp', '.webp'], [$lines[1][2], $lines[1][4]]);
        unlink($csv);

        // Segunda corrida: nada pendiente.
        $this->artisan('products:prune-missing-images')
            ->expectsOutputToContain('Productos con imagen inexistente: 0.')
            ->assertSuccessful();
    }
}
