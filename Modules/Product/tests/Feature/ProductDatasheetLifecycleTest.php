<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * Fichas tecnicas (2026-10-06): reemplazar o quitar borra el PDF anterior
 * del disco, y el comando de limpieza quita referencias a archivos que no
 * existen (catalogo importado con "HA-10018.pdf" sin archivo).
 */
class ProductDatasheetLifecycleTest extends TestCase
{
    public function test_replacing_the_datasheet_deletes_the_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('datasheets/old.pdf', 'old');
        Storage::disk('public')->put('datasheets/new.pdf', 'new');
        $product = Product::factory()->create(['datasheet_path' => 'datasheets/old.pdf']);

        $product->update(['datasheet_path' => 'datasheets/new.pdf']);

        Storage::disk('public')->assertMissing('datasheets/old.pdf');
        Storage::disk('public')->assertExists('datasheets/new.pdf');
    }

    public function test_removing_the_datasheet_via_api_deletes_the_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('datasheets/gone.pdf', 'x');
        $product = Product::factory()->create(['datasheet_path' => 'datasheets/gone.pdf']);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('products')
            ->withData(['type' => 'products', 'id' => (string) $product->id, 'attributes' => ['datasheetPath' => null]])
            ->patch("/api/v1/products/{$product->id}")
            ->assertOk();

        $this->assertNull($product->fresh()->datasheet_path);
        Storage::disk('public')->assertMissing('datasheets/gone.pdf');
    }

    public function test_shared_file_is_kept_when_one_product_drops_it(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('datasheets/shared.pdf', 'x');
        $a = Product::factory()->create(['datasheet_path' => 'datasheets/shared.pdf']);
        Product::factory()->create(['datasheet_path' => 'shared.pdf']);

        $a->update(['datasheet_path' => null]);

        Storage::disk('public')->assertExists('datasheets/shared.pdf');
    }

    public function test_prune_command_reports_then_cleans_missing_references(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('datasheets/ok.pdf', 'x');
        $ok = Product::factory()->create(['datasheet_path' => 'datasheets/ok.pdf']);
        $legacy = Product::factory()->create(['datasheet_path' => 'HA-10018.pdf']);
        $broken = Product::factory()->create(['datasheet_path' => 'datasheets/nada.pdf']);
        $external = Product::factory()->create(['datasheet_path' => 'https://fabricante.test/ficha.pdf']);
        $csv = storage_path('app/prune-test.csv');

        // El seeder de pruebas trae mas productos con ficha: se valida por fila, no por total.
        $this->artisan('products:prune-missing-datasheets', ['--csv' => $csv])
            ->expectsOutputToContain('Con archivo faltante:')
            ->assertSuccessful();
        $this->assertSame('HA-10018.pdf', $legacy->fresh()->datasheet_path, 'sin --apply no cambia nada');
        // Ids como conjunto (comparar substrings "5," confundia el 5 con el 15)
        $ids = array_map(static fn (string $line) => (int) strtok($line, ','), array_slice(file($csv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), 1));
        $this->assertContains($legacy->id, $ids);
        $this->assertContains($broken->id, $ids);
        $this->assertNotContains($ok->id, $ids);
        $this->assertNotContains($external->id, $ids);

        $this->artisan('products:prune-missing-datasheets', ['--apply' => true])->assertSuccessful();

        $this->assertNull($legacy->fresh()->datasheet_path);
        $this->assertNull($broken->fresh()->datasheet_path);
        $this->assertSame('datasheets/ok.pdf', $ok->fresh()->datasheet_path);
        $this->assertSame('https://fabricante.test/ficha.pdf', $external->fresh()->datasheet_path);
        @unlink($csv);
    }
}
