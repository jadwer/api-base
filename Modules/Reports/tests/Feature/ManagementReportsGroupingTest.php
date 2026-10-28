<?php

namespace Modules\Reports\Tests\Feature;

use Carbon\Carbon;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\Product\Models\Product;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Reports\Services\ManagementReports\AgingReportService;
use Modules\Reports\Services\ManagementReports\PurchaseReportService;
use Modules\Reports\Services\ManagementReports\SalesReportService;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Tests\TestCase;

/**
 * Los reportes por cliente/proveedor agrupaban por customer_id/supplier_id,
 * columnas que no existen: todo caia en una fila con el nombre del primero.
 * Hallado al cruzar la linea base de Larastan (2026-10-28).
 */
class ManagementReportsGroupingTest extends TestCase
{
    public function test_sales_by_customer_has_one_row_per_customer_and_counts_delivered_orders(): void
    {
        [$a, $b] = Contact::factory()->customer()->count(2)->create();
        SalesOrder::factory()->create(['contact_id' => $a->id, 'status' => 'delivered', 'order_date' => now(), 'total_amount' => 100]);
        SalesOrder::factory()->create(['contact_id' => $b->id, 'status' => 'confirmed', 'order_date' => now(), 'total_amount' => 50]);
        SalesOrder::factory()->create(['contact_id' => $b->id, 'status' => 'cancelled', 'order_date' => now(), 'total_amount' => 999]);

        $report = app(SalesReportService::class)->generateByCustomer(now()->subDay(), now()->addDay());

        $rows = collect($report['customers'])->keyBy('customer_id');
        $this->assertCount(2, $rows);
        $this->assertEquals(100, $rows[$a->id]['total_sales']);
        $this->assertSame($a->name, $rows[$a->id]['customer_name']);
        $this->assertEquals(50, $rows[$b->id]['total_sales']);
    }

    public function test_sales_by_product_uses_the_item_total(): void
    {
        $order = SalesOrder::factory()->create(['status' => 'delivered', 'order_date' => now()]);
        $product = Product::factory()->create();
        SalesOrderItem::factory()->create(['sales_order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2, 'unit_price' => 60, 'discount' => 0, 'total' => 120]);

        $report = app(SalesReportService::class)->generateByProduct(now()->subDay(), now()->addDay());

        $row = collect($report['products'])->firstWhere('product_id', $product->id);
        $this->assertEquals(120, $row['total_revenue']);
        $this->assertSame($product->sku, $row['product_code']);
    }

    public function test_ar_aging_groups_by_contact_and_skips_cancelled(): void
    {
        [$a, $b] = Contact::factory()->customer()->count(2)->create();
        foreach ([[$a, 'posted', 300], [$b, 'posted', 200], [$b, 'cancelled', 999]] as [$contact, $status, $amount]) {
            ARInvoice::factory()->create([
                'contact_id' => $contact->id, 'status' => $status, 'total_amount' => $amount, 'paid_amount' => 0,
                'invoice_date' => now()->subDays(10), 'due_date' => now()->addDays(20),
            ]);
        }

        $report = app(AgingReportService::class)->generateAR(Carbon::now());

        $rows = collect($report['customers'])->keyBy('customer_id');
        $this->assertCount(2, $rows);
        $this->assertEquals(300, $rows[$a->id]['total']);
        $this->assertEquals(200, $rows[$b->id]['total']);
    }

    public function test_purchases_by_supplier_has_one_row_per_supplier(): void
    {
        [$a, $b] = Contact::factory()->supplier()->count(2)->create();
        PurchaseOrder::factory()->create(['contact_id' => $a->id, 'status' => 'received', 'order_date' => now(), 'total_amount' => 70]);
        PurchaseOrder::factory()->create(['contact_id' => $b->id, 'status' => 'received', 'order_date' => now(), 'total_amount' => 30]);

        $report = app(PurchaseReportService::class)->generateBySupplier(now()->subDay(), now()->addDay());

        $this->assertCount(2, $report['suppliers']);
    }
}
