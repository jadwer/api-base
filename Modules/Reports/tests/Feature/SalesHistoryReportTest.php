<?php

namespace Modules\Reports\Tests\Feature;

use Tests\TestCase;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Product\Models\Product;
use Modules\Product\Models\Category;
use Modules\Product\Models\Brand;
use Modules\Contacts\Models\Contact;
use Modules\User\Models\User;

/**
 * Historico de Ventas (v1) Feature Tests
 *
 * El TestDatabaseSeeder siembra ordenes dentro de la ventana del reporte,
 * asi que los tests de totales usan un contact_id propio (dataset controlado)
 * o miden baseline y comparan el delta exacto (patron SalesOrderReportsTest).
 */
class SalesHistoryReportTest extends TestCase
{
    private $admin;
    private $contact;
    private $seller;
    private $productWithIva;
    private $productWithoutIva;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->getAdminUser();
        $this->contact = Contact::factory()->customer()->create(['name' => 'Cliente Historico']);
        $this->seller = User::factory()->create(['name' => 'Vendedor Historico']);

        $this->productWithIva = Product::factory()->create([
            'cost' => 50,
            'price' => 100,
            'iva' => true,
        ]);
        $this->productWithoutIva = Product::factory()->create([
            'cost' => 30,
            'price' => 80,
            'iva' => false,
        ]);
    }

    /**
     * Crea una orden con montos conocidos y un item de 2 x $100 (costo $50).
     */
    private function makeOrder(array $orderAttrs = [], array $itemAttrs = []): SalesOrder
    {
        $order = SalesOrder::factory()->create(array_merge([
            'contact_id' => $this->contact->id,
            'assigned_to' => $this->seller->id,
            'status' => 'confirmed',
            'order_date' => now()->toDateString(),
            'subtotal' => 200.00,
            'discount_total' => 20.00,
            'tax_amount' => 28.80,
            'total_amount' => 208.80,
            'currency' => 'MXN',
        ], $orderAttrs));

        SalesOrderItem::factory()->create(array_merge([
            'sales_order_id' => $order->id,
            'product_id' => $this->productWithIva->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'discount' => 0,
            'total' => 200.00,
        ], $itemAttrs));

        return $order;
    }

    private function getReport(string $query = ''): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/sales-history' . ($query ? '?' . $query : ''));
    }

    private function controlledQuery(string $extra = ''): string
    {
        $base = 'contact_id=' . $this->contact->id
            . '&start_date=' . now()->subDay()->toDateString()
            . '&end_date=' . now()->toDateString();

        return $extra ? $base . '&' . $extra : $base;
    }

    // ========================================================================
    // ESTRUCTURA Y CALCULOS BASE
    // ========================================================================

    public function test_returns_expected_row_structure_and_values(): void
    {
        $order = $this->makeOrder();

        $response = $this->getReport($this->controlledQuery());

        $response->assertOk();
        $response->assertJsonStructure([
            'period' => ['start_date', 'end_date'],
            'report_type',
            'currency',
            'group_by',
            'data' => [
                '*' => [
                    'order_id', 'order_number', 'date', 'customer_name',
                    'salesperson_name', 'cost', 'profit', 'subtotal',
                    'discount', 'iva', 'total', 'status',
                ],
            ],
            'totals' => ['cost', 'profit', 'subtotal', 'discount', 'iva', 'total', 'count'],
            'meta' => ['page' => ['number', 'size', 'total', 'last_page']],
        ]);

        $rows = $response->json('data');
        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertEquals($order->id, $row['order_id']);
        $this->assertEquals($order->order_number, $row['order_number']);
        $this->assertEquals('Cliente Historico', $row['customer_name']);
        $this->assertEquals('Vendedor Historico', $row['salesperson_name']);

        // Costo = 2 x 50 (producto), utilidad = subtotal 200 - costo 100
        $this->assertEqualsWithDelta(100.00, $row['cost'], 0.01);
        $this->assertEqualsWithDelta(100.00, $row['profit'], 0.01);
        $this->assertEqualsWithDelta(200.00, $row['subtotal'], 0.01);
        $this->assertEqualsWithDelta(20.00, $row['discount'], 0.01);
        $this->assertEqualsWithDelta(28.80, $row['iva'], 0.01);
        $this->assertEqualsWithDelta(208.80, $row['total'], 0.01);
        $this->assertEquals('confirmed', $row['status']);
    }

    public function test_totals_are_exact_for_controlled_dataset(): void
    {
        // Orden A: subtotal 200, desc 20, iva 28.80, total 208.80, costo 100
        $this->makeOrder();

        // Orden B: subtotal 300, desc 0, iva 48, total 348, costo 3 x 50 = 150
        $this->makeOrder(
            ['subtotal' => 300.00, 'discount_total' => 0, 'tax_amount' => 48.00, 'total_amount' => 348.00],
            ['quantity' => 3, 'unit_price' => 100.00, 'total' => 300.00]
        );

        $response = $this->getReport($this->controlledQuery());

        $response->assertOk();
        $totals = $response->json('totals');

        $this->assertEquals(2, $totals['count']);
        $this->assertEqualsWithDelta(250.00, $totals['cost'], 0.01);
        $this->assertEqualsWithDelta(250.00, $totals['profit'], 0.01);
        $this->assertEqualsWithDelta(500.00, $totals['subtotal'], 0.01);
        $this->assertEqualsWithDelta(20.00, $totals['discount'], 0.01);
        $this->assertEqualsWithDelta(76.80, $totals['iva'], 0.01);
        $this->assertEqualsWithDelta(556.80, $totals['total'], 0.01);
    }

    public function test_totals_delta_against_baseline_on_default_period(): void
    {
        // Baseline: periodo default (mes actual), sin filtros
        $baseline = $this->getReport()->assertOk()->json('totals');

        $this->makeOrder();

        $after = $this->getReport()->assertOk()->json('totals');

        $this->assertEquals($baseline['count'] + 1, $after['count']);
        $this->assertEqualsWithDelta($baseline['total'] + 208.80, $after['total'], 0.01);
        $this->assertEqualsWithDelta($baseline['subtotal'] + 200.00, $after['subtotal'], 0.01);
        $this->assertEqualsWithDelta($baseline['cost'] + 100.00, $after['cost'], 0.01);
        $this->assertEqualsWithDelta($baseline['profit'] + 100.00, $after['profit'], 0.01);
        $this->assertEqualsWithDelta($baseline['iva'] + 28.80, $after['iva'], 0.01);
        $this->assertEqualsWithDelta($baseline['discount'] + 20.00, $after['discount'], 0.01);
    }

    // ========================================================================
    // FILTROS
    // ========================================================================

    public function test_filters_by_contact(): void
    {
        $otherContact = Contact::factory()->customer()->create();

        $this->makeOrder();
        $this->makeOrder(['contact_id' => $otherContact->id]);

        $response = $this->getReport($this->controlledQuery());

        $response->assertOk();
        $this->assertEquals(1, $response->json('totals.count'));
        $this->assertEquals('Cliente Historico', $response->json('data.0.customer_name'));
    }

    public function test_filters_by_salesperson(): void
    {
        $otherSeller = User::factory()->create(['name' => 'Otro Vendedor']);

        $this->makeOrder();
        $this->makeOrder(['assigned_to' => $otherSeller->id]);

        $response = $this->getReport($this->controlledQuery('assigned_to=' . $this->seller->id));

        $response->assertOk();
        $this->assertEquals(1, $response->json('totals.count'));
        $this->assertEquals('Vendedor Historico', $response->json('data.0.salesperson_name'));
    }

    public function test_filters_by_product(): void
    {
        $this->makeOrder();
        $this->makeOrder([], ['product_id' => $this->productWithoutIva->id]);

        $response = $this->getReport($this->controlledQuery('product_id=' . $this->productWithIva->id));

        $response->assertOk();
        $this->assertEquals(1, $response->json('totals.count'));
    }

    public function test_filters_by_category(): void
    {
        $category = Category::factory()->create();
        $productInCategory = Product::factory()->create([
            'cost' => 10,
            'category_id' => $category->id,
        ]);

        $orderInCategory = $this->makeOrder([], ['product_id' => $productInCategory->id]);
        $this->makeOrder(); // otro producto, otra categoria

        $response = $this->getReport($this->controlledQuery('category_id=' . $category->id));

        $response->assertOk();
        $this->assertEquals(1, $response->json('totals.count'));
        $this->assertEquals($orderInCategory->id, $response->json('data.0.order_id'));
    }

    public function test_filters_by_brand(): void
    {
        $brand = Brand::factory()->create();
        $productOfBrand = Product::factory()->create([
            'cost' => 10,
            'brand_id' => $brand->id,
        ]);

        $orderOfBrand = $this->makeOrder([], ['product_id' => $productOfBrand->id]);
        $this->makeOrder(); // otra marca

        $response = $this->getReport($this->controlledQuery('brand_id=' . $brand->id));

        $response->assertOk();
        $this->assertEquals(1, $response->json('totals.count'));
        $this->assertEquals($orderOfBrand->id, $response->json('data.0.order_id'));
    }

    public function test_filters_by_multi_status_csv(): void
    {
        $this->makeOrder(['status' => 'confirmed']);
        $this->makeOrder(['status' => 'delivered']);
        $this->makeOrder(['status' => 'draft']);

        $response = $this->getReport($this->controlledQuery('status=confirmed,delivered'));

        $response->assertOk();
        $this->assertEquals(2, $response->json('totals.count'));

        $statuses = collect($response->json('data'))->pluck('status')->unique()->sort()->values()->all();
        $this->assertEquals(['confirmed', 'delivered'], $statuses);
    }

    public function test_rejects_invalid_status_value(): void
    {
        $response = $this->getReport($this->controlledQuery('status=confirmed,bogus'));

        $response->assertStatus(422);
    }

    public function test_filters_by_currency(): void
    {
        $this->makeOrder(['currency' => 'MXN']);
        $usdOrder = $this->makeOrder(['currency' => 'USD']);

        $response = $this->getReport($this->controlledQuery('currency=USD'));

        $response->assertOk();
        $this->assertEquals(1, $response->json('totals.count'));
        $this->assertEquals($usdOrder->id, $response->json('data.0.order_id'));
    }

    public function test_filters_by_iva(): void
    {
        $orderWithIva = $this->makeOrder(); // producto con iva=true
        $orderWithoutIva = $this->makeOrder([], ['product_id' => $this->productWithoutIva->id]);

        $withIva = $this->getReport($this->controlledQuery('iva=1'));
        $withIva->assertOk();
        $this->assertEquals(1, $withIva->json('totals.count'));
        $this->assertEquals($orderWithIva->id, $withIva->json('data.0.order_id'));

        $withoutIva = $this->getReport($this->controlledQuery('iva=0'));
        $withoutIva->assertOk();
        $this->assertEquals(1, $withoutIva->json('totals.count'));
        $this->assertEquals($orderWithoutIva->id, $withoutIva->json('data.0.order_id'));
    }

    public function test_filters_by_order_number_like(): void
    {
        $target = $this->makeOrder(['order_number' => 'SO-HIST-12345']);
        $this->makeOrder(['order_number' => 'SO-OTHER-99999']);

        $response = $this->getReport($this->controlledQuery('order_number=HIST'));

        $response->assertOk();
        $this->assertEquals(1, $response->json('totals.count'));
        $this->assertEquals($target->order_number, $response->json('data.0.order_number'));
    }

    // ========================================================================
    // AGRUPACION
    // ========================================================================

    public function test_group_by_status_aggregates_correctly(): void
    {
        $this->makeOrder(['status' => 'confirmed']); // total 208.80
        $this->makeOrder(['status' => 'confirmed']); // total 208.80
        $this->makeOrder(['status' => 'draft']);     // total 208.80

        $response = $this->getReport($this->controlledQuery('group_by=status'));

        $response->assertOk();
        $grouped = $response->json('grouped');
        $this->assertIsArray($grouped);
        $this->assertCount(2, $grouped);

        $confirmed = collect($grouped)->firstWhere('group_key', 'confirmed');
        $this->assertNotNull($confirmed);
        $this->assertEquals(2, $confirmed['count']);
        $this->assertEqualsWithDelta(417.60, $confirmed['total'], 0.01);
        $this->assertEqualsWithDelta(200.00, $confirmed['cost'], 0.01);
        $this->assertEqualsWithDelta(200.00, $confirmed['profit'], 0.01);

        $draft = collect($grouped)->firstWhere('group_key', 'draft');
        $this->assertNotNull($draft);
        $this->assertEquals(1, $draft['count']);
        $this->assertEqualsWithDelta(208.80, $draft['total'], 0.01);
    }

    public function test_group_by_customer_aggregates_correctly(): void
    {
        $otherContact = Contact::factory()->customer()->create(['name' => 'Otro Cliente']);

        $this->makeOrder();
        $this->makeOrder();
        $this->makeOrder(['contact_id' => $otherContact->id]);

        // Sin filtro de contacto: acotar por vendedor propio para dataset controlado
        $query = 'assigned_to=' . $this->seller->id
            . '&start_date=' . now()->subDay()->toDateString()
            . '&end_date=' . now()->toDateString()
            . '&group_by=customer';

        $response = $this->getReport($query);

        $response->assertOk();
        $grouped = $response->json('grouped');
        $this->assertCount(2, $grouped);

        $main = collect($grouped)->firstWhere('group_key', (string) $this->contact->id);
        $this->assertNotNull($main);
        $this->assertEquals('Cliente Historico', $main['group_label']);
        $this->assertEquals(2, $main['count']);
        $this->assertEqualsWithDelta(417.60, $main['total'], 0.01);

        $other = collect($grouped)->firstWhere('group_key', (string) $otherContact->id);
        $this->assertNotNull($other);
        $this->assertEquals('Otro Cliente', $other['group_label']);
        $this->assertEquals(1, $other['count']);
    }

    public function test_grouped_is_absent_when_group_by_none(): void
    {
        $this->makeOrder();

        $response = $this->getReport($this->controlledQuery());

        $response->assertOk();
        $this->assertArrayNotHasKey('grouped', $response->json());
    }

    public function test_rejects_invalid_group_by(): void
    {
        $response = $this->getReport($this->controlledQuery('group_by=warehouse'));

        $response->assertStatus(422);
    }

    // ========================================================================
    // PAGINACION
    // ========================================================================

    public function test_pagination_slices_data_but_totals_stay_global(): void
    {
        $this->makeOrder();
        $this->makeOrder();
        $this->makeOrder();

        $response = $this->getReport($this->controlledQuery('page[number]=1&page[size]=2'));

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));

        // Totales SIEMPRE del conjunto completo filtrado
        $this->assertEquals(3, $response->json('totals.count'));
        $this->assertEqualsWithDelta(626.40, $response->json('totals.total'), 0.01);

        $meta = $response->json('meta.page');
        $this->assertEquals(1, $meta['number']);
        $this->assertEquals(2, $meta['size']);
        $this->assertEquals(3, $meta['total']);
        $this->assertEquals(2, $meta['last_page']);

        // Segunda pagina: 1 registro restante, mismos totales
        $page2 = $this->getReport($this->controlledQuery('page[number]=2&page[size]=2'));
        $page2->assertOk();
        $this->assertCount(1, $page2->json('data'));
        $this->assertEquals(3, $page2->json('totals.count'));
    }

    public function test_page_size_defaults_to_25_and_caps_at_100(): void
    {
        $this->makeOrder();

        $default = $this->getReport($this->controlledQuery());
        $default->assertOk();
        $this->assertEquals(25, $default->json('meta.page.size'));

        $tooBig = $this->getReport($this->controlledQuery('page[size]=101'));
        $tooBig->assertStatus(422);
    }

    // ========================================================================
    // AUTORIZACION
    // ========================================================================

    public function test_guest_cannot_access_report(): void
    {
        $response = $this->getJson('/api/v1/reports/sales-history');

        $response->assertUnauthorized();
    }

    public function test_customer_without_permission_gets_403(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/reports/sales-history');

        $response->assertForbidden();
    }

    public function test_user_with_permission_can_access_report(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('reports.sales-history.index');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/reports/sales-history');

        $response->assertOk();
    }

    // ========================================================================
    // EXPORT
    // ========================================================================

    public function test_can_export_sales_history_csv(): void
    {
        $this->makeOrder();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->get('/api/v1/reports/sales-history/export?format=csv&' . $this->controlledQuery());

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_export_requires_format(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/sales-history/export');

        $response->assertStatus(422);
    }

    public function test_guest_cannot_export(): void
    {
        $response = $this->getJson('/api/v1/reports/sales-history/export?format=csv');

        $response->assertUnauthorized();
    }
}
