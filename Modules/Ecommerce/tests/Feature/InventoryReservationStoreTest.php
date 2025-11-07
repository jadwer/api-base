<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Inventory\Models\Stock;
use Modules\Product\Models\Product;
use Modules\Inventory\Models\Warehouse;

class InventoryReservationStoreTest extends TestCase
{
    public function test_admin_can_create_inventory_reservation(): void
    {
        $admin = $this->getAdminUser();
        $session = CheckoutSession::factory()->create();
        $stock = Stock::factory()->create();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $data = [
            'type' => 'inventory-reservations',
            'attributes' => [
                'checkoutSessionId' => $session->id,
                'stock_id' => $stock->id,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'quantityReserved' => 5,
                'status' => 'active',
                'expiresAt' => now()->addHours(2)->toIso8601String(),
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-reservations')
            ->withData($data)
            ->post('/api/v1/inventory-reservations');

        $response->assertCreated();
        $this->assertDatabaseHas('inventory_reservations', [
            'checkoutSessionId' => $session->id,
            'quantity_reserved' => 5,
            'status' => 'active',
        ]);
    }

    public function test_customer_cannot_create_inventory_reservation(): void
    {
        $customer = $this->getCustomerUser();
        $session = CheckoutSession::factory()->create();

        $data = [
            'type' => 'inventory-reservations',
            'attributes' => [
                'checkoutSessionId' => $session->id,
                'quantityReserved' => 5,
                'status' => 'active',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('inventory-reservations')
            ->withData($data)
            ->post('/api/v1/inventory-reservations');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_inventory_reservation(): void
    {
        $session = CheckoutSession::factory()->create();

        $data = [
            'type' => 'inventory-reservations',
            'attributes' => [
                'checkoutSessionId' => $session->id,
                'quantityReserved' => 5,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('inventory-reservations')
            ->withData($data)
            ->post('/api/v1/inventory-reservations');

        $response->assertStatus(401);
    }
}
