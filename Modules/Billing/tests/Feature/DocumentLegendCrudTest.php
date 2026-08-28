<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Models\DocumentLegend;
use Tests\TestCase;

class DocumentLegendCrudTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_create_document_legend(): void
    {
        $admin = $this->getAdminUser();

        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('document-legends')
            ->withData([
                'type' => 'document-legends',
                'attributes' => [
                    'documentType' => 'quote',
                    'body' => 'Cotizacion {folio} valida hasta {fecha_vencimiento}.',
                    'isActive' => true,
                ],
            ])
            ->post('/api/v1/document-legends')
            ->assertCreated();

        $legend = DocumentLegend::first();
        $this->assertNotNull($legend);
        $this->assertEquals('quote', $legend->document_type);
        $this->assertTrue($legend->is_active);
    }

    /** @test */
    public function it_rejects_invalid_document_type(): void
    {
        $admin = $this->getAdminUser();

        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('document-legends')
            ->withData([
                'type' => 'document-legends',
                'attributes' => [
                    'documentType' => 'invoice_xml',
                    'body' => 'Leyenda',
                ],
            ])
            ->post('/api/v1/document-legends')
            ->assertStatus(422);

        $this->assertEquals(0, DocumentLegend::count());
    }

    /** @test */
    public function it_rejects_duplicate_document_type(): void
    {
        $admin = $this->getAdminUser();
        DocumentLegend::factory()->forType('quote')->create();

        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('document-legends')
            ->withData([
                'type' => 'document-legends',
                'attributes' => [
                    'documentType' => 'quote',
                    'body' => 'Otra leyenda para quote',
                ],
            ])
            ->post('/api/v1/document-legends')
            ->assertStatus(422);

        $this->assertEquals(1, DocumentLegend::count());
    }

    /** @test */
    public function update_keeps_own_document_type_without_unique_conflict(): void
    {
        $admin = $this->getAdminUser();
        $legend = DocumentLegend::factory()->forType('quote')->create();

        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('document-legends')
            ->withData([
                'type' => 'document-legends',
                'id' => (string) $legend->id,
                'attributes' => [
                    'documentType' => 'quote',
                    'body' => 'Texto actualizado {total}.',
                    'isActive' => false,
                ],
            ])
            ->patch("/api/v1/document-legends/{$legend->id}")
            ->assertSuccessful();

        $legend->refresh();
        $this->assertEquals('Texto actualizado {total}.', $legend->body);
        $this->assertFalse($legend->is_active);
    }

    /** @test */
    public function admin_can_list_and_filter_by_document_type(): void
    {
        $admin = $this->getAdminUser();
        DocumentLegend::factory()->forType('quote')->create();
        DocumentLegend::factory()->forType('remission')->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('document-legends')
            ->get('/api/v1/document-legends?filter[documentType]=remission');

        $response->assertSuccessful();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('remission', $response->json('data.0.attributes.documentType'));
    }

    /** @test */
    public function admin_can_delete_document_legend(): void
    {
        $admin = $this->getAdminUser();
        $legend = DocumentLegend::factory()->forType('quote')->create();

        $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->delete("/api/v1/document-legends/{$legend->id}")
            ->assertSuccessful();

        $this->assertEquals(0, DocumentLegend::count());
    }

    /** @test */
    public function guest_cannot_list_document_legends(): void
    {
        $this->jsonApi()
            ->expects('document-legends')
            ->get('/api/v1/document-legends')
            ->assertStatus(401);
    }

    /** @test */
    public function customer_cannot_create_document_legend(): void
    {
        $customer = $this->getCustomerUser();

        $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('document-legends')
            ->withData([
                'type' => 'document-legends',
                'attributes' => [
                    'documentType' => 'quote',
                    'body' => 'Leyenda',
                ],
            ])
            ->post('/api/v1/document-legends')
            ->assertStatus(403);
    }

    /** @test */
    public function placeholders_endpoint_returns_catalog(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/document-legends-placeholders');

        $response->assertSuccessful();
        $placeholders = collect($response->json('data'))->pluck('placeholder');
        $this->assertContains('{folio}', $placeholders);
        $this->assertContains('{total_letra}', $placeholders);
    }

    /** @test */
    public function placeholders_endpoint_requires_auth(): void
    {
        $this->getJson('/api/v1/document-legends-placeholders')
            ->assertStatus(401);
    }
}
