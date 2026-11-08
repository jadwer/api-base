<?php

namespace Modules\Sales\Tests\Feature;

use App\Models\User;
use Atomo\Guide\Guide;
use Atomo\Guide\NextAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Models\Remission;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Services\OrderStatusService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Flujo guiado F3: lo que la guia (GET /api/v1/guide/{tipo}/{id}) dice de
 * cotizaciones, pedidos y remisiones, el expediente que arma y las reglas del
 * VAM: la frase sale del backend en espanol, "puede hacerlo" es la misma regla
 * que el endpoint, toda accion con body declara `asks` (D10) y el cliente del
 * portal solo lee la guia de sus documentos.
 */
class GuideStepsTest extends TestCase
{
    use RefreshDatabase;

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'api'));
        }

        return $user;
    }

    private function quote(string $status = 'draft', ?Contact $contact = null, float $price = 100): Quote
    {
        $quote = Quote::factory()->create(['status' => $status, 'contact_id' => ($contact ?? Contact::factory()->customer()->create())->id]);
        QuoteItem::factory()->create(['quote_id' => $quote->id, 'unit_price' => $price, 'quoted_price' => $price]);

        return $quote;
    }

    private function order(string $status, ?Quote $quote = null): SalesOrder
    {
        $order = SalesOrder::factory()->create([
            'status' => $status,
            'quote_id' => $quote?->id,
            'contact_id' => $quote?->contact_id ?? Contact::factory()->customer()->create()->id,
        ]);
        SalesOrderItem::factory()->create(['sales_order_id' => $order->id, 'quantity' => 2]);
        $quote?->update(['sales_order_id' => $order->id, 'status' => 'converted']);

        return $order;
    }

    private function guide(\Illuminate\Contracts\Auth\Authenticatable $user, string $type, int $id, array $headers = [])
    {
        return $this->actingAs($user, 'sanctum')->withHeaders($headers)->getJson("/api/v1/guide/{$type}/{$id}");
    }

    public function test_la_cotizacion_en_borrador_ofrece_enviar_aceptar_directo_y_cancelar_en_espanol(): void
    {
        $quote = $this->quote('draft');

        $response = $this->guide($this->getAdminUser(), 'quotes', $quote->id, ['Accept-Language' => 'en-US,en;q=0.9'])->assertOk();

        $response->assertJsonPath('version', 1)
            ->assertJsonPath('data.type', 'quotes')
            ->assertJsonPath('data.label', $quote->quote_number)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.department', 'ventas')
            ->assertJsonPath('data.next.0.key', 'sales::guide.quote.send')
            ->assertJsonPath('data.next.0.phrase', "Envía la cotización {$quote->quote_number} al cliente")
            ->assertJsonPath('data.next.0.kind', 'primary')
            ->assertJsonPath('data.next.0.allowed', true)
            ->assertJsonPath('data.next.0.asks', ['recipients'])
            ->assertJsonPath('data.next.0.action.path', "/api/v1/quotes/{$quote->id}/send")
            ->assertJsonPath('data.next.1.key', 'sales::guide.quote.accept_direct')
            ->assertJsonPath('data.next.1.kind', 'secondary')
            ->assertJsonPath('data.next.2.key', 'sales::guide.quote.cancel')
            ->assertJsonPath('data.trail.0.current', true);
        $this->assertCount(1, $response->json('data.trail'));
    }

    public function test_con_precio_por_confirmar_lo_primero_es_capturarlo_y_convertir_queda_bloqueado(): void
    {
        $quote = $this->quote('accepted', price: 0);

        $response = $this->guide($this->getAdminUser(), 'quotes', $quote->id)->assertOk();

        $response->assertJsonPath('data.next.0.key', 'sales::guide.quote.confirm_price')
            ->assertJsonPath('data.next.0.phrase', "Captura el precio por confirmar de {$quote->quote_number} (1 partidas)")
            ->assertJsonPath('data.next.0.asks', ['items'])
            ->assertJsonPath('data.next.1.key', 'sales::guide.quote.convert')
            ->assertJsonPath('data.next.1.kind', 'secondary')
            ->assertJsonPath('data.next.1.blockers.0.field', 'price_pending');
        $this->assertStringStartsWith('Captura el precio de venta', $response->json('data.next.1.blockers.0.message'));
    }

    public function test_convertir_pide_lo_mismo_que_el_endpoint_y_su_permiso_es_el_del_endpoint(): void
    {
        $quote = $this->quote('accepted');

        $this->guide($this->userWith('quotes.show'), 'quotes', $quote->id)->assertOk()
            ->assertJsonPath('data.next.0.key', 'sales::guide.quote.convert')
            ->assertJsonPath('data.next.0.allowed', false)
            ->assertJsonPath('data.next.0.asks', ['order_type', 'acceptance_channel', 'customer_po_number', 'payment_method', 'credit_days'])
            ->assertJsonPath('data.next.0.blockers', []);
        $this->guide($this->userWith('quotes.show', 'quotes.update'), 'quotes', $quote->id)->assertOk()
            ->assertJsonPath('data.next.0.allowed', true);
    }

    public function test_el_pedido_pendiente_se_confirma_con_el_permiso_asignable_y_el_body_del_endpoint(): void
    {
        $order = $this->order('pending');

        $this->guide($this->userWith('sales-orders.show', 'sales-orders.update'), 'sales-orders', $order->id)->assertOk()
            ->assertJsonPath('data.department', 'ventas')
            ->assertJsonPath('data.next.0.key', 'sales::guide.order.confirm')
            ->assertJsonPath('data.next.0.phrase', "Confirma el pedido {$order->order_number}")
            ->assertJsonPath('data.next.0.allowed', false)
            ->assertJsonPath('data.next.0.action.method', 'POST')
            ->assertJsonPath('data.next.0.action.path', "/api/v1/orders/{$order->id}/status")
            ->assertJsonPath('data.next.0.action.body.status', 'confirmed')
            ->assertJsonPath('data.next.1.key', 'sales::guide.order.cancel');
        $this->guide($this->userWith('sales-orders.show', 'sales-orders.confirm'), 'sales-orders', $order->id)
            ->assertJsonPath('data.next.0.allowed', true);
    }

    public function test_el_pedido_confirmado_manda_a_almacen_a_remisionar_y_entregar_es_de_god_admin(): void
    {
        $order = $this->order('confirmed');

        $response = $this->guide($this->userWith('sales-orders.show', 'remissions.store'), 'sales-orders', $order->id)->assertOk();

        $response->assertJsonPath('data.department', 'almacen')
            ->assertJsonPath('data.next.0.key', 'sales::guide.order.remit')
            ->assertJsonPath('data.next.0.kind', 'primary')
            ->assertJsonPath('data.next.0.allowed', true)
            ->assertJsonPath('data.next.0.action.path', '/api/v1/remissions/from-order-full')
            ->assertJsonPath('data.next.0.action.body.sales_order_id', $order->id)
            ->assertJsonPath('data.next.0.asks', ['items', 'warehouse_id', 'delivery_notes'])
            ->assertJsonPath('data.next.1.key', 'sales::guide.order.deliver')
            ->assertJsonPath('data.next.1.kind', 'secondary')
            ->assertJsonPath('data.next.1.allowed', false)
            ->assertJsonPath('data.next.1.action.body.status', 'delivered');
        $this->guide($this->getAdminUser(), 'sales-orders', $order->id)->assertJsonPath('data.next.1.allowed', true);

        // La guia y el endpoint leen la misma regla: sin ser god/admin, entregar a mano da 403.
        $this->actingAs($this->userWith('sales-orders.update', 'sales-orders.confirm'), 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/status", ['status' => 'delivered'])->assertStatus(403);
    }

    public function test_el_pedido_entregado_se_factura_y_con_cfdi_vigente_ya_no_tiene_siguiente_paso(): void
    {
        $order = $this->order('delivered');

        $this->guide($this->userWith('sales-orders.show', 'billing.cfdi-invoices.store'), 'sales-orders', $order->id)->assertOk()
            ->assertJsonPath('data.department', 'facturacion')
            ->assertJsonPath('data.next.0.key', 'sales::guide.order.invoice')
            ->assertJsonPath('data.next.0.allowed', true)
            ->assertJsonPath('data.next.0.asks', ['forma_pago'])
            ->assertJsonPath('data.next.0.action.path', "/api/v1/sales-orders/{$order->id}/facturar");

        $invoice = ARInvoice::factory()->create(['sales_order_id' => $order->id, 'contact_id' => $order->contact_id, 'status' => 'pending']);
        CFDIInvoice::factory()->valid()->create(['ar_invoice_id' => $invoice->id, 'contact_id' => $order->contact_id]);

        $this->guide($this->getAdminUser(), 'sales-orders', $order->id)->assertOk()
            ->assertJsonPath('data.department', 'system')
            ->assertJsonPath('data.next', []);
    }

    public function test_la_remision_imprime_y_luego_entrega_pidiendo_quien_recibe(): void
    {
        $order = $this->order('confirmed');
        $draft = Remission::factory()->forOrder($order)->create(['status' => 'draft']);
        $printed = Remission::factory()->forOrder($order)->printed()->create();

        $this->guide($this->getAdminUser(), 'remissions', $draft->id)->assertOk()
            ->assertJsonPath('data.label', $draft->remission_number)
            ->assertJsonPath('data.department', 'almacen')
            ->assertJsonPath('data.next.0.key', 'sales::guide.remission.print')
            ->assertJsonPath('data.next.0.action.path', "/api/v1/remissions/{$draft->id}/print")
            ->assertJsonPath('data.next.1.key', 'sales::guide.remission.cancel');
        $this->guide($this->getAdminUser(), 'remissions', $printed->id)->assertOk()
            ->assertJsonPath('data.next.0.key', 'sales::guide.remission.deliver')
            ->assertJsonPath('data.next.0.asks', ['received_by', 'delivery_notes']);
        // Con remision, la entrega del pedido va por ella: el pedido ya no ofrece entregar a mano.
        $keys = array_column($this->guide($this->getAdminUser(), 'sales-orders', $order->id)->json('data.next'), 'key');
        $this->assertNotContains('sales::guide.order.deliver', $keys);
        $this->assertSame(['sales::guide.order.remit', 'sales::guide.order.cancel'], $keys);
    }

    public function test_el_expediente_arranca_en_la_cotizacion_y_cuelga_pedido_remision_cxc_cfdi_y_oc(): void
    {
        $quote = $this->quote('accepted');
        $order = $this->order('delivered', $quote);
        $remission = Remission::factory()->forOrder($order)->delivered()->create();
        $invoice = ARInvoice::factory()->create(['sales_order_id' => $order->id, 'contact_id' => $order->contact_id, 'status' => 'pending']);
        $cfdi = CFDIInvoice::factory()->valid()->create(['ar_invoice_id' => $invoice->id, 'contact_id' => $order->contact_id]);
        $po = PurchaseOrder::factory()->pending()->create(['quote_id' => $quote->id]);

        $trail = $this->guide($this->getAdminUser(), 'remissions', $remission->id)->assertOk()->json('data.trail');

        $this->assertSame(
            ['quotes:'.$quote->id, 'sales-orders:'.$order->id, 'purchase-orders:'.$po->id, 'remissions:'.$remission->id, 'ar-invoices:'.$invoice->id, 'cfdi-invoices:'.$cfdi->id],
            array_map(fn ($d) => $d['type'].':'.$d['id'], $trail)
        );
        $current = array_values(array_filter($trail, fn ($d) => $d['current']));
        $this->assertSame(['remissions'], array_column($current, 'type'));
        $this->assertSame('compras', $trail[2]['department']);
        $this->assertSame('cobranza', $trail[4]['department']);
        $this->assertSame($cfdi->series.'-'.$cfdi->folio, $trail[5]['label']);

        // Lo que no se puede ver no sale, y la cadena sigue (la OC no es de ventas).
        $staff = $this->userWith('quotes.show', 'sales-orders.show', 'remissions.show');
        $types = array_column($this->guide($staff, 'quotes', $quote->id)->json('data.trail'), 'type');
        $this->assertSame(['quotes', 'sales-orders', 'remissions'], $types);
    }

    public function test_el_historial_del_pedido_une_status_history_y_activity_log(): void
    {
        $this->actingAs($this->getAdminUser(), 'sanctum');
        $order = $this->order('pending');
        app(OrderStatusService::class)->updateStatus($order, 'confirmed', 'Confirmado por teléfono', [], ['notify' => false]);
        $order->refresh();

        $history = $this->guide($this->getAdminUser(), 'sales-orders', $order->id)->json('data.trail.0.history');

        $confirmed = array_values(array_filter($history, fn ($h) => $h['to'] === 'confirmed'));
        $this->assertCount(1, $confirmed, 'El mismo paso en metadata y en el log se muestra una sola vez');
        $this->assertSame('pending', $confirmed[0]['from']);
        $this->assertSame($this->getAdminUser()->name, $confirmed[0]['by']);
        $this->assertSame('Confirmado por teléfono', $confirmed[0]['reason']);
        $this->assertNotNull($confirmed[0]['at']);
    }

    /** D10: toda accion cuyo endpoint valida body declara `asks` (o pide motivo); la barra nunca inventa el formulario. */
    public function test_toda_accion_con_body_declara_asks_o_motivo(): void
    {
        $withBody = ['quotes.send', 'quotes.reject', 'quotes.convert', 'quotes.update', 'sales-orders.remit', 'sales-orders.invoice', 'remissions.deliver'];
        $quote = $this->quote('draft');
        $documents = [
            $quote, $this->quote('sent'), $this->quote('accepted', price: 0), $this->quote('expired'),
            $this->order('pending'), $this->order('confirmed'), $this->order('delivered'),
            Remission::factory()->forOrder($this->order('confirmed'))->create(['status' => 'draft']),
            Remission::factory()->forOrder($this->order('confirmed'))->printed()->create(),
        ];
        $seen = [];
        foreach ($documents as $document) {
            $entry = Guide::forModel($document);
            /** @var NextAction $action */
            foreach (app($entry['provider'])->next($document, $this->getAdminUser()) as $action) {
                $seen[] = $action->ability;
                if (in_array($action->ability, $withBody, true)) {
                    $this->assertTrue($action->askedFields() !== [] || $action->requiresReason(), "{$action->ability} valida body y no declara asks");
                }
                $this->assertStringStartsWith('sales::guide.', $action->key);
                $this->assertNotSame($action->key, __($action->key), "Falta la frase en espanol de {$action->key}");
            }
        }
        $this->assertSame([], array_diff($withBody, $seen), 'Las fixtures deben alcanzar todas las acciones con body');
    }

    /** Auditoria 5a del plan: cada action.path y metodo que ofrece la guia existe en las rutas registradas. */
    public function test_toda_accion_apunta_a_una_ruta_registrada(): void
    {
        $documents = [
            $this->quote('draft'), $this->quote('sent'), $this->quote('accepted', price: 0), $this->quote('expired'),
            $this->order('pending'), $this->order('confirmed'), $this->order('delivered'),
            Remission::factory()->forOrder($this->order('confirmed'))->create(['status' => 'draft']),
            Remission::factory()->forOrder($this->order('confirmed'))->printed()->create(),
        ];
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        $checked = 0;
        foreach ($documents as $document) {
            $entry = Guide::forModel($document);
            foreach (app($entry['provider'])->next($document, $this->getAdminUser()) as $action) {
                $path = '/api/v1/'.str_replace(['{type}', '{id}'], [$entry['type'], (string) $document->getKey()], $action->path);
                try {
                    $routes->match(\Illuminate\Http\Request::create($path, $action->method));
                } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                    $this->fail("{$action->ability}: {$action->method} {$path} no corresponde a ninguna ruta ({$e->getMessage()})");
                }
                $checked++;
            }
        }
        $this->assertGreaterThan(10, $checked);
    }

    public function test_el_cliente_del_portal_solo_lee_la_guia_de_sus_documentos(): void
    {
        Role::firstOrCreate(['name' => 'cliente', 'guard_name' => 'api']);
        $customer = $this->userWith('quotes.index', 'quotes.show', 'sales-orders.index', 'sales-orders.show');
        $customer->assignRole('cliente');
        $own = Contact::factory()->customer()->create(['email' => $customer->email]);
        $mine = $this->quote('accepted', $own);
        $myOrder = $this->order('confirmed', $mine);
        $other = $this->quote('sent');

        $this->guide($customer, 'quotes', $mine->id)->assertOk()
            ->assertJsonPath('data.label', $mine->quote_number)
            ->assertJsonPath('data.trail.1.type', 'sales-orders');
        $this->guide($customer, 'sales-orders', $myOrder->id)->assertOk();
        $this->guide($customer, 'quotes', $other->id)->assertNotFound();
        $this->guide($customer, 'sales-orders', $this->order('confirmed')->id)->assertNotFound();
        // Lo mismo con el nombre que usa el seeder limpio.
        $customer->syncRoles(['customer']);
        $this->guide($customer, 'quotes', $other->id)->assertNotFound();
        $this->guide($customer, 'quotes', $mine->id)->assertOk();

        // El personal con el permiso ve cualquiera (regla de la guia).
        $this->guide($this->userWith('quotes.show'), 'quotes', $other->id)->assertOk();
    }

    public function test_el_cfdi_se_lee_con_el_permiso_de_billing_y_sin_el_responde_404(): void
    {
        $cfdi = CFDIInvoice::factory()->valid()->create();

        $this->guide($this->userWith('billing.cfdi-invoices.show'), 'cfdi-invoices', $cfdi->id)->assertOk()
            ->assertJsonPath('data.type', 'cfdi-invoices')
            ->assertJsonPath('data.department', 'system');
        $this->guide($this->userWith('quotes.show'), 'cfdi-invoices', $cfdi->id)->assertNotFound();
        $this->assertFalse(Permission::where('name', 'cfdi-invoices.show')->exists(), 'La habilidad puente no debe existir como permiso (D3)');
    }
}
