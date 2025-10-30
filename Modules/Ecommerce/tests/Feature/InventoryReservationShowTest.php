<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\Ecommerce\Models\InventoryReservation;

class InventoryReservationShowTest extends TestCase
{
    public function test_admin_can_view_inventory_reservation(): void
    {
        $admin = $this->getAdminUser();
        $reservation = InventoryReservation::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-reservations')
            ->get('/api/v1/inventory-reservations/' . $reservation->id);

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'type' => 'inventory-reservations',
                'id' => (string) $reservation->id,
            ]
        ]);
    }

    public function test_tech_user_can_view_inventory_reservation(): void
    {
        $tech = $this->getTechUser();
        $reservation = InventoryReservation::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('inventory-reservations')
            ->get('/api/v1/inventory-reservations/' . $reservation->id);

        $response->assertOk();
    }

    public function test_guest_cannot_view_inventory_reservation(): void
    {
        $reservation = InventoryReservation::factory()->create();

        $response = $this->jsonApi()
            ->expects('inventory-reservations')
            ->get('/api/v1/inventory-reservations/' . $reservation->id);

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_inventory_reservation(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-reservations')
            ->get('/api/v1/inventory-reservations/999999');

        $response->assertStatus(404);
    }
}
