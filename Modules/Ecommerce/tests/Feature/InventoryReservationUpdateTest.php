<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\InventoryReservation;

class InventoryReservationUpdateTest extends TestCase
{
    public function test_admin_can_update_inventory_reservation(): void
    {
        $admin = $this->getAdminUser();
        $reservation = InventoryReservation::factory()->create(['status' => 'active']);

        $data = [
            'type' => 'inventory-reservations',
            'id' => (string) $reservation->id,
            'attributes' => [
                'status' => 'released',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-reservations')
            ->withData($data)
            ->patch('/api/v1/inventory-reservations/' . $reservation->id);

        $response->assertOk();
        $this->assertDatabaseHas('inventory_reservations', [
            'id' => $reservation->id,
            'status' => 'released',
        ]);
    }

    public function test_customer_cannot_update_inventory_reservation(): void
    {
        $customer = $this->getCustomerUser();
        $reservation = InventoryReservation::factory()->create();

        $data = [
            'type' => 'inventory-reservations',
            'id' => (string) $reservation->id,
            'attributes' => [
                'status' => 'released',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('inventory-reservations')
            ->withData($data)
            ->patch('/api/v1/inventory-reservations/' . $reservation->id);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_inventory_reservation(): void
    {
        $reservation = InventoryReservation::factory()->create();

        $data = [
            'type' => 'inventory-reservations',
            'id' => (string) $reservation->id,
            'attributes' => [
                'status' => 'released',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('inventory-reservations')
            ->withData($data)
            ->patch('/api/v1/inventory-reservations/' . $reservation->id);

        $response->assertStatus(401);
    }
}
