<?php

namespace Modules\Reports\Tests\Feature;

use Carbon\Carbon;
use Modules\Contacts\Models\Contact;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Reports\Services\PurchaseReports\PurchaseBySupplierReportService;
use Modules\Reports\Services\SalesReports\SalesByCustomerReportService;
use Modules\Sales\Models\SalesOrder;
use Tests\TestCase;

/**
 * REP-06 (2026-11-22): los reportes que usa la pantalla agrupaban por columnas
 * que no existen (customer_id, supplier_id) y todo caia en una sola fila.
 */
class JsonApiReportsGroupingTest extends TestCase
{
    public function test_ventas_por_cliente_da_una_fila_por_cliente(): void
    {
        $a = Contact::factory()->customer()->create(['name' => 'Cliente Uno']);
        $b = Contact::factory()->customer()->create(['name' => 'Cliente Dos']);
        SalesOrder::factory()->create(['contact_id' => $a->id, 'status' => 'delivered', 'order_date' => now(), 'total_amount' => 100]);
        SalesOrder::factory()->create(['contact_id' => $a->id, 'status' => 'delivered', 'order_date' => now(), 'total_amount' => 50]);
        SalesOrder::factory()->create(['contact_id' => $b->id, 'status' => 'delivered', 'order_date' => now(), 'total_amount' => 300]);

        $report = app(SalesByCustomerReportService::class)->generate(Carbon::now()->subDay(), Carbon::now()->addDay());
        $rows = collect($report['salesByCustomer'])->whereIn('customer_name', ['Cliente Uno', 'Cliente Dos'])->keyBy('customer_name');

        $this->assertCount(2, $rows);
        $this->assertEquals(150.0, $rows['Cliente Uno']['total_amount']);
        $this->assertSame(2, $rows['Cliente Uno']['total_orders']);
        $this->assertEquals(300.0, $rows['Cliente Dos']['total_amount']);
    }

    public function test_compras_por_proveedor_da_una_fila_por_proveedor(): void
    {
        $a = Contact::factory()->create(['name' => 'Proveedor Uno', 'is_supplier' => true]);
        $b = Contact::factory()->create(['name' => 'Proveedor Dos', 'is_supplier' => true]);
        PurchaseOrder::factory()->create(['contact_id' => $a->id, 'status' => 'received', 'order_date' => now(), 'total_amount' => 80]);
        PurchaseOrder::factory()->create(['contact_id' => $b->id, 'status' => 'received', 'order_date' => now(), 'total_amount' => 20]);

        $report = app(PurchaseBySupplierReportService::class)->generate(Carbon::now()->subDay(), Carbon::now()->addDay());
        $rows = collect($report['purchasesBySupplier'])->whereIn('supplier_name', ['Proveedor Uno', 'Proveedor Dos']);

        $this->assertCount(2, $rows);
    }
}
