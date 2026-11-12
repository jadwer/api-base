<?php

namespace Modules\Sales\Tests\Feature;

use Atomo\Documents\Contracts\PdfRenderer;
use Atomo\Documents\Layout\Layouts;
use Atomo\Documents\Testing\FakePdfRenderer;
use Modules\AppConfig\Models\AppSetting;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Services\SalesOrderPDFGenerator;
use Modules\Sales\Support\Documents\SalesOrderDocumentData;
use Tests\TestCase;

/** Pedido con el constructor de documentos de Atomo, detras de documents.builder.sales_order (12-11-2026). */
class SalesOrderDocumentBuilderTest extends TestCase
{
    private function order(): SalesOrder
    {
        $contact = Contact::factory()->customer()->create(['name' => 'Farmabiot SA']);
        $order = SalesOrder::factory()->create([
            'contact_id' => $contact->id, 'order_number' => 'OV-26009001', 'status' => 'confirmed',
            'customer_po_number' => 'OC-2026-0157', 'payment_method' => 'PPD', 'credit_days' => 30,
            'notes' => 'Entregar en recepción',
        ]);
        SalesOrderItem::factory()->create(['sales_order_id' => $order->id, 'quantity' => 2, 'unit_price' => 100, 'discount' => 20]);

        return $order->fresh();
    }

    private function prepared(SalesOrder $o): array
    {
        return (fn () => $this->prepareData($o, \Modules\Billing\Models\CompanySetting::getActive(), []))->call(app(SalesOrderPDFGenerator::class));
    }

    public function test_el_doc_del_pedido_trae_estado_oc_pago_partidas_con_descuento_y_totales(): void
    {
        $this->assertSame(1, Layouts::get('lwm-pedido')['version']);
        $o = $this->order();
        $doc = SalesOrderDocumentData::from($o, $this->prepared($o));

        $this->assertSame('OV-26009001', $doc['title']['number']);
        $this->assertSame('Confirmado', $doc['title']['chip']);
        $this->assertSame('Farmabiot SA', $doc['party']['name']);
        $meta = collect($doc['meta'])->pluck('value', 'key');
        $this->assertSame('OC-2026-0157', $meta['po']);
        $this->assertSame('Pago en parcialidades o diferido', $meta['payment']);
        $this->assertSame('30 días', $meta['credit']);
        $this->assertEquals(20.0, $doc['lines'][0]['discount']);
        $this->assertEquals(180.0, $doc['lines'][0]['total']);
        $this->assertSame('Entregar en recepción', $doc['notes']);
    }

    public function test_con_el_interruptor_sale_por_bloques_y_sin_el_por_la_plantilla(): void
    {
        $this->app->instance(PdfRenderer::class, $fake = new FakePdfRenderer);
        $o = $this->order();

        $this->assertStringStartsWith('%PDF', app(SalesOrderPDFGenerator::class)->render($o, $this->prepared($o)));

        AppSetting::set('documents.builder.sales_order', true, 'documents', 'boolean');
        app(SalesOrderPDFGenerator::class)->render($o, $this->prepared($o));
        $this->assertStringContainsString('OV-26009001', $fake->lastHtml());
        $this->assertStringContainsString('OC-2026-0157', $fake->lastHtml());
    }
}
