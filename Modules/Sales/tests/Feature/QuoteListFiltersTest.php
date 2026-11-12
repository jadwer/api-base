<?php

namespace Modules\Sales\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Filtros del listado del rediseno (12-11-2026): la busqueda encuentra por
 * folio, cliente o vendedor (antes solo el folio exacto) y "por vencer" filtra
 * de verdad (antes el boton "Ver todas" no mandaba nada).
 */
class QuoteListFiltersTest extends TestCase
{
    private function ids(string $query): array
    {
        return collect($this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('quotes')
            ->get('/api/v1/quotes?'.$query)->assertOk()->json('data'))->pluck('id')->all();
    }

    public function test_la_busqueda_encuentra_por_folio_parcial_cliente_y_vendedor(): void
    {
        $farma = Contact::factory()->customer()->create(['name' => 'Farmabiot SA', 'email' => 'compras@farmabiot.mx']);
        $otro = Contact::factory()->customer()->create(['name' => 'Otro Cliente']);
        $ana = User::factory()->create(['name' => 'Ana Ventas']);
        $a = Quote::factory()->create(['contact_id' => $farma->id, 'quote_number' => 'COT-26009991']);
        $b = Quote::factory()->create(['contact_id' => $otro->id, 'quote_number' => 'COT-26009992', 'assigned_to' => $ana->id]);

        $this->assertSame([(string) $a->id], $this->ids('filter[search]=farmabiot'));
        $this->assertSame([(string) $a->id], $this->ids('filter[search]=compras@farma'));
        $this->assertEqualsCanonicalizing([(string) $a->id, (string) $b->id], $this->ids('filter[search]=COT-2600999'));
        $this->assertSame([(string) $b->id], $this->ids('filter[search]=Ana Ventas'));
    }

    public function test_por_vencer_filtra_enviadas_dentro_del_plazo(): void
    {
        $contact = Contact::factory()->customer()->create();
        $soon = Quote::factory()->create(['contact_id' => $contact->id, 'status' => 'sent', 'valid_until' => now()->addDays(3)->toDateString()]);
        Quote::factory()->create(['contact_id' => $contact->id, 'status' => 'sent', 'valid_until' => now()->addDays(20)->toDateString()]);
        Quote::factory()->create(['contact_id' => $contact->id, 'status' => 'draft', 'valid_until' => now()->addDays(3)->toDateString()]);

        $this->assertSame([(string) $soon->id], $this->ids('filter[expiringWithin]=7'));
    }
}
