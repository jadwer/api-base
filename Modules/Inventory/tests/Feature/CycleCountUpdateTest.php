<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\CycleCount;

class CycleCountUpdateTest extends TestCase
{
    protected function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    protected function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    protected function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_update_cycle_count(): void
    {
        $admin = $this->getAdminUser();
        $cycleCount = CycleCount::factory()->scheduled()->create([
            'notes' => 'Original notes',
        ]);

        $data = [
            'type' => 'cycle-counts',
            'id' => (string) $cycleCount->id,
            'attributes' => [
                'notes' => 'Updated notes',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->withData($data)
            ->patch('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertOk();
        $this->assertDatabaseHas('cycle_counts', [
            'id' => $cycleCount->id,
            'notes' => 'Updated notes',
        ]);
    }

    public function test_el_estatus_no_se_cambia_por_patch(): void
    {
        $admin = $this->getAdminUser();
        $cycleCount = CycleCount::factory()->scheduled()->create();

        $response = $this->actingAs($admin, 'sanctum')->jsonApi()->expects('cycle-counts')
            ->withData(['type' => 'cycle-counts', 'id' => (string) $cycleCount->id, 'attributes' => ['status' => 'in_progress']])
            ->patch('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertUnprocessable();
        $this->assertStringContainsString('acciones Iniciar', $response->getContent());
        $this->assertDatabaseHas('cycle_counts', ['id' => $cycleCount->id, 'status' => 'scheduled']);
    }

    public function test_lo_contado_no_se_captura_por_patch(): void
    {
        $admin = $this->getAdminUser();
        $cycleCount = CycleCount::factory()->inProgress()->create();

        $response = $this->actingAs($admin, 'sanctum')->jsonApi()->expects('cycle-counts')
            ->withData(['type' => 'cycle-counts', 'id' => (string) $cycleCount->id, 'attributes' => ['countedQuantity' => 150.5, 'varianceQuantity' => 3]])
            ->patch('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertUnprocessable();
        $this->assertStringContainsString('Registrar conteo', $response->getContent());
    }

    public function test_un_conteo_en_proceso_no_cambia_de_producto(): void
    {
        $admin = $this->getAdminUser();
        $cycleCount = CycleCount::factory()->inProgress()->create();
        $other = \Modules\Product\Models\Product::factory()->create();

        $this->actingAs($admin, 'sanctum')->jsonApi()->expects('cycle-counts')
            ->withData(['type' => 'cycle-counts', 'id' => (string) $cycleCount->id, 'attributes' => ['productId' => $other->id]])
            ->patch('/api/v1/cycle-counts/' . $cycleCount->id)
            ->assertUnprocessable();

        // Las notas si se editan
        $this->actingAs($admin, 'sanctum')->jsonApi()->expects('cycle-counts')
            ->withData(['type' => 'cycle-counts', 'id' => (string) $cycleCount->id, 'attributes' => ['notes' => 'Revisar anaquel 3']])
            ->patch('/api/v1/cycle-counts/' . $cycleCount->id)
            ->assertOk();
    }

    public function test_unauthorized_user_cannot_update_cycle_count(): void
    {
        $cycleCount = CycleCount::factory()->create();

        $data = [
            'type' => 'cycle-counts',
            'id' => (string) $cycleCount->id,
            'attributes' => [
                'notes' => 'Updated notes',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cycle-counts')
            ->withData($data)
            ->patch('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_update_cycle_count(): void
    {
        $customer = $this->getCustomerUser();
        $cycleCount = CycleCount::factory()->create();

        $data = [
            'type' => 'cycle-counts',
            'id' => (string) $cycleCount->id,
            'attributes' => [
                'notes' => 'Updated notes',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->withData($data)
            ->patch('/api/v1/cycle-counts/' . $cycleCount->id);

        $response->assertForbidden();
    }
}
