<?php

namespace Modules\Sales\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Vendedor de la cotizacion (rediseno 12-11-2026): por omision el del contacto
 * o el personal que la crea; editable; visible por nombre sin permiso de
 * usuarios; filtrable; y lo hereda el pedido al convertir (comisiones).
 */
class QuoteSellerTest extends TestCase
{
    private function create(User $as, Contact $contact, array $extra = [])
    {
        return $this->actingAs($as, 'sanctum')->jsonApi()->expects('quotes')
            ->withData(['type' => 'quotes', 'attributes' => ['contactId' => $contact->id, 'quoteDate' => now()->toDateString()] + $extra])
            ->post('/api/v1/quotes');
    }

    public function test_por_omision_es_el_vendedor_del_contacto(): void
    {
        $seller = User::factory()->create(['name' => 'Ana Ventas']);
        $contact = Contact::factory()->customer()->create(['default_salesperson_id' => $seller->id]);

        $response = $this->create($this->getAdminUser(), $contact)->assertCreated();

        $this->assertSame($seller->id, Quote::find($response->json('data.id'))->assigned_to);
        $response->assertJsonPath('data.attributes.assignedTo', $seller->id)
            ->assertJsonPath('data.attributes.assignedUserName', 'Ana Ventas');
    }

    public function test_sin_vendedor_en_el_contacto_es_quien_la_crea_si_es_personal(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create(['default_salesperson_id' => null]);

        $response = $this->create($admin, $contact)->assertCreated();

        $this->assertSame($admin->id, Quote::find($response->json('data.id'))->assigned_to);
    }

    public function test_un_cliente_del_portal_no_queda_como_vendedor(): void
    {
        $contact = Contact::factory()->customer()->create(['default_salesperson_id' => null]);
        $this->actingAs($this->getCustomerUser(), 'sanctum');

        $quote = Quote::factory()->create(['contact_id' => $contact->id, 'assigned_to' => null]);

        $this->assertNull($quote->fresh()->assigned_to);
    }

    public function test_se_puede_elegir_y_cambiar_el_vendedor_y_filtrar_por_el(): void
    {
        $ana = User::factory()->create(['name' => 'Ana']);
        $beto = User::factory()->create(['name' => 'Beto']);
        $contact = Contact::factory()->customer()->create();

        $id = $this->create($this->getAdminUser(), $contact, ['assignedTo' => $ana->id])->assertCreated()->json('data.id');
        $other = Quote::factory()->create(['contact_id' => $contact->id, 'assigned_to' => $beto->id]);

        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('quotes')
            ->withData(['type' => 'quotes', 'id' => (string) $id, 'attributes' => ['assignedTo' => $beto->id]])
            ->patch("/api/v1/quotes/{$id}")->assertOk()
            ->assertJsonPath('data.attributes.assignedUserName', 'Beto');

        $ids = collect($this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('quotes')
            ->get("/api/v1/quotes?filter[assignedTo]={$beto->id}")->assertOk()->json('data'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([(string) $id, (string) $other->id], $ids);

        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('quotes')
            ->withData(['type' => 'quotes', 'id' => (string) $id, 'attributes' => ['assignedTo' => 999999]])
            ->patch("/api/v1/quotes/{$id}")->assertStatus(422);
    }

    public function test_al_convertir_el_pedido_hereda_el_vendedor(): void
    {
        $seller = User::factory()->create();
        $quote = Quote::factory()->create(['status' => 'accepted', 'assigned_to' => $seller->id, 'contact_id' => Contact::factory()->customer()->create()->id]);
        QuoteItem::factory()->create(['quote_id' => $quote->id, 'unit_price' => 100, 'quoted_price' => 100]);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", ['order_type' => 'order', 'acceptance_channel' => 'phone'])
            ->assertCreated();

        $orderId = $response->json('data.salesOrder.id');
        $this->assertSame($seller->id, \Modules\Sales\Models\SalesOrder::find($orderId)->assigned_to);
    }
}
