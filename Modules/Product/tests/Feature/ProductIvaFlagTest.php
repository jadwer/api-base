<?php

namespace Modules\Product\Tests\Feature;

use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * Todos los productos con IVA 16% (Jasim 2026-10-22): la bandera iva manda
 * sobre tax_rate y los productos nuevos nacen con IVA.
 */
class ProductIvaFlagTest extends TestCase
{
    public function test_iva_flag_drives_tax_rate(): void
    {
        $product = Product::factory()->create(['iva' => true]);
        $this->assertEquals(16, $product->fresh()->effective_tax_rate);

        $product->update(['iva' => false]);
        $this->assertEquals(0, $product->fresh()->effective_tax_rate);

        $product->update(['iva' => true]);
        $this->assertEquals(16, $product->fresh()->effective_tax_rate);
    }

    public function test_explicit_tax_rate_is_respected(): void
    {
        $product = Product::factory()->create(['iva' => true, 'tax_rate' => 8]);

        $this->assertEquals(8, $product->fresh()->effective_tax_rate);
    }
}
