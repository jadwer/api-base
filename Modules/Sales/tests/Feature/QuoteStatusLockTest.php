<?php

namespace Modules\Sales\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Tests\TestCase;

/**
 * B1 (revision de AtomoPlatform 2026-11-16): el estatus, el folio y los totales de la
 * cotizacion eran escribibles por PATCH; una cotizacion podia quedar "convertida" sin pedido
 * o con un total distinto a sus partidas.
 */
class QuoteStatusLockTest extends TestCase
{
    public function test_status_cannot_be_changed_by_patch(): void
    {
        $quote = Quote::factory()->create(['status' => 'draft']);

        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()
            ->withData(['type' => 'quotes', 'id' => (string) $quote->id, 'attributes' => ['status' => 'converted']])
            ->patch("/api/v1/quotes/{$quote->id}")
            ->assertStatus(422)
            ->assertJsonPath('errors.0.detail', 'El estatus de la cotización cambia con sus acciones (enviar, aceptar, rechazar, convertir, cancelar).');

        $this->assertSame('draft', $quote->fresh()->status);
    }

    public function test_quote_is_born_as_draft(): void
    {
        $contact = Contact::factory()->create();

        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()
            ->withData(['type' => 'quotes', 'attributes' => [
                'contactId' => $contact->id,
                'quoteDate' => now()->toDateString(),
                'status' => 'accepted',
            ]])
            ->post('/api/v1/quotes')
            ->assertStatus(422)
            ->assertJsonPath('errors.0.detail', 'La cotización nace en borrador; el estatus cambia con sus acciones.');
    }

    public function test_totals_and_number_are_not_captured(): void
    {
        $quote = Quote::factory()->create(['status' => 'draft', 'total_amount' => 100]);

        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()
            ->withData(['type' => 'quotes', 'id' => (string) $quote->id, 'attributes' => ['quoteNumber' => 'COT-OTRO']])
            ->patch("/api/v1/quotes/{$quote->id}")
            ->assertStatus(422)
            ->assertJsonPath('errors.0.detail', 'El folio de la cotización lo asigna el sistema.');

        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()
            ->withData(['type' => 'quotes', 'id' => (string) $quote->id, 'attributes' => ['totalAmount' => 1, 'notes' => 'nota']])
            ->patch("/api/v1/quotes/{$quote->id}");

        $this->assertEqualsWithDelta(100, (float) $quote->fresh()->total_amount, 0.001);
    }
}
