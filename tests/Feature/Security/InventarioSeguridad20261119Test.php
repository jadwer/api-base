<?php

namespace Tests\Feature\Security;

use Modules\Contacts\Models\Contact;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Inventory\Models\ProductBatch;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Sales\Models\SalesOrder;
use Tests\TestCase;

/**
 * Cuatro huecos de permisos del inventario de pendientes del 19-11, verificados en el codigo y,
 * el del portal, contra dev: un cliente podia fijarse el descuento del carrito, rechazar (y asi
 * cancelar) una orden de compra, poner un lote en cuarentena y cancelar un pedido confirmado.
 */
class InventarioSeguridad20261119Test extends TestCase
{
    public function test_cart_owner_cannot_set_discount_shipping_tax_or_coupon(): void
    {
        $customer = $this->getCustomerUser();
        $cart = ShoppingCart::factory()->create([
            'user_id' => $customer->id,
            'status' => 'active',
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'coupon_code' => null,
        ]);

        $this->actingAs($customer, 'sanctum')->jsonApi()
            ->withData(['type' => 'shopping-carts', 'id' => (string) $cart->id, 'attributes' => [
                'status' => 'active',
                'totalAmount' => 1,
                'currency' => 'MXN',
                'discountAmount' => 999,
                'shippingAmount' => 0,
                'taxAmount' => 0,
                'couponCode' => 'GRATIS',
            ]])
            ->patch("/api/v1/shopping-carts/{$cart->id}");

        $cart->refresh();
        $this->assertEquals(0, (float) $cart->discount_amount);
        $this->assertNull($cart->coupon_code);
    }

    public function test_customer_cannot_reject_a_purchase_order(): void
    {
        $order = PurchaseOrder::factory()->create(['status' => 'pending']);

        $this->actingAs($this->getCustomerUser(), 'sanctum')
            ->postJson("/api/v1/purchase-orders/{$order->id}/reject", ['reason' => 'No la quiero'])
            ->assertForbidden()
            ->assertJsonPath('errors.0.detail', 'No tiene permisos para rechazar esta orden de compra.');

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_admin_can_still_reject_a_pending_purchase_order(): void
    {
        $order = PurchaseOrder::factory()->create(['status' => 'pending']);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/purchase-orders/{$order->id}/reject", ['reason' => 'Precio fuera de presupuesto'])
            ->assertOk();
    }

    public function test_customer_cannot_use_lot_traceability_or_recall(): void
    {
        $batch = ProductBatch::factory()->create();
        $customer = $this->getCustomerUser();

        $this->actingAs($customer, 'sanctum')->getJson("/api/v1/lot-traceability/{$batch->id}/genealogy")->assertForbidden();
        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/lot-traceability/{$batch->id}/initiate-recall", ['reason' => 'Retiro hecho por un cliente'])
            ->assertForbidden();

        $this->assertNotSame('recalled', $batch->fresh()->status);
    }

    public function test_customer_cannot_cancel_a_confirmed_order_from_the_portal(): void
    {
        $customer = $this->getCustomerUser();
        $contact = Contact::factory()->create(['email' => $customer->email, 'is_customer' => true]);
        $confirmed = SalesOrder::factory()->create(['contact_id' => $contact->id, 'status' => 'confirmed']);
        $pending = SalesOrder::factory()->create(['contact_id' => $contact->id, 'status' => 'pending', 'payment_status' => 'unpaid']);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/my-orders/{$confirmed->id}/cancel", ['reason' => 'ya no'])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Tu pedido ya está confirmado, pagado o facturado. Para cancelarlo comunícate con ventas.');
        $this->assertSame('confirmed', $confirmed->fresh()->status);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/my-orders/{$pending->id}/cancel", ['reason' => 'me equivoque'])
            ->assertOk();
    }
}
