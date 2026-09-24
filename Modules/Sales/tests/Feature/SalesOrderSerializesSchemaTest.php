<?php

namespace Modules\Sales\Tests\Feature;

use Modules\Branch\Models\Branch;
use Modules\Sales\Models\SalesOrder;
use Tests\TestCase;

/**
 * Guardian de la regla 8 (Resource pisa Schema) para ventas: todo lo que el
 * Schema declara debe viajar en la respuesta. E2E dev 2026-09-18: tipo de
 * orden, OC del cliente, metodo de pago y direcciones no se veian en el ERP.
 */
class SalesOrderSerializesSchemaTest extends TestCase
{
    public function test_show_exposes_every_schema_attribute_and_relationship(): void
    {
        $admin = $this->getAdminUser();
        $order = SalesOrder::factory()->create([
            'order_type' => 'order',
            'customer_po_number' => 'OC-E2E-001',
            'payment_method' => '03',
            'credit_days' => 30,
            'shipping_address' => ['street' => 'Av. Prueba 123', 'city' => 'CDMX', 'postal_code' => '01000'],
            'billing_address' => ['street' => 'Av. Fiscal 9'],
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->includePaths('branch')
            ->get("/api/v1/sales-orders/{$order->id}");

        $response->assertFetchedOne($order);
        $attributes = $response->json('data.attributes');

        foreach (['orderType', 'customerPoNumber', 'paymentMethod', 'creditDays', 'currency', 'shippingAddress', 'billingAddress', 'branchId', 'subtotalAmount', 'paymentStatus'] as $key) {
            $this->assertArrayHasKey($key, $attributes, "Falta el atributo {$key} en sales-orders");
        }
        $this->assertSame('OC-E2E-001', $attributes['customerPoNumber']);
        $this->assertSame('Av. Prueba 123', $attributes['shippingAddress']['street']);

        $relationships = array_keys($response->json('data.relationships'));
        foreach (['contact', 'quote', 'branch', 'items', 'shipments'] as $rel) {
            $this->assertContains($rel, $relationships, "Falta la relacion {$rel} en sales-orders");
        }
        $response->assertIsIncluded('branches', Branch::main());
    }

    public function test_admin_can_update_shipping_address(): void
    {
        $admin = $this->getAdminUser();
        $order = SalesOrder::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('sales-orders')
            ->withData([
                'type' => 'sales-orders',
                'id' => (string) $order->id,
                'attributes' => ['shippingAddress' => ['street' => 'Nueva 1', 'city' => 'Toluca']],
            ])
            ->patch("/api/v1/sales-orders/{$order->id}");

        $response->assertFetchedOne($order);
        $this->assertSame('Toluca', $order->fresh()->shipping_address['city']);
    }
}
