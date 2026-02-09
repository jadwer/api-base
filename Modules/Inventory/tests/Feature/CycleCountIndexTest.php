<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Inventory\Models\CycleCount;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;

class CycleCountIndexTest extends TestCase
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

    public function test_admin_can_list_cycle_counts(): void
    {
        $admin = $this->getAdminUser();

        CycleCount::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->get('/api/v1/cycle-counts');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'countNumber',
                        'scheduledDate',
                        'status',
                        'abcClass',
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_filter_cycle_counts_by_status(): void
    {
        $admin = $this->getAdminUser();

        CycleCount::factory()->scheduled()->create();
        CycleCount::factory()->scheduled()->create();
        CycleCount::factory()->completed()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->filter(['status' => 'scheduled'])
            ->get('/api/v1/cycle-counts');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_can_filter_cycle_counts_by_abc_class(): void
    {
        $admin = $this->getAdminUser();

        CycleCount::factory()->classA()->create();
        CycleCount::factory()->classB()->create();
        CycleCount::factory()->classC()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->filter(['abcClass' => 'A'])
            ->get('/api/v1/cycle-counts');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_unauthorized_user_cannot_list_cycle_counts(): void
    {
        $response = $this->jsonApi()
            ->expects('cycle-counts')
            ->get('/api/v1/cycle-counts');

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_list_cycle_counts(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('cycle-counts')
            ->get('/api/v1/cycle-counts');

        $response->assertForbidden();
    }
}
