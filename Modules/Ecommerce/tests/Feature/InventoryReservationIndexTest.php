<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\InventoryReservation;

class InventoryReservationIndexTest extends TestCase
{
    public function test_admin_can_list_inventory_reservations(): void
    {
        $admin = $this->getAdminUser();
        InventoryReservation::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-reservations')
            ->get('/api/v1/inventory-reservations');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'checkoutSessionId',
                        'productId',
                        'warehouseId',
                        'quantityReserved',
                        'status',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_filter_inventory_reservations_by_status(): void
    {
        $admin = $this->getAdminUser();
        InventoryReservation::factory()->create(['status' => 'active']);
        InventoryReservation::factory()->create(['status' => 'released']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-reservations')
            ->get('/api/v1/inventory-reservations?filter[status]=active');

        $response->assertOk();
    }

    public function test_tech_user_can_list_inventory_reservations(): void
    {
        $tech = $this->getTechUser();
        InventoryReservation::factory()->count(2)->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('inventory-reservations')
            ->get('/api/v1/inventory-reservations');

        $response->assertOk();
    }

    public function test_customer_cannot_list_all_inventory_reservations(): void
    {
        $customer = $this->getCustomerUser();
        InventoryReservation::factory()->count(2)->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('inventory-reservations')
            ->get('/api/v1/inventory-reservations');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_inventory_reservations(): void
    {
        InventoryReservation::factory()->count(2)->create();

        $response = $this->jsonApi()
            ->expects('inventory-reservations')
            ->get('/api/v1/inventory-reservations');

        $response->assertStatus(401);
    }
}
