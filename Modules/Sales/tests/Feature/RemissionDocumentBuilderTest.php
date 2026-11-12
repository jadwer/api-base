<?php

namespace Modules\Sales\Tests\Feature;

use Atomo\Documents\Contracts\PdfRenderer;
use Atomo\Documents\Layout\Layouts;
use Atomo\Documents\Testing\FakePdfRenderer;
use Modules\AppConfig\Models\AppSetting;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Remission;
use Modules\Sales\Models\RemissionItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\RemissionPDFGenerator;
use Modules\Sales\Support\Documents\RemissionDocumentData;
use Tests\TestCase;

/** Remision con el constructor de documentos de Atomo, detras de documents.builder.remission (12-11-2026). */
class RemissionDocumentBuilderTest extends TestCase
{
    private function remission(): Remission
    {
        $contact = Contact::factory()->customer()->create(['name' => 'Farmabiot SA']);
        $order = SalesOrder::factory()->create(['contact_id' => $contact->id, 'order_number' => 'OV-26009001']);
        $remission = Remission::factory()->forOrder($order)->create(['remission_number' => 'REM-26009001', 'delivered_by' => 'Luis Paz', 'delivery_notes' => 'Entregar en recepción']);
        RemissionItem::factory()->create(['remission_id' => $remission->id, 'product_name' => 'Caldo Eugon LT', 'quantity' => 3, 'batch_number' => 'L2409A']);

        return $remission->fresh();
    }

    private function prepared(Remission $r): array
    {
        return (fn () => $this->prepareData($r))->call(app(RemissionPDFGenerator::class));
    }

    public function test_el_doc_de_la_remision_trae_pedido_cliente_partidas_sin_precio_y_firmas(): void
    {
        $this->assertTrue(Layouts::has('lwm-remision'));
        $r = $this->remission();
        $doc = RemissionDocumentData::from($r, $this->prepared($r));

        $this->assertSame('REM-26009001', $doc['title']['number']);
        $this->assertSame('Pedido OV-26009001', $doc['title']['chip']);
        $this->assertSame('Farmabiot SA', $doc['party']['name']);
        $this->assertSame('Caldo Eugon LT', $doc['lines'][0]['description']);
        $this->assertSame('L2409A', $doc['lines'][0]['batch']);
        $this->assertNull($doc['lines'][0]['unitPrice']);
        $this->assertSame('Luis Paz', $doc['signatures'][0]['name']);
        $this->assertSame('Entregar en recepción', $doc['notes']);
    }

    public function test_con_el_interruptor_sale_por_bloques_y_sin_el_por_la_plantilla(): void
    {
        $this->app->instance(PdfRenderer::class, $fake = new FakePdfRenderer);
        $r = $this->remission();

        $this->assertStringStartsWith('%PDF', app(RemissionPDFGenerator::class)->render($r, $this->prepared($r)));

        AppSetting::set('documents.builder.remission', true, 'documents', 'boolean');
        app(RemissionPDFGenerator::class)->render($r, $this->prepared($r));
        $this->assertStringContainsString('REM-26009001', $fake->lastHtml());
        $this->assertStringContainsString('Caldo Eugon LT', $fake->lastHtml());
    }
}
