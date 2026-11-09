<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CFDIItem;
use Modules\Contacts\Models\Contact;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Regla 8 (2026-11-09, hallazgo reproducido desde Atomo): el cliente del portal
 * tiene billing.cfdi-invoices.show y billing.cfdi-items.*, pero solo puede ver
 * SUS CFDI. El listado de facturas ya filtraba por contacto; el show, las
 * relaciones, los includes, el listado de partidas y las descargas no.
 */
class CFDIOwnershipShowTest extends TestCase
{
    use RefreshDatabase;

    private CFDIInvoice $mine;

    private CFDIInvoice $theirs;

    private CFDIItem $myItem;

    private CFDIItem $theirItem;

    protected function setUp(): void
    {
        parent::setUp();
        $customer = $this->getCustomerUser();
        foreach (['billing.cfdi-invoices.index', 'billing.cfdi-invoices.show', 'billing.cfdi-items.index', 'billing.cfdi-items.show', 'billing.cfdi-invoices.download-pdf', 'billing.cfdi-invoices.prefactura'] as $permission) {
            $customer->givePermissionTo(Permission::findOrCreate($permission, 'api'));
        }
        $myContact = Contact::factory()->customer()->create(['email' => $customer->email]);
        $otherContact = Contact::factory()->customer()->create(['email' => 'otro.cliente@example.com']);
        $this->mine = CFDIInvoice::factory()->valid()->create(['contact_id' => $myContact->id]);
        $this->theirs = CFDIInvoice::factory()->valid()->create(['contact_id' => $otherContact->id]);
        $this->myItem = CFDIItem::factory()->create(['cfdi_invoice_id' => $this->mine->id]);
        $this->theirItem = CFDIItem::factory()->create(['cfdi_invoice_id' => $this->theirs->id]);
    }

    public function test_el_cliente_ve_su_cfdi_y_no_el_ajeno_por_id(): void
    {
        $this->actingAs($this->getCustomerUser(), 'sanctum')->jsonApi()->expects('cfdi-invoices')
            ->get("/api/v1/cfdi-invoices/{$this->mine->id}")->assertFetchedOne($this->mine);
        $this->actingAs($this->getCustomerUser(), 'sanctum')->jsonApi()->expects('cfdi-invoices')
            ->get("/api/v1/cfdi-invoices/{$this->theirs->id}")->assertStatus(403);
        $this->actingAs($this->getCustomerUser(), 'sanctum')->jsonApi()->expects('cfdi-invoices')
            ->get("/api/v1/cfdi-invoices/{$this->theirs->id}?include=items,contact")->assertStatus(403);
    }

    public function test_el_cliente_no_alcanza_las_partidas_ni_las_relaciones_del_cfdi_ajeno(): void
    {
        $customer = $this->getCustomerUser();
        $this->actingAs($customer, 'sanctum')->jsonApi()->expects('cfdi-items')
            ->get("/api/v1/cfdi-invoices/{$this->theirs->id}/items")->assertStatus(403);
        $this->actingAs($customer, 'sanctum')->jsonApi()->expects('cfdi-items')
            ->get("/api/v1/cfdi-invoices/{$this->theirs->id}/relationships/items")->assertStatus(403);
        $this->actingAs($customer, 'sanctum')->jsonApi()->expects('cfdi-items')
            ->get("/api/v1/cfdi-items/{$this->theirItem->id}")->assertStatus(403);
        $this->actingAs($customer, 'sanctum')->jsonApi()->expects('cfdi-items')
            ->get("/api/v1/cfdi-items/{$this->myItem->id}")->assertFetchedOne($this->myItem);
    }

    public function test_el_listado_de_partidas_solo_trae_las_de_sus_cfdi(): void
    {
        $response = $this->actingAs($this->getCustomerUser(), 'sanctum')->jsonApi()->expects('cfdi-items')
            ->get('/api/v1/cfdi-items?page[size]=100')->assertOk();
        $ids = array_column($response->json('data'), 'id');

        $this->assertContains((string) $this->myItem->id, $ids);
        $this->assertNotContains((string) $this->theirItem->id, $ids);

        $response = $this->actingAs($this->getCustomerUser(), 'sanctum')->jsonApi()->expects('cfdi-items')
            ->get("/api/v1/cfdi-items?filter[cfdiInvoiceId]={$this->theirs->id}")->assertOk();
        $this->assertSame([], $response->json('data'));
    }

    public function test_las_descargas_del_cfdi_ajeno_responden_403(): void
    {
        $customer = $this->getCustomerUser();
        $this->actingAs($customer, 'sanctum')->get("/api/v1/cfdi-invoices/{$this->theirs->id}/download-pdf")->assertStatus(403);
        $this->actingAs($customer, 'sanctum')->get("/api/v1/cfdi-invoices/{$this->theirs->id}/preview-pdf")->assertStatus(403);
        $this->actingAs($customer, 'sanctum')->getJson("/api/v1/cfdi-invoices/{$this->theirs->id}/prefactura")->assertStatus(403);
    }

    public function test_el_personal_con_el_permiso_sigue_viendo_cualquier_cfdi(): void
    {
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('cfdi-invoices')
            ->get("/api/v1/cfdi-invoices/{$this->theirs->id}")->assertFetchedOne($this->theirs);
        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('cfdi-items')
            ->get('/api/v1/cfdi-items?page[size]=100')->assertOk();
        $this->assertContains((string) $this->theirItem->id, array_column($response->json('data'), 'id'));
    }
}
