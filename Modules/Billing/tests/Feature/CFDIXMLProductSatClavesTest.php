<?php

namespace Modules\Billing\Tests\Feature;

use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CFDIItem;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Services\CFDI\CFDIXMLGenerator;
use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * WS9: the XML generator must use the SAT keys configured on the linked
 * product (sat_clave_prod_serv / sat_clave_unidad), falling back to the
 * item-level values and finally to the generic defaults (01010101 / E48).
 */
class CFDIXMLProductSatClavesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // A single active CompanySetting for the generator
        CompanySetting::query()->update(['is_active' => false]);
        CompanySetting::factory()->create(['is_active' => true]);
    }

    public function test_xml_uses_sat_claves_from_the_linked_product(): void
    {
        $product = Product::factory()->create([
            'sat_clave_prod_serv' => '12352301',
            'sat_clave_unidad' => 'LTR',
        ]);

        $invoice = CFDIInvoice::factory()->create();
        CFDIItem::factory()->create([
            'cfdi_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'clave_prod_serv' => '01010101',
            'clave_unidad' => 'E48',
        ]);

        $xml = (new CFDIXMLGenerator())->generate($invoice->fresh('items'));

        $this->assertStringContainsString('ClaveProdServ="12352301"', $xml);
        $this->assertStringContainsString('ClaveUnidad="LTR"', $xml);
    }

    public function test_xml_keeps_item_claves_when_product_has_no_sat_claves(): void
    {
        $product = Product::factory()->create([
            'sat_clave_prod_serv' => null,
            'sat_clave_unidad' => null,
        ]);

        $invoice = CFDIInvoice::factory()->create();
        CFDIItem::factory()->create([
            'cfdi_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'clave_prod_serv' => '43231500',
            'clave_unidad' => 'H87',
        ]);

        $xml = (new CFDIXMLGenerator())->generate($invoice->fresh('items'));

        $this->assertStringContainsString('ClaveProdServ="43231500"', $xml);
        $this->assertStringContainsString('ClaveUnidad="H87"', $xml);
    }

    public function test_xml_keeps_item_claves_when_item_has_no_product(): void
    {
        $invoice = CFDIInvoice::factory()->create();
        CFDIItem::factory()->create([
            'cfdi_invoice_id' => $invoice->id,
            'product_id' => null,
            'clave_prod_serv' => '85121800',
            'clave_unidad' => 'ACT',
        ]);

        $xml = (new CFDIXMLGenerator())->generate($invoice->fresh('items'));

        $this->assertStringContainsString('ClaveProdServ="85121800"', $xml);
        $this->assertStringContainsString('ClaveUnidad="ACT"', $xml);
    }

    public function test_xml_falls_back_to_item_claves_when_product_claves_are_empty_strings(): void
    {
        $product = Product::factory()->create([
            'sat_clave_prod_serv' => '',
            'sat_clave_unidad' => '',
        ]);

        $invoice = CFDIInvoice::factory()->create();
        CFDIItem::factory()->create([
            'cfdi_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'clave_prod_serv' => '01010101',
            'clave_unidad' => 'E48',
        ]);

        $xml = (new CFDIXMLGenerator())->generate($invoice->fresh('items'));

        $this->assertStringContainsString('ClaveProdServ="01010101"', $xml);
        $this->assertStringContainsString('ClaveUnidad="E48"', $xml);
    }
}
