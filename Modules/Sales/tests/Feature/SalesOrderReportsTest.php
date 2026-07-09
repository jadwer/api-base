<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;

class SalesOrderReportsTest extends TestCase
{
    /**
     * El TestDatabaseSeeder siembra ordenes dentro de la ventana del reporte,
     * asi que se mide un baseline y se compara el delta exacto.
     */
    private function getStatusRow(string $status): array
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sales-orders/reports?period=30');

        $response->assertOk();

        $row = collect($response->json('data.attributes.sales_by_status'))
            ->firstWhere('status', $status);

        return [
            'count' => (int) ($row['count'] ?? 0),
            'revenue' => (float) ($row['revenue'] ?? 0),
            'total_revenue' => (float) $response->json('data.attributes.summary.total_revenue'),
        ];
    }

    public function test_sales_by_status_revenue_is_not_duplicated_for_orders_with_multiple_items(): void
    {
        $baseline = $this->getStatusRow('confirmed');

        $contact = Contact::factory()->create(['is_customer' => true]);

        $order = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'confirmed',
            'created_at' => now()->subDay(),
        ]);

        // 3 items: 100 + 200 + 300 = 600 exactos
        foreach ([100.00, 200.00, 300.00] as $total) {
            SalesOrderItem::factory()->create([
                'sales_order_id' => $order->id,
                'quantity' => 1,
                'unit_price' => $total,
                'discount' => 0,
                'total' => $total,
            ]);
        }

        $after = $this->getStatusRow('confirmed');

        // Una orden mas, revenue exacto de 600 (sin duplicar por numero de items)
        $this->assertEquals($baseline['count'] + 1, $after['count']);
        $this->assertEqualsWithDelta($baseline['revenue'] + 600.00, $after['revenue'], 0.01);

        // El summary tambien debe reflejar el total sin duplicar
        $this->assertEqualsWithDelta($baseline['total_revenue'] + 600.00, $after['total_revenue'], 0.01);
    }

    public function test_sales_by_status_groups_multiple_orders_correctly(): void
    {
        $baselineConfirmed = $this->getStatusRow('confirmed');
        $baselineDraft = $this->getStatusRow('draft');

        $contact = Contact::factory()->create(['is_customer' => true]);

        // Dos ordenes confirmed (150 y 250) y una draft (50)
        foreach ([150.00, 250.00] as $total) {
            $order = SalesOrder::factory()->create([
                'contact_id' => $contact->id,
                'status' => 'confirmed',
                'created_at' => now()->subDay(),
            ]);
            SalesOrderItem::factory()->create([
                'sales_order_id' => $order->id,
                'quantity' => 1,
                'unit_price' => $total,
                'discount' => 0,
                'total' => $total,
            ]);
        }

        $draftOrder = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'draft',
            'created_at' => now()->subDay(),
        ]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $draftOrder->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'discount' => 0,
            'total' => 50.00,
        ]);

        $afterConfirmed = $this->getStatusRow('confirmed');
        $afterDraft = $this->getStatusRow('draft');

        $this->assertEquals($baselineConfirmed['count'] + 2, $afterConfirmed['count']);
        $this->assertEqualsWithDelta($baselineConfirmed['revenue'] + 400.00, $afterConfirmed['revenue'], 0.01);

        $this->assertEquals($baselineDraft['count'] + 1, $afterDraft['count']);
        $this->assertEqualsWithDelta($baselineDraft['revenue'] + 50.00, $afterDraft['revenue'], 0.01);
    }
}
