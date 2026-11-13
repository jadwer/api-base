<?php

namespace Modules\Sales\Tests\Feature;

use Atomo\Documents\Contracts\PdfRenderer;
use Atomo\Documents\Testing\FakePdfRenderer;
use Modules\AppConfig\Models\AppSetting;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\DocumentDesign;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Services\QuotePDFGenerator;
use Modules\Sales\Support\Documents\DocumentDesigns;
use Modules\User\Models\User;
use Tests\TestCase;

/** Diseno de los PDF: galeria de plantillas, estilo por fila, vista previa, guardar y restaurar (2026-11-12). */
class DocumentDesignEditorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CleanDatabaseSeeder::class);
        $this->seed(\Modules\AppConfig\Database\Seeders\AppSettingSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'god@example.com')->firstOrFail();
    }

    private function quote(): Quote
    {
        $contact = Contact::factory()->customer()->create(['name' => 'Farmabiot SA']);
        $quote = Quote::factory()->create(['contact_id' => $contact->id, 'quote_number' => 'COT-26009101']);
        QuoteItem::factory()->create(['quote_id' => $quote->id, 'product_name' => 'Caldo Eugon LT', 'quantity' => 2, 'unit_price' => 100, 'quoted_price' => 100]);

        return $quote->fresh();
    }

    /** @return array<string, mixed> */
    private function cell(array $layout, string $block): array
    {
        foreach ($layout['rows'] as $row) {
            foreach ($row['cells'] as $cell) {
                if ($cell['block'] === $block) {
                    return $cell;
                }
            }
        }
        $this->fail("No hay bloque {$block}");
    }

    public function test_el_editor_trae_la_galeria_y_solo_opciones_de_estilo(): void
    {
        $data = $this->actingAs($this->admin(), 'sanctum')->getJson('/api/v1/document-designs/remission')->assertOk()->json('data');

        $this->assertFalse($data['customized']);
        $this->assertSame('standard', $data['style']);
        $this->assertSame(['standard', 'essential', 'professional', 'total'], array_column($data['styles'], 'key'));
        $this->assertTrue($data['styles'][2]['thumbnail']);
        $this->assertArrayHasKey('signature', $data['catalog']['blocks']);
        $this->assertArrayNotHasKey('totals', $data['catalog']['blocks']);
        $lines = array_column($data['catalog']['blocks']['lines']['options'], 'key');
        $this->assertContains('header', $lines);
        $this->assertNotContains('columns', $lines, 'el contenido no se edita en el panel');
        $this->assertContains('accent', array_column($data['catalog']['theme'], 'key'));
    }

    public function test_la_remision_sigue_en_blanco_y_negro_por_omision(): void
    {
        $this->assertSame('mono', DocumentDesigns::layout('remission')['theme']['mode']);
        $this->assertSame('color', DocumentDesigns::layout('quote')['theme']['mode']);
    }

    public function test_aplicar_una_plantilla_devuelve_el_diseno_sin_guardarlo(): void
    {
        $data = $this->actingAs($this->admin(), 'sanctum')->postJson('/api/v1/document-designs/quote/style', ['style' => 'professional'])
            ->assertOk()->json('data');

        $this->assertSame('professional', $data['style']);
        $this->assertSame('mono', $data['layout']['theme']['mode']);
        $this->assertSame('dark', $this->cell($data['layout'], 'lines')['options']['header']);
        $this->assertSame($this->cell(DocumentDesigns::factory('quote'), 'lines')['options']['columns'], $this->cell($data['layout'], 'lines')['options']['columns'], 'la plantilla no toca el contenido');
        $this->assertSame(0, DocumentDesign::count());

        $this->postJson('/api/v1/document-designs/quote/style', ['style' => 'sepia'])
            ->assertStatus(422)->assertJsonPath('message', 'Esa plantilla no existe.');
    }

    public function test_guardar_cambia_el_pdf_y_restaurar_regresa_al_de_fabrica(): void
    {
        $this->app->instance(PdfRenderer::class, $fake = new FakePdfRenderer);
        AppSetting::set('documents.builder.quote', true, 'documents', 'boolean');
        $quote = $this->quote();
        $layout = DocumentDesigns::withStyle('quote', 'professional');

        $this->actingAs($this->admin(), 'sanctum')->putJson('/api/v1/document-designs/quote', ['layout' => $layout, 'style' => 'professional'])
            ->assertOk()->assertJsonPath('data.customized', true)->assertJsonPath('data.style', 'professional');
        $this->assertSame('mono', DocumentDesigns::layout('quote')['theme']['mode']);

        app(QuotePDFGenerator::class)->render($quote, (fn () => $this->prepareData($quote))->call(app(QuotePDFGenerator::class)));
        $this->assertStringContainsString('COT-26009101', $fake->lastHtml());

        $this->deleteJson('/api/v1/document-designs/quote')->assertOk()->assertJsonPath('data.customized', false);
        $this->assertSame('color', DocumentDesigns::layout('quote')['theme']['mode']);
    }

    public function test_un_diseno_invalido_se_rechaza_en_espanol_con_la_fila(): void
    {
        $layout = DocumentDesigns::factory('quote');
        $layout['rows'][2]['cells'][0]['options']['columns'] = [['key' => 'sku', 'width' => 80], ['key' => 'description', 'width' => 80]];
        $this->actingAs($this->admin(), 'sanctum')->putJson('/api/v1/document-designs/quote', ['layout' => $layout])
            ->assertStatus(422)->assertJsonPath('message', 'Fila 3, columnas: los anchos suman más de 100.');

        $layout = DocumentDesigns::factory('quote');
        $layout['rows'][0]['cells'][0]['options'] = ['logo' => 'gigante'];
        $message = $this->putJson('/api/v1/document-designs/quote', ['layout' => $layout])->assertStatus(422)->json('message');
        $this->assertStringStartsWith('Fila 1, celda 1', $message);
        $this->assertSame(0, DocumentDesign::count());
    }

    public function test_no_acepta_una_seccion_que_el_documento_no_llena(): void
    {
        $layout = DocumentDesigns::factory('remission');
        $layout['rows'][] = ['cells' => [['block' => 'totals']]];

        $this->actingAs($this->admin(), 'sanctum')->putJson('/api/v1/document-designs/remission', ['layout' => $layout])
            ->assertStatus(422)->assertJsonPath('message', 'Fila '.count($layout['rows']).': la sección "Totales" no se usa en Remisión.');
    }

    public function test_la_vista_previa_y_la_miniatura(): void
    {
        $this->app->instance(PdfRenderer::class, $fake = new FakePdfRenderer);
        $this->quote();

        $this->actingAs($this->admin(), 'sanctum')->postJson('/api/v1/document-designs/quote/preview', ['layout' => DocumentDesigns::withStyle('quote', 'total')])
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('COT-26009101', $fake->lastHtml());

        $this->get('/api/v1/document-designs/sales_order/styles/essential/thumbnail')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->getJson('/api/v1/document-designs/sales_order/styles/sepia/thumbnail')->assertStatus(404);
    }

    public function test_un_cliente_no_puede_cambiar_el_diseno(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer, 'sanctum')->putJson('/api/v1/document-designs/quote', ['layout' => DocumentDesigns::factory('quote')])
            ->assertStatus(403)->assertJsonPath('message', 'No tiene permisos para cambiar el diseño de los documentos.');
    }

    public function test_un_diseno_guardado_que_ya_no_valida_no_tumba_el_pdf(): void
    {
        DocumentDesign::query()->create(['document_type' => 'quote', 'layout' => ['version' => 1, 'rows' => []]]);

        $this->assertContains('lines', array_column(array_merge(...DocumentDesigns::layout('quote')['rows']), 'block'));
    }
}
