<?php

namespace Modules\Purchase\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Purchase\Models\PurchaseOrder;
use Tests\TestCase;

/** ETA de la orden de compra: la da el proveedor (junta con el cliente 2026-11-12, J1). */
class PurchaseOrderExpectedArrivalTest extends TestCase
{
    private function withItems(array $extra)
    {
        return $this->actingAs($this->getAdminUser(), 'sanctum')->postJson('/api/v1/purchase-orders/with-items', $extra + [
            'contact_id' => Contact::factory()->create(['is_supplier' => true])->id,
            'order_date' => '2026-11-15',
            'status' => 'pending',
            'items' => [['product_id' => Product::factory()->create()->id, 'quantity' => 2, 'unit_price' => 100, 'discount' => 0]],
        ]);
    }

    public function test_la_orden_con_partidas_guarda_la_eta_y_la_expone(): void
    {
        $id = $this->withItems(['expected_arrival_date' => '2026-11-28'])->assertCreated()->json('data.id')
            ?? PurchaseOrder::query()->latest('id')->value('id');

        $this->assertSame('2026-11-28', PurchaseOrder::findOrFail($id)->expected_arrival_date?->toDateString());
        $this->jsonApi()->expects('purchase-orders')->get("/api/v1/purchase-orders/{$id}")
            ->assertOk()->assertJsonPath('data.attributes.expectedArrivalDate', '2026-11-28');
    }

    public function test_la_eta_es_opcional(): void
    {
        $this->withItems([])->assertCreated();
        $this->assertNull(PurchaseOrder::query()->latest('id')->first()->expected_arrival_date);
    }

    public function test_la_eta_no_puede_ser_antes_de_la_orden_y_el_error_va_en_espanol(): void
    {
        $this->withItems(['expected_arrival_date' => '2026-11-01'])
            ->assertStatus(422)
            ->assertJsonPath('errors.expected_arrival_date.0', 'El campo fecha estimada de llegada debe ser una fecha posterior o igual a fecha de la orden.');
    }

    public function test_se_puede_capturar_despues_al_editar_la_orden(): void
    {
        $this->actingAs($this->getAdminUser(), 'sanctum');
        $order = PurchaseOrder::factory()->create(['order_date' => '2026-11-15', 'status' => 'pending']);

        $this->jsonApi()->expects('purchase-orders')->withData([
            'type' => 'purchase-orders',
            'id' => (string) $order->id,
            'attributes' => ['expectedArrivalDate' => '2026-12-02'],
        ])->patch("/api/v1/purchase-orders/{$order->id}")->assertOk();

        $this->assertSame('2026-12-02', $order->fresh()->expected_arrival_date?->toDateString());
    }
}
