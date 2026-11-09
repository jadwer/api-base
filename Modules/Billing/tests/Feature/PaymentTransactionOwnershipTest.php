<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Billing\Models\PaymentTransaction;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\SalesOrder;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Regla 8 (2026-11-09): el cliente del portal tiene billing.payment-transactions.index
 * y .show, pero solo puede ver las transacciones de SUS compras. Antes el listado
 * traia las de todos los clientes (montos, tarjeta y pedido).
 */
class PaymentTransactionOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private PaymentTransaction $mine;

    private PaymentTransaction $theirs;

    protected function setUp(): void
    {
        parent::setUp();
        $customer = $this->getCustomerUser();
        foreach (['billing.payment-transactions.index', 'billing.payment-transactions.show'] as $permission) {
            $customer->givePermissionTo(Permission::findOrCreate($permission, 'api'));
        }
        $myContact = Contact::factory()->customer()->create(['email' => $customer->email]);
        $otherContact = Contact::factory()->customer()->create(['email' => 'otro.cliente@example.com']);
        $myOrder = SalesOrder::factory()->create(['contact_id' => $myContact->id]);
        $theirOrder = SalesOrder::factory()->create(['contact_id' => $otherContact->id]);
        $this->mine = PaymentTransaction::factory()->create(['sales_order_id' => $myOrder->id, 'checkout_session_id' => null]);
        $this->theirs = PaymentTransaction::factory()->create(['sales_order_id' => $theirOrder->id, 'checkout_session_id' => null]);
    }

    public function test_el_cliente_lista_solo_sus_transacciones(): void
    {
        $response = $this->actingAs($this->getCustomerUser(), 'sanctum')->jsonApi()->expects('payment-transactions')
            ->get('/api/v1/payment-transactions?page[size]=100')->assertOk();
        $ids = array_column($response->json('data'), 'id');

        $this->assertSame([(string) $this->mine->id], $ids);
    }

    public function test_el_cliente_ve_la_suya_y_no_la_ajena_por_id(): void
    {
        $this->actingAs($this->getCustomerUser(), 'sanctum')->jsonApi()->expects('payment-transactions')
            ->get("/api/v1/payment-transactions/{$this->mine->id}")->assertFetchedOne($this->mine);
        $this->actingAs($this->getCustomerUser(), 'sanctum')->jsonApi()->expects('payment-transactions')
            ->get("/api/v1/payment-transactions/{$this->theirs->id}")->assertStatus(403);
    }

    public function test_el_personal_con_el_permiso_ve_todas(): void
    {
        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('payment-transactions')
            ->get('/api/v1/payment-transactions?page[size]=100')->assertOk();
        $ids = array_column($response->json('data'), 'id');

        $this->assertContains((string) $this->theirs->id, $ids);
        $this->assertContains((string) $this->mine->id, $ids);
    }
}
