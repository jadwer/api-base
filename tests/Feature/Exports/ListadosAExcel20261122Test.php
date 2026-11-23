<?php

namespace Tests\Feature\Exports;

use App\Exports\QueryExport;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Product;
use Modules\Purchase\Models\PurchaseOrder;
use Tests\TestCase;

/** PRO-21 y COM-16 (2026-11-22): exportar a Excel con los filtros del listado. */
class ListadosAExcel20261122Test extends TestCase
{
    public function test_el_catalogo_se_exporta_con_los_filtros_y_las_claves_sat(): void
    {
        Excel::fake();
        $brand = Brand::factory()->create();
        $inside = Product::factory()->create(['brand_id' => $brand->id, 'sku' => 'EXP-0001', 'sat_clave_prod_serv' => '41116105', 'is_active' => true]);
        Product::factory()->create(['sku' => 'EXP-0002', 'is_active' => true]);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->get('/api/v1/products/export?filter[brand_id]='.$brand->id.'&filter[is_active]=true')
            ->assertOk();

        Excel::assertDownloaded('productos-'.now()->format('Y-m-d').'.xlsx', function (QueryExport $export) use ($inside) {
            $rows = $export->query()->get();
            $mapped = $export->map($rows->first());

            return $rows->count() === 1
                && $rows->first()->id === $inside->id
                && in_array('Clave SAT de producto', $export->headings(), true)
                && in_array('41116105', $mapped, true)
                && $mapped[0] === 'EXP-0001';
        });
    }

    public function test_las_ordenes_de_compra_se_exportan_con_los_filtros(): void
    {
        Excel::fake();
        $supplier = Contact::factory()->create(['name' => 'Proveedor Exportado', 'is_supplier' => true]);
        PurchaseOrder::factory()->create(['contact_id' => $supplier->id, 'status' => 'approved', 'total_amount' => 1160]);
        PurchaseOrder::factory()->create(['contact_id' => $supplier->id, 'status' => 'cancelled']);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->get('/api/v1/purchase-orders/export?filter[status]=approved&filter[contact]='.$supplier->id)
            ->assertOk();

        Excel::assertDownloaded('ordenes-de-compra-'.now()->format('Y-m-d').'.xlsx', function (QueryExport $export) {
            $rows = $export->query()->get();
            $mapped = $export->map($rows->first());

            return $rows->count() === 1 && $mapped[2] === 'Proveedor Exportado' && $mapped[4] === 'Aprobada' && $mapped[5] === 1160.0;
        });
    }

    public function test_un_cliente_del_portal_no_puede_exportar(): void
    {
        $customer = $this->getCustomerUser();

        $this->actingAs($customer, 'sanctum')->getJson('/api/v1/products/export')->assertForbidden();
        $this->actingAs($customer, 'sanctum')->getJson('/api/v1/purchase-orders/export')->assertForbidden();
    }
}
