<?php

namespace Modules\Product\Tests\Feature;

use App\Support\DownloadFilename;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * Bug 2026-09-24: la ficha tecnica daba 500 cuando el SKU trae diagonal
 * (238 productos en prod, ej. CH0138B/PR). Symfony rechaza "/" y "\" en el
 * filename de Content-Disposition.
 */
class ProductDatasheetDownloadTest extends TestCase
{
    public function test_datasheet_downloads_when_sku_has_slash(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('datasheets/ch0138b.pdf', '%PDF-1.4 prueba');
        $product = Product::factory()->create([
            'sku' => 'CH0138B/PR',
            'datasheet_path' => 'datasheets/ch0138b.pdf',
        ]);

        $response = $this->get("/api/v1/products/{$product->id}/datasheet");

        $response->assertOk();
        $this->assertStringContainsString('ficha-tecnica-CH0138B-PR.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_sanitize_removes_path_separators_and_quotes(): void
    {
        $this->assertSame('ficha-tecnica-CH0138B-PR.pdf', DownloadFilename::sanitize('ficha-tecnica-CH0138B/PR.pdf'));
        $this->assertSame('OV_A-B-C.pdf', DownloadFilename::sanitize('OV_A\\B/C.pdf'));
        $this->assertSame('oc-cliente-12-3.pdf', DownloadFilename::sanitize('oc-cliente-12"3.pdf'));
        $this->assertSame('documento', DownloadFilename::sanitize(null, 'documento'));
        $this->assertSame('Cotización Ñandú.pdf', DownloadFilename::sanitize('Cotización Ñandú.pdf'));
    }
}
