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

/** Editor visual de documentos: leer, validar, guardar, restaurar y ver en PDF (2026-11-12). */
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
    private function withoutBank(): array
    {
        $layout = DocumentDesigns::factory('quote');
        $layout['rows'] = array_values(array_filter($layout['rows'], fn ($row) => $row['cells'][0]['block'] !== 'bank'));

        return $layout;
    }

    public function test_el_editor_trae_el_diseno_de_fabrica_y_el_catalogo_del_documento(): void
    {
        $data = $this->actingAs($this->admin(), 'sanctum')->getJson('/api/v1/document-designs/remission')->assertOk()->json('data');

        $this->assertFalse($data['customized']);
        $this->assertSame('Remisión', $data['label']);
        $this->assertContains('signature', array_column($data['catalog']['blocks'], 'key'));
        $this->assertNotContains('totals', array_column($data['catalog']['blocks'], 'key'));
        $this->assertContains('batch', array_column($data['catalog']['columns'], 'key'));
        $this->assertSame(['color', 'mono', 'mixed'], array_column($data['modes'], 'value'));
    }

    public function test_guardar_cambia_el_pdf_y_restaurar_regresa_al_de_fabrica(): void
    {
        $this->app->instance(PdfRenderer::class, $fake = new FakePdfRenderer);
        AppSetting::set('documents.builder.quote', true, 'documents', 'boolean');
        $quote = $this->quote();

        $this->actingAs($this->admin(), 'sanctum')->putJson('/api/v1/document-designs/quote', ['layout' => $this->withoutBank()])
            ->assertOk()->assertJsonPath('data.customized', true);
        $this->assertSame(1, DocumentDesign::count());
        $this->assertNotContains('bank', array_column(array_merge(...DocumentDesigns::layout('quote')['rows']), 'block'));

        app(QuotePDFGenerator::class)->render($quote, (fn () => $this->prepareData($quote))->call(app(QuotePDFGenerator::class)));
        $this->assertStringContainsString('COT-26009101', $fake->lastHtml());

        $this->deleteJson('/api/v1/document-designs/quote')->assertOk()->assertJsonPath('data.customized', false);
        $this->assertContains('bank', array_column(array_merge(...DocumentDesigns::layout('quote')['rows']), 'block'));
    }

    public function test_un_diseno_invalido_se_rechaza_en_espanol_con_la_fila(): void
    {
        $layout = DocumentDesigns::factory('quote');
        $layout['rows'][0]['cells'][0]['span'] = 9;

        $this->actingAs($this->admin(), 'sanctum')->putJson('/api/v1/document-designs/quote', ['layout' => $layout])
            ->assertStatus(422)->assertJsonPath('message', 'Fila 1: los anchos de la fila deben sumar 12.');
        $this->assertSame(0, DocumentDesign::count());

        $layout = DocumentDesigns::factory('quote');
        $layout['rows'][2]['cells'][0]['options']['columns'] = [['key' => 'sku', 'width' => 80], ['key' => 'description', 'width' => 80]];
        $this->putJson('/api/v1/document-designs/quote', ['layout' => $layout])
            ->assertStatus(422)->assertJsonPath('message', 'Fila 3, columnas: los anchos suman más de 100.');
    }

    public function test_no_acepta_una_seccion_o_columna_que_el_documento_no_llena(): void
    {
        $layout = DocumentDesigns::factory('remission');
        $layout['rows'][] = ['cells' => [['block' => 'totals']]];

        $this->actingAs($this->admin(), 'sanctum')->putJson('/api/v1/document-designs/remission', ['layout' => $layout])
            ->assertStatus(422)->assertJsonPath('message', 'Fila '.count($layout['rows']).': la sección "Totales" no se usa en Remisión.');
    }

    public function test_la_vista_previa_devuelve_el_pdf_sin_guardar(): void
    {
        $this->app->instance(PdfRenderer::class, $fake = new FakePdfRenderer);
        $this->quote();

        $response = $this->actingAs($this->admin(), 'sanctum')->postJson('/api/v1/document-designs/quote/preview', ['layout' => $this->withoutBank(), 'mode' => 'mono']);

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('COT-26009101', $fake->lastHtml());
        $this->assertSame(0, DocumentDesign::count());
    }

    public function test_un_cliente_no_puede_cambiar_el_diseno(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer, 'sanctum')->putJson('/api/v1/document-designs/quote', ['layout' => $this->withoutBank()])
            ->assertStatus(403)->assertJsonPath('message', 'No tiene permisos para cambiar el diseño de los documentos.');
    }

    public function test_un_diseno_guardado_que_ya_no_valida_no_tumba_el_pdf(): void
    {
        DocumentDesign::query()->create(['document_type' => 'quote', 'layout' => ['version' => 1, 'rows' => []]]);

        $this->assertContains('lines', array_column(array_merge(...DocumentDesigns::layout('quote')['rows']), 'block'));
    }
}
