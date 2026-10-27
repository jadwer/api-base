<?php

namespace Modules\Purchase\Tests\Integration;

use Laravel\Sanctum\Sanctum;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\APInvoice;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderItem;
use Tests\TestCase;

/**
 * Reglas de recepcion de compras (hallazgo 9, 26-10): aprobacion previa,
 * tolerancia desde la config y APInvoice por lo recibido.
 */
class PurchaseOrderReceiveRulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        FiscalPeriod::firstOrCreate(
            ['year' => now()->year, 'month' => now()->month],
            ['name' => now()->format('Y-m'), 'start_date' => now()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->endOfMonth()->format('Y-m-d'), 'status' => 'open']
        );
        Sanctum::actingAs($this->getAdminUser());
    }

    /** @return array{0: PurchaseOrder, 1: PurchaseOrderItem} */
    private function order(array $attrs = [], float $qty = 10): array
    {
        $po = PurchaseOrder::factory()->create(array_merge([
            'contact_id' => Contact::factory()->create(['is_supplier' => true])->id,
            'warehouse_id' => Warehouse::factory()->create(['is_active' => true])->id,
            'status' => 'approved',
            'approval_status' => 'not_required',
            'total_amount' => $qty * 100,
        ], $attrs));
        $item = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => Product::factory()->create()->id,
            'quantity' => $qty,
            'unit_price' => 100,
            'discount' => 0,
            'subtotal' => $qty * 100,
            'total' => $qty * 100,
            'received_quantity' => 0,
        ]);

        return [$po, $item];
    }

    private function receive(PurchaseOrder $po, PurchaseOrderItem $item, float $qty)
    {
        return $this->postJson("/api/v1/purchase-orders/{$po->id}/receive", ['items' => [['id' => $item->id, 'quantity' => $qty]]]);
    }

    public function test_order_pending_approval_cannot_be_received(): void
    {
        [$po, $item] = $this->order(['status' => 'pending', 'approval_status' => 'pending']);

        $this->receive($po, $item, 5)->assertStatus(422)
            ->assertJsonPath('errors.0.detail', 'La orden de compra requiere aprobacion antes de recibirse.');
        $this->assertEquals(0, (float) $item->fresh()->received_quantity);
    }

    public function test_approving_an_order_that_needs_no_approval_says_so(): void
    {
        [$po] = $this->order(['status' => 'pending', 'approval_status' => 'not_required']);

        $this->postJson("/api/v1/purchase-orders/{$po->id}/approve")->assertStatus(422)
            ->assertJsonPath('errors.0.detail', 'Esta orden de compra no requiere aprobación; puedes recibirla directamente.');
    }

    public function test_tolerance_comes_from_config(): void
    {
        config(['purchase.receiving_tolerance_percent' => 10]);
        [$po, $item] = $this->order();

        $this->receive($po, $item, 11)->assertStatus(200);
        $this->assertEquals(11, (float) $item->fresh()->received_quantity);

        [$po2, $item2] = $this->order();
        $this->receive($po2, $item2, 11.5)->assertStatus(400);
        $this->assertEquals(0, (float) $item2->fresh()->received_quantity);
    }

    public function test_ap_invoice_subtotal_follows_received_quantity(): void
    {
        config(['purchase.receiving_tolerance_percent' => 10]);
        [$po, $item] = $this->order();

        $this->receive($po, $item, 11)->assertStatus(200);

        $invoice = APInvoice::where('purchase_order_id', $po->id)->firstOrFail();
        $this->assertEqualsWithDelta(1100, (float) $invoice->subtotal, 0.01);
    }

    /** Hallazgo 11 (decision de Gabino): el presupuesto avisa pero no bloquea. */
    public function test_approval_warns_when_budget_is_exceeded(): void
    {
        \Modules\Purchase\Models\Budget::query()->delete();
        \Modules\Purchase\Models\Budget::factory()->create([
            'name' => 'General de octubre',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'budgeted_amount' => 1000,
            'committed_amount' => 0,
            'spent_amount' => 0,
            'hard_limit' => true,
        ]);
        [$po] = $this->order(['status' => 'pending', 'approval_status' => 'pending']);

        $response = $this->postJson("/api/v1/purchase-orders/{$po->id}/approve")->assertStatus(200);

        $this->assertNotEmpty($response->json('meta.budget_warnings'));
        $this->assertStringContainsString('General de octubre', $response->json('meta.budget_warnings.0'));
        $this->assertSame('approved', $po->fresh()->status);
    }
}
