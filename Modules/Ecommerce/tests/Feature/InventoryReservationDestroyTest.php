<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\InventoryReservation;

class InventoryReservationDestroyTest extends TestCase
{
    public function test_admin_can_delete_inventory_reservation(): void
    {
        $admin = $this->getAdminUser();
        $reservation = InventoryReservation::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-reservations')
            ->delete('/api/v1/inventory-reservations/' . $reservation->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('inventory_reservations', ['id' => $reservation->id]);
    }

    public function test_customer_cannot_delete_inventory_reservation(): void
    {
        $customer = $this->getCustomerUser();
        $reservation = InventoryReservation::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('inventory-reservations')
            ->delete('/api/v1/inventory-reservations/' . $reservation->id);

        $response->assertStatus(403);
        $this->assertDatabaseHas('inventory_reservations', ['id' => $reservation->id]);
    }

    public function test_guest_cannot_delete_inventory_reservation(): void
    {
        $reservation = InventoryReservation::factory()->create();

        $response = $this->jsonApi()
            ->expects('inventory-reservations')
            ->delete('/api/v1/inventory-reservations/' . $reservation->id);

        $response->assertStatus(401);
        $this->assertDatabaseHas('inventory_reservations', ['id' => $reservation->id]);
    }

    public function test_returns_404_when_deleting_nonexistent_inventory_reservation(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-reservations')
            ->delete('/api/v1/inventory-reservations/999999');

        $response->assertStatus(404);
    }
}
