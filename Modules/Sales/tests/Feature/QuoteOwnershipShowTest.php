<?php

namespace Modules\Sales\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Tests\TestCase;

/**
 * Regla 8 (2026-11): el cliente del portal tiene quotes.show y quote-items.show,
 * pero solo puede ver SUS cotizaciones. Antes el show no validaba dueno y
 * cambiando el id se leian cotizaciones ajenas (IDOR); el index si filtraba.
 */
class QuoteOwnershipShowTest extends TestCase
{
    private Quote $mine;

    private Quote $theirs;

    private QuoteItem $theirItem;

    protected function setUp(): void
    {
        parent::setUp();

        $customer = $this->getCustomerUser();
        $myContact = Contact::factory()->customer()->create(['email' => $customer->email]);
        $otherContact = Contact::factory()->customer()->create(['email' => 'otro.cliente@example.com']);

        $this->mine = Quote::factory()->create(['contact_id' => $myContact->id]);
        $this->theirs = Quote::factory()->create(['contact_id' => $otherContact->id]);
        $this->theirItem = QuoteItem::factory()->create(['quote_id' => $this->theirs->id]);
    }

    public function test_el_cliente_ve_su_cotizacion(): void
    {
        $this->actingAs($this->getCustomerUser(), 'sanctum')
            ->jsonApi()->expects('quotes')
            ->get("/api/v1/quotes/{$this->mine->id}")
            ->assertFetchedOne($this->mine);
    }

    public function test_el_cliente_no_ve_una_cotizacion_ajena_por_id(): void
    {
        $this->actingAs($this->getCustomerUser(), 'sanctum')
            ->jsonApi()->expects('quotes')
            ->get("/api/v1/quotes/{$this->theirs->id}")
            ->assertStatus(403);
    }

    public function test_el_cliente_no_ve_partidas_ajenas(): void
    {
        // Las cotizaciones no exponen rutas de relacion (/quotes/{id}/items);
        // las partidas se piden por su propio recurso.
        $this->actingAs($this->getCustomerUser(), 'sanctum')
            ->jsonApi()->expects('quote-items')
            ->get("/api/v1/quote-items/{$this->theirItem->id}")
            ->assertStatus(403);
    }

    public function test_el_cliente_no_descarga_el_pdf_de_una_cotizacion_ajena(): void
    {
        $customer = $this->getCustomerUser();

        $this->actingAs($customer, 'sanctum')
            ->get("/api/v1/quotes/{$this->theirs->id}/pdf/stream")
            ->assertStatus(403);

        $this->actingAs($customer, 'sanctum')
            ->get("/api/v1/quotes/{$this->theirs->id}/pdf/download")
            ->assertStatus(403);
    }

    /**
     * En produccion el rol del portal se llama `cliente` (lo creo el cliente
     * desde Usuarios) y tiene quotes.index y quotes.show. Antes solo `customer`
     * contaba como cliente: un `cliente` veia TODAS las cotizaciones en la lista
     * y por id (medido en prod el 2026-11-07).
     */
    public function test_el_rol_cliente_de_produccion_tambien_queda_restringido(): void
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'cliente', 'guard_name' => 'api']);
        $role->syncPermissions(['quotes.index', 'quotes.show', 'quote-items.index', 'quote-items.show']);
        $user = \App\Models\User::factory()->create(['email' => 'portal.cliente@example.com']);
        $user->assignRole('cliente');
        $own = Quote::factory()->create(['contact_id' => Contact::factory()->customer()->create(['email' => $user->email])->id]);

        $this->actingAs($user, 'sanctum')
            ->jsonApi()->expects('quotes')
            ->get("/api/v1/quotes/{$this->theirs->id}")
            ->assertStatus(403);

        $ids = collect($this->actingAs($user, 'sanctum')
            ->jsonApi()->expects('quotes')
            ->get('/api/v1/quotes')
            ->assertSuccessful()
            ->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertSame([$own->id], $ids, 'El cliente debe ver solo sus cotizaciones en la lista');
    }

    public function test_el_personal_sigue_viendo_todas(): void
    {
        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()->expects('quotes')
            ->get("/api/v1/quotes/{$this->theirs->id}")
            ->assertFetchedOne($this->theirs);

        $this->actingAs($this->getTechUser(), 'sanctum')
            ->jsonApi()->expects('quotes')
            ->get("/api/v1/quotes/{$this->theirs->id}")
            ->assertFetchedOne($this->theirs);
    }
}
