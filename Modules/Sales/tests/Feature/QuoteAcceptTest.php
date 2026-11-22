<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;

class QuoteAcceptTest extends TestCase
{
    public function test_admin_can_accept_sent_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/accept");

        $response->assertOk();
        $response->assertJson([
            'message' => 'Cotización aceptada.',
        ]);
        $this->assertEquals('accepted', $response->json('data.attributes.status'));
        $this->assertNotNull($response->json('data.attributes.acceptedAt'));
    }

    public function test_tech_can_accept_quote(): void
    {
        $tech = $this->getTechUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/accept");

        $response->assertOk();
    }

    public function test_guest_cannot_accept_quote(): void
    {
        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
        ]);

        $response = $this->postJson("/api/v1/quotes/{$quote->id}/accept");

        $response->assertStatus(401);
    }

    public function test_vendedor_puede_aceptar_borrador_por_aceptacion_telefonica(): void
    {
        // Decision 2026-09-10: el envio por correo NO es prerequisito
        // del ciclo; el vendedor registra la aceptacion recibida por
        // telefono o mostrador directamente desde el borrador.
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/accept");

        $response->assertOk();
        $this->assertEquals('accepted', $response->json('data.attributes.status'));
    }

    public function test_cliente_del_portal_no_puede_aceptar_un_borrador(): void
    {
        // El borrador sigue siendo del vendedor: el cliente solo acepta
        // cotizaciones que le fueron enviadas.
        $customer = $this->getCustomerUser();

        $contact = Contact::factory()->customer()->create([
            'email' => $customer->email,
        ]);
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/accept");

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Esta cotización no se puede aceptar en su estado actual.',
        ]);
    }

    public function test_cliente_del_portal_si_puede_aceptar_una_enviada(): void
    {
        $customer = $this->getCustomerUser();

        $contact = Contact::factory()->customer()->create([
            'email' => $customer->email,
        ]);
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/accept");

        $response->assertOk();
        $this->assertEquals('accepted', $response->json('data.attributes.status'));
    }

    public function test_cannot_accept_already_accepted_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/accept");

        $response->assertStatus(400);
    }

    public function test_cannot_accept_rejected_quote(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create();
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'rejected',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/accept");

        $response->assertStatus(400);
    }

    public function test_returns_404_for_nonexistent_quote(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/99999/accept');

        $response->assertStatus(404);
    }

    /** TIE-14 (2026-11-22): Rechazar desde el portal. */
    public function test_cliente_del_portal_rechaza_una_enviada_y_el_motivo_queda_en_notas_internas(): void
    {
        $customer = $this->getCustomerUser();
        $contact = Contact::factory()->customer()->create(['email' => $customer->email]);
        $quote = Quote::factory()->create(['contact_id' => $contact->id, 'status' => 'sent', 'sent_at' => now(), 'internal_notes' => null]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/reject", ['reason' => 'El precio queda fuera de presupuesto']);

        $response->assertOk();
        $this->assertEquals('rejected', $response->json('data.attributes.status'));
        $this->assertSame('Motivo del rechazo: El precio queda fuera de presupuesto', $quote->fresh()->internal_notes);
    }

    public function test_cliente_del_portal_no_puede_rechazar_un_borrador_ni_una_ajena(): void
    {
        $customer = $this->getCustomerUser();
        $contact = Contact::factory()->customer()->create(['email' => $customer->email]);
        $draft = Quote::factory()->create(['contact_id' => $contact->id, 'status' => 'draft']);
        $foreign = Quote::factory()->create(['contact_id' => Contact::factory()->customer()->create()->id, 'status' => 'sent', 'sent_at' => now()]);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/quotes/{$draft->id}/reject")
            ->assertStatus(400)
            ->assertJson(['error' => 'Esta cotización no se puede rechazar en su estado actual.']);
        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/quotes/{$foreign->id}/reject")
            ->assertStatus(403);

        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertSame('sent', $foreign->fresh()->status);
    }

    public function test_vendedor_si_puede_rechazar_un_borrador(): void
    {
        $admin = $this->getAdminUser();
        $quote = Quote::factory()->create(['contact_id' => Contact::factory()->customer()->create()->id, 'status' => 'draft']);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/quotes/{$quote->id}/reject")->assertOk();
        $this->assertSame('rejected', $quote->fresh()->status);
    }
}
